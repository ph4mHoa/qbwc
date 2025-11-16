<?php
/**
 * Session Repository Interface
 *
 * @category  Vendor
 * @package   Vendor_QuickbooksConnector
 */
declare(strict_types=1);

namespace Vendor\QuickbooksConnector\Api;

use Vendor\QuickbooksConnector\Api\Data\SessionInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotDeleteException;

interface SessionRepositoryInterface
{
    /**
     * Save session
     *
     * @param SessionInterface $session
     * @return SessionInterface
     * @throws CouldNotSaveException
     */
    public function save(SessionInterface $session): SessionInterface;

    /**
     * Get session by ID
     *
     * @param int $sessionId
     * @return SessionInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $sessionId): SessionInterface;

    /**
     * Get session by ticket
     *
     * @param string $ticket
     * @return SessionInterface
     * @throws NoSuchEntityException
     */
    public function getByTicket(string $ticket): SessionInterface;

    /**
     * Delete session
     *
     * @param SessionInterface $session
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(SessionInterface $session): bool;

    /**
     * Delete session by ID
     *
     * @param int $sessionId
     * @return bool
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     */
    public function deleteById(int $sessionId): bool;
}
