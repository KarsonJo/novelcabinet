<?php

namespace KarsonJo\BookPost\Route {

    use Exception;
    use KarsonJo\BookPost\Book;
    use KarsonJo\BookPost\BookContents;

    use WP_REST_Response;


    use KarsonJo\BookPost\SqlQuery\AuthorQuery;
    use KarsonJo\BookPost\SqlQuery\BookQuery;
    use KarsonJo\BookPost\SqlQuery\QueryException;
    use KarsonJo\Utilities\Algorithms\StringAlgorithms;
    use NovelCabinet\Utilities\ArrayHelper;

    use WP_Error;
    use WP_REST_Request;
    use WP_Term;

    class VolumeChapterRestResource extends ChapterRestResource
    {
        protected function registerRoutes($namespace, $path, $permissions)
        {
            /**
             * 插入一章
             */
            register_rest_route($namespace, $path, [
                'methods' => 'POST',
                'permission_callback' => static::permissionCallback($permissions),
                'callback' => fn ($r) => $this->insertToTheVolume($r)
            ]);
        }

        protected function getIdentifier()
        {
            return "VolumeChapter";
        }

        protected function insertToTheVolume(WP_REST_Request $request)
        {
            return $this->insertToVolume($request, $request['bookId'], $request['volumeId']);
        }
    }
}
