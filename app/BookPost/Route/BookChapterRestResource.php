<?php

namespace KarsonJo\BookPost\Route {

    use Exception;
    use KarsonJo\BookPost\SqlQuery\BookQuery;
    use WP_REST_Request;
    use WP_REST_Response;

    class BookChapterRestResource extends ChapterRestResource
    {
        protected function registerRoutes($namespace, $path, $permissions)
        {
            /**
             * 插入一章
             */
            register_rest_route($namespace, $path, [
                'methods' => 'POST',
                'permission_callback' => static::permissionCallback($permissions),
                'callback' => fn ($r) => $this->insertToLastVolume($r)
            ]);
        }

        protected function getIdentifier()
        {
            return "BookChapter";
        }

        protected function insertToLastVolume(WP_REST_Request $request)
        {
            return $this->insertToVolume($request, $request['bookId'], BookQuery::getLastVolumeID($request['bookId']));
        }
    }
}
