<?php
/**
 * Example Session Initializer - Logging
 *
 * Example implementation showing how to use session_initializer callback
 * Logs session initialization with details
 *
 * @category  Vendor
 * @package   Vendor_QuickbooksConnector
 */
declare(strict_types=1);

namespace Vendor\QuickbooksConnector\Model\Callback\Example;

use Vendor\QuickbooksConnector\Api\SessionInitializerInterface;
use Vendor\QuickbooksConnector\Api\Data\SessionInterface;
use Psr\Log\LoggerInterface;

/**
 * Example callback implementation
 *
 * To enable this callback, add to di.xml:
 * <type name="Vendor\QuickbooksConnector\Model\CallbackManager">
 *     <arguments>
 *         <argument name="sessionInitializers" xsi:type="array">
 *             <item name="logging" xsi:type="object">Vendor\QuickbooksConnector\Model\Callback\Example\LoggingSessionInitializer</item>
 *         </argument>
 *     </arguments>
 * </type>
 */
class LoggingSessionInitializer implements SessionInitializerInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Constructor
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     *
     * Example: Log session start with all details
     */
    public function initialize(SessionInterface $session): void
    {
        $this->logger->info('=== QuickBooks Session Initialized ===', [
            'ticket' => $session->getTicket(),
            'user' => $session->getUser(),
            'company' => $session->getCompany(),
            'pending_jobs' => $session->getPendingJobsArray(),
            'initial_job_count' => $session->getInitialJobCount(),
            'current_job' => $session->getCurrentJob(),
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
}
