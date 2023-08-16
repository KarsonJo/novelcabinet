<?php

namespace KarsonJo\BookPost;

use KarsonJo\BookPost\PostCache\CacheBuilder;
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
     * 目前加载的书籍
     */
    private int $book = -1;
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
    public function __construct(WP_Post|int $post)
    {
        $this->loadContents($post);
    }

    /**
     * 构建目录，如果传入章节id，还会设置目录的活跃位置
     * @param $post 书、卷、章节的wp对象或id
     */
    public function loadContents(WP_Post|int $post)
    {

        $book = get_book_from_post($post)->ID;
        $this->book = $book;

        // if ($post instanceof WP_Post)
        //     $post = $post->ID;


        global $wpdb;
        $table_name = $wpdb->prefix . 'posts';
        // A sql query to return all post titles
        $results = $wpdb->get_results($wpdb->prepare("
        select      p2.post_parent as parent2_id,
                    p1.post_parent as parent_id,
                    p1.ID,
                    p1.post_title
        from        $table_name p1
        left join   $table_name p2 on p2.ID = p1.post_parent 
        where       %d in (p1.post_parent, p2.post_parent) 
                    and p1.post_status = 'publish'
                    and p1.post_type = %s
        order by    parent2_id, parent_id, p1.menu_order, p1.post_title;", $book, KBP_BOOK));

        if (!$results)
            return false;

        // 缓存文章基本信息
        $ids = array_map(function ($result) {
            return $result->ID;
        }, $results);
        
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

        $this->set_active_chapter($post);
    }

    /**
     * 目录是否为空
     * @return bool
     */
    public function book_empty(): bool
    {
        return !$this->contents || !$this->contents[array_key_first($this->contents)];
    }

    /**
     * 书的第一章
     */
    public function get_first_chapter(): ?BookContentsItem
    {
        if ($this->book_empty())
            return null;

        $first_volume = $this->contents[array_key_first($this->contents)][0]->ID;
        if (!$this->contents[$first_volume])
            return null;

        return $this->contents[$first_volume][0];
    }

    /**
     * @return BookContentsItem[] 所有卷
     */
    public function get_volumes(): array
    {
        if ($this->book_empty())
            return [];
        return $this->contents[array_key_first($this->contents)];
    }

    /**
     * 返回一卷的所有章节（按目录顺序）
     * @param int $vkey 卷在目录的索引
     * @return BookContentsItem[]
     */
    private function get_volume_chapters(int $vkey): array
    {
        return $this->contents[$this->contents[array_key_first($this->contents)][$vkey]->ID];
    }


    /**
     * 返回给定文章在目录中的索引位置，O(n)操作
     * @param WP_Post|int $post 书的卷或章
     * @return array [卷索引，章索引]二元组，如某项不符合，返回-1
     */
    public function locate_chapter(WP_Post|int $post): array
    {
        if ($post instanceof WP_Post)
            $post = $post->ID;

        foreach ($this->get_volumes() as $vkey => $volume) {
            if ($volume->ID == $post)
                return [$vkey, -1];
            foreach ($this->contents[$volume->ID] as $ckey => $chapter)
                if ($chapter->ID == $post)
                    return [$vkey, $ckey];
        }
        return [-1, -1];
    }

    private function pos_or_default(array $pos): array
    {
        return $pos ? $pos : [$this->volume_index, $this->chapter_index];
    }

    /**
     * @param array $curr [卷索引，章索引]二元组，当前章节在目录的位置，缺省为目录的记录位置
     * @return BookContentsItem|null 上一章的目录对象，如没有，返回null
     */
    public function previous_chapter(array $curr = []): BookContentsItem|null
    {
        [$vkey, $ckey] = $this->pos_or_default($curr);

        if ($vkey < 0 || $ckey < 0)
            return null;

        if ($ckey == 0) {
            if ($vkey == 0)
                return null; //没有了
            $pre_volume = $this->get_volume_chapters($vkey - 1);
            return $pre_volume[count($pre_volume) - 1]; //上一卷最后一章
        }
        return $this->get_volume_chapters($vkey)[$ckey - 1]; //这一卷上一章
    }

    /**
     * @param array $curr [卷索引，章索引]二元组，当前章节在目录的位置，缺省为目录的记录位置
     * @return BookContentsItem|null 下一章的目录对象，如没有，返回null
     */
    public function next_chapter(array $curr = []): BookContentsItem|null
    {
        [$vkey, $ckey] = $this->pos_or_default($curr);

        if ($vkey < 0 || $ckey < 0)
            return null;

        if ($ckey == count($this->get_volume_chapters($vkey)) - 1) {
            if ($vkey == count($this->get_volumes()) - 1)
                return null; //没有了
            $next_volume = $this->get_volume_chapters($vkey + 1);
            return $next_volume[0]; //下一卷第一章
        }
        return $this->get_volume_chapters($vkey)[$ckey + 1]; //这一卷下一章
    }

    public function set_active_chapter(WP_Post|int $post)
    {
        [$this->volume_index, $this->chapter_index] = $this->locate_chapter($post);
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

class BookContentsItem
{
    public $ID;
    public $post_title;
    // public $post_parent;


    /**
     * @param WP_Post $WP_Post
     */
    public function __construct($WP_Post)
    {
        $this->ID = $WP_Post->ID;
        $this->post_title = $WP_Post->post_title;
    }
}
