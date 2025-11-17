<?php
/**
 * Session Repository
 *
 * Cloned from QBWC Rails gem - Repository pattern for Session persistence
 *
 * @category  Vendor
 * @package   Vendor_QuickbooksConnector
 */
declare(strict_types=1);

namespace Vendor\QuickbooksConnector\Model;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotDeleteException;
use Vendor\QuickbooksConnector\Api\SessionRepositoryInterface;
use Vendor\QuickbooksConnector\Api\Data\SessionInterface;
use Vendor\QuickbooksConnector\Api\Data\SessionInterfaceFactory;
use Vendor\QuickbooksConnector\Model\ResourceModel\Session as SessionResource;
use Vendor\QuickbooksConnector\Model\ResourceModel\Session\CollectionFactory;
use Psr\Log\LoggerInterface;

class SessionRepository implements SessionRepositoryInterface
{
    /**
     * @var SessionResource
     */
    private $resource;

    /**
     * @var SessionInterfaceFactory
     */
    private $sessionFactory;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var SessionInterface[]
     */
    private $instances = [];

    /**
     * Constructor
     *
     * @param SessionResource $resource
     * @param SessionInterfaceFactory $sessionFactory
     * @param CollectionFactory $collectionFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        SessionResource $resource,
        SessionInterfaceFactory $sessionFactory,
        CollectionFactory $collectionFactory,
        LoggerInterface $logger
    ) {
        $this->resource = $resource;
        $this->sessionFactory = $sessionFactory;
        $this->collectionFactory = $collectionFactory;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function save(SessionInterface $session): SessionInterface
    {
        try {
            $this->resource->save($session);

            // Clear cache for this session
            if ($session->getEntityId()) {
                unset($this->instances[$session->getEntityId()]);
            }

            $this->logger->info(
                'Session saved successfully',
                ['ticket' => $session->getTicket(), 'id' => $session->getEntityId()]
            );

            return $session;
        } catch (\Exception $e) {
            $this->logger->error(
                'Error saving session: ' . $e->getMessage(),
                ['ticket' => $session->getTicket()]
            );
            throw new CouldNotSaveException(
                __('Could not save the session: %1', $e->getMessage()),
                $e
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function getById(int $sessionId): SessionInterface
    {
        // Check cache first
        if (isset($this->instances[$sessionId])) {
            return $this->instances[$sessionId];
        }

        $session = $this->sessionFactory->create();
        $this->resource->load($session, $sessionId);

        if (!$session->getEntityId()) {
            throw new NoSuchEntityException(
                __('Session with id "%1" does not exist.', $sessionId)
            );
        }

        // Cache the instance
        $this->instances[$sessionId] = $session;

        return $session;
    }

    /**
     * @inheritDoc
     */
    public function getByTicket(string $ticket): SessionInterface
    {
        $sessionData = $this->resource->getByTicket($ticket);

        if (!$sessionData) {
            throw new NoSuchEntityException(
                __('Session with ticket "%1" does not exist.', $ticket)
            );
        }

        // Check cache by ID
        $sessionId = (int) $sessionData['entity_id'];
        if (isset($this->instances[$sessionId])) {
            return $this->instances[$sessionId];
        }

        $session = $this->sessionFactory->create();
        $session->setData($sessionData);

        // Cache the instance
        $this->instances[$sessionId] = $session;

        return $session;
    }

    /**
     * @inheritDoc
     */
    public function delete(SessionInterface $session): bool
    {
        try {
            $sessionId = $session->getEntityId();
            $this->resource->delete($session);

            // Clear cache
            if ($sessionId) {
                unset($this->instances[$sessionId]);
            }

            $this->logger->info(
                'Session deleted successfully',
                ['ticket' => $session->getTicket(), 'id' => $sessionId]
            );

            return true;
        } catch (\Exception $e) {
            $this->logger->error(
                'Error deleting session: ' . $e->getMessage(),
                ['ticket' => $session->getTicket()]
            );
            throw new CouldNotDeleteException(
                __('Could not delete the session: %1', $e->getMessage()),
                $e
            );
        }
    }

    /**
     * Delete session by ID
     *
     * @param int $sessionId
     * @return bool
     * @throws NoSuchEntityException
     * @throws CouldNotDeleteException
     */
    public function deleteById(int $sessionId): bool
    {
        $session = $this->getById($sessionId);
        return $this->delete($session);
    }

    /**
     * Get active sessions
     *
     * @return SessionInterface[]
     */
    public function getActiveSessions(): array
    {
        $collection = $this->collectionFactory->create();
        $collection->addActiveFilter()
            ->orderByCreatedAt('DESC');

        return $collection->getItems();
    }

    /**
     * Get sessions by company
     *
     * @param string $company
     * @return SessionInterface[]
     */
    public function getByCompany(string $company): array
    {
        $collection = $this->collectionFactory->create();
        $collection->addCompanyFilter($company)
            ->orderByCreatedAt('DESC');

        return $collection->getItems();
    }

    /**
     * Delete old sessions
     *
     * @param int $hours
     * @return int Number of deleted sessions
     */
    public function deleteOldSessions(int $hours = 24): int
    {
        try {
            $deletedCount = $this->resource->deleteOldSessions($hours);

            // Clear all cached instances (simplest approach after bulk delete)
            $this->instances = [];

            $this->logger->info(
                "Deleted {$deletedCount} old sessions (older than {$hours} hours)"
            );

            return $deletedCount;
        } catch (\Exception $e) {
            $this->logger->error(
                'Error deleting old sessions: ' . $e->getMessage()
            );
            throw new CouldNotDeleteException(
                __('Could not delete old sessions: %1', $e->getMessage()),
                $e
            );
        }
    }

    /**
     * Check if session with ticket exists
     *
     * @param string $ticket
     * @return bool
     */
    public function ticketExists(string $ticket): bool
    {
        return $this->resource->ticketExists($ticket);
    }

    /**
     * Get active sessions count
     *
     * @return int
     */
    public function getActiveSessionsCount(): int
    {
        return $this->resource->getActiveSessionsCount();
    }
}
