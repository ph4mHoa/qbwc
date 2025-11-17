<?php
/**
 * Session Model Unit Tests
 *
 * @category  Vendor
 * @package   Vendor_QuickbooksConnector
 */
declare(strict_types=1);

namespace Vendor\QuickbooksConnector\Test\Unit\Model;

use PHPUnit\Framework\TestCase;
use Vendor\QuickbooksConnector\Model\Session;
use Vendor\QuickbooksConnector\Model\ResourceModel\Session as SessionResource;
use Magento\Framework\Serialize\SerializerInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
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

        $this->resourceMock = $this->createMock(SessionResource::class);
        $this->serializerMock = $this->createMock(SerializerInterface::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $contextMock = $this->createMock(Context::class);
        $registryMock = $this->createMock(Registry::class);

        $this->session = $this->objectManager->getObject(
            Session::class,
            [
                'context' => $contextMock,
                'registry' => $registryMock,
                'serializer' => $this->serializerMock,
                'logger' => $this->loggerMock,
                'resource' => $this->resourceMock
            ]
        );
    }

    /**
     * Test ticket generation produces valid SHA-256 hash
     *
     * @test
     */
    public function testTicketGeneration()
    {
        $ticket = Session::generateTicket('testuser', 'C:\\QuickBooks\\test.qbw');

        $this->assertNotEmpty($ticket);
        $this->assertEquals(64, strlen($ticket)); // SHA256 = 64 hex chars
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $ticket);
    }

    /**
     * Test ticket uniqueness
     *
     * @test
     */
    public function testTicketUniqueness()
    {
        $ticket1 = Session::generateTicket('user1', 'company1.qbw');
        $ticket2 = Session::generateTicket('user1', 'company1.qbw');

        $this->assertNotEquals($ticket1, $ticket2, 'Tickets should be unique even with same inputs');
    }

    /**
     * Test session getters and setters
     *
     * @test
     */
    public function testGettersAndSetters()
    {
        $this->session->setTicket('abc123xyz');
        $this->session->setUser('testuser');
        $this->session->setCompany('C:\\QuickBooks\\test.qbw');
        $this->session->setProgress(50);
        $this->session->setCurrentJob('sync_customers');

        $this->assertEquals('abc123xyz', $this->session->getTicket());
        $this->assertEquals('testuser', $this->session->getUser());
        $this->assertEquals('C:\\QuickBooks\\test.qbw', $this->session->getCompany());
        $this->assertEquals(50, $this->session->getProgress());
        $this->assertEquals('sync_customers', $this->session->getCurrentJob());
    }

    /**
     * Test progress calculation
     *
     * @test
     */
    public function testProgressCalculation()
    {
        $pendingJobs = ['job1', 'job2'];
        $serialized = json_encode($pendingJobs);

        $this->serializerMock->expects($this->once())
            ->method('unserialize')
            ->with($serialized)
            ->willReturn($pendingJobs);

        $this->session->setInitialJobCount(4);
        $this->session->setPendingJobs($serialized);

        $this->session->calculateProgress();

        // 4 initial jobs, 2 remaining = 50% progress
        $this->assertEquals(50, $this->session->getProgress());
    }

    /**
     * Test progress calculation with no jobs
     *
     * @test
     */
    public function testProgressCalculationWithNoJobs()
    {
        $this->session->setInitialJobCount(0);
        $this->session->setPendingJobs(null);

        $this->session->calculateProgress();

        // No jobs means no change to progress
        $this->assertEquals(0, $this->session->getProgress());
    }

    /**
     * Test progress calculation when all jobs completed
     *
     * @test
     */
    public function testProgressCalculationAllJobsCompleted()
    {
        $this->serializerMock->expects($this->once())
            ->method('unserialize')
            ->with('[]')
            ->willReturn([]);

        $this->session->setInitialJobCount(5);
        $this->session->setPendingJobs('[]');

        $this->session->calculateProgress();

        // All 5 jobs completed = 100% progress
        $this->assertEquals(100, $this->session->getProgress());
    }

    /**
     * Test pending jobs serialization
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
        $this->assertEquals($serialized, $this->session->getPendingJobs());
    }

    /**
     * Test pending jobs deserialization
     *
     * @test
     */
    public function testPendingJobsDeserialization()
    {
        $jobs = ['job1', 'job2', 'job3'];
        $serialized = json_encode($jobs);

        $this->serializerMock->expects($this->once())
            ->method('unserialize')
            ->with($serialized)
            ->willReturn($jobs);

        $this->session->setPendingJobs($serialized);
        $result = $this->session->getPendingJobsArray();

        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertEquals($jobs, $result);
    }

    /**
     * Test pending jobs deserialization handles empty value
     *
     * @test
     */
    public function testPendingJobsDeserializationEmpty()
    {
        $this->session->setPendingJobs(null);
        $result = $this->session->getPendingJobsArray();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test pending jobs deserialization handles exception
     *
     * @test
     */
    public function testPendingJobsDeserializationHandlesException()
    {
        $this->serializerMock->expects($this->once())
            ->method('unserialize')
            ->willThrowException(new \Exception('Invalid JSON'));

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Failed to unserialize pending jobs'));

        $this->session->setPendingJobs('invalid_json');
        $result = $this->session->getPendingJobsArray();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test error handling
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
     * Test hasError returns false when no error
     *
     * @test
     */
    public function testHasErrorReturnsFalse()
    {
        $this->session->setError(null);
        $this->assertFalse($this->session->hasError());

        $this->session->setError('');
        $this->assertFalse($this->session->hasError());
    }

    /**
     * Test responseIsError method
     *
     * @test
     */
    public function testResponseIsError()
    {
        $this->session->setError('Test error');
        $this->session->setStatusSeverity('Error');

        $this->assertTrue($this->session->responseIsError());
    }

    /**
     * Test responseIsError returns false for warnings
     *
     * @test
     */
    public function testResponseIsErrorReturnsFalseForWarnings()
    {
        $this->session->setError('Test warning');
        $this->session->setStatusSeverity('Warn');

        $this->assertFalse($this->session->responseIsError());
    }

    /**
     * Test responseIsError returns false when no error
     *
     * @test
     */
    public function testResponseIsErrorReturnsFalseWhenNoError()
    {
        $this->session->setError(null);
        $this->session->setStatusSeverity('Info');

        $this->assertFalse($this->session->responseIsError());
    }

    /**
     * Test session completion check
     *
     * @test
     */
    public function testSessionCompletion()
    {
        $this->session->setProgress(100);
        $this->assertTrue($this->session->isCompleted());

        $this->session->setProgress(101);
        $this->assertTrue($this->session->isCompleted());

        $this->session->setProgress(99);
        $this->assertFalse($this->session->isCompleted());

        $this->session->setProgress(0);
        $this->assertFalse($this->session->isCompleted());
    }

    /**
     * Test iterator ID handling
     *
     * @test
     */
    public function testIteratorId()
    {
        $this->session->setIteratorId('12345');
        $this->assertEquals('12345', $this->session->getIteratorId());

        $this->session->setIteratorId(null);
        $this->assertNull($this->session->getIteratorId());
    }

    /**
     * Test initial job count
     *
     * @test
     */
    public function testInitialJobCount()
    {
        $this->session->setInitialJobCount(10);
        $this->assertEquals(10, $this->session->getInitialJobCount());

        $this->session->setInitialJobCount(0);
        $this->assertEquals(0, $this->session->getInitialJobCount());
    }

    /**
     * Test getKey method
     *
     * @test
     */
    public function testGetKey()
    {
        $this->session->setUser('testuser');
        $this->session->setCompany('test.qbw');

        $key = $this->session->getKey();

        $this->assertIsArray($key);
        $this->assertCount(2, $key);
        $this->assertEquals(['testuser', 'test.qbw'], $key);
    }

    /**
     * Test shouldStopOnError method
     *
     * @test
     */
    public function testShouldStopOnError()
    {
        $this->session->setError('Test error');
        $this->session->setStatusSeverity('Error');

        $this->assertTrue($this->session->shouldStopOnError('stopOnError'));
        $this->assertFalse($this->session->shouldStopOnError('continueOnError'));
    }

    /**
     * Test shouldStopOnError returns false when no error
     *
     * @test
     */
    public function testShouldStopOnErrorReturnsFalseWhenNoError()
    {
        $this->session->setError(null);
        $this->session->setStatusSeverity('Info');

        $this->assertFalse($this->session->shouldStopOnError('stopOnError'));
        $this->assertFalse($this->session->shouldStopOnError('continueOnError'));
    }

    /**
     * Test created_at and updated_at timestamps
     *
     * @test
     */
    public function testTimestamps()
    {
        $now = date('Y-m-d H:i:s');

        $this->session->setCreatedAt($now);
        $this->session->setUpdatedAt($now);

        $this->assertEquals($now, $this->session->getCreatedAt());
        $this->assertEquals($now, $this->session->getUpdatedAt());
    }

    /**
     * Test entity ID
     *
     * @test
     */
    public function testEntityId()
    {
        $this->session->setEntityId(123);
        $this->assertEquals(123, $this->session->getEntityId());

        $this->session->setEntityId(0);
        $this->assertEquals(0, $this->session->getEntityId());
    }

    /**
     * Test entity ID returns null when not set
     *
     * @test
     */
    public function testEntityIdReturnsNullWhenNotSet()
    {
        $this->assertNull($this->session->getEntityId());
    }
}
