<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Rest_Docs extends Controller
{
    public $auto_render = false;

    public function action_index()
    {
        $base = URL::site('api/v1');
        $html = <<<HTML
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="utf-8">
<title>REST API Debug</title>
<style>
  body { font: 14px/1.5 -apple-system, "Segoe UI", Roboto, sans-serif; max-width: 1100px; margin: 20px auto; padding: 0 20px; color: #24292f; }
  h1 { border-bottom: 2px solid #d0d7de; padding-bottom: 8px; }
  h2 { margin-top: 32px; color: #0969da; border-bottom: 1px solid #d0d7de; padding-bottom: 6px; }
  fieldset { border: 1px solid #d0d7de; border-radius: 6px; padding: 12px 14px; margin: 10px 0; background: #fafbfc; }
  legend { font-weight: bold; padding: 0 6px; }
  label { display: inline-block; width: 120px; vertical-align: top; }
  input[type=text], input[type=password], textarea, select { width: 500px; padding: 4px 8px; border: 1px solid #d0d7de; border-radius: 4px; font-family: monospace; }
  textarea { height: 100px; }
  button { padding: 6px 16px; cursor: pointer; border: 1px solid #0969da; background: #0969da; color: #fff; border-radius: 4px; margin-top: 8px; font-size: 14px; }
  button:hover { background: #0757b8; }
  button.secondary { background: #fff; color: #0969da; }
  button.secondary:hover { background: #ddf4ff; }
  pre { background: #f6f8fa; padding: 12px; overflow: auto; max-height: 500px; border-radius: 6px; border: 1px solid #d0d7de; font-size: 12px; }
  .token-info { background: #dafbe1; padding: 8px 12px; border-radius: 4px; font-family: monospace; font-size: 12px; margin: 8px 0; word-break: break-all; }
  .endpoint { background: #ddf4ff; padding: 2px 8px; border-radius: 4px; font-family: monospace; font-size: 12px; margin-left: 6px; }
  .hint { color: #57606a; font-size: 12px; margin: 4px 0; }
  .status-ok { color: #1a7f37; font-weight: bold; }
  .status-err { color: #cf222e; font-weight: bold; }
</style>
</head>
<body>

<h1>REST API Debug</h1>
<p>Интерактивная страница для проверки REST API. Токен сохраняется в браузере.</p>

<!-- ====== ЛОГИН ====== -->
<h2>1. Аутентификация <span class="endpoint">POST /api/v1/auth/login</span></h2>
<fieldset>
  <legend>Логин</legend>
  <div><label>username</label><input type="text" id="login_username" value="ADMIN"></div>
  <div><label>password</label><input type="password" id="login_password" value="333"></div>
  <button onclick="doLogin()">Войти</button>
  <button class="secondary" onclick="clearToken()">Сбросить токен</button>
  <div id="token_info" class="token-info">Токен не установлен</div>
</fieldset>

<!-- ====== ПРОИЗВОЛЬНЫЙ ЗАПРОС ====== -->
<h2>2. Произвольный запрос</h2>
<fieldset>
  <legend>HTTP-запрос</legend>
  <div><label>Method</label>
    <select id="req_method">
      <option>GET</option>
      <option>POST</option>
      <option>PUT</option>
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

<!-- ====== БЫСТРЫЕ КНОПКИ ====== -->
<h2>3. Быстрые эндпоинты</h2>
<fieldset>
  <legend>Готовые запросы</legend>

  <div>
    <button onclick="quickGet('{$base}/version')">GET /version</button>
    <button onclick="quickGet('{$base}/auth/me')">GET /auth/me</button>
    <button onclick="quickGet('{$base}/orgs')">GET /orgs</button>
    <button onclick="quickGet('{$base}/persons')">GET /persons</button>
  </div>
</fieldset>

<script>
function getToken() {
  return localStorage.getItem('rest_api_token') || '';
}

function setToken(token) {
  localStorage.setItem('rest_api_token', token);
  document.getElementById('token_info').textContent = token ? 'Токен: ' + token.substring(0, 60) + '...' : 'Токен не установлен';
}

function clearToken() {
  localStorage.removeItem('rest_api_token');
  document.getElementById('token_info').textContent = 'Токен сброшен';
}

async function doLogin() {
  const username = document.getElementById('login_username').value;
  const password = document.getElementById('login_password').value;

  const body = new URLSearchParams();
  body.append('username', username);
  body.append('password', password);

  try {
    const resp = await fetch('{$base}/auth/login', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: body.toString()
    });

    const data = await resp.json();
    document.getElementById('resp_status').innerHTML =
      'HTTP ' + resp.status + ' <span class="' + (resp.ok ? 'status-ok' : 'status-err') + '">' + (resp.ok ? 'OK' : 'ERROR') + '</span>';
    document.getElementById('resp_body').textContent = JSON.stringify(data, null, 2);

    if (data.ok && data.data && data.data.token) {
      setToken(data.data.token);
    }
  } catch (e) {
    document.getElementById('resp_body').textContent = 'Ошибка: ' + e.message;
  }
}

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

  const opts = { method, headers };

  if (method !== 'GET' && method !== 'HEAD' && bodyRaw.trim() !== '') {
    opts.body = bodyRaw;
  }

  document.getElementById('resp_status').textContent = 'Отправка...';
  document.getElementById('resp_body').textContent = '';

  try {
    const resp = await fetch(url, opts);
    const text = await resp.text();
    let pretty = text;
    try {
      pretty = JSON.stringify(JSON.parse(text), null, 2);
    } catch (e) { /* не JSON — оставляем как есть */ }

    document.getElementById('resp_status').innerHTML =
      'HTTP ' + resp.status + ' <span class="' + (resp.ok ? 'status-ok' : 'status-err') + '">' + (resp.ok ? 'OK' : 'ERROR') + '</span>';
    document.getElementById('resp_body').textContent = pretty;
  } catch (e) {
    document.getElementById('resp_status').innerHTML = '<span class="status-err">Сеть</span>';
    document.getElementById('resp_body').textContent = 'Ошибка: ' + e.message;
  }
}

async function quickGet(url) {
  document.getElementById('req_method').value = 'GET';
  document.getElementById('req_url').value = url;
  document.getElementById('req_body').value = '';
  document.getElementById('req_headers').value = '{}';
  await doRequest();
}

function clearResponse() {
  document.getElementById('resp_status').textContent = '';
  document.getElementById('resp_body').textContent = '(пусто)';
}

// Инициализация
window.addEventListener('load', function () {
  const t = getToken();
  document.getElementById('token_info').textContent = t ? 'Токен: ' + t.substring(0, 60) + '...' : 'Токен не установлен';
});
</script>

</body>
</html>
HTML;
        $this->response->headers('Content-Type', 'text/html; charset=utf-8');
        $this->response->body($html);
    }
}