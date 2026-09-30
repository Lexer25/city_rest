<?php defined('SYSPATH') or die('No direct script access.');

class Model_Orgs
{
    /**
     * Поиск организаций.
     *
     * @param array  $filters
     * @param int    $limit
     * @param int    $offset
     * @param string $sort
     * @param string $order
     * @return array  {items: array, total: int} | {error: string, status?: int}
     */
    public function search(array $filters, $limit, $offset, $sort, $order)
    {
        // TODO: заменить на реальный SQL к Firebird.
        // Пока — пустая заглушка, чтобы контроллер и пагинация
        // можно было проверить на живом API.

        return array(
            'items' => array(),
            'total' => 0,
        );
    }

    public function find_by_guid($guid)
    {
        // TODO: SELECT ... WHERE GUID = ?
        return array('item' => null);
    }

    public function create(array $data)
    {
        // TODO: INSERT
        return array('item' => array_merge($data, array('guid' => null)));
    }

    public function update($guid, array $data)
    {
        // TODO: UPDATE WHERE GUID = ?
        return array('item' => null);
    }

    public function delete($guid)
    {
        // TODO: DELETE / UPDATE SET deleted = 1
        return array('deleted' => false);
    }
}
