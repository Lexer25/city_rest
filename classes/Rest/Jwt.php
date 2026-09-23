<?php defined('SYSPATH') or die('No direct script access.');

class Rest_Jwt
{
    public static function encode(array $payload)
    {
        $config = Kohana::$config->load('rest.jwt');
        $now = time();
        $payload = array_merge(array(
            'iss' => $config['issuer'],
            'iat' => $now,
            'exp' => $now + (int) $config['ttl'],
        ), $payload);
        $header = array('typ' => 'JWT', 'alg' => $config['algorithm']);
        $b64 = function ($d) {
            return rtrim(strtr(base64_encode($d), '+/', '-_'), '=');
        };
        $h = $b64(json_encode($header));
        $p = $b64(json_encode($payload));
        $sig = hash_hmac('sha256', $h . '.' . $p, $config['secret'], true);
        return $h . '.' . $p . '.' . $b64($sig);
    }

    public static function decode($token)
    {
        $config = Kohana::$config->load('rest.jwt');
        $parts = explode('.', (string) $token);
        if (count($parts) !== 3) {
            return null;
        }
        list($h, $p, $s) = $parts;
        $b64d = function ($d) {
            $pad = strlen($d) % 4;
            if ($pad) {
                $d .= str_repeat('=', 4 - $pad);
            }
            return base64_decode(strtr($d, '-_', '+/'));
        };
        $expected = hash_hmac('sha256', $h . '.' . $p, $config['secret'], true);
        $actual   = $b64d($s);
        if (!hash_equals($expected, $actual)) {
            return null;
        }
        $payload = json_decode($b64d($p), true);
        if (!is_array($payload)) {
            return null;
        }
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return null;
        }
        return $payload;
    }

    public static function from_request(Request $request)
    {
        $h = (string) $request->headers('Authorization');
        if ($h === '' && isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $h = (string) $_SERVER['HTTP_AUTHORIZATION'];
        }
        if (preg_match('/^Bearer\s+(.+)$/i', $h, $m)) {
            return trim($m[1]);
        }
        return '';
    }
}