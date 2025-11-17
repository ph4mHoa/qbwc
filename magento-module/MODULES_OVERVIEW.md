# Magento 2 QuickBooks Integration Modules

This directory contains two Magento 2 modules for QuickBooks Desktop integration via QuickBooks Web Connector (QBWC).

---

## 📦 Modules

### 1. Vendor_QuickbooksConnector (Core Module)

**Purpose:** Core QuickBooks Web Connector integration for Magento 2

**Status:** ✅ Production-ready foundation (Core implementation complete)

**Location:** `Vendor/QuickbooksConnector/`

**What it provides:**
- ✅ SOAP API endpoints for QBWC communication
- ✅ Session management (authenticate, track progress)
- ✅ Job management (create, enable, disable, delete)
- ✅ Worker pattern for extensible sync logic
- ✅ QBXML parser and request builder
- ✅ Database tables: `qbwc_sessions`, `qbwc_jobs`
- ✅ Repository pattern for data access
- ✅ Service Contracts for API stability
- ✅ Callback/hooks system
- ✅ Complete documentation

**Key Files:**
```
Vendor/QuickbooksConnector/
├── Api/                          # Service Contracts
│   ├── QbwcServiceInterface.php  # Main SOAP service (8 endpoints)
│   ├── JobRepositoryInterface.php
│   ├── SessionRepositoryInterface.php
│   └── Data/                     # Data interfaces
├── Model/                        # Business logic
│   ├── QbwcService.php          # SOAP implementation
│   ├── Job.php                  # Job model
│   ├── Session.php              # Session model
│   ├── JobRepository.php
│   ├── SessionRepository.php
│   ├── QbxmlParser.php          # XML parser
│   └── Worker/
│       └── AbstractWorker.php   # Base worker class
├── Controller/
│   └── Qwc/Download.php         # QWC file download
├── etc/
│   ├── module.xml
│   ├── di.xml
│   ├── webapi.xml               # SOAP endpoints
│   └── db_schema.xml            # Database schema
└── README.md                     # Module documentation
```

**Documentation:**
- `Vendor/QuickbooksConnector/README.md`
- `Vendor/QuickbooksConnector/COMPLETE_MODULE_STRUCTURE.md`
- `/docs/magento-module/` (comprehensive docs)

---

### 2. Sample_QuickbooksDemo (Sample/Demo Module)

**Purpose:** Sample implementation showing how to use Vendor_QuickbooksConnector

**Status:** ✅ Complete with 3 working examples

**Location:** `Sample/QuickbooksDemo/`

**What it demonstrates:**
- ✅ **3 Complete Worker Examples:**
  - `CustomerSyncWorker` - Customer synchronization with pagination
  - `InvoiceSyncWorker` - Invoice sync with date filtering
  - `ProductQueryWorker` - Multi-type product queries
- ✅ **JobManager Service** - Simplified job creation and management
- ✅ **5 CLI Commands** - Interactive job creation and management
- ✅ **Logger Integration** - Custom logging to `var/log/quickbooks_demo.log`
- ✅ **Comprehensive Documentation** - README + Quick Start guide

**Key Files:**
```
Sample/QuickbooksDemo/
├── Model/
│   ├── JobManager.php              # Job management service
│   └── Worker/
│       ├── CustomerSyncWorker.php  # Customer sync example
│       ├── InvoiceSyncWorker.php   # Invoice sync example
│       └── ProductQueryWorker.php  # Product query example
├── Console/Command/
│   ├── CustomerSyncCommand.php     # Create customer sync job
│   ├── InvoiceSyncCommand.php      # Create invoice sync job
│   ├── ProductQueryCommand.php     # Create product query job
│   ├── JobListCommand.php          # List jobs
│   └── JobCreateCommand.php        # Interactive job wizard
├── Api/
│   └── JobManagerInterface.php
├── Logger/
│   ├── Handler.php
│   └── Logger.php
└── README.md                        # Full documentation
```

**CLI Commands:**
```bash
# Create jobs
php bin/magento sample:qb:customer:sync
php bin/magento sample:qb:invoice:sync --from=2025-01-01
php bin/magento sample:qb:product:query --force

# Manage jobs
php bin/magento sample:qb:job:list
php bin/magento sample:qb:job:create
```

**Documentation:**
- `Sample/QuickbooksDemo/README.md` - Full documentation
- `Sample/QuickbooksDemo/QUICK_START.md` - 5-minute quick start

---

## 🚀 Quick Start (Both Modules)

### 1. Installation

```bash
# Copy both modules to Magento
cp -r Vendor /path/to/magento/app/code/
cp -r Sample /path/to/magento/app/code/

# Enable modules
php bin/magento module:enable Vendor_QuickbooksConnector Sample_QuickbooksDemo

# Install
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento cache:flush
```

### 2. Verify Installation

```bash
# Check modules enabled
php bin/magento module:status | grep -E "(Vendor_QuickbooksConnector|Sample_QuickbooksDemo)"

# Check database tables created
mysql -u root -p -e "USE magento_db; SHOW TABLES LIKE 'qbwc_%';"

# Should see:
# qbwc_jobs
# qbwc_sessions
```

### 3. Create First Job

```bash
# Interactive wizard
php bin/magento sample:qb:job:create

# Or direct command
php bin/magento sample:qb:customer:sync "C:\QuickBooks\MyCompany.qbw"
```

### 4. Configure QuickBooks Web Connector

1. Download QWC: `https://your-magento.com/qbwc/qwc`
2. Import in QBWC
3. Enter password (configured in module)
4. Run update

### 5. Monitor

```bash
# Watch demo logs
tail -f var/log/quickbooks_demo.log

# Watch connector logs
tail -f var/log/quickbooks_connector.log
```

---

## 📊 Module Dependencies

```
Sample_QuickbooksDemo
    └── depends on → Vendor_QuickbooksConnector
                        └── depends on → Magento Framework
```

**Installation Order:**
1. Vendor_QuickbooksConnector (required)
2. Sample_QuickbooksDemo (optional, for examples)

---

## 🎯 Use Cases

### Use Vendor_QuickbooksConnector when you need:
- Core QBWC integration functionality
- SOAP endpoints for QB communication
- Session and job management
- Base worker pattern
- Production QuickBooks integration

### Use Sample_QuickbooksDemo when you want to:
- Learn how to use Vendor_QuickbooksConnector
- See working examples of Workers
- Get started quickly with templates
- Test QB integration
- Reference implementation patterns

---

## 📚 Architecture

### Request Flow

```
QuickBooks Desktop
    ↓ (SOAP over HTTPS)
QuickBooks Web Connector (QBWC)
    ↓ (SOAP XML)
Magento: Vendor_QuickbooksConnector
    ↓
QbwcService (SOAP endpoint)
    ↓
Session Management
    ↓
Job Repository (fetch pending jobs)
    ↓
Worker::requests() → Generate QBXML
    ↓ (return QBXML to QBWC)
QuickBooks Desktop processes request
    ↓ (return response)
Worker::handleResponse() → Process data
    ↓
Sync to Magento (Customer/Order/Product)
```

### Database Schema

**qbwc_sessions:**
- Tracks QBWC sessions
- Stores progress, current job, pending jobs
- Manages iterator state for pagination

**qbwc_jobs:**
- Job definitions
- Worker class mappings
- Enable/disable status
- Job-specific data

---

## 🧪 Testing

### Unit Tests
```bash
vendor/bin/phpunit -c dev/tests/unit/phpunit.xml.dist \
  app/code/Vendor/QuickbooksConnector/Test/Unit/
```

### Integration Tests
```bash
php bin/magento dev:tests:run integration Vendor_QuickbooksConnector
```

### Manual Testing with Sample Module
```bash
# Create test jobs
php bin/magento sample:qb:job:create

# Run QBWC update
# Check logs
tail -f var/log/quickbooks_demo.log
```

---

## 🔧 Development Workflow

### 1. Study the Samples
```bash
# Read sample workers
cat Sample/QuickbooksDemo/Model/Worker/CustomerSyncWorker.php
cat Sample/QuickbooksDemo/Model/Worker/InvoiceSyncWorker.php
cat Sample/QuickbooksDemo/Model/Worker/ProductQueryWorker.php
```

### 2. Create Your Worker
```php
<?php
namespace YourVendor\YourModule\Model\Worker;

use Vendor\QuickbooksConnector\Model\Worker\AbstractWorker;

class YourWorker extends AbstractWorker
{
    public function requests($job, $session, $data): array
    {
        // Return QBXML requests
    }

    public function handleResponse($response, $session, $job, $request, $data): void
    {
        // Process QB response
    }
}
```

### 3. Create Jobs
```php
// Use JobRepository or create your own JobManager
$job = $this->jobFactory->create();
$job->setName('your_job')
    ->setCompany('C:\\QB\\company.qbw')
    ->setWorkerClass(\YourVendor\YourModule\Model\Worker\YourWorker::class)
    ->setEnabled(true);

$this->jobRepository->save($job);
```

### 4. Test & Deploy
```bash
# Enable module
php bin/magento module:enable YourVendor_YourModule

# Test
php bin/magento setup:upgrade
php bin/magento cache:flush

# Monitor
tail -f var/log/system.log
```

---

## 📖 Documentation Index

### Core Module Documentation
- `Vendor/QuickbooksConnector/README.md` - Main overview
- `Vendor/QuickbooksConnector/COMPLETE_MODULE_STRUCTURE.md` - Implementation guide
- `/docs/magento-module/README.md` - Comprehensive documentation
- `/docs/magento-module/ARCHITECTURE.md` - Architecture deep dive
- `/docs/magento-module/API_DOCUMENTATION.md` - API reference
- `/docs/magento-module/DEVELOPMENT.md` - Development guide
- `/docs/magento-module/TESTCASE.md` - Testing guide

### Sample Module Documentation
- `Sample/QuickbooksDemo/README.md` - Full usage guide
- `Sample/QuickbooksDemo/QUICK_START.md` - 5-minute setup
- Worker source code (heavily commented)

### External References
- QuickBooks SDK: https://developer.intuit.com/
- QBXML Reference: https://developer-static.intuit.com/qbSDK-current/Common/newOSR/index.html
- Rails QBWC gem: https://github.com/skryl/qbwc

---

## 🎓 Learning Path

### For Beginners:
1. ✅ Read `Sample/QuickbooksDemo/QUICK_START.md`
2. ✅ Install both modules
3. ✅ Run sample commands
4. ✅ Read worker code with comments
5. ✅ Modify a worker to suit your needs

### For Advanced Users:
1. ✅ Read `Vendor/QuickbooksConnector/ARCHITECTURE.md`
2. ✅ Study SOAP service implementation
3. ✅ Review test cases
4. ✅ Create custom workers
5. ✅ Extend with custom callbacks/hooks

---

## 🤝 Contributing

### To Core Module (Vendor_QuickbooksConnector)
- Follow Magento coding standards
- Add unit and integration tests
- Update documentation
- Ensure backward compatibility

### To Sample Module (Sample_QuickbooksDemo)
- Add more worker examples
- Improve CLI commands
- Add more use cases
- Enhance documentation

---

## 📄 License

Both modules: MIT License

---

## 🙏 Credits

- Based on [Rails QBWC gem](https://github.com/skryl/qbwc) by Alex Skryl
- Adapted for Magento 2.4.8
- Sample code for educational and production use

---

## 📞 Support

**Issues:**
- Check module logs: `var/log/quickbooks_*.log`
- Review documentation above
- Check QuickBooks SDK docs

**Resources:**
- Module documentation (see Documentation Index above)
- QuickBooks Developer Portal
- Magento DevDocs

---

**Last Updated:** 2025-11-17

**Status:**
- ✅ Vendor_QuickbooksConnector: Production-ready core
- ✅ Sample_QuickbooksDemo: Complete with examples

**Ready for:** Development, Testing, Production deployment
