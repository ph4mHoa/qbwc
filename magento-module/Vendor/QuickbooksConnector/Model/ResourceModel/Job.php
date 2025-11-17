<?php
/**
 * Job Resource Model
 *
 * Cloned from QBWC Rails gem - Database layer for Job model
 *
 * @category  Vendor
 * @package   Vendor_QuickbooksConnector
 */
declare(strict_types=1);

namespace Vendor\QuickbooksConnector\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Job extends AbstractDb
{
    /**
     * Table name
     */
    const TABLE_NAME = 'qbwc_jobs';

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
     * Get job by name
     *
     * Cloned from Rails: lib/qbwc/job.rb - find_by methods
     *
     * @param string $name
     * @return array|false
     */
    public function getByName(string $name)
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable())
            ->where('name = ?', $name)
            ->limit(1);

        return $connection->fetchRow($select);
    }

    /**
     * Get pending jobs for company
     *
     * Cloned from Rails: lib/qbwc/job.rb:116-119 - pending_jobs method
     *
     * @param string $company
     * @return array
     */
    public function getPendingJobs(string $company): array
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable())
            ->where('company = ?', $company)
            ->where('enabled = ?', 1)
            ->order('entity_id ASC');

        return $connection->fetchAll($select);
    }

    /**
     * Get enabled jobs count
     *
     * @param string|null $company
     * @return int
     */
    public function getEnabledJobsCount(?string $company = null): int
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable(), 'COUNT(*)')
            ->where('enabled = ?', 1);

        if ($company !== null) {
            $select->where('company = ?', $company);
        }

        return (int) $connection->fetchOne($select);
    }

    /**
     * Get all jobs for company
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
            ->order('name ASC');

        return $connection->fetchAll($select);
    }

    /**
     * Enable job by name
     *
     * @param string $name
     * @return int Number of affected rows
     */
    public function enableByName(string $name): int
    {
        $connection = $this->getConnection();
        return $connection->update(
            $this->getMainTable(),
            ['enabled' => 1],
            ['name = ?' => $name]
        );
    }

    /**
     * Disable job by name
     *
     * @param string $name
     * @return int Number of affected rows
     */
    public function disableByName(string $name): int
    {
        $connection = $this->getConnection();
        return $connection->update(
            $this->getMainTable(),
            ['enabled' => 0],
            ['name = ?' => $name]
        );
    }

    /**
     * Check if job exists
     *
     * @param string $name
     * @return bool
     */
    public function jobExists(string $name): bool
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable(), 'COUNT(*)')
            ->where('name = ?', $name);

        return (int) $connection->fetchOne($select) > 0;
    }

    /**
     * Reset all jobs for company
     *
     * Clears request_index and requests for all jobs
     *
     * @param string $company
     * @return int Number of affected rows
     */
    public function resetJobsForCompany(string $company): int
    {
        $connection = $this->getConnection();
        return $connection->update(
            $this->getMainTable(),
            [
                'request_index' => null,
                'requests' => null
            ],
            ['company = ?' => $company]
        );
    }
}
