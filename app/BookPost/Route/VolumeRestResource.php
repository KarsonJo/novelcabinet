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

    class VolumeRestResource extends RestResource
    {
        protected static string $idPattern = '(?P<volumeId>\d+)';

        protected function registerRoutes($namespace, $path, $permissions)
        {
            /**
             * 插入一章
             */
            register_rest_route($namespace, $path, [
                'methods' => 'POST',
                'permission_callback' => static::permissionCallback($permissions),
                'callback' => fn ($r) => $this->insertVolume($r, $namespace, $path)
            ]);
        }

        protected function getIdentifier()
        {
            return "Volume";
        }

        protected function insertVolume(WP_REST_Request $request, $namespace, $path)
        {
            $bookId = $request['bookId'];
            if (empty($request['title']))
                return new WP_REST_Response([
                    'message' => 'failed to insert volume',
                    'error' => static::getErrorMessage(QueryException::fieldInvalid(('title is empty')))
                ], 422);
            $volumeId = BookQuery::createVolume(BookQuery::getBook($bookId), $request['title']);

            global $wp;
            return new WP_REST_Response(
                [
                    'message' => 'successfully inserted volume',
                    'data' => ['id' => $volumeId],
                ],
                201,
                ['Location' => home_url("$wp->request/$volumeId")]
            );
        }
    }
}
