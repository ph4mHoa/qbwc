<?php
/**
 * QBWC SOAP Service Implementation
 *
 * Cloned from QBWC Rails gem - lib/qbwc/controller.rb
 *
 * @category  Vendor
 * @package   Vendor_QuickbooksConnector
 */
declare(strict_types=1);

namespace Vendor\QuickbooksConnector\Model;

use Vendor\QuickbooksConnector\Api\QbwcServiceInterface;
use Vendor\QuickbooksConnector\Api\SessionRepositoryInterface;
use Vendor\QuickbooksConnector\Api\JobRepositoryInterface;
use Vendor\QuickbooksConnector\Api\Data\SessionInterfaceFactory;
use Vendor\QuickbooksConnector\Model\Config;
use Vendor\QuickbooksConnector\Model\Request;
use Psr\Log\LoggerInterface;

class QbwcService implements QbwcServiceInterface
{
    /**
     * Authentication constants
     * Cloned from Rails: lib/qbwc/controller.rb:6-7
     */
    const AUTHENTICATE_NOT_VALID_USER = 'nvu';
    const AUTHENTICATE_NO_WORK = 'none';

    /**
     * @var SessionRepositoryInterface
     */
    private $sessionRepository;

    /**
     * @var JobRepositoryInterface
     */
    private $jobRepository;

    /**
     * @var SessionInterfaceFactory
     */
    private $sessionFactory;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var QbxmlParser
     */
    private $qbxmlParser;

    /**
     * Constructor
     *
     * @param SessionRepositoryInterface $sessionRepository
     * @param JobRepositoryInterface $jobRepository
     * @param SessionInterfaceFactory $sessionFactory
     * @param Config $config
     * @param QbxmlParser $qbxmlParser
     * @param LoggerInterface $logger
     */
    public function __construct(
        SessionRepositoryInterface $sessionRepository,
        JobRepositoryInterface $jobRepository,
        SessionInterfaceFactory $sessionFactory,
        Config $config,
        QbxmlParser $qbxmlParser,
        LoggerInterface $logger
    ) {
        $this->sessionRepository = $sessionRepository;
        $this->jobRepository = $jobRepository;
        $this->sessionFactory = $sessionFactory;
        $this->config = $config;
        $this->qbxmlParser = $qbxmlParser;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     *
     * Cloned from Rails: lib/qbwc/controller.rb:94-96
     */
    public function serverVersion(): string
    {
        $version = $this->config->getServerVersion();
        $this->logger->info("Server version requested: {$version}");
        return $version;
    }

    /**
     * @inheritDoc
     *
     * Cloned from Rails: lib/qbwc/controller.rb:98-100
     */
    public function clientVersion(string $clientVersion): string
    {
        $minVersion = $this->config->getMinimumClientVersion();
        $supportedVersion = $this->config->getSupportedClientVersion();

        if (empty($minVersion) || version_compare($clientVersion, $minVersion, '>=')) {
            $this->logger->info("Client version {$clientVersion} accepted");
            return '';  // Empty string means version is supported
        }

        $this->logger->warning("Client version {$clientVersion} rejected. Minimum: {$minVersion}");
        return "W:Client version {$clientVersion} not supported. Please upgrade to {$supportedVersion} or later.";
    }

    /**
     * @inheritDoc
     *
     * Cloned from Rails: lib/qbwc/controller.rb:102-130
     */
    public function authenticate(string $username, string $password): array
    {
        // Authenticate user
        $companyFilePath = $this->config->authenticate($username, $password);

        if ($companyFilePath === null) {
            $this->logger->info("Authentication of user '{$username}' failed.");
            return ['', self::AUTHENTICATE_NOT_VALID_USER];
        }

        // Create session
        $session = $this->sessionFactory->create();
        $ticket = Session::generateTicket($username, $companyFilePath);
        $session->setTicket($ticket);
        $session->setUser($username);
        $session->setCompany($companyFilePath);
        $session->setProgress(0);

        // Get pending jobs for this company
        $pendingJobs = $this->jobRepository->getPendingJobs($companyFilePath);

        if (empty($pendingJobs)) {
            $this->logger->info(
                "Authentication of user '{$username}' succeeded, but no jobs pending for '{$companyFilePath}'."
            );
            return ['', self::AUTHENTICATE_NO_WORK];
        }

        // Initialize session with pending jobs
        $jobNames = array_map(function ($job) {
            return $job->getName();
        }, $pendingJobs);

        $session->setPendingJobsArray($jobNames);
        $session->setInitialJobCount(count($jobNames));
        $session->setCurrentJob($jobNames[0] ?? null);

        $this->sessionRepository->save($session);

        $this->logger->info(
            "Authentication of user '{$username}' succeeded, " .
            count($pendingJobs) . " jobs pending for '{$companyFilePath}'."
        );

        return [$ticket, $companyFilePath];
    }

    /**
     * @inheritDoc
     *
     * Cloned from Rails: lib/qbwc/controller.rb:132-135
     */
    public function sendRequestXML(
        string $ticket,
        string $hcpResponse,
        string $companyFileName,
        string $qbXMLCountry,
        string $qbXMLMajorVers,
        string $qbXMLMinorVers
    ): string {
        try {
            $session = $this->sessionRepository->getByTicket($ticket);

            // Get the next request from the session
            $request = $this->getNextRequest($session);

            if ($request === null) {
                // No more requests, session is complete
                $this->logger->info("No more requests for session {$ticket}");
                return '';
            }

            $this->logger->info(
                "Sending request from job '{$session->getCurrentJob()}' for session {$ticket}"
            );

            return $request;
        } catch (\Exception $e) {
            $this->logger->error("Error in sendRequestXML: " . $e->getMessage());
            return '';
        }
    }

    /**
     * @inheritDoc
     *
     * Cloned from Rails: lib/qbwc/controller.rb:137-147
     */
    public function receiveResponseXML(
        string $ticket,
        string $response,
        string $hresult,
        string $message
    ): int {
        try {
            $session = $this->sessionRepository->getByTicket($ticket);

            // Handle error response
            if (!empty($hresult)) {
                $this->logger->warning("{$hresult}: {$message}");
                $session->setError($message);
                $session->setStatusCode($hresult);
                $session->setStatusSeverity('Error');
            }

            // Process the response
            $this->processResponse($session, $response);

            // Save session
            $this->sessionRepository->save($session);

            $continueOnError = $this->config->getContinueOnError();

            // Return progress or -1 to stop
            if ($continueOnError || !$session->hasError()) {
                return $session->getProgress();
            } else {
                return -1;  // Stop processing
            }
        } catch (\Exception $e) {
            $this->logger->error("Error in receiveResponseXML: " . $e->getMessage());
            return -1;
        }
    }

    /**
     * @inheritDoc
     *
     * Cloned from Rails: lib/qbwc/controller.rb:149-152
     */
    public function closeConnection(string $ticket): string
    {
        try {
            $session = $this->sessionRepository->getByTicket($ticket);
            $this->sessionRepository->delete($session);

            $this->logger->info("Session {$ticket} closed successfully");

            return 'OK';
        } catch (\Exception $e) {
            $this->logger->error("Error in closeConnection: " . $e->getMessage());
            return 'OK';  // Return OK even on error
        }
    }

    /**
     * @inheritDoc
     *
     * Cloned from Rails: lib/qbwc/controller.rb:154-158
     */
    public function connectionError(string $ticket, string $hresult, string $message): string
    {
        try {
            $this->logger->warning("Connection error - {$hresult}: {$message}");

            $session = $this->sessionRepository->getByTicket($ticket);
            $this->sessionRepository->delete($session);
        } catch (\Exception $e) {
            $this->logger->error("Error in connectionError: " . $e->getMessage());
        }

        return 'done';
    }

    /**
     * @inheritDoc
     *
     * Cloned from Rails: lib/qbwc/controller.rb:160-162
     */
    public function getLastError(string $ticket): string
    {
        try {
            $session = $this->sessionRepository->getByTicket($ticket);
            return $session->getError() ?? '';
        } catch (\Exception $e) {
            $this->logger->error("Error in getLastError: " . $e->getMessage());
            return '';
        }
    }

    /**
     * Get next request for session
     *
     * Cloned from Rails: lib/qbwc/session.rb:72-79 (request_to_send)
     * and lib/qbwc/session.rb:43-57 (next_request)
     *
     * @param \Vendor\QuickbooksConnector\Api\Data\SessionInterface $session
     * @return string|null
     */
    private function getNextRequest($session): ?string
    {
        // Check if session is complete or has error
        if ($session->getProgress() >= 100 || ($session->hasError() && !$this->config->getContinueOnError())) {
            $session->setProgress(100);
            return null;
        }

        $currentJobName = $session->getCurrentJob();
        if (empty($currentJobName)) {
            $session->setProgress(100);
            return null;
        }

        try {
            $job = $this->jobRepository->getByName($currentJobName);
            $worker = $job->getWorker();

            // Get session key for request tracking
            $sessionKey = [$session->getUser(), $session->getCompany()];

            // Get or generate requests for this session
            $requests = $job->getRequestsForSession($sessionKey);
            if (empty($requests)) {
                // Generate new requests from worker
                $jobData = $job->getJobData();
                $requests = $worker->requests($job, $session, $jobData);

                if (!empty($requests)) {
                    $job->setRequestsForSession($sessionKey, $requests);
                    $this->jobRepository->save($job);
                }
            }

            // Get current request index
            $requestIndex = $job->getRequestIndexForSession($sessionKey);

            if (!isset($requests[$requestIndex])) {
                // No more requests for this job, move to next job
                $this->moveToNextJob($session);
                return $this->getNextRequest($session);  // Recursive call
            }

            // Advance to next request for next iteration
            $job->advanceNextRequest($sessionKey);
            $this->jobRepository->save($job);

            // Convert request array to QBXML
            $request = $requests[$requestIndex];
            $qbxml = $this->qbxmlParser->arrayToQbxml($request);

            return $qbxml;
        } catch (\Exception $e) {
            $this->logger->error("Error getting next request: " . $e->getMessage());
            $session->setError($e->getMessage());
            return null;
        }
    }

    /**
     * Process QuickBooks response
     *
     * Cloned from Rails: lib/qbwc/session.rb:81-97 (response=)
     *
     * @param \Vendor\QuickbooksConnector\Api\Data\SessionInterface $session
     * @param string $qbxmlResponse
     * @return void
     */
    private function processResponse($session, string $qbxmlResponse): void
    {
        if (empty($qbxmlResponse)) {
            return;
        }

        try {
            $this->logger->info('Parsing response');

            // Parse QBXML response
            $response = $this->qbxmlParser->qbxmlToArray($qbxmlResponse);

            // Parse response headers
            $this->parseResponseHeader($session, $response);

            // Get current job and let it process the response
            $currentJobName = $session->getCurrentJob();
            if (!empty($currentJobName)) {
                $job = $this->jobRepository->getByName($currentJobName);
                $worker = $job->getWorker();

                // Get session key and current request
                $sessionKey = [$session->getUser(), $session->getCompany()];
                $requests = $job->getRequestsForSession($sessionKey);
                $requestIndex = $job->getRequestIndexForSession($sessionKey) - 1;  // -1 because we already advanced
                $request = $requests[$requestIndex] ?? null;

                // Let worker handle the response
                $jobData = $job->getJobData();
                $worker->handleResponse($response, $session, $job, $request, $jobData);
            }
        } catch (\Exception $e) {
            $session->setError($e->getMessage());
            $this->logger->warning("An error occurred processing response: " . $e->getMessage());
        }
    }

    /**
     * Parse response headers
     *
     * Cloned from Rails: lib/qbwc/session.rb:132-157 (parse_response_header)
     *
     * @param \Vendor\QuickbooksConnector\Api\Data\SessionInterface $session
     * @param array $response
     * @return void
     */
    private function parseResponseHeader($session, array $response): void
    {
        $this->logger->info('Parsing headers');

        // Reset error state
        $session->setIteratorId(null);
        $session->setError(null);
        $session->setStatusCode(null);
        $session->setStatusSeverity(null);

        // Extract response attributes
        $xmlAttributes = $response['xml_attributes'] ?? [];

        if (!empty($xmlAttributes)) {
            $statusCode = $xmlAttributes['statusCode'] ?? null;
            $statusSeverity = $xmlAttributes['statusSeverity'] ?? null;
            $statusMessage = $xmlAttributes['statusMessage'] ?? '';
            $iteratorRemainingCount = $xmlAttributes['iteratorRemainingCount'] ?? 0;
            $iteratorId = $xmlAttributes['iteratorID'] ?? null;

            $this->logger->info(
                "Parsed headers. statusSeverity: '{$statusSeverity}'. statusCode: '{$statusCode}'"
            );

            // Handle errors and warnings
            if ($statusSeverity === 'Error' || $statusSeverity === 'Warn') {
                $errorMsg = "QBWC " . strtoupper($statusSeverity) . ": {$statusCode} - {$statusMessage}";
                $session->setError($errorMsg);
                $session->setStatusCode((string) $statusCode);
                $session->setStatusSeverity($statusSeverity);

                if ($statusSeverity === 'Error') {
                    $this->logger->error($errorMsg);
                } else {
                    $this->logger->warning($errorMsg);
                }
            }

            // Handle iterator
            if ($iteratorRemainingCount > 0 && $statusSeverity !== 'Error') {
                $session->setIteratorId($iteratorId);
            }
        }
    }

    /**
     * Move to next job in the queue
     *
     * Cloned from Rails: lib/qbwc/session.rb:43-57 (next_request logic)
     *
     * @param \Vendor\QuickbooksConnector\Api\Data\SessionInterface $session
     * @return void
     */
    private function moveToNextJob($session): void
    {
        $pendingJobs = $session->getPendingJobsArray();
        array_shift($pendingJobs);  // Remove current job

        if (empty($pendingJobs)) {
            // No more jobs
            $session->setCurrentJob(null);
            $session->setPendingJobsArray([]);
            $session->setProgress(100);
            $this->logger->info('All jobs completed');
        } else {
            // Move to next job
            $session->setPendingJobsArray($pendingJobs);
            $session->setCurrentJob($pendingJobs[0]);

            // Calculate progress
            $session->calculateProgress();

            $this->logger->info("Moved to next job: {$pendingJobs[0]}");
        }
    }
}
