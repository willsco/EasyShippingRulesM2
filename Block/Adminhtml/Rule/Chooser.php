<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Block\Adminhtml\Rule;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Grid\Column;
use Magento\Backend\Block\Widget\Grid\Extended;
use Magento\Backend\Helper\Data as BackendHelper;
use WillSco\EasyShippingRulesM2\Model\ResourceModel\Rule\CollectionFactory;
use WillSco\EasyShippingRulesM2\Model\Source\Status;

/**
 * Rule picker opened from the "Shipping Rule" condition.
 *
 * Column IDs are prefixed with "chooser_" so their filter inputs do not clash with the rule form fields.
 */
class Chooser extends Extended
{
    /**
     * Initialize dependencies.
     *
     * @param Context $context
     * @param BackendHelper $backendHelper
     * @param CollectionFactory $collectionFactory
     * @param Status $statusSource
     * @param array $data
     */
    public function __construct(
        Context $context,
        BackendHelper $backendHelper,
        private readonly CollectionFactory $collectionFactory,
        private readonly Status $statusSource,
        array $data = []
    ) {
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        parent::_construct();

        // The form object name comes from the request and is printed unescaped into JS: accept identifiers only.
        $form = (string)$this->getJsFormObject();
        if (preg_match('/^[A-Za-z_$][\w$]*$/', $form)) {
            $this->setRowClickCallback("{$form}.chooserGridRowClick.bind({$form})");
            $this->setCheckboxCheckCallback("{$form}.chooserGridCheckboxCheck.bind({$form})");
            $this->setRowInitCallback("{$form}.chooserGridRowInit.bind({$form})");
        }

        $this->setId('easyshippingrules_rule_chooser_' . $this->getId());
        $this->setDefaultSort('chooser_rule_id');
        $this->setDefaultDir('DESC');
        $this->setUseAjax(true);
        if ($this->getRequest()->getParam('collapse')) {
            $this->setIsCollapsed(true);
        }
    }

    /**
     * Add column filter to collection.
     *
     * @param Column $column
     * @return $this
     */
    protected function _addColumnFilterToCollection($column)
    {
        if ($column->getId() !== 'in_rules') {
            return parent::_addColumnFilterToCollection($column);
        }

        $selected = $this->getSelectedRules();
        if ($column->getFilter()->getValue()) {
            $this->getCollection()->addFieldToFilter('main_table.rule_id', ['in' => $selected ?: [0]]);
        } elseif ($selected) {
            $this->getCollection()->addFieldToFilter('main_table.rule_id', ['nin' => $selected]);
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    protected function _prepareCollection()
    {
        $this->setCollection($this->collectionFactory->create());

        return parent::_prepareCollection();
    }

    /**
     * @inheritdoc
     */
    protected function _prepareColumns()
    {
        $this->addColumn('in_rules', [
            'header_css_class' => 'a-center',
            'type' => 'checkbox',
            'name' => 'in_rules',
            'values' => $this->getSelectedRules(),
            'align' => 'center',
            'index' => 'rule_id',
            'use_index' => true,
        ]);
        $this->addColumn('chooser_rule_id', [
            'header' => __('ID'),
            'index' => 'rule_id',
            'type' => 'number',
            'header_css_class' => 'col-id',
            'column_css_class' => 'col-id',
        ]);
        $this->addColumn('chooser_name', [
            'header' => __('Rule Name'),
            'index' => 'name',
        ]);
        $this->addColumn('chooser_is_active', [
            'header' => __('Status'),
            'index' => 'is_active',
            'type' => 'options',
            'options' => array_column($this->statusSource->toOptionArray(), 'label', 'value'),
        ]);

        return parent::_prepareColumns();
    }

    /**
     * @inheritdoc
     */
    public function getGridUrl()
    {
        return $this->getUrl('*/*/chooser', ['_current' => true, 'collapse' => null]);
    }

    /**
     * Get selected rules.
     *
     * @return int[]
     */
    private function getSelectedRules(): array
    {
        return array_map('intval', (array)$this->getRequest()->getPost('selected', []));
    }
}
