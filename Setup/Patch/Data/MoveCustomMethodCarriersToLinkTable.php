<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Setup\Patch\Data;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use WillSco\EasyShippingRulesM2\Model\ResourceModel\CustomMethod as CustomMethodResource;

/**
 * Custom methods used to belong to one carrier (custom_method.carrier_id); they now link to many.
 */
class MoveCustomMethodCarriersToLinkTable implements DataPatchInterface
{
    /**
     * Initialize dependencies.
     *
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup
    ) {
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        $connection = $this->moduleDataSetup->getConnection();
        $methodTable = $this->moduleDataSetup->getTable(CustomMethodResource::MAIN_TABLE);
        $linkTable = $this->moduleDataSetup->getTable(CustomMethodResource::CARRIER_TABLE);

        $select = $connection->select()
            ->from($methodTable, ['method_id', 'carrier_id'])
            ->where('carrier_id IS NOT NULL');
        $connection->query($connection->insertFromSelect(
            $select,
            $linkTable,
            ['method_id', 'carrier_id'],
            AdapterInterface::INSERT_IGNORE
        ));
        $connection->update($methodTable, ['carrier_id' => null]);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }
}
