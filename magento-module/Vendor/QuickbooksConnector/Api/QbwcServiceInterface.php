<?php
/**
 * QBWC SOAP Service Interface
 *
 * @category  Vendor
 * @package   Vendor_QuickbooksConnector
 */
declare(strict_types=1);

namespace Vendor\QuickbooksConnector\Api;

interface QbwcServiceInterface
{
    /**
     * Server version
     *
     * @return string
     */
    public function serverVersion(): string;

    /**
     * Client version validation
     *
     * @param string $strVersion
     * @return string
     */
    public function clientVersion(string $strVersion): string;

    /**
     * Authenticate user
     *
     * @param string $strUserName
     * @param string $strPassword
     * @return string[]
     */
    public function authenticate(string $strUserName, string $strPassword): array;

    /**
     * Send request XML to QuickBooks
     *
     * @param string $ticket
     * @param string $strHCPResponse
     * @param string $strCompanyFilename
     * @param string $qbXMLCountry
     * @param string $qbXMLMajorVers
     * @param string $qbXMLMinorVers
     * @return string
     */
    public function sendRequestXML(
        string $ticket,
        string $strHCPResponse,
        string $strCompanyFilename,
        string $qbXMLCountry,
        string $qbXMLMajorVers,
        string $qbXMLMinorVers
    ): string;

    /**
     * Receive response XML from QuickBooks
     *
     * @param string $ticket
     * @param string $response
     * @param string $hresult
     * @param string $message
     * @return int
     */
    public function receiveResponseXML(
        string $ticket,
        string $response,
        string $hresult,
        string $message
    ): int;

    /**
     * Close connection
     *
     * @param string $ticket
     * @return string
     */
    public function closeConnection(string $ticket): string;

    /**
     * Connection error
     *
     * @param string $ticket
     * @param string $hresult
     * @param string $message
     * @return string
     */
    public function connectionError(
        string $ticket,
        string $hresult,
        string $message
    ): string;

    /**
     * Get last error
     *
     * @param string $ticket
     * @return string
     */
    public function getLastError(string $ticket): string;
}
