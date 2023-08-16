<?php

namespace KarsonJo\Utilities\PostCache {
    class CacheHelpers
    {
        /**
         * 尝试获取一个wp_cache
         * 若不存在，将从给定方法获取，并存入wp_cache
         * @param int|string $key cache键
         * @param string $group cache所属组
         * @param callable $value_provider 失败时获取数值的函数
         * @param int $expire 设置cache时，可指定的过期时间（秒）
         * @return mixed|false
         */
        public static function getOrSetCache(int|string $key, string $group = '', ?callable $value_provider = null, $expire = 0): mixed
        {
            $res = wp_cache_get($key, $group);
            if ($res) return $res;

            if (!$value_provider) return false;

            $res = $value_provider();
            wp_cache_set($key, $res, $group, $expire);

            return $res;
        }
    }
}
