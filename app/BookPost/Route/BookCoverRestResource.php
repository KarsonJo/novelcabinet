<?php

namespace KarsonJo\BookPost\Route {

    use WP_REST_Response;
    use KarsonJo\BookPost\SqlQuery\BookQuery;
    use KarsonJo\BookPost\SqlQuery\QueryException;
    use NovelCabinet\Utilities\ArrayHelper;
    use WP_Error;
    use WP_REST_Request;

    class BookCoverRestResource extends RestResource
    {
        protected function registerRoutes($namespace, $path, $permissions)
        {
            // print_r("route: $path\n");
            // print_r("route: " . static::pathWithIdPattern($path) . "\n");

            register_rest_route($namespace, $path, [
                'methods' => 'GET',
                'permission_callback' => static::permissionCallback($permissions),
                'callback' => fn ($r) => $this->getBookCover($r)
            ]);
            /**
             * php的put/patch请求不接受multipart/form-data
             * 但restful api在上传文件时需要put的multipart支持
             * 因此自行处理
             * https://bugs.php.net/bug.php?id=55815
             * 
             */
            register_rest_route($namespace, $path, [
                'methods' => 'POST',
                'permission_callback' => static::permissionCallback($permissions),
                'callback' =>  fn ($r) => $this->updateBookCover($r)
            ]);
        }

        protected function getIdentifier()
        {
            return "BookCover";
        }

        protected function getBookCover(WP_REST_Request $request)
        {
            $book = BookQuery::getBook($request['bookId']);
            if (!$book || $book->ID != $request['bookId']) {
                return new WP_REST_Response([
                    'message' => 'book not exists',
                    'error' => static::getErrorMessage(QueryException::fieldInvalid(('book not found')))
                ], 404);
            }

            $url = get_the_post_thumbnail_url($book->ID);
            if (!$url)
                return new WP_REST_Response([
                    'message' => 'thumbnail not exists',
                    'error' => static::getErrorMessage(QueryException::fieldInvalid(('thumbnail not found')))
                ], 404);
            return new WP_REST_Response(['url' => $url]);
        }

        protected function updateBookCover(WP_REST_Request $request)
        {
            /**
             * 检测输入合法性
             */
            $book = BookQuery::getBook($request['bookId']);
            if (!$book || $book->ID != $request['bookId']) {
                return new WP_REST_Response([
                    'message' => 'failed uploading image',
                    'error' => static::getErrorMessage(QueryException::fieldInvalid(('book not found')))
                ], 404);
            }
            if (empty($_FILES['src'])) {
                return new WP_REST_Response([
                    'message' => 'failed uploading image',
                    'error' => static::getErrorMessage(QueryException::fieldInvalid(('src is empty')))
                ], 422);
            }

            /**
             * 检测当前封面图状态
             */
            $deleteOld = false;
            //有封面图
            if (($oldThumbnailId = get_post_thumbnail_id($book->ID)) && ($thumbnailFile = get_attached_file($oldThumbnailId))) {
                $newMd5Hash = md5_file($_FILES["src"]["tmp_name"]);
                $currMd5Hash = md5_file($thumbnailFile);
                // 文件相同
                if ($newMd5Hash == $currMd5Hash)
                    return new WP_REST_Response(['message' => 'current cover is the same'], 200);
                // 删除旧的
                else
                    $deleteOld = true;
            }

            /**
             * 上传新图片
             */
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');

            // 不要产生不同尺寸
            // todo: 也许应该上升为设置
            $forceOnlyOneSize = fn () => [];
            add_filter('intermediate_image_sizes_advanced', $forceOnlyOneSize, 9, 0);

            $attachment_id = media_handle_upload('src', $book->ID);
            if ($attachment_id instanceof WP_Error) {
                return new WP_REST_Response([
                    'message' => 'failed uploading image',
                    'error' => static::getErrorMessage($attachment_id),
                ], 422);
            }

            $result = set_post_thumbnail($book->ID, $attachment_id);
            if (!$result) {
                return new WP_REST_Response([
                    'message' => 'file uploaded, but failed to set thumbnail',
                    'error' => static::getErrorMessage(['unknown']),
                ], 422);
            }

            remove_filter('intermediate_image_sizes_advanced', $forceOnlyOneSize);


            /**
             * 秋后算账（删除）
             */
            if ($oldThumbnailId && $deleteOld) {
                $attachement = get_post($oldThumbnailId);
                // 是从属于该文章的attachment才删除
                if ($attachement->post_parent == $book->ID)
                    wp_delete_attachment($oldThumbnailId, true);
            }

            if ($oldThumbnailId)
                return new WP_REST_Response(['message' => 'cover successfully updated']);
            else
                return new WP_REST_Response(['message' => 'cover successfully uploaded'], 201);
        }
    }
}
