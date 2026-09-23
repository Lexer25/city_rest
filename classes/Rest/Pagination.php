<?php defined('SYSPATH') or die('No direct script access.');

class Rest_Pagination
{
    public $limit = 50;
    public $offset = 0;
    public $sort = '';
    public $order = 'asc';
    public $total = 0;
    public $filters = array();

    public static function from_request(Request $request)
    {
        $config = Kohana::$config->load('rest.pagination');
        $p = new self();
        $p->limit  = (int) $request->query('limit');
        $p->offset = (int) $request->query('offset');
        $p->sort   = trim((string) $request->query('sort'));
        $p->order  = strtolower(trim((string) $request->query('order')));

        if ($p->limit <= 0) {
            $p->limit = (int) $config['default_limit'];
        }
        if ($p->limit > (int) $config['max_limit']) {
            $p->limit = (int) $config['max_limit'];
        }
        if ($p->offset < 0) {
            $p->offset = 0;
        }
        if (!in_array($p->order, array('asc', 'desc'), true)) {
            $p->order = 'asc';
        }

        $reserved = array('limit', 'offset', 'sort', 'order');
        foreach ($request->query() as $k => $v) {
            if (!in_array($k, $reserved, true)) {
                $p->filters[$k] = $v;
            }
        }

        return $p;
    }

    public function meta()
    {
        return array(
            'total'  => (int) $this->total,
            'limit'  => (int) $this->limit,
            'offset' => (int) $this->offset,
        );
    }
}