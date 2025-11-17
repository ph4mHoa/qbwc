<?php
/**
 * Session Complete Success Callback Interface
 *
 * Called WHEN session completes all jobs without errors
 * Cloned from Rails: QBWC.session_complete_success (lib/qbwc.rb:58)
 *
 * @category  Vendor
 * @package   Vendor_QuickbooksConnector
 */
declare(strict_types=1);

namespace Vendor\QuickbooksConnector\Api;

use Vendor\QuickbooksConnector\Api\Data\SessionInterface;

interface SessionCompleteInterface
{
    /**
     * Handle session completion
     *
     * Called WHEN:
     * - Session progress reaches 100%
     * - All jobs completed
     * - No errors occurred
     *
     * Example use cases:
     * - Log completion
     * - Send success notifications
     * - Update external systems
     * - Generate reports
     * - Cleanup temporary data
     *
     * Cloned from Rails: lib/qbwc/session.rb:128-130
     * def complete_with_success
     *   QBWC.session_complete_success.call(self) if QBWC.session_complete_success
     * end
     *
     * @param SessionInterface $session The completed session
     * @return void
     */
    public function onComplete(SessionInterface $session): void;
}
