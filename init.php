<?php defined('SYSPATH') or die('No direct script access.');

defined('REST_VERSION') OR define('REST_VERSION', '1.0.0');

// Маршруты REST
require_once MODPATH . 'rest/config/routes.php';
//echo Debug::vars('7', Route::all());exit;


Kohana::$config->load('menu')
    ->set('rest', array(
        'title' => 'rest',
        'url' => 'rest',
        'icon' => 'fa-cog',
        'order' => 200,
		'disabled' => false, 
		 'children' => array(
            'tasks' => array(
                'title' => 'Probe',
                'url' => 'Probe'
            ),
            'setting' => array(
                'title' => 'docs',
                'url' => 'api/v1/docs'
            ),
			
			
        )

    ));