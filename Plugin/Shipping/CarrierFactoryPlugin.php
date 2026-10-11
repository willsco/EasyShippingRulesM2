<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Plugin\Shipping;

use Magento\Framework\ObjectManager\ResetAfterRequestInterface;
use Magento\Shipping\Model\CarrierFactory;
use WillSco\EasyShippingRulesM2\Model\Shipping\Carrier;
use WillSco\EasyShippingRulesM2\Model\Shipping\CarrierCode;
use WillSco\EasyShippingRulesM2\Model\Shipping\CarrierFactory as EsrCarrierFactory;

/**
 * Resolves "easyshippingrules{id}" carrier codes, which have no entry in system config.
 *
 * Without it Magento drops custom method rates when grouping them and cannot load
 * the carrier of orders placed with them.
 */
class CarrierFactoryPlugin implements ResetAfterRequestInterface
{
    /**
     * @var array<string, Carrier>
     */
    private array $instances = [];

    /**
     * Initialize dependencies.
     *
     * @param EsrCarrierFactory $carrierFactory
     * @param CarrierCode $carrierCode
     */
    public function __construct(
        private readonly EsrCarrierFactory $carrierFactory,
        private readonly CarrierCode $carrierCode
    ) {
    }

    /**
     * Drop cached data between requests of a long-running application server.
     *
     * @return void
     */
    public function _resetState(): void
    {
        $this->instances = [];
    }

    /**
     * Plugin after get.
     *
     * @param CarrierFactory $subject
     * @param mixed $result
     * @param string $carrierCode
     * @return mixed
     */
    public function afterGet(CarrierFactory $subject, $result, $carrierCode)
    {
        if ($result !== false || $this->carrierCode->parse((string)$carrierCode) === null) {
            return $result;
        }

        return $this->instances[$carrierCode] ??= $this->createCarrier((string)$carrierCode);
    }

    /**
     * Plugin after create.
     *
     * @param CarrierFactory $subject
     * @param mixed $result
     * @param string $carrierCode
     * @param int|null $storeId
     * @return mixed
     */
    public function afterCreate(CarrierFactory $subject, $result, $carrierCode, $storeId = null)
    {
        if ($result !== false || $this->carrierCode->parse((string)$carrierCode) === null) {
            return $result;
        }

        $carrier = $this->createCarrier((string)$carrierCode);
        if ($storeId) {
            $carrier->setStore($storeId);
        }

        return $carrier;
    }

    /**
     * Create carrier.
     *
     * @param string $carrierCode
     * @return Carrier
     */
    private function createCarrier(string $carrierCode): Carrier
    {
        $carrier = $this->carrierFactory->create();
        $carrier->setId($carrierCode);

        return $carrier;
    }
}
