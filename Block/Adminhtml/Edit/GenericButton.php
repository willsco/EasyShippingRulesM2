<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Block\Adminhtml\Edit;

use Magento\Backend\Block\Widget\Context;

/**
 * Base for the form buttons shared by every entity; URLs are relative to the current controller.
 */
class GenericButton
{
    /**
     * Initialize dependencies.
     *
     * @param Context $context
     */
    public function __construct(
        protected readonly Context $context
    ) {
    }

    /**
     * Get entity ID.
     *
     * @return int|null ID of the entity being edited
     */
    public function getEntityId(): ?int
    {
        $id = (int)$this->context->getRequest()->getParam('id');

        return $id ?: null;
    }

    /**
     * Get URL.
     *
     * @param string $route
     * @param array $params
     * @return string
     */
    public function getUrl(string $route = '', array $params = []): string
    {
        return $this->context->getUrlBuilder()->getUrl($route, $params);
    }
}
