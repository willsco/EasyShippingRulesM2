<?php

namespace WillSco\EasyShippingRulesM2\Api;

interface CarriersInterface
{
    /**
     * Get carrier from carrier id.
     * @param $id
     * @return mixed
     */
    public function getById($id);

    /**
     * Get collection.
     * @return mixed
     */
    public function getCollection();

    /**
     * Save carrier model.
     * @param CarriersInterface $model
     * @return mixed
     */
    public function save(CarriersInterface $model);

    /**
     * Delete carrier model.
     * @param CarriersInterface $model
     * @return mixed
     */
    public function delete(CarriersInterface $model);
}
