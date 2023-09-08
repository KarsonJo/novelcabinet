<?php

namespace KarsonJo\BookPost\SqlQuery {

    use Exception;
    use WP_User;

    class AuthorQuery
    {
        /**
         * 以给定用户名创建用户
         * @param string $userLogin 
         * @return int id 
         * @throws QueryException 
         */
        static function createAuthor(string $userLogin, ?string $displayName = null): int
        {
            // print_r($userLogin);
            // $result = wp_create_user($userName, wp_generate_password());
            $result = wp_insert_user([
                'user_login' => $userLogin,
                'user_pass' => wp_generate_password(),
                'display_name' => $displayName ?? $userLogin
            ]);

            // print_r($result);
            if (is_wp_error($result))
                throw QueryException::cannotCreateUser($result->get_error_message());

            return $result;
        }

        /**
         * 根据用户名，获取UserID
         * @param string $userName 
         * @param bool $autoCreate 
         * @return int
         * @throws QueryException 
         */
        static function getAuthorID(string $userName, $autoCreate = false, ?string $createDisplayName = null): int
        {
            $user = get_user_by('login', $userName);
            if ($user !== false)
                return $user->ID;

            try {
                if ($autoCreate)
                    return static::createAuthor($userName, $createDisplayName);
            } catch (QueryException $e) {
                throw QueryException::userNotExist($e->getMessage(), $e);
            }
            throw QueryException::userNotExist();
            // return 0;
        }



        static function getAuthorUserName(int $id)
        {
            // print($id);
            // return "123";
            $loginName = static::getAuthorField($id, 'user_login');
            // if ($loginName === false)
            //     throw QueryException::userNotExist();

            return $loginName;
        }

        static function getAuthorDisplayName(int $id)
        {
            $displayName = static::getAuthorField($id, 'display_name');
            if ($displayName === false)
                return static::getAuthorUserName($id);

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
