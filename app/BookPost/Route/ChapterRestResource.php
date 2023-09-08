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

    abstract class ChapterRestResource extends RestResource
    {
        protected static string $idPattern = '(?P<chapterId>\d+)';
        
        protected function insertToVolume(WP_REST_Request $request, string $bookId, string $volumeId)
        {
            $jsonData = $request->get_json_params();
            try {
                $report = BookQuery::createChapters(BookQuery::getBook($bookId), $volumeId, $jsonData, 50);
                return new WP_REST_Response(
                    [
                        'message' => 'successfully inserted chapters',
                        'report' => $report
                    ],
                    201
                );
            } catch (Exception $e) {
                return new WP_REST_Response([
                    'message' => 'failed to insert chapters',
                    'error' => static::getErrorMessage($e),
                ]);
            }
        }
    }
}
