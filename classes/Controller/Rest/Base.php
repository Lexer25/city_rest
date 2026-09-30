<?php defined('SYSPATH') or die('No direct script access.');

abstract class Controller_Rest_Base extends Controller
{
    public $auto_render = false;

    protected $_resource_name = '';
    protected $_model_class   = '';
    protected $_model         = null;
    protected $_user          = null;
    protected $_request_id    = '';

    public function before()
    {
        parent::before();
        $this->response->headers('Content-Type', 'application/json; charset=utf-8');

        $cors = Kohana::$config->load('rest.cors');
        if (!empty($cors['enabled'])) {
            $this->response->headers('Access-Control-Allow-Origin',  $cors['allow_origin']);
            $this->response->headers('Access-Control-Allow-Methods', $cors['allow_methods']);
            $this->response->headers('Access-Control-Allow-Headers', $cors['allow_headers']);
        }

        // Preflight-запрос — отвечаем 204 и прерываем выполнение.
        // В Kohana 3.3 после before() action всё равно вызывается,
        // поэтому явно переключаемся на action_options.
        if ($this->request->method() === 'OPTIONS') {
            $this->response->status(204);
            $this->request->action('options');
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

    /**
     * Диспетчер: разводит запрос по action'ам в зависимости
     * от HTTP-метода и наличия <id> в URL.
     *
     * Ресурсный контроллер реализует только:
     *   action_index()   — GET    /resource
     *   action_get()     — GET    /resource/<id>
     *   action_create()  — POST   /resource
     *   action_update()  — PUT    /resource/<id>  (или PATCH)
     *   action_delete()  — DELETE /resource/<id>
     */
    public function action_dispatch()
    {
        $method = strtoupper($this->request->method());
        $id     = (string) $this->request->param('id');

        // Карта: HTTP-метод → имя action'а (без префикса action_)
        // null означает «метод недопустим для такого URL».
        $map = array(
            'GET'    => $id === '' ? 'index'  : 'get',
            'HEAD'   => $id === '' ? 'index'  : 'get',
            'POST'   => $id === '' ? 'create' : null,
            'PUT'    => $id !== '' ? 'update' : null,
            'PATCH'  => $id !== '' ? 'update' : null,
            'DELETE' => $id !== '' ? 'delete' : null,
        );

        if (!array_key_exists($method, $map) || $map[$method] === null) {
            $this->response->headers('Allow', 'GET, HEAD, POST, PUT, PATCH, DELETE, OPTIONS');
            $this->_respond(Rest_Response::error(
                Rest_Error::BAD_REQUEST,
                'Method ' . $method . ' is not allowed for this URL',
                405
            ));
            return;
        }

        $action = 'action_' . $map[$method];
        if (!method_exists($this, $action)) {
            $this->_respond(Rest_Response::error(
                Rest_Error::INTERNAL_ERROR,
                'Action ' . $action . ' is not implemented for resource ' . $this->_resource_name,
                500
            ));
            return;
        }

        $this->{$action}();
    }

    /**
     * Обработчик preflight-запросов. Ничего не делает — заголовки
     * CORS уже проставлены в before(), статус 204 тоже.
     */
    public function action_options()
    {
        $this->response->status(204);
        $this->response->body('');
    }

    // ------------------------------------------------------------------
    // Хелперы
    // ------------------------------------------------------------------

    protected function _require_auth()
    {
        if ($this->_user !== null) {
            return true;
        }
        $this->_respond(Rest_Response::error(
            Rest_Error::UNAUTHORIZED,
            'Authentication required',
            401
        ));
        return false;
    }

    protected function _require_model()
    {
        if ($this->_model !== null) {
            return true;
        }
        $this->_respond(Rest_Response::error(
            Rest_Error::INTERNAL_ERROR,
            'Model ' . $this->_model_class . ' is not available',
            500
        ));
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
        $msg    = isset($res['error'])  ? (string) $res['error']  : 'Unknown error';
        $status = isset($res['status']) ? (int)    $res['status'] : Rest_Error::http_status($code);
        return Rest_Response::error($code, $msg, $status);
    }
}