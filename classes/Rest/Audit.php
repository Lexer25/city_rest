<?php defined('SYSPATH') or die('No direct script access.');

class Rest_Audit
{
    public static function set_context(array $ctx)
    {
        $c = Kohana::$config->load('rest.audit');
        if (empty($c['enabled'])) return;
        $sql = 'SELECT '
             . 'RDB$SET_CONTEXT(''USER_TRANSACTION'', ''APP_USER_ID'',    ?) AS A, '
             . 'RDB$SET_CONTEXT(''USER_TRANSACTION'', ''APP_USER_LOGIN'', ?) AS B, '
             . 'RDB$SET_CONTEXT(''USER_TRANSACTION'', ''APP_USER_IP'',    ?) AS C, '
             . 'RDB$SET_CONTEXT(''USER_TRANSACTION'', ''APP_REQUEST_ID'', ?) AS D '
             . 'FROM RDB$DATABASE';
        try {
            DB::query(Database::SELECT, $sql)
                ->param(1, (string)Arr::get($ctx,'user_id',''))
                ->param(2, (string)Arr::get($ctx,'user_login',''))
                ->param(3, (string)Arr::get($ctx,'user_ip',''))
                ->param(4, (string)Arr::get($ctx,'request_id',''))
                ->execute(Database::instance('fb'));
        } catch (Exception $e) {
            Kohana::$log->add(Log::ERROR, 'Rest_Audit::set_context: ' . $e->getMessage());
        }
    }
}
