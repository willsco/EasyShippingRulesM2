<?php

namespace WillSco\EasyShippingRulesM2\Model\ResourceModel\Carriers;

use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use WillSco\EasyShippingRulesM2\Model\CarriersFactory;

class DataProvider extends AbstractDataProvider
{
    protected $collection;
    protected DataPersistorInterface $dataPersistor;
    protected $loadedData;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CarriersFactory $carriers
     * @param DataPersistorInterface $dataPersistor
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CarriersFactory $carriers,
        DataPersistorInterface $dataPersistor,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collection = $carriers->create()->getCollection();
        $this->dataPersistor = $dataPersistor;
        $this->meta = $this->prepareMeta($this->meta);
    }

    /**
     * @param array $meta
     * @return array
     */
    public function prepareMeta(array $meta): array
    {
        return $meta;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }
        $items = $this->collection->getItems();
        foreach ($items as $carrier) {
            $this->loadedData[$carrier->getId()] = $carrier->getData();
        }

        $data = $this->dataPersistor->get('willsco_esrm2_carriers');
        if (!empty($data)) {
            $carrier = $this->collection->getNewEmptyItem();
            $carrier->setData($data);
            $this->loadedData[$carrier->getId()] = $carrier->getData();
            $this->dataPersistor->clear('willsco_esrm2_carriers');
        }

        return $this->loadedData;
    }
}
