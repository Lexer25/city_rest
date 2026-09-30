<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Organization — доменная модель организации.
 *
 * Работает с таблицей ORGANIZATION в InterBase.
 * Не знает ничего про HTTP, JSON, REST — это чистая модель данных.
 *
 * Клиентские имена полей (snake_case) маппятся на UPPERCASE-колонки БД
 * через константы FIELDS / SORTABLE / FILTERS.
 */
class Organization
{
    /** @var Database */
    protected $_db;

    /** Таблица в БД */
    const TABLE = 'ORGANIZATION';

    /** Клиентское имя → реальная колонка */
    const FIELDS = array(
        'id_org'            => 'ID_ORG',
        'id_db'             => 'ID_DB',
        'name'              => 'NAME',
        'id_parent'         => 'ID_PARENT',
        'flag'              => 'FLAG',
        'id_def_accessname' => 'ID_DEF_ACCESSNAME',
        'divcode'           => 'DIVCODE',
        'guid'              => 'GUID',
        'time_stamp'        => 'TIME_STAMP',
    );

    /** Белый список сортировки: клиентское имя → колонка */
    const SORTABLE = array(
        'id_org'     => 'ID_ORG',
        'name'       => 'NAME',
        'id_parent'  => 'ID_PARENT',
        'divcode'    => 'DIVCODE',
        'time_stamp' => 'TIME_STAMP',
    );

    /** Белый список фильтров: клиентское имя → колонка */
    const FILTERS = array(
        'id_db'     => 'ID_DB',
        'id_parent' => 'ID_PARENT',
        'flag'      => 'FLAG',
        'divcode'   => 'DIVCODE',
        'guid'      => 'GUID',
    );

    /** Что разрешено писать при create/update */
    const WRITABLE = array(
        'name', 'id_parent', 'flag', 'id_def_accessname', 'divcode', 'guid',
    );

    /** Обязательные при создании */
    const REQUIRED_ON_CREATE = array('divcode');

    /** Максимальные длины строковых полей */
    const MAX_LENGTH = array(
        'name'    => 50,
        'divcode' => 50,
        'guid'    => 50,
    );

    /** Целочисленные поля */
    const INT_FIELDS = array('id_parent', 'flag', 'id_def_accessname');

    /** Имя генератора для ID_ORG (см. CREATE GENERATOR GEN_ORG_ID) */
    const GEN_ID = 'GEN_ORG_ID';

    public function __construct()
    {
        $this->_db = Database::instance('fb');
    }

    // ------------------------------------------------------------------
    // GET список
    // ------------------------------------------------------------------

    public function get_list(array $filters, $limit, $offset, $sort, $order)
    {
        $limit  = max(1, (int) $limit);
        $offset = max(0, (int) $offset);

        $where = $this->_build_where($filters);

        // array_key_exists, а не isset — self::SORTABLE это константа,
        // isset() к ней неприменим (PHP: "Cannot use isset() on the result
        // of an expression")
        $sort_col = array_key_exists($sort, self::SORTABLE)
            ? self::SORTABLE[$sort]
            : 'ID_ORG';
        $order    = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';

        $sql = 'SELECT FIRST ' . $limit . ' SKIP ' . $offset . ' '
             . $this->_select_fields()
             . ' FROM ' . self::TABLE
             . $where
             . ' ORDER BY ' . $sort_col . ' ' . $order;

        $rows = DB::query(Database::SELECT, $sql)
            ->execute($this->_db)
            ->as_array();

        return array(
            'items' => $this->_normalize_rows($rows),
            'total' => $this->count($filters),
        );
    }

    public function count(array $filters)
    {
        $where = $this->_build_where($filters);

        $sql = 'SELECT COUNT(*) AS CNT FROM ' . self::TABLE . $where;

        $row = DB::query(Database::SELECT, $sql)
            ->execute($this->_db)
            ->current();

        if (!$row) {
            return 0;
        }
        $u = array_change_key_case($row, CASE_UPPER);
        return (int) (isset($u['CNT']) ? $u['CNT'] : 0);
    }

    // ------------------------------------------------------------------
    // GET одна
    // ------------------------------------------------------------------

    public function get_by_guid($guid)
    {
        return $this->_get_one('GUID', $guid);
    }

    public function get_by_id($id)
    {
        return $this->_get_one('ID_ORG', (int) $id);
    }

    protected function _get_one($column, $value)
    {
        $sql = 'SELECT ' . $this->_select_fields()
             . ' FROM ' . self::TABLE
             . ' WHERE ' . $column . ' = ' . $this->_q($value);

        $row = DB::query(Database::SELECT, $sql)
            ->execute($this->_db)
            ->current();

        return $row ? $this->_normalize_row($row) : null;
    }

    // ------------------------------------------------------------------
    // POST создание
    // ------------------------------------------------------------------

    public function create(array $data)
    {
        $errors = $this->_validate($data, true);
        if (!empty($errors)) {
            return array('error' => 'validation failed', 'fields' => $errors);
        }

        $id_org = $this->_next_id();

        $cols = array('ID_ORG', 'ID_DB', 'TIME_STAMP');
        $vals = array(
            (int) $id_org,
            isset($data['id_db']) ? (int) $data['id_db'] : 1,
            'CURRENT_TIMESTAMP',
        );

        foreach (self::WRITABLE as $name) {
            if (!array_key_exists($name, $data)) {
                continue;
            }
            $cols[] = self::FIELDS[$name];
            $vals[] = $this->_q($this->_cast($name, $data[$name]));
        }

        $sql = 'INSERT INTO ' . self::TABLE
             . ' (' . implode(', ', $cols) . ')'
             . ' VALUES (' . implode(', ', $vals) . ')';

        DB::query(Database::INSERT, $sql)->execute($this->_db);

        return $this->get_by_id($id_org);
    }

    // ------------------------------------------------------------------
    // PUT обновление
    // ------------------------------------------------------------------

    public function update($guid, array $data)
    {
        $current = $this->get_by_guid($guid);
        if ($current === null) {
            return null;
        }

        $errors = $this->_validate($data, false);
        if (!empty($errors)) {
            return array('error' => 'validation failed', 'fields' => $errors);
        }

        $set = array();
        foreach (self::WRITABLE as $name) {
            if (!array_key_exists($name, $data)) {
                continue;
            }
            $set[] = self::FIELDS[$name]
                   . ' = ' . $this->_q($this->_cast($name, $data[$name]));
        }

        if (empty($set)) {
            return $current;
        }

        $set[] = 'TIME_STAMP = CURRENT_TIMESTAMP';

        $sql = 'UPDATE ' . self::TABLE
             . ' SET ' . implode(', ', $set)
             . ' WHERE ID_ORG = ' . (int) $current['id_org'];

        DB::query(Database::UPDATE, $sql)->execute($this->_db);

        return $this->get_by_guid($guid);
    }

    // ------------------------------------------------------------------
    // DELETE
    // ------------------------------------------------------------------

    public function delete($guid)
    {
        $current = $this->get_by_guid($guid);
        if ($current === null) {
            return false;
        }

        $sql = 'DELETE FROM ' . self::TABLE
             . ' WHERE ID_ORG = ' . (int) $current['id_org'];

        DB::query(Database::DELETE, $sql)->execute($this->_db);

        return true;
    }

    // ------------------------------------------------------------------
    // Внутренние помощники
    // ------------------------------------------------------------------

    protected function _select_fields()
    {
        return implode(', ', array_values(self::FIELDS));
    }

    protected function _build_where(array $filters)
    {
        $where = array();
        foreach ($filters as $key => $val) {
            // array_key_exists — self::FILTERS константа
            if (!array_key_exists($key, self::FILTERS)) {
                continue;
            }
            if ($val === '' || $val === null) {
                continue;
            }
            $where[] = self::FILTERS[$key] . ' = ' . $this->_q($val);
        }
        return $where ? ' WHERE ' . implode(' AND ', $where) : '';
    }

    protected function _normalize_rows(array $rows)
    {
        $out = array();
        foreach ($rows as $r) {
            $out[] = $this->_normalize_row($r);
        }
        return $out;
    }

    protected function _normalize_row(array $row)
    {
        $u = array_change_key_case($row, CASE_UPPER);

        $item = array();
        foreach (self::FIELDS as $client => $col) {
            $item[$client] = isset($u[strtoupper($col)])
                ? $u[strtoupper($col)]
                : null;
        }
        return $item;
    }

    protected function _cast($name, $value)
    {
        if ($value === null) {
            return null;
        }
        if (in_array($name, self::INT_FIELDS, true)) {
            return (int) $value;
        }
        return (string) $value;
    }

    protected function _validate(array $data, $is_create)
    {
        $errors = array();

        if ($is_create) {
            foreach (self::REQUIRED_ON_CREATE as $name) {
                if (!isset($data[$name]) || $data[$name] === '') {
                    $errors[$name] = 'required';
                }
            }
        }

        foreach (self::MAX_LENGTH as $name => $max) {
            if (isset($data[$name]) && $data[$name] !== null) {
                if (function_exists('mb_strlen')
                    && mb_strlen((string) $data[$name]) > $max) {
                    $errors[$name] = 'max length ' . $max;
                }
            }
        }

        foreach (self::INT_FIELDS as $name) {
            if (isset($data[$name]) && $data[$name] !== null && $data[$name] !== '') {
                if (!is_numeric($data[$name])) {
                    $errors[$name] = 'must be integer';
                }
            }
        }

        return $errors;
    }

    /**
     * Следующий ID_ORG из генератора.
     * Требует: CREATE GENERATOR GEN_ORG_ID; (в InterBase)
     */
    protected function _next_id()
    {
        $row = DB::query(Database::SELECT,
                'SELECT GEN_ID(' . self::GEN_ID . ', 1) AS GEN FROM RDB$DATABASE')
            ->execute($this->_db)
            ->current();

        if (!$row) {
            throw new Kohana_Exception('Cannot get next ID from ' . self::GEN_ID);
        }
        $u = array_change_key_case($row, CASE_UPPER);
        return (int) $u['GEN'];
    }

    /**
     * SQL-литерал для InterBase/Firebird.
     * NULL → NULL, число → число, строка → 'строка' с удвоением кавычек.
     */
    protected function _q($v)
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
	
	
	/**
	 * Прямые дети узла.
	 *
	 * @param int $parent_id  ID_ORG родителя
	 * @return array  список нормализованных узлов с флагом has_children
	 */
	public function get_children($parent_id)
	{
		$parent_id = (int) $parent_id;

		$sql = 'SELECT ' . $this->_select_fields()
			 . ' FROM ' . self::TABLE
			 . ' WHERE ID_PARENT = ' . $parent_id
			 . ' AND ID_ORG <> ' . $parent_id
			 . ' ORDER BY NAME';

		$rows = DB::query(Database::SELECT, $sql)
			->execute($this->_db)
			->as_array();

		$children = $this->_normalize_rows($rows);

		if (empty($children)) {
			return array();
		}

		// Один запрос — узнать, у кого из детей есть свои дети.
		$child_ids = array();
		foreach ($children as $c) {
			$child_ids[] = (int) $c['id_org'];
		}

		$has = array();
		$sql2 = 'SELECT DISTINCT ID_PARENT FROM ' . self::TABLE
			  . ' WHERE ID_PARENT IN (' . implode(',', $child_ids) . ')';
		$rows2 = DB::query(Database::SELECT, $sql2)
			->execute($this->_db)
			->as_array();

		foreach ($rows2 as $r) {
			$u = array_change_key_case($r, CASE_UPPER);
			$has[(int) $u['ID_PARENT']] = true;
		}

		foreach ($children as &$c) {
			$c['has_children'] = isset($has[(int) $c['id_org']]);
		}
		unset($c);

		return $children;
	}
	
	
	/**
 * Корневые организации (ID_PARENT = 1).
 *
 * @return array
 */
public function get_roots()
{
    $sql = 'SELECT ' . $this->_select_fields()
         . ' FROM ' . self::TABLE
         . ' WHERE ID_PARENT = 1'
         . ' AND ID_ORG <> 1'
         . ' ORDER BY NAME';

    $rows = DB::query(Database::SELECT, $sql)
        ->execute($this->_db)
        ->as_array();

    $items = $this->_normalize_rows($rows);

    if (empty($items)) {
        return array();
    }

    // Флаги has_children
    $ids = array();
    foreach ($items as $it) {
        $ids[] = (int) $it['id_org'];
    }

    $has = array();
    $sql2 = 'SELECT DISTINCT ID_PARENT FROM ' . self::TABLE
          . ' WHERE ID_PARENT IN (' . implode(',', $ids) . ')';
    $rows2 = DB::query(Database::SELECT, $sql2)
        ->execute($this->_db)
        ->as_array();

    foreach ($rows2 as $r) {
        $u = array_change_key_case($r, CASE_UPPER);
        $has[(int) $u['ID_PARENT']] = true;
    }

    foreach ($items as &$it) {
        $it['has_children'] = isset($has[(int) $it['id_org']]);
    }
    unset($it);

    return $items;
}
}
