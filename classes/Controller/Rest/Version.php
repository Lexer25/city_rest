<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Rest_Version extends Controller
{
    public $auto_render = false;

    public function before()
    {
        parent::before();
        $this->response->headers('Content-Type', 'application/json; charset=utf-8');
    }

    public function action_index()
    {
		Rest_Response::render($this->response, Rest_Response::ok(array(
            'rest_version'    => defined('REST_VERSION') ? REST_VERSION : 'unknown',
            'parsec_version'  => defined('PARSEC_VERSION') ? PARSEC_VERSION : null,
            'kohana_version'  => Kohana::VERSION,
            'php_version'     => PHP_VERSION,
            'time'            => date('c'),
        )));
    }
}
