<?php
/**
 * QWC File Download Controller
 *
 * Generates and downloads .qwc configuration file for QuickBooks Web Connector
 * Cloned from Rails: lib/qbwc/controller.rb:62-89
 *
 * @category  Vendor
 * @package   Vendor_QuickbooksConnector
 */
declare(strict_types=1);

namespace Vendor\QuickbooksConnector\Controller\Qwc;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\UrlInterface;
use Vendor\QuickbooksConnector\Model\Config;
use Psr\Log\LoggerInterface;

class Download implements HttpGetActionInterface
{
    /**
     * @var FileFactory
     */
    private $fileFactory;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Constructor
     *
     * @param FileFactory $fileFactory
     * @param Config $config
     * @param UrlInterface $urlBuilder
     * @param LoggerInterface $logger
     */
    public function __construct(
        FileFactory $fileFactory,
        Config $config,
        UrlInterface $urlBuilder,
        LoggerInterface $logger
    ) {
        $this->fileFactory = $fileFactory;
        $this->config = $config;
        $this->urlBuilder = $urlBuilder;
        $this->logger = $logger;
    }

    /**
     * Execute QWC file download
     *
     * Cloned from Rails: lib/qbwc/controller.rb:62-89
     * def qwc
     *   send_data qwc, :filename => "app.qwc", :content_type => 'application/x-qwc'
     * end
     *
     * @return ResponseInterface|ResultInterface
     */
    public function execute()
    {
        try {
            // Generate SOAP endpoint URL
            // In Rails: qbwc_action_url(:only_path => false)
            $soapUrl = $this->urlBuilder->getUrl(
                'soap/index/index',
                [
                    '_type' => UrlInterface::URL_TYPE_WEB,
                    '_secure' => true
                ]
            );

            // Generate QWC file content
            $qwcContent = $this->config->generateQwcFileContent($soapUrl);

            // Get filename
            $filename = $this->getQwcFilename();

            $this->logger->info("QWC file download requested: {$filename}");

            // Send file download
            // Cloned from Rails: send_data with content_type 'application/x-qwc'
            return $this->fileFactory->create(
                $filename,
                $qwcContent,
                \Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR,
                'application/x-qwc'
            );
        } catch (\Exception $e) {
            $this->logger->error('Error generating QWC file: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get QWC filename
     *
     * Cloned from Rails: lib/qbwc/controller.rb:88
     * "#{Rails.application.class.module_parent_name}.qwc"
     *
     * @return string
     */
    private function getQwcFilename(): string
    {
        $appName = $this->config->getAppName();

        // Sanitize app name for filename
        $sanitized = preg_replace('/[^a-zA-Z0-9_-]/', '_', $appName);

        return $sanitized . '.qwc';
    }
}
