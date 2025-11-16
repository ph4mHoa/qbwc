<?php
/**
 * Request Class
 *
 * Wrapper for QBXML requests
 * Cloned from QBWC Rails gem - lib/qbwc/request.rb
 *
 * @category  Vendor
 * @package   Vendor_QuickbooksConnector
 */
declare(strict_types=1);

namespace Vendor\QuickbooksConnector\Model;

class Request
{
    /**
     * @var QbxmlParser
     */
    private $qbxmlParser;

    /**
     * @var array|string
     */
    private $request;

    /**
     * @var string|null
     */
    private $qbxml;

    /**
     * Constructor
     *
     * Cloned from Rails: lib/qbwc/request.rb:5-18
     *
     * @param QbxmlParser $qbxmlParser
     * @param array|string $request Hash or QBXML string
     */
    public function __construct(
        QbxmlParser $qbxmlParser,
        $request
    ) {
        $this->qbxmlParser = $qbxmlParser;

        if (is_array($request)) {
            // Convert hash to QBXML
            $this->request = $request;
            $this->qbxml = $qbxmlParser->arrayToQbxml($request, true);
        } elseif (is_string($request)) {
            // Direct QBXML string
            $this->qbxml = $request;
            $this->request = null;
        } else {
            throw new \InvalidArgumentException(
                'Request must be an array or a string.'
            );
        }
    }

    /**
     * Get QBXML string
     *
     * @return string
     */
    public function getQbxml(): string
    {
        return $this->qbxml;
    }

    /**
     * Get request as array
     *
     * Cloned from Rails: lib/qbwc/request.rb:24-27
     *
     * @return array
     */
    public function toArray(): array
    {
        if ($this->request !== null) {
            return $this->request;
        }

        // Parse QBXML to array
        $parsed = $this->qbxmlParser->qbxmlToArray($this->qbxml);

        // Extract the request part
        if (isset($parsed['qbxml']['qbxml_msgs_rq'])) {
            $request = $parsed['qbxml']['qbxml_msgs_rq'];
            unset($request['xml_attributes']);
            return $request;
        }

        return $parsed;
    }

    /**
     * Get QBXML string (alias)
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->qbxml;
    }
}
