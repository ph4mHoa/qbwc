<?php
/**
 * Job Manager Interface - Helper service for managing QuickBooks jobs
 *
 * @category  Sample
 * @package   Sample_QuickbooksDemo
 */
declare(strict_types=1);

namespace Sample\QuickbooksDemo\Api;

use Vendor\QuickbooksConnector\Api\Data\JobInterface;

interface JobManagerInterface
{
    /**
     * Create a customer sync job
     *
     * @param string $companyFile QuickBooks company file path
     * @param bool $enabled Enable job immediately
     * @return JobInterface
     */
    public function createCustomerSyncJob(string $companyFile, bool $enabled = true): JobInterface;

    /**
     * Create an invoice sync job
     *
     * @param string $companyFile QuickBooks company file path
     * @param bool $enabled Enable job immediately
     * @param string|null $dateFrom Optional start date (YYYY-MM-DD)
     * @param string|null $dateTo Optional end date (YYYY-MM-DD)
     * @return JobInterface
     */
    public function createInvoiceSyncJob(
        string $companyFile,
        bool $enabled = true,
        ?string $dateFrom = null,
        ?string $dateTo = null
    ): JobInterface;

    /**
     * Create a product query job
     *
     * @param string $companyFile QuickBooks company file path
     * @param bool $enabled Enable job immediately
     * @param bool $force Force run even on weekdays
     * @return JobInterface
     */
    public function createProductQueryJob(
        string $companyFile,
        bool $enabled = true,
        bool $force = false
    ): JobInterface;

    /**
     * Get all jobs for a company
     *
     * @param string $companyFile QuickBooks company file path
     * @return JobInterface[]
     */
    public function getJobsByCompany(string $companyFile): array;

    /**
     * Enable a job by name
     *
     * @param string $jobName
     * @return bool
     */
    public function enableJob(string $jobName): bool;

    /**
     * Disable a job by name
     *
     * @param string $jobName
     * @return bool
     */
    public function disableJob(string $jobName): bool;

    /**
     * Delete a job by name
     *
     * @param string $jobName
     * @return bool
     */
    public function deleteJob(string $jobName): bool;

    /**
     * List all jobs
     *
     * @return JobInterface[]
     */
    public function listAllJobs(): array;
}
