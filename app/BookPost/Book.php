<?php

namespace KarsonJo\BookPost {

    use DateTime;
    use KarsonJo\BookPost\SqlQuery\BookFilterBuilder;
    use WP_Post;

    class Book
    {
        public int $ID;
        /**
         * 书的封面图url
         */
        public string $cover;
        /**
         * 书的永久链接
         */
        public string $permalink;

        public string $title;
        public string $author;
        public string $excerpt;
        public ?DateTime $updateTime;
        /**
         * Book类型的Genre Taxonomy
         */
        public array $genres;

        public float $rating;
        /**
         * 评分权重，可用作记录评分人数等
         */
        public int $ratingWeight;
        public int $wordCount;

        /**
         * 书的目录
         * 懒加载变量 $this->contents;
         */
        private ?BookContents $_contents = null;

        // /**
        //  * 只用ID初始化的WP_Post对象
        //  * 用于某些只需要id，但需要传入WP_Post的WordPress函数
        //  * （避免直接传入id多查询一次数据库） <--木大哒
        //  */
        // protected WP_Post $_post;

        protected function __construct()
        {
        }

        public static function initBookFromPost(WP_Post|int $id): ?Book
        {
            if ($id instanceof WP_Post)
                $id = $id->ID;
            $book = BookFilterBuilder::create(null, false)->of_id($id)->get_as_book();
            if (!$book)
                return null;

            return $book[0];
        }

        public static function initBookFromArray(array $params): ?Book
        {
            if (!array_key_exists('ID', $params))
                return null;

            $book = new Book();

            $book->ID = $params['ID'];

            $book->title = $params['post_title'] ?? '';
            $book->excerpt = $params['post_excerpt'] ?? '';
            $book->updateTime = isset($params['update_date']) ? DateTime::createFromFormat('Y-m-d G:i:s', $params['update_date']) : null;
            $book->rating = round($params['rating'], 2) ?? 0;
            $book->ratingWeight = $params['rating_weight'] ?? 0;
            $book->wordCount = $params['word_count'] ?? 0;

            if (isset($params['post_author']))
                $book->author = get_the_author_meta('display_name', $params['post_author']);

            // 获取额外信息
            // $book->permalink = get_permalink($_post);
            $book->permalink = get_post_permalink($book->ID);

            $images = wp_get_attachment_image_src(get_post_thumbnail_id($book->ID), "full");
            $book->cover = is_array($images) ? $images[0] : "";


            $book->genres = get_the_terms($book->ID, $bookGenre ?? defined('KBP_BOOK_GENRE') ? KBP_BOOK_GENRE : "category");

            return $book;
        }

        public static function initBookFromParams(...$params): ?Book
        {
            return Book::initBookFromArray($params);
        }

        public static function initBookFromObject(object $obj): ?Book
        {
            return Book::initBookFromArray((array)$obj);
        }

        public function __get($name)
        {
            // Check if the property is not already loaded
            if ($name === 'contents') {
                if ($this->ID === null)
                    return null;

                if ($this->_contents == null) {
                    $this->_contents = new BookContents($this->ID);
                }
                return $this->_contents;
            }
            return null;
        }
    }
}
