<?php

namespace WillSco\EasyShippingRulesM2\Block\Adminhtml\Carriers\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use WillSco\EasyShippingRulesM2\Model\CarriersFactory;

class SaveButton implements ButtonProviderInterface
{
    /**
     * @var Context
     */
    protected $context;

    /**
     * @var CarriersFactory
     */
    protected $carriers;

    /**
     * SaveButton constructor.
     * @param Context $context
     * @param CarriersFactory $carriers
     */
    public function __construct(
        Context $context,
        CarriersFactory $carriers
    ) {
        $this->context = $context;
        $this->carriers = $carriers;
    }

    /**
     * @return null
     */
    public function getPageId()
    {
        try {
            return $this->carriers->load(
                $this->context->getRequest()->getParam('id')
            )->getId();
        } catch (NoSuchEntityException $e) {
        }
        return null;
    }

    /**
     * @param string $route
     * @param array $params
     * @return string
     */
    public function getUrl($route = '', $params = [])
    {
        return $this->context->getUrlBuilder()->getUrl($route, $params);
    }

    /**
     * @return array
     */
    public function getButtonData()
    {
        return [
            'label' => __('Save Carrier'),
            'class' => 'save primary',
            'data_attribute' => [
                'mage-init' => ['button' => ['event' => 'save']],
                'form-role' => 'save',
            ],
            'sort_order' => 90,
        ];
    }
}
