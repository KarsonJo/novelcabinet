<?php

namespace KarsonJo\BookPost {

    use App\View\Composers\BookChapter;
    use KarsonJo\BookPost\SqlQuery\BookQuery;
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

            if ($public)
                $status = 'publish';
            else
                $status = null;

            $results = BookQuery::bookHierarchy($book, $status);

            if (!$results)
                return false;

            // 缓存文章基本信息
            $ids = array_map(fn ($result) => $result->ID, $results);

            CacheBuilder::create()
                ->cachePosts($ids)
                ->withoutPostContent()
                ->cache();

            $this->contents[$book] = []; //书结点
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

            if ($ckey == count($this->getVolumeChapters($vkey)) - 1) {
                if ($vkey == count($this->getVolumes()) - 1)
                    return null; //没有了
                $next_volume = $this->getVolumeChapters($vkey + 1);
                return $next_volume[0]; //下一卷第一章
            }
            return $this->getVolumeChapters($vkey)[$ckey + 1]; //这一卷下一章
        }

        public function setActiveChapter(WP_Post|int $post)
        {
            [$this->volume_index, $this->chapter_index] = $this->locateChapter($post);
        }

        /**
         * 返回一个可转化为json的通用格式
         * 保持层次结构，
         * 书结点有：{id:int , volumes:[{volume1}, {volume2}, ...]}
         * 卷结点有：{id:int , title:string, url:string, chapters:[{chapter1}, {chapter2}, ...]}
         * 章结点有：{id:int , title:string, url:string}
         * @return void 
         */
        public function toJsonArray(): array
        {
            $bookObj = [
                'id' => array_key_first($this->contents),
                'volumes' => [],
            ];

            foreach ($this->getVolumes() as $volume) {
                $volumeObj = [
                    'id' => $volume->ID,
                    'title' => $volume->post_title,
                    'url' => get_permalink($volume->ID),
                    'chapters' => [],
                ];

                foreach ($this[$volume->ID] as $chapter)
                    $volumeObj['chapters'][] = [
                        'id' => $chapter->ID,
                        'title' => $chapter->post_title,
                        'url' => get_permalink($chapter->ID),
                    ];

                $bookObj['volumes'][] = $volumeObj;
            }
            return $bookObj;
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
