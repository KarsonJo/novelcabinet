<?php

namespace KarsonJo\BookPost\Route {

    use KarsonJo\BookPost\SqlQuery\BookQuery;
    use WP_REST_Request;
    use WP_REST_Response;

    class GenreRestResource extends RestResource {
        
        protected static string $idPattern = '(?P<genreId>\d+)';

        protected function registerRoutes($namespace, $path, $permissions)
        {
            /**
             * 插入book
             */
            register_rest_route($namespace, $path, [
                'methods' => 'GET',
                'permission_callback' => static::permissionCallback($permissions),
                'callback' => fn ($r) => $this->getGenres($r, $namespace, $path)
            ]);
        }

        protected function getIdentifier()
        {
            return "Genre";
        }

        protected function getGenres(WP_REST_Request $request)
        {
            $genres = BookQuery::allBookGenres();
            return new WP_REST_Response(array_map(fn($genre) => ['id' => $genre->term_id, 'name' => $genre->name], $genres));
        }

    }
}