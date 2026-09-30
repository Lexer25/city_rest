<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Rest_Docs extends Controller
{
    public $auto_render = false;

    public function action_index()
    {
        $base      = URL::site('api/v1');
        $endpoints = Kohana::$config->load('endpoints')->as_array();

        $sections_html = '';
        foreach ($endpoints as $section_key => $section) {
            $sections_html .= $this->_render_section($section_key, $section);
        }

        // JSON — чтобы JS знал структуру без повторного запроса
        $endpoints_json = json_encode($endpoints, JSON_UNESCAPED_UNICODE);

        $html = <<<HTML
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="utf-8">
<title>REST API Debug</title>
<style>
  body { font: 14px/1.5 -apple-system, "Segoe UI", Roboto, sans-serif; max-width: 1200px; margin: 20px auto; padding: 0 20px; color: #24292f; }
  h1 { border-bottom: 2px solid #d0d7de; padding-bottom: 8px; }
  h2 { margin-top: 32px; color: #0969da; border-bottom: 1px solid #d0d7de; padding-bottom: 6px; }
  h3 { margin-top: 20px; color: #24292f; }
  fieldset { border: 1px solid #d0d7de; border-radius: 6px; padding: 12px 14px; margin: 10px 0; background: #fafbfc; }
  legend { font-weight: bold; padding: 0 6px; }
  label { display: inline-block; width: 130px; vertical-align: top; font-family: monospace; }
  input[type=text], input[type=password], input[type=number], textarea, select {
    width: 480px; padding: 4px 8px; border: 1px solid #d0d7de; border-radius: 4px;
    font-family: monospace; font-size: 13px;
  }
  textarea { height: 90px; }
  button { padding: 6px 16px; cursor: pointer; border: 1px solid #0969da; background: #0969da; color: #fff; border-radius: 4px; margin-top: 8px; font-size: 14px; }
  button:hover { background: #0757b8; }
  button.secondary { background: #fff; color: #0969da; }
  button.secondary:hover { background: #ddf4ff; }
  pre { background: #f6f8fa; padding: 12px; overflow: auto; max-height: 500px; border-radius: 6px; border: 1px solid #d0d7de; font-size: 12px; }
  .token-info { background: #dafbe1; padding: 8px 12px; border-radius: 4px; font-family: monospace; font-size: 12px; margin: 8px 0; word-break: break-all; }
  .endpoint { background: #ddf4ff; padding: 2px 8px; border-radius: 4px; font-family: monospace; font-size: 12px; margin-left: 6px; }
  .method { display: inline-block; padding: 1px 8px; border-radius: 4px; font-family: monospace; font-size: 11px; color: #fff; margin-right: 6px; }
  .m-GET    { background: #1a7f37; }
  .m-POST   { background: #0969da; }
  .m-PUT    { background: #bf8700; }
  .m-PATCH  { background: #bf8700; }
  .m-DELETE { background: #cf222e; }
  .hint { color: #57606a; font-size: 12px; margin: 4px 0; }
  .status-ok  { color: #1a7f37; font-weight: bold; }
  .status-err { color: #cf222e; font-weight: bold; }
  .ep-block { border-top: 1px dashed #d0d7de; padding-top: 12px; margin-top: 12px; }
  .ep-block:first-of-type { border-top: none; padding-top: 0; margin-top: 0; }
</style>
</head>
<body>

<h1>REST API Debug</h1>
<p>Интерактивная страница для проверки REST API. Токен сохраняется в браузере.</p>

<h2>1. Аутентификация</h2>
<fieldset>
  <legend>Логин</legend>
  <div><label>username</label><input type="text" id="login_username" value="ADMIN"></div>
  <div><label>password</label><input type="password" id="login_password" value="333"></div>
  <button onclick="doLogin()">Войти</button>
  <button class="secondary" onclick="clearToken()">Сбросить токен</button>
  <div id="token_info" class="token-info">Токен не установлен</div>
</fieldset>

<h2>2. Эндпоинты</h2>
{$sections_html}

<h2>3. Произвольный запрос</h2>
<fieldset>
  <legend>HTTP-запрос</legend>
  <div><label>Method</label>
    <select id="req_method">
      <option>GET</option>
      <option>POST</option>
      <option>PUT</option>
      <option>PATCH</option>
      <option>DELETE</option>
      <option>OPTIONS</option>
    </select>
  </div>
  <div><label>URL</label><input type="text" id="req_url" value="{$base}/version" style="width: 600px;"></div>
  <div><label>Headers</label>
    <textarea id="req_headers" placeholder='{"Content-Type":"application/json"}'>{"Content-Type":"application/json"}</textarea>
  </div>
  <div><label>Body (JSON)</label>
    <textarea id="req_body" placeholder='{"key":"value"}'></textarea>
  </div>
  <div><label>Authorization</label>
    <input type="checkbox" id="req_auth" checked> Использовать Bearer-токен
  </div>
  <button onclick="doRequest()">Отправить</button>
  <button class="secondary" onclick="clearResponse()">Очистить</button>

  <h3>Ответ</h3>
  <div id="resp_status" class="hint"></div>
  <pre id="resp_body">(пусто)</pre>
</fieldset>

<script>
var REST_ENDPOINTS = {$endpoints_json};
var REST_BASE = "{$base}";

// ---------- токен ----------
function getToken() { return localStorage.getItem('rest_api_token') || ''; }
function setToken(t) {
  localStorage.setItem('rest_api_token', t);
  document.getElementById('token_info').textContent =
    t ? ('Токен: ' + t.substring(0, 60) + '...') : 'Токен не установлен';
}
function clearToken() {
  localStorage.removeItem('rest_api_token');
  document.getElementById('token_info').textContent = 'Токен сброшен';
}

// ---------- логин ----------
async function doLogin() {
  const username = document.getElementById('login_username').value;
  const password = document.getElementById('login_password').value;

  const body = new URLSearchParams();
  body.append('username', username);
  body.append('password', password);

  try {
    const resp = await fetch(REST_BASE + '/auth/login', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: body.toString()
    });
    const data = await resp.json();
    showResponse(resp, data);
    if (data.ok && data.data && data.data.token) {
      setToken(data.data.token);
    }
  } catch (e) {
    document.getElementById('resp_body').textContent = 'Ошибка: ' + e.message;
  }
}

// ---------- рендер эндпоинтов ----------
function buildUrl(ep, values) {
  let path = ep.path;
  const query = [];

  (ep.params || []).forEach(function (p) {
    const v = values[p.name];
    if (v === undefined || v === null || v === '') return;

    if (p.in === 'path') {
      path = path.replace('<' + p.name + '>', encodeURIComponent(v));
    } else if (p.in === 'query') {
      query.push(encodeURIComponent(p.name) + '=' + encodeURIComponent(v));
    }
  });

  let url = REST_BASE + '/' + path;
  if (query.length) url += '?' + query.join('&');
  return url;
}

function buildBody(ep, values) {
  const body = {};
  let has = false;

  (ep.params || []).forEach(function (p) {
    if (p.in !== 'body') return;
    const v = values[p.name];
    if (v === undefined || v === null || v === '') return;
    has = true;
    body[p.name] = (p.type === 'int') ? parseInt(v, 10) : v;
  });

  if (!has) return null;

  if (ep.body_mode === 'form') {
    const form = new URLSearchParams();
    Object.keys(body).forEach(function (k) { form.append(k, body[k]); });
    return { body: form.toString(), contentType: 'application/x-www-form-urlencoded' };
  }

  return { body: JSON.stringify(body), contentType: 'application/json' };
}

async function callEndpoint(epId) {
  const ep = findEndpoint(epId);
  if (!ep) return;

  const values = collectValues(ep);

  const url    = buildUrl(ep, values);
  const bodyObj = buildBody(ep, values);

  const headers = {};
  if (bodyObj) headers['Content-Type'] = bodyObj.contentType;
  if (ep.auth) {
    const t = getToken();
    if (t) headers['Authorization'] = 'Bearer ' + t;
  }

  const opts = { method: ep.method, headers: headers };
  if (bodyObj) opts.body = bodyObj.body;

  document.getElementById('resp_status').textContent = 'Отправка...';
  document.getElementById('resp_body').textContent = '';

  try {
    const resp = await fetch(url, opts);
    const text = await resp.text();
    let pretty = text;
    try { pretty = JSON.stringify(JSON.parse(text), null, 2); } catch (e) {}
    showResponse(resp, null, pretty, url, opts);
  } catch (e) {
    document.getElementById('resp_status').innerHTML = '<span class="status-err">Сеть</span>';
    document.getElementById('resp_body').textContent = 'Ошибка: ' + e.message;
  }
}

function findEndpoint(id) {
  for (const sec in REST_ENDPOINTS) {
    const list = REST_ENDPOINTS[sec].endpoints || [];
    for (let i = 0; i < list.length; i++) {
      if (list[i].id === id) return list[i];
    }
  }
  return null;
}

function collectValues(ep) {
  const values = {};
  (ep.params || []).forEach(function (p) {
    const el = document.getElementById('ep_' + ep.id + '_' + p.name);
    if (el) values[p.name] = el.value;
  });
  return values;
}

function showResponse(resp, data, text, url, opts) {
  const okClass = resp.ok ? 'status-ok' : 'status-err';
  document.getElementById('resp_status').innerHTML =
    'HTTP ' + resp.status + ' <span class="' + okClass + '">' +
    (resp.ok ? 'OK' : 'ERROR') + '</span>' +
    (url ? ' <span class="hint">' + opts.method + ' ' + url + '</span>' : '');

  if (data !== undefined && data !== null) {
    document.getElementById('resp_body').textContent = JSON.stringify(data, null, 2);
  } else if (text !== undefined) {
    document.getElementById('resp_body').textContent = text;
  }
}

// ---------- произвольный запрос ----------
async function doRequest() {
  const method  = document.getElementById('req_method').value;
  const url     = document.getElementById('req_url').value;
  const bodyRaw = document.getElementById('req_body').value;
  const authOn  = document.getElementById('req_auth').checked;

  let headers = {};
  try {
    headers = JSON.parse(document.getElementById('req_headers').value || '{}');
  } catch (e) {
    alert('Невалидный JSON в Headers');
    return;
  }

  if (authOn) {
    const t = getToken();
    if (t) headers['Authorization'] = 'Bearer ' + t;
  }

  const opts = { method: method, headers: headers };
  if (method !== 'GET' && method !== 'HEAD' && bodyRaw.trim() !== '') {
    opts.body = bodyRaw;
  }

  document.getElementById('resp_status').textContent = 'Отправка...';
  document.getElementById('resp_body').textContent = '';

  try {
    const resp = await fetch(url, opts);
    const text = await resp.text();
    let pretty = text;
    try { pretty = JSON.stringify(JSON.parse(text), null, 2); } catch (e) {}
    showResponse(resp, null, pretty, url, opts);
  } catch (e) {
    document.getElementById('resp_status').innerHTML = '<span class="status-err">Сеть</span>';
    document.getElementById('resp_body').textContent = 'Ошибка: ' + e.message;
  }
}

function clearResponse() {
  document.getElementById('resp_status').textContent = '';
  document.getElementById('resp_body').textContent = '(пусто)';
}

// ---------- init ----------
window.addEventListener('load', function () {
  const t = getToken();
  document.getElementById('token_info').textContent =
    t ? ('Токен: ' + t.substring(0, 60) + '...') : 'Токен не установлен';
});
</script>

</body>
</html>
HTML;

        $this->response->headers('Content-Type', 'text/html; charset=utf-8');
        $this->response->body($html);
    }

    // ------------------------------------------------------------------
    // Рендер секции с эндпоинтами
    // ------------------------------------------------------------------

    protected function _render_section($key, array $section)
    {
        $title = isset($section['title']) ? $section['title'] : $key;
        $html  = '<h3>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h3>';

        foreach ($section['endpoints'] as $ep) {
            $html .= $this->_render_endpoint($ep);
        }

        return $html;
    }

    protected function _render_endpoint(array $ep)
    {
        $method  = htmlspecialchars($ep['method'], ENT_QUOTES, 'UTF-8');
        $path    = htmlspecialchars($ep['path'], ENT_QUOTES, 'UTF-8');
        $summary = isset($ep['summary']) ? htmlspecialchars($ep['summary'], ENT_QUOTES, 'UTF-8') : '';
        $auth    = !empty($ep['auth']) ? ' <span class="hint">(нужен Bearer)</span>' : '';

        $h  = '<div class="ep-block">';
        $h .= '<div><span class="method m-' . $method . '">' . $method . '</span>'
            . '<span class="endpoint">/api/v1/' . $path . '</span>'
            . $auth
            . ' <span class="hint">' . $summary . '</span></div>';

        // Поля ввода
        if (!empty($ep['params'])) {
            $h .= '<div style="margin-top:8px;">';
            foreach ($ep['params'] as $p) {
                $name = htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8');
                $in   = isset($p['in']) ? $p['in'] : 'query';
                $ex   = isset($p['example']) ? htmlspecialchars($p['example'], ENT_QUOTES, 'UTF-8') : '';
                $type = isset($p['type']) ? $p['type'] : 'string';
                $inp_type = ($type === 'int') ? 'number' : 'text';
                $label = $name . ' <span class="hint">(' . $in . ')</span>';

                $h .= '<div><label for="ep_' . htmlspecialchars($ep['id'], ENT_QUOTES, 'UTF-8') . '_' . $name . '">'
                    . $label . '</label>'
                    . '<input type="' . $inp_type . '" '
                    . 'id="ep_' . htmlspecialchars($ep['id'], ENT_QUOTES, 'UTF-8') . '_' . $name . '" '
                    . 'value="' . $ex . '"></div>';
            }
            $h .= '</div>';
        }

        // Кнопка запуска
        $h .= '<button onclick="callEndpoint(\'' . htmlspecialchars($ep['id'], ENT_QUOTES, 'UTF-8') . '\')">Выполнить</button>';
        $h .= '</div>';

        return $h;
    }
}