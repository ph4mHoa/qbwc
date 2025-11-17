<?php
/**
 * Job Model Unit Tests
 *
 * @category  Vendor
 * @package   Vendor_QuickbooksConnector
 */
declare(strict_types=1);

namespace Vendor\QuickbooksConnector\Test\Unit\Model;

use PHPUnit\Framework\TestCase;
use Vendor\QuickbooksConnector\Model\Job;
use Vendor\QuickbooksConnector\Model\ResourceModel\Job as JobResource;
use Vendor\QuickbooksConnector\Model\Worker\AbstractWorker;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\ObjectManagerInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class JobTest extends TestCase
{
    /**
     * @var Job
     */
    private $job;

    /**
     * @var JobResource|\PHPUnit\Framework\MockObject\MockObject
     */
    private $resourceMock;

    /**
     * @var SerializerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $serializerMock;

    /**
     * @var ObjectManagerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $objectManagerMock;

    /**
     * @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $loggerMock;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * Set up test environment
     */
    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);

        $this->resourceMock = $this->createMock(JobResource::class);
        $this->serializerMock = $this->createMock(SerializerInterface::class);
        $this->objectManagerMock = $this->createMock(ObjectManagerInterface::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $contextMock = $this->createMock(Context::class);
        $registryMock = $this->createMock(Registry::class);

        $this->job = $this->objectManager->getObject(
            Job::class,
            [
                'context' => $contextMock,
                'registry' => $registryMock,
                'serializer' => $this->serializerMock,
                'objectManager' => $this->objectManagerMock,
                'logger' => $this->loggerMock,
                'resource' => $this->resourceMock
            ]
        );
    }

    /**
     * Test job initialization and basic getters/setters
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
     * Test enable job
     *
     * @test
     */
    public function testEnableJob()
    {
        $this->job->setEnabled(false);
        $this->job->enable();

        $this->assertTrue($this->job->isEnabled());
        $this->assertTrue($this->job->getEnabled());
    }

    /**
     * Test disable job
     *
     * @test
     */
    public function testDisableJob()
    {
        $this->job->setEnabled(true);
        $this->job->disable();

        $this->assertFalse($this->job->isEnabled());
        $this->assertFalse($this->job->getEnabled());
    }

    /**
     * Test isEnabled method
     *
     * @test
     */
    public function testIsEnabled()
    {
        $this->job->setEnabled(true);
        $this->assertTrue($this->job->isEnabled());

        $this->job->setEnabled(false);
        $this->assertFalse($this->job->isEnabled());
    }

    /**
     * Test request index tracking for session
     *
     * @test
     */
    public function testRequestIndexForSession()
    {
        $sessionKey = ['testuser', 'test.qbw'];
        $indexData = [
            'testuser_test.qbw' => 5
        ];

        $this->serializerMock->expects($this->once())
            ->method('unserialize')
            ->willReturn($indexData);

        $this->job->setRequestIndex(json_encode($indexData));
        $currentIndex = $this->job->getRequestIndexForSession($sessionKey);

        $this->assertEquals(5, $currentIndex);
    }

    /**
     * Test request index returns 0 when not set
     *
     * @test
     */
    public function testRequestIndexForSessionReturnsZeroWhenNotSet()
    {
        $sessionKey = ['testuser', 'test.qbw'];

        $this->job->setRequestIndex(null);
        $currentIndex = $this->job->getRequestIndexForSession($sessionKey);

        $this->assertEquals(0, $currentIndex);
    }

    /**
     * Test request index returns 0 when session key not found
     *
     * @test
     */
    public function testRequestIndexForSessionReturnsZeroWhenKeyNotFound()
    {
        $sessionKey = ['testuser', 'test.qbw'];
        $indexData = [
            'otheruser_other.qbw' => 5
        ];

        $this->serializerMock->expects($this->once())
            ->method('unserialize')
            ->willReturn($indexData);

        $this->job->setRequestIndex(json_encode($indexData));
        $currentIndex = $this->job->getRequestIndexForSession($sessionKey);

        $this->assertEquals(0, $currentIndex);
    }

    /**
     * Test request index handles deserialization exception
     *
     * @test
     */
    public function testRequestIndexHandlesException()
    {
        $sessionKey = ['testuser', 'test.qbw'];

        $this->serializerMock->expects($this->once())
            ->method('unserialize')
            ->willThrowException(new \Exception('Invalid JSON'));

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Failed to get request index'));

        $this->job->setRequestIndex('invalid_json');
        $currentIndex = $this->job->getRequestIndexForSession($sessionKey);

        $this->assertEquals(0, $currentIndex);
    }

    /**
     * Test set request index for session
     *
     * @test
     */
    public function testSetRequestIndexForSession()
    {
        $sessionKey = ['testuser', 'test.qbw'];
        $existingData = ['otheruser_other.qbw' => 3];
        $expectedData = [
            'otheruser_other.qbw' => 3,
            'testuser_test.qbw' => 7
        ];

        $this->serializerMock->expects($this->once())
            ->method('unserialize')
            ->willReturn($existingData);

        $this->serializerMock->expects($this->once())
            ->method('serialize')
            ->with($expectedData)
            ->willReturn(json_encode($expectedData));

        $this->job->setRequestIndex(json_encode($existingData));
        $this->job->setRequestIndexForSession($sessionKey, 7);

        $this->assertEquals(json_encode($expectedData), $this->job->getRequestIndex());
    }

    /**
     * Test advance to next request
     *
     * @test
     */
    public function testAdvanceNextRequest()
    {
        $sessionKey = ['testuser', 'test.qbw'];
        $indexData = ['testuser_test.qbw' => 2];

        $this->serializerMock->expects($this->exactly(2))
            ->method('unserialize')
            ->willReturn($indexData);

        $this->serializerMock->expects($this->once())
            ->method('serialize')
            ->with(['testuser_test.qbw' => 3])
            ->willReturn(json_encode(['testuser_test.qbw' => 3]));

        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with($this->stringContains('advancing to request #3'));

        $this->job->setName('test_job');
        $this->job->setRequestIndex(json_encode($indexData));
        $this->job->advanceNextRequest($sessionKey);
    }

    /**
     * Test get requests for session
     *
     * @test
     */
    public function testGetRequestsForSession()
    {
        $sessionKey = ['testuser', 'test.qbw'];
        $requests = ['request1', 'request2'];
        $allRequests = [
            'testuser_test.qbw' => $requests
        ];

        $this->serializerMock->expects($this->once())
            ->method('unserialize')
            ->willReturn($allRequests);

        $this->job->setRequests(json_encode($allRequests));
        $result = $this->job->getRequestsForSession($sessionKey);

        $this->assertEquals($requests, $result);
    }

    /**
     * Test get requests for session returns empty when not found
     *
     * @test
     */
    public function testGetRequestsForSessionReturnsEmptyWhenNotFound()
    {
        $sessionKey = ['testuser', 'test.qbw'];
        $allRequests = [
            'otheruser_other.qbw' => ['request1']
        ];

        $this->serializerMock->expects($this->once())
            ->method('unserialize')
            ->willReturn($allRequests);

        $this->job->setRequests(json_encode($allRequests));
        $result = $this->job->getRequestsForSession($sessionKey);

        $this->assertEmpty($result);
    }

    /**
     * Test get requests for session with secondary key (nil username)
     *
     * @test
     */
    public function testGetRequestsForSessionWithSecondaryKey()
    {
        $sessionKey = ['testuser', 'test.qbw'];
        $requests = ['request1', 'request2'];
        $allRequests = [
            '_test.qbw' => $requests  // Secondary key with nil username
        ];

        $this->serializerMock->expects($this->once())
            ->method('unserialize')
            ->willReturn($allRequests);

        $this->job->setRequests(json_encode($allRequests));
        $result = $this->job->getRequestsForSession($sessionKey);

        $this->assertEquals($requests, $result);
    }

    /**
     * Test set requests for session
     *
     * @test
     */
    public function testSetRequestsForSession()
    {
        $sessionKey = ['testuser', 'test.qbw'];
        $requests = ['request1', 'request2'];
        $expectedData = [
            'testuser_test.qbw' => $requests
        ];

        $this->serializerMock->expects($this->once())
            ->method('unserialize')
            ->willReturn([]);

        $this->serializerMock->expects($this->once())
            ->method('serialize')
            ->with($expectedData)
            ->willReturn(json_encode($expectedData));

        $this->job->setRequestsForSession($sessionKey, $requests);
    }

    /**
     * Test job data serialization
     *
     * @test
     */
    public function testJobDataSerialization()
    {
        $data = ['key1' => 'value1', 'key2' => 'value2'];
        $serialized = json_encode($data);

        $this->serializerMock->expects($this->once())
            ->method('serialize')
            ->with($data)
            ->willReturn($serialized);

        $this->job->setJobData($data);
    }

    /**
     * Test job data deserialization
     *
     * @test
     */
    public function testJobDataDeserialization()
    {
        $data = ['key1' => 'value1', 'key2' => 'value2'];
        $serialized = json_encode($data);

        $this->serializerMock->expects($this->once())
            ->method('unserialize')
            ->with($serialized)
            ->willReturn($data);

        // Manually set the data field
        $this->job->setData('data', $serialized);
        $result = $this->job->getJobData();

        $this->assertEquals($data, $result);
    }

    /**
     * Test job data returns empty array when not set
     *
     * @test
     */
    public function testJobDataReturnsEmptyArrayWhenNotSet()
    {
        $result = $this->job->getJobData();
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test job data handles deserialization exception
     *
     * @test
     */
    public function testJobDataHandlesDeserializationException()
    {
        $this->serializerMock->expects($this->once())
            ->method('unserialize')
            ->willThrowException(new \Exception('Invalid JSON'));

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Failed to unserialize job data'));

        $this->job->setData('data', 'invalid_json');
        $result = $this->job->getJobData();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test job reset
     *
     * @test
     */
    public function testJobReset()
    {
        $this->job->setRequestIndex('{"key": 10}');
        $this->job->setRequests('{"key": ["req1", "req2"]}');
        $this->job->setRequestsProvidedWhenJobAdded(false);

        $this->job->reset();

        $this->assertNull($this->job->getRequestIndex());
        $this->assertNull($this->job->getRequests());
    }

    /**
     * Test job reset preserves requests when provided at job creation
     *
     * @test
     */
    public function testJobResetPreservesRequestsWhenProvided()
    {
        $requests = '{"key": ["req1", "req2"]}';

        $this->job->setRequestIndex('{"key": 10}');
        $this->job->setRequests($requests);
        $this->job->setRequestsProvidedWhenJobAdded(true);

        $this->job->reset();

        $this->assertNull($this->job->getRequestIndex());
        $this->assertEquals($requests, $this->job->getRequests());
    }

    /**
     * Test get worker instance
     *
     * @test
     */
    public function testGetWorker()
    {
        $workerMock = $this->createMock(AbstractWorker::class);
        $workerClass = 'Vendor\QuickbooksConnector\Worker\TestWorker';

        $this->objectManagerMock->expects($this->once())
            ->method('create')
            ->with($workerClass)
            ->willReturn($workerMock);

        $this->job->setWorkerClass($workerClass);
        $worker = $this->job->getWorker();

        $this->assertInstanceOf(AbstractWorker::class, $worker);
    }

    /**
     * Test get worker throws exception when worker class not defined
     *
     * @test
     */
    public function testGetWorkerThrowsExceptionWhenClassNotDefined()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Worker class not defined');

        $this->job->setWorkerClass('');
        $this->job->getWorker();
    }

    /**
     * Test get worker throws exception when worker creation fails
     *
     * @test
     */
    public function testGetWorkerThrowsExceptionWhenCreationFails()
    {
        $workerClass = 'Invalid\Worker\Class';

        $this->objectManagerMock->expects($this->once())
            ->method('create')
            ->with($workerClass)
            ->willThrowException(new \Exception('Class not found'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to create worker');

        $this->job->setWorkerClass($workerClass);
        $this->job->getWorker();
    }

    /**
     * Test requests provided when job added flag
     *
     * @test
     */
    public function testRequestsProvidedWhenJobAdded()
    {
        $this->job->setRequestsProvidedWhenJobAdded(true);
        $this->assertTrue($this->job->getRequestsProvidedWhenJobAdded());

        $this->job->setRequestsProvidedWhenJobAdded(false);
        $this->assertFalse($this->job->getRequestsProvidedWhenJobAdded());
    }

    /**
     * Test entity ID
     *
     * @test
     */
    public function testEntityId()
    {
        $this->job->setEntityId(456);
        $this->assertEquals(456, $this->job->getEntityId());
    }

    /**
     * Test entity ID returns null when not set
     *
     * @test
     */
    public function testEntityIdReturnsNullWhenNotSet()
    {
        $this->assertNull($this->job->getEntityId());
    }

    /**
     * Test timestamps
     *
     * @test
     */
    public function testTimestamps()
    {
        $now = date('Y-m-d H:i:s');

        $this->job->setData('created_at', $now);
        $this->job->setData('updated_at', $now);

        $this->assertEquals($now, $this->job->getCreatedAt());
        $this->assertEquals($now, $this->job->getUpdatedAt());
    }
}
