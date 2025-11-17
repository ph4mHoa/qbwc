<?php
/**
 * Invoice Sync Worker - Demonstrates Invoice/Order Synchronization from QuickBooks to Magento
 *
 * This worker shows how to:
 * - Query invoices from QuickBooks
 * - Filter invoices by date range
 * - Handle invoice line items
 * - Process payment information
 *
 * @category  Sample
 * @package   Sample_QuickbooksDemo
 */
declare(strict_types=1);

namespace Sample\QuickbooksDemo\Model\Worker;

use Vendor\QuickbooksConnector\Model\Worker\AbstractWorker;
use Vendor\QuickbooksConnector\Api\Data\JobInterface;
use Vendor\QuickbooksConnector\Api\Data\SessionInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Psr\Log\LoggerInterface;

class InvoiceSyncWorker extends AbstractWorker
{
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * Constructor
     *
     * @param LoggerInterface $logger
     * @param OrderRepositoryInterface $orderRepository
     * @param SerializerInterface $serializer
     */
    public function __construct(
        LoggerInterface $logger,
        OrderRepositoryInterface $orderRepository,
        SerializerInterface $serializer
    ) {
        parent::__construct($logger);
        $this->orderRepository = $orderRepository;
        $this->serializer = $serializer;
    }

    /**
     * Generate QBXML request to query invoices from QuickBooks
     *
     * This example demonstrates:
     * - Date range filtering
     * - Including line items
     * - Requesting specific fields
     *
     * @param JobInterface $job
     * @param SessionInterface $session
     * @param mixed $data Can contain date range, customer filter, etc.
     * @return array
     */
    public function requests(JobInterface $job, SessionInterface $session, $data): array
    {
        $this->logInfo('InvoiceSyncWorker: Generating invoice query request');

        // Parse job data to get date range (if provided)
        $dateFrom = null;
        $dateTo = null;

        if ($data) {
            $jobData = is_string($data) ? $this->serializer->unserialize($data) : $data;
            $dateFrom = $jobData['date_from'] ?? null;
            $dateTo = $jobData['date_to'] ?? null;
        }

        // Build InvoiceQuery request
        $request = [
            'InvoiceQueryRq' => [
                'xml_attributes' => [
                    'requestID' => '1',
                    'iterator' => 'Start'
                ],
                'MaxReturned' => 50
            ]
        ];

        // Add date filter if provided
        if ($dateFrom) {
            $request['InvoiceQueryRq']['TxnDateRangeFilter'] = [
                'FromTxnDate' => $dateFrom,
            ];
            if ($dateTo) {
                $request['InvoiceQueryRq']['TxnDateRangeFilter']['ToTxnDate'] = $dateTo;
            }
        }

        // Include line items and payment info
        $request['InvoiceQueryRq']['IncludeLineItems'] = true;

        return [$request];
    }

    /**
     * Handle invoice data response from QuickBooks
     *
     * This demonstrates:
     * - Processing invoice header data
     * - Processing invoice line items
     * - Extracting payment information
     * - Matching to Magento orders
     *
     * @param array $response
     * @param SessionInterface $session
     * @param JobInterface $job
     * @param array|null $request
     * @param mixed $data
     * @return void
     */
    public function handleResponse(
        array $response,
        SessionInterface $session,
        JobInterface $job,
        ?array $request,
        $data
    ): void {
        $this->logInfo('InvoiceSyncWorker: Processing invoice response');

        try {
            // Check pagination status
            $iteratorRemainingCount = $response['xml_attributes']['iteratorRemainingCount'] ?? '0';
            $isComplete = $iteratorRemainingCount === '0';

            $this->logInfo("Invoices remaining: {$iteratorRemainingCount}");

            // Get invoice records
            $invoices = $response['InvoiceRet'] ?? [];

            // Handle single invoice
            if (isset($invoices['TxnID'])) {
                $invoices = [$invoices];
            }

            $processedCount = 0;
            $errorCount = 0;

            // Process each invoice
            foreach ($invoices as $qbInvoice) {
                try {
                    $this->processInvoice($qbInvoice);
                    $processedCount++;
                } catch (\Exception $e) {
                    $errorCount++;
                    $this->logError(
                        'Failed to process invoice: ' . $e->getMessage(),
                        [
                            'qb_txn_id' => $qbInvoice['TxnID'] ?? 'unknown',
                            'qb_ref_number' => $qbInvoice['RefNumber'] ?? 'unknown'
                        ]
                    );
                }
            }

            $this->logInfo(
                "Invoice sync batch complete. Processed: {$processedCount}, Errors: {$errorCount}"
            );

            if ($isComplete) {
                $this->logInfo('Invoice sync job completed successfully');
            }

        } catch (\Exception $e) {
            $this->logError('InvoiceSyncWorker error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Process a single invoice from QuickBooks
     *
     * @param array $qbInvoice QuickBooks invoice data
     * @return void
     * @throws \Exception
     */
    private function processInvoice(array $qbInvoice): void
    {
        // Extract invoice header data
        $txnId = $qbInvoice['TxnID'] ?? null;
        $refNumber = $qbInvoice['RefNumber'] ?? '';
        $txnDate = $qbInvoice['TxnDate'] ?? '';
        $customerRef = $qbInvoice['CustomerRef']['ListID'] ?? null;
        $customerName = $qbInvoice['CustomerRef']['FullName'] ?? '';
        $subtotal = $qbInvoice['Subtotal'] ?? 0;
        $salesTaxTotal = $qbInvoice['SalesTaxTotal'] ?? 0;
        $totalAmount = $qbInvoice['TotalAmount'] ?? 0;
        $balanceRemaining = $qbInvoice['BalanceRemaining'] ?? 0;

        $this->logDebug("Processing QB Invoice: {$txnId} - {$refNumber}");

        // Extract line items
        $lineItems = $qbInvoice['InvoiceLineRet'] ?? [];
        if (isset($lineItems['TxnLineID'])) {
            $lineItems = [$lineItems];
        }

        // Log invoice summary
        $this->logInfo(
            "Invoice data from QB",
            [
                'txn_id' => $txnId,
                'ref_number' => $refNumber,
                'date' => $txnDate,
                'customer' => $customerName,
                'subtotal' => $subtotal,
                'tax' => $salesTaxTotal,
                'total' => $totalAmount,
                'balance' => $balanceRemaining,
                'line_item_count' => count($lineItems)
            ]
        );

        // Process line items
        foreach ($lineItems as $lineItem) {
            $this->processLineItem($lineItem, $txnId);
        }

        // In production, you would:
        // 1. Match invoice to Magento order by QB TxnID or RefNumber
        // 2. Update order status based on balance remaining
        // 3. Create Magento invoice if not exists
        // 4. Sync payment information
        // 5. Update inventory if needed
    }

    /**
     * Process invoice line item
     *
     * @param array $lineItem QuickBooks line item data
     * @param string $invoiceTxnId Parent invoice TxnID
     * @return void
     */
    private function processLineItem(array $lineItem, string $invoiceTxnId): void
    {
        $txnLineId = $lineItem['TxnLineID'] ?? '';
        $itemRef = $lineItem['ItemRef']['ListID'] ?? null;
        $itemName = $lineItem['ItemRef']['FullName'] ?? '';
        $desc = $lineItem['Desc'] ?? '';
        $quantity = $lineItem['Quantity'] ?? 0;
        $rate = $lineItem['Rate'] ?? 0;
        $amount = $lineItem['Amount'] ?? 0;

        $this->logDebug(
            "Line Item: {$itemName} x {$quantity} @ {$rate} = {$amount}",
            [
                'invoice_txn_id' => $invoiceTxnId,
                'line_id' => $txnLineId,
                'item_ref' => $itemRef
            ]
        );

        // In production: Match item to Magento product SKU
    }

    /**
     * Conditional execution example
     *
     * Only sync invoices during business hours or specific conditions
     *
     * @param JobInterface $job
     * @param SessionInterface $session
     * @param mixed $data
     * @return bool
     */
    public function shouldRun(JobInterface $job, SessionInterface $session, $data): bool
    {
        // Example: Only run between 9 PM and 6 AM to avoid peak hours
        $currentHour = (int) date('G');

        if ($currentHour >= 21 || $currentHour < 6) {
            $this->logInfo('Invoice sync allowed: Outside business hours');
            return true;
        }

        $this->logWarning('Invoice sync skipped: During business hours');
        return false;
    }
}
