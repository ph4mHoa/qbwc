<?php
/**
 * Example Session Complete Handler - Notifications
 *
 * Example implementation showing how to use session_complete_success callback
 * Sends notification when session completes successfully
 *
 * @category  Vendor
 * @package   Vendor_QuickbooksConnector
 */
declare(strict_types=1);

namespace Vendor\QuickbooksConnector\Model\Callback\Example;

use Vendor\QuickbooksConnector\Api\SessionCompleteInterface;
use Vendor\QuickbooksConnector\Api\Data\SessionInterface;
use Psr\Log\LoggerInterface;

/**
 * Example callback implementation
 *
 * To enable this callback, add to di.xml:
 * <type name="Vendor\QuickbooksConnector\Model\CallbackManager">
 *     <arguments>
 *         <argument name="sessionCompleteHandlers" xsi:type="array">
 *             <item name="notification" xsi:type="object">Vendor\QuickbooksConnector\Model\Callback\Example\NotificationSessionComplete</item>
 *         </argument>
 *     </arguments>
 * </type>
 */
class NotificationSessionComplete implements SessionCompleteInterface
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
     * Example: Log completion and send notification
     * In production, you might:
     * - Send email notification
     * - Update dashboard
     * - Trigger webhooks
     * - Generate reports
     */
    public function onComplete(SessionInterface $session): void
    {
        $this->logger->info('=== QuickBooks Session Completed Successfully ===', [
            'ticket' => $session->getTicket(),
            'user' => $session->getUser(),
            'company' => $session->getCompany(),
            'progress' => $session->getProgress(),
            'initial_job_count' => $session->getInitialJobCount(),
            'timestamp' => date('Y-m-d H:i:s')
        ]);

        // Example: Send notification (implement your own notification logic)
        // $this->sendNotification($session);

        // Example: Update statistics
        // $this->updateStatistics($session);

        // Example: Cleanup temporary data
        // $this->cleanupTempData($session);
    }
}
