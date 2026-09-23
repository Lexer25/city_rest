<?php defined('SYSPATH') or die('No direct script access.');

class Rest_Error
{
    const VALIDATION_ERROR    = 'VALIDATION_ERROR';
    const BAD_REQUEST         = 'BAD_REQUEST';
    const UNAUTHORIZED        = 'UNAUTHORIZED';
    const TOKEN_EXPIRED       = 'TOKEN_EXPIRED';
    const TOKEN_INVALID       = 'TOKEN_INVALID';
    const FORBIDDEN           = 'FORBIDDEN';
    const NOT_FOUND           = 'NOT_FOUND';
    const PERSON_NOT_FOUND    = 'PERSON_NOT_FOUND';
    const ORG_NOT_FOUND       = 'ORG_NOT_FOUND';
    const CARD_NOT_FOUND      = 'CARD_NOT_FOUND';
    const ACCESS_NOT_FOUND    = 'ACCESS_NOT_FOUND';
    const CONFLICT            = 'CONFLICT';
    const GUID_ALREADY_EXISTS = 'GUID_ALREADY_EXISTS';
    const CARD_ALREADY_ISSUED = 'CARD_ALREADY_ISSUED';
    const UNPROCESSABLE       = 'UNPROCESSABLE';
    const INTERNAL_ERROR      = 'INTERNAL_ERROR';
    const DB_ERROR            = 'DB_ERROR';

    public static function http_status($code)
    {
        static $map = array(
            self::VALIDATION_ERROR    => 400,
            self::BAD_REQUEST         => 400,
            self::UNAUTHORIZED        => 401,
            self::TOKEN_EXPIRED       => 401,
            self::TOKEN_INVALID       => 401,
            self::FORBIDDEN           => 403,
            self::NOT_FOUND           => 404,
            self::PERSON_NOT_FOUND    => 404,
            self::ORG_NOT_FOUND       => 404,
            self::CARD_NOT_FOUND      => 404,
            self::ACCESS_NOT_FOUND    => 404,
            self::CONFLICT            => 409,
            self::GUID_ALREADY_EXISTS => 409,
            self::CARD_ALREADY_ISSUED => 409,
            self::UNPROCESSABLE       => 422,
            self::INTERNAL_ERROR      => 500,
            self::DB_ERROR            => 500,
        );
        return isset($map[$code]) ? $map[$code] : 500;
    }
}
