<?php

namespace KarsonJo\BookPost\SqlQuery {

    use Exception;
    use Throwable;

    class QueryException extends Exception
    {
        public const ERROR_UNKNOWN = 0;
        public const ERROR_FIELD_OUT_OF_RANGE = 1;
        public const ERROR_WPDB_EXCEPTION = 2;
        public const ERROR_FIELD_INVALID = 3;

        public const ERROR_CANNOT_CREATE_USER = 100;
        public const ERROR_USER_NOT_EXIST = 101;


        /**
         * coded使用类中的常量
         */
        protected function __construct($code, $message = '', Throwable $previous = null)
        {
            parent::__construct($message, $code, $previous);
        }

        protected static function createError($code, $defaultMessage, $message = '', $previous = null)
        {
            $message = $message ? $message : $defaultMessage;
            return new QueryException($code, $message, $previous);
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

        public static function fieldInvalid($message = '', Throwable $previous = null)
        {
            $message = $message ? $message : 'field invalid';
            return new QueryException(QueryException::ERROR_FIELD_INVALID, $message, $previous);
        }

        public static function cannotCreateUser($message = '', Throwable $previous = null)
        {
            return static::createError(QueryException::ERROR_CANNOT_CREATE_USER, 'cannot create user', $message, $previous);
        }

        public static function userNotExist($message = '', Throwable $previous = null)
        {
            return static::createError(QueryException::ERROR_USER_NOT_EXIST, 'user not exist', $message, $previous);
        }
    }
}
