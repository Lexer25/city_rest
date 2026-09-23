<?php defined('SYSPATH') or die('No direct script access.');

class Rest_Response
{
    public static function ok($data = null, array $meta = array())
    {
        $r = array('ok' => true);
        if ($data !== null) {
            $r['data'] = $data;
        }
        if (!empty($meta)) {
            $r['meta'] = $meta;
        }
        return $r;
    }

    public static function error($code, $message, $status = 400, array $extra = array())
    {
        $error = array(
            'code'    => $code,
            'message' => $message,
            'status'  => (int) $status,
        );
        if (!empty($extra)) {
            $error = array_merge($error, $extra);
        }
        return array('ok' => false, 'error' => $error);
    }

    public static function to_utf8($v)
    {
        if (is_array($v)) {
            $out = array();
            foreach ($v as $k => $x) {
                $out[$k] = self::to_utf8($x);
            }
            return $out;
        }
        if (is_object($v)) {
            return self::to_utf8((array) $v);
        }
        if (is_string($v) && $v !== '') {
            if (function_exists('mb_check_encoding') && mb_check_encoding($v, 'UTF-8')) {
                return $v;
            }
            $conv = @iconv('windows-1251', 'UTF-8//IGNORE', $v);
            return ($conv === false) ? $v : $conv;
        }
        return $v;
    }

    public static function render(Response $response, array $data)
    {
        $data = self::to_utf8($data);
        $status = 200;
        if (empty($data['ok']) && isset($data['error']['status'])) {
            $status = (int) $data['error']['status'];
        }
        $json = json_encode($data, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            $json = json_encode(array(
                'ok'    => false,
                'error' => array(
                    'code'    => 'JSON_ENCODE_ERROR',
                    'message' => json_last_error_msg(),
                    'status'  => 500,
                ),
            ));
            $status = 500;
        }
        $response->status($status);
        $response->headers('Content-Type', 'application/json; charset=utf-8');
        $response->body($json);
        return $response;
    }
}