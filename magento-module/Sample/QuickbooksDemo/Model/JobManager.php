<?php
/**
 * Job Manager - Helper service for managing QuickBooks jobs
 *
 * This class demonstrates how to:
 * - Create jobs programmatically
 * - Configure worker classes
 * - Pass data to workers
 * - Manage job lifecycle
 *
 * Based on Rails QBWC gem: QBWC.add_job()
 *
 * @category  Sample
 * @package   Sample_QuickbooksDemo
 */
declare(strict_types=1);

namespace Sample\QuickbooksDemo\Model;

use Sample\QuickbooksDemo\Api\JobManagerInterface;
use Vendor\QuickbooksConnector\Api\JobRepositoryInterface;
use Vendor\QuickbooksConnector\Api\Data\JobInterface;
use Vendor\QuickbooksConnector\Api\Data\JobInterfaceFactory;
use Vendor\QuickbooksConnector\Model\ResourceModel\Job\CollectionFactory;
use Magento\Framework\Serialize\SerializerInterface;
use Psr\Log\LoggerInterface;

class JobManager implements JobManagerInterface
{
    /**
     * @var JobRepositoryInterface
     */
    private $jobRepository;

    /**
     * @var JobInterfaceFactory
     */
    private $jobFactory;

    /**
     * @var CollectionFactory
     */
    private $jobCollectionFactory;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Constructor
     *
     * @param JobRepositoryInterface $jobRepository
     * @param JobInterfaceFactory $jobFactory
     * @param CollectionFactory $jobCollectionFactory
     * @param SerializerInterface $serializer
     * @param LoggerInterface $logger
     */
    public function __construct(
        JobRepositoryInterface $jobRepository,
        JobInterfaceFactory $jobFactory,
        CollectionFactory $jobCollectionFactory,
        SerializerInterface $serializer,
        LoggerInterface $logger
    ) {
        $this->jobRepository = $jobRepository;
        $this->jobFactory = $jobFactory;
        $this->jobCollectionFactory = $jobCollectionFactory;
        $this->serializer = $serializer;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function createCustomerSyncJob(string $companyFile, bool $enabled = true): JobInterface
    {
        $this->logger->info('Creating customer sync job', ['company' => $companyFile]);

        /** @var JobInterface $job */
        $job = $this->jobFactory->create();

        // Set job properties
        $job->setName('sample_customer_sync')
            ->setCompany($companyFile)
            ->setWorkerClass(\Sample\QuickbooksDemo\Model\Worker\CustomerSyncWorker::class)
            ->setEnabled($enabled)
            ->setRequestsProvidedWhenJobAdded(false);  // Worker generates requests dynamically

        // Save job
        return $this->jobRepository->save($job);
    }

    /**
     * @inheritDoc
     */
    public function createInvoiceSyncJob(
        string $companyFile,
        bool $enabled = true,
        ?string $dateFrom = null,
        ?string $dateTo = null
    ): JobInterface {
        $this->logger->info('Creating invoice sync job', [
            'company' => $companyFile,
            'date_from' => $dateFrom,
            'date_to' => $dateTo
        ]);

        /** @var JobInterface $job */
        $job = $this->jobFactory->create();

        // Prepare job data (pass date range to worker)
        $jobData = [];
        if ($dateFrom) {
            $jobData['date_from'] = $dateFrom;
        }
        if ($dateTo) {
            $jobData['date_to'] = $dateTo;
        }

        $job->setName('sample_invoice_sync')
            ->setCompany($companyFile)
            ->setWorkerClass(\Sample\QuickbooksDemo\Model\Worker\InvoiceSyncWorker::class)
            ->setEnabled($enabled)
            ->setRequestsProvidedWhenJobAdded(false);

        // Set job data if provided
        if (!empty($jobData)) {
            $job->setData($this->serializer->serialize($jobData));
        }

        return $this->jobRepository->save($job);
    }

    /**
     * @inheritDoc
     */
    public function createProductQueryJob(
        string $companyFile,
        bool $enabled = true,
        bool $force = false
    ): JobInterface {
        $this->logger->info('Creating product query job', [
            'company' => $companyFile,
            'force' => $force
        ]);

        /** @var JobInterface $job */
        $job = $this->jobFactory->create();

        // Prepare job data
        $jobData = ['force' => $force];

        $job->setName('sample_product_query')
            ->setCompany($companyFile)
            ->setWorkerClass(\Sample\QuickbooksDemo\Model\Worker\ProductQueryWorker::class)
            ->setEnabled($enabled)
            ->setRequestsProvidedWhenJobAdded(false)
            ->setData($this->serializer->serialize($jobData));

        return $this->jobRepository->save($job);
    }

    /**
     * @inheritDoc
     */
    public function getJobsByCompany(string $companyFile): array
    {
        $collection = $this->jobCollectionFactory->create();
        $collection->addFieldToFilter('company', $companyFile);

        return $collection->getItems();
    }

    /**
     * @inheritDoc
     */
    public function enableJob(string $jobName): bool
    {
        try {
            $job = $this->jobRepository->getByName($jobName);
            $job->setEnabled(true);
            $this->jobRepository->save($job);

            $this->logger->info("Job enabled: {$jobName}");
            return true;
        } catch (\Exception $e) {
            $this->logger->error("Failed to enable job {$jobName}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function disableJob(string $jobName): bool
    {
        try {
            $job = $this->jobRepository->getByName($jobName);
            $job->setEnabled(false);
            $this->jobRepository->save($job);

            $this->logger->info("Job disabled: {$jobName}");
            return true;
        } catch (\Exception $e) {
            $this->logger->error("Failed to disable job {$jobName}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function deleteJob(string $jobName): bool
    {
        try {
            $job = $this->jobRepository->getByName($jobName);
            $this->jobRepository->delete($job);

            $this->logger->info("Job deleted: {$jobName}");
            return true;
        } catch (\Exception $e) {
            $this->logger->error("Failed to delete job {$jobName}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function listAllJobs(): array
    {
        $collection = $this->jobCollectionFactory->create();
        return $collection->getItems();
    }
}
