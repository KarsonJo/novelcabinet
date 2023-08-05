<?php

namespace KarsonJo\BookPost {

    use Exception;
    use Throwable;

    class QueryException extends Exception
    {
        public const ERROR_UNKNOWN = 0;
        public const ERROR_FIELD_OUT_OF_RANGE = 1;
        public const ERROR_WPDB_EXCEPTION = 2;


        /**
         * coded使用类中的常量
         */
        protected function __construct($code, $message = '', Throwable $previous = null)
        {
            parent::__construct($message, $code, $previous);
        }

        public static function fieldOutOfRange($message = '', Throwable $previous = null)
        {
            $message = $message ? $message : 'field out of range';
            return new QueryException(QueryException::ERROR_FIELD_OUT_OF_RANGE, $message, $previous);
        }

        public static function wpdbException($message = '')
        {
            $message = $message ? $message : 'error when executing $wpdb query';
            return new QueryException(QueryException::ERROR_WPDB_EXCEPTION, $message);
        }
    }
}
