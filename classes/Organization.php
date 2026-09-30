<?php defined('SYSPATH') or die('No direct script access.');

class Organization
{
    /** @var Database */
    protected $_db;

    /** Таблица в БД */
    const TABLE = 'ORGANIZATION';

    /**
     * Соответствие: клиентское имя → реальная колонка.
     * UPPERCASE, потому что InterBase отдаёт всё в верхнем регистре.
     */
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

    /** Какие поля можно писать при create/update */
    const WRITABLE = array(
        'name', 'id_parent', 'flag', 'id_def_accessname', 'divcode', 'guid',
    );

    /** Обязательные при создании */
    const REQUIRED_ON_CREATE = array('divcode');

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

        $sort_col = isset(self::SORTABLE[$sort]) ? self::SORTABLE[$sort] : 'ID_ORG';
        $order    = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';

        $sql = 'SELECT FIRST ' . $limit . ' SKIP ' . $offset . ' '
             . $this->_select_fields()
             . ' FROM ' . self::TABLE
             . $where
             . ' ORDER BY ' . $sort_col . ' ' . $order;

        $rows  = DB::query(Database::SELECT, $sql)
            ->execute($this->_db)
            ->as_array();

        $items = $this->_normalize_rows($rows);
        $total = $this->count($filters);

        return array('items' => $items, 'total' => $total);
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
            1,
            'CURRENT_TIMESTAMP',
        );

        foreach (self::WRITABLE as $name) {
            if (!array_key_exists($name, $data)) {
                continue;
            }
            $col    = self::FIELDS[$name];
            $cols[] = $col;
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
            $col    = self::FIELDS[$name];
            $set[]  = $col . ' = ' . $this->_q($this->_cast($name, $data[$name]));
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
            if (!isset(self::FILTERS[$key])) {
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
            $item[$client] = isset($u[strtoupper($col)]) ? $u[strtoupper($col)] : null;
        }
        return $item;
    }

    protected function _cast($name, $value)
    {
        if ($value === null) {
            return null;
        }
        switch ($name) {
            case 'id_parent':
            case 'flag':
            case 'id_def_accessname':
                return (int) $value;
            default:
                return (string) $value;
        }
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

        // Длины строк
        $max_len = array(
            'name'    => 50,
            'divcode' => 50,
            'guid'    => 50,
        );
        foreach ($max_len as $name => $max) {
            if (isset($data[$name]) && $data[$name] !== null) {
                if (function_exists('mb_strlen')
                    && mb_strlen((string) $data[$name]) > $max) {
                    $errors[$name] = 'max length ' . $max;
                }
            }
        }

        // Нечисловые значения в числовых полях
        $int_fields = array('id_parent', 'flag', 'id_def_accessname');
        foreach ($int_fields as $name) {
            if (isset($data[$name]) && $data[$name] !== null && $data[$name] !== '') {
                if (!is_numeric($data[$name])) {
                    $errors[$name] = 'must be integer';
                }
            }
        }

        return $errors;
    }

    protected function _next_id()
    {
        $row = DB::query(Database::SELECT,
                'SELECT MAX(ID_ORG) AS MAX_ID FROM ' . self::TABLE)
            ->execute($this->_db)
            ->current();

        if (!$row) {
            return 1;
        }
        $u = array_change_key_case($row, CASE_UPPER);
        return (isset($u['MAX_ID']) ? (int) $u['MAX_ID'] : 0) + 1;
    }

    /**
     * SQL-литерал для InterBase.
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
}