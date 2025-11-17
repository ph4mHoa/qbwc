<?php
/**
 * QBXML Parser Unit Tests
 *
 * @category  Vendor
 * @package   Vendor_QuickbooksConnector
 */
declare(strict_types=1);

namespace Vendor\QuickbooksConnector\Test\Unit\Model;

use PHPUnit\Framework\TestCase;
use Vendor\QuickbooksConnector\Model\QbxmlParser;
use Vendor\QuickbooksConnector\Model\Config;
use Psr\Log\LoggerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class QbxmlParserTest extends TestCase
{
    /**
     * @var QbxmlParser
     */
    private $parser;

    /**
     * @var Config|\PHPUnit\Framework\MockObject\MockObject
     */
    private $configMock;

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

        $this->configMock = $this->createMock(Config::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->configMock->method('getLogRequestsAndResponses')->willReturn(false);

        $this->parser = $this->objectManager->getObject(
            QbxmlParser::class,
            [
                'config' => $this->configMock,
                'logger' => $this->loggerMock
            ]
        );
    }

    /**
     * Test parse valid QBXML response
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

        $result = $this->parser->qbxmlToArray($qbxml);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('qbxml_msgs_rs', $result);
        $this->assertArrayHasKey('customer_query_rs', $result['qbxml_msgs_rs']);

        $customerRs = $result['qbxml_msgs_rs']['customer_query_rs'];
        $this->assertArrayHasKey('xml_attributes', $customerRs);
        $this->assertEquals('0', $customerRs['xml_attributes']['statusCode']);
        $this->assertEquals('Info', $customerRs['xml_attributes']['statusSeverity']);
        $this->assertEquals('John Doe', $customerRs['customer_ret']['name']);
    }

    /**
     * Test parse QBXML with error
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

        $result = $this->parser->qbxmlToArray($qbxml);

        $this->assertArrayHasKey('qbxml_msgs_rs', $result);
        $customerRs = $result['qbxml_msgs_rs']['customer_query_rs'];

        $this->assertEquals('500', $customerRs['xml_attributes']['statusCode']);
        $this->assertEquals('Error', $customerRs['xml_attributes']['statusSeverity']);
        $this->assertEquals('Invalid request', $customerRs['xml_attributes']['statusMessage']);
    }

    /**
     * Test parse QBXML with iterator response
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

        $result = $this->parser->qbxmlToArray($qbxml);

        $customerRs = $result['qbxml_msgs_rs']['customer_query_rs'];
        $this->assertEquals('50', $customerRs['xml_attributes']['iteratorRemainingCount']);
        $this->assertEquals('12345', $customerRs['xml_attributes']['iteratorID']);
    }

    /**
     * Test parse invalid QBXML throws exception
     *
     * @test
     */
    public function testParseInvalidQbxmlThrowsException()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to parse QBXML');

        $invalidXml = 'not valid xml';
        $this->parser->qbxmlToArray($invalidXml);
    }

    /**
     * Test generate QBXML from array
     *
     * @test
     */
    public function testArrayToQbxml()
    {
        $this->configMock->method('getContinueOnError')->willReturn(false);

        $data = [
            'customer_query_rq' => [
                'xml_attributes' => [
                    'requestID' => '1'
                ],
                'max_returned' => 100
            ]
        ];

        $qbxml = $this->parser->arrayToQbxml($data);

        $this->assertStringContainsString('<?xml', $qbxml);
        $this->assertStringContainsString('QBXML', $qbxml);
        $this->assertStringContainsString('<CustomerQueryRq', $qbxml);
        $this->assertStringContainsString('requestID="1"', $qbxml);
        $this->assertStringContainsString('<MaxReturned>100</MaxReturned>', $qbxml);
    }

    /**
     * Test array to QBXML wraps request properly
     *
     * @test
     */
    public function testArrayToQbxmlWrapsRequest()
    {
        $this->configMock->method('getContinueOnError')->willReturn(true);

        $data = [
            'customer_query_rq' => []
        ];

        $qbxml = $this->parser->arrayToQbxml($data);

        $this->assertStringContainsString('QBXML', $qbxml);
        $this->assertStringContainsString('QBXMLMsgsRq', $qbxml);
        $this->assertStringContainsString('onError="continueOnError"', $qbxml);
    }

    /**
     * Test array to QBXML with stopOnError
     *
     * @test
     */
    public function testArrayToQbxmlWithStopOnError()
    {
        $this->configMock->method('getContinueOnError')->willReturn(false);

        $data = [
            'customer_query_rq' => []
        ];

        $qbxml = $this->parser->arrayToQbxml($data);

        $this->assertStringContainsString('onError="stopOnError"', $qbxml);
    }

    /**
     * Test array to QBXML with already wrapped request
     *
     * @test
     */
    public function testArrayToQbxmlWithAlreadyWrappedRequest()
    {
        $data = [
            'qbxml' => [
                'qbxml_msgs_rq' => [
                    'customer_query_rq' => []
                ]
            ]
        ];

        $qbxml = $this->parser->arrayToQbxml($data);

        $this->assertStringContainsString('QBXML', $qbxml);
        $this->assertStringContainsString('QBXMLMsgsRq', $qbxml);
    }

    /**
     * Test array to QBXML with numeric array (multiple items)
     *
     * @test
     */
    public function testArrayToQbxmlWithNumericArray()
    {
        $this->configMock->method('getContinueOnError')->willReturn(false);

        $data = [
            'invoice_add_rq' => [
                'invoice_line_add' => [
                    ['item_ref' => ['full_name' => 'Item 1']],
                    ['item_ref' => ['full_name' => 'Item 2']],
                ]
            ]
        ];

        $qbxml = $this->parser->arrayToQbxml($data);

        $this->assertStringContainsString('<InvoiceLineAdd>', $qbxml);
        $this->assertStringContainsString('<FullName>Item 1</FullName>', $qbxml);
        $this->assertStringContainsString('<FullName>Item 2</FullName>', $qbxml);
    }

    /**
     * Test createRequest helper method
     *
     * @test
     */
    public function testCreateRequest()
    {
        $data = [
            'max_returned' => 100,
            'active_status' => 'ActiveOnly'
        ];

        $attributes = [
            'requestID' => '1',
            'iterator' => 'Start'
        ];

        $result = $this->parser->createRequest('CustomerQueryRq', $data, $attributes);

        $this->assertArrayHasKey('CustomerQueryRq', $result);
        $this->assertEquals(100, $result['CustomerQueryRq']['max_returned']);
        $this->assertEquals('ActiveOnly', $result['CustomerQueryRq']['active_status']);
        $this->assertEquals('1', $result['CustomerQueryRq']['xml_attributes']['requestID']);
        $this->assertEquals('Start', $result['CustomerQueryRq']['xml_attributes']['iterator']);
    }

    /**
     * Test createRequest without attributes
     *
     * @test
     */
    public function testCreateRequestWithoutAttributes()
    {
        $data = [
            'max_returned' => 50
        ];

        $result = $this->parser->createRequest('CustomerQueryRq', $data);

        $this->assertArrayHasKey('CustomerQueryRq', $result);
        $this->assertEquals(50, $result['CustomerQueryRq']['max_returned']);
        $this->assertArrayNotHasKey('xml_attributes', $result['CustomerQueryRq']);
    }

    /**
     * Test validate QBXML returns true for valid XML
     *
     * @test
     */
    public function testValidateQbxmlValid()
    {
        $qbxml = <<<XML
<?xml version="1.0"?>
<QBXML>
    <QBXMLMsgsRq>
        <CustomerQueryRq>
        </CustomerQueryRq>
    </QBXMLMsgsRq>
</QBXML>
XML;

        $result = $this->parser->validateQbxml($qbxml);
        $this->assertTrue($result);
    }

    /**
     * Test validate QBXML returns false for invalid XML
     *
     * @test
     */
    public function testValidateQbxmlInvalid()
    {
        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Invalid QBXML'));

        $invalidXml = '<invalid>xml without closing tag';
        $result = $this->parser->validateQbxml($invalidXml);

        $this->assertFalse($result);
    }

    /**
     * Test special case conversions (QBXML specific)
     *
     * @test
     */
    public function testSpecialCaseConversions()
    {
        $this->configMock->method('getContinueOnError')->willReturn(false);

        $data = [
            'qbxml_msgs_rq' => [
                'customer_query_rq' => []
            ]
        ];

        $qbxml = $this->parser->arrayToQbxml($data);

        // Should convert to QBXMLMsgsRq (not QbxmlMsgsRq)
        $this->assertStringContainsString('QBXMLMsgsRq', $qbxml);
        $this->assertStringNotContainsString('QbxmlMsgsRq', $qbxml);
    }

    /**
     * Test snake_case to PascalCase conversion
     *
     * @test
     */
    public function testSnakeCaseToPascalCase()
    {
        $this->configMock->method('getContinueOnError')->willReturn(false);

        $data = [
            'customer_query_rq' => [
                'list_id' => '12345',
                'full_name' => 'Test Customer',
                'is_active' => 'true'
            ]
        ];

        $qbxml = $this->parser->arrayToQbxml($data);

        $this->assertStringContainsString('<ListID>12345</ListID>', $qbxml);
        $this->assertStringContainsString('<FullName>Test Customer</FullName>', $qbxml);
        $this->assertStringContainsString('<IsActive>true</IsActive>', $qbxml);
    }

    /**
     * Test PascalCase to snake_case conversion
     *
     * @test
     */
    public function testPascalCaseToSnakeCase()
    {
        $qbxml = <<<XML
<?xml version="1.0"?>
<QBXML>
    <QBXMLMsgsRs>
        <CustomerQueryRs>
            <CustomerRet>
                <ListID>12345</ListID>
                <FullName>Test Customer</FullName>
                <IsActive>true</IsActive>
            </CustomerRet>
        </CustomerQueryRs>
    </QBXMLMsgsRs>
</QBXML>
XML;

        $result = $this->parser->qbxmlToArray($qbxml);

        $customerRet = $result['qbxml_msgs_rs']['customer_query_rs']['customer_ret'];
        $this->assertEquals('12345', $customerRet['list_id']);
        $this->assertEquals('Test Customer', $customerRet['full_name']);
        $this->assertEquals('true', $customerRet['is_active']);
    }

    /**
     * Test HTML entities are escaped in QBXML
     *
     * @test
     */
    public function testHtmlEntitiesEscaped()
    {
        $this->configMock->method('getContinueOnError')->willReturn(false);

        $data = [
            'customer_add_rq' => [
                'customer_add' => [
                    'name' => 'Test & Company <Ltd>'
                ]
            ]
        ];

        $qbxml = $this->parser->arrayToQbxml($data);

        $this->assertStringContainsString('&amp;', $qbxml);
        $this->assertStringContainsString('&lt;', $qbxml);
        $this->assertStringContainsString('&gt;', $qbxml);
    }

    /**
     * Test logging when enabled
     *
     * @test
     */
    public function testLoggingWhenEnabled()
    {
        $this->configMock->method('getContinueOnError')->willReturn(false);
        $this->configMock->method('getLogRequestsAndResponses')->willReturn(true);

        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with($this->stringContains('Generated QBXML'));

        $data = [
            'customer_query_rq' => []
        ];

        $this->parser->arrayToQbxml($data);
    }

    /**
     * Test parsing with logging enabled
     *
     * @test
     */
    public function testParsingWithLoggingEnabled()
    {
        $this->configMock->method('getLogRequestsAndResponses')->willReturn(true);

        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with($this->stringContains('Parsing QBXML'));

        $qbxml = <<<XML
<?xml version="1.0"?>
<QBXML>
    <QBXMLMsgsRs>
        <CustomerQueryRs statusCode="0">
        </CustomerQueryRs>
    </QBXMLMsgsRs>
</QBXML>
XML;

        $this->parser->qbxmlToArray($qbxml);
    }

    /**
     * Test parsing response with multiple customers
     *
     * @test
     */
    public function testParseMultipleCustomers()
    {
        $qbxml = <<<XML
<?xml version="1.0"?>
<QBXML>
    <QBXMLMsgsRs>
        <CustomerQueryRs statusCode="0">
            <CustomerRet>
                <Name>Customer 1</Name>
            </CustomerRet>
            <CustomerRet>
                <Name>Customer 2</Name>
            </CustomerRet>
            <CustomerRet>
                <Name>Customer 3</Name>
            </CustomerRet>
        </CustomerQueryRs>
    </QBXMLMsgsRs>
</QBXML>
XML;

        $result = $this->parser->qbxmlToArray($qbxml);

        $customers = $result['qbxml_msgs_rs']['customer_query_rs']['customer_ret'];
        $this->assertIsArray($customers);
        $this->assertCount(3, $customers);
        $this->assertEquals('Customer 1', $customers[0]['name']);
        $this->assertEquals('Customer 2', $customers[1]['name']);
        $this->assertEquals('Customer 3', $customers[2]['name']);
    }
}
