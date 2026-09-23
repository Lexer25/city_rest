<?php defined('SYSPATH') or die('No direct script access.');

abstract class Controller_Rest_Base extends Controller
{
    public $auto_render = false;
    protected $_resource_name = '';
    protected $_model_class = '';
    protected $_model = null;
    protected $_user = null;
    protected $_request_id = '';

    public function before()
    {
        parent::before();
        $this->response->headers('Content-Type', 'application/json; charset=utf-8');

        $cors = Kohana::$config->load('rest.cors');
        if (!empty($cors['enabled'])) {
            $this->response->headers('Access-Control-Allow-Origin', $cors['allow_origin']);
            $this->response->headers('Access-Control-Allow-Methods', $cors['allow_methods']);
            $this->response->headers('Access-Control-Allow-Headers', $cors['allow_headers']);
        }

        if ($this->request->method() === 'OPTIONS') {
            $this->response->status(204);
            return;
        }

        $this->_request_id = (string) $this->request->headers('X-Request-ID');
        if ($this->_request_id === '') {
            $this->_request_id = 'req-' . uniqid('', true);
        }

        $token = Rest_Jwt::from_request($this->request);
        if ($token !== '') {
            $this->_user = Rest_Jwt::decode($token);
        }

        if ($this->_model_class !== '' && class_exists($this->_model_class)) {
            $this->_model = new $this->_model_class();
        }
    }

    protected function _require_auth()
    {
        if ($this->_user !== null) {
            return true;
        }
        $this->_respond(Rest_Response::error(Rest_Error::UNAUTHORIZED, 'Authentication required', 401));
        return false;
    }

    protected function _audit_context()
    {
        if ($this->_user === null) {
            return;
        }
        Rest_Audit::set_context(array(
            'user_id'    => Arr::get($this->_user, 'sub', ''),
            'user_login' => Arr::get($this->_user, 'login', ''),
            'user_ip'    => Request::$client_ip,
            'request_id' => $this->_request_id,
        ));
    }

    protected function _param($key, $default = null)
    {
        $v = $this->request->post($key);
        if ($v !== null) {
            return $v;
        }
        $v = $this->request->query($key);
        if ($v !== null) {
            return $v;
        }
        $j = $this->_json_body();
        if (is_array($j) && array_key_exists($key, $j)) {
            return $j[$key];
        }
        return $default;
    }

    protected function _json_body()
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }
        $raw = $this->request->body();
        if ($raw === '' || $raw === null) {
            $cache = array();
            return $cache;
        }
        $d = json_decode($raw, true);
        $cache = is_array($d) ? $d : array();
        return $cache;
    }

    protected function _respond(array $data)
    {
        Rest_Response::render($this->response, $data);
    }

    protected function _wrap_model_error(array $res, $code = Rest_Error::VALIDATION_ERROR)
    {
        $msg    = isset($res['error']) ? (string) $res['error'] : 'Unknown error';
        $status = isset($res['status']) ? (int) $res['status'] : Rest_Error::http_status($code);
        return Rest_Response::error($code, $msg, $status);
    }
}