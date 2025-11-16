<?php
/**
 * Abstract Worker Base Class
 *
 * @category  Vendor
 * @package   Vendor_QuickbooksConnector
 */
declare(strict_types=1);

namespace Vendor\QuickbooksConnector\Model\Worker;

use Vendor\QuickbooksConnector\Api\Data\JobInterface;
use Vendor\QuickbooksConnector\Api\Data\SessionInterface;
use Psr\Log\LoggerInterface;

abstract class AbstractWorker
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Constructor
     *
     * @param LoggerInterface $logger
     */
    public function __construct(
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
    }

    /**
     * Generate QBXML requests
     *
     * This method should return an array of QBXML requests to be sent to QuickBooks.
     * Each request should be an associative array that will be converted to QBXML.
     *
     * Example:
     * return [
     *     [
     *         'CustomerQueryRq' => [
     *             'xml_attributes' => ['requestID' => '1', 'iterator' => 'Start'],
     *             'MaxReturned' => 100
     *         ]
     *     ]
     * ];
     *
     * @param JobInterface $job
     * @param SessionInterface $session
     * @param mixed $data Job data
     * @return array
     */
    abstract public function requests(JobInterface $job, SessionInterface $session, $data): array;

    /**
     * Determine if this job should run
     *
     * Override this method to add conditional logic for job execution.
     * Return false to skip this job for the current session.
     *
     * @param JobInterface $job
     * @param SessionInterface $session
     * @param mixed $data Job data
     * @return bool
     */
    public function shouldRun(JobInterface $job, SessionInterface $session, $data): bool
    {
        return true;
    }

    /**
     * Handle QBXML response from QuickBooks
     *
     * This method is called after receiving a response from QuickBooks.
     * Implement your business logic here to process the response data.
     *
     * @param array $response Parsed QBXML response
     * @param SessionInterface $session Current session
     * @param JobInterface $job Current job
     * @param array|null $request Original request that generated this response
     * @param mixed $data Job data
     * @return void
     */
    abstract public function handleResponse(
        array $response,
        SessionInterface $session,
        JobInterface $job,
        ?array $request,
        $data
    ): void;

    /**
     * Log info message
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    protected function logInfo(string $message, array $context = []): void
    {
        $this->logger->info($message, $context);
    }

    /**
     * Log warning message
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    protected function logWarning(string $message, array $context = []): void
    {
        $this->logger->warning($message, $context);
    }

    /**
     * Log error message
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    protected function logError(string $message, array $context = []): void
    {
        $this->logger->error($message, $context);
    }

    /**
     * Log debug message
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    protected function logDebug(string $message, array $context = []): void
    {
        $this->logger->debug($message, $context);
    }
}
