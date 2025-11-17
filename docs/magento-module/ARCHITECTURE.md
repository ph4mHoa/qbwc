# Architecture Documentation

## 📋 Mục Lục

- [Overview](#overview)
- [System Architecture](#system-architecture)
- [Design Patterns](#design-patterns)
- [Component Details](#component-details)
- [Data Flow](#data-flow)
- [Database Schema](#database-schema)
- [Extension Points](#extension-points)
- [Performance Considerations](#performance-considerations)

---

## 🎯 Overview

**Vendor_QuickbooksConnector** được thiết kế theo kiến trúc Magento 2 chuẩn, áp dụng Service Contract pattern và tuân thủ SOLID principles.

### Core Principles

1. **Separation of Concerns** - Tách biệt business logic, data access, presentation
2. **Dependency Injection** - Loose coupling, dễ test
3. **Service Contracts** - API stability
4. **Repository Pattern** - Data abstraction
5. **Observer Pattern** - Extensibility

---

## 🏗️ System Architecture

### High-Level Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                  QuickBooks Desktop                         │
│                                                             │
│  ┌─────────────────────────────────────────────────────┐  │
│  │         QuickBooks Web Connector (Client)           │  │
│  └────────────────┬────────────────────────────────────┘  │
└───────────────────┼────────────────────────────────────────┘
                    │ SOAP/HTTPS
                    ▼
┌─────────────────────────────────────────────────────────────┐
│               Magento 2 Application Layer                   │
│                                                             │
│  ┌──────────────────────────────────────────────────────┐  │
│  │             SOAP Service Layer                       │  │
│  │  (QbwcServiceInterface Implementation)               │  │
│  └────────────────┬─────────────────────────────────────┘  │
│                   │                                         │
│  ┌────────────────▼─────────────────────────────────────┐  │
│  │           Business Logic Layer                       │  │
│  │  ┌──────────────┬──────────────┬──────────────┐     │  │
│  │  │   Session    │     Job      │   Worker     │     │  │
│  │  │  Management  │  Management  │   Pattern    │     │  │
│  │  └──────────────┴──────────────┴──────────────┘     │  │
│  └────────────────┬─────────────────────────────────────┘  │
│                   │                                         │
│  ┌────────────────▼─────────────────────────────────────┐  │
│  │         Data Access Layer (Repositories)             │  │
│  └────────────────┬─────────────────────────────────────┘  │
│                   │                                         │
│  ┌────────────────▼─────────────────────────────────────┐  │
│  │         Persistence Layer (Models/Resources)         │  │
│  └────────────────┬─────────────────────────────────────┘  │
└───────────────────┼─────────────────────────────────────────┘
                    │
                    ▼
┌─────────────────────────────────────────────────────────────┐
│                   MySQL Database                            │
│  ┌──────────────────┬──────────────────────────────────┐  │
│  │  qbwc_sessions  │       qbwc_jobs                  │  │
│  └──────────────────┴──────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
```

### Layer Responsibilities

| Layer | Responsibility | Key Components |
|-------|---------------|----------------|
| **Service Contract** | API definition, version stability | Interfaces in Api/ |
| **Business Logic** | Core functionality | Models/ classes |
| **Data Access** | CRUD operations | Repository classes |
| **Persistence** | Database operations | ResourceModel/ classes |
| **Presentation** | Admin UI, CLI | Block/, Controller/, Console/ |

---

## 🎨 Design Patterns

### 1. Service Contract Pattern

**Purpose**: Định nghĩa API contracts rõ ràng, stable, versionable

**Implementation**:

```php
// Api/QbwcServiceInterface.php - Service Interface
interface QbwcServiceInterface
{
    /**
     * @param string $strUserName
     * @param string $strPassword
     * @return string[]
     */
    public function authenticate($strUserName, $strPassword);
}

// Model/QbwcService.php - Implementation
class QbwcService implements QbwcServiceInterface
{
    public function authenticate($strUserName, $strPassword)
    {
        // Implementation
    }
}
```

**Benefits**:
- API versioning
- Backward compatibility
- Clear contracts
- Easy mocking for tests

---

### 2. Repository Pattern

**Purpose**: Abstraction layer giữa business logic và data access

**Implementation**:

```php
// Api/SessionRepositoryInterface.php
interface SessionRepositoryInterface
{
    public function save(SessionInterface $session);
    public function getById($id);
    public function getByTicket($ticket);
    public function delete(SessionInterface $session);
}

// Model/SessionRepository.php
class SessionRepository implements SessionRepositoryInterface
{
    private $sessionFactory;
    private $sessionResource;

    public function save(SessionInterface $session)
    {
        $this->sessionResource->save($session);
        return $session;
    }

    public function getById($id)
    {
        $session = $this->sessionFactory->create();
        $this->sessionResource->load($session, $id);
        if (!$session->getId()) {
            throw new NoSuchEntityException(__('Session not found'));
        }
        return $session;
    }
}
```

**Benefits**:
- Testability (mock repositories)
- Flexibility (swap implementations)
- Separation of concerns
- Magento best practice

---

### 3. Dependency Injection Pattern

**Purpose**: Loose coupling, easier testing

**Configuration**: `etc/di.xml`

```xml
<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <!-- Preference: Interface -> Implementation -->
    <preference for="Vendor\QuickbooksConnector\Api\QbwcServiceInterface"
                type="Vendor\QuickbooksConnector\Model\QbwcService"/>

    <preference for="Vendor\QuickbooksConnector\Api\SessionRepositoryInterface"
                type="Vendor\QuickbooksConnector\Model\SessionRepository"/>

    <!-- Virtual Type for different configs -->
    <virtualType name="QbwcLogger" type="Monolog\Logger">
        <arguments>
            <argument name="name" xsi:type="string">qbwc</argument>
        </arguments>
    </virtualType>

    <!-- Constructor injection -->
    <type name="Vendor\QuickbooksConnector\Model\QbwcService">
        <arguments>
            <argument name="logger" xsi:type="object">QbwcLogger</argument>
        </arguments>
    </type>
</config>
```

**Usage**:

```php
class QbwcService
{
    public function __construct(
        SessionRepositoryInterface $sessionRepository,  // Interface, not concrete
        JobRepositoryInterface $jobRepository,
        LoggerInterface $logger
    ) {
        $this->sessionRepository = $sessionRepository;
        $this->jobRepository = $jobRepository;
        $this->logger = $logger;
    }
}
```

---

### 4. Factory Pattern

**Purpose**: Object creation abstraction

**Auto-generated Factories**:

```php
// Magento auto-generates SessionFactory
$session = $this->sessionFactory->create();
$session->setTicket('abc');
```

**Custom Factories**:

```php
class WorkerFactory
{
    public function create($workerClassName)
    {
        $objectManager = ObjectManager::getInstance();
        $worker = $objectManager->create($workerClassName);

        if (!$worker instanceof AbstractWorker) {
            throw new \InvalidArgumentException('Invalid worker class');
        }

        return $worker;
    }
}
```

---

### 5. Strategy Pattern (Worker Pattern)

**Purpose**: Pluggable business logic

**Abstract Worker**:

```php
abstract class AbstractWorker implements WorkerInterface
{
    /**
     * Generate QBXML requests
     */
    abstract public function requests($job, $session, $data);

    /**
     * Decide if job should run
     */
    public function shouldRun($job, $session, $data)
    {
        return true;
    }

    /**
     * Handle QuickBooks response
     */
    abstract public function handleResponse($response, $session, $job, $request, $data);
}
```

**Concrete Worker**:

```php
class CustomerSyncWorker extends AbstractWorker
{
    public function requests($job, $session, $data)
    {
        return [
            [
                'CustomerQueryRq' => [
                    'xml_attributes' => ['requestID' => '1'],
                    'MaxReturned' => 100
                ]
            ]
        ];
    }

    public function handleResponse($response, $session, $job, $request, $data)
    {
        // Process customer data
    }
}
```

---

### 6. Observer Pattern (Event System)

**Purpose**: Extensibility without modifying core code

**Dispatch Events**:

```php
// In QbwcService.php
public function authenticate($username, $password)
{
    // ... authentication logic ...

    $this->eventManager->dispatch(
        'qbwc_session_authenticated',
        ['session' => $session, 'username' => $username]
    );

    return [$ticket, $companyFile];
}
```

**Observer Registration**: `etc/events.xml`

```xml
<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <event name="qbwc_session_authenticated">
        <observer name="log_authentication"
                  instance="Vendor\CustomModule\Observer\LogAuthentication"/>
    </event>

    <event name="qbwc_job_completed">
        <observer name="notify_completion"
                  instance="Vendor\CustomModule\Observer\NotifyJobCompletion"/>
    </event>
</config>
```

**Observer Implementation**:

```php
class LogAuthentication implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        $session = $observer->getData('session');
        $this->logger->info('User authenticated: ' . $session->getUser());
    }
}
```

---

### 7. Singleton Pattern (Configuration)

**Purpose**: Single instance of configuration

```php
class Config
{
    private static $instance = null;

    private function __construct() {
        // Private constructor
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}
```

**Note**: Magento DI handles singletons automatically via `shared="true"` in di.xml

---

## 📦 Component Details

### 1. Service Layer (Api/)

#### Structure:

```
Api/
├── QbwcServiceInterface.php          # Main SOAP service
├── SessionRepositoryInterface.php    # Session CRUD
├── JobRepositoryInterface.php        # Job CRUD
├── WorkerInterface.php               # Worker contract
│
└── Data/                             # Data Interfaces
    ├── SessionInterface.php
    ├── JobInterface.php
    └── RequestInterface.php
```

#### Responsibility:

- Define API contracts
- Ensure backward compatibility
- Provide clear documentation via PHPDoc
- Support versioning (V1, V2, etc.)

---

### 2. Business Logic Layer (Model/)

#### Structure:

```
Model/
├── QbwcService.php                   # Main service implementation
├── Session.php                       # Session entity & logic
├── SessionRepository.php
├── Job.php                           # Job entity & logic
├── JobRepository.php
├── Request.php                       # QBXML request wrapper
├── QbxmlParser.php                   # XML parser
├── Config.php                        # Configuration management
│
└── Worker/
    ├── AbstractWorker.php            # Base worker class
    ├── CustomerWorker.php            # Example workers
    └── InvoiceWorker.php
```

#### Key Classes:

**QbwcService.php** - Orchestrates SOAP workflow

```php
class QbwcService implements QbwcServiceInterface
{
    public function authenticate($username, $password)
    {
        // 1. Validate credentials
        // 2. Create session
        // 3. Check pending jobs
        // 4. Return ticket or error code
    }

    public function sendRequest($ticket, ...)
    {
        // 1. Load session by ticket
        // 2. Get current job
        // 3. Generate QBXML request
        // 4. Return QBXML string
    }

    public function receiveResponse($ticket, $response, ...)
    {
        // 1. Load session
        // 2. Parse QBXML response
        // 3. Call worker handler
        // 4. Update progress
        // 5. Return progress percentage
    }
}
```

**Session.php** - Session state management

```php
class Session extends AbstractModel implements SessionInterface
{
    // State tracking
    private $pendingJobs = [];
    private $currentJob = null;
    private $progress = 0;
    private $iteratorId = null;

    public function calculateProgress()
    {
        $completed = $this->initialJobCount - count($this->pendingJobs);
        $this->progress = ($completed / $this->initialJobCount) * 100;
    }

    public function nextJob()
    {
        if (empty($this->pendingJobs)) {
            return null;
        }
        return array_shift($this->pendingJobs);
    }
}
```

**Job.php** - Job management

```php
class Job extends AbstractModel implements JobInterface
{
    public function getNextRequest($session)
    {
        $requests = $this->getRequestsForSession($session);
        $index = $this->getRequestIndex($session);

        if ($index >= count($requests)) {
            return null; // Job complete
        }

        return $requests[$index];
    }

    public function processResponse($response, $session)
    {
        // Parse response
        $parsed = $this->parser->parse($response);

        // Get worker
        $worker = $this->workerFactory->create($this->workerClass);

        // Handle response
        $worker->handleResponse($parsed, $session, $this, ...);

        // Advance index
        $this->advanceRequestIndex($session);
    }
}
```

---

### 3. Data Access Layer (Repositories)

**SessionRepository.php**:

```php
class SessionRepository implements SessionRepositoryInterface
{
    private $sessionFactory;
    private $sessionResource;
    private $collectionFactory;
    private $searchResultsFactory;

    public function save(SessionInterface $session)
    {
        try {
            $this->sessionResource->save($session);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__($e->getMessage()));
        }
        return $session;
    }

    public function getByTicket($ticket)
    {
        $session = $this->sessionFactory->create();
        $this->sessionResource->load($session, $ticket, 'ticket');

        if (!$session->getId()) {
            throw new NoSuchEntityException(__('Session not found'));
        }

        return $session;
    }

    public function getList(SearchCriteriaInterface $criteria)
    {
        $collection = $this->collectionFactory->create();

        // Apply filters
        foreach ($criteria->getFilterGroups() as $filterGroup) {
            // ... apply filters ...
        }

        // Apply sorting
        // Apply pagination

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($collection->getSize());

        return $searchResults;
    }
}
```

---

### 4. Persistence Layer (ResourceModel/)

**Session.php (ResourceModel)**:

```php
namespace Vendor\QuickbooksConnector\Model\ResourceModel;

class Session extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('qbwc_sessions', 'entity_id');
    }

    /**
     * Custom load by ticket
     */
    public function loadByTicket($object, $ticket)
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable())
            ->where('ticket = :ticket');

        $bind = ['ticket' => $ticket];
        $data = $connection->fetchRow($select, $bind);

        if ($data) {
            $object->setData($data);
        }

        $this->unserializeFields($object);

        return $this;
    }

    /**
     * Before save processing
     */
    protected function _beforeSave(AbstractModel $object)
    {
        // Serialize complex fields
        if ($object->getData('pending_jobs') && is_array($object->getData('pending_jobs'))) {
            $object->setData(
                'pending_jobs',
                $this->serializer->serialize($object->getData('pending_jobs'))
            );
        }

        return parent::_beforeSave($object);
    }

    /**
     * After load processing
     */
    protected function _afterLoad(AbstractModel $object)
    {
        $this->unserializeFields($object);
        return parent::_afterLoad($object);
    }

    protected function unserializeFields($object)
    {
        $value = $object->getData('pending_jobs');
        if ($value && is_string($value)) {
            $object->setData(
                'pending_jobs',
                $this->serializer->unserialize($value)
            );
        }
    }
}
```

---

## 🔄 Data Flow

### Authentication Flow

```
1. QBWC Client
   │
   ├─► authenticate(username, password)
   │
   ▼
2. QbwcService::authenticate()
   │
   ├─► Config::validateCredentials()
   │
   ├─► SessionFactory::create()
   │
   ├─► Session::generateTicket()
   │
   ├─► JobRepository::getPendingJobs()
   │
   ├─► SessionRepository::save()
   │
   └─► Return [ticket, companyFile]
```

### Request/Response Flow

```
1. QBWC Client
   │
   ├─► sendRequestXML(ticket)
   │
   ▼
2. QbwcService::sendRequest()
   │
   ├─► SessionRepository::getByTicket()
   │
   ├─► Session::getCurrentJob()
   │
   ├─► Job::getNextRequest()
   │
   ├─► Worker::requests()
   │
   ├─► Request::toQbxml()
   │
   └─► Return QBXML string
   │
   ▼
3. QuickBooks processes request
   │
   ▼
4. QBWC Client
   │
   ├─► receiveResponseXML(ticket, response)
   │
   ▼
5. QbwcService::receiveResponse()
   │
   ├─► SessionRepository::getByTicket()
   │
   ├─► QbxmlParser::parse(response)
   │
   ├─► Session::parseHeaders()
   │
   ├─► Job::processResponse()
   │
   ├─► Worker::handleResponse()
   │
   ├─► Session::calculateProgress()
   │
   ├─► SessionRepository::save()
   │
   └─► Return progress (0-100)
```

### Close Connection Flow

```
1. QBWC Client
   │
   ├─► closeConnection(ticket)
   │
   ▼
2. QbwcService::closeConnection()
   │
   ├─► SessionRepository::getByTicket()
   │
   ├─► EventManager::dispatch('qbwc_session_completed')
   │
   ├─► SessionRepository::delete()
   │
   └─► Return 'OK'
```

---

## 🗄️ Database Schema

### qbwc_sessions Table

```sql
CREATE TABLE `qbwc_sessions` (
  `entity_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ticket` varchar(255) NOT NULL COMMENT 'Session Ticket',
  `user` varchar(255) NOT NULL COMMENT 'Username',
  `company` varchar(1000) NOT NULL COMMENT 'Company File Path',
  `progress` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Progress %',
  `current_job` varchar(255) DEFAULT NULL COMMENT 'Current Job Name',
  `pending_jobs` text DEFAULT NULL COMMENT 'Pending Jobs JSON',
  `iterator_id` varchar(255) DEFAULT NULL COMMENT 'Iterator ID',
  `error` text DEFAULT NULL COMMENT 'Error Message',
  `status_code` varchar(50) DEFAULT NULL COMMENT 'Status Code',
  `status_severity` varchar(50) DEFAULT NULL COMMENT 'Severity',
  `initial_job_count` int(10) unsigned DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`entity_id`),
  UNIQUE KEY `QBWC_SESSIONS_TICKET` (`ticket`),
  KEY `QBWC_SESSIONS_USER` (`user`),
  KEY `QBWC_SESSIONS_COMPANY` (`company`(255)),
  KEY `QBWC_SESSIONS_CREATED` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### qbwc_jobs Table

```sql
CREATE TABLE `qbwc_jobs` (
  `entity_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL COMMENT 'Job Name',
  `company` varchar(1000) NOT NULL COMMENT 'Company File Path',
  `worker_class` varchar(255) NOT NULL COMMENT 'Worker Class Name',
  `enabled` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Is Enabled',
  `request_index` text DEFAULT NULL COMMENT 'Request Index JSON',
  `requests` text DEFAULT NULL COMMENT 'Requests JSON',
  `requests_provided_when_job_added` tinyint(1) NOT NULL DEFAULT 0,
  `data` text DEFAULT NULL COMMENT 'Job Data JSON',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`entity_id`),
  UNIQUE KEY `QBWC_JOBS_NAME` (`name`),
  KEY `QBWC_JOBS_COMPANY` (`company`(255)),
  KEY `QBWC_JOBS_ENABLED` (`enabled`),
  KEY `QBWC_JOBS_CREATED` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Indexes Rationale

| Index | Purpose |
|-------|---------|
| `ticket` (UNIQUE) | Fast session lookup, prevent duplicates |
| `user` | Filter sessions by user |
| `company` | Filter by company file |
| `created_at` | Cleanup old sessions |
| `name` (UNIQUE) | Fast job lookup, prevent duplicates |
| `enabled` | Quick filter for active jobs |

---

## 🔌 Extension Points

### 1. Custom Workers

**Create custom worker**:

```php
namespace Vendor\CustomModule\Worker;

use Vendor\QuickbooksConnector\Model\Worker\AbstractWorker;

class ProductSyncWorker extends AbstractWorker
{
    protected $productRepository;

    public function __construct(
        ProductRepositoryInterface $productRepository
    ) {
        $this->productRepository = $productRepository;
    }

    public function requests($job, $session, $data)
    {
        return [
            [
                'ItemQueryRq' => [
                    'xml_attributes' => ['requestID' => '1'],
                    'MaxReturned' => 100
                ]
            ]
        ];
    }

    public function handleResponse($response, $session, $job, $request, $data)
    {
        foreach ($response['ItemQueryRs']['ItemInventoryRet'] as $item) {
            $this->syncProduct($item);
        }
    }

    protected function syncProduct($itemData)
    {
        // Custom sync logic
    }
}
```

### 2. Event Observers

**Available Events**:

| Event | When Fired | Data Available |
|-------|-----------|----------------|
| `qbwc_session_authenticated` | After successful auth | session, username |
| `qbwc_session_initialized` | Session initializer called | session |
| `qbwc_request_sent` | Before sending request | session, job, request |
| `qbwc_response_received` | After receiving response | session, job, response |
| `qbwc_job_started` | Job starts processing | job, session |
| `qbwc_job_completed` | Job finishes | job, session |
| `qbwc_session_completed` | All jobs done | session |
| `qbwc_error_occurred` | On error | session, error, severity |

**Example Observer**:

```php
class SendEmailOnError implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        $error = $observer->getData('error');
        $severity = $observer->getData('severity');

        if ($severity === 'Error') {
            $this->emailSender->send([
                'to' => 'admin@example.com',
                'subject' => 'QBWC Error',
                'body' => $error
            ]);
        }
    }
}
```

### 3. Plugins (Interceptors)

**Before Plugin**:

```xml
<type name="Vendor\QuickbooksConnector\Model\QbwcService">
    <plugin name="log_authentication"
            type="Vendor\CustomModule\Plugin\QbwcServicePlugin"/>
</type>
```

```php
class QbwcServicePlugin
{
    public function beforeAuthenticate(
        \Vendor\QuickbooksConnector\Model\QbwcService $subject,
        $username,
        $password
    ) {
        $this->logger->info("Auth attempt: $username");
        return [$username, $password];
    }

    public function afterAuthenticate(
        \Vendor\QuickbooksConnector\Model\QbwcService $subject,
        $result
    ) {
        [$ticket, $status] = $result;
        $this->logger->info("Auth result: $status");
        return $result;
    }
}
```

### 4. Preference Override

**Override default implementation**:

```xml
<preference for="Vendor\QuickbooksConnector\Model\QbxmlParser"
            type="Vendor\CustomModule\Model\CustomQbxmlParser"/>
```

---

## ⚡ Performance Considerations

### 1. Caching Strategy

**Session Caching**:

```php
class SessionRepository
{
    private $cache;

    public function getByTicket($ticket)
    {
        $cacheKey = 'qbwc_session_' . $ticket;

        // Try cache first
        $cached = $this->cache->load($cacheKey);
        if ($cached) {
            return $this->serializer->unserialize($cached);
        }

        // Load from DB
        $session = $this->loadFromDb($ticket);

        // Cache it
        $this->cache->save(
            $this->serializer->serialize($session),
            $cacheKey,
            ['qbwc_session'],
            3600  // 1 hour
        );

        return $session;
    }
}
```

### 2. Database Optimization

**Indexes**: Already covered in schema

**Query Optimization**:

```php
// Bad: N+1 query problem
foreach ($jobs as $job) {
    $worker = $this->workerRepository->getById($job->getWorkerId());
}

// Good: Eager loading
$jobs = $this->jobRepository->getListWithWorkers($criteria);
```

### 3. Memory Management

**Large Response Handling**:

```php
class QbxmlParser
{
    public function parseStream($xmlStream)
    {
        $reader = new \XMLReader();
        $reader->open($xmlStream);

        while ($reader->read()) {
            if ($reader->nodeType == \XMLReader::ELEMENT) {
                // Process incrementally
                $this->processNode($reader);
            }
        }

        $reader->close();
    }
}
```

### 4. Connection Pooling

**Database connections**: Magento handles automatically

**HTTP keep-alive**: Enabled in SOAP client

---

## 📊 Scalability

### Horizontal Scaling

**Session Storage**:
- Use Redis for session storage
- Share Redis across multiple web servers

**Job Distribution**:
- Use queue system (RabbitMQ)
- Distribute jobs to multiple workers

### Vertical Scaling

**PHP Configuration**:
```ini
memory_limit = 512M
max_execution_time = 300
```

**MySQL Tuning**:
```ini
innodb_buffer_pool_size = 2G
max_connections = 500
```

---

## 🔐 Security Architecture

### 1. Authentication Layer

```
QBWC Client ─► Username/Password ─► Config::validateCredentials()
                                     │
                                     ├─► Check against Magento config
                                     ├─► Or custom authenticator callback
                                     └─► Generate secure ticket (SHA-256)
```

### 2. Authorization Layer

```
Ticket ─► SessionRepository::getByTicket()
          │
          ├─► Verify ticket exists
          ├─► Check ticket not expired
          └─► Verify company file matches
```

### 3. Data Validation

```php
class QbwcService
{
    public function receiveResponse($ticket, $response, $hresult, $message)
    {
        // Validate inputs
        if (!$this->validator->isValidTicket($ticket)) {
            throw new InvalidArgumentException('Invalid ticket');
        }

        if (!$this->validator->isValidQbxml($response)) {
            throw new InvalidArgumentException('Invalid QBXML');
        }

        // Sanitize before processing
        $response = $this->sanitizer->sanitizeXml($response);

        // Process...
    }
}
```

---

**Last Updated**: 2025-11-16
