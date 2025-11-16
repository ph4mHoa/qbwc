<?php
/**
 * Job Repository
 *
 * Cloned from QBWC Rails gem - Repository pattern for Job persistence
 *
 * @category  Vendor
 * @package   Vendor_QuickbooksConnector
 */
declare(strict_types=1);

namespace Vendor\QuickbooksConnector\Model;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Vendor\QuickbooksConnector\Api\JobRepositoryInterface;
use Vendor\QuickbooksConnector\Api\Data\JobInterface;
use Vendor\QuickbooksConnector\Api\Data\JobInterfaceFactory;
use Vendor\QuickbooksConnector\Model\ResourceModel\Job as JobResource;
use Vendor\QuickbooksConnector\Model\ResourceModel\Job\CollectionFactory;
use Psr\Log\LoggerInterface;

class JobRepository implements JobRepositoryInterface
{
    /**
     * @var JobResource
     */
    private $resource;

    /**
     * @var JobInterfaceFactory
     */
    private $jobFactory;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var SearchResultsInterfaceFactory
     */
    private $searchResultsFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var JobInterface[]
     */
    private $instances = [];

    /**
     * @var JobInterface[]
     */
    private $instancesByName = [];

    /**
     * Constructor
     *
     * @param JobResource $resource
     * @param JobInterfaceFactory $jobFactory
     * @param CollectionFactory $collectionFactory
     * @param SearchResultsInterfaceFactory $searchResultsFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        JobResource $resource,
        JobInterfaceFactory $jobFactory,
        CollectionFactory $collectionFactory,
        SearchResultsInterfaceFactory $searchResultsFactory,
        LoggerInterface $logger
    ) {
        $this->resource = $resource;
        $this->jobFactory = $jobFactory;
        $this->collectionFactory = $collectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function save(JobInterface $job): JobInterface
    {
        try {
            $this->resource->save($job);

            // Clear cache for this job
            if ($job->getEntityId()) {
                unset($this->instances[$job->getEntityId()]);
            }
            if ($job->getName()) {
                unset($this->instancesByName[$job->getName()]);
            }

            $this->logger->info(
                'Job saved successfully',
                ['name' => $job->getName(), 'id' => $job->getEntityId()]
            );

            return $job;
        } catch (\Exception $e) {
            $this->logger->error(
                'Error saving job: ' . $e->getMessage(),
                ['name' => $job->getName()]
            );
            throw new CouldNotSaveException(
                __('Could not save the job: %1', $e->getMessage()),
                $e
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function getById(int $jobId): JobInterface
    {
        // Check cache first
        if (isset($this->instances[$jobId])) {
            return $this->instances[$jobId];
        }

        $job = $this->jobFactory->create();
        $this->resource->load($job, $jobId);

        if (!$job->getEntityId()) {
            throw new NoSuchEntityException(
                __('Job with id "%1" does not exist.', $jobId)
            );
        }

        // Cache the instance
        $this->instances[$jobId] = $job;
        if ($job->getName()) {
            $this->instancesByName[$job->getName()] = $job;
        }

        return $job;
    }

    /**
     * @inheritDoc
     */
    public function getByName(string $name): JobInterface
    {
        // Check cache first
        if (isset($this->instancesByName[$name])) {
            return $this->instancesByName[$name];
        }

        $jobData = $this->resource->getByName($name);

        if (!$jobData) {
            throw new NoSuchEntityException(
                __('Job with name "%1" does not exist.', $name)
            );
        }

        $job = $this->jobFactory->create();
        $job->setData($jobData);

        // Cache the instance
        $jobId = (int) $job->getEntityId();
        $this->instances[$jobId] = $job;
        $this->instancesByName[$name] = $job;

        return $job;
    }

    /**
     * @inheritDoc
     */
    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultsInterface
    {
        $collection = $this->collectionFactory->create();

        // Apply filters
        foreach ($searchCriteria->getFilterGroups() as $filterGroup) {
            foreach ($filterGroup->getFilters() as $filter) {
                $condition = $filter->getConditionType() ?: 'eq';
                $collection->addFieldToFilter(
                    $filter->getField(),
                    [$condition => $filter->getValue()]
                );
            }
        }

        // Apply sorting
        $sortOrders = $searchCriteria->getSortOrders();
        if ($sortOrders) {
            foreach ($sortOrders as $sortOrder) {
                $collection->addOrder(
                    $sortOrder->getField(),
                    $sortOrder->getDirection()
                );
            }
        }

        // Apply pagination
        $collection->setCurPage($searchCriteria->getCurrentPage());
        $collection->setPageSize($searchCriteria->getPageSize());

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($collection->getSize());

        return $searchResults;
    }

    /**
     * @inheritDoc
     */
    public function getPendingJobs(string $company): array
    {
        $collection = $this->collectionFactory->create();
        $collection->getPendingJobs($company);

        return $collection->getItems();
    }

    /**
     * @inheritDoc
     */
    public function delete(JobInterface $job): bool
    {
        try {
            $jobId = $job->getEntityId();
            $jobName = $job->getName();

            $this->resource->delete($job);

            // Clear cache
            if ($jobId) {
                unset($this->instances[$jobId]);
            }
            if ($jobName) {
                unset($this->instancesByName[$jobName]);
            }

            $this->logger->info(
                'Job deleted successfully',
                ['name' => $jobName, 'id' => $jobId]
            );

            return true;
        } catch (\Exception $e) {
            $this->logger->error(
                'Error deleting job: ' . $e->getMessage(),
                ['name' => $job->getName()]
            );
            throw new CouldNotDeleteException(
                __('Could not delete the job: %1', $e->getMessage()),
                $e
            );
        }
    }

    /**
     * Delete job by ID
     *
     * @param int $jobId
     * @return bool
     * @throws NoSuchEntityException
     * @throws CouldNotDeleteException
     */
    public function deleteById(int $jobId): bool
    {
        $job = $this->getById($jobId);
        return $this->delete($job);
    }

    /**
     * Delete job by name
     *
     * @param string $name
     * @return bool
     * @throws NoSuchEntityException
     * @throws CouldNotDeleteException
     */
    public function deleteByName(string $name): bool
    {
        $job = $this->getByName($name);
        return $this->delete($job);
    }

    /**
     * Get jobs by company
     *
     * @param string $company
     * @return JobInterface[]
     */
    public function getByCompany(string $company): array
    {
        $collection = $this->collectionFactory->create();
        $collection->addCompanyFilter($company)
            ->orderByName('ASC');

        return $collection->getItems();
    }

    /**
     * Get enabled jobs
     *
     * @param string|null $company
     * @return JobInterface[]
     */
    public function getEnabledJobs(?string $company = null): array
    {
        $collection = $this->collectionFactory->create();
        $collection->addEnabledFilter();

        if ($company !== null) {
            $collection->addCompanyFilter($company);
        }

        $collection->orderByName('ASC');

        return $collection->getItems();
    }

    /**
     * Enable job by name
     *
     * @param string $name
     * @return bool
     */
    public function enableJob(string $name): bool
    {
        try {
            $affected = $this->resource->enableByName($name);

            // Clear cache
            unset($this->instancesByName[$name]);

            $this->logger->info("Job '{$name}' enabled successfully");

            return $affected > 0;
        } catch (\Exception $e) {
            $this->logger->error("Error enabling job '{$name}': " . $e->getMessage());
            return false;
        }
    }

    /**
     * Disable job by name
     *
     * @param string $name
     * @return bool
     */
    public function disableJob(string $name): bool
    {
        try {
            $affected = $this->resource->disableByName($name);

            // Clear cache
            unset($this->instancesByName[$name]);

            $this->logger->info("Job '{$name}' disabled successfully");

            return $affected > 0;
        } catch (\Exception $e) {
            $this->logger->error("Error disabling job '{$name}': " . $e->getMessage());
            return false;
        }
    }

    /**
     * Reset all jobs for company
     *
     * @param string $company
     * @return int Number of affected jobs
     */
    public function resetJobsForCompany(string $company): int
    {
        try {
            $affected = $this->resource->resetJobsForCompany($company);

            // Clear all caches (simplest approach after bulk update)
            $this->instances = [];
            $this->instancesByName = [];

            $this->logger->info("Reset {$affected} jobs for company '{$company}'");

            return $affected;
        } catch (\Exception $e) {
            $this->logger->error("Error resetting jobs for company '{$company}': " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Check if job exists
     *
     * @param string $name
     * @return bool
     */
    public function jobExists(string $name): bool
    {
        return $this->resource->jobExists($name);
    }
}
