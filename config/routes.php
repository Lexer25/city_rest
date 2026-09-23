<?php defined('SYSPATH') or die('No direct script access.');

// Аутентификация
Route::set('rest_auth', 'api/v1/auth(/<action>)')
    ->defaults(array(
        'controller' => 'Rest_Auth',
        'action'     => 'index',
    ));

// Версия
Route::set('rest_version', 'api/v1/version')
    ->defaults(array(
        'controller' => 'Rest_Version',
        'action'     => 'index',
    ));

