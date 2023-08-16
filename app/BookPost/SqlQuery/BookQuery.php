<?php

namespace KarsonJo\BookPost\SqlQuery {

    use KarsonJo\BookPost\BookPost;
    use WP_Post;
    use WP_Term;

    class BookQuery
    {
        /**
         * 获取所有书类型
         * @return \WP_Term[]|false
         */
        public static function allBookGenres(): array|false
        {
            return get_terms([
                'taxonomy' => BookPost::KBP_BOOK_GENRE,
                'hide_empty' => false,
            ]);
        }


        /**
         * 查找根文章
         * 用于从卷或章（子文章）找到所属书籍
         * @param \WP_Post|int $post 卷或章的对象或编号
         * @return \WP_Post|null 返回当前post的最根文章
         */
        public static function rootPost(WP_Post|int $post): WP_Post
        {
            $post = get_post($post);
            while ($post && $post->post_parent != 0)
                $post = get_post($post->post_parent);

            return $post;
        }
    }
}
