<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Rest_Auth extends Controller
{
    public $auto_render = false;

    public function before()
    {
        parent::before();
        $this->response->headers('Content-Type', 'application/json; charset=utf-8');
    }

    public function action_index()
    {
        $this->_respond(Rest_Response::ok(array(
            'endpoints' => array(
                'POST /api/v1/auth/login',
                'POST /api/v1/auth/logout',
                'GET  /api/v1/auth/me',
            ),
        )));
    }

    public function action_login()
    {
        $login = trim((string) $this->_param('username'));
        $pass  = (string) $this->_param('password');

        if ($login === '' || $pass === '') {
            $this->_respond(Rest_Response::error(
                Rest_Error::VALIDATION_ERROR,
                'username and password are required',
                400
            ));
            return;
        }
        $u = Rest_Auth::login($login, $pass);
Kohana::$log->add(Log::INFO, '39 u  ' .Debug::vars($u));
        if ($u === null) {
            $this->_respond(Rest_Response::error(
                Rest_Error::UNAUTHORIZED,
                'Invalid credentials',
                401
            ));
            return;
        }

        $token = Rest_Auth::issue_token($u);
		Kohana::$log->add(Log::INFO, '51 token ' .Debug::vars($token));
        $ttl   = (int) Kohana::$config->load('rest.jwt.ttl');

Kohana::$log->add(Log::INFO, '51 ttl ' .Debug::vars($ttl));
        $this->_respond(Rest_Response::ok(array(
            'token'      => $token,
            'token_type' => 'Bearer',
            'expires_in' => $ttl,
            'user'       => array(
                'guid'  => $u['guid'],
                'login' => $u['login'],
            ),
        )));
    }

    public function action_logout()
    {
        $this->_respond(Rest_Response::ok(array('logged_out' => true)));
    }

    public function action_me()
    {
        $t = Rest_Jwt::from_request($this->request);
        $p = Rest_Jwt::decode($t);

        if ($p === null) {
            $this->_respond(Rest_Response::error(
                Rest_Error::UNAUTHORIZED,
                'Authentication required',
                401
            ));
            return;
        }

        $this->_respond(Rest_Response::ok(array(
            'user' => array(
                'guid'  => Arr::get($p, 'sub', ''),
                'login' => Arr::get($p, 'login', ''),
            ),
        )));
    }

    /**
     * Читает параметр: сначала POST, потом query, потом JSON-тело.
     *
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    protected function _param($key, $default = '')
    {
        $v = $this->request->post($key);
        if ($v !== null) {
            return $v;
        }

        $v = $this->request->query($key);
        if ($v !== null) {
            return $v;
        }

        $raw = $this->request->body();
        if ($raw !== '' && $raw !== null) {
            $j = json_decode($raw, true);
            if (is_array($j) && array_key_exists($key, $j)) {
                return $j[$key];
            }
        }

        return $default;
    }

    protected function _respond(array $data)
    {
        Rest_Response::render($this->response, $data);
    }
}
