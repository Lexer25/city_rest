<?php defined('SYSPATH') or die('No direct script access.');

class Rest_Audit
{
    public static function set_context(array $ctx)
    {
        $c = Kohana::$config->load('rest.audit');
        if (empty($c['enabled'])) {
            return;
        }

        $map = array(
            'APP_USER_ID'    => 'user_id',
            'APP_USER_LOGIN' => 'user_login',
            'APP_USER_IP'    => 'user_ip',
            'APP_REQUEST_ID' => 'request_id',
        );

        $db = Database::instance('fb');

        foreach ($map as $ctx_name => $key) {
            $val = (string) Arr::get($ctx, $key, '');

            $sql = 'SELECT RDB$SET_CONTEXT('
                 . self::_q('USER_TRANSACTION') . ', '
                 . self::_q($ctx_name)          . ', '
                 . self::_q($val)               . ') AS X '
                 . 'FROM RDB$DATABASE';

            try {
                DB::query(Database::SELECT, $sql)->execute($db);
            } catch (Exception $e) {
                Kohana::$log->add(Log::ERROR,
                    'Rest_Audit::set_context[' . $ctx_name . ']: ' . $e->getMessage());
                Kohana::$log->add(Log::ERROR,
                    'Rest_Audit SQL: ' . $sql);
            }
        }
    }

    /**
     * SQL-литерал для Firebird.
     * NULL → NULL, число → число, строка → 'строка' с удвоением кавычек.
     */
    protected static function _q($v)
    {
        if ($v === null) {
            return 'NULL';
        }
        if (is_int($v) || is_float($v)) {
            return (string) $v;
        }
        if (is_bool($v)) {
            return $v ? '1' : '0';
        }
        return "'" . str_replace("'", "''", (string) $v) . "'";
    }
}