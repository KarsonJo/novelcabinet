<?php

namespace KarsonJo\BookPost {

    use App\View\Composers\BookChapter;
    use AppendIterator;
    use Iterator;
    use KarsonJo\BookPost\SqlQuery\BookQuery;
    use KarsonJo\Utilities\Algorithms\StringAlgorithms;
    use KarsonJo\Utilities\PostCache\CacheBuilder;
    use WP_Post;

    /**
     * 书的目录，形如：
     * [书1] => [卷1, 卷2],
     * [卷1] => [章1, 章2],
     * [卷2] => [章3, 章4],
     */
    class BookContents implements \ArrayAccess, \Iterator, \Countable
    {
        private array $contents = [];

        /**
         * 目前所在卷
         */
        private int $volume_index = -1;
        /**
         * 目前所在章节
         */
        private int $chapter_index = -1;

        /**
         * 名称到ID的映射
         * @var null|int[]
         */
        private ?array $_nameIdMap = null;
        private ?array $_volumeNameIdMap = null;
        private ?array $_chapterNameIdMap = null;

        /**
         * 
         * @var null|BookContentsItem[]
         */
        private ?array $_idContentMap = null;

        /**
         * 构建目录，如果传入章节id，还会设置目录的活跃位置
         * @param $post 书、卷、章节的wp对象或id
         */
        public function __construct(WP_Post|int $post, bool $public = true)
        {
            $this->loadContents($post, $public);
        }

        /**
         * 构建目录，如果传入章节id，还会设置目录的活跃位置
         * @param WP_Post|int $post 书、卷、章节的wp对象或id
         * @param array|string $public 是否公开，如果是，只会查询已公开章节
         */
        public function loadContents(WP_Post|int $post, bool $public)
        {

            $book = BookQuery::rootPost($post)->ID;
            $this->_nameIdMap = null;
            $this->_volumeNameIdMap = null;
            $this->_chapterNameIdMap = null;
            $this->_idContentMap = null;

            if ($public)
                $status = 'publish';
            else
                $status = null;

            $results = BookQuery::bookHierarchy($book, $status);
            $this->contents[$book] = []; //书结点

            if (!$results)
                return false;

            // 缓存文章基本信息
            $ids = array_map(fn ($result) => $result->ID, $results);

            CacheBuilder::create()
                ->cachePosts($ids)
                ->withoutPostContent()
                ->cache();

            foreach ($results as $result) {
                if ($result->parent_id == $book && !array_key_exists($result->ID, $this->contents)) //是卷
                    $this->contents[$result->ID] = []; //新的卷结点
                $this->contents[$result->parent_id][] = new BookContentsItem($result); //加到最后
            }

            $this->setActiveChapter($post);
        }

        // /**
        //  * 在数据库中按指定顺序更新书本的排序
        //  * @return void 
        //  */
        // public function updateContentsOrder()
        // {
        // }

        // protected function getAllBookPosts()
        // {
        //     $volumeIds = array_map(fn ($item) => $item->ID, reset($this->contents)); // 卷
        //     $bookPosts = array_merge($volumeIds, [key($this->contents)]); // 卷 + 书
        //     foreach ($volumeIds as $volumeId) {
        //         $bookPosts = array_merge($$bookPosts, array_map(fn ($item) => $item->ID, $this->contents[$volumeId]));
        //     }
        // }

        /**
         * 目录是否为空
         * @return bool
         */
        public function bookEmpty(): bool
        {
            return !$this->contents || !$this->contents[array_key_first($this->contents)];
        }

        /**
         * 书的第一章
         */
        public function getFirstChapter(): ?BookContentsItem
        {
            if ($this->bookEmpty())
                return null;

            $first_volume = $this->contents[array_key_first($this->contents)][0]->ID;
            if (!$this->contents[$first_volume])
                return null;

            return $this->contents[$first_volume][0];
        }

        /**
         * @return BookContentsItem[] 所有卷
         */
        public function getVolumes(): array
        {
            if ($this->bookEmpty())
                return [];
            return $this->contents[array_key_first($this->contents)];
        }

        /**
         * 返回一卷的所有章节（按目录顺序）
         * @param int $vkey 卷在目录的索引
         * @return BookContentsItem[]
         */
        private function getVolumeChapters(int $vkey): array
        {
            return $this->contents[$this->contents[array_key_first($this->contents)][$vkey]->ID];
        }


        /**
         * 返回给定文章在目录中的索引位置，O(n)操作
         * @param WP_Post|int $post 书的卷或章
         * @return array [卷索引，章索引]二元组，如某项不符合，返回-1
         */
        public function locateChapter(WP_Post|int $post): array
        {
            if ($post instanceof WP_Post)
                $post = $post->ID;

            foreach ($this->getVolumes() as $vkey => $volume) {
                if ($volume->ID == $post)
                    return [$vkey, -1];
                foreach ($this->contents[$volume->ID] as $ckey => $chapter)
                    if ($chapter->ID == $post)
                        return [$vkey, $ckey];
            }
            return [-1, -1];
        }

        private function posOrDefault(array $pos): array
        {
            return $pos ? $pos : [$this->volume_index, $this->chapter_index];
        }

        /**
         * @param array $curr [卷索引，章索引]二元组，当前章节在目录的位置，缺省为目录的记录位置
         * @return BookContentsItem|null 上一章的目录对象，如没有，返回null
         */
        public function previousChapter(array $curr = []): BookContentsItem|null
        {
            [$vkey, $ckey] = $this->posOrDefault($curr);

            if ($vkey < 0 || $ckey < 0)
                return null;

            if ($ckey == 0) {
                if ($vkey == 0)
                    return null; //没有了
                $pre_volume = $this->getVolumeChapters($vkey - 1);
                return $pre_volume[count($pre_volume) - 1]; //上一卷最后一章
            }
            return $this->getVolumeChapters($vkey)[$ckey - 1]; //这一卷上一章
        }

        /**
         * @param array $curr [卷索引，章索引]二元组，当前章节在目录的位置，缺省为目录的记录位置
         * @return BookContentsItem|null 下一章的目录对象，如没有，返回null
         */
        public function nextChapter(array $curr = []): BookContentsItem|null
        {
            [$vkey, $ckey] = $this->posOrDefault($curr);

            if ($vkey < 0 || $ckey < 0)
                return null;
            print_r(1);
            if ($ckey == count($this->getVolumeChapters($vkey)) - 1) {
                if ($vkey == count($this->getVolumes()) - 1)
                    return null; //没有了
                $next_volume = $this->getVolumeChapters($vkey + 1);
                return $next_volume[0]; //下一卷第一章
            }
            print_r(2);

            return $this->getVolumeChapters($vkey)[$ckey + 1]; //这一卷下一章
        }

        public function setActiveChapter(WP_Post|int $post)
        {
            [$this->volume_index, $this->chapter_index] = $this->locateChapter($post);
        }

        /**
         * 返回一个可转化为json的通用格式
         * 保持层次结构，
         * 书结点有：{id:int volumes:[{volume1}, {volume2}, ...]}
         * 卷结点有：{id:int, title:string, url:string, chapters:[{chapter1}, {chapter2}, ...]}
         * 章结点有：{id:int, title:string, url:string}
         * @param array $fields 输出的字段
         * @return void 
         */
        public function toJsonArray(array $fields = []): array
        {
            if (!$fields)
                $fields = ['id', 'title', 'url'];

            $set = [];
            foreach ($fields as $field)
                $set[$field] = 1;

            $bookObj = [
                'id' => array_key_first($this->contents),
                'volumes' => [],
            ];

            foreach ($this->getVolumes() as $volume) {
                $volumeObj = [];
                if (isset($set['id']))
                    $volumeObj['id'] = $volume->ID;
                if (isset($set['title']))
                    $volumeObj['title'] = $volume->post_title;
                if (isset($set['url']))
                    $volumeObj['url'] = get_permalink($volume->ID);
                $volumeObj['chapters'] = [];

                // $volumeObj = [
                //     'id' => $volume->ID,
                //     'title' => $volume->post_title,
                //     'url' => get_permalink($volume->ID),
                //     'chapters' => [],
                // ];

                foreach ($this[$volume->ID] as $chapter) {
                    $chapterObj = [];
                    if (isset($set['id']))
                        $chapterObj['id'] = $chapter->ID;
                    if (isset($set['title']))
                        $chapterObj['title'] = $chapter->post_title;
                    if (isset($set['url']))
                        $chapterObj['url'] = get_permalink($chapter->ID);

                    $volumeObj['chapters'][] = $chapterObj;

                    // $volumeObj['chapters'][] = [
                    //     'id' => $chapter->ID,
                    //     'title' => $chapter->post_title,
                    //     'url' => get_permalink($chapter->ID),
                    // ];
                }

                $bookObj['volumes'][] = $volumeObj;
            }
            return $bookObj;
        }

        /**
         * @param string $filter 查找的类型: volume/chapter
         * 
         */
        protected function getNameIdMap(string $filter): array
        {

            switch ($filter) {
                case 'volume':
                    if ($this->_volumeNameIdMap === null) {
                        $this->_volumeNameIdMap = [];
                        foreach ($this->getVolumes() as $volume)
                            $this->_volumeNameIdMap[$volume->post_title] = $volume->ID;
                    }
                    return $this->_volumeNameIdMap;
                case 'chapter':
                    if ($this->_chapterNameIdMap === null) {
                        $this->_chapterNameIdMap = [];
                        foreach ($this->getVolumes() as $volume) {
                            foreach ($this[$volume->ID] as $chapter)
                                $this->_chapterNameIdMap[$chapter->post_title] = $chapter->ID;
                        }
                    }
                    return $this->_chapterNameIdMap;
                default:
                    break;
            }
            return [];
        }

        /**
         * 根据名称获取文章id
         * 龟速操作，会在第一次调用时生成name=>id映射
         * @param string $type 查找的类型: volume/chapter
         * @param string $name 
         * @return int 文章ID，若不存在，返回0
         */
        public function idByName(string $type, string $name): int
        {
            $nameIdMap = $this->getNameIdMap($type);
            return $nameIdMap[$name] ?? 0;
        }

        /**
         * 在目录中查找相似的标题项
         * $threshold不要给太大，否则可能会造成长时间运算
         * @param string $type 查找的类型: volume/chapter/all
         * @param string $searched 查找的标题
         * @param int $threshold 最大允许编辑距离，与给定字符串长度的25%取小值
         * @param bool $fullScan 全程查找得到最短编辑距离 or 找到第一个符合的返回
         * @return array [$id, $distance] or [0, -1]
         */
        public function idBySimilarName(string $type, string $searched, int $threshold = 2, bool $fullScan = true): array
        {
            $strlen = strlen($searched);
            // 阈值不应该超过25%的字符串长度
            $threshold = min($threshold, intdiv($strlen, 4));
            // 精确搜索
            if ($threshold === 0) {
                $id = $this->idByName($type, $searched);
                if ($id)
                    return [$id, 0];
            }
            // 编辑距离搜索
            else {

                $resId = 0;
                $minDis = $threshold + 1; // 不会超过threshold

                $nameIdMap = $this->getNameIdMap($type);
                foreach ($nameIdMap as $name => $id) {
                    // assert(gettype($name) === "string", new \Exception($name));
                    $distance = StringAlgorithms::levenshteinWithThreshold(strval($name), $searched, $threshold);
                    if ($distance !== false) {
                        // 距离为0可以直接返回了
                        if ($distance === 0 || !$fullScan)
                            return [$id, $distance];
                        else if ($distance < $minDis) {
                            $resId = $id;
                            $minDis = $distance;
                        }
                    }
                }
            }
            if ($resId === 0)
                return [0, -1];
            else
                return [$resId, $minDis];
        }


        /**
         * id => contentsItem
         * @return BookContentsItem[] 
         */
        protected function getIdContentsMap(): array
        {
            if ($this->_idContentMap === null) {
                $this->_idContentMap = [];
                foreach ($this->getVolumes() as $volume) {
                    $this->_idContentMap[$volume->ID] = $volume;

                    foreach ($this[$volume->ID] as $chapter)
                        $this->_idContentMap[$chapter->ID] = $chapter;
                }
            }
            return $this->_idContentMap;
        }

        /**
         * 根据id获取contentsItem
         * 龟速操作，会在第一次调用时生成name=>id映射缓存
         * 如果本次访问需要使用get_post，用它就好，不需要用这个函数
         * 只有打算避免get_post该函数才有意义
         * @return null|BookContentsItem 
         */
        public function contentsItemById($id): ?BookContentsItem
        {
            $map = $this->getIdContentsMap();
            return $map[$id] ?? null;
        }

        /**
         * 目录（卷或章）中是否包含给定的id
         * @param mixed $id 
         * @return bool 
         */
        public function containsId($id): bool
        {
            return $this->contentsItemById($id) !== null;
        }


        protected int $position = 0;

        public function count(): int
        {
            return count($this->contents);
        }

        public function rewind(): void
        {
            $this->position = 0;
        }

        public function current(): string
        {
            return $this->contents[$this->position];
        }

        public function key(): int
        {
            return $this->position;
        }

        public function next(): void
        {
            ++$this->position;
        }

        public function valid(): bool
        {
            return isset($this->contents[$this->position]);
        }

        public function offsetSet($offset, $value): void
        {
            if (is_null($offset)) {
                $this->contents[] = $value;
            } else {
                $this->contents[$offset] = $value;
            }
        }

        public function offsetExists($offset): bool
        {
            return isset($this->contents[$offset]);
        }

        public function offsetUnset($offset): void
        {
            unset($this->contents[$offset]);
        }

        public function offsetGet($offset): array
        {
            return isset($this->contents[$offset]) ? $this->contents[$offset] : null;
        }
    }
}
