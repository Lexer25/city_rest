<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Rest_Persons extends Controller_Rest_Base
{
    protected $_resource_name = 'persons';
    protected $_model_class   = 'Model_Persons';

    public function action_index()
    {
        if (!$this->_require_model()) return;

        $p   = Rest_Pagination::from_request($this->request);
        $res = $this->_model->search($p->filters, $p->limit, $p->offset, $p->sort, $p->order);

        if (!empty($res['error'])) {
            $this->_respond($this->_wrap_model_error($res, Rest_Error::DB_ERROR));
            return;
        }

        $p->total = (int) Arr::get($res, 'total', 0);
        $this->_respond(Rest_Response::ok(
            array('items' => Arr::get($res, 'items', array())),
            $p->meta()
        ));
    }

    public function action_get()
    {
        if (!$this->_require_model()) return;

        $id  = (string) $this->request->param('id');
        $res = $this->_model->find_by_guid($id);

        if (empty($res['item'])) {
            $this->_respond(Rest_Response::error(
                Rest_Error::PERSON_NOT_FOUND, 'Person not found', 404
            ));
            return;
        }
        $this->_respond(Rest_Response::ok(array('item' => $res['item'])));
    }

    public function action_create()
    {
        if (!$this->_require_model()) return;
        if (!$this->_require_auth())  return;
        $this->_audit_context();

        $data = $this->_json_body();
        $res  = $this->_model->create($data);

        if (!empty($res['error'])) {
            $this->_respond($this->_wrap_model_error($res));
            return;
        }
        $this->response->status(201);
        $this->_respond(Rest_Response::ok(array('item' => Arr::get($res, 'item', array()))));
    }

    public function action_update()
    {
        if (!$this->_require_model()) return;
        if (!$this->_require_auth())  return;
        $this->_audit_context();

        $id   = (string) $this->request->param('id');
        $data = $this->_json_body();
        $res  = $this->_model->update($id, $data);

        if (empty($res['item'])) {
            $this->_respond(Rest_Response::error(
                Rest_Error::PERSON_NOT_FOUND, 'Person not found', 404
            ));
            return;
        }
        $this->_respond(Rest_Response::ok(array('item' => $res['item'])));
    }

    public function action_delete()
    {
        if (!$this->_require_model()) return;
        if (!$this->_require_auth())  return;
        $this->_audit_context();

        $id  = (string) $this->request->param('id');
        $res = $this->_model->delete($id);

        if (empty($res['deleted'])) {
            $this->_respond(Rest_Response::error(
                Rest_Error::PERSON_NOT_FOUND, 'Person not found', 404
            ));
            return;
        }
        $this->_respond(Rest_Response::ok(array('deleted' => true)));
    }
}