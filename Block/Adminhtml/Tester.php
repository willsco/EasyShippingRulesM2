<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Block\Adminhtml;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Customer\Model\ResourceModel\Group\CollectionFactory as GroupCollectionFactory;
use Magento\Directory\Model\Config\Source\Country;
use Magento\Store\Model\System\Store as SystemStore;
use WillSco\EasyShippingRulesM2\Controller\Adminhtml\Tester\Index as TesterController;
use WillSco\EasyShippingRulesM2\Model\ResourceModel\Rule\CollectionFactory as RuleCollectionFactory;
use WillSco\EasyShippingRulesM2\Model\Rule;

/**
 * Rule tester form and results.
 *
 * Results are set by the controller as the "result" data key; posted values as "input".
 */
class Tester extends Template
{
    /**
     * @var string
     */
    protected $_template = 'WillSco_EasyShippingRulesM2::tester.phtml';

    /**
     * Initialize dependencies.
     *
     * @param Context $context
     * @param SystemStore $systemStore
     * @param GroupCollectionFactory $groupCollectionFactory
     * @param Country $countrySource
     * @param RuleCollectionFactory $ruleCollectionFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        private readonly SystemStore $systemStore,
        private readonly GroupCollectionFactory $groupCollectionFactory,
        private readonly Country $countrySource,
        private readonly RuleCollectionFactory $ruleCollectionFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Store views grouped by website, as value/label pairs (nested for optgroups).
     *
     * @return array
     */
    public function getStoreOptions(): array
    {
        return $this->systemStore->getStoreValuesForForm(false, false);
    }

    /**
     * Customer groups, including NOT LOGGED IN.
     *
     * @return array
     */
    public function getGroupOptions(): array
    {
        return $this->groupCollectionFactory->create()->toOptionArray();
    }

    /**
     * Countries.
     *
     * @return array
     */
    public function getCountryOptions(): array
    {
        return $this->countrySource->toOptionArray(false);
    }

    /**
     * Whether the admin user may simulate a customer by email (see the tester controller).
     *
     * @return bool
     */
    public function canSimulateCustomer(): bool
    {
        return $this->_authorization->isAllowed(TesterController::CUSTOMER_RESOURCE);
    }

    /**
     * Every rule, disabled ones marked, with whether it is active in the simulation.
     *
     * Before the first run the enabled rules are selected; afterwards, the posted ones.
     *
     * @return array<array{value: string, label: string, selected: bool}>
     */
    public function getRuleOptions(): array
    {
        $input = (array)$this->getData('input');
        $posted = isset($input['rule_ids']) ? array_map('strval', (array)$input['rule_ids']) : null;

        $options = [];
        /** @var Rule $rule */
        foreach ($this->ruleCollectionFactory->create()->setOrder('rule_id', 'ASC') as $rule) {
            $label = '#' . $rule->getId() . ' ' . $rule->getName();
            $options[] = [
                'value' => (string)$rule->getId(),
                'label' => $rule->isActive() ? $label : __('%1 (disabled)', $label)->render(),
                'selected' => $posted === null ? $rule->isActive() : in_array((string)$rule->getId(), $posted, true),
            ];
        }

        return $options;
    }

    /**
     * Posted value of a form field, for refilling the form.
     *
     * @param string $field
     * @param string $default
     * @return string
     */
    public function getInputValue(string $field, string $default = ''): string
    {
        $input = (array)$this->getData('input');

        return isset($input[$field]) ? (string)$input[$field] : $default;
    }

    /**
     * Simulation result, or null before the first run.
     *
     * @return array|null
     */
    public function getResult(): ?array
    {
        $result = $this->getData('result');

        return is_array($result) ? $result : null;
    }

    /**
     * Form submit URL.
     *
     * @return string
     */
    public function getFormAction(): string
    {
        return $this->getUrl('easyshippingrules/tester/index');
    }

    /**
     * Formatted base currency price.
     *
     * @param float $price
     * @return string
     */
    public function formatPrice(float $price): string
    {
        return $this->_storeManager->getStore()->getBaseCurrency()->formatPrecision($price, 2, [], false);
    }
}
