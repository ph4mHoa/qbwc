<?php
/**
 * Customer Sync Worker - Demonstrates Customer Synchronization from QuickBooks to Magento
 *
 * This worker shows how to:
 * - Query customers from QuickBooks
 * - Handle paginated responses using iterator
 * - Process and sync customer data to Magento
 *
 * Based on Rails QBWC gem example: CustomerTestWorker
 *
 * @category  Sample
 * @package   Sample_QuickbooksDemo
 */
declare(strict_types=1);

namespace Sample\QuickbooksDemo\Model\Worker;

use Vendor\QuickbooksConnector\Model\Worker\AbstractWorker;
use Vendor\QuickbooksConnector\Api\Data\JobInterface;
use Vendor\QuickbooksConnector\Api\Data\SessionInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Psr\Log\LoggerInterface;

class CustomerSyncWorker extends AbstractWorker
{
    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var CustomerInterfaceFactory
     */
    private $customerFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * Constructor
     *
     * @param LoggerInterface $logger
     * @param CustomerRepositoryInterface $customerRepository
     * @param CustomerInterfaceFactory $customerFactory
     * @param StoreManagerInterface $storeManager
     * @param SerializerInterface $serializer
     */
    public function __construct(
        LoggerInterface $logger,
        CustomerRepositoryInterface $customerRepository,
        CustomerInterfaceFactory $customerFactory,
        StoreManagerInterface $storeManager,
        SerializerInterface $serializer
    ) {
        parent::__construct($logger);
        $this->customerRepository = $customerRepository;
        $this->customerFactory = $customerFactory;
        $this->storeManager = $storeManager;
        $this->serializer = $serializer;
    }

    /**
     * Generate QBXML request to query customers from QuickBooks
     *
     * Implements iterator pattern for pagination (100 customers per request)
     *
     * @param JobInterface $job
     * @param SessionInterface $session
     * @param mixed $data
     * @return array
     */
    public function requests(JobInterface $job, SessionInterface $session, $data): array
    {
        $this->logInfo('CustomerSyncWorker: Generating customer query request');

        // Build CustomerQuery request with iterator support
        return [
            [
                'CustomerQueryRq' => [
                    'xml_attributes' => [
                        'requestID' => '1',
                        'iterator' => 'Start'  // Start pagination
                    ],
                    'MaxReturned' => 100,  // Fetch 100 customers per request
                    'ActiveStatus' => 'All'  // Get both active and inactive customers
                ]
            ]
        ];
    }

    /**
     * Handle customer data response from QuickBooks
     *
     * This method demonstrates:
     * - Processing QB customer data
     * - Checking iterator status for pagination
     * - Syncing customers to Magento
     * - Error handling
     *
     * @param array $response QBXML response parsed into array
     * @param SessionInterface $session Current session
     * @param JobInterface $job Current job
     * @param array|null $request Original request
     * @param mixed $data Job data
     * @return void
     */
    public function handleResponse(
        array $response,
        SessionInterface $session,
        JobInterface $job,
        ?array $request,
        $data
    ): void {
        $this->logInfo('CustomerSyncWorker: Processing customer response');

        try {
            // Check if there are more customers to fetch (iterator pattern)
            $iteratorRemainingCount = $response['xml_attributes']['iteratorRemainingCount'] ?? '0';
            $isComplete = $iteratorRemainingCount === '0';

            $this->logInfo("Customers remaining: {$iteratorRemainingCount}");

            // Get customer records from response
            $customers = $response['CustomerRet'] ?? [];

            // Handle single customer (convert to array)
            if (isset($customers['ListID'])) {
                $customers = [$customers];
            }

            $syncedCount = 0;
            $errorCount = 0;

            // Process each customer
            foreach ($customers as $qbCustomer) {
                try {
                    $this->syncCustomer($qbCustomer);
                    $syncedCount++;
                } catch (\Exception $e) {
                    $errorCount++;
                    $this->logError(
                        'Failed to sync customer: ' . $e->getMessage(),
                        [
                            'qb_list_id' => $qbCustomer['ListID'] ?? 'unknown',
                            'qb_name' => $qbCustomer['Name'] ?? 'unknown'
                        ]
                    );
                }
            }

            $this->logInfo(
                "Customer sync batch complete. Synced: {$syncedCount}, Errors: {$errorCount}"
            );

            // If completed, log summary
            if ($isComplete) {
                $this->logInfo('Customer sync job completed successfully');
            } else {
                $this->logInfo('More customers to fetch. Will continue in next iteration.');
            }

        } catch (\Exception $e) {
            $this->logError('CustomerSyncWorker error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Sync a single customer from QuickBooks to Magento
     *
     * @param array $qbCustomer QuickBooks customer data
     * @return void
     * @throws \Exception
     */
    private function syncCustomer(array $qbCustomer): void
    {
        // Extract QB customer data
        $qbListId = $qbCustomer['ListID'] ?? null;
        $qbName = $qbCustomer['Name'] ?? '';
        $qbFullName = $qbCustomer['FullName'] ?? $qbName;
        $qbEmail = $qbCustomer['Email'] ?? null;
        $qbPhone = $qbCustomer['Phone'] ?? null;

        // Log customer info
        $this->logDebug("Processing QB Customer: {$qbListId} - {$qbFullName}");

        if (!$qbListId) {
            throw new \Exception('QuickBooks customer missing ListID');
        }

        // For demonstration purposes, we'll just log the customer data
        // In production, you would:
        // 1. Search for existing customer by QB ListID (stored in custom attribute)
        // 2. Create new customer if not exists
        // 3. Update existing customer if found
        // 4. Sync billing/shipping addresses
        // 5. Handle custom attributes

        $this->logInfo(
            "Customer data from QB",
            [
                'qb_list_id' => $qbListId,
                'name' => $qbFullName,
                'email' => $qbEmail,
                'phone' => $qbPhone
            ]
        );

        // Example: Store QB data in job data for later processing
        // In production implementation, you would create/update Magento customer here
    }

    /**
     * Determine if this job should run
     *
     * You can add conditional logic here, for example:
     * - Only run during certain hours
     * - Only run if certain conditions are met
     * - Skip if recent sync already completed
     *
     * @param JobInterface $job
     * @param SessionInterface $session
     * @param mixed $data
     * @return bool
     */
    public function shouldRun(JobInterface $job, SessionInterface $session, $data): bool
    {
        // Always run for demo purposes
        // In production, you might check:
        // - Last sync timestamp
        // - Business hours
        // - Feature flags
        return true;
    }
}
