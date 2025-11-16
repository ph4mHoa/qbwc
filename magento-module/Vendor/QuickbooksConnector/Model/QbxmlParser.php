<?php
/**
 * QBXML Parser
 *
 * Converts between PHP arrays and QBXML format
 * Cloned from QBWC Rails gem - QBWC.parser interface
 *
 * @category  Vendor
 * @package   Vendor_QuickbooksConnector
 */
declare(strict_types=1);

namespace Vendor\QuickbooksConnector\Model;

use Psr\Log\LoggerInterface;

class QbxmlParser
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Config
     */
    private $config;

    /**
     * Constructor
     *
     * @param Config $config
     * @param LoggerInterface $logger
     */
    public function __construct(
        Config $config,
        LoggerInterface $logger
    ) {
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * Convert PHP array to QBXML string
     *
     * Cloned from Rails: QBWC.parser.to_qbxml
     * and lib/qbwc/request.rb:30-35 (wrap_request)
     *
     * @param array $data
     * @param bool $validate
     * @return string
     */
    public function arrayToQbxml(array $data, bool $validate = true): string
    {
        // Wrap request if needed
        $wrapped = $this->wrapRequest($data);

        // Convert to XML
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><?qbxml version="13.0"?><QBXML/>');

        $this->arrayToXml($wrapped, $xml);

        $qbxml = $xml->asXML();

        if ($this->config->getLogRequestsAndResponses()) {
            $this->logger->info("Generated QBXML:\n{$qbxml}");
        }

        return $qbxml;
    }

    /**
     * Convert QBXML string to PHP array
     *
     * Cloned from Rails: QBWC.parser.from_qbxml
     *
     * @param string $qbxml
     * @return array
     */
    public function qbxmlToArray(string $qbxml): array
    {
        try {
            if ($this->config->getLogRequestsAndResponses()) {
                $this->logger->info("Parsing QBXML:\n{$qbxml}");
            }

            // Parse XML
            $xml = new \SimpleXMLElement($qbxml);

            // Convert to array
            $result = $this->xmlToArray($xml);

            return $result;
        } catch (\Exception $e) {
            $this->logger->error("Error parsing QBXML: " . $e->getMessage());
            throw new \RuntimeException("Failed to parse QBXML: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Wrap request with qbxml_msgs_rq
     *
     * Cloned from Rails: lib/qbwc/request.rb:30-35
     *
     * @param array $request
     * @return array
     */
    private function wrapRequest(array $request): array
    {
        // Check if already wrapped
        if (isset($request['qbxml']) || isset($request['QBXML'])) {
            return $request;
        }

        if (isset($request['qbxml_msgs_rq']) || isset($request['QBXMLMsgsRq'])) {
            return ['qbxml' => $request];
        }

        // Wrap the request
        $onError = $this->config->getContinueOnError() ? 'continueOnError' : 'stopOnError';

        return [
            'qbxml' => [
                'qbxml_msgs_rq' => array_merge(
                    ['xml_attributes' => ['onError' => $onError]],
                    $request
                )
            ]
        ];
    }

    /**
     * Convert array to XML recursively
     *
     * @param array $data
     * @param \SimpleXMLElement $xml
     * @return void
     */
    private function arrayToXml(array $data, \SimpleXMLElement $xml): void
    {
        foreach ($data as $key => $value) {
            // Handle special xml_attributes key
            if ($key === 'xml_attributes') {
                foreach ($value as $attrKey => $attrValue) {
                    $xml->addAttribute($attrKey, (string) $attrValue);
                }
                continue;
            }

            // Convert snake_case to PascalCase for QBXML
            $xmlKey = $this->toPascalCase($key);

            if (is_array($value)) {
                // Check if this is a numeric array (multiple items)
                if ($this->isNumericArray($value)) {
                    foreach ($value as $item) {
                        $child = $xml->addChild($xmlKey);
                        if (is_array($item)) {
                            $this->arrayToXml($item, $child);
                        } else {
                            $child[0] = (string) $item;
                        }
                    }
                } else {
                    // Associative array
                    $child = $xml->addChild($xmlKey);
                    $this->arrayToXml($value, $child);
                }
            } else {
                // Scalar value
                $xml->addChild($xmlKey, htmlspecialchars((string) $value, ENT_XML1, 'UTF-8'));
            }
        }
    }

    /**
     * Convert XML to array recursively
     *
     * @param \SimpleXMLElement $xml
     * @return array
     */
    private function xmlToArray(\SimpleXMLElement $xml): array
    {
        $result = [];

        // Get attributes
        $attributes = [];
        foreach ($xml->attributes() as $key => $value) {
            $attributes[$key] = (string) $value;
        }

        if (!empty($attributes)) {
            $result['xml_attributes'] = $attributes;
        }

        // Get children
        foreach ($xml->children() as $child) {
            $name = $child->getName();
            $snakeName = $this->toSnakeCase($name);

            // Check if child has children or attributes
            $hasChildren = count($child->children()) > 0;
            $hasAttributes = count($child->attributes()) > 0;

            if ($hasChildren || $hasAttributes) {
                $childArray = $this->xmlToArray($child);
            } else {
                $childArray = (string) $child;
            }

            // Handle multiple elements with same name
            if (isset($result[$snakeName])) {
                if (!isset($result[$snakeName][0])) {
                    $result[$snakeName] = [$result[$snakeName]];
                }
                $result[$snakeName][] = $childArray;
            } else {
                $result[$snakeName] = $childArray;
            }
        }

        // If no children, return text content
        if (empty($result)) {
            return (string) $xml;
        }

        return $result;
    }

    /**
     * Convert string to PascalCase
     *
     * @param string $string
     * @return string
     */
    private function toPascalCase(string $string): string
    {
        // Handle special cases that should not be converted
        $special = [
            'qbxml' => 'QBXML',
            'qbxml_msgs_rq' => 'QBXMLMsgsRq',
            'qbxml_msgs_rs' => 'QBXMLMsgsRs',
        ];

        $lower = strtolower($string);
        if (isset($special[$lower])) {
            return $special[$lower];
        }

        // Convert snake_case to PascalCase
        return str_replace('_', '', ucwords($string, '_'));
    }

    /**
     * Convert string to snake_case
     *
     * @param string $string
     * @return string
     */
    private function toSnakeCase(string $string): string
    {
        // Handle special cases
        $special = [
            'QBXML' => 'qbxml',
            'QBXMLMsgsRq' => 'qbxml_msgs_rq',
            'QBXMLMsgsRs' => 'qbxml_msgs_rs',
        ];

        if (isset($special[$string])) {
            return $special[$string];
        }

        // Convert PascalCase to snake_case
        $result = preg_replace('/([a-z])([A-Z])/', '$1_$2', $string);
        return strtolower($result);
    }

    /**
     * Check if array is numeric (not associative)
     *
     * @param array $array
     * @return bool
     */
    private function isNumericArray(array $array): bool
    {
        if (empty($array)) {
            return false;
        }

        return array_keys($array) === range(0, count($array) - 1);
    }

    /**
     * Create a QBXML request from array
     *
     * Helper method for creating properly formatted requests
     *
     * @param string $requestType (e.g., 'CustomerQueryRq', 'InvoiceAddRq')
     * @param array $data
     * @param array $attributes Optional attributes (e.g., requestID, iterator)
     * @return array
     */
    public function createRequest(string $requestType, array $data = [], array $attributes = []): array
    {
        $request = [$requestType => $data];

        if (!empty($attributes)) {
            $request[$requestType]['xml_attributes'] = $attributes;
        }

        return $request;
    }

    /**
     * Validate QBXML string
     *
     * @param string $qbxml
     * @return bool
     */
    public function validateQbxml(string $qbxml): bool
    {
        try {
            new \SimpleXMLElement($qbxml);
            return true;
        } catch (\Exception $e) {
            $this->logger->error("Invalid QBXML: " . $e->getMessage());
            return false;
        }
    }
}
