<?php defined('SYSPATH') or die('No direct script access.');

return array(

    'jwt' => array(
        'secret'      => getenv('REST_JWT_SECRET') ?: 'change-me-in-production',
        'algorithm'   => 'HS256',
        'ttl'         => 3600,
        'refresh_ttl' => 86400,
        'issuer'      => 'city-rest',
    ),

    'auth' => array(
        'table'        => 'PEOPLE',
        'field_login'  => 'LOGIN',
        'field_pass'   => 'PSWD',
        'field_guid'   => 'GUID',
        'field_active' => '"ACTIVE"',
    ),

    'pagination' => array(
        'default_limit' => 50,
        'max_limit'     => 500,
    ),

    'audit' => array(
        'enabled' => true,
        'table'   => 'AUDIT_LOG',
        'source'  => 'rest-api',
    ),

    'cors' => array(
        'enabled'       => false,
        'allow_origin'  => '*',
        'allow_methods' => 'GET,POST,PUT,DELETE,OPTIONS',
        'allow_headers' => 'Content-Type,Authorization,X-Request-ID',
    ),
);
