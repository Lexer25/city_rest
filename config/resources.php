<?php defined('SYSPATH') or die('No direct script access.');

return array(

    'orgs' => array(
        'table'        => 'ORGANIZATION',
        'primary_key'  => 'ID_ORG',
        'guid_field'   => 'GUID',
        'order_by'     => 'ID_ORG',      // дефолтная сортировка

        // Описание полей: как REST-клиент видит сущность
        // type       — тип для валидации и документации
        // column     — реальная колонка в Firebird
        // readonly   — нельзя менять через API
        // nullable   — допускается NULL
        // max        — для строк
        'fields' => array(
            'id_org'            => array('type' => 'int',      'column' => 'ID_ORG',            'readonly' => true),
            'id_db'             => array('type' => 'int',      'column' => 'ID_DB',             'readonly' => true),
            'name'              => array('type' => 'string',   'column' => 'NAME',              'max' => 50,  'nullable' => true),
            'id_parent'         => array('type' => 'int',      'column' => 'ID_PARENT',         'min' => 0),
            'flag'              => array('type' => 'int',      'column' => 'FLAG',              'min' => 0, 'max' => 32767),
            'id_def_accessname' => array('type' => 'int',      'column' => 'ID_DEF_ACCESSNAME', 'nullable' => true),
            'divcode'           => array('type' => 'string',   'column' => 'DIVCODE',           'max' => 50),
            'guid'              => array('type' => 'string',   'column' => 'GUID',              'max' => 50,  'nullable' => true),
            'time_stamp'        => array('type' => 'datetime', 'column' => 'TIME_STAMP',        'readonly' => true),
        ),

        // Что можно передавать в ?filter: ?id_parent=5&divcode=ABC
        'filters'  => array('id_db', 'id_parent', 'flag', 'divcode', 'guid'),

        // Что можно использовать в ?sort=...&order=asc|desc
        'sortable' => array('id_org', 'name', 'id_parent', 'divcode', 'time_stamp'),

        // Что разрешено создавать/обновлять
        'writable' => array('name', 'id_parent', 'flag', 'id_def_accessname', 'divcode', 'guid'),

        // Обязательные при создании
        'required_on_create' => array('divcode'),
    ),

);
