<?php

namespace KarsonJo\BookPost\BookMeta {

    use KarsonJo\BookPost\BookPost;
    use KarsonJo\BookPost\Route\QueryData;
    use KarsonJo\BookPost\SqlQuery\BookQuery;
    use TenQuality\WP\Database\QueryBuilder;
    use WP_Query;

    class MetaManager
    {
        public static function init()
        {
            add_filter('wp_insert_post_data', [__CLASS__, 'preventUpdatingModifiedDate'], 1, 2);
            add_filter('wp_insert_post_data', [__CLASS__, 'newPostWithParent'], 1, 2);
            add_filter('wp_insert_post_data', [__CLASS__, 'newBookChapter'], 1, 2);
            // add_filter('save_post_' . BookPost::KBP_BOOK, [__CLASS__, 'newBookChapter'], 1, 2);


            add_action('pre_get_posts', [__CLASS__, 'customAdminPostOrder']);
        }

        /**
         * 维护书本的最后更新时间
         * 阻止修改元数据时更新书的最后更新时间
         * https://wordpress.stackexchange.com/questions/237878/how-to-prevent-wordpress-from-updating-the-modified-time
         * https://brogramo.com/how-to-update-a-wordpress-post-without-updating-the-modified-date-using-wp_update_post/
         */
        static function preventUpdatingModifiedDate($data, $postarr)
        {

            // 只干预Book的父文章
            if ($postarr['post_type'] != BookPost::KBP_BOOK || $postarr['post_parent'] != 0)
                return $data;

            // return if the modified date is not set
            // this happens in revisions and can heppen in other post types
            if (!isset($postarr['post_modified']) || !isset($postarr['post_modified_gmt']))
                return $data;

            // 将修改时间改回缺省值
            $data['post_modified'] = $postarr['post_modified'];
            $data['post_modified_gmt']  = $postarr['post_modified_gmt'];

            // write_log($data);
            // write_log($postarr);

            return $data;
        }
        // $postarr = [];
        // $postarr['ID']         = 17; // post ID being updated
        // $postarr['ababa'] = 'ababa'; // array of tags (Or it can be something like changing the post's status)
        // $postarr['post_modified'] = "1970-1-1 00:00:00";
        // $postarr['post_modified_gmt'] = "1970-1-1 00:00:00";

        // update post
        // $update_post = wp_update_post($postarr);

        /**
         * 支持新建文章时使用"post_parent"查询字符串指定父亲
         * @return void 
         */
        static function newPostWithParent($data, $postarr)
        {
            // 不是新文章 或 不是书， 不关我事
            if ($postarr['post_type'] != BookPost::KBP_BOOK || !empty($postarr['ID']))
                return $data;

            // 没设置，也不关我事
            $parent_id = QueryData::getAdminQueryArg(QueryData::POST_PARENT);
            if (empty($parent_id) || !is_numeric($parent_id))
                return $data;

            // 用户无权编辑父文章，驳回
            if (!current_user_can('edit_post', $parent_id))
                return $data;

            // 父文章与当前文章不是同一类型，也不执行
            if (get_post($parent_id)->post_type != $postarr['post_type'])
                return $data;

            // 满足所有条件，设置父亲
            $data->post_parent = $parent_id;
            return $data;
        }

        /**
         * 支持新建文章时使用"chapter_of"指定文章为该书的新章节
         * @return void 
         */
        static function newBookChapter($data, $postarr)
        {
            // print($postarr['post_type']);
            // print($postarr['ID']);
            // 不是新文章 或 不是书， 不关我事
            if ($postarr['post_type'] != BookPost::KBP_BOOK || !empty($postarr['ID']))
                return $data;

            // 没设置，也不关我事
            $book_id = QueryData::chapterOf();
            // print($book_id);
            if (!$book_id)
                return $data;

            // 用户无权编辑书本，驳回
            if (!current_user_can('edit_post', $book_id))
                return $data;
            // print("can edit");

            // 查找最后一卷
            // 找不到任何卷，返回？？？创建？？？
            // print(BookQuery::getLastVolumeID());
            $parent_id = BookQuery::getLastVolumeID($book_id);
            // print($parent_id);
            if (!$parent_id)
                return $data;
            // print("found");

            // 满足所有条件，设置父亲
            $data['post_parent'] = $parent_id;
            // $data['menu_order'] = 100;
            return $data;
        }

        // /**
        //  * 为书的新卷、章加menu order
        //  * @return mixed 
        //  */
        // static function updateNewBookMenuOrder($data, $postarr)
        // {
        //     // 不是新文章 或 不是书， 不关我事
        //     if ($postarr['post_type'] != BookPost::KBP_BOOK || !empty($postarr['ID']))
        //         return $data;

        //     if ($postarr)
        // }

        /**
         * 改变Book类型的WordPress admin的文章排序
         * @param mixed $query 
         * @return void 
         */
        static function customAdminPostOrder($query)
        {
            if (is_admin() && $query->is_main_query() && $query->get('post_type') === BookPost::KBP_BOOK)
                return BookQuery::WPQuerySetBookOrder($query);

            return $query;
        }
    }
}
