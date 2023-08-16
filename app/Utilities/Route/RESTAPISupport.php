<?php

namespace KarsonJo\Utilities\Route {
    class RESTAPISupport
    {
        private static bool $init = false;
        public static function addJavascriptSupport()
        {
            if (static::$init) return;
            static::$init = true;

            add_action('wp_enqueue_scripts', function () {
                wp_enqueue_script('wp-api');
                wp_localize_script('wp-api', 'wpApiSettings', [
                    'root' => esc_url_raw(rest_url()),
                    'nonce' => wp_create_nonce('wp_rest')
                ]);
            });
        }
    }
}
