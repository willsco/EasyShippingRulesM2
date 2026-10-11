<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Plugin\Shipping;

use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Shipping;
use Psr\Log\LoggerInterface;
use WillSco\EasyShippingRulesM2\Model\Shipping\ProcessorState;
use WillSco\EasyShippingRulesM2\Model\Shipping\RateProcessor;

/**
 * Runs after every carrier collected its rates, for any flow that rates a quote address
 * (cart estimate, checkout, multishipping, admin order create, APIs).
 */
class ApplyRulesPlugin
{
    /**
     * Initialize dependencies.
     *
     * @param RateProcessor $rateProcessor
     * @param ProcessorState $processorState
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly RateProcessor $rateProcessor,
        private readonly ProcessorState $processorState,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Plugin after collect rates.
     *
     * @param Shipping $subject
     * @param Shipping $result
     * @param RateRequest $request
     * @return Shipping
     */
    public function afterCollectRates(Shipping $subject, Shipping $result, RateRequest $request): Shipping
    {
        $address = $this->getQuoteAddress($request);
        if ($address === null || !$this->processorState->isEnabled()) {
            return $result;
        }

        try {
            $this->rateProcessor->process($subject->getResult(), $request, $address);
        } catch (\Throwable $e) {
            // A broken rule (or a TypeError in a third-party condition) must not take the checkout down;
            // the carriers' own rates still show.
            $this->logger->error('Easy Shipping Rules: ' . $e->getMessage(), ['exception' => $e]);
        }

        return $result;
    }

    /**
     * Get quote address.
     *
     * @param RateRequest $request
     * @return Address|null
     */
    private function getQuoteAddress(RateRequest $request): ?Address
    {
        foreach ((array)$request->getAllItems() as $item) {
            $address = is_object($item) && method_exists($item, 'getAddress') ? $item->getAddress() : null;
            if ($address instanceof Address) {
                return $address;
            }
        }

        return null;
    }
}
