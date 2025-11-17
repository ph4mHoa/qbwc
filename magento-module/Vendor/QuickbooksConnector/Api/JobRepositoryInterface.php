<?php
/**
 * Job Repository Interface
 *
 * @category  Vendor
 * @package   Vendor_QuickbooksConnector
 */
declare(strict_types=1);

namespace Vendor\QuickbooksConnector\Api;

use Vendor\QuickbooksConnector\Api\Data\JobInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotDeleteException;

interface JobRepositoryInterface
{
    /**
     * Save job
     *
     * @param JobInterface $job
     * @return JobInterface
     * @throws CouldNotSaveException
     */
    public function save(JobInterface $job): JobInterface;

    /**
     * Get job by ID
     *
     * @param int $jobId
     * @return JobInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $jobId): JobInterface;

    /**
     * Get job by name
     *
     * @param string $name
     * @return JobInterface
     * @throws NoSuchEntityException
     */
    public function getByName(string $name): JobInterface;

    /**
     * Get list of all jobs
     *
     * @return JobInterface[]
     */
    public function getList(): array;

    /**
     * Get pending jobs for company
     *
     * @param string $company
     * @return JobInterface[]
     */
    public function getPendingJobs(string $company): array;

    /**
     * Delete job
     *
     * @param JobInterface $job
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(JobInterface $job): bool;

    /**
     * Delete job by name
     *
     * @param string $name
     * @return bool
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     */
    public function deleteByName(string $name): bool;
}
