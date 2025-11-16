<?php
/**
 * Job Collection
 *
 * Collection for fetching multiple Job records
 * Cloned from QBWC Rails gem - ActiveRecord scopes
 *
 * @category  Vendor
 * @package   Vendor_QuickbooksConnector
 */
declare(strict_types=1);

namespace Vendor\QuickbooksConnector\Model\ResourceModel\Job;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Vendor\QuickbooksConnector\Model\Job as JobModel;
use Vendor\QuickbooksConnector\Model\ResourceModel\Job as JobResource;

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
    protected $_eventPrefix = 'qbwc_job_collection';

    /**
     * Event object
     *
     * @var string
     */
    protected $_eventObject = 'job_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(JobModel::class, JobResource::class);
    }

    /**
     * Filter by name
     *
     * @param string $name
     * @return $this
     */
    public function addNameFilter(string $name): self
    {
        $this->addFieldToFilter('name', $name);
        return $this;
    }

    /**
     * Filter by company
     *
     * Cloned from Rails: lib/qbwc/job.rb - scopes
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
     * Filter by worker class
     *
     * @param string $workerClass
     * @return $this
     */
    public function addWorkerClassFilter(string $workerClass): self
    {
        $this->addFieldToFilter('worker_class', $workerClass);
        return $this;
    }

    /**
     * Filter enabled jobs
     *
     * Cloned from Rails: lib/qbwc/job.rb:116-119 - pending_jobs scope
     *
     * @return $this
     */
    public function addEnabledFilter(): self
    {
        $this->addFieldToFilter('enabled', 1);
        return $this;
    }

    /**
     * Filter disabled jobs
     *
     * @return $this
     */
    public function addDisabledFilter(): self
    {
        $this->addFieldToFilter('enabled', 0);
        return $this;
    }

    /**
     * Get pending jobs for company
     *
     * Cloned from Rails: lib/qbwc/job.rb:116-119
     *
     * @param string $company
     * @return $this
     */
    public function getPendingJobs(string $company): self
    {
        $this->addCompanyFilter($company);
        $this->addEnabledFilter();
        $this->setOrder('entity_id', 'ASC');
        return $this;
    }

    /**
     * Order by name
     *
     * @param string $direction
     * @return $this
     */
    public function orderByName(string $direction = 'ASC'): self
    {
        $this->setOrder('name', $direction);
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
     * Filter jobs with requests provided when added
     *
     * @return $this
     */
    public function addRequestsProvidedFilter(): self
    {
        $this->addFieldToFilter('requests_provided_when_job_added', 1);
        return $this;
    }

    /**
     * Filter jobs without requests
     *
     * @return $this
     */
    public function addNoRequestsFilter(): self
    {
        $this->addFieldToFilter('requests', ['null' => true]);
        return $this;
    }
}
