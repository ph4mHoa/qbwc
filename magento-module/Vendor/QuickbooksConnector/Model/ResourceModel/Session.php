<?php
/**
 * Session Resource Model
 *
 * Cloned from QBWC Rails gem - Database layer for Session model
 *
 * @category  Vendor
 * @package   Vendor_QuickbooksConnector
 */
declare(strict_types=1);

namespace Vendor\QuickbooksConnector\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Session extends AbstractDb
{
    /**
     * Table name
     */
    const TABLE_NAME = 'qbwc_sessions';

    /**
     * Primary key field
     */
    const ID_FIELD_NAME = 'entity_id';

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(self::TABLE_NAME, self::ID_FIELD_NAME);
    }

    /**
     * Get session by ticket
     *
     * @param string $ticket
     * @return array|false
     */
    public function getByTicket(string $ticket)
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable())
            ->where('ticket = ?', $ticket)
            ->limit(1);

        return $connection->fetchRow($select);
    }

    /**
     * Delete old sessions
     *
     * Cleanup sessions older than specified hours
     *
     * @param int $hours
     * @return int Number of deleted rows
     */
    public function deleteOldSessions(int $hours = 24): int
    {
        $connection = $this->getConnection();
        $timestamp = date('Y-m-d H:i:s', time() - ($hours * 3600));

        return $connection->delete(
            $this->getMainTable(),
            ['created_at < ?' => $timestamp]
        );
    }

    /**
     * Get active sessions count
     *
     * @return int
     */
    public function getActiveSessionsCount(): int
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable(), 'COUNT(*)')
            ->where('progress < ?', 100);

        return (int) $connection->fetchOne($select);
    }

    /**
     * Get sessions by company
     *
     * @param string $company
     * @return array
     */
    public function getByCompany(string $company): array
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable())
            ->where('company = ?', $company)
            ->order('created_at DESC');

        return $connection->fetchAll($select);
    }

    /**
     * Check if ticket exists
     *
     * @param string $ticket
     * @return bool
     */
    public function ticketExists(string $ticket): bool
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable(), 'COUNT(*)')
            ->where('ticket = ?', $ticket);

        return (int) $connection->fetchOne($select) > 0;
    }
}
