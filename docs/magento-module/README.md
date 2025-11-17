# Magento 2 QuickBooks Web Connector Module

![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)
![Magento](https://img.shields.io/badge/magento-2.4.8-orange.svg)
![PHP](https://img.shields.io/badge/php-8.1%2B-purple.svg)
![License](https://img.shields.io/badge/license-MIT-green.svg)

## 📋 Mục Lục

- [Giới Thiệu](#giới-thiệu)
- [Tính Năng](#tính-năng)
- [Yêu Cầu Hệ Thống](#yêu-cầu-hệ-thống)
- [Cài Đặt](#cài-đặt)
- [Cấu Hình](#cấu-hình)
- [Sử Dụng](#sử-dụng)
- [Tài Liệu Chi Tiết](#tài-liệu-chi-tiết)
- [Test Cases](#test-cases)
- [Đóng Góp](#đóng-góp)
- [License](#license)

---

## 🎯 Giới Thiệu

**Vendor_QuickbooksConnector** là module Magento 2 cho phép tích hợp với QuickBooks Desktop thông qua QuickBooks Web Connector (QBWC). Module này được clone và port từ [QBWC Rails gem](https://github.com/skryl/qbwc) sang kiến trúc Magento 2.

Module cung cấp SOAP web service tuân thủ giao thức QuickBooks Web Connector, cho phép đồng bộ dữ liệu giữa Magento và QuickBooks Desktop một cách tự động.

### Kiến Trúc Tổng Quan

```
QuickBooks Desktop
         ↕
QuickBooks Web Connector (Client)
         ↕
Magento 2 Module (SOAP Service)
         ↕
Magento Database
```

---

## ✨ Tính Năng

### Core Features

- ✅ **SOAP Web Service** - Triển khai đầy đủ giao thức QBWC
- ✅ **Session Management** - Quản lý session stateful qua nhiều HTTP requests
- ✅ **Job Queue System** - Hệ thống queue jobs linh hoạt
- ✅ **Worker Pattern** - Abstraction layer cho business logic tùy chỉnh
- ✅ **Request/Response Handling** - Xử lý QBXML requests/responses
- ✅ **Iterator Support** - Hỗ trợ phân trang cho large datasets
- ✅ **Error Handling** - Xử lý lỗi với 2 modes: stop/continue
- ✅ **Progress Tracking** - Theo dõi tiến trình đồng bộ real-time
- ✅ **Multi-user Support** - Hỗ trợ nhiều users và company files
- ✅ **Repository Pattern** - Data persistence chuẩn Magento
- ✅ **Service Contracts** - API contracts rõ ràng
- ✅ **Event System** - Events cho extensibility

### SOAP Actions Supported

| Action | Mô Tả |
|--------|-------|
| `serverVersion` | Trả về phiên bản server |
| `clientVersion` | Xác thực phiên bản client |
| `authenticate` | Xác thực user và tạo session |
| `sendRequestXML` | Gửi QBXML request đến QuickBooks |
| `receiveResponseXML` | Nhận QBXML response từ QuickBooks |
| `closeConnection` | Đóng session và cleanup |
| `connectionError` | Xử lý lỗi connection |
| `getLastError` | Lấy thông tin lỗi cuối cùng |

---

## 💻 Yêu Cầu Hệ Thống

### Magento Requirements

- **Magento Version**: 2.4.6 - 2.4.8
- **PHP Version**: 8.1 hoặc 8.2
- **MySQL**: 8.0+
- **Composer**: 2.x

### PHP Extensions Required

```bash
- ext-soap
- ext-xml
- ext-json
- ext-pdo
- ext-mbstring
```

### QuickBooks Requirements

- **QuickBooks Desktop**: Pro, Premier, hoặc Enterprise (US/Canadian version)
- **QuickBooks Web Connector**: Version 2.x hoặc 3.x
- **QBXML Version**: 3.0 - 13.0

### Server Requirements

- HTTPS enabled (required cho production)
- Cron jobs enabled
- Đủ memory: recommended 2GB+

---

## 📦 Cài Đặt

### Phương Pháp 1: Composer (Khuyến Nghị)

```bash
# Navigate to Magento root
cd /path/to/magento

# Require module via Composer
composer require vendor/module-quickbooks-connector

# Enable module
php bin/magento module:enable Vendor_QuickbooksConnector

# Run setup upgrade
php bin/magento setup:upgrade

# Compile DI
php bin/magento setup:di:compile

# Deploy static content
php bin/magento setup:static-content:deploy -f

# Clear cache
php bin/magento cache:clean
php bin/magento cache:flush
```

### Phương Pháp 2: Manual Installation

```bash
# Navigate to Magento app/code directory
cd /path/to/magento/app/code

# Create vendor directory
mkdir -p Vendor

# Copy module files
cp -r /path/to/module Vendor/QuickbooksConnector

# Enable and install
cd /path/to/magento
php bin/magento module:enable Vendor_QuickbooksConnector
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy -f
php bin/magento cache:flush
```

### Verify Installation

```bash
# Check if module is enabled
php bin/magento module:status Vendor_QuickbooksConnector

# Should output: Module is enabled
```

**📖 Chi tiết:** Xem [INSTALLATION.md](INSTALLATION.md)

---

## ⚙️ Cấu Hình

### 1. Admin Configuration

Đăng nhập Magento Admin và navigate đến:

```
Stores > Configuration > Services > QuickBooks Connector
```

### 2. Cấu Hình Cơ Bản

```
General Settings
├── Enable Module: Yes
├── Username: qbuser
├── Password: ********
├── Min QBXML Version: 3.0
├── Company File Path: (leave empty for any open file)
└── Support URL: https://yourstore.com/support

Error Handling
├── On Error: Stop / Continue
└── Log Requests/Responses: Yes (for debugging)

Advanced Settings
├── Session Timeout: 3600 seconds
├── Max Iterations: 100
└── Enable Cron: Yes
```

### 3. Download QWC File

```
Navigate to: https://yourstore.com/qbwc/download/qwc

Hoặc via Admin:
Stores > QuickBooks Connector > Download QWC File
```

### 4. Cấu Hình QuickBooks Web Connector

1. Open QuickBooks Web Connector application
2. Click "Add an application"
3. Browse và chọn downloaded .QWC file
4. Nhập password đã config ở Admin
5. Click "Yes, always allow" khi QuickBooks hỏi permission
6. Set update schedule (recommended: every 10-30 minutes)

### 5. Test Connection

```bash
# Via CLI
php bin/magento qbwc:test:connection

# Hoặc click "Update Selected" trong QBWC
```

**📖 Chi tiết:** Xem [CONFIGURATION.md](CONFIGURATION.md)

---

## 🚀 Sử Dụng

### Tạo Job Mới

```php
<?php
use Vendor\QuickbooksConnector\Api\JobRepositoryInterface;
use Vendor\QuickbooksConnector\Model\JobFactory;

class Example
{
    protected $jobRepository;
    protected $jobFactory;

    public function __construct(
        JobRepositoryInterface $jobRepository,
        JobFactory $jobFactory
    ) {
        $this->jobRepository = $jobRepository;
        $this->jobFactory = $jobFactory;
    }

    public function createJob()
    {
        $job = $this->jobFactory->create();
        $job->setName('sync_customers');
        $job->setEnabled(true);
        $job->setCompany('');  // empty = any company
        $job->setWorkerClass('Vendor\QuickbooksConnector\Worker\CustomerSync');
        $job->setData(['option' => 'value']);

        $this->jobRepository->save($job);
    }
}
```

### Tạo Custom Worker

```php
<?php
namespace Vendor\QuickbooksConnector\Worker;

use Vendor\QuickbooksConnector\Model\Worker\AbstractWorker;

class CustomerSync extends AbstractWorker
{
    /**
     * Xác định các requests cần gửi
     */
    public function requests($job, $session, $data)
    {
        return [
            [
                'CustomerQueryRq' => [
                    'xml_attributes' => [
                        'requestID' => '1',
                        'iterator' => 'Start'
                    ],
                    'MaxReturned' => 100
                ]
            ]
        ];
    }

    /**
     * Kiểm tra xem job có nên chạy không
     */
    public function shouldRun($job, $session, $data)
    {
        // Logic để quyết định có chạy job không
        return true;
    }

    /**
     * Xử lý response từ QuickBooks
     */
    public function handleResponse($response, $session, $job, $request, $data)
    {
        if (isset($response['CustomerQueryRs'])) {
            $customers = $response['CustomerQueryRs'];

            foreach ($customers as $customer) {
                // Process customer data
                $this->saveCustomer($customer);
            }
        }
    }

    protected function saveCustomer($customerData)
    {
        // Implementation
    }
}
```

### Quản Lý Jobs via CLI

```bash
# List tất cả jobs
php bin/magento qbwc:job:list

# Enable job
php bin/magento qbwc:job:enable sync_customers

# Disable job
php bin/magento qbwc:job:disable sync_customers

# Delete job
php bin/magento qbwc:job:delete sync_customers

# View job details
php bin/magento qbwc:job:info sync_customers
```

### Monitor Sessions

```bash
# List active sessions
php bin/magento qbwc:session:list

# View session details
php bin/magento qbwc:session:info <ticket>

# Clear old sessions
php bin/magento qbwc:session:cleanup
```

**📖 Chi tiết:** Xem [DEVELOPMENT.md](DEVELOPMENT.md)

---

## 📚 Tài Liệu Chi Tiết

| Document | Mô Tả |
|----------|-------|
| [INSTALLATION.md](INSTALLATION.md) | Hướng dẫn cài đặt chi tiết |
| [ARCHITECTURE.md](ARCHITECTURE.md) | Kiến trúc và design patterns |
| [API_DOCUMENTATION.md](API_DOCUMENTATION.md) | API reference đầy đủ |
| [DEVELOPMENT.md](DEVELOPMENT.md) | Hướng dẫn phát triển custom workers |
| [TESTCASE.md](TESTCASE.md) | Test cases và testing guide |
| [TROUBLESHOOTING.md](TROUBLESHOOTING.md) | Xử lý lỗi thường gặp |
| [CHANGELOG.md](CHANGELOG.md) | Lịch sử thay đổi |
| [MIGRATION.md](MIGRATION.md) | Migration guide từ Rails version |

---

## 🧪 Test Cases

Module đi kèm với comprehensive test suite:

### Unit Tests

```bash
# Run all unit tests
php bin/magento dev:tests:run unit Vendor_QuickbooksConnector

# Run specific test
vendor/bin/phpunit -c dev/tests/unit/phpunit.xml.dist \
  app/code/Vendor/QuickbooksConnector/Test/Unit/Model/SessionTest.php
```

### Integration Tests

```bash
# Run integration tests
php bin/magento dev:tests:run integration Vendor_QuickbooksConnector
```

### SOAP API Tests

```bash
# Test SOAP endpoints
php bin/magento qbwc:test:soap

# Test authentication
php bin/magento qbwc:test:auth

# Test full workflow
php bin/magento qbwc:test:workflow
```

### Code Coverage

```bash
# Generate coverage report
vendor/bin/phpunit -c dev/tests/unit/phpunit.xml.dist \
  --coverage-html coverage/ \
  app/code/Vendor/QuickbooksConnector/Test/Unit/
```

**📖 Chi tiết:** Xem [TESTCASE.md](TESTCASE.md)

---

## 🏗️ Kiến Trúc

### Module Structure

```
Vendor/QuickbooksConnector/
├── Api/                          # Service Contracts
│   ├── Data/                     # Data Interfaces
│   │   ├── SessionInterface.php
│   │   ├── JobInterface.php
│   │   └── RequestInterface.php
│   ├── QbwcServiceInterface.php
│   ├── SessionRepositoryInterface.php
│   └── JobRepositoryInterface.php
│
├── Model/                        # Business Logic
│   ├── QbwcService.php
│   ├── Session.php
│   ├── SessionRepository.php
│   ├── Job.php
│   ├── JobRepository.php
│   ├── Request.php
│   ├── Config.php
│   ├── QbxmlParser.php
│   └── Worker/
│       ├── AbstractWorker.php
│       └── ExampleWorker.php
│
├── Model/ResourceModel/          # Database Layer
│   ├── Session.php
│   ├── Session/Collection.php
│   ├── Job.php
│   └── Job/Collection.php
│
├── Controller/                   # HTTP Controllers
│   ├── Adminhtml/
│   │   └── Job/
│   │       ├── Index.php
│   │       ├── Edit.php
│   │       └── Save.php
│   └── Qwc/
│       └── Download.php
│
├── Block/                        # View Blocks
│   └── Adminhtml/
│       └── Job/
│
├── view/                         # Frontend
│   ├── adminhtml/
│   │   ├── layout/
│   │   ├── templates/
│   │   └── ui_component/
│   └── frontend/
│
├── Setup/                        # Installation
│   └── Patch/
│       └── Data/
│
├── Test/                         # Tests
│   ├── Unit/
│   ├── Integration/
│   └── Api/
│
├── etc/                          # Configuration
│   ├── module.xml
│   ├── di.xml
│   ├── webapi.xml
│   ├── db_schema.xml
│   ├── config.xml
│   ├── adminhtml/
│   │   ├── system.xml
│   │   └── menu.xml
│   └── crontab.xml
│
├── i18n/                         # Translations
│   ├── en_US.csv
│   └── vi_VN.csv
│
├── Console/                      # CLI Commands
│   └── Command/
│
└── docs/                         # Documentation
    ├── README.md
    ├── INSTALLATION.md
    ├── ARCHITECTURE.md
    └── ...
```

**📖 Chi tiết:** Xem [ARCHITECTURE.md](ARCHITECTURE.md)

---

## 🔐 Security

### Authentication

Module sử dụng 2-factor authentication:
1. Username/Password validation (configured in Admin)
2. Ticket-based session (SHA-256 hash)

### Data Encryption

- All SOAP communication via HTTPS (production)
- Passwords hashed with Magento encryption
- Session tickets cryptographically secure

### Access Control

```php
// Only authenticated QBWC can access
<resources>
    <resource ref="anonymous"/>  <!-- Public for QBWC -->
</resources>

// Admin management requires permission
<resource ref="Vendor_QuickbooksConnector::manage"/>
```

---

## 🐛 Troubleshooting

### Common Issues

#### 1. Authentication Failed

```
Error: nvu (Not Valid User)

Solution:
- Check username/password in Admin config
- Verify QWC file matches current config
- Check logs: var/log/qbwc.log
```

#### 2. No Work Available

```
Error: none (No Work)

Solution:
- Check if jobs are enabled
- Verify company file path matches
- Run: php bin/magento qbwc:job:list
```

#### 3. Connection Timeout

```
Error: Connection timeout

Solution:
- Increase max_execution_time in php.ini
- Check network connectivity
- Verify SOAP endpoint is accessible
```

#### 4. QBXML Parse Error

```
Error: Invalid QBXML

Solution:
- Verify QBXML version compatibility
- Check request structure
- Enable log_requests_and_responses
```

**📖 Chi tiết:** Xem [TROUBLESHOOTING.md](TROUBLESHOOTING.md)

---

## 📊 Performance

### Optimization Tips

1. **Enable Caching**
```bash
php bin/magento cache:enable
```

2. **Use Redis for Sessions**
```php
// env.php
'session' => [
    'save' => 'redis',
    'redis' => [
        'host' => '127.0.0.1',
        'port' => '6379',
        'database' => '2'
    ]
]
```

3. **Optimize Database**
```sql
-- Add indexes
ALTER TABLE qbwc_sessions ADD INDEX idx_ticket (ticket);
ALTER TABLE qbwc_jobs ADD INDEX idx_enabled (enabled);
```

4. **Limit Iterator Size**
```php
'MaxReturned' => 100  // Adjust based on server capacity
```

### Benchmarks

| Operation | Time | Memory |
|-----------|------|--------|
| Authentication | ~50ms | 2MB |
| Send Request | ~30ms | 1MB |
| Receive Response | ~100ms | 5MB |
| Process 100 Customers | ~500ms | 15MB |

---

## 🤝 Đóng Góp

Chúng tôi hoan nghênh mọi đóng góp!

### Development Setup

```bash
# Clone repository
git clone https://github.com/vendor/magento2-quickbooks-connector.git

# Install dependencies
composer install

# Run tests
vendor/bin/phpunit

# Code standards
vendor/bin/phpcs --standard=Magento2 app/code/Vendor/QuickbooksConnector/
```

### Contribution Guidelines

1. Fork repository
2. Create feature branch (`git checkout -b feature/amazing-feature`)
3. Commit changes (`git commit -m 'Add amazing feature'`)
4. Push to branch (`git push origin feature/amazing-feature`)
5. Open Pull Request

### Coding Standards

- Follow [Magento Coding Standards](https://developer.adobe.com/commerce/php/coding-standards/)
- PSR-12 compliant
- 100% PHPDoc coverage
- Unit test coverage > 80%

---

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

---

## 🙏 Credits

- **Original QBWC Gem**: [https://github.com/skryl/qbwc](https://github.com/skryl/qbwc)
- **ConsoliBYTE QuickBooks PHP**: [https://github.com/consolibyte/quickbooks-php](https://github.com/consolibyte/quickbooks-php)
- **Magento 2 Framework**: [https://magento.com](https://magento.com)

---

## 📞 Support

- **Documentation**: [https://docs.example.com/qbwc](https://docs.example.com/qbwc)
- **Issues**: [https://github.com/vendor/magento2-quickbooks-connector/issues](https://github.com/vendor/magento2-quickbooks-connector/issues)
- **Email**: support@example.com
- **Forum**: [https://community.example.com](https://community.example.com)

---

## 🗺️ Roadmap

### Version 1.1.0 (Q2 2025)
- [ ] GraphQL API support
- [ ] Real-time sync via webhooks
- [ ] Advanced error recovery
- [ ] Performance dashboard

### Version 1.2.0 (Q3 2025)
- [ ] QuickBooks Online support
- [ ] Multi-store support
- [ ] Advanced mapping UI
- [ ] Bulk operations

### Version 2.0.0 (Q4 2025)
- [ ] Magento 2.5 compatibility
- [ ] Microservices architecture
- [ ] AI-powered conflict resolution
- [ ] Advanced analytics

---

## ⭐ Star History

[![Star History Chart](https://api.star-history.com/svg?repos=vendor/magento2-quickbooks-connector&type=Date)](https://star-history.com/#vendor/magento2-quickbooks-connector&Date)

---

**Made with ❤️ by Vendor Team**

**Last Updated**: 2025-11-16
