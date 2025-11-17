<?php
/**
 * Session Initializer Callback Interface
 *
 * Called AFTER authentication succeeds and jobs are pending
 * Cloned from Rails: QBWC.session_initializer (lib/qbwc.rb:54)
 *
 * @category  Vendor
 * @package   Vendor_QuickbooksConnector
 */
declare(strict_types=1);

namespace Vendor\QuickbooksConnector\Api;

use Vendor\QuickbooksConnector\Api\Data\SessionInterface;

interface SessionInitializerInterface
{
    /**
     * Initialize session after authentication
     *
     * Called in authenticate() AFTER:
     * - Authentication succeeds
     * - Jobs are pending for the company
     * - Session is created and saved
     *
     * Example use cases:
     * - Set custom session data
     * - Log session start
     * - Send notifications
     * - Initialize external systems
     *
     * Cloned from Rails: lib/qbwc/controller.rb:127
     * QBWC.session_initializer.call(session)
     *
     * @param SessionInterface $session The newly created session
     * @return void
     */
    public function initialize(SessionInterface $session): void;
}
