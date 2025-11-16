# Test Cases Documentation

## 📋 Mục Lục

- [Overview](#overview)
- [Test Environment Setup](#test-environment-setup)
- [Unit Tests](#unit-tests)
- [Integration Tests](#integration-tests)
- [Functional Tests](#functional-tests)
- [SOAP API Tests](#soap-api-tests)
- [End-to-End Tests](#end-to-end-tests)
- [Performance Tests](#performance-tests)
- [Security Tests](#security-tests)
- [Test Data](#test-data)
- [Coverage Reports](#coverage-reports)

---

## 🎯 Overview

Tài liệu này mô tả chi tiết tất cả test cases cho module **Vendor_QuickbooksConnector**.

### Test Coverage Goals

- **Unit Tests**: > 90%
- **Integration Tests**: > 80%
- **Functional Tests**: 100% critical paths
- **API Tests**: 100% SOAP endpoints

### Testing Framework

- **PHPUnit**: 9.5+
- **Magento Testing Framework**: 2.4
- **SOAP UI**: For SOAP testing
- **PHP CodeSniffer**: Code standards

---

## 🔧 Test Environment Setup

### 1. Install Testing Dependencies

```bash
cd /path/to/magento

# Install PHPUnit
composer require --dev phpunit/phpunit

# Install Magento test framework
composer require --dev magento/magento2-functional-testing-framework
```

### 2. Configure PHPUnit

**File: `dev/tests/unit/phpunit.xml.dist`**

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="http://schema.phpunit.de/9.5/phpunit.xsd"
         colors="true"
         bootstrap="./framework/bootstrap.php">
    <testsuites>
        <testsuite name="Vendor QuickBooks Connector Unit Tests">
            <directory>../../../app/code/Vendor/QuickbooksConnector/Test/Unit</directory>
        </testsuite>
    </testsuites>
    <filter>
        <whitelist>
            <directory suffix=".php">../../../app/code/Vendor/QuickbooksConnector</directory>
            <exclude>
                <directory>../../../app/code/Vendor/QuickbooksConnector/Test</directory>
            </exclude>
        </whitelist>
    </filter>
</phpunit>
```

### 3. Setup Test Database

```bash
# Create test database
mysql -u root -p -e "CREATE DATABASE magento_test;"

# Configure test database
cp dev/tests/integration/etc/install-config-mysql.php.dist \
   dev/tests/integration/etc/install-config-mysql.php

# Edit install-config-mysql.php with test DB credentials
```

### 4. Install QuickBooks Web Connector (Test Environment)

- Download QBWC from Intuit
- Install on Windows test machine
- Configure test QuickBooks company file

---

## 🧪 Unit Tests

### TC-UNIT-001: Session Model Test

**File**: `Test/Unit/Model/SessionTest.php`

```php
<?php
namespace Vendor\QuickbooksConnector\Test\Unit\Model;

use PHPUnit\Framework\TestCase;
use Vendor\QuickbooksConnector\Model\Session;
use Vendor\QuickbooksConnector\Model\ResourceModel\Session as SessionResource;
use Magento\Framework\Serialize\SerializerInterface;

class SessionTest extends TestCase
{
    private $session;
    private $resourceMock;
    private $serializerMock;

    protected function setUp(): void
    {
        $this->resourceMock = $this->createMock(SessionResource::class);
        $this->serializerMock = $this->createMock(SerializerInterface::class);

        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->session = $objectManager->getObject(
            Session::class,
            [
                'resource' => $this->resourceMock,
                'serializer' => $this->serializerMock
            ]
        );
    }

    /**
     * Test Case: Session ticket generation
     *
     * @test
     */
    public function testTicketGeneration()
    {
        $ticket = $this->session->generateTicket('testuser', 'testcompany.qbw');

        $this->assertNotEmpty($ticket);
        $this->assertEquals(64, strlen($ticket)); // SHA256 = 64 chars
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $ticket);
    }

    /**
     * Test Case: Session getters and setters
     *
     * @test
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
     * Test Case: Progress calculation
     *
     * @test
     */
    public function testProgressCalculation()
    {
        $this->serializerMock->method('unserialize')
            ->willReturn(['job1', 'job2']);

        $this->session->setData('initial_job_count', 4);
        $this->session->setData('pending_jobs', '["job1","job2"]');

        $this->session->calculateProgress();

        // 4 initial jobs, 2 remaining = 50% progress
        $this->assertEquals(50, $this->session->getProgress());
    }

    /**
     * Test Case: Pending jobs serialization
     *
     * @test
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
        $this->assertEquals($serialized, $this->session->getData('pending_jobs'));
    }

    /**
     * Test Case: Error handling
     *
     * @test
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
     * Test Case: Session completion check
     *
     * @test
     */
    public function testSessionCompletion()
    {
        $this->session->setProgress(100);
        $this->assertTrue($this->session->isCompleted());

        $this->session->setProgress(99);
        $this->assertFalse($this->session->isCompleted());
    }
}
```

**Expected Results:**
- ✅ All assertions pass
- ✅ Ticket generation produces valid SHA-256 hash
- ✅ Progress calculation accurate
- ✅ Serialization/deserialization works correctly

---

### TC-UNIT-002: Job Model Test

**File**: `Test/Unit/Model/JobTest.php`

```php
<?php
namespace Vendor\QuickbooksConnector\Test\Unit\Model;

use PHPUnit\Framework\TestCase;
use Vendor\QuickbooksConnector\Model\Job;

class JobTest extends TestCase
{
    private $job;

    protected function setUp(): void
    {
        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->job = $objectManager->getObject(Job::class);
    }

    /**
     * Test Case: Job initialization
     *
     * @test
     */
    public function testJobInitialization()
    {
        $this->job->setName('test_job');
        $this->job->setEnabled(true);
        $this->job->setCompany('test.qbw');
        $this->job->setWorkerClass('Vendor\QuickbooksConnector\Worker\TestWorker');

        $this->assertEquals('test_job', $this->job->getName());
        $this->assertTrue($this->job->getEnabled());
        $this->assertEquals('test.qbw', $this->job->getCompany());
        $this->assertEquals('Vendor\QuickbooksConnector\Worker\TestWorker', $this->job->getWorkerClass());
    }

    /**
     * Test Case: Enable/Disable job
     *
     * @test
     */
    public function testEnableDisableJob()
    {
        $this->job->setEnabled(true);
        $this->assertTrue($this->job->isEnabled());

        $this->job->setEnabled(false);
        $this->assertFalse($this->job->isEnabled());
    }

    /**
     * Test Case: Request index tracking
     *
     * @test
     */
    public function testRequestIndexTracking()
    {
        $sessionKey = ['testuser', 'test.qbw'];
        $indexData = [
            'testuser_test.qbw' => 5
        ];

        $this->job->setRequestIndex(json_encode($indexData));
        $currentIndex = $this->job->getRequestIndexForSession($sessionKey);

        $this->assertEquals(5, $currentIndex);
    }

    /**
     * Test Case: Job reset
     *
     * @test
     */
    public function testJobReset()
    {
        $this->job->setRequestIndex('{"key": 10}');
        $this->job->setRequests('{"key": ["req1", "req2"]}');

        $this->job->reset();

        $this->assertEmpty($this->job->getRequestIndex());
        // Requests should be cleared if not provided when job added
    }
}
```

**Expected Results:**
- ✅ Job properties set/get correctly
- ✅ Enable/disable functionality works
- ✅ Request index tracks properly
- ✅ Reset clears state

---

### TC-UNIT-003: QBXML Parser Test

**File**: `Test/Unit/Model/QbxmlParserTest.php`

```php
<?php
namespace Vendor\QuickbooksConnector\Test\Unit\Model;

use PHPUnit\Framework\TestCase;
use Vendor\QuickbooksConnector\Model\QbxmlParser;

class QbxmlParserTest extends TestCase
{
    private $parser;

    protected function setUp(): void
    {
        $this->parser = new QbxmlParser();
    }

    /**
     * Test Case: Parse valid QBXML response
     *
     * @test
     */
    public function testParseValidQbxml()
    {
        $qbxml = <<<XML
<?xml version="1.0"?>
<QBXML>
    <QBXMLMsgsRs>
        <CustomerQueryRs statusCode="0" statusSeverity="Info">
            <CustomerRet>
                <ListID>80000001-1234567890</ListID>
                <Name>John Doe</Name>
                <FullName>John Doe</FullName>
                <IsActive>true</IsActive>
            </CustomerRet>
        </CustomerQueryRs>
    </QBXMLMsgsRs>
</QBXML>
XML;

        $result = $this->parser->parse($qbxml);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('CustomerQueryRs', $result);
        $this->assertEquals('0', $result['CustomerQueryRs']['@attributes']['statusCode']);
        $this->assertEquals('John Doe', $result['CustomerQueryRs']['CustomerRet']['Name']);
    }

    /**
     * Test Case: Parse QBXML with error
     *
     * @test
     */
    public function testParseQbxmlWithError()
    {
        $qbxml = <<<XML
<?xml version="1.0"?>
<QBXML>
    <QBXMLMsgsRs>
        <CustomerQueryRs statusCode="500" statusSeverity="Error" statusMessage="Invalid request">
        </CustomerQueryRs>
    </QBXMLMsgsRs>
</QBXML>
XML;

        $result = $this->parser->parse($qbxml);

        $this->assertEquals('500', $result['CustomerQueryRs']['@attributes']['statusCode']);
        $this->assertEquals('Error', $result['CustomerQueryRs']['@attributes']['statusSeverity']);
    }

    /**
     * Test Case: Generate QBXML from array
     *
     * @test
     */
    public function testGenerateQbxml()
    {
        $data = [
            'CustomerQueryRq' => [
                '@attributes' => [
                    'requestID' => '1'
                ],
                'MaxReturned' => 100
            ]
        ];

        $qbxml = $this->parser->toQbxml($data);

        $this->assertStringContainsString('<CustomerQueryRq', $qbxml);
        $this->assertStringContainsString('requestID="1"', $qbxml);
        $this->assertStringContainsString('<MaxReturned>100</MaxReturned>', $qbxml);
    }

    /**
     * Test Case: Handle iterator response
     *
     * @test
     */
    public function testParseIteratorResponse()
    {
        $qbxml = <<<XML
<?xml version="1.0"?>
<QBXML>
    <QBXMLMsgsRs>
        <CustomerQueryRs statusCode="0" iteratorRemainingCount="50" iteratorID="12345">
            <CustomerRet>
                <Name>Customer 1</Name>
            </CustomerRet>
        </CustomerQueryRs>
    </QBXMLMsgsRs>
</QBXML>
XML;

        $result = $this->parser->parse($qbxml);

        $this->assertEquals('50', $result['CustomerQueryRs']['@attributes']['iteratorRemainingCount']);
        $this->assertEquals('12345', $result['CustomerQueryRs']['@attributes']['iteratorID']);
    }
}
```

**Expected Results:**
- ✅ Valid QBXML parsed correctly
- ✅ Error responses handled
- ✅ Array to QBXML conversion works
- ✅ Iterator attributes extracted

---

## 🔗 Integration Tests

### TC-INT-001: Session Repository Test

**File**: `Test/Integration/Model/SessionRepositoryTest.php`

```php
<?php
namespace Vendor\QuickbooksConnector\Test\Integration\Model;

use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Vendor\QuickbooksConnector\Api\SessionRepositoryInterface;
use Vendor\QuickbooksConnector\Api\Data\SessionInterfaceFactory;

class SessionRepositoryTest extends TestCase
{
    private $sessionRepository;
    private $sessionFactory;
    private $objectManager;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->sessionRepository = $this->objectManager->create(SessionRepositoryInterface::class);
        $this->sessionFactory = $this->objectManager->create(SessionInterfaceFactory::class);
    }

    /**
     * Test Case: Save and retrieve session
     *
     * @magentoDbIsolation enabled
     * @test
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
        $this->assertNotNull($savedSession->getId());

        // Retrieve
        $loadedSession = $this->sessionRepository->getById($savedSession->getId());
        $this->assertEquals('test_ticket_123', $loadedSession->getTicket());
        $this->assertEquals('testuser', $loadedSession->getUser());
        $this->assertEquals('test.qbw', $loadedSession->getCompany());
    }

    /**
     * Test Case: Get session by ticket
     *
     * @magentoDbIsolation enabled
     * @test
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
     * Test Case: Delete session
     *
     * @magentoDbIsolation enabled
     * @test
     */
    public function testDeleteSession()
    {
        $session = $this->sessionFactory->create();
        $session->setTicket('delete_me');
        $session->setUser('deleteuser');
        $session->setCompany('delete.qbw');

        $saved = $this->sessionRepository->save($session);
        $id = $saved->getId();

        $this->sessionRepository->delete($saved);

        $this->expectException(\Magento\Framework\Exception\NoSuchEntityException::class);
        $this->sessionRepository->getById($id);
    }

    /**
     * Test Case: Update session progress
     *
     * @magentoDbIsolation enabled
     * @test
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
        $reloaded = $this->sessionRepository->getById($saved->getId());
        $this->assertEquals(50, $reloaded->getProgress());
    }
}
```

**Expected Results:**
- ✅ Session saved to database
- ✅ Session retrieved by ID and ticket
- ✅ Session deleted successfully
- ✅ Session updates persisted

---

### TC-INT-002: Job Repository Test

**File**: `Test/Integration/Model/JobRepositoryTest.php`

```php
<?php
namespace Vendor\QuickbooksConnector\Test\Integration\Model;

use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Vendor\QuickbooksConnector\Api\JobRepositoryInterface;
use Vendor\QuickbooksConnector\Api\Data\JobInterfaceFactory;

class JobRepositoryTest extends TestCase
{
    private $jobRepository;
    private $jobFactory;

    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->jobRepository = $objectManager->create(JobRepositoryInterface::class);
        $this->jobFactory = $objectManager->create(JobInterfaceFactory::class);
    }

    /**
     * Test Case: Create and save job
     *
     * @magentoDbIsolation enabled
     * @test
     */
    public function testCreateJob()
    {
        $job = $this->jobFactory->create();
        $job->setName('integration_test_job');
        $job->setEnabled(true);
        $job->setCompany('');
        $job->setWorkerClass('Vendor\QuickbooksConnector\Worker\TestWorker');

        $saved = $this->jobRepository->save($job);

        $this->assertNotNull($saved->getId());
        $this->assertEquals('integration_test_job', $saved->getName());
    }

    /**
     * Test Case: Get job by name
     *
     * @magentoDbIsolation enabled
     * @test
     */
    public function testGetJobByName()
    {
        $job = $this->jobFactory->create();
        $job->setName('get_by_name_job');
        $job->setEnabled(true);
        $job->setCompany('test.qbw');
        $job->setWorkerClass('TestWorker');

        $this->jobRepository->save($job);

        $retrieved = $this->jobRepository->getByName('get_by_name_job');
        $this->assertEquals('test.qbw', $retrieved->getCompany());
    }

    /**
     * Test Case: List pending jobs
     *
     * @magentoDbIsolation enabled
     * @test
     */
    public function testGetPendingJobs()
    {
        // Create enabled job
        $job1 = $this->jobFactory->create();
        $job1->setName('pending_job_1');
        $job1->setEnabled(true);
        $job1->setCompany('test.qbw');
        $job1->setWorkerClass('Worker1');
        $this->jobRepository->save($job1);

        // Create disabled job
        $job2 = $this->jobFactory->create();
        $job2->setName('disabled_job');
        $job2->setEnabled(false);
        $job2->setCompany('test.qbw');
        $job2->setWorkerClass('Worker2');
        $this->jobRepository->save($job2);

        $pendingJobs = $this->jobRepository->getPendingJobs('test.qbw');

        $this->assertCount(1, $pendingJobs);
        $this->assertEquals('pending_job_1', $pendingJobs[0]->getName());
    }
}
```

**Expected Results:**
- ✅ Job created and saved
- ✅ Job retrieved by name
- ✅ Pending jobs filtered correctly

---

## 🌐 SOAP API Tests

### TC-SOAP-001: Authentication Test

```php
<?php
namespace Vendor\QuickbooksConnector\Test\Api;

use Magento\TestFramework\TestCase\WebapiAbstract;

class AuthenticationTest extends WebapiAbstract
{
    const RESOURCE_PATH = '/V1/qbwc/authenticate';

    /**
     * Test Case: Successful authentication
     *
     * @test
     */
    public function testSuccessfulAuthentication()
    {
        $serviceInfo = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH,
                'httpMethod' => \Magento\Framework\Webapi\Rest\Request::HTTP_METHOD_POST,
            ],
            'soap' => [
                'service' => 'qbwcServiceV1',
                'operation' => 'qbwcServiceV1Authenticate',
            ],
        ];

        $requestData = [
            'strUserName' => 'qbuser',
            'strPassword' => 'qbpass'
        ];

        $response = $this->_webApiCall($serviceInfo, $requestData);

        $this->assertIsArray($response);
        $this->assertCount(2, $response);
        $this->assertNotEmpty($response[0]); // ticket
        $this->assertNotEquals('nvu', $response[1]); // not "not valid user"
    }

    /**
     * Test Case: Failed authentication - invalid credentials
     *
     * @test
     */
    public function testFailedAuthentication()
    {
        $serviceInfo = [
            'soap' => [
                'service' => 'qbwcServiceV1',
                'operation' => 'qbwcServiceV1Authenticate',
            ],
        ];

        $requestData = [
            'strUserName' => 'invalid',
            'strPassword' => 'wrong'
        ];

        $response = $this->_webApiCall($serviceInfo, $requestData);

        $this->assertEquals('', $response[0]); // empty ticket
        $this->assertEquals('nvu', $response[1]); // not valid user
    }

    /**
     * Test Case: No work available
     *
     * @test
     * @magentoDbIsolation enabled
     */
    public function testNoWorkAvailable()
    {
        // Clear all jobs first
        // ... cleanup code ...

        $serviceInfo = [
            'soap' => [
                'service' => 'qbwcServiceV1',
                'operation' => 'qbwcServiceV1Authenticate',
            ],
        ];

        $requestData = [
            'strUserName' => 'qbuser',
            'strPassword' => 'qbpass'
        ];

        $response = $this->_webApiCall($serviceInfo, $requestData);

        $this->assertEquals('', $response[0]);
        $this->assertEquals('none', $response[1]); // no work
    }
}
```

**Test Execution:**

```bash
php bin/magento dev:tests:run api Vendor_QuickbooksConnector
```

**Expected Results:**
- ✅ Valid credentials return ticket
- ✅ Invalid credentials return 'nvu'
- ✅ No jobs return 'none'

---

### TC-SOAP-002: Send Request Test

```php
<?php
namespace Vendor\QuickbooksConnector\Test\Api;

use Magento\TestFramework\TestCase\WebapiAbstract;

class SendRequestTest extends WebapiAbstract
{
    const RESOURCE_PATH = '/V1/qbwc/sendRequest';

    /**
     * Test Case: Send request with valid ticket
     *
     * @test
     * @depends testSuccessfulAuthentication
     */
    public function testSendRequestValidTicket()
    {
        // First authenticate to get ticket
        $ticket = $this->authenticate();

        $serviceInfo = [
            'soap' => [
                'service' => 'qbwcServiceV1',
                'operation' => 'qbwcServiceV1SendRequest',
            ],
        ];

        $requestData = [
            'ticket' => $ticket,
            'strHCPResponse' => '',
            'strCompanyFilename' => 'test.qbw',
            'qbXMLCountry' => 'US',
            'qbXMLMajorVers' => '13',
            'qbXMLMinorVers' => '0'
        ];

        $response = $this->_webApiCall($serviceInfo, $requestData);

        $this->assertIsString($response);
        $this->assertStringContainsString('<?xml', $response);
        $this->assertStringContainsString('QBXML', $response);
    }

    /**
     * Test Case: Send request with invalid ticket
     *
     * @test
     */
    public function testSendRequestInvalidTicket()
    {
        $this->expectException(\Exception::class);

        $serviceInfo = [
            'soap' => [
                'service' => 'qbwcServiceV1',
                'operation' => 'qbwcServiceV1SendRequest',
            ],
        ];

        $requestData = [
            'ticket' => 'invalid_ticket',
            'strHCPResponse' => '',
            'strCompanyFilename' => '',
            'qbXMLCountry' => 'US',
            'qbXMLMajorVers' => '13',
            'qbXMLMinorVers' => '0'
        ];

        $this->_webApiCall($serviceInfo, $requestData);
    }

    private function authenticate()
    {
        $serviceInfo = [
            'soap' => [
                'service' => 'qbwcServiceV1',
                'operation' => 'qbwcServiceV1Authenticate',
            ],
        ];

        $response = $this->_webApiCall($serviceInfo, [
            'strUserName' => 'qbuser',
            'strPassword' => 'qbpass'
        ]);

        return $response[0];
    }
}
```

**Expected Results:**
- ✅ Valid ticket returns QBXML request
- ✅ Invalid ticket throws exception

---

### TC-SOAP-003: Receive Response Test

```php
<?php
namespace Vendor\QuickbooksConnector\Test\Api;

use Magento\TestFramework\TestCase\WebapiAbstract;

class ReceiveResponseTest extends WebapiAbstract
{
    /**
     * Test Case: Receive successful response
     *
     * @test
     */
    public function testReceiveSuccessfulResponse()
    {
        $ticket = $this->authenticate();

        $qbxmlResponse = <<<XML
<?xml version="1.0"?>
<QBXML>
    <QBXMLMsgsRs>
        <CustomerQueryRs statusCode="0" statusSeverity="Info">
            <CustomerRet>
                <ListID>80000001-1234567890</ListID>
                <Name>Test Customer</Name>
            </CustomerRet>
        </CustomerQueryRs>
    </QBXMLMsgsRs>
</QBXML>
XML;

        $serviceInfo = [
            'soap' => [
                'service' => 'qbwcServiceV1',
                'operation' => 'qbwcServiceV1ReceiveResponse',
            ],
        ];

        $requestData = [
            'ticket' => $ticket,
            'response' => $qbxmlResponse,
            'hresult' => '',
            'message' => ''
        ];

        $progress = $this->_webApiCall($serviceInfo, $requestData);

        $this->assertIsInt($progress);
        $this->assertGreaterThanOrEqual(0, $progress);
        $this->assertLessThanOrEqual(100, $progress);
    }

    /**
     * Test Case: Receive error response
     *
     * @test
     */
    public function testReceiveErrorResponse()
    {
        $ticket = $this->authenticate();

        $serviceInfo = [
            'soap' => [
                'service' => 'qbwcServiceV1',
                'operation' => 'qbwcServiceV1ReceiveResponse',
            ],
        ];

        $requestData = [
            'ticket' => $ticket,
            'response' => '',
            'hresult' => '0x80040400',
            'message' => 'QuickBooks error: Invalid request'
        ];

        $progress = $this->_webApiCall($serviceInfo, $requestData);

        // With stopOnError, should return -1
        $this->assertEquals(-1, $progress);
    }
}
```

**Expected Results:**
- ✅ Success response returns progress (0-100)
- ✅ Error response returns -1 (stop on error)

---

## 🎭 Functional Tests

### TC-FUNC-001: End-to-End Workflow Test

**Test Scenario**: Complete sync workflow from authentication to completion

**Steps:**

1. **Setup**
   - Create test job: `sync_test_customers`
   - Enable job
   - Configure worker

2. **Authenticate**
   ```
   Input: username='qbuser', password='qbpass'
   Expected: ticket returned, company file path returned
   ```

3. **Send Request**
   ```
   Input: ticket from step 2
   Expected: CustomerQueryRq QBXML returned
   ```

4. **Receive Response**
   ```
   Input: ticket, QBXML response with 10 customers
   Expected: progress = 100%
   ```

5. **Close Connection**
   ```
   Input: ticket
   Expected: 'OK' returned, session deleted
   ```

**Verification:**
- ✅ Session created on authenticate
- ✅ Request contains proper QBXML
- ✅ Response processed by worker
- ✅ Progress calculated correctly
- ✅ Session cleaned up on close

---

### TC-FUNC-002: Iterator/Pagination Test

**Test Scenario**: Handle large dataset with pagination

**Steps:**

1. Create job requesting 500 customers (MaxReturned=100)

2. **First Request**
   ```xml
   <CustomerQueryRq requestID="1" iterator="Start">
       <MaxReturned>100</MaxReturned>
   </CustomerQueryRq>
   ```

3. **First Response**
   ```xml
   <CustomerQueryRs iteratorRemainingCount="400" iteratorID="12345">
       <!-- 100 customers -->
   </CustomerQueryRs>
   ```
   Expected: iteratorID saved to session

4. **Continue Request**
   ```xml
   <CustomerQueryRq requestID="1" iterator="Continue" iteratorID="12345">
   </CustomerQueryRq>
   ```

5. **Repeat** until iteratorRemainingCount = 0

**Verification:**
- ✅ Iterator continues automatically
- ✅ All 500 customers received
- ✅ Progress updates correctly
- ✅ Iterator cleared when done

---

### TC-FUNC-003: Error Handling Test

**Test Scenario A: Stop on Error**

**Config:** `on_error = 'stopOnError'`

**Steps:**
1. Create 3 jobs
2. Job 2 encounters error
3. Verify: Processing stops, progress = -1, jobs 3 not executed

**Test Scenario B: Continue on Error**

**Config:** `on_error = 'continueOnError'`

**Steps:**
1. Create 3 jobs
2. Job 2 encounters error
3. Verify: Processing continues, job 3 executes, error logged

---

## ⚡ Performance Tests

### TC-PERF-001: High Volume Test

**Objective**: Test performance with large datasets

**Test Configuration:**
- 10,000 customer records
- MaxReturned: 100
- Expected iterations: 100

**Metrics to Measure:**
- Total execution time
- Memory usage peak
- Database queries count
- Average response time per iteration

**Acceptance Criteria:**
- Total time: < 5 minutes
- Memory usage: < 256MB
- No memory leaks
- Average response time: < 500ms

**Test Code:**

```php
public function testHighVolumeSync()
{
    $startTime = microtime(true);
    $startMemory = memory_get_usage();

    // Authenticate
    $ticket = $this->authenticate();

    // Process 100 iterations
    for ($i = 0; $i < 100; $i++) {
        $request = $this->sendRequest($ticket);
        $response = $this->generateMockResponse(100); // 100 customers
        $progress = $this->receiveResponse($ticket, $response);

        // Memory check
        $currentMemory = memory_get_usage();
        $this->assertLessThan(256 * 1024 * 1024, $currentMemory); // < 256MB
    }

    $endTime = microtime(true);
    $totalTime = $endTime - $startTime;

    $this->assertLessThan(300, $totalTime); // < 5 minutes

    $this->closeConnection($ticket);
}
```

---

### TC-PERF-002: Concurrent Sessions Test

**Objective**: Test multiple concurrent sessions

**Test Configuration:**
- 5 concurrent sessions
- Different users/company files
- Each session syncing 1000 records

**Metrics:**
- Session isolation
- Database lock contention
- Response time degradation

**Expected:**
- No session data cross-contamination
- Minimal performance degradation (< 20%)

---

## 🔐 Security Tests

### TC-SEC-001: Authentication Security

**Test Cases:**

1. **SQL Injection**
   ```php
   $username = "admin' OR '1'='1";
   $password = "any";
   $result = $this->authenticate($username, $password);
   // Expected: Authentication fails
   ```

2. **Brute Force Protection**
   ```php
   for ($i = 0; $i < 100; $i++) {
       $this->authenticate('user', 'wrong_pass_' . $i);
   }
   // Expected: Rate limiting kicks in
   ```

3. **Session Hijacking**
   ```php
   $ticket1 = $this->authenticate('user1', 'pass1');
   // Try to use ticket1 with user2's company
   // Expected: Access denied
   ```

---

### TC-SEC-002: SOAP Injection

**Test Case:**

```php
public function testSoapInjection()
{
    $ticket = $this->authenticate();

    $maliciousXml = <<<XML
<?xml version="1.0"?>
<!DOCTYPE foo [<!ENTITY xxe SYSTEM "file:///etc/passwd">]>
<QBXML>
    <QBXMLMsgsRs>
        <CustomerQueryRs>&xxe;</CustomerQueryRs>
    </QBXMLMsgsRs>
</QBXML>
XML;

    $this->expectException(\Exception::class);
    $this->receiveResponse($ticket, $maliciousXml);
}
```

**Expected:** XXE attack prevented

---

## 📊 Test Data

### Sample Customer Data

```json
{
  "CustomerRet": {
    "ListID": "80000001-1234567890",
    "Name": "John Doe",
    "FullName": "John Doe",
    "IsActive": true,
    "CompanyName": "Doe Inc",
    "FirstName": "John",
    "LastName": "Doe",
    "BillAddress": {
      "Addr1": "123 Main St",
      "City": "New York",
      "State": "NY",
      "PostalCode": "10001",
      "Country": "USA"
    },
    "Phone": "555-1234",
    "Email": "john@doe.com",
    "Balance": "1000.00"
  }
}
```

### Sample Order Data

```json
{
  "InvoiceRet": {
    "TxnID": "1234-5678",
    "RefNumber": "INV-001",
    "TxnDate": "2025-01-15",
    "CustomerRef": {
      "ListID": "80000001-1234567890",
      "FullName": "John Doe"
    },
    "Subtotal": "100.00",
    "SalesTaxTotal": "8.00",
    "TotalAmount": "108.00",
    "InvoiceLineRet": [
      {
        "ItemRef": {
          "ListID": "80000010",
          "FullName": "Product A"
        },
        "Quantity": "2",
        "Rate": "50.00",
        "Amount": "100.00"
      }
    ]
  }
}
```

---

## 📈 Coverage Reports

### Generate Coverage Report

```bash
# Unit test coverage
vendor/bin/phpunit -c dev/tests/unit/phpunit.xml.dist \
  --coverage-html coverage/unit \
  --coverage-clover coverage/unit/clover.xml \
  app/code/Vendor/QuickbooksConnector/Test/Unit/

# Integration test coverage
php bin/magento dev:tests:run integration \
  --coverage-html coverage/integration \
  Vendor_QuickbooksConnector

# Combined coverage
vendor/bin/phpcov merge --html coverage/combined \
  coverage/unit coverage/integration
```

### View Coverage Report

```bash
open coverage/combined/index.html
```

### Coverage Goals

| Component | Target | Current |
|-----------|--------|---------|
| Models | 95% | - |
| Repositories | 90% | - |
| Services | 95% | - |
| Workers | 85% | - |
| Controllers | 80% | - |
| Overall | 90% | - |

---

## 🔄 Continuous Integration

### GitHub Actions Workflow

**File:** `.github/workflows/test.yml`

```yaml
name: Run Tests

on: [push, pull_request]

jobs:
  phpunit:
    runs-on: ubuntu-latest

    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: root
          MYSQL_DATABASE: magento_test
        ports:
          - 3306:3306

    steps:
      - uses: actions/checkout@v2

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.1'
          extensions: soap, xml, json, pdo, mbstring
          coverage: xdebug

      - name: Install Magento
        run: |
          composer create-project --repository-url=https://repo.magento.com/ \
            magento/project-community-edition magento

      - name: Install Module
        run: |
          cp -r . magento/app/code/Vendor/QuickbooksConnector
          cd magento
          php bin/magento module:enable Vendor_QuickbooksConnector
          php bin/magento setup:upgrade

      - name: Run Unit Tests
        run: |
          cd magento
          vendor/bin/phpunit -c dev/tests/unit/phpunit.xml.dist \
            app/code/Vendor/QuickbooksConnector/Test/Unit/

      - name: Run Integration Tests
        run: |
          cd magento
          php bin/magento dev:tests:run integration Vendor_QuickbooksConnector

      - name: Upload Coverage
        uses: codecov/codecov-action@v2
        with:
          files: ./magento/coverage/clover.xml
```

---

## ✅ Test Checklist

### Pre-Release Testing

- [ ] All unit tests pass
- [ ] All integration tests pass
- [ ] All functional tests pass
- [ ] SOAP API tests pass
- [ ] Performance tests meet criteria
- [ ] Security tests pass
- [ ] Code coverage > 90%
- [ ] No critical bugs
- [ ] Documentation updated
- [ ] Tested with QuickBooks Desktop (multiple versions)
- [ ] Tested with QBWC client
- [ ] Tested on Magento 2.4.6, 2.4.7, 2.4.8
- [ ] Tested on PHP 8.1 and 8.2

---

## 📞 Support

Need help with testing?

- **Documentation**: See individual test files
- **Issues**: https://github.com/vendor/module/issues
- **Email**: support@example.com

---

**Last Updated**: 2025-11-16
