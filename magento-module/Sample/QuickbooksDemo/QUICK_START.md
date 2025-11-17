# Quick Start Guide - Sample QuickBooks Demo

## 5-Minute Setup

### 1. Install Module (2 minutes)

```bash
# Copy to Magento
cp -r Sample/QuickbooksDemo /path/to/magento/app/code/Sample/

# Enable & install
php bin/magento module:enable Sample_QuickbooksDemo
php bin/magento setup:upgrade
php bin/magento cache:flush
```

### 2. Create Your First Job (1 minute)

**Option A: Interactive Wizard**
```bash
php bin/magento sample:qb:job:create
```

**Option B: Direct Command**
```bash
php bin/magento sample:qb:customer:sync "C:\QuickBooks\MyCompany.qbw"
```

### 3. Verify Job Created (30 seconds)

```bash
php bin/magento sample:qb:job:list
```

Expected output:
```
+----+----------------------+-------------+---------------------+---------+---------------------+
| ID | Name                 | Company     | Worker              | Enabled | Created             |
+----+----------------------+-------------+---------------------+---------+---------------------+
| 1  | sample_customer_sync | company.qbw | CustomerSyncWorker  | Yes     | 2025-11-17 10:00:00 |
+----+----------------------+-------------+---------------------+---------+---------------------+
```

### 4. Configure QuickBooks Web Connector (1.5 minutes)

1. **Download QWC file:** `https://your-magento.com/qbwc/qwc`
2. **Import in QBWC:** File → Add Application → Select QWC file
3. **Enter password:** (configured in Vendor_QuickbooksConnector)
4. **Click "Update Selected"**

### 5. Watch It Work!

**View logs:**
```bash
tail -f var/log/quickbooks_demo.log
```

**Expected log output:**
```
[2025-11-17 10:05:00] INFO: CustomerSyncWorker: Generating customer query request
[2025-11-17 10:05:02] INFO: CustomerSyncWorker: Processing customer response
[2025-11-17 10:05:02] INFO: Customers remaining: 150
[2025-11-17 10:05:02] INFO: Customer data from QB {"qb_list_id":"8000001-1234567890","name":"John Doe"}
...
[2025-11-17 10:05:10] INFO: Customer sync job completed successfully
```

---

## Common Commands

### Create Jobs

```bash
# Customer sync
php bin/magento sample:qb:customer:sync

# Invoice sync (last 30 days)
php bin/magento sample:qb:invoice:sync \
    --from="2025-10-17" \
    --to="2025-11-17"

# Product query (force run on weekday)
php bin/magento sample:qb:product:query --force
```

### Manage Jobs

```bash
# List all jobs
php bin/magento sample:qb:job:list

# List jobs for specific company
php bin/magento sample:qb:job:list --company="C:\QB\Company.qbw"

# Interactive job creation
php bin/magento sample:qb:job:create
```

---

## What Each Sample Does

### 🔵 CustomerSyncWorker
- Queries customers from QuickBooks (100 at a time)
- Logs customer data (ListID, Name, Email, Phone)
- Demonstrates iterator pagination
- **Runs:** Always

### 🟢 InvoiceSyncWorker
- Queries invoices with optional date filter
- Processes invoice line items
- Shows payment info extraction
- **Runs:** Only 9 PM - 6 AM (business hours restriction)

### 🟡 ProductQueryWorker
- Queries 3 item types: Inventory, Service, Non-Inventory
- Handles different QB product structures
- Shows price/qty/cost extraction
- **Runs:** Weekends only (unless --force)

---

## Customization Examples

### Change When Job Runs

Edit `shouldRun()` in worker:

```php
// InvoiceSyncWorker.php - Change to run only on weekends
public function shouldRun(...): bool
{
    $dayOfWeek = (int) date('N');
    return $dayOfWeek >= 6;  // Sat/Sun only
}
```

### Pass Custom Data to Worker

```php
// In your code
$jobData = ['customer_type' => 'retail', 'limit' => 50];
$job->setData($this->serializer->serialize($jobData));

// In worker
public function requests(..., $data): array
{
    $jobData = $this->serializer->unserialize($data);
    $limit = $jobData['limit'] ?? 100;

    return [
        ['CustomerQueryRq' => ['MaxReturned' => $limit]]
    ];
}
```

### Add Actual Sync Logic

```php
// In handleResponse()
private function syncCustomer(array $qbCustomer): void
{
    $qbListId = $qbCustomer['ListID'];
    $email = $qbCustomer['Email'];

    // Check if customer exists in Magento
    $customer = $this->findMagentoCustomerByQbListId($qbListId);

    if (!$customer) {
        // Create new customer
        $customer = $this->customerFactory->create();
        $customer->setEmail($email);
        // ... set other fields
        $customer->setCustomAttribute('qb_list_id', $qbListId);

        $this->customerRepository->save($customer);
        $this->logInfo("Created customer: {$email}");
    } else {
        // Update existing customer
        $customer->setFirstname($qbCustomer['FirstName']);
        // ... update fields
        $this->customerRepository->save($customer);
        $this->logInfo("Updated customer: {$email}");
    }
}
```

---

## Troubleshooting

| Issue | Solution |
|-------|----------|
| Job not in QBWC | Check company file path matches exactly |
| Worker not running | Check `shouldRun()` conditions (time/day) |
| No logs | Check `var/log/quickbooks_demo.log` exists |
| QBWC error | Check `var/log/quickbooks_connector.log` |

---

## Next Steps

1. ✅ Read full [README.md](README.md) for detailed examples
2. ✅ Review worker code to understand QBXML structure
3. ✅ Customize workers for your actual sync needs
4. ✅ Add error handling and rollback logic
5. ✅ Set up production monitoring

---

**📖 Full Documentation:** See [README.md](README.md)

**🎉 You're ready to integrate QuickBooks with Magento!**
