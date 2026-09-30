<?php defined('SYSPATH') or die('No direct script access.');

class Model_Orgs
{
    /** @var array */
    protected $_cfg;

    /** @var Database */
    protected $_db;

    public function __construct()
    {
        $cfg = Kohana::$config->load('resources');

        if ($cfg === null) {
            throw new Kohana_Exception(
                'Config group "resources" is not loaded. ' .
                'Check rest/config/resources.php and Kohana::modules().'
            );
        }

        $this->_cfg = $cfg->get('orgs');

        if (empty($this->_cfg)) {
            throw new Kohana_Exception(
                'Config "resources.orgs" is empty or missing.'
            );
        }

        $this->_db = Database::instance('fb');
    }

    // ------------------------------------------------------------------
    // SELECT (список) + COUNT
    // ------------------------------------------------------------------

    /**
     * @param array  $filters  из query string, только разрешённые
     * @param int    $limit
     * @param int    $offset
     * @param string $sort     имя поля (клиентское)
     * @param string $order    asc|desc
     * @return array {items, total} | {error, status}
     */
    public function search(array $filters, $limit, $offset, $sort, $order)
    {
        try {
            $limit  = (int) $limit;
            $offset = (int) $offset;
            if ($limit <= 0) {
                $limit = 50;
            }
            if ($offset < 0) {
                $offset = 0;
            }

            $where = $this->_build_where($filters, $params);
            $order = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';
            $col   = $this->_resolve_sort($sort);

            // Firebird: FIRST n SKIP m — работает во всех версиях,
            // в отличие от ROWS n TO m (появился только в FB 2.0).
            // SKIP — 0-based offset.
            $sql = 'SELECT FIRST ' . $limit . ' SKIP ' . $offset . ' '
                 . $this->_select_list()
                 . ' FROM ' . $this->_cfg['table']
                 . $where
                 . ' ORDER BY ' . $col . ' ' . $order;

            $q = DB::query(Database::SELECT, $sql);
            foreach ($params as $i => $v) {
                $q->param($i, $v);
            }
            $rows = $q->execute($this->_db)->as_array();

            $items = $this->_map_rows($rows);
            $total = $this->_count($filters);

            return array('items' => $items, 'total' => $total);

        } catch (Exception $e) {
            Kohana::$log->add(Log::ERROR, 'Model_Orgs::search: ' . $e->getMessage());
            return array('error' => 'DB error', 'status' => 500);
        }
    }

    // ------------------------------------------------------------------
    // SELECT (одна запись)
    // ------------------------------------------------------------------

    /**
     * Ищем по GUID — так REST-клиент работает с «внешним» идентификатором.
     */
    public function find_by_guid($guid)
    {
        return $this->_find_one('GUID', $guid);
    }

    /**
     * Поиск по первичному ключу.
     */
    public function find_by_id($id)
    {
        return $this->_find_one('ID_ORG', (int) $id);
    }

    protected function _find_one($column, $value)
    {
        try {
         $sql = 'SELECT ' . $this->_select_one()
     . ' FROM ' . $this->_cfg['table']
     . ' WHERE ' . $column . ' = ' . $this->_sql_literal($value);

$row = DB::query(Database::SELECT, $sql)
    ->execute($this->_db)
    ->current();

            if (!$row) {
                return array('item' => null);
            }
            return array('item' => $this->_map_row($row));

        } catch (Exception $e) {
            Kohana::$log->add(Log::ERROR, 'Model_Orgs::_find_one: ' . $e->getMessage());
            return array('error' => 'DB error', 'status' => 500);
        }
    }

    // ------------------------------------------------------------------
    // INSERT
    // ------------------------------------------------------------------

    /**
     * @param array $data  клиентские поля (name, id_parent, ...)
     * @return array {item} | {error, status}
     */
public function create(array $data)
{
    $errors = $this->_validate($data, true);
    if (!empty($errors)) {
        return array('error' => 'validation failed', 'status' => 400, 'fields' => $errors);
    }

    $id_org = $this->_next_id_org();

    $cols = array('ID_ORG', 'ID_DB', 'TIME_STAMP');
    $vals = array(
        (int) $id_org,
        isset($data['id_db']) ? (int) $data['id_db'] : 1,
        'CURRENT_TIMESTAMP',
    );

    foreach ($this->_cfg['writable'] as $name) {
        if (!array_key_exists($name, $data)) {
            continue;
        }
        $f = $this->_cfg['fields'][$name];
        $cols[] = $f['column'];
        $vals[] = $this->_sql_literal($this->_cast_for_db($f, $data[$name]));
    }

    $sql = 'INSERT INTO ' . $this->_cfg['table']
         . ' (' . implode(', ', $cols) . ')'
         . ' VALUES (' . implode(', ', $vals) . ')';

    try {
        DB::query(Database::INSERT, $sql)->execute($this->_db);
        return $this->find_by_id($id_org);
    } catch (Exception $e) {
        Kohana::$log->add(Log::ERROR, 'Model_Orgs::create: ' . $e->getMessage());
        Kohana::$log->add(Log::ERROR, 'Model_Orgs::create SQL: ' . $sql);
        return array('error' => 'DB error', 'status' => 500);
    }
}

    // ------------------------------------------------------------------
    // UPDATE
    // ------------------------------------------------------------------

    public function update($guid, array $data)
    {
        // Сначала найдём запись — она даст нам ID_ORG
        $found = $this->find_by_guid($guid);
        if (!empty($found['error'])) {
            return $found;
        }
        if (empty($found['item'])) {
            return array('item' => null);
        }

        $errors = $this->_validate($data, false);
        if (!empty($errors)) {
            return array('error' => 'validation failed', 'status' => 400, 'fields' => $errors);
        }

        $set    = array();
        $params = array();
        $i      = 1;

        foreach ($this->_cfg['writable'] as $name) {
            if (!array_key_exists($name, $data)) {
                continue;
            }
            $f = $this->_cfg['fields'][$name];
            $set[] = $f['column'] . ' = ?';
            $params[$i++] = $this->_cast_for_db($f, $data[$name]);
        }

        if (empty($set)) {
            // нечего обновлять — вернём текущее состояние
            return $found;
        }

        $set[] = 'TIME_STAMP = CURRENT_TIMESTAMP';

        $sql = 'UPDATE ' . $this->_cfg['table']
             . ' SET ' . implode(', ', $set)
             . ' WHERE ID_ORG = ?';

        $params[$i++] = (int) $found['item']['id_org'];

        try {
            $q = DB::query(Database::UPDATE, $sql);
            foreach ($params as $k => $v) {
                $q->param($k, $v);
            }
            $q->execute($this->_db);

            return $this->find_by_guid($guid);

        } catch (Exception $e) {
            Kohana::$log->add(Log::ERROR, 'Model_Orgs::update: ' . $e->getMessage());
            return array('error' => 'DB error', 'status' => 500);
        }
    }

    // ------------------------------------------------------------------
    // DELETE
    // ------------------------------------------------------------------

    public function delete($guid)
    {
        $found = $this->find_by_guid($guid);
        if (!empty($found['error'])) {
            return $found;
        }
        if (empty($found['item'])) {
            return array('deleted' => false);
        }

        try {
            $sql = 'DELETE FROM ' . $this->_cfg['table'] . ' WHERE ID_ORG = ?';
            DB::query(Database::DELETE, $sql)
                ->param(1, (int) $found['item']['id_org'])
                ->execute($this->_db);

            return array('deleted' => true);

        } catch (Exception $e) {
            Kohana::$log->add(Log::ERROR, 'Model_Orgs::delete: ' . $e->getMessage());
            return array('error' => 'DB error', 'status' => 500);
        }
    }

    // ------------------------------------------------------------------
    // Внутренние помощники
    // ------------------------------------------------------------------

    protected function _select_list()
    {
        $cols = array();
        foreach ($this->_cfg['fields'] as $name => $f) {
            // Без AS: Firebird всё равно приведёт незакавыченный
            // алиас к UPPERCASE, а _map_row() уже умеет искать
            // значение по имени реальной колонки.
            $cols[] = $f['column'];
        }
        return implode(', ', $cols);
    }

    protected function _select_one()
    {
        return $this->_select_list();
    }

    protected function _build_where(array $filters, &$params)
    {
        $params = array();
        $i = 1;
        $where = array();

        foreach ($filters as $key => $val) {
            if (!in_array($key, $this->_cfg['filters'], true)) {
                continue;
            }
            if (!isset($this->_cfg['fields'][$key])) {
                continue;
            }
            $col = $this->_cfg['fields'][$key]['column'];
            $where[] = $col . ' = ?';
            $params[$i++] = $val;
        }

        return $where ? ' WHERE ' . implode(' AND ', $where) : '';
    }

    protected function _resolve_sort($sort)
    {
        if (in_array($sort, $this->_cfg['sortable'], true)
            && isset($this->_cfg['fields'][$sort])
        ) {
            return $this->_cfg['fields'][$sort]['column'];
        }
        return $this->_cfg['order_by'];
    }

    protected function _count(array $filters)
    {
        $params = array();
        $where  = $this->_build_where($filters, $params);

        $sql = 'SELECT COUNT(*) AS CNT FROM ' . $this->_cfg['table'] . $where;

        try {
            $q = DB::query(Database::SELECT, $sql);
            foreach ($params as $k => $v) {
                $q->param($k, $v);
            }
            $row = $q->execute($this->_db)->current();
            if (!$row) {
                return 0;
            }
            $row = array_change_key_case($row, CASE_UPPER);
            return (int) (isset($row['CNT']) ? $row['CNT'] : 0);
        } catch (Exception $e) {
            Kohana::$log->add(Log::ERROR, 'Model_Orgs::_count: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Firebird вернул UPPERCASE — а мы просили AS с маленькими буквами.
     * Проверьте на своей версии драйвера: иногда ключи приходят как есть,
     * иногда — в UPPERCASE. Здесь — универсальный маппинг по колонке.
     */
    protected function _map_rows(array $rows)
    {
        $out = array();
        foreach ($rows as $r) {
            $out[] = $this->_map_row($r);
        }
        return $out;
    }

    protected function _map_row(array $row)
    {
        // Firebird обычно возвращает ключи в UPPERCASE,
        // но разные драйверы ведут себя по-разному.
        // Готовим две версии строки — с исходным регистром и с UPPERCASE.
        $upper = array_change_key_case($row, CASE_UPPER);

        $item = array();
        foreach ($this->_cfg['fields'] as $name => $f) {
            if (array_key_exists($name, $row)) {
                $item[$name] = $row[$name];
            } elseif (array_key_exists($f['column'], $row)) {
                $item[$name] = $row[$f['column']];
            } elseif (isset($upper[strtoupper($f['column'])])) {
                $item[$name] = $upper[strtoupper($f['column'])];
            } else {
                $item[$name] = null;
            }
        }
        return $item;
    }

    protected function _cast_for_db(array $f, $value)
    {
        if ($value === null) {
            return null;
        }
        switch ($f['type']) {
            case 'int':      return (int) $value;
            case 'bool':     return $value ? 1 : 0;
            case 'string':   return (string) $value;
            case 'datetime': return (string) $value;
            default:         return $value;
        }
    }

    protected function _validate(array $data, $is_create)
    {
        $errors = array();
        $required = $is_create
            ? (isset($this->_cfg['required_on_create']) ? $this->_cfg['required_on_create'] : array())
            : array();

        foreach ($required as $name) {
            if (!array_key_exists($name, $data) || $data[$name] === '' || $data[$name] === null) {
                $errors[$name] = 'required';
            }
        }

        foreach ($data as $name => $value) {
            if (!isset($this->_cfg['fields'][$name])) {
                // Неизвестное поле — игнорируем, не ошибка (гибкость)
                continue;
            }
            if (!in_array($name, $this->_cfg['writable'], true)) {
                $errors[$name] = 'readonly';
                continue;
            }
            $f = $this->_cfg['fields'][$name];

            if ($value === null) {
                if (empty($f['nullable'])) {
                    $errors[$name] = 'null not allowed';
                }
                continue;
            }

            switch ($f['type']) {
                case 'int':
                    if (!is_numeric($value)) {
                        $errors[$name] = 'must be integer';
                    } elseif (isset($f['min']) && $value < $f['min']) {
                        $errors[$name] = 'min ' . $f['min'];
                    } elseif (isset($f['max']) && $value > $f['max']) {
                        $errors[$name] = 'max ' . $f['max'];
                    }
                    break;

                case 'string':
                    if (!is_string($value) && !is_numeric($value)) {
                        $errors[$name] = 'must be string';
                    } elseif (isset($f['max']) && function_exists('mb_strlen')
                              && mb_strlen((string) $value) > $f['max']) {
                        $errors[$name] = 'max length ' . $f['max'];
                    }
                    break;

                case 'bool':
                    if (!is_bool($value) && $value !== 0 && $value !== 1) {
                        $errors[$name] = 'must be boolean';
                    }
                    break;
            }
        }

        return $errors;
    }

    /**
     * Генерация нового ID_ORG из  SEQUENCE/GENERATOR в схеме,
     *   CREATE SEQUENCE GEN_ORG_ID;
     * и использовать GEN_ID(GEN_ORG_ID, 1).
     */
		protected function _next_id_org()
		{
			$row = DB::query(Database::SELECT,
					'SELECT GEN_ID(GEN_ORG_ID, 1) AS GEN FROM RDB$DATABASE')
				->execute($this->_db)
				->current();

			if (!$row) {
				throw new Kohana_Exception('Cannot get next ID_ORG from GEN_ORG_ID');
			}

			$row = array_change_key_case($row, CASE_UPPER);
			return (int) $row['GEN'];
		}
		
		
		/**
 * Преобразует PHP-значение в SQL-литерал для Firebird.
 * NULL → NULL, число → число, строка → 'строка' с удвоением кавычек.
 */
protected function _sql_literal($v)
{
    if ($v === null) {
        return 'NULL';
    }
    if (is_int($v) || is_float($v)) {
        return (string) $v;
    }
    if (is_bool($v)) {
        return $v ? '1' : '0';
    }
    return "'" . str_replace("'", "''", (string) $v) . "'";
}
}
