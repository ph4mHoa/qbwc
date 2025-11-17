<?php
/**
 * QBWC Service Unit Tests
 *
 * @category  Vendor
 * @package   Vendor_QuickbooksConnector
 */
declare(strict_types=1);

namespace Vendor\QuickbooksConnector\Test\Unit\Model;

use PHPUnit\Framework\TestCase;
use Vendor\QuickbooksConnector\Model\QbwcService;
use Vendor\QuickbooksConnector\Api\SessionRepositoryInterface;
use Vendor\QuickbooksConnector\Api\JobRepositoryInterface;
use Vendor\QuickbooksConnector\Api\Data\SessionInterfaceFactory;
use Vendor\QuickbooksConnector\Api\Data\SessionInterface;
use Vendor\QuickbooksConnector\Api\Data\JobInterface;
use Vendor\QuickbooksConnector\Model\Config;
use Vendor\QuickbooksConnector\Model\QbxmlParser;
use Vendor\QuickbooksConnector\Model\CallbackManager;
use Psr\Log\LoggerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class QbwcServiceTest extends TestCase
{
    /**
     * @var QbwcService
     */
    private $service;

    /**
     * @var SessionRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $sessionRepositoryMock;

    /**
     * @var JobRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $jobRepositoryMock;

    /**
     * @var SessionInterfaceFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $sessionFactoryMock;

    /**
     * @var Config|\PHPUnit\Framework\MockObject\MockObject
     */
    private $configMock;

    /**
     * @var QbxmlParser|\PHPUnit\Framework\MockObject\MockObject
     */
    private $qbxmlParserMock;

    /**
     * @var CallbackManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $callbackManagerMock;

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

        $this->sessionRepositoryMock = $this->createMock(SessionRepositoryInterface::class);
        $this->jobRepositoryMock = $this->createMock(JobRepositoryInterface::class);
        $this->sessionFactoryMock = $this->createMock(SessionInterfaceFactory::class);
        $this->configMock = $this->createMock(Config::class);
        $this->qbxmlParserMock = $this->createMock(QbxmlParser::class);
        $this->callbackManagerMock = $this->createMock(CallbackManager::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->service = $this->objectManager->getObject(
            QbwcService::class,
            [
                'sessionRepository' => $this->sessionRepositoryMock,
                'jobRepository' => $this->jobRepositoryMock,
                'sessionFactory' => $this->sessionFactoryMock,
                'config' => $this->configMock,
                'qbxmlParser' => $this->qbxmlParserMock,
                'callbackManager' => $this->callbackManagerMock,
                'logger' => $this->loggerMock
            ]
        );
    }

    /**
     * Test server version returns configured version
     *
     * @test
     */
    public function testServerVersion()
    {
        $expectedVersion = '2.1.0.30';

        $this->configMock->expects($this->once())
            ->method('getServerVersion')
            ->willReturn($expectedVersion);

        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with($this->stringContains($expectedVersion));

        $result = $this->service->serverVersion();

        $this->assertEquals($expectedVersion, $result);
    }

    /**
     * Test client version accepts supported version
     *
     * @test
     */
    public function testClientVersionAccepted()
    {
        $clientVersion = '2.1.0.30';
        $minVersion = '2.0.0.0';

        $this->configMock->expects($this->once())
            ->method('getMinimumClientVersion')
            ->willReturn($minVersion);

        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with($this->stringContains('accepted'));

        $result = $this->service->clientVersion($clientVersion);

        $this->assertEquals('', $result);
    }

    /**
     * Test client version rejects unsupported version
     *
     * @test
     */
    public function testClientVersionRejected()
    {
        $clientVersion = '1.0.0.0';
        $minVersion = '2.0.0.0';
        $supportedVersion = '2.1.0.0';

        $this->configMock->expects($this->once())
            ->method('getMinimumClientVersion')
            ->willReturn($minVersion);

        $this->configMock->expects($this->once())
            ->method('getSupportedClientVersion')
            ->willReturn($supportedVersion);

        $this->loggerMock->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('rejected'));

        $result = $this->service->clientVersion($clientVersion);

        $this->assertStringStartsWith('W:', $result);
        $this->assertStringContainsString($supportedVersion, $result);
    }

    /**
     * Test successful authentication with jobs pending
     *
     * @test
     */
    public function testAuthenticateSuccessWithJobs()
    {
        $username = 'qbuser';
        $password = 'qbpass';
        $companyFile = 'C:\\QuickBooks\\test.qbw';

        $sessionMock = $this->createMock(SessionInterface::class);
        $jobMock = $this->createMock(JobInterface::class);

        $this->configMock->expects($this->once())
            ->method('authenticate')
            ->with($username, $password)
            ->willReturn($companyFile);

        $this->sessionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($sessionMock);

        $jobMock->expects($this->once())
            ->method('getName')
            ->willReturn('sync_customers');

        $this->jobRepositoryMock->expects($this->once())
            ->method('getPendingJobs')
            ->with($companyFile)
            ->willReturn([$jobMock]);

        $sessionMock->expects($this->once())
            ->method('setTicket')
            ->with($this->isType('string'))
            ->willReturnSelf();

        $sessionMock->expects($this->once())
            ->method('setUser')
            ->with($username)
            ->willReturnSelf();

        $sessionMock->expects($this->once())
            ->method('setCompany')
            ->with($companyFile)
            ->willReturnSelf();

        $sessionMock->expects($this->once())
            ->method('setProgress')
            ->with(0)
            ->willReturnSelf();

        $this->sessionRepositoryMock->expects($this->once())
            ->method('save')
            ->with($sessionMock);

        $this->callbackManagerMock->expects($this->once())
            ->method('invokeSessionInitializers')
            ->with($sessionMock);

        $result = $this->service->authenticate($username, $password);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertNotEmpty($result[0]); // ticket
        $this->assertEquals($companyFile, $result[1]);
    }

    /**
     * Test authentication fails with invalid credentials
     *
     * @test
     */
    public function testAuthenticateFailsWithInvalidCredentials()
    {
        $username = 'invalid';
        $password = 'wrong';

        $this->configMock->expects($this->once())
            ->method('authenticate')
            ->with($username, $password)
            ->willReturn(null);

        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with($this->stringContains('failed'));

        $result = $this->service->authenticate($username, $password);

        $this->assertIsArray($result);
        $this->assertEquals('', $result[0]);
        $this->assertEquals(QbwcService::AUTHENTICATE_NOT_VALID_USER, $result[1]);
    }

    /**
     * Test authentication returns none when no jobs pending
     *
     * @test
     */
    public function testAuthenticateNoWorkAvailable()
    {
        $username = 'qbuser';
        $password = 'qbpass';
        $companyFile = 'C:\\QuickBooks\\test.qbw';

        $this->configMock->expects($this->once())
            ->method('authenticate')
            ->with($username, $password)
            ->willReturn($companyFile);

        $this->jobRepositoryMock->expects($this->once())
            ->method('getPendingJobs')
            ->with($companyFile)
            ->willReturn([]);

        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with($this->stringContains('no jobs pending'));

        $result = $this->service->authenticate($username, $password);

        $this->assertIsArray($result);
        $this->assertEquals('', $result[0]);
        $this->assertEquals(QbwcService::AUTHENTICATE_NO_WORK, $result[1]);
    }

    /**
     * Test close connection deletes session
     *
     * @test
     */
    public function testCloseConnection()
    {
        $ticket = 'test_ticket_123';
        $sessionMock = $this->createMock(SessionInterface::class);

        $this->sessionRepositoryMock->expects($this->once())
            ->method('getByTicket')
            ->with($ticket)
            ->willReturn($sessionMock);

        $this->sessionRepositoryMock->expects($this->once())
            ->method('delete')
            ->with($sessionMock);

        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with($this->stringContains('closed successfully'));

        $result = $this->service->closeConnection($ticket);

        $this->assertEquals('OK', $result);
    }

    /**
     * Test close connection returns OK even on exception
     *
     * @test
     */
    public function testCloseConnectionHandlesException()
    {
        $ticket = 'test_ticket_123';

        $this->sessionRepositoryMock->expects($this->once())
            ->method('getByTicket')
            ->with($ticket)
            ->willThrowException(new \Exception('Session not found'));

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Error in closeConnection'));

        $result = $this->service->closeConnection($ticket);

        $this->assertEquals('OK', $result);
    }

    /**
     * Test connection error deletes session
     *
     * @test
     */
    public function testConnectionError()
    {
        $ticket = 'test_ticket_123';
        $hresult = '0x80040400';
        $message = 'QuickBooks error';
        $sessionMock = $this->createMock(SessionInterface::class);

        $this->sessionRepositoryMock->expects($this->once())
            ->method('getByTicket')
            ->with($ticket)
            ->willReturn($sessionMock);

        $this->sessionRepositoryMock->expects($this->once())
            ->method('delete')
            ->with($sessionMock);

        $this->loggerMock->expects($this->once())
            ->method('warning')
            ->with($this->stringContains($hresult));

        $result = $this->service->connectionError($ticket, $hresult, $message);

        $this->assertEquals('done', $result);
    }

    /**
     * Test get last error returns session error
     *
     * @test
     */
    public function testGetLastError()
    {
        $ticket = 'test_ticket_123';
        $errorMessage = 'Test error message';
        $sessionMock = $this->createMock(SessionInterface::class);

        $this->sessionRepositoryMock->expects($this->once())
            ->method('getByTicket')
            ->with($ticket)
            ->willReturn($sessionMock);

        $sessionMock->expects($this->once())
            ->method('getError')
            ->willReturn($errorMessage);

        $result = $this->service->getLastError($ticket);

        $this->assertEquals($errorMessage, $result);
    }

    /**
     * Test get last error returns empty string when no error
     *
     * @test
     */
    public function testGetLastErrorReturnsEmptyWhenNoError()
    {
        $ticket = 'test_ticket_123';
        $sessionMock = $this->createMock(SessionInterface::class);

        $this->sessionRepositoryMock->expects($this->once())
            ->method('getByTicket')
            ->with($ticket)
            ->willReturn($sessionMock);

        $sessionMock->expects($this->once())
            ->method('getError')
            ->willReturn(null);

        $result = $this->service->getLastError($ticket);

        $this->assertEquals('', $result);
    }

    /**
     * Test get last error handles exception
     *
     * @test
     */
    public function testGetLastErrorHandlesException()
    {
        $ticket = 'test_ticket_123';

        $this->sessionRepositoryMock->expects($this->once())
            ->method('getByTicket')
            ->with($ticket)
            ->willThrowException(new \Exception('Session not found'));

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Error in getLastError'));

        $result = $this->service->getLastError($ticket);

        $this->assertEquals('', $result);
    }

    /**
     * Test receive response returns progress percentage
     *
     * @test
     */
    public function testReceiveResponseReturnsProgress()
    {
        $ticket = 'test_ticket_123';
        $response = '<?xml version="1.0"?><QBXML><QBXMLMsgsRs><CustomerQueryRs statusCode="0"></CustomerQueryRs></QBXMLMsgsRs></QBXML>';
        $hresult = '';
        $message = '';

        $sessionMock = $this->createMock(SessionInterface::class);

        $this->sessionRepositoryMock->expects($this->once())
            ->method('getByTicket')
            ->with($ticket)
            ->willReturn($sessionMock);

        $sessionMock->expects($this->once())
            ->method('hasError')
            ->willReturn(false);

        $sessionMock->expects($this->once())
            ->method('getProgress')
            ->willReturn(50);

        $this->configMock->expects($this->once())
            ->method('getContinueOnError')
            ->willReturn(false);

        $this->sessionRepositoryMock->expects($this->once())
            ->method('save')
            ->with($sessionMock);

        $result = $this->service->receiveResponseXML($ticket, $response, $hresult, $message);

        $this->assertEquals(50, $result);
    }

    /**
     * Test receive response returns -1 on error with stopOnError
     *
     * @test
     */
    public function testReceiveResponseStopsOnError()
    {
        $ticket = 'test_ticket_123';
        $response = '';
        $hresult = '0x80040400';
        $message = 'QuickBooks error';

        $sessionMock = $this->createMock(SessionInterface::class);

        $this->sessionRepositoryMock->expects($this->once())
            ->method('getByTicket')
            ->with($ticket)
            ->willReturn($sessionMock);

        $sessionMock->expects($this->once())
            ->method('setError')
            ->with($message);

        $sessionMock->expects($this->once())
            ->method('setStatusCode')
            ->with($hresult);

        $sessionMock->expects($this->once())
            ->method('setStatusSeverity')
            ->with('Error');

        $sessionMock->expects($this->once())
            ->method('hasError')
            ->willReturn(true);

        $this->configMock->expects($this->once())
            ->method('getContinueOnError')
            ->willReturn(false);

        $this->loggerMock->expects($this->once())
            ->method('warning')
            ->with($this->stringContains($hresult));

        $result = $this->service->receiveResponseXML($ticket, $response, $hresult, $message);

        $this->assertEquals(-1, $result);
    }
}
