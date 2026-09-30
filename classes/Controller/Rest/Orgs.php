<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Rest_Orgs extends Controller_Rest_Base
{
    protected $_resource_name = 'orgs';

    /** @var Organization */
    protected $_org;

    public function before()
    {
        parent::before();
        $this->_org = new Organization();
    }

    // GET /api/v1/orgs
    public function action_index()
    {
        $p = Rest_Pagination::from_request($this->request);

        $res = $this->_org->get_list(
            $p->filters, $p->limit, $p->offset, $p->sort, $p->order
        );

        $p->total = (int) Arr::get($res, 'total', 0);

        $this->_respond(Rest_Response::ok(
            array('items' => Arr::get($res, 'items', array())),
            $p->meta()
        ));
    }

    // GET /api/v1/orgs/<id>
    public function action_get()
    {
        $id  = (string) $this->request->param('id');
        $org = $this->_org->get_by_guid($id);

        if ($org === null) {
            $this->_respond(Rest_Response::error(
                Rest_Error::ORG_NOT_FOUND, 'Organization not found', 404
            ));
            return;
        }

        $this->_respond(Rest_Response::ok(array('item' => $org)));
    }

    // POST /api/v1/orgs
    public function action_create()
    {
        if (!$this->_require_auth()) return;

        $data = $this->_json_body();
        if (empty($data)) {
            $this->_respond(Rest_Response::error(
                Rest_Error::VALIDATION_ERROR, 'Empty body', 400
            ));
            return;
        }

        $res = $this->_org->create($data);
        if (isset($res['error'])) {
            $this->_respond(Rest_Response::error(
                Rest_Error::VALIDATION_ERROR,
                $res['error'],
                400,
                isset($res['fields']) ? array('fields' => $res['fields']) : array()
            ));
            return;
        }

        $this->response->status(201);
        $this->_respond(Rest_Response::ok(array('item' => $res)));
    }

    // PUT /api/v1/orgs/<id>
    public function action_update()
    {
        if (!$this->_require_auth()) return;

        $id   = (string) $this->request->param('id');
        $data = $this->_json_body();

        $res = $this->_org->update($id, $data);
        if ($res === null) {
            $this->_respond(Rest_Response::error(
                Rest_Error::ORG_NOT_FOUND, 'Organization not found', 404
            ));
            return;
        }
        if (isset($res['error'])) {
            $this->_respond(Rest_Response::error(
                Rest_Error::VALIDATION_ERROR,
                $res['error'],
                400,
                isset($res['fields']) ? array('fields' => $res['fields']) : array()
            ));
            return;
        }

        $this->_respond(Rest_Response::ok(array('item' => $res)));
    }

    // DELETE /api/v1/orgs/<id>
    public function action_delete()
    {
        if (!$this->_require_auth()) return;

        $id  = (string) $this->request->param('id');
        $ok  = $this->_org->delete($id);

        if (!$ok) {
            $this->_respond(Rest_Response::error(
                Rest_Error::ORG_NOT_FOUND, 'Organization not found', 404
            ));
            return;
        }

        $this->_respond(Rest_Response::ok(array('deleted' => true)));
    }
}