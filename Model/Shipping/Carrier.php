<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Model\Shipping;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Psr\Log\LoggerInterface;
use WillSco\EasyShippingRulesM2\Api\CarrierRepositoryInterface;
use WillSco\EasyShippingRulesM2\Api\Data\CarrierInterface as EsrCarrierInterface;

/**
 * Carrier instance returned for "easyshippingrules{id}" codes.
 *
 * It does not collect rates itself (custom method rates are added after all carriers ran,
 * see Plugin\Shipping\ApplyRulesPlugin); it exists so Magento can resolve the carrier code
 * of those rates, and it reads title, position and status from the module carrier.
 */
class Carrier extends AbstractCarrier implements CarrierInterface
{
    /**
     * @var string
     */
    protected $_code = CarrierCode::PREFIX;

    /**
     * Initialize dependencies.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param ErrorFactory $rateErrorFactory
     * @param LoggerInterface $logger
     * @param CarrierRepositoryInterface $carrierRepository
     * @param CarrierCode $carrierCode
     * @param array $data
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ErrorFactory $rateErrorFactory,
        LoggerInterface $logger,
        private readonly CarrierRepositoryInterface $carrierRepository,
        private readonly CarrierCode $carrierCode,
        array $data = []
    ) {
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
    }

    /**
     * @inheritdoc
     */
    public function collectRates(RateRequest $request)
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function getAllowedMethods()
    {
        return [];
    }

    /**
     * Title, position and status come from the module carrier, not from system config.
     *
     * @param string $field
     * @return mixed
     */
    public function getConfigData($field)
    {
        $carrier = $this->getEsrCarrier();

        return match ($field) {
            'title', 'name' => $carrier?->getName(),
            'sort_order' => $carrier?->getSortOrder(),
            'active' => $carrier?->isActive(),
            default => parent::getConfigData($field),
        };
    }

    /**
     * @inheritdoc
     */
    public function isActive()
    {
        return (bool)$this->getEsrCarrier()?->isActive();
    }

    /**
     * Get ESR carrier.
     *
     * @return EsrCarrierInterface|null
     */
    private function getEsrCarrier(): ?EsrCarrierInterface
    {
        $carrierId = $this->carrierCode->parse((string)$this->getId());
        if ($carrierId === null) {
            return null;
        }

        try {
            return $this->carrierRepository->getById($carrierId);
        } catch (NoSuchEntityException $e) {
            return null;
        }
    }
}
