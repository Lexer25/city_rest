<?php defined('SYSPATH') or die('No direct script access.');

class Rest_Auth
{
    public static function login($login, $password)
    {
        $c = Kohana::$config->load('rest.auth');
        $sql = 'SELECT ' . $c['field_guid']   . ' AS GUID, '
             . $c['field_login']  . ' AS LOGIN, '
             . $c['field_pass']   . ' AS PASS_HASH, '
             . $c['field_active'] . ' AS IS_ACTIVE '
             . 'FROM ' . $c['table'] . ' WHERE ' . $c['field_login'] . ' = \''.$login.'\'';
       try {
            $row =  DB::query(Database::SELECT, $sql)
               // ->param(1, $login)
                ->execute(Database::instance('fb'))
                ->current()
				;

            if (!$row) {
                return null;
            }
            if (empty($row['IS_ACTIVE'])) {
                return null;
            }
            /* if (!password_verify($password, $row['PASS_HASH'])) {
                return null;
            } */
			// Пароль не подходит (строгое совпадение)
			if ($password !== $row['PASS_HASH']) {
				return null;
			}
			
            return array(
                'guid'  => trim((string) $row['GUID']),
                'login' => trim((string) $row['LOGIN']),
            );
        } catch (Exception $e) {
            return null;
        }
    }

    public static function issue_token(array $user)
    {
        return Rest_Jwt::encode(array(
            'sub'   => $user['guid'],
            'login' => $user['login'],
        ));
    }
}
