<?php
/**
 * Session Grid Data Provider
 *
 * Provides data for session listing UI component
 *
 * @category  Vendor
 * @package   Vendor_QuickbooksConnector
 */
declare(strict_types=1);

namespace Vendor\QuickbooksConnector\Ui\Component\Listing\Session;

use Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider as AbstractDataProvider;
use Vendor\QuickbooksConnector\Model\ResourceModel\Session\CollectionFactory;

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
        $collection->orderByCreatedAt('DESC');

        $items = [];
        foreach ($collection as $session) {
            $items[] = [
                'entity_id' => $session->getEntityId(),
                'ticket' => substr($session->getTicket(), 0, 16) . '...',
                'ticket_full' => $session->getTicket(),
                'user' => $session->getUser(),
                'company' => $session->getCompany(),
                'progress' => $session->getProgress(),
                'current_job' => $session->getCurrentJob() ?: '-',
                'error' => $session->getError() ?: '-',
                'created_at' => $session->getCreatedAt(),
                'updated_at' => $session->getUpdatedAt()
            ];
        }

        return [
            'totalRecords' => count($items),
            'items' => $items
        ];
    }
}
