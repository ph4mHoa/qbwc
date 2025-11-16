# Complete Magento 2.4.8 QuickBooks Connector Module Structure

## 📁 Full Module Structure

```
Vendor/QuickbooksConnector/
│
├── registration.php                          ✅ CREATED
├── composer.json                             ⚠️ TO CREATE
│
├── Api/                                      # Service Contracts
│   ├── QbwcServiceInterface.php             ✅ CREATED
│   ├── SessionRepositoryInterface.php        ✅ CREATED
│   ├── JobRepositoryInterface.php            ✅ CREATED
│   ├── WorkerInterface.php                   ⚠️ TO CREATE
│   └── Data/
│       ├── SessionInterface.php              ✅ CREATED
│       ├── JobInterface.php                  ✅ CREATED
│       └── RequestInterface.php              ⚠️ TO CREATE
│
├── Model/                                    # Business Logic
│   ├── Session.php                           ⚠️ TO CREATE (Template below)
│   ├── SessionRepository.php                 ⚠️ TO CREATE
│   ├── Job.php                               ⚠️ TO CREATE
│   ├── JobRepository.php                     ⚠️ TO CREATE
│   ├── QbwcService.php                       ⚠️ TO CREATE (Template below)
│   ├── Request.php                           ⚠️ TO CREATE
│   ├── Config.php                            ⚠️ TO CREATE
│   ├── QbxmlParser.php                       ⚠️ TO CREATE
│   │
│   ├── ResourceModel/                        # Database Layer
│   │   ├── Session.php                       ⚠️ TO CREATE
│   │   ├── Session/
│   │   │   └── Collection.php                ⚠️ TO CREATE
│   │   ├── Job.php                           ⚠️ TO CREATE
│   │   └── Job/
│   │       └── Collection.php                ⚠️ TO CREATE
│   │
│   └── Worker/                               # Worker System
│       ├── AbstractWorker.php                ⚠️ TO CREATE (Template below)
│       └── Example/
│           ├── CustomerSync.php              ⚠️ TO CREATE
│           └── InvoiceSync.php               ⚠️ TO CREATE
│
├── Controller/                               # HTTP Controllers
│   └── Qwc/
│       └── Download.php                      ⚠️ TO CREATE
│
├── Console/                                  # CLI Commands
│   └── Command/
│       ├── Job/
│       │   ├── ListCommand.php               ⚠️ TO CREATE
│       │   ├── CreateCommand.php             ⚠️ TO CREATE
│       │   ├── EnableCommand.php             ⚠️ TO CREATE
│       │   ├── DisableCommand.php            ⚠️ TO CREATE
│       │   └── DeleteCommand.php             ⚠️ TO CREATE
│       └── Session/
│           ├── ListCommand.php               ⚠️ TO CREATE
│           └── CleanupCommand.php            ⚠️ TO CREATE
│
├── Logger/                                   # Custom Logger
│   └── Handler.php                           ⚠️ TO CREATE
│
├── etc/                                      # Configuration
│   ├── module.xml                            ✅ CREATED
│   ├── di.xml                                ✅ CREATED
│   ├── webapi.xml                            ✅ CREATED
│   ├── db_schema.xml                         ✅ CREATED
│   ├── config.xml                            ⚠️ TO CREATE
│   ├── events.xml                            ⚠️ TO CREATE
│   └── adminhtml/
│       ├── system.xml                        ⚠️ TO CREATE
│       └── menu.xml                          ⚠️ TO CREATE
│
└── Test/                                     # Tests
    ├── Unit/
    │   ├── Model/
    │   │   ├── SessionTest.php               ⚠️ TO CREATE (Template below)
    │   │   ├── JobTest.php                   ⚠️ TO CREATE
    │   │   └── QbxmlParserTest.php           ⚠️ TO CREATE
    │   └── Worker/
    │       └── AbstractWorkerTest.php        ⚠️ TO CREATE
    │
    ├── Integration/
    │   ├── Model/
    │   │   ├── SessionRepositoryTest.php     ⚠️ TO CREATE (Template below)
    │   │   └── JobRepositoryTest.php         ⚠️ TO CREATE
    │   └── Api/
    │       └── QbwcServiceTest.php           ⚠️ TO CREATE (Template below)
    │
    └── Api/
        ├── AuthenticationTest.php            ⚠️ TO CREATE
        ├── SendRequestTest.php               ⚠️ TO CREATE
        └── ReceiveResponseTest.php           ⚠️ TO CREATE
```

---

## 📝 File Templates

### 1. Model/Session.php (Complete Implementation)

```php
<?php
declare(strict_types=1);

namespace Vendor\QuickbooksConnector\Model;

use Magento\Framework\Model\AbstractModel;
use Vendor\QuickbooksConnector\Api\Data\SessionInterface;
use Vendor\QuickbooksConnector\Model\ResourceModel\Session as SessionResource;

class Session extends AbstractModel implements SessionInterface
{
    /**
     * @var \Magento\Framework\Serialize\SerializerInterface
     */
    private $serializer;

    /**
     * Constructor
     *
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Serialize\SerializerInterface $serializer
     * @param SessionResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Serialize\SerializerInterface $serializer,
        SessionResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->serializer = $serializer;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(SessionResource::class);
    }

    /**
     * @inheritDoc
     */
    public function getEntityId(): ?int
    {
        return $this->getData(self::ENTITY_ID) ? (int) $this->getData(self::ENTITY_ID) : null;
    }

    /**
     * @inheritDoc
     */
    public function setEntityId(int $entityId): SessionInterface
    {
        return $this->setData(self::ENTITY_ID, $entityId);
    }

    /**
     * @inheritDoc
     */
    public function getTicket(): string
    {
        return (string) $this->getData(self::TICKET);
    }

    /**
     * @inheritDoc
     */
    public function setTicket(string $ticket): SessionInterface
    {
        return $this->setData(self::TICKET, $ticket);
    }

    /**
     * @inheritDoc
     */
    public function getUser(): string
    {
        return (string) $this->getData(self::USER);
    }

    /**
     * @inheritDoc
     */
    public function setUser(string $user): SessionInterface
    {
        return $this->setData(self::USER, $user);
    }

    /**
     * @inheritDoc
     */
    public function getCompany(): string
    {
        return (string) $this->getData(self::COMPANY);
    }

    /**
     * @inheritDoc
     */
    public function setCompany(string $company): SessionInterface
    {
        return $this->setData(self::COMPANY, $company);
    }

    /**
     * @inheritDoc
     */
    public function getProgress(): int
    {
        return (int) $this->getData(self::PROGRESS);
    }

    /**
     * @inheritDoc
     */
    public function setProgress(int $progress): SessionInterface
    {
        return $this->setData(self::PROGRESS, $progress);
    }

    /**
     * @inheritDoc
     */
    public function getCurrentJob(): ?string
    {
        return $this->getData(self::CURRENT_JOB);
    }

    /**
     * @inheritDoc
     */
    public function setCurrentJob(?string $jobName): SessionInterface
    {
        return $this->setData(self::CURRENT_JOB, $jobName);
    }

    /**
     * @inheritDoc
     */
    public function getPendingJobs(): ?string
    {
        return $this->getData(self::PENDING_JOBS);
    }

    /**
     * @inheritDoc
     */
    public function setPendingJobs(?string $jobs): SessionInterface
    {
        return $this->setData(self::PENDING_JOBS, $jobs);
    }

    /**
     * Get pending jobs as array
     *
     * @return array
     */
    public function getPendingJobsArray(): array
    {
        $jobs = $this->getPendingJobs();
        if (empty($jobs)) {
            return [];
        }
        try {
            return $this->serializer->unserialize($jobs);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Set pending jobs from array
     *
     * @param array $jobs
     * @return $this
     */
    public function setPendingJobsArray(array $jobs): self
    {
        $serialized = $this->serializer->serialize($jobs);
        return $this->setPendingJobs($serialized);
    }

    /**
     * @inheritDoc
     */
    public function getIteratorId(): ?string
    {
        return $this->getData(self::ITERATOR_ID);
    }

    /**
     * @inheritDoc
     */
    public function setIteratorId(?string $iteratorId): SessionInterface
    {
        return $this->setData(self::ITERATOR_ID, $iteratorId);
    }

    /**
     * @inheritDoc
     */
    public function getError(): ?string
    {
        return $this->getData(self::ERROR);
    }

    /**
     * @inheritDoc
     */
    public function setError(?string $error): SessionInterface
    {
        return $this->setData(self::ERROR, $error);
    }

    /**
     * @inheritDoc
     */
    public function getStatusCode(): ?string
    {
        return $this->getData(self::STATUS_CODE);
    }

    /**
     * @inheritDoc
     */
    public function setStatusCode(?string $code): SessionInterface
    {
        return $this->setData(self::STATUS_CODE, $code);
    }

    /**
     * @inheritDoc
     */
    public function getStatusSeverity(): ?string
    {
        return $this->getData(self::STATUS_SEVERITY);
    }

    /**
     * @inheritDoc
     */
    public function setStatusSeverity(?string $severity): SessionInterface
    {
        return $this->setData(self::STATUS_SEVERITY, $severity);
    }

    /**
     * @inheritDoc
     */
    public function getInitialJobCount(): int
    {
        return (int) $this->getData(self::INITIAL_JOB_COUNT);
    }

    /**
     * @inheritDoc
     */
    public function setInitialJobCount(int $count): SessionInterface
    {
        return $this->setData(self::INITIAL_JOB_COUNT, $count);
    }

    /**
     * @inheritDoc
     */
    public function getCreatedAt(): string
    {
        return (string) $this->getData(self::CREATED_AT);
    }

    /**
     * @inheritDoc
     */
    public function setCreatedAt(string $createdAt): SessionInterface
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * @inheritDoc
     */
    public function getUpdatedAt(): string
    {
        return (string) $this->getData(self::UPDATED_AT);
    }

    /**
     * @inheritDoc
     */
    public function setUpdatedAt(string $updatedAt): SessionInterface
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }

    /**
     * Calculate progress percentage
     *
     * @return $this
     */
    public function calculateProgress(): self
    {
        $initialCount = $this->getInitialJobCount();
        $pendingJobs = $this->getPendingJobsArray();
        $currentCount = count($pendingJobs);

        if ($initialCount > 0) {
            $jobsCompleted = $initialCount - $currentCount;
            $progress = (int) (($jobsCompleted / $initialCount) * 100);
            $this->setProgress($progress);
        }

        return $this;
    }

    /**
     * Check if session has error
     *
     * @return bool
     */
    public function hasError(): bool
    {
        return !empty($this->getError());
    }

    /**
     * Check if session is completed
     *
     * @return bool
     */
    public function isCompleted(): bool
    {
        return $this->getProgress() >= 100;
    }

    /**
     * Generate unique ticket
     *
     * @param string $username
     * @param string $company
     * @return string
     */
    public static function generateTicket(string $username, string $company): string
    {
        return hash('sha256', uniqid($username . $company, true));
    }
}
```

### 2. Test/Unit/Model/SessionTest.php (Complete Unit Test)

```php
<?php
declare(strict_types=1);

namespace Vendor\QuickbooksConnector\Test\Unit\Model;

use PHPUnit\Framework\TestCase;
use Vendor\QuickbooksConnector\Model\Session;
use Vendor\QuickbooksConnector\Model\ResourceModel\Session as SessionResource;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class SessionTest extends TestCase
{
    /**
     * @var Session
     */
    private $session;

    /**
     * @var SessionResource|\PHPUnit\Framework\MockObject\MockObject
     */
    private $resourceMock;

    /**
     * @var SerializerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $serializerMock;

    /**
     * Set up test
     */
    protected function setUp(): void
    {
        $this->resourceMock = $this->createMock(SessionResource::class);
        $this->serializerMock = $this->createMock(SerializerInterface::class);

        $objectManager = new ObjectManager($this);
        $this->session = $objectManager->getObject(
            Session::class,
            [
                'resource' => $this->resourceMock,
                'serializer' => $this->serializerMock
            ]
        );
    }

    /**
     * Test ticket generation
     */
    public function testTicketGeneration()
    {
        $ticket = Session::generateTicket('testuser', 'testcompany.qbw');

        $this->assertNotEmpty($ticket);
        $this->assertEquals(64, strlen($ticket)); // SHA256 = 64 chars
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $ticket);
    }

    /**
     * Test getters and setters
     */
    public function testGettersAndSetters()
    {
        $this->session->setTicket('abc123');
        $this->session->setUser('testuser');
        $this->session->setCompany('C:\\QB\\test.qbw');
        $this->session->setProgress(50);

        $this->assertEquals('abc123', $this->session->getTicket());
        $this->assertEquals('testuser', $this->session->getUser());
        $this->assertEquals('C:\\QB\\test.qbw', $this->session->getCompany());
        $this->assertEquals(50, $this->session->getProgress());
    }

    /**
     * Test progress calculation
     */
    public function testProgressCalculation()
    {
        $this->serializerMock->expects($this->once())
            ->method('unserialize')
            ->willReturn(['job1', 'job2']);

        $this->session->setInitialJobCount(4);
        $this->session->setPendingJobs('["job1","job2"]');

        $this->session->calculateProgress();

        // 4 initial jobs, 2 remaining = 50% progress
        $this->assertEquals(50, $this->session->getProgress());
    }

    /**
     * Test pending jobs serialization
     */
    public function testPendingJobsSerialization()
    {
        $jobs = ['sync_customers', 'sync_orders', 'sync_products'];
        $serialized = '["sync_customers","sync_orders","sync_products"]';

        $this->serializerMock->expects($this->once())
            ->method('serialize')
            ->with($jobs)
            ->willReturn($serialized);

        $this->session->setPendingJobsArray($jobs);
        $this->assertEquals($serialized, $this->session->getPendingJobs());
    }

    /**
     * Test pending jobs deserialization
     */
    public function testPendingJobsDeserialization()
    {
        $jobs = ['sync_customers', 'sync_orders'];
        $serialized = '["sync_customers","sync_orders"]';

        $this->session->setPendingJobs($serialized);

        $this->serializerMock->expects($this->once())
            ->method('unserialize')
            ->with($serialized)
            ->willReturn($jobs);

        $result = $this->session->getPendingJobsArray();
        $this->assertEquals($jobs, $result);
    }

    /**
     * Test error handling
     */
    public function testErrorHandling()
    {
        $this->session->setError('Test error message');
        $this->session->setStatusCode('500');
        $this->session->setStatusSeverity('Error');

        $this->assertEquals('Test error message', $this->session->getError());
        $this->assertEquals('500', $this->session->getStatusCode());
        $this->assertEquals('Error', $this->session->getStatusSeverity());
        $this->assertTrue($this->session->hasError());
    }

    /**
     * Test session completion check
     */
    public function testSessionCompletion()
    {
        $this->session->setProgress(100);
        $this->assertTrue($this->session->isCompleted());

        $this->session->setProgress(99);
        $this->assertFalse($this->session->isCompleted());

        $this->session->setProgress(0);
        $this->assertFalse($this->session->isCompleted());
    }

    /**
     * Test iterator ID
     */
    public function testIteratorId()
    {
        $this->session->setIteratorId('12345');
        $this->assertEquals('12345', $this->session->getIteratorId());

        $this->session->setIteratorId(null);
        $this->assertNull($this->session->getIteratorId());
    }

    /**
     * Test current job
     */
    public function testCurrentJob()
    {
        $this->session->setCurrentJob('sync_customers');
        $this->assertEquals('sync_customers', $this->session->getCurrentJob());

        $this->session->setCurrentJob(null);
        $this->assertNull($this->session->getCurrentJob());
    }
}
```

### 3. Test/Integration/Model/SessionRepositoryTest.php

```php
<?php
declare(strict_types=1);

namespace Vendor\QuickbooksConnector\Test\Integration\Model;

use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Vendor\QuickbooksConnector\Api\SessionRepositoryInterface;
use Vendor\QuickbooksConnector\Api\Data\SessionInterfaceFactory;
use Magento\Framework\Exception\NoSuchEntityException;

class SessionRepositoryTest extends TestCase
{
    /**
     * @var SessionRepositoryInterface
     */
    private $sessionRepository;

    /**
     * @var SessionInterfaceFactory
     */
    private $sessionFactory;

    /**
     * Set up test
     */
    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->sessionRepository = $objectManager->create(SessionRepositoryInterface::class);
        $this->sessionFactory = $objectManager->create(SessionInterfaceFactory::class);
    }

    /**
     * Test save and retrieve session
     *
     * @magentoDbIsolation enabled
     */
    public function testSaveAndGetSession()
    {
        // Create session
        $session = $this->sessionFactory->create();
        $session->setTicket('test_ticket_123');
        $session->setUser('testuser');
        $session->setCompany('test.qbw');
        $session->setProgress(0);

        // Save
        $savedSession = $this->sessionRepository->save($session);
        $this->assertNotNull($savedSession->getEntityId());

        // Retrieve by ID
        $loadedSession = $this->sessionRepository->getById($savedSession->getEntityId());
        $this->assertEquals('test_ticket_123', $loadedSession->getTicket());
        $this->assertEquals('testuser', $loadedSession->getUser());
        $this->assertEquals('test.qbw', $loadedSession->getCompany());
    }

    /**
     * Test get session by ticket
     *
     * @magentoDbIsolation enabled
     */
    public function testGetByTicket()
    {
        $session = $this->sessionFactory->create();
        $session->setTicket('unique_ticket_456');
        $session->setUser('user2');
        $session->setCompany('company2.qbw');

        $this->sessionRepository->save($session);

        $retrieved = $this->sessionRepository->getByTicket('unique_ticket_456');
        $this->assertEquals('user2', $retrieved->getUser());
    }

    /**
     * Test delete session
     *
     * @magentoDbIsolation enabled
     */
    public function testDeleteSession()
    {
        $session = $this->sessionFactory->create();
        $session->setTicket('delete_me');
        $session->setUser('deleteuser');
        $session->setCompany('delete.qbw');

        $saved = $this->sessionRepository->save($session);
        $id = $saved->getEntityId();

        $this->sessionRepository->delete($saved);

        $this->expectException(NoSuchEntityException::class);
        $this->sessionRepository->getById($id);
    }

    /**
     * Test update session progress
     *
     * @magentoDbIsolation enabled
     */
    public function testUpdateSessionProgress()
    {
        $session = $this->sessionFactory->create();
        $session->setTicket('progress_test');
        $session->setUser('progressuser');
        $session->setCompany('progress.qbw');
        $session->setProgress(0);

        $saved = $this->sessionRepository->save($session);

        // Update progress
        $saved->setProgress(50);
        $this->sessionRepository->save($saved);

        // Reload and verify
        $reloaded = $this->sessionRepository->getById($saved->getEntityId());
        $this->assertEquals(50, $reloaded->getProgress());
    }

    /**
     * Test get non-existent session throws exception
     */
    public function testGetNonExistentSessionThrowsException()
    {
        $this->expectException(NoSuchEntityException::class);
        $this->sessionRepository->getById(999999);
    }

    /**
     * Test get by invalid ticket throws exception
     */
    public function testGetByInvalidTicketThrowsException()
    {
        $this->expectException(NoSuchEntityException::class);
        $this->sessionRepository->getByTicket('invalid_ticket');
    }
}
```

---

## 🧪 Test Case Summary

### Unit Tests (15 tests)

**Test/Unit/Model/**
- SessionTest.php (8 tests) ✅ Template above
- JobTest.php (6 tests)
- QbxmlParserTest.php (4 tests)

**Test/Unit/Worker/**
- AbstractWorkerTest.php (3 tests)

### Integration Tests (12 tests)

**Test/Integration/Model/**
- SessionRepositoryTest.php (6 tests) ✅ Template above
- JobRepositoryTest.php (5 tests)

**Test/Integration/Api/**
- QbwcServiceTest.php (5 tests)

### SOAP API Tests (8 tests)

**Test/Api/**
- AuthenticationTest.php (3 tests)
- SendRequestTest.php (2 tests)
- ReceiveResponseTest.php (3 tests)

---

## 📦 composer.json

```json
{
    "name": "vendor/module-quickbooks-connector",
    "description": "QuickBooks Web Connector for Magento 2",
    "type": "magento2-module",
    "version": "1.0.0",
    "license": "MIT",
    "authors": [
        {
            "name": "Vendor Team",
            "email": "dev@example.com"
        }
    ],
    "require": {
        "php": "^8.1",
        "magento/framework": "^103.0",
        "ext-soap": "*",
        "ext-xml": "*",
        "ext-json": "*"
    },
    "require-dev": {
        "phpunit/phpunit": "^9.5"
    },
    "autoload": {
        "files": [
            "registration.php"
        ],
        "psr-4": {
            "Vendor\\QuickbooksConnector\\": ""
        }
    }
}
```

---

## 🎯 Implementation Priority

### Phase 1: Core (Week 1-2)
1. ✅ Module structure
2. ✅ Service Contracts (Interfaces)
3. ✅ Database schema
4. ✅ Configuration files (di.xml, webapi.xml)
5. ⚠️ Session Model (use template above)
6. ⚠️ Job Model
7. ⚠️ Repositories

### Phase 2: SOAP & Workers (Week 3-4)
1. ⚠️ QbwcService implementation
2. ⚠️ QbxmlParser
3. ⚠️ AbstractWorker
4. ⚠️ Example workers (Customer, Invoice)
5. ⚠️ Request/Response handling

### Phase 3: CLI & Admin (Week 5)
1. ⚠️ CLI Commands
2. ⚠️ Admin configuration
3. ⚠️ QWC file download controller
4. ⚠️ Logger

### Phase 4: Testing (Week 6-7)
1. ⚠️ Unit tests (use templates above)
2. ⚠️ Integration tests
3. ⚠️ SOAP API tests
4. ⚠️ End-to-end testing with QBWC

### Phase 5: Documentation & Polish (Week 8)
1. Code documentation
2. User guide
3. Performance optimization
4. Security audit

---

## 📝 Quick Start for Remaining Files

All remaining files follow standard Magento 2 patterns. Refer to:
- Magento DevDocs: https://devdocs.magento.com/
- Templates provided above
- Original Rails code in `/home/user/qbwc/lib/qbwc/`

**Estimated Total Development Time:** 8-10 weeks fulltime

---

**Last Updated:** 2025-11-16
