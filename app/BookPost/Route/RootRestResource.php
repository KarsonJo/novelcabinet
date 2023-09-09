<?php

namespace KarsonJo\BookPost\Route {

    use WP_REST_Response;

    /**
     * 某REST API的根资源
     * @package KarsonJo\BookPost\Route
     */
    class RootRestResource extends RestResource implements IRestAPI
    {
        private string $namespace;
        protected function __construct()
        {
        }

        // public static function createAPI(string $apiDomain, string $apiVersion) : RestResource
        // {
        //     $this->namespace = $apiDomain . '/' . $apiVersion;
        //     return parent::create('');
        // }

        public static function createAPI(string $apiDomain, string $apiVersion): RootRestResource
        {
            $instance = static::create('');
            $instance->namespace = $apiDomain . '/' . $apiVersion;
            return $instance;
        }

        public function init()
        {
            add_action('rest_api_init', fn () => $this->addRoutes($this->namespace));

            // add_filter('rest_namespace_index', function ($response) {
            //     $data = $response->get_data();
            //     $data['namespaces'] = [];
            //     $data['routes'] = [];
            //     $response->set_data($data);

            //     return $response;
            // });
            static::allowLocalHttpAuth();
        }

        protected function getIdentifier()
        {
            return "Root";
        }

        protected function registerRoutes($namespace, $path, $permissions)
        {
            // print_r("route: $path\n");
            // https://wordpress.stackexchange.com/questions/355005/hiding-api-routes-list
            rest_get_server()->register_route($namespace, "/$namespace", [
                'methods' => 'GET',
                'permission_callback' => static::permissionCallback($permissions),
                'callback' => fn ($r) => $this->representationBrowser($r),
            ], true);
            // register_rest_route("kbp/v1", "/", [
            //     'methods' => 'GET',
            //     'permission_callback' => static::permissionCallback($permissions),
            //     'callback' => fn ($r) => $this->representationBrowser($r),
            // ],true);
        }

        protected function representationBrowser($_)
        {
            return new WP_REST_Response($this->getRoutes($this->namespace));
        }

        /**
         * 允许本地环回地址通过http验证身份
         * @return void 
         */
        static function allowLocalHttpAuth()
        {
            // print_r(1);
            add_filter('wp_is_application_passwords_available', function (bool $available) {
                // print_r($_SERVER['REMOTE_ADDR']);
                return $available || $_SERVER['REMOTE_ADDR'] === '127.0.0.1';
            });
        }
    }
}
