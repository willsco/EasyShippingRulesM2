<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Edit / Delete links; configure "indexField" and "urlPrefix" (e.g. "easyshippingrules/carrier").
 *
 * The prefix must be explicit: grid data is also rendered by mui/index/render, where "*" would point to mui.
 */
class RowActions extends Column
{
    /**
     * Initialize dependencies.
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        private readonly UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * @inheritdoc
     */
    public function prepareDataSource(array $dataSource)
    {
        $indexField = $this->getData('config/indexField');
        $urlPrefix = $this->getData('config/urlPrefix');
        $name = $this->getData('name');

        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }

        foreach ($dataSource['data']['items'] as &$item) {
            if (!isset($item[$indexField])) {
                continue;
            }
            $params = ['id' => $item[$indexField]];
            $item[$name] = [
                'edit' => [
                    'href' => $this->urlBuilder->getUrl($urlPrefix . '/edit', $params),
                    'label' => __('Edit'),
                ],
                'delete' => [
                    'href' => $this->urlBuilder->getUrl($urlPrefix . '/delete', $params),
                    'label' => __('Delete'),
                    'confirm' => [
                        'title' => __('Delete'),
                        'message' => __('Are you sure you want to delete this item?'),
                    ],
                    'post' => true,
                ],
            ];
        }

        return $dataSource;
    }
}
