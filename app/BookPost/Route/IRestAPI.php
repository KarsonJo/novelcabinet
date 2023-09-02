<?php

namespace KarsonJo\BookPost\Route {
    interface IRestAPI
    {
        /**
         * 生成一个API实例
         * @param string $apiDomain 
         * @param string $apiVersion 
         * @return IRestAPI 
         */
        public static function createAPI(string $apiDomain, string $apiVersion): RestResource;
        public function init();
    }
}
