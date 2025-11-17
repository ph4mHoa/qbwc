<?php
/**
 * Product Query Worker - Demonstrates Product/Item Query from QuickBooks
 *
 * This worker shows how to:
 * - Query inventory items from QuickBooks
 * - Handle different item types (Inventory, Service, Non-Inventory)
 * - Extract pricing and quantity information
 * - Process item categories/classes
 *
 * @category  Sample
 * @package   Sample_QuickbooksDemo
 */
declare(strict_types=1);

namespace Sample\QuickbooksDemo\Model\Worker;

use Vendor\QuickbooksConnector\Model\Worker\AbstractWorker;
use Vendor\QuickbooksConnector\Api\Data\JobInterface;
use Vendor\QuickbooksConnector\Api\Data\SessionInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Psr\Log\LoggerInterface;

class ProductQueryWorker extends AbstractWorker
{
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * Constructor
     *
     * @param LoggerInterface $logger
     * @param ProductRepositoryInterface $productRepository
     * @param SerializerInterface $serializer
     */
    public function __construct(
        LoggerInterface $logger,
        ProductRepositoryInterface $productRepository,
        SerializerInterface $serializer
    ) {
        parent::__construct($logger);
        $this->productRepository = $productRepository;
        $this->serializer = $serializer;
    }

    /**
     * Generate QBXML request to query items (products) from QuickBooks
     *
     * QuickBooks has different item types:
     * - ItemInventory (physical products with qty tracking)
     * - ItemNonInventory (products without qty tracking)
     * - ItemService (services)
     * - ItemInventoryAssembly (bundle products)
     *
     * @param JobInterface $job
     * @param SessionInterface $session
     * @param mixed $data
     * @return array
     */
    public function requests(JobInterface $job, SessionInterface $session, $data): array
    {
        $this->logInfo('ProductQueryWorker: Generating item query requests');

        // We can query multiple item types in one session
        // Each request type returns different data structures
        return [
            // Query Inventory Items
            [
                'ItemInventoryQueryRq' => [
                    'xml_attributes' => [
                        'requestID' => '1',
                        'iterator' => 'Start'
                    ],
                    'MaxReturned' => 100,
                    'ActiveStatus' => 'ActiveOnly'  // Only active items
                ]
            ],
            // Query Service Items
            [
                'ItemServiceQueryRq' => [
                    'xml_attributes' => [
                        'requestID' => '2',
                        'iterator' => 'Start'
                    ],
                    'MaxReturned' => 100,
                    'ActiveStatus' => 'ActiveOnly'
                ]
            ],
            // Query Non-Inventory Items
            [
                'ItemNonInventoryQueryRq' => [
                    'xml_attributes' => [
                        'requestID' => '3',
                        'iterator' => 'Start'
                    ],
                    'MaxReturned' => 100,
                    'ActiveStatus' => 'ActiveOnly'
                ]
            ]
        ];
    }

    /**
     * Handle item (product) response from QuickBooks
     *
     * This demonstrates handling different item types returned by QB
     *
     * @param array $response
     * @param SessionInterface $session
     * @param JobInterface $job
     * @param array|null $request
     * @param mixed $data
     * @return void
     */
    public function handleResponse(
        array $response,
        SessionInterface $session,
        JobInterface $job,
        ?array $request,
        $data
    ): void {
        $this->logInfo('ProductQueryWorker: Processing item response');

        try {
            // Determine which type of response this is
            $requestId = $request['xml_attributes']['requestID'] ?? 'unknown';

            // Check pagination
            $iteratorRemainingCount = $response['xml_attributes']['iteratorRemainingCount'] ?? '0';

            $this->logInfo("Processing request ID {$requestId}, remaining: {$iteratorRemainingCount}");

            // Handle different item types
            if (isset($response['ItemInventoryRet'])) {
                $this->processInventoryItems($response['ItemInventoryRet']);
            } elseif (isset($response['ItemServiceRet'])) {
                $this->processServiceItems($response['ItemServiceRet']);
            } elseif (isset($response['ItemNonInventoryRet'])) {
                $this->processNonInventoryItems($response['ItemNonInventoryRet']);
            } else {
                $this->logWarning('Unknown item response type received');
            }

        } catch (\Exception $e) {
            $this->logError('ProductQueryWorker error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Process inventory items (products with qty tracking)
     *
     * @param array $items
     * @return void
     */
    private function processInventoryItems(array $items): void
    {
        // Handle single item
        if (isset($items['ListID'])) {
            $items = [$items];
        }

        foreach ($items as $item) {
            $listId = $item['ListID'] ?? '';
            $name = $item['Name'] ?? '';
            $fullName = $item['FullName'] ?? $name;
            $sku = $item['ManufacturerPartNumber'] ?? $name;  // Use MPN as SKU
            $salesDesc = $item['SalesDesc'] ?? '';
            $salesPrice = $item['SalesPrice'] ?? 0;
            $purchaseCost = $item['PurchaseCost'] ?? 0;
            $quantityOnHand = $item['QuantityOnHand'] ?? 0;
            $reorderPoint = $item['ReorderPoint'] ?? 0;

            $this->logInfo(
                "Inventory Item: {$fullName}",
                [
                    'list_id' => $listId,
                    'sku' => $sku,
                    'price' => $salesPrice,
                    'cost' => $purchaseCost,
                    'qty' => $quantityOnHand,
                    'reorder_point' => $reorderPoint
                ]
            );

            // In production:
            // 1. Match to Magento product by SKU or QB ListID
            // 2. Update price, cost, qty
            // 3. Update product attributes
            // 4. Handle categories (QB SubItem)
        }
    }

    /**
     * Process service items (non-physical products)
     *
     * @param array $items
     * @return void
     */
    private function processServiceItems(array $items): void
    {
        // Handle single item
        if (isset($items['ListID'])) {
            $items = [$items];
        }

        foreach ($items as $item) {
            $listId = $item['ListID'] ?? '';
            $name = $item['Name'] ?? '';
            $fullName = $item['FullName'] ?? $name;
            $salesOrPurchase = $item['SalesOrPurchase'] ?? [];
            $price = $salesOrPurchase['Price'] ?? 0;
            $desc = $salesOrPurchase['Desc'] ?? '';

            $this->logInfo(
                "Service Item: {$fullName}",
                [
                    'list_id' => $listId,
                    'price' => $price,
                    'description' => $desc
                ]
            );

            // In production: Create/update Magento virtual product
        }
    }

    /**
     * Process non-inventory items (products without qty tracking)
     *
     * @param array $items
     * @return void
     */
    private function processNonInventoryItems(array $items): void
    {
        // Handle single item
        if (isset($items['ListID'])) {
            $items = [$items];
        }

        foreach ($items as $item) {
            $listId = $item['ListID'] ?? '';
            $name = $item['Name'] ?? '';
            $fullName = $item['FullName'] ?? $name;
            $salesOrPurchase = $item['SalesOrPurchase'] ?? [];
            $price = $salesOrPurchase['Price'] ?? 0;

            $this->logInfo(
                "Non-Inventory Item: {$fullName}",
                [
                    'list_id' => $listId,
                    'price' => $price
                ]
            );

            // In production: Create/update Magento simple product
        }
    }

    /**
     * Example: Only run product sync on weekends
     *
     * @param JobInterface $job
     * @param SessionInterface $session
     * @param mixed $data
     * @return bool
     */
    public function shouldRun(JobInterface $job, SessionInterface $session, $data): bool
    {
        // Run on weekends or if forced via job data
        $dayOfWeek = (int) date('N');  // 1=Monday, 7=Sunday

        if ($data) {
            $jobData = is_string($data) ? $this->serializer->unserialize($data) : $data;
            if (isset($jobData['force']) && $jobData['force'] === true) {
                $this->logInfo('Product sync forced via job data');
                return true;
            }
        }

        if ($dayOfWeek >= 6) {
            $this->logInfo('Product sync allowed: Weekend');
            return true;
        }

        $this->logWarning('Product sync skipped: Weekday (set force=true in job data to override)');
        return false;
    }
}
