<?php defined('SYSPATH') or die('No direct script access.');

return array(

    'auth' => array(
        'title' => 'Аутентификация',
        'endpoints' => array(

            array(
                'id'      => 'auth_login',
                'method'  => 'POST',
                'path'    => 'auth/login',
                'summary' => 'Получить JWT',
                'auth'    => false,
                'params'  => array(
                    array('name' => 'username', 'in' => 'body', 'type' => 'string', 'required' => true,  'example' => 'ADMIN'),
                    array('name' => 'password', 'in' => 'body', 'type' => 'string', 'required' => true,  'example' => '333'),
                ),
                'body_mode' => 'form',   // urlencoded, как в action_login
            ),

            array(
                'id'      => 'auth_me',
                'method'  => 'GET',
                'path'    => 'auth/me',
                'summary' => 'Текущий пользователь',
                'auth'    => true,
                'params'  => array(),
            ),

            array(
                'id'      => 'auth_logout',
                'method'  => 'POST',
                'path'    => 'auth/logout',
                'summary' => 'Выход (JWT не отзывается)',
                'auth'    => true,
                'params'  => array(),
            ),
        ),
    ),

    'system' => array(
        'title' => 'Система',
        'endpoints' => array(

            array(
                'id'      => 'version',
                'method'  => 'GET',
                'path'    => 'version',
                'summary' => 'Версия API',
                'auth'    => false,
                'params'  => array(),
            ),
        ),
    ),

    'orgs' => array(
        'title' => 'Организации',
        'endpoints' => array(

            array(
                'id'      => 'orgs_list',
                'method'  => 'GET',
                'path'    => 'orgs',
                'summary' => 'Список организаций',
                'auth'    => false,
                'params'  => array(
                    array('name' => 'limit',    'in' => 'query', 'type' => 'int',    'example' => '10'),
                    array('name' => 'offset',   'in' => 'query', 'type' => 'int',    'example' => '0'),
                    array('name' => 'sort',     'in' => 'query', 'type' => 'string', 'example' => 'id_org'),
                    array('name' => 'order',    'in' => 'query', 'type' => 'string', 'example' => 'asc'),
                    array('name' => 'id_parent','in' => 'query', 'type' => 'int',    'example' => ''),
                    array('name' => 'divcode',  'in' => 'query', 'type' => 'string', 'example' => ''),
                ),
            ),

            array(
                'id'      => 'orgs_get',
                'method'  => 'GET',
                'path'    => 'orgs/<id>',
                'summary' => 'Одна организация по GUID',
                'auth'    => false,
                'params'  => array(
                    array('name' => 'id', 'in' => 'path', 'type' => 'string', 'required' => true, 'example' => '12345678-1234-1234-1234-123456789012'),
                ),
            ),

            array(
                'id'      => 'orgs_create',
                'method'  => 'POST',
                'path'    => 'orgs',
                'summary' => 'Создать организацию',
                'auth'    => true,
                'params'  => array(
                    array('name' => 'name',              'in' => 'body', 'type' => 'string', 'example' => 'Acme'),
                    array('name' => 'id_parent',         'in' => 'body', 'type' => 'int',    'example' => '1'),
                    array('name' => 'flag',              'in' => 'body', 'type' => 'int',    'example' => '0'),
                    array('name' => 'id_def_accessname', 'in' => 'body', 'type' => 'int',    'example' => ''),
                    array('name' => 'divcode',           'in' => 'body', 'type' => 'string', 'example' => 'ACME'),
                    array('name' => 'guid',              'in' => 'body', 'type' => 'string', 'example' => '12345678-1234-1234-1234-123456789012'),
                ),
                'body_mode' => 'json',
            ),

            array(
                'id'      => 'orgs_update',
                'method'  => 'PUT',
                'path'    => 'orgs/<id>',
                'summary' => 'Обновить организацию',
                'auth'    => true,
                'params'  => array(
                    array('name' => 'id',        'in' => 'path', 'type' => 'string', 'required' => true, 'example' => '12345678-1234-1234-1234-123456789012'),
                    array('name' => 'name',      'in' => 'body', 'type' => 'string', 'example' => 'Acme Updated'),
                    array('name' => 'id_parent', 'in' => 'body', 'type' => 'int',    'example' => '1'),
                    array('name' => 'divcode',   'in' => 'body', 'type' => 'string', 'example' => 'ACME'),
                ),
                'body_mode' => 'json',
            ),

            array(
                'id'      => 'orgs_delete',
                'method'  => 'DELETE',
                'path'    => 'orgs/<id>',
                'summary' => 'Удалить организацию',
                'auth'    => true,
                'params'  => array(
                    array('name' => 'id', 'in' => 'path', 'type' => 'string', 'required' => true, 'example' => '12345678-1234-1234-1234-123456789012'),
                ),
            ),
        ),
    ),

);