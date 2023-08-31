<?php

namespace KarsonJo\BookPost {

    use DateTime;
    use KarsonJo\BookPost\SqlQuery\AuthorQuery;
    use KarsonJo\BookPost\SqlQuery\BookFilterBuilder;
    use KarsonJo\BookPost\SqlQuery\BookQuery;
    use TenQuality\WP\Database\QueryBuilder;
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
        public string $authorId;
        public string $authorLogin;
        public string $excerpt;
        public ?DateTime $updateTime;
        public string $status;

        /**
         * Book类型的Genre Taxonomy
         * @var \WP_Term[]
         */
        public array $genres;
        public array $tags;

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

        protected function __construct()
        {
        }

        public static function initBookFromPost(WP_Post|int $id): ?Book
        {
            if ($id instanceof WP_Post)
                $id = $id->ID;
            return BookQuery::getBook(['ID' => $id]);
            // $book = BookFilterBuilder::create(null, false)->of_id($id)->get_as_book();
            // if (!$book)
            //     return null;

            // return $book[0];
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
            $book->status = $params['post_status'] ?? '';

            if (isset($params['post_author'])) {
                $book->authorId = $params['post_author'];
                $book->author = AuthorQuery::getAuthorDisplayName($book->authorId);
                $book->authorLogin = AuthorQuery::getAuthorUserName($book->authorId);
            }
            // 获取额外信息
            // $book->permalink = get_permalink($_post);
            $book->permalink = get_post_permalink($book->ID);

            $images = wp_get_attachment_image_src(get_post_thumbnail_id($book->ID), "full");
            $book->cover = is_array($images) ? $images[0] : "";


            $book->genres = get_the_terms($book->ID, $bookGenre ?? BookPost::KBP_BOOK_GENRE ?? "category") ?: [];

            $book->tags = wp_get_post_tags($book->ID);

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
