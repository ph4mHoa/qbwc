<?php
/**
 * Configuration Class
 *
 * Handles QBWC module configuration
 * Cloned from QBWC Rails gem configuration pattern
 *
 * @category  Vendor
 * @package   Vendor_QuickbooksConnector
 */
declare(strict_types=1);

namespace Vendor\QuickbooksConnector\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{
    /**
     * Configuration paths
     */
    const XML_PATH_ENABLED = 'qbwc/general/enabled';
    const XML_PATH_USERNAME = 'qbwc/auth/username';
    const XML_PATH_PASSWORD = 'qbwc/auth/password';
    const XML_PATH_COMPANY_FILE = 'qbwc/general/company_file';
    const XML_PATH_APP_NAME = 'qbwc/general/app_name';
    const XML_PATH_APP_DESCRIPTION = 'qbwc/general/app_description';
    const XML_PATH_APP_SUPPORT_URL = 'qbwc/general/support_url';
    const XML_PATH_OWNER_ID = 'qbwc/general/owner_id';
    const XML_PATH_FILE_ID = 'qbwc/general/file_id';
    const XML_PATH_RUN_EVERY_N_MINUTES = 'qbwc/general/run_every_n_minutes';
    const XML_PATH_SERVER_VERSION = 'qbwc/general/server_version';
    const XML_PATH_MIN_CLIENT_VERSION = 'qbwc/general/min_client_version';
    const XML_PATH_SUPPORTED_CLIENT_VERSION = 'qbwc/general/supported_client_version';
    const XML_PATH_CONTINUE_ON_ERROR = 'qbwc/general/continue_on_error';
    const XML_PATH_LOG_REQUESTS = 'qbwc/logging/log_requests_and_responses';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var callable|null
     */
    private $authenticatorCallback;

    /**
     * Constructor
     *
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Check if module is enabled
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return (bool) $this->scopeConfig->getValue(
            self::XML_PATH_ENABLED,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get configured username
     *
     * @return string
     */
    public function getUsername(): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_USERNAME,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get configured password
     *
     * @return string
     */
    public function getPassword(): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_PASSWORD,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get company file path
     *
     * @return string
     */
    public function getCompanyFilePath(): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_COMPANY_FILE,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get application name
     *
     * @return string
     */
    public function getAppName(): string
    {
        $appName = $this->scopeConfig->getValue(
            self::XML_PATH_APP_NAME,
            ScopeInterface::SCOPE_STORE
        );

        return $appName ?: 'Magento QuickBooks Connector';
    }

    /**
     * Get application description
     *
     * @return string
     */
    public function getAppDescription(): string
    {
        $description = $this->scopeConfig->getValue(
            self::XML_PATH_APP_DESCRIPTION,
            ScopeInterface::SCOPE_STORE
        );

        return $description ?: 'QuickBooks integration for Magento';
    }

    /**
     * Get support URL
     *
     * @return string
     */
    public function getSupportUrl(): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_APP_SUPPORT_URL,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get owner ID
     *
     * @return string
     */
    public function getOwnerId(): string
    {
        $ownerId = $this->scopeConfig->getValue(
            self::XML_PATH_OWNER_ID,
            ScopeInterface::SCOPE_STORE
        );

        return $ownerId ?: '{' . strtoupper(bin2hex(random_bytes(16))) . '}';
    }

    /**
     * Get file ID
     *
     * @return string
     */
    public function getFileId(): string
    {
        $fileId = $this->scopeConfig->getValue(
            self::XML_PATH_FILE_ID,
            ScopeInterface::SCOPE_STORE
        );

        return $fileId ?: '{90A44FB5-33D9-4815-AC85-BC87A7E7D1EB}';
    }

    /**
     * Get run interval in minutes
     *
     * @return int|null
     */
    public function getRunEveryNMinutes(): ?int
    {
        $minutes = $this->scopeConfig->getValue(
            self::XML_PATH_RUN_EVERY_N_MINUTES,
            ScopeInterface::SCOPE_STORE
        );

        return $minutes ? (int) $minutes : null;
    }

    /**
     * Get server version
     *
     * @return string
     */
    public function getServerVersion(): string
    {
        $version = $this->scopeConfig->getValue(
            self::XML_PATH_SERVER_VERSION,
            ScopeInterface::SCOPE_STORE
        );

        return $version ?: '1.0.0';
    }

    /**
     * Get minimum client version
     *
     * @return string
     */
    public function getMinimumClientVersion(): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_MIN_CLIENT_VERSION,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get supported client version
     *
     * @return string
     */
    public function getSupportedClientVersion(): string
    {
        $version = $this->scopeConfig->getValue(
            self::XML_PATH_SUPPORTED_CLIENT_VERSION,
            ScopeInterface::SCOPE_STORE
        );

        return $version ?: '2.3.0.30';
    }

    /**
     * Check if should continue on error
     *
     * @return bool
     */
    public function getContinueOnError(): bool
    {
        return (bool) $this->scopeConfig->getValue(
            self::XML_PATH_CONTINUE_ON_ERROR,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Check if should log requests and responses
     *
     * @return bool
     */
    public function getLogRequestsAndResponses(): bool
    {
        return (bool) $this->scopeConfig->getValue(
            self::XML_PATH_LOG_REQUESTS,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Set custom authenticator callback
     *
     * Cloned from Rails: QBWC.authenticator
     *
     * @param callable $callback
     * @return $this
     */
    public function setAuthenticator(callable $callback): self
    {
        $this->authenticatorCallback = $callback;
        return $this;
    }

    /**
     * Authenticate user and return company file path
     *
     * Cloned from Rails: lib/qbwc/controller.rb:105-111
     *
     * @param string $username
     * @param string $password
     * @return string|null Company file path or null if authentication failed
     */
    public function authenticate(string $username, string $password): ?string
    {
        // Use custom authenticator if provided
        if ($this->authenticatorCallback !== null) {
            return call_user_func($this->authenticatorCallback, $username, $password);
        }

        // Default authentication
        $configUsername = $this->getUsername();
        $configPassword = $this->getPassword();

        if ($username === $configUsername && $password === $configPassword) {
            return $this->getCompanyFilePath();
        }

        return null;
    }

    /**
     * Generate QWC file content
     *
     * Cloned from Rails: lib/qbwc/controller.rb:61-88
     *
     * @param string $appUrl
     * @return string
     */
    public function generateQwcFileContent(string $appUrl): string
    {
        $appName = $this->getAppName();
        $appDescription = $this->getAppDescription();
        $supportUrl = $this->getSupportUrl();
        $username = $this->getUsername();
        $ownerId = $this->getOwnerId();
        $fileId = $this->getFileId();
        $runInterval = $this->getRunEveryNMinutes();

        // Optional scheduler block
        $schedulerBlock = '';
        if ($runInterval !== null) {
            $schedulerBlock = "   <Scheduler>\n      <RunEveryNMinutes>{$runInterval}</RunEveryNMinutes>\n   </Scheduler>\n";
        }

        $qwc = <<<QWC
<QBWCXML>
   <AppName>{$appName}</AppName>
   <AppID></AppID>
   <AppURL>{$appUrl}</AppURL>
   <AppDescription>{$appDescription}</AppDescription>
   <AppSupport>{$supportUrl}</AppSupport>
   <UserName>{$username}</UserName>
   <OwnerID>{$ownerId}</OwnerID>
   <FileID>{$fileId}</FileID>
   <QBType>QBFS</QBType>
   <Style>Document</Style>
   {$schedulerBlock}</QBWCXML>
QWC;

        return $qwc;
    }
}
