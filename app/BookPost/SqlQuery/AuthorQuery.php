<?php

namespace KarsonJo\BookPost\SqlQuery {

    use Exception;
    use WP_User;

    class AuthorQuery
    {
        /**
         * 以给定用户名创建用户
         * @param string $userName 
         * @return int id 
         * @throws QueryException 
         */
        static function createAuthor(string $userName): int
        {
            $result = wp_create_user($userName, wp_generate_password());
            if (is_wp_error($result))
                throw QueryException::cannotCreateUser();

            return $result;
        }

        /**
         * 根据用户名，获取UserID
         * @param string $userName 
         * @param bool $autoCreate 
         * @return int
         * @throws QueryException 
         */
        static function getAuthorID(string $userName, $autoCreate = false): int
        {
            $user = get_user_by('login', $userName);
            if ($user !== false)
                return $user->ID;

            try {
                if ($autoCreate)
                    $user = static::createAuthor($userName);
            } catch (QueryException $e) {
                throw QueryException::userNotExist('', $e);
            }
            throw QueryException::userNotExist();
        }



        static function getAuthorUserName(int $id)
        {
            $loginName = static::getAuthorField($id, 'user_login');
            if ($loginName === false)
                throw QueryException::userNotExist();

            return $loginName;
        }

        static function getAuthorDisplayName(int $id)
        {
            $displayName = static::getAuthorField($id, 'display_name');
            if ($displayName === false)
                throw QueryException::userNotExist();

            return $displayName;
        }

        static protected function getAuthorField(int $id, string $field)
        {
            $user = get_user_by('id', $id);
            if ($user !== false)
                return $user->$field;
            return false;
        }
    }
}
