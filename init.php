<?php defined('SYSPATH') or die('No direct script access.');

defined('REST_VERSION') OR define('REST_VERSION', '1.0.0');

// Маршруты REST
require_once MODPATH . 'rest/config/routes.php';
//echo Debug::vars('7', Route::all());exit;
