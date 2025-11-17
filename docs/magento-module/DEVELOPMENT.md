# Development Guide

## 📋 Mục Lục

- [Getting Started](#getting-started)
- [Creating Custom Workers](#creating-custom-workers)
- [Working with QBXML](#working-with-qbxml)
- [Testing](#testing)
- [Debugging](#debugging)
- [Best Practices](#best-practices)
- [Common Patterns](#common-patterns)
- [Examples](#examples)

---

## 🚀 Getting Started

### Development Environment Setup

#### 1. Clone and Install Module

```bash
# Navigate to Magento root
cd /path/to/magento

# Create module directory
mkdir -p app/code/Vendor/QuickbooksConnector

# Copy module files or clone from git
git clone <repository-url> app/code/Vendor/QuickbooksConnector

# Enable module
php bin/magento module:enable Vendor_QuickbooksConnector

# Run setup
php bin/magento setup:upgrade
php bin/magento setup:di:compile

# Set developer mode
php bin/magento deploy:mode:set developer

# Disable cache during development
php bin/magento cache:disable
```

#### 2. Enable Logging

```bash
# Enable QBWC logging
php bin/magento config:set qbwc/general/log_requests_and_responses 1

# Check logs
tail -f var/log/qbwc.log
tail -f var/log/system.log
```

#### 3. Install QuickBooks Web Connector

- Download from Intuit website
- Install on Windows machine (can use VM)
- Download QWC file from Magento: `https://yourstore.com/qbwc/download/qwc`

---

## 🔧 Creating Custom Workers

### Worker Structure

```php
<?php
namespace Vendor\MyModule\Worker;

use Vendor\QuickbooksConnector\Model\Worker\AbstractWorker;
use Vendor\QuickbooksConnector\Api\Data\JobInterface;
use Vendor\QuickbooksConnector\Api\Data\SessionInterface;

class MyCustomWorker extends AbstractWorker
{
    /**
     * Define what QBXML requests to send
     *
     * @param JobInterface $job
     * @param SessionInterface $session
     * @param mixed $data
     * @return array
     */
    public function requests($job, $session, $data)
    {
        return [
            // Request 1
            [
                'CustomerQueryRq' => [
                    'xml_attributes' => [
                        'requestID' => '1',
                        'iterator' => 'Start'
                    ],
                    'MaxReturned' => 100
                ]
            ],

            // Request 2
            [
                'InvoiceQueryRq' => [
                    'xml_attributes' => ['requestID' => '2'],
                    'MaxReturned' => 50
                ]
            ]
        ];
    }

    /**
     * Determine if this job should run
     *
     * @param JobInterface $job
     * @param SessionInterface $session
     * @param mixed $data
     * @return bool
     */
    public function shouldRun($job, $session, $data)
    {
        // Example: Only run during business hours
        $hour = (int) date('H');
        return ($hour >= 8 && $hour <= 18);
    }

    /**
     * Handle response from QuickBooks
     *
     * @param array $response Parsed QBXML response
     * @param SessionInterface $session
     * @param JobInterface $job
     * @param array $request Original request
     * @param mixed $data Job data
     * @return void
     */
    public function handleResponse($response, $session, $job, $request, $data)
    {
        // Process the response
        if (isset($response['CustomerQueryRs'])) {
            $this->processCustomers($response['CustomerQueryRs']);
        }

        if (isset($response['InvoiceQueryRs'])) {
            $this->processInvoices($response['InvoiceQueryRs']);
        }
    }

    protected function processCustomers($data)
    {
        // Implementation
    }

    protected function processInvoices($data)
    {
        // Implementation
    }
}
```

---

### Example 1: Customer Sync Worker

```php
<?php
namespace Vendor\MyModule\Worker;

use Vendor\QuickbooksConnector\Model\Worker\AbstractWorker;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Psr\Log\LoggerInterface;

class CustomerSyncWorker extends AbstractWorker
{
    protected $customerRepository;
    protected $customerFactory;
    protected $logger;

    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        CustomerInterfaceFactory $customerFactory,
        LoggerInterface $logger
    ) {
        $this->customerRepository = $customerRepository;
        $this->customerFactory = $customerFactory;
        $this->logger = $logger;
    }

    public function requests($job, $session, $data)
    {
        $lastSyncTime = $data['last_sync_time'] ?? null;

        $request = [
            'CustomerQueryRq' => [
                'xml_attributes' => [
                    'requestID' => '1',
                    'iterator' => 'Start'
                ],
                'MaxReturned' => 100,
                'ActiveStatus' => 'All'
            ]
        ];

        // Only sync modified customers
        if ($lastSyncTime) {
            $request['CustomerQueryRq']['FromModifiedDate'] =
                date('Y-m-d\TH:i:s', $lastSyncTime);
        }

        return [$request];
    }

    public function handleResponse($response, $session, $job, $request, $data)
    {
        if (!isset($response['CustomerQueryRs']['CustomerRet'])) {
            $this->logger->warning('No customers in response');
            return;
        }

        $customers = $response['CustomerQueryRs']['CustomerRet'];

        // Handle single customer case
        if (isset($customers['ListID'])) {
            $customers = [$customers];
        }

        $synced = 0;
        $errors = 0;

        foreach ($customers as $qbCustomer) {
            try {
                $this->syncCustomer($qbCustomer);
                $synced++;
            } catch (\Exception $e) {
                $this->logger->error('Customer sync error: ' . $e->getMessage());
                $errors++;
            }
        }

        $this->logger->info("Synced {$synced} customers, {$errors} errors");

        // Update last sync time
        $job->setData('last_sync_time', time());
    }

    protected function syncCustomer($qbCustomer)
    {
        $qbListId = $qbCustomer['ListID'];

        // Find existing customer by QB ID
        $customer = $this->findCustomerByQbId($qbListId);

        if (!$customer) {
            $customer = $this->customerFactory->create();
            $customer->setCustomAttribute('qb_list_id', $qbListId);
        }

        // Map QuickBooks fields to Magento
        $nameParts = explode(' ', $qbCustomer['FullName'] ?? '', 2);
        $customer->setFirstname($nameParts[0] ?? 'Unknown');
        $customer->setLastname($nameParts[1] ?? '');
        $customer->setEmail($qbCustomer['Email'] ?? $this->generateEmail($qbListId));

        // Address
        if (isset($qbCustomer['BillAddress'])) {
            $this->mapAddress($customer, $qbCustomer['BillAddress']);
        }

        // Save
        $this->customerRepository->save($customer);

        $this->logger->info("Synced customer: {$qbCustomer['Name']}");
    }

    protected function findCustomerByQbId($qbListId)
    {
        // Search for customer with matching QB ID
        // Implementation depends on your setup
        return null;
    }

    protected function generateEmail($qbListId)
    {
        return 'qb_' . md5($qbListId) . '@noemail.com';
    }

    protected function mapAddress($customer, $qbAddress)
    {
        // Map QB address to Magento address
        // Implementation...
    }
}
```

---

### Example 2: Invoice Sync Worker

```php
<?php
namespace Vendor\MyModule\Worker;

use Vendor\QuickbooksConnector\Model\Worker\AbstractWorker;
use Magento\Sales\Api\OrderRepositoryInterface;

class InvoiceSyncWorker extends AbstractWorker
{
    protected $orderRepository;

    public function __construct(
        OrderRepositoryInterface $orderRepository
    ) {
        $this->orderRepository = $orderRepository;
    }

    public function requests($job, $session, $data)
    {
        // Get Magento orders that need to be synced to QB
        $orderIds = $data['order_ids'] ?? [];

        if (empty($orderIds)) {
            return [];
        }

        $requests = [];

        foreach ($orderIds as $orderId) {
            $order = $this->orderRepository->get($orderId);

            $requests[] = [
                'InvoiceAddRq' => [
                    'xml_attributes' => [
                        'requestID' => $orderId
                    ],
                    'InvoiceAdd' => $this->buildInvoiceData($order)
                ]
            ];
        }

        return $requests;
    }

    public function handleResponse($response, $session, $job, $request, $data)
    {
        if (!isset($response['InvoiceAddRs'])) {
            return;
        }

        $result = $response['InvoiceAddRs'];
        $requestId = $result['@attributes']['requestID'];

        if ($result['@attributes']['statusCode'] === '0') {
            // Success
            $txnId = $result['InvoiceRet']['TxnID'];

            // Update Magento order with QB TxnID
            $order = $this->orderRepository->get($requestId);
            $order->setData('qb_txn_id', $txnId);
            $this->orderRepository->save($order);

            $this->logger->info("Invoice created in QB: {$txnId}");
        } else {
            // Error
            $statusMessage = $result['@attributes']['statusMessage'];
            $this->logger->error("Invoice creation failed: {$statusMessage}");
        }
    }

    protected function buildInvoiceData($order)
    {
        return [
            'CustomerRef' => [
                'ListID' => $order->getCustomer()->getData('qb_list_id')
            ],
            'TxnDate' => date('Y-m-d', strtotime($order->getCreatedAt())),
            'RefNumber' => $order->getIncrementId(),
            'BillAddress' => $this->formatAddress($order->getBillingAddress()),
            'InvoiceLineAdd' => $this->buildLineItems($order)
        ];
    }

    protected function buildLineItems($order)
    {
        $lines = [];

        foreach ($order->getAllVisibleItems() as $item) {
            $lines[] = [
                'ItemRef' => [
                    'FullName' => $item->getSku()
                ],
                'Desc' => $item->getName(),
                'Quantity' => $item->getQtyOrdered(),
                'Rate' => $item->getPrice(),
                'Amount' => $item->getRowTotal()
            ];
        }

        return $lines;
    }

    protected function formatAddress($address)
    {
        // Format Magento address to QB address
        return [
            'Addr1' => $address->getStreetLine(1),
            'Addr2' => $address->getStreetLine(2),
            'City' => $address->getCity(),
            'State' => $address->getRegion(),
            'PostalCode' => $address->getPostcode(),
            'Country' => $address->getCountryId()
        ];
    }
}
```

---

### Example 3: Dynamic Requests Worker

```php
<?php
namespace Vendor\MyModule\Worker;

use Vendor\QuickbooksConnector\Model\Worker\AbstractWorker;

/**
 * Worker that generates requests dynamically based on Magento data
 */
class DynamicSyncWorker extends AbstractWorker
{
    protected $productRepository;
    protected $searchCriteriaBuilder;

    public function __construct(
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * Generate requests dynamically based on Magento products
     */
    public function requests($job, $session, $data)
    {
        // Get products that need sync
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('qb_needs_sync', 1)
            ->setPageSize(50)
            ->create();

        $products = $this->productRepository->getList($searchCriteria)->getItems();

        $requests = [];

        foreach ($products as $product) {
            $qbId = $product->getCustomAttribute('qb_list_id');

            if ($qbId) {
                // Update existing item
                $requests[] = $this->buildItemModRequest($product);
            } else {
                // Add new item
                $requests[] = $this->buildItemAddRequest($product);
            }
        }

        return $requests;
    }

    public function handleResponse($response, $session, $job, $request, $data)
    {
        // Handle ItemAddRs
        if (isset($response['ItemInventoryAddRs'])) {
            $this->handleItemAdd($response['ItemInventoryAddRs']);
        }

        // Handle ItemModRs
        if (isset($response['ItemInventoryModRs'])) {
            $this->handleItemMod($response['ItemInventoryModRs']);
        }
    }

    protected function buildItemAddRequest($product)
    {
        return [
            'ItemInventoryAddRq' => [
                'xml_attributes' => [
                    'requestID' => $product->getId()
                ],
                'ItemInventoryAdd' => [
                    'Name' => $product->getSku(),
                    'IsActive' => $product->getStatus() == 1 ? 'true' : 'false',
                    'SalesDesc' => $product->getName(),
                    'SalesPrice' => $product->getPrice(),
                    'QuantityOnHand' => $product->getStockItem()->getQty()
                ]
            ]
        ];
    }

    protected function buildItemModRequest($product)
    {
        return [
            'ItemInventoryModRq' => [
                'xml_attributes' => [
                    'requestID' => $product->getId()
                ],
                'ItemInventoryMod' => [
                    'ListID' => $product->getCustomAttribute('qb_list_id')->getValue(),
                    'EditSequence' => $product->getCustomAttribute('qb_edit_sequence')->getValue(),
                    'Name' => $product->getSku(),
                    'IsActive' => $product->getStatus() == 1 ? 'true' : 'false',
                    'SalesPrice' => $product->getPrice()
                ]
            ]
        ];
    }

    protected function handleItemAdd($response)
    {
        $productId = $response['@attributes']['requestID'];

        if ($response['@attributes']['statusCode'] === '0') {
            $listId = $response['ItemInventoryRet']['ListID'];
            $editSequence = $response['ItemInventoryRet']['EditSequence'];

            // Update product with QB data
            $product = $this->productRepository->getById($productId);
            $product->setCustomAttribute('qb_list_id', $listId);
            $product->setCustomAttribute('qb_edit_sequence', $editSequence);
            $product->setCustomAttribute('qb_needs_sync', 0);
            $this->productRepository->save($product);
        }
    }

    protected function handleItemMod($response)
    {
        // Similar to handleItemAdd
    }
}
```

---

## 📝 Working with QBXML

### QBXML Request Structure

```php
$request = [
    'QueryOrAddRqName' => [              // Request name
        'xml_attributes' => [             // XML attributes
            'requestID' => '1',           // Unique request ID
            'iterator' => 'Start'         // Iterator: Start/Continue
        ],
        'MaxReturned' => 100,             // Fields
        'FromModifiedDate' => '2025-01-01T00:00:00',
        'IncludeRetElement' => ['Name', 'Email']  // Specific fields
    ]
];
```

**Converts to:**

```xml
<QueryOrAddRqName requestID="1" iterator="Start">
    <MaxReturned>100</MaxReturned>
    <FromModifiedDate>2025-01-01T00:00:00</FromModifiedDate>
    <IncludeRetElement>Name</IncludeRetElement>
    <IncludeRetElement>Email</IncludeRetElement>
</QueryOrAddRqName>
```

---

### Common QBXML Requests

#### Customer Query

```php
[
    'CustomerQueryRq' => [
        'xml_attributes' => ['requestID' => '1'],
        'MaxReturned' => 100,
        'ActiveStatus' => 'All',  // All/ActiveOnly/InactiveOnly
        'FromModifiedDate' => '2025-01-01T00:00:00',
        'ToModifiedDate' => '2025-01-31T23:59:59',
        'IncludeRetElement' => ['ListID', 'Name', 'Email', 'BillAddress']
    ]
]
```

#### Invoice Add

```php
[
    'InvoiceAddRq' => [
        'xml_attributes' => ['requestID' => '123'],
        'InvoiceAdd' => [
            'CustomerRef' => [
                'ListID' => '80000001-1234567890'
            ],
            'TxnDate' => '2025-01-15',
            'RefNumber' => 'INV-001',
            'BillAddress' => [
                'Addr1' => '123 Main St',
                'City' => 'New York',
                'State' => 'NY',
                'PostalCode' => '10001'
            ],
            'InvoiceLineAdd' => [
                [
                    'ItemRef' => ['FullName' => 'Product A'],
                    'Desc' => 'Product Description',
                    'Quantity' => 2,
                    'Rate' => 50.00,
                    'Amount' => 100.00
                ],
                [
                    'ItemRef' => ['FullName' => 'Product B'],
                    'Quantity' => 1,
                    'Rate' => 75.00,
                    'Amount' => 75.00
                ]
            ]
        ]
    ]
]
```

#### Item Inventory Query

```php
[
    'ItemInventoryQueryRq' => [
        'xml_attributes' => ['requestID' => '1', 'iterator' => 'Start'],
        'MaxReturned' => 100,
        'ActiveStatus' => 'ActiveOnly',
        'IncludeRetElement' => [
            'ListID',
            'Name',
            'IsActive',
            'SalesPrice',
            'QuantityOnHand'
        ]
    ]
]
```

---

### Handling Iterator (Pagination)

```php
public function requests($job, $session, $data)
{
    // First request starts the iterator
    return [
        [
            'CustomerQueryRq' => [
                'xml_attributes' => [
                    'requestID' => '1',
                    'iterator' => 'Start'  // Important!
                ],
                'MaxReturned' => 100
            ]
        ]
    ];
}

public function handleResponse($response, $session, $job, $request, $data)
{
    $attrs = $response['CustomerQueryRs']['@attributes'];

    // Check if more data available
    if (isset($attrs['iteratorRemainingCount']) &&
        $attrs['iteratorRemainingCount'] > 0) {

        // Session will automatically continue with same request
        // using iteratorID from response
        $this->logger->info(
            "More data available: {$attrs['iteratorRemainingCount']} remaining"
        );
    }

    // Process current batch
    $customers = $response['CustomerQueryRs']['CustomerRet'];
    // ...
}
```

**QBWC automatically handles iterator continuation!**

---

## 🧪 Testing

### Unit Testing Worker

```php
<?php
namespace Vendor\MyModule\Test\Unit\Worker;

use PHPUnit\Framework\TestCase;
use Vendor\MyModule\Worker\CustomerSyncWorker;

class CustomerSyncWorkerTest extends TestCase
{
    private $worker;
    private $customerRepository;

    protected function setUp(): void
    {
        $this->customerRepository = $this->createMock(
            \Magento\Customer\Api\CustomerRepositoryInterface::class
        );

        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->worker = $objectManager->getObject(
            CustomerSyncWorker::class,
            ['customerRepository' => $this->customerRepository]
        );
    }

    public function testRequestsGeneration()
    {
        $job = $this->createMock(\Vendor\QuickbooksConnector\Api\Data\JobInterface::class);
        $session = $this->createMock(\Vendor\QuickbooksConnector\Api\Data\SessionInterface::class);

        $requests = $this->worker->requests($job, $session, []);

        $this->assertIsArray($requests);
        $this->assertNotEmpty($requests);
        $this->assertArrayHasKey('CustomerQueryRq', $requests[0]);
    }

    public function testHandleResponse()
    {
        $response = [
            'CustomerQueryRs' => [
                '@attributes' => ['statusCode' => '0'],
                'CustomerRet' => [
                    'ListID' => '80000001',
                    'Name' => 'Test Customer',
                    'Email' => 'test@example.com'
                ]
            ]
        ];

        $this->customerRepository->expects($this->once())
            ->method('save');

        $this->worker->handleResponse($response, null, null, null, []);
    }
}
```

---

### Integration Testing

```php
/**
 * @magentoDbIsolation enabled
 * @magentoAppArea frontend
 */
public function testFullSync()
{
    // Create job
    $job = $this->jobFactory->create();
    $job->setName('test_sync');
    $job->setWorkerClass(CustomerSyncWorker::class);
    $this->jobRepository->save($job);

    // Create session
    $session = $this->sessionFactory->create();
    $session->setTicket('test_ticket');
    $this->sessionRepository->save($session);

    // Get requests
    $worker = $this->workerFactory->create(CustomerSyncWorker::class);
    $requests = $worker->requests($job, $session, []);

    $this->assertNotEmpty($requests);
}
```

---

## 🐛 Debugging

### Enable Debug Logging

```php
class MyWorker extends AbstractWorker
{
    public function handleResponse($response, $session, $job, $request, $data)
    {
        // Log full response
        $this->logger->debug('Full response:', ['response' => $response]);

        // Log specific fields
        $this->logger->debug('Status code: ' . $response['...']['@attributes']['statusCode']);

        // Your logic
    }
}
```

### Check Logs

```bash
# QBWC specific log
tail -f var/log/qbwc.log

# System log
tail -f var/log/system.log

# Exception log
tail -f var/log/exception.log

# Search for specific errors
grep "ERROR" var/log/qbwc.log
```

### Xdebug Setup

```ini
; php.ini
[xdebug]
zend_extension=xdebug.so
xdebug.mode=debug
xdebug.start_with_request=yes
xdebug.client_host=localhost
xdebug.client_port=9003
```

---

## ✅ Best Practices

### 1. Error Handling

```php
public function handleResponse($response, $session, $job, $request, $data)
{
    try {
        // Check response status
        $attrs = $response['CustomerQueryRs']['@attributes'] ?? [];

        if (($attrs['statusCode'] ?? '0') !== '0') {
            $this->logger->error('QB Error: ' . ($attrs['statusMessage'] ?? 'Unknown'));
            return;
        }

        // Process data
        $this->processData($response);

    } catch (\Exception $e) {
        $this->logger->error('Worker error: ' . $e->getMessage());
        $this->logger->error($e->getTraceAsString());

        // Don't throw - let QBWC continue or stop based on config
    }
}
```

### 2. Idempotency

```php
protected function syncCustomer($qbCustomer)
{
    $qbId = $qbCustomer['ListID'];

    // Always check if customer exists first
    $customer = $this->findCustomerByQbId($qbId);

    if ($customer) {
        // Update existing
        $this->logger->info("Updating existing customer: {$qbId}");
    } else {
        // Create new
        $customer = $this->customerFactory->create();
        $this->logger->info("Creating new customer: {$qbId}");
    }

    // Apply updates
    // ...
}
```

### 3. Batch Processing

```php
public function handleResponse($response, $session, $job, $request, $data)
{
    $customers = $response['CustomerQueryRs']['CustomerRet'];

    if (isset($customers['ListID'])) {
        $customers = [$customers];
    }

    // Process in batches
    $batch = [];
    $batchSize = 10;

    foreach ($customers as $customer) {
        $batch[] = $customer;

        if (count($batch) >= $batchSize) {
            $this->processBatch($batch);
            $batch = [];
        }
    }

    // Process remaining
    if (!empty($batch)) {
        $this->processBatch($batch);
    }
}
```

### 4. Data Validation

```php
protected function syncCustomer($qbCustomer)
{
    // Validate required fields
    if (empty($qbCustomer['ListID'])) {
        $this->logger->warning('Customer missing ListID, skipping');
        return;
    }

    if (empty($qbCustomer['Email'])) {
        $this->logger->info('Customer missing email, generating placeholder');
        $qbCustomer['Email'] = $this->generateEmail($qbCustomer['ListID']);
    }

    // Sanitize data
    $email = filter_var($qbCustomer['Email'], FILTER_SANITIZE_EMAIL);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $this->logger->warning("Invalid email: {$email}");
        $email = $this->generateEmail($qbCustomer['ListID']);
    }

    // Continue with sync
}
```

---

## 🎯 Common Patterns

### Pattern 1: Two-Way Sync

```php
/**
 * Sync Magento → QuickBooks
 */
class MagentoToQbWorker extends AbstractWorker
{
    public function requests($job, $session, $data)
    {
        // Get Magento orders that need sync
        $orders = $this->getOrdersNeedingSync();

        $requests = [];
        foreach ($orders as $order) {
            $requests[] = $this->buildInvoiceAddRequest($order);
        }

        return $requests;
    }

    public function handleResponse($response, $session, $job, $request, $data)
    {
        // Update Magento with QB IDs
    }
}

/**
 * Sync QuickBooks → Magento
 */
class QbToMagentoWorker extends AbstractWorker
{
    public function requests($job, $session, $data)
    {
        // Query QB for changes
        return [
            [
                'CustomerQueryRq' => [
                    'FromModifiedDate' => $this->getLastSyncTime()
                ]
            ]
        ];
    }

    public function handleResponse($response, $session, $job, $request, $data)
    {
        // Update Magento with QB data
    }
}
```

### Pattern 2: Conditional Sync

```php
public function shouldRun($job, $session, $data)
{
    // Only run on specific days
    if (!in_array(date('N'), [1, 3, 5])) { // Mon, Wed, Fri
        return false;
    }

    // Only if there's data to sync
    $count = $this->getUnsyncedCount();
    if ($count === 0) {
        $this->logger->info('No data to sync');
        return false;
    }

    return true;
}
```

---

**Last Updated**: 2025-11-16
