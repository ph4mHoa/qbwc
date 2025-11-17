<?php
/**
 * Callback Manager
 *
 * Manages and invokes session lifecycle callbacks
 * Cloned from Rails: QBWC module callbacks (lib/qbwc.rb:54-59, 105-113)
 *
 * @category  Vendor
 * @package   Vendor_QuickbooksConnector
 */
declare(strict_types=1);

namespace Vendor\QuickbooksConnector\Model;

use Vendor\QuickbooksConnector\Api\SessionInitializerInterface;
use Vendor\QuickbooksConnector\Api\SessionCompleteInterface;
use Vendor\QuickbooksConnector\Api\Data\SessionInterface;
use Psr\Log\LoggerInterface;

class CallbackManager
{
    /**
     * @var SessionInitializerInterface[]
     */
    private $sessionInitializers = [];

    /**
     * @var SessionCompleteInterface[]
     */
    private $sessionCompleteHandlers = [];

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Constructor
     *
     * @param LoggerInterface $logger
     * @param array $sessionInitializers Array of SessionInitializerInterface
     * @param array $sessionCompleteHandlers Array of SessionCompleteInterface
     */
    public function __construct(
        LoggerInterface $logger,
        array $sessionInitializers = [],
        array $sessionCompleteHandlers = []
    ) {
        $this->logger = $logger;
        $this->sessionInitializers = $sessionInitializers;
        $this->sessionCompleteHandlers = $sessionCompleteHandlers;
    }

    /**
     * Register session initializer callback
     *
     * Cloned from Rails: lib/qbwc.rb:105-108
     * def set_session_initializer(&block)
     *   @@session_initializer = block
     * end
     *
     * @param SessionInitializerInterface $initializer
     * @return $this
     */
    public function registerSessionInitializer(SessionInitializerInterface $initializer): self
    {
        $this->sessionInitializers[] = $initializer;
        $this->logger->debug('Session initializer registered: ' . get_class($initializer));
        return $this;
    }

    /**
     * Register session complete callback
     *
     * Cloned from Rails: lib/qbwc.rb:110-113
     * def set_session_complete_success(&block)
     *   @@session_complete_success = block
     * end
     *
     * @param SessionCompleteInterface $handler
     * @return $this
     */
    public function registerSessionCompleteHandler(SessionCompleteInterface $handler): self
    {
        $this->sessionCompleteHandlers[] = $handler;
        $this->logger->debug('Session complete handler registered: ' . get_class($handler));
        return $this;
    }

    /**
     * Invoke session initializer callbacks
     *
     * Called AFTER authentication succeeds and jobs are pending
     * Cloned from Rails: lib/qbwc/controller.rb:127
     * QBWC.session_initializer.call(session) unless QBWC.session_initializer.nil?
     *
     * @param SessionInterface $session
     * @return void
     */
    public function invokeSessionInitializers(SessionInterface $session): void
    {
        if (empty($this->sessionInitializers)) {
            $this->logger->debug('No session initializers to invoke');
            return;
        }

        $this->logger->info(
            'Invoking ' . count($this->sessionInitializers) . ' session initializer(s)',
            ['ticket' => $session->getTicket()]
        );

        foreach ($this->sessionInitializers as $initializer) {
            try {
                $this->logger->debug(
                    'Invoking initializer: ' . get_class($initializer),
                    ['ticket' => $session->getTicket()]
                );

                $initializer->initialize($session);

                $this->logger->debug(
                    'Initializer completed: ' . get_class($initializer)
                );
            } catch (\Exception $e) {
                // Log error but continue with other callbacks
                $this->logger->error(
                    'Error in session initializer ' . get_class($initializer) . ': ' . $e->getMessage(),
                    [
                        'ticket' => $session->getTicket(),
                        'exception' => $e
                    ]
                );
            }
        }
    }

    /**
     * Invoke session complete callbacks
     *
     * Called WHEN session completes all jobs without errors
     * Cloned from Rails: lib/qbwc/session.rb:128-130
     * def complete_with_success
     *   QBWC.session_complete_success.call(self) if QBWC.session_complete_success
     * end
     *
     * @param SessionInterface $session
     * @return void
     */
    public function invokeSessionComplete(SessionInterface $session): void
    {
        if (empty($this->sessionCompleteHandlers)) {
            $this->logger->debug('No session complete handlers to invoke');
            return;
        }

        $this->logger->info(
            'Invoking ' . count($this->sessionCompleteHandlers) . ' session complete handler(s)',
            ['ticket' => $session->getTicket(), 'progress' => $session->getProgress()]
        );

        foreach ($this->sessionCompleteHandlers as $handler) {
            try {
                $this->logger->debug(
                    'Invoking complete handler: ' . get_class($handler),
                    ['ticket' => $session->getTicket()]
                );

                $handler->onComplete($session);

                $this->logger->debug(
                    'Complete handler finished: ' . get_class($handler)
                );
            } catch (\Exception $e) {
                // Log error but continue with other callbacks
                $this->logger->error(
                    'Error in session complete handler ' . get_class($handler) . ': ' . $e->getMessage(),
                    [
                        'ticket' => $session->getTicket(),
                        'exception' => $e
                    ]
                );
            }
        }
    }

    /**
     * Check if any initializers are registered
     *
     * @return bool
     */
    public function hasSessionInitializers(): bool
    {
        return !empty($this->sessionInitializers);
    }

    /**
     * Check if any complete handlers are registered
     *
     * @return bool
     */
    public function hasSessionCompleteHandlers(): bool
    {
        return !empty($this->sessionCompleteHandlers);
    }

    /**
     * Get count of registered initializers
     *
     * @return int
     */
    public function getInitializerCount(): int
    {
        return count($this->sessionInitializers);
    }

    /**
     * Get count of registered complete handlers
     *
     * @return int
     */
    public function getCompleteHandlerCount(): int
    {
        return count($this->sessionCompleteHandlers);
    }
}
