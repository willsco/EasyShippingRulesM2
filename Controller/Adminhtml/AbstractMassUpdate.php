<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Controller\Adminhtml;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\Model\AbstractModel;
use Magento\Ui\Component\MassAction\Filter;

/**
 * Sets one field ("field" param) to one value ("value" param) on the records selected in a listing.
 *
 * Only fields listed in $allowedFields (configured in etc/adminhtml/di.xml) can be changed,
 * and only to a value offered by their option source.
 */
abstract class AbstractMassUpdate extends Action implements HttpPostActionInterface
{
    /**
     * Initialize dependencies.
     *
     * @param Context $context
     * @param Filter $filter
     * @param OptionSourceInterface[] $allowedFields option sources indexed by field
     */
    public function __construct(
        Context $context,
        private readonly Filter $filter,
        private readonly array $allowedFields = []
    ) {
        parent::__construct($context);
    }

    /**
     * Create collection.
     *
     * @return AbstractDb
     */
    abstract protected function createCollection(): AbstractDb;

    /**
     * Save item.
     *
     * @param AbstractModel $item
     * @return void
     */
    abstract protected function saveItem(AbstractModel $item): void;

    /**
     * Execute the action.
     *
     * @return Redirect
     */
    public function execute()
    {
        $redirect = $this->resultRedirectFactory->create()->setPath('*/*/');
        $field = (string)$this->getRequest()->getParam('field');
        $value = (string)$this->getRequest()->getParam('value');

        if (!$this->isAllowed($field, $value)) {
            $this->messageManager->addErrorMessage(__('The requested change is not allowed.'));
            return $redirect;
        }

        try {
            $updated = 0;
            foreach ($this->filter->getCollection($this->createCollection()) as $item) {
                $item->setData($field, $value);
                $this->saveItem($item);
                $updated++;
            }
            $this->messageManager->addSuccessMessage(__('A total of %1 record(s) have been updated.', $updated));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        return $redirect;
    }

    /**
     * Whether allowed.
     *
     * @param string $field
     * @param string $value
     * @return bool
     */
    private function isAllowed(string $field, string $value): bool
    {
        $source = $this->allowedFields[$field] ?? null;
        if (!$source instanceof OptionSourceInterface) {
            return false;
        }

        foreach ($source->toOptionArray() as $option) {
            if (!is_array($option['value'] ?? null) && (string)$option['value'] === $value) {
                return true;
            }
        }

        return false;
    }
}
