<?php defined('SYSPATH') or die('No direct script access.');

class Controller_OrgTree extends Controller
{
    public $auto_render = false;

    public function action_index()
    {
        $html = <<<'HTML'
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="utf-8">
<title>Дерево организаций</title>
<style>
  body { font: 14px/1.5 -apple-system, "Segoe UI", Roboto, sans-serif; max-width: 800px; margin: 20px auto; padding: 0 20px; color: #24292f; }
  h1 { border-bottom: 2px solid #d0d7de; padding-bottom: 8px; }
  ul.tree { list-style: none; padding-left: 0; margin: 0; }
  ul.tree ul { list-style: none; padding-left: 20px; margin: 0; }
  li.node { padding: 2px 0; }
  .toggle {
    display: inline-block; width: 16px; cursor: pointer;
    user-select: none; text-align: center; color: #57606a;
    font-family: monospace;
  }
  .toggle.leaf { visibility: hidden; }
  .label { cursor: pointer; padding: 1px 4px; border-radius: 3px; }
  .label:hover { background: #ddf4ff; }
  .node.loading > .label { color: #57606a; font-style: italic; }
  .node.error > .label { color: #cf222e; }
  .id { color: #57606a; font-size: 11px; margin-left: 6px; }
</style>
</head>
<body>

<h1>Дерево организаций</h1>
<p>Клик по треугольнику — раскрыть/загрузить ветку. Клик по названию — раскрыть/свернуть.</p>

<ul class="tree" id="tree">
  <li class="node"><span class="toggle">▽</span> <span class="label">Загрузка…</span></li>
</ul>

<script>
var API_BASE = "REPLACE_API_BASE";

// --- Раскрытие/загрузка узла ---
async function toggleNode(li) {
  const ul = li.querySelector(':scope > ul');
  const isOpen = li.getAttribute('data-open') === '1';

  if (isOpen) {
    // Сворачиваем
    if (ul) ul.style.display = 'none';
    li.setAttribute('data-open', '0');
    updateToggle(li);
    return;
  }

  // Разворачиваем
  li.setAttribute('data-open', '1');
  updateToggle(li);

  if (ul) {
    // Уже загружено — просто показываем
    ul.style.display = '';
    return;
  }

  // Загружаем
  const idOrg = li.getAttribute('data-id');
  li.classList.add('loading');

  try {
    const resp = await fetch(API_BASE + '/orgs/' + idOrg + '/children', {
      headers: { 'Accept': 'application/json' }
    });
    const data = await resp.json();

    li.classList.remove('loading');

    if (!data.ok || !data.data || !data.data.items) {
      li.classList.add('error');
      return;
    }

    const items = data.data.items;
    if (!items.length) {
      li.classList.add('leaf');
      return;
    }

    const newUl = document.createElement('ul');
    items.forEach(function (it) {
      newUl.appendChild(buildNode(it));
    });

    // Добавляем UL после label
    li.appendChild(newUl);

  } catch (e) {
    li.classList.remove('loading');
    li.classList.add('error');
    console.error(e);
  }
}

// --- Построение DOM-узла ---
function buildNode(it) {
  const li = document.createElement('li');
  li.className = 'node';
  li.setAttribute('data-id', it.id_org);
  li.setAttribute('data-open', '0');

  const toggle = document.createElement('span');
  toggle.className = 'toggle' + (it.has_children ? '' : ' leaf');
  toggle.textContent = '▽';
  toggle.onclick = function (e) {
    e.stopPropagation();
    if (it.has_children) toggleNode(li);
  };

  const label = document.createElement('span');
  label.className = 'label';
  label.textContent = it.name || '(без названия)';
  label.onclick = function () {
    if (it.has_children) toggleNode(li);
  };

  const idSpan = document.createElement('span');
  idSpan.className = 'id';
  idSpan.textContent = '#' + it.id_org;

  li.appendChild(toggle);
  li.appendChild(label);
  li.appendChild(idSpan);

  return li;
}

// --- Обновление символа toggle ---
function updateToggle(li) {
  const t = li.querySelector(':scope > .toggle');
  if (!t) return;
  const isOpen = li.getAttribute('data-open') === '1';
  t.textContent = isOpen ? '▽' : '▷';
}

// --- Инициализация: загрузка корней ---
async function loadRoots() {
  const tree = document.getElementById('tree');
  tree.innerHTML = '<li class="node"><span class="toggle">▽</span> <span class="label">Загрузка…</span></li>';

  try {
    const resp = await fetch(API_BASE + '/orgs/roots', {
      headers: { 'Accept': 'application/json' }
    });
    const data = await resp.json();

    tree.innerHTML = '';

    if (!data.ok || !data.data || !data.data.items) {
      tree.innerHTML = '<li class="node error"><span class="label">Ошибка загрузки</span></li>';
      return;
    }

    const items = data.data.items;
    if (!items.length) {
      tree.innerHTML = '<li class="node"><span class="label">Организаций нет</span></li>';
      return;
    }

    items.forEach(function (it) {
      tree.appendChild(buildNode(it));
    });

  } catch (e) {
    tree.innerHTML = '<li class="node error"><span class="label">Сеть недоступна</span></li>';
    console.error(e);
  }
}

window.addEventListener('load', loadRoots);
</script>

</body>
</html>
HTML;

        // Подставляем реальный base_url в JS
        $api_base = URL::site('api/v1', TRUE);   // TRUE — с протоколом и хостом
        $html = str_replace('REPLACE_API_BASE', $api_base, $html);

        $this->response->headers('Content-Type', 'text/html; charset=utf-8');
        $this->response->body($html);
    }
}