<?php defined('SYSPATH') or die('No direct script access.');

// ---- RPC-эндпоинты (не ресурсные, оставляем как было) ----

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

// Документация
Route::set('rest_docs', 'api/v1/docs')
    ->defaults(array(
        'controller' => 'Rest_Docs',
        'action'     => 'index',
    ));
	
// rest/config/routes.php

// корни
Route::set('rest_orgs_roots', 'api/v1/orgs/roots')
    ->defaults(array(
        'controller' => 'Rest_Orgs',
        'action'     => 'roots',
    ));

// дети узла
Route::set('rest_orgs_children', 'api/v1/orgs/<id>/children', array(
        'id' => '\d+',   // только числовой ID_ORG
    ))
    ->defaults(array(
        'controller' => 'Rest_Orgs',
        'action'     => 'children',
    ));

// общий ресурсный роут — ПОСЛЕ них
Route::set('rest_orgs', 'api/v1/orgs(/<id>)', array(
        'id' => '[^/.,;?\n]++',
    ))
    ->defaults(array(
        'controller' => 'Rest_Orgs',
        'action'     => 'dispatch',
    ));

// ---- Ресурсные роуты (REST по канону) ----
//
// URL один, поведение определяется HTTP-методом:
//   GET    /api/v1/orgs        → index
//   POST   /api/v1/orgs        → create
//   GET    /api/v1/orgs/<id>   → get
//   PUT    /api/v1/orgs/<id>   → update
//   PATCH  /api/v1/orgs/<id>   → update
//   DELETE /api/v1/orgs/<id>   → delete

Route::set('rest_orgs', 'api/v1/orgs(/<id>)', array(
        'id' => '[^/.,;?\n]++',
    ))
    ->defaults(array(
        'controller' => 'Rest_Orgs',
        'action'     => 'dispatch',
    ));

