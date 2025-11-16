<?php
/**
 * Session Collection
 *
 * Collection for fetching multiple Session records
 *
 * @category  Vendor
 * @package   Vendor_QuickbooksConnector
 */
declare(strict_types=1);

namespace Vendor\QuickbooksConnector\Model\ResourceModel\Session;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Vendor\QuickbooksConnector\Model\Session as SessionModel;
use Vendor\QuickbooksConnector\Model\ResourceModel\Session as SessionResource;

class Collection extends AbstractCollection
{
    /**
     * ID field name
     *
     * @var string
     */
    protected $_idFieldName = 'entity_id';

    /**
     * Event prefix
     *
     * @var string
     */
    protected $_eventPrefix = 'qbwc_session_collection';

    /**
     * Event object
     *
     * @var string
     */
    protected $_eventObject = 'session_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(SessionModel::class, SessionResource::class);
    }

    /**
     * Filter by ticket
     *
     * @param string $ticket
     * @return $this
     */
    public function addTicketFilter(string $ticket): self
    {
        $this->addFieldToFilter('ticket', $ticket);
        return $this;
    }

    /**
     * Filter by company
     *
     * @param string $company
     * @return $this
     */
    public function addCompanyFilter(string $company): self
    {
        $this->addFieldToFilter('company', $company);
        return $this;
    }

    /**
     * Filter by user
     *
     * @param string $user
     * @return $this
     */
    public function addUserFilter(string $user): self
    {
        $this->addFieldToFilter('user', $user);
        return $this;
    }

    /**
     * Filter active sessions (progress < 100)
     *
     * @return $this
     */
    public function addActiveFilter(): self
    {
        $this->addFieldToFilter('progress', ['lt' => 100]);
        return $this;
    }

    /**
     * Filter completed sessions (progress = 100)
     *
     * @return $this
     */
    public function addCompletedFilter(): self
    {
        $this->addFieldToFilter('progress', 100);
        return $this;
    }

    /**
     * Filter sessions with errors
     *
     * @return $this
     */
    public function addErrorFilter(): self
    {
        $this->addFieldToFilter('error', ['notnull' => true]);
        return $this;
    }

    /**
     * Order by creation date
     *
     * @param string $direction
     * @return $this
     */
    public function orderByCreatedAt(string $direction = 'DESC'): self
    {
        $this->setOrder('created_at', $direction);
        return $this;
    }

    /**
     * Order by progress
     *
     * @param string $direction
     * @return $this
     */
    public function orderByProgress(string $direction = 'ASC'): self
    {
        $this->setOrder('progress', $direction);
        return $this;
    }

    /**
     * Get sessions older than specified hours
     *
     * @param int $hours
     * @return $this
     */
    public function addOlderThanFilter(int $hours = 24): self
    {
        $timestamp = date('Y-m-d H:i:s', time() - ($hours * 3600));
        $this->addFieldToFilter('created_at', ['lt' => $timestamp]);
        return $this;
    }
}
