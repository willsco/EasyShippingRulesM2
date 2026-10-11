<?php
/**
 * Copyright © WillSco All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace WillSco\EasyShippingRulesM2\Ui\DataProvider;

use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use WillSco\EasyShippingRulesM2\Api\Data\MethodInterface;

/**
 * Edit form data for any module entity; configured per entity through virtual types.
 *
 * Data posted in a failed save is restored from the data persistor.
 */
class FormDataProvider extends AbstractDataProvider
{
    /**
     * @var array|null
     */
    private ?array $loadedData = null;

    /**
     * Initialize dependencies.
     *
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param DataPersistorInterface $dataPersistor
     * @param StoreManagerInterface $storeManager
     * @param AbstractCollection $collection entity collection (configure it with shared="false")
     * @param string $persistorKey
     * @param bool $hasStoreField hide "store_ids" in single store mode
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        private readonly DataPersistorInterface $dataPersistor,
        private readonly StoreManagerInterface $storeManager,
        AbstractCollection $collection,
        private readonly string $persistorKey,
        private readonly bool $hasStoreField = false,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collection;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * @inheritdoc
     */
    public function getData()
    {
        if ($this->loadedData !== null) {
            return $this->loadedData;
        }

        $this->loadedData = [];
        foreach ($this->collection->getItems() as $item) {
            $this->loadedData[$item->getId()] = $this->stringifyLists($item->getData());
        }

        $persisted = $this->dataPersistor->get($this->persistorKey);
        if (!empty($persisted)) {
            $this->loadedData[$persisted[$this->primaryFieldName] ?? ''] = $persisted;
            $this->dataPersistor->clear($this->persistorKey);
        }

        return $this->loadedData;
    }

    /**
     * Multiselects only mark options whose value is identical, and option values are strings.
     *
     * @param array $data
     * @return array
     */
    private function stringifyLists(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = array_map(static fn ($item) => is_scalar($item) ? (string)$item : $item, $value);
            }
        }

        return $data;
    }

    /**
     * @inheritdoc
     */
    public function getMeta()
    {
        $meta = parent::getMeta();
        if ($this->hasStoreField && $this->storeManager->isSingleStoreMode()) {
            $meta['general']['children'][MethodInterface::STORE_IDS]['arguments']['data']['config']['visible'] = false;
        }

        return $meta;
    }
}
