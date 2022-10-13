<?php

/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Model;

use Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use WillSco\EasyShippingRulesM2\Api\CarriersInterface;

class CarriersRepository implements CarriersInterface
{
    /**
     * @var ScopeConfigInterface
     */
    protected ScopeConfigInterface $scopeConfig;

    /**
     * @var CarriersFactory
     */
    protected CarriersFactory $carriersFactory;
    protected CarriersFactory $cotacoesFactory;


    /**
r.
     * @param CarriersFactory $carriersFactory
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        CarriersFactory $carriersFactory,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->cotacoesFactory = $carriersFactory;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param $id
     * @return Carriers
     */
    public function getById($id): Carriers
    {
        return $this->cotacoesFactory->create()->load($id);
    }

    /**
     * @return AbstractDb|AbstractCollection|null
     */
    public function getCollection()
    {
        return $this->cotacoesFactory
            ->create()
            ->getCollection();
    }

    /**
     * @param CarriersInterface $model
     * @return Carriers
     * @throws Exception
     */
    public function save(CarriersInterface $model): Carriers
    {
        return $this->cotacoesFactory
            ->create()
            ->setData($model->getData())
            ->save();
    }

    /**
     * @param CarriersInterface $model
     * @return Carriers
     * @throws Exception
     */
    public function delete(CarriersInterface $model): Carriers
    {
        return $this->cotacoesFactory
            ->create()
            ->load($model->getId())
            ->delete();
    }
}
