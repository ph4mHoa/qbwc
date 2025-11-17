<?php
/**
 * Job Grid Data Provider
 *
 * Provides data for job listing UI component
 *
 * @category  Vendor
 * @package   Vendor_QuickbooksConnector
 */
declare(strict_types=1);

namespace Vendor\QuickbooksConnector\Ui\Component\Listing\Job;

use Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider as AbstractDataProvider;
use Vendor\QuickbooksConnector\Model\ResourceModel\Job\CollectionFactory;

class DataProvider extends AbstractDataProvider
{
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * Constructor
     *
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        $collection = $this->collectionFactory->create();

        $items = [];
        foreach ($collection as $job) {
            $items[] = [
                'entity_id' => $job->getEntityId(),
                'name' => $job->getName(),
                'company' => $job->getCompany() ?: '(any)',
                'enabled' => $job->getEnabled(),
                'worker_class' => $job->getWorkerClass(),
                'created_at' => $job->getCreatedAt(),
                'updated_at' => $job->getUpdatedAt()
            ];
        }

        return [
            'totalRecords' => count($items),
            'items' => $items
        ];
    }
}
