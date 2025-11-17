# Sample QuickBooks Demo Module

**Demonstrates how to use `Vendor_QuickbooksConnector` module for QuickBooks Desktop integration**

## 📋 Overview

This sample module demonstrates complete usage of the `Vendor_QuickbooksConnector` module, showing real-world examples of:

- ✅ **Creating custom Workers** for QB data synchronization
- ✅ **Managing Jobs** programmatically via JobManager service
- ✅ **CLI Commands** for testing and administration
- ✅ **Handling QB responses** with pagination and error handling
- ✅ **Passing data to Workers** for flexible job configuration
- ✅ **Conditional job execution** with `shouldRun()` logic

Based on the [Rails QBWC gem](https://github.com/skryl/qbwc) examples, adapted for Magento 2.

---

## 🎯 What's Included

### 1. Sample Workers (3 Complete Examples)

#### CustomerSyncWorker
**Purpose:** Sync customers from QuickBooks to Magento

**Features:**
- Demonstrates iterator pagination (100 customers per batch)
- Shows how to parse QB customer data
- Example of handling QB `ListID` for matching
- Logs customer information for demonstration

**Location:** `Model/Worker/CustomerSyncWorker.php`

#### InvoiceSyncWorker
**Purpose:** Sync invoices/orders from QuickBooks to Magento

**Features:**
- Date range filtering via job data
- Processing invoice line items
- Payment information extraction
- Conditional execution (only runs 9 PM - 6 AM)

**Location:** `Model/Worker/InvoiceSyncWorker.php`

#### ProductQueryWorker
**Purpose:** Query inventory items from QuickBooks

**Features:**
- Multiple request types (Inventory, Service, Non-Inventory items)
- Handles different QB item structures
- Weekend-only execution (configurable)
- Force run option via job data

**Location:** `Model/Worker/ProductQueryWorker.php`

### 2. JobManager Service

**Purpose:** Simplified job creation and management

**Features:**
- Easy job creation methods
- Automatic worker class configuration
- Job lifecycle management (enable/disable/delete)
- Company-based filtering

**Location:** `Model/JobManager.php`

**Interface:** `Api/JobManagerInterface.php`

### 3. CLI Commands (5 Interactive Commands)

| Command | Purpose |
|---------|---------|
| `sample:qb:customer:sync` | Create customer sync job |
| `sample:qb:invoice:sync` | Create invoice sync job with date range |
| `sample:qb:product:query` | Create product query job |
| `sample:qb:job:list` | List all jobs (filterable by company) |
| `sample:qb:job:create` | Interactive job creation wizard |

**Location:** `Console/Command/`

---

## 🚀 Quick Start

### 1. Installation

```bash
# Copy module to Magento
cp -r Sample/QuickbooksDemo /path/to/magento/app/code/Sample/

# Enable modules
php bin/magento module:enable Vendor_QuickbooksConnector Sample_QuickbooksDemo

# Run setup
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento cache:flush
```

### 2. Create Your First Job

**Option A: Using CLI Command**
```bash
# Interactive job creation
php bin/magento sample:qb:job:create

# Or create directly
php bin/magento sample:qb:customer:sync "C:\QuickBooks\MyCompany.qbw"
```

**Option B: Using JobManager in Code**
```php
<?php
use Sample\QuickbooksDemo\Api\JobManagerInterface;

class YourClass
{
    private $jobManager;

    public function __construct(JobManagerInterface $jobManager)
    {
        $this->jobManager = $jobManager;
    }

    public function createJob()
    {
        // Create customer sync job
        $job = $this->jobManager->createCustomerSyncJob(
            'C:\\QuickBooks\\MyCompany.qbw',
            true  // enabled
        );

        echo "Job created: " . $job->getName();
    }
}
```

### 3. View Jobs

```bash
# List all jobs
php bin/magento sample:qb:job:list

# Filter by company
php bin/magento sample:qb:job:list --company="C:\QuickBooks\MyCompany.qbw"
```

### 4. Configure QuickBooks Web Connector

1. Download QWC file from Magento: `https://your-magento.com/qbwc/qwc`
2. Import QWC file in QuickBooks Web Connector
3. Enter password (configured in `config/qbwc.rb`)
4. Run "Update Selected" to execute jobs

### 5. Monitor Logs

```bash
# Watch demo logs
tail -f var/log/quickbooks_demo.log

# Watch connector logs
tail -f var/log/quickbooks_connector.log
```

---

## 📚 Usage Examples

### Example 1: Create Customer Sync Job

```bash
# Basic usage
php bin/magento sample:qb:customer:sync

# Specify company file
php bin/magento sample:qb:customer:sync "C:\QB\OceanicAir.qbw"

# Create disabled (enable later)
php bin/magento sample:qb:customer:sync --disable
```

**What happens:**
1. Job is created in `qbwc_jobs` table
2. When QBWC connects, job is picked up
3. `CustomerSyncWorker::requests()` generates QBXML
4. QB processes request and returns customer data
5. `CustomerSyncWorker::handleResponse()` processes each customer
6. Iterator continues until all customers synced

### Example 2: Create Invoice Sync Job with Date Filter

```bash
# Sync invoices from last month
php bin/magento sample:qb:invoice:sync \
    "C:\QB\Company.qbw" \
    --from="2025-10-01" \
    --to="2025-10-31"

# Sync all invoices
php bin/magento sample:qb:invoice:sync
```

**Job data passed to worker:**
```json
{
    "date_from": "2025-10-01",
    "date_to": "2025-10-31"
}
```

**Worker receives data:**
```php
public function requests(JobInterface $job, SessionInterface $session, $data): array
{
    $jobData = $this->serializer->unserialize($data);
    $dateFrom = $jobData['date_from'] ?? null;
    $dateTo = $jobData['date_to'] ?? null;

    // Use dates in QBXML request...
}
```

### Example 3: Create Product Query Job

```bash
# Normal mode (weekends only)
php bin/magento sample:qb:product:query

# Force run on any day
php bin/magento sample:qb:product:query --force
```

**Conditional execution:**
```php
public function shouldRun(JobInterface $job, SessionInterface $session, $data): bool
{
    // Check force flag in job data
    $jobData = $this->serializer->unserialize($data);
    if ($jobData['force'] ?? false) {
        return true;
    }

    // Otherwise only run on weekends
    $dayOfWeek = (int) date('N');
    return $dayOfWeek >= 6;
}
```

### Example 4: Programmatic Job Management

```php
<?php
use Sample\QuickbooksDemo\Api\JobManagerInterface;

class MyService
{
    private $jobManager;

    public function __construct(JobManagerInterface $jobManager)
    {
        $this->jobManager = $jobManager;
    }

    public function setupQuickBooksSync()
    {
        $companyFile = 'C:\\QuickBooks\\MyCompany.qbw';

        // Create all sync jobs
        $this->jobManager->createCustomerSyncJob($companyFile, true);

        $this->jobManager->createInvoiceSyncJob(
            $companyFile,
            true,
            date('Y-m-01'),  // First day of month
            date('Y-m-t')    // Last day of month
        );

        $this->jobManager->createProductQueryJob($companyFile, false);

        // List all jobs
        $jobs = $this->jobManager->listAllJobs();
        foreach ($jobs as $job) {
            echo $job->getName() . " - " .
                 ($job->getEnabled() ? 'Enabled' : 'Disabled') . "\n";
        }

        // Enable/disable jobs
        $this->jobManager->enableJob('sample_customer_sync');
        $this->jobManager->disableJob('sample_product_query');

        // Delete job
        $this->jobManager->deleteJob('sample_invoice_sync');
    }
}
```

---

## 🛠️ Creating Your Own Worker

### Step 1: Create Worker Class

```php
<?php
namespace YourVendor\YourModule\Model\Worker;

use Vendor\QuickbooksConnector\Model\Worker\AbstractWorker;
use Vendor\QuickbooksConnector\Api\Data\JobInterface;
use Vendor\QuickbooksConnector\Api\Data\SessionInterface;

class MyCustomWorker extends AbstractWorker
{
    /**
     * Generate QBXML requests
     */
    public function requests(JobInterface $job, SessionInterface $session, $data): array
    {
        // Return QBXML request(s)
        return [
            [
                'CustomerQueryRq' => [
                    'xml_attributes' => ['requestID' => '1'],
                    'MaxReturned' => 100
                ]
            ]
        ];
    }

    /**
     * Handle QB response
     */
    public function handleResponse(
        array $response,
        SessionInterface $session,
        JobInterface $job,
        ?array $request,
        $data
    ): void {
        // Process response data
        $customers = $response['CustomerRet'] ?? [];

        foreach ($customers as $customer) {
            $this->logInfo("Customer: " . $customer['Name']);
            // Your sync logic here
        }
    }

    /**
     * Conditional execution (optional)
     */
    public function shouldRun(JobInterface $job, SessionInterface $session, $data): bool
    {
        // Add your conditions
        return true;
    }
}
```

### Step 2: Create Job

```php
<?php
use Vendor\QuickbooksConnector\Api\JobRepositoryInterface;
use Vendor\QuickbooksConnector\Api\Data\JobInterfaceFactory;

class JobCreator
{
    private $jobRepository;
    private $jobFactory;

    public function createMyJob()
    {
        $job = $this->jobFactory->create();

        $job->setName('my_custom_job')
            ->setCompany('C:\\QuickBooks\\company.qbw')
            ->setWorkerClass(\YourVendor\YourModule\Model\Worker\MyCustomWorker::class)
            ->setEnabled(true)
            ->setRequestsProvidedWhenJobAdded(false);

        $this->jobRepository->save($job);
    }
}
```

---

## 📖 QBXML Request Examples

### Customer Query
```php
return [
    [
        'CustomerQueryRq' => [
            'xml_attributes' => [
                'requestID' => '1',
                'iterator' => 'Start'
            ],
            'MaxReturned' => 100,
            'ActiveStatus' => 'All',
            'FromModifiedDate' => '2025-01-01T00:00:00',
            'ToModifiedDate' => '2025-12-31T23:59:59'
        ]
    ]
];
```

### Invoice Query
```php
return [
    [
        'InvoiceQueryRq' => [
            'xml_attributes' => ['requestID' => '1'],
            'TxnDateRangeFilter' => [
                'FromTxnDate' => '2025-01-01',
                'ToTxnDate' => '2025-12-31'
            ],
            'IncludeLineItems' => true,
            'MaxReturned' => 50
        ]
    ]
];
```

### Item (Product) Query
```php
return [
    [
        'ItemInventoryQueryRq' => [
            'xml_attributes' => ['requestID' => '1'],
            'ActiveStatus' => 'ActiveOnly',
            'FromModifiedDate' => '2025-01-01',
            'MaxReturned' => 100
        ]
    ]
];
```

**QBXML Reference:** https://developer-static.intuit.com/qbSDK-current/Common/newOSR/index.html

---

## 🎓 Learning from the Samples

### CustomerSyncWorker Teaches:
- ✅ Iterator pagination pattern
- ✅ Handling single vs array responses
- ✅ Extracting QB data fields
- ✅ Logging best practices
- ✅ Error handling per item

### InvoiceSyncWorker Teaches:
- ✅ Date range filtering
- ✅ Processing line items
- ✅ Passing job data to workers
- ✅ Conditional execution with time checks
- ✅ Complex response structures

### ProductQueryWorker Teaches:
- ✅ Multiple request types in one job
- ✅ Handling different item types
- ✅ Force flags via job data
- ✅ Day-of-week scheduling
- ✅ QB item type differences

### JobManager Teaches:
- ✅ Service Contract pattern
- ✅ Repository usage
- ✅ Job lifecycle management
- ✅ Data serialization
- ✅ Collection filtering

---

## 🔍 Troubleshooting

### Jobs not appearing in QBWC

**Check:**
1. Job is enabled: `php bin/magento sample:qb:job:list`
2. Company file matches exactly
3. QBWC connected successfully

**Fix:**
```bash
# Enable job
bin/magento sample:qb:job:enable sample_customer_sync
```

### Worker not running (shouldRun returns false)

**Check worker logs:**
```bash
tail -f var/log/quickbooks_demo.log | grep "shouldRun"
```

**Common reasons:**
- InvoiceSyncWorker: Only runs 9 PM - 6 AM
- ProductQueryWorker: Only runs on weekends (unless forced)

**Override:**
```bash
# Force product query
php bin/magento sample:qb:product:query --force
```

### Response data not as expected

**Enable debug logging in worker:**
```php
public function handleResponse(...)
{
    // Log full response
    $this->logDebug('Full response: ' . print_r($response, true));

    // Process...
}
```

### Iterator not continuing

**Check:**
- Response contains `iteratorRemainingCount`
- Session maintains iterator state
- Job doesn't delete itself prematurely

---

## 🎯 Next Steps

### 1. Customize for Your Needs

- Modify workers to actually sync data (not just log)
- Add Magento customer/product creation
- Implement two-way sync (Magento → QB)
- Add error notifications

### 2. Add More Workers

**Suggested:**
- Payment sync (ReceivePayment)
- Sales order export (SalesOrder Add)
- Product price updates (Item Modify)
- Vendor/supplier sync

### 3. Production Readiness

**Checklist:**
- [ ] Add proper error handling and rollback
- [ ] Implement retry logic for failed items
- [ ] Add admin UI for job management
- [ ] Set up monitoring and alerts
- [ ] Add data validation before sync
- [ ] Implement conflict resolution
- [ ] Add comprehensive logging
- [ ] Performance optimization for large datasets
- [ ] Security audit (credentials, QB access)

---

## 📦 Module Structure

```
Sample/QuickbooksDemo/
├── Api/
│   └── JobManagerInterface.php
├── Console/
│   └── Command/
│       ├── CustomerSyncCommand.php
│       ├── InvoiceSyncCommand.php
│       ├── ProductQueryCommand.php
│       ├── JobListCommand.php
│       └── JobCreateCommand.php
├── Logger/
│   ├── Handler.php
│   └── Logger.php
├── Model/
│   ├── JobManager.php
│   └── Worker/
│       ├── CustomerSyncWorker.php
│       ├── InvoiceSyncWorker.php
│       └── ProductQueryWorker.php
├── etc/
│   ├── module.xml
│   └── di.xml
├── registration.php
├── composer.json
└── README.md (this file)
```

---

## 🤝 Contributing

This is a sample/demo module. Feel free to:
- Copy and modify for your needs
- Use as a learning resource
- Share improvements
- Report issues

---

## 📄 License

MIT License - Free to use and modify

---

## 🙏 Credits

- Based on [Rails QBWC gem](https://github.com/skryl/qbwc) by Alex Skryl
- Adapted for Magento 2.4.8 using `Vendor_QuickbooksConnector`
- Sample code for educational purposes

---

## 📞 Support

**Documentation:**
- QuickBooks SDK: https://developer.intuit.com/
- QBXML Reference: https://developer-static.intuit.com/qbSDK-current/Common/newOSR/index.html
- Vendor_QuickbooksConnector: See module documentation

**Logs:**
- Module logs: `var/log/quickbooks_demo.log`
- Connector logs: `var/log/quickbooks_connector.log`
- System logs: `var/log/system.log`

---

**🎉 Happy QuickBooks Integration!**

Last Updated: 2025-11-17
