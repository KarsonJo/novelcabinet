<?php

namespace KarsonJo\BookPost\PostCache {

    define('KBP_CACHE_DOMAIN', 'kbp_post_cache');

    use TenQuality\WP\Database\QueryBuilder;

    class CacheBuilder
    {
        // 简单的预缓存支持

        protected $cached;

        protected function __construct()
        {
        }

        public static function create(): CacheBuilder
        {
            return new CacheBuilder();
        }

        protected function prepareCache(string $key, array &$value)
        {
            if (!isset($this->cached[$key]))
                $this->cached[$key] = [];

            foreach ($value as $var)
                if (!isset($this->cached[$key][$var]))
                    $this->cached[$key][$var] = $var;
        }

        /**
         * 如果指定，则只缓存文章元数据的WP_Post
         * （若后续需要获取文章内容，需要清除对应的Post缓存）
         * 适用于只获取大量文章列表的情况
         */
        public function withoutPostContent()
        {
            $this->cached['no_content'] = true;
            return $this;
        }

        public function cachePosts(array &$post_ids)
        {
            $this->prepareCache('posts', $post_ids);
            return $this;
        }

        /**
         * 统一缓存所有给定文章的postmeta
         * https://hitchhackerguide.com/2011/11/01/reducing-postmeta-queries-with-update_meta_cache/
         * @param int[] &$post_ids
         */
        public function cachePostmeta(array &$post_ids)
        {
            $this->prepareCache('postmeta', $post_ids);
            return $this;
        }

        /**
         * 统一缓存给定文章的特色图片状态
         * thumbnail post + postmeta
         * @param int[] &$post_ids
         */
        public function cacheThumbnailStatus(array &$post_ids)
        {
            $this->prepareCache('thumbnail', $post_ids);
            return $this;
        }

        /**
                    
         * 统一所有给定文章作为[object_type]类型的“所有”taxonomy
         * @param int[] &$post_ids
         */
        public function cacheTaxonomy(array &$post_ids)
        {
            $this->prepareCache('taxonomy', $post_ids);
            return $this;
        }

        public function cache()
        {
            // print_r('[build_cache]');
            // postmeta
            if (isset($this->cached['postmeta']) || isset($this->cached['thumbnail'])) {
                $posts = $this->cached['postmeta'] + $this->cached['thumbnail'];
                update_meta_cache('post', $posts);
                // print_r('[build_cache]');
            }

            if (isset($this->cached['thumbnail'])) {
                // 需要获取postmeta
                $thumb_ids = array_map(function ($post_id) {
                    return (int) get_post_meta($post_id, '_thumbnail_id', true);
                }, $this->cached['thumbnail']);

                // 缓存post
                $this->prepareCache('posts', $thumb_ids);

                // 再次缓存postmeta
                update_meta_cache('post', $thumb_ids);
            }

            if (isset($this->cached['posts'])) {
                $post_ids = array_filter($this->cached['posts'], function ($val) {
                    return !boolval(wp_cache_get($val, 'post'));
                });

                if (isset($this->cached['no_content'])) {
                    // print_r($post_ids);
                    $post_objs = QueryBuilder::create()
                        ->select('ID, post_author, post_date, post_date_gmt, post_title, post_excerpt, post_status, comment_status')
                        ->select('post_password, post_name, post_modified, post_modified_gmt, post_parent')
                        ->select('guid, menu_order, post_type, post_mime_type, comment_count')
                        ->from('posts')
                        ->where(['ID' => ['operator' => 'IN', 'value' => array_values($post_ids)]])
                        ->get();
                    // print_r($post_objs);
                    foreach ($post_objs as $post) {
                        wp_cache_add($post->ID, $post, 'posts');
                    }
                } else
                    get_posts([
                        'include' => $post_ids,
                        'post_type' => 'any',
                        'post_status' => 'any',
                    ]);
            }


            if (isset($this->cached['taxonomy']))
                update_object_term_cache($this->cached['taxonomy'], KBP_BOOK);
        }
    }

    /**
     * 尝试获取一个wp_cache
     * 若不存在，将从给定方法获取，并存入wp_cache
     * @param int|string $key cache键
     * @param string $group cache所属组
     * @param callable $value_provider 失败时获取数值的函数
     * @param int $expire 设置cache时，可指定的过期时间（秒）
     * @return mixed|false
     */
    function get_or_set_cache(int|string $key, string $group = '', ?callable $value_provider = null, $expire = 0): mixed
    {
        $res = $value_provider();
        return $res;


        $res = wp_cache_get($key, $group);
        if ($res) return $res;

        if (!$value_provider) return false;

        $res = $value_provider();
        wp_cache_set($key, $res, $group, $expire);

        return $res;
    }
}
