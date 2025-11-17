# Troubleshooting Guide

## 📋 Mục Lục

- [Common Issues](#common-issues)
- [Authentication Problems](#authentication-problems)
- [Connection Issues](#connection-issues)
- [QBXML Errors](#qbxml-errors)
- [Performance Issues](#performance-issues)
- [Data Sync Issues](#data-sync-issues)
- [Debugging Tools](#debugging-tools)
- [FAQ](#faq)

---

## 🔧 Common Issues

### Issue: Module Not Appearing in Admin

**Symptoms:**
- Module không xuất hiện trong `System > Extensions`
- Config menu không có

**Diagnosis:**

```bash
# Check if module is enabled
php bin/magento module:status Vendor_QuickbooksConnector

# Should show: Module is enabled
```

**Solution:**

```bash
# Enable module
php bin/magento module:enable Vendor_QuickbooksConnector

# Run setup upgrade
php bin/magento setup:upgrade

# Clear cache
php bin/magento cache:flush

# Recompile
php bin/magento setup:di:compile
```

---

### Issue: SOAP Endpoint Not Found (404)

**Symptoms:**
- QBWC cannot connect
- 404 error when accessing WSDL
- URL: `https://yourstore.com/soap/default?wsdl&services=qbwcServiceV1`

**Diagnosis:**

```bash
# Check if webapi.xml is correct
cat app/code/Vendor/QuickbooksConnector/etc/webapi.xml

# Check Magento routes
php bin/magento debug:router:match "/soap/default"
```

**Solution:**

```bash
# Clear config cache
php bin/magento cache:clean config

# Regenerate
php bin/magento setup:upgrade
php bin/magento cache:flush

# Check file permissions
chmod -R 755 app/code/Vendor/QuickbooksConnector
```

---

### Issue: Database Tables Not Created

**Symptoms:**
- Error: `Table 'qbwc_sessions' doesn't exist`
- Fresh installation fails

**Diagnosis:**

```bash
# Check database
mysql -u root -p
USE magento_db;
SHOW TABLES LIKE 'qbwc_%';
```

**Solution:**

```bash
# Run setup upgrade
php bin/magento setup:upgrade

# If still failing, check db_schema.xml
cat app/code/Vendor/QuickbooksConnector/etc/db_schema.xml

# Manually run declarative schema
php bin/magento setup:db-declaration:generate-whitelist
```

---

## 🔐 Authentication Problems

### Issue: "nvu" (Not Valid User)

**Symptoms:**
- QBWC shows "Not valid user"
- Authentication fails immediately

**Diagnosis:**

Check configuration:

```bash
# Via CLI
php bin/magento config:show qbwc/general/username
php bin/magento config:show qbwc/general/password

# Via database
SELECT * FROM core_config_data WHERE path LIKE 'qbwc/%';
```

**Solution 1: Check Credentials**

```bash
# Set correct credentials
php bin/magento config:set qbwc/general/username "qbuser"
php bin/magento config:set qbwc/general/password "qbpass123"

# Clear config cache
php bin/magento cache:clean config
```

**Solution 2: Re-download QWC File**

```
1. Navigate to: https://yourstore.com/qbwc/download/qwc
2. Delete old QWC from QBWC application
3. Add new QWC file
4. Enter password when prompted
```

**Solution 3: Check Custom Authenticator**

If using custom authenticator:

```php
// Check di.xml
<type name="Vendor\QuickbooksConnector\Model\Config">
    <arguments>
        <argument name="authenticator" xsi:type="object">
            YourCustomAuthenticator
        </argument>
    </arguments>
</type>

// Verify authenticator returns company file path or null
class YourCustomAuthenticator
{
    public function authenticate($username, $password)
    {
        // Must return company file path string or null
        if ($this->isValid($username, $password)) {
            return 'C:\\QB\\company.qbw';  // or empty string
        }
        return null;  // Triggers "nvu"
    }
}
```

---

### Issue: "none" (No Work Available)

**Symptoms:**
- Authentication succeeds
- But QBWC shows "No work to do"

**Diagnosis:**

```bash
# List all jobs
php bin/magento qbwc:job:list

# Check if jobs are enabled
mysql -u root -p
SELECT name, enabled, company FROM qbwc_jobs;
```

**Solution:**

```bash
# Enable jobs
php bin/magento qbwc:job:enable job_name

# Or via database
UPDATE qbwc_jobs SET enabled = 1 WHERE name = 'job_name';

# Verify jobs exist
php bin/magento qbwc:job:list
```

**Check `shouldRun()` Method:**

```php
class YourWorker extends AbstractWorker
{
    public function shouldRun($job, $session, $data)
    {
        // Add debug logging
        $this->logger->debug('shouldRun called for job: ' . $job->getName());

        // Check your conditions
        $result = /* your logic */;

        $this->logger->debug('shouldRun result: ' . ($result ? 'true' : 'false'));

        return $result;
    }
}
```

---

## 🌐 Connection Issues

### Issue: Connection Timeout

**Symptoms:**
- QBWC shows "Connection timeout"
- Long delay before error

**Diagnosis:**

```bash
# Check PHP settings
php -i | grep max_execution_time
php -i | grep default_socket_timeout

# Test SOAP endpoint
curl -I https://yourstore.com/soap/default?wsdl
```

**Solution:**

```ini
# php.ini
max_execution_time = 300
default_socket_timeout = 300
memory_limit = 512M

# Restart PHP-FPM
sudo service php8.1-fpm restart
```

**Check Firewall:**

```bash
# Allow HTTPS
sudo ufw allow 443/tcp

# Check if port is open
sudo netstat -tulpn | grep :443
```

---

### Issue: SSL/HTTPS Errors

**Symptoms:**
- "SSL certificate problem"
- "Unable to verify SSL certificate"

**Diagnosis:**

```bash
# Test SSL
curl -v https://yourstore.com/soap/default?wsdl

# Check certificate
openssl s_client -connect yourstore.com:443
```

**Solution:**

```bash
# Install valid SSL certificate
# Using Let's Encrypt
sudo certbot --nginx -d yourstore.com

# Or disable SSL verification (DEVELOPMENT ONLY!)
# In QBWC settings, use HTTP instead of HTTPS
# NOT RECOMMENDED FOR PRODUCTION
```

---

### Issue: Session Expired/Lost

**Symptoms:**
- Mid-sync failure
- "Session not found" error

**Diagnosis:**

```bash
# Check session in database
mysql -u root -p
SELECT * FROM qbwc_sessions WHERE ticket = 'your_ticket';

# Check session timeout
php bin/magento config:show qbwc/general/session_timeout
```

**Solution:**

```bash
# Increase session timeout
php bin/magento config:set qbwc/general/session_timeout 7200

# Clear old sessions
php bin/magento qbwc:session:cleanup --days=1

# Use Redis for session storage (recommended)
# env.php
'session' => [
    'save' => 'redis',
    'redis' => [
        'host' => '127.0.0.1',
        'port' => '6379',
        'database' => '2',
        'max_lifetime' => 7200
    ]
]
```

---

## 📝 QBXML Errors

### Issue: Status Code 500 - Invalid Request

**Symptoms:**
- QuickBooks returns error 500
- "Invalid request" message

**Diagnosis:**

Check QBXML version compatibility:

```bash
# Enable request logging
php bin/magento config:set qbwc/general/log_requests_and_responses 1

# Check logs
tail -f var/log/qbwc.log
```

**Common Causes:**

1. **Invalid XML Structure**

```php
// WRONG - missing xml_attributes
[
    'CustomerQueryRq' => [
        'MaxReturned' => 100
    ]
]

// CORRECT
[
    'CustomerQueryRq' => [
        'xml_attributes' => ['requestID' => '1'],
        'MaxReturned' => 100
    ]
]
```

2. **Invalid Field Names**

```php
// Check QuickBooks OSR (Onscreen Reference)
// for correct field names for your QBXML version

// Example: 'FullName' vs 'Name'
'CustomerRef' => [
    'FullName' => 'Customer Name'  // Correct for CustomerRef
]
```

3. **Required Fields Missing**

```xml
<!-- InvoiceAdd requires CustomerRef -->
<InvoiceAdd>
    <CustomerRef>
        <ListID>80000001-123</ListID>
    </CustomerRef>
    <TxnDate>2025-01-15</TxnDate>
    <!-- Other fields -->
</InvoiceAdd>
```

**Solution:**

```php
// Add validation before sending
protected function validateRequest($request)
{
    // Check required fields
    if (!isset($request['InvoiceAdd']['CustomerRef'])) {
        throw new \InvalidArgumentException('CustomerRef is required');
    }

    // Validate data types
    if (!is_numeric($request['InvoiceAdd']['InvoiceLineAdd'][0]['Quantity'])) {
        throw new \InvalidArgumentException('Quantity must be numeric');
    }

    return true;
}
```

---

### Issue: Status Code 3120 - Object Not Found

**Symptoms:**
- "There was an error when modifying a XXX. QuickBooks error message: Object not found"

**Cause:**
- Trying to modify/delete object that doesn't exist
- Using wrong ListID or EditSequence

**Solution:**

```php
// Always query first before modifying
protected function updateCustomer($qbListId, $newData)
{
    // Query to verify exists and get EditSequence
    $queryRequest = [
        'CustomerQueryRq' => [
            'xml_attributes' => ['requestID' => 'verify'],
            'ListID' => $qbListId
        ]
    ];

    // Send query request
    // Get EditSequence from response

    // Then modify
    $modRequest = [
        'CustomerModRq' => [
            'CustomerMod' => [
                'ListID' => $qbListId,
                'EditSequence' => $editSequence,  // From query response
                // New data
            ]
        ]
    ];
}
```

---

### Issue: Status Code 3200 - Edit Sequence Mismatch

**Cause:**
- EditSequence out of date (record modified since last query)

**Solution:**

```php
// Re-query to get latest EditSequence
protected function retryWithLatestEditSequence($listId)
{
    // Query for latest
    $latest = $this->queryCustomer($listId);

    // Use latest EditSequence
    return [
        'CustomerModRq' => [
            'CustomerMod' => [
                'ListID' => $listId,
                'EditSequence' => $latest['EditSequence'],
                // Updated data
            ]
        ]
    ];
}
```

---

## ⚡ Performance Issues

### Issue: Slow Sync Performance

**Symptoms:**
- Sync takes hours
- High memory usage
- Timeout errors

**Diagnosis:**

```bash
# Check current performance
tail -f var/log/qbwc.log | grep "Processing"

# Monitor memory
watch -n 1 free -m

# Check MySQL slow queries
mysql -u root -p
SHOW FULL PROCESSLIST;
```

**Solution 1: Optimize Batch Size**

```php
public function requests($job, $session, $data)
{
    return [
        [
            'CustomerQueryRq' => [
                'xml_attributes' => ['iterator' => 'Start'],
                'MaxReturned' => 50  // Reduce from 100 to 50
            ]
        ]
    ];
}
```

**Solution 2: Add Database Indexes**

```sql
-- Add custom indexes
ALTER TABLE qbwc_sessions ADD INDEX idx_created (created_at);
ALTER TABLE qbwc_jobs ADD INDEX idx_company_enabled (company(255), enabled);

-- Analyze tables
ANALYZE TABLE qbwc_sessions, qbwc_jobs;
```

**Solution 3: Use Caching**

```php
class CustomerSyncWorker extends AbstractWorker
{
    protected $cache;

    public function handleResponse($response, $session, $job, $request, $data)
    {
        $cacheKey = 'qbwc_customer_map';

        // Get cached mapping
        $customerMap = $this->cache->load($cacheKey);
        if (!$customerMap) {
            $customerMap = $this->buildCustomerMap();
            $this->cache->save(
                serialize($customerMap),
                $cacheKey,
                ['qbwc'],
                3600
            );
        }

        // Use cached data
    }
}
```

**Solution 4: Process in Background**

```php
// Queue large jobs for background processing
protected function handleLargeDataset($customers)
{
    foreach ($customers as $customer) {
        // Add to queue
        $this->messagePublisher->publish(
            'qbwc.customer.sync',
            json_encode($customer)
        );
    }
}
```

---

### Issue: Memory Limit Exceeded

**Symptoms:**
- Fatal error: Allowed memory size exhausted

**Solution:**

```ini
# php.ini
memory_limit = 1024M

# Or increase in code (not recommended)
ini_set('memory_limit', '1024M');
```

```php
// Process in smaller batches
protected function handleResponse($response, $session, $job, $request, $data)
{
    $customers = $response['CustomerQueryRs']['CustomerRet'];

    // Process in chunks
    foreach (array_chunk($customers, 10) as $chunk) {
        $this->processChunk($chunk);
        gc_collect_cycles();  // Free memory
    }
}
```

---

## 📊 Data Sync Issues

### Issue: Duplicate Records

**Symptoms:**
- Same customer/order synced multiple times
- Duplicate entries in Magento

**Diagnosis:**

```sql
-- Find duplicates
SELECT qb_list_id, COUNT(*) as count
FROM customer_entity
WHERE qb_list_id IS NOT NULL
GROUP BY qb_list_id
HAVING count > 1;
```

**Solution:**

```php
// Always check for existing records
protected function syncCustomer($qbCustomer)
{
    $qbListId = $qbCustomer['ListID'];

    // Search by QB ID
    $customer = $this->customerRepository->getByQbListId($qbListId);

    if ($customer) {
        // Update existing
        $this->logger->info("Updating customer: $qbListId");
        $this->updateCustomer($customer, $qbCustomer);
    } else {
        // Check by email to avoid duplicates
        try {
            $customer = $this->customerRepository->get($qbCustomer['Email']);
            // Customer exists, update QB ID
            $customer->setCustomAttribute('qb_list_id', $qbListId);
        } catch (NoSuchEntityException $e) {
            // Create new
            $customer = $this->createCustomer($qbCustomer);
        }
    }

    $this->customerRepository->save($customer);
}
```

---

### Issue: Data Not Updating

**Symptoms:**
- Changes in QB not reflected in Magento
- Or vice versa

**Diagnosis:**

```bash
# Check last sync time
php bin/magento qbwc:job:info job_name

# Check if worker is processing responses
tail -f var/log/qbwc.log | grep "handleResponse"
```

**Solution:**

```php
// Add logging to verify data updates
public function handleResponse($response, $session, $job, $request, $data)
{
    $this->logger->info('Response received', [
        'job' => $job->getName(),
        'response_count' => count($response['CustomerQueryRs']['CustomerRet'] ?? [])
    ]);

    // Process each customer
    foreach ($customers as $customer) {
        $this->logger->debug('Processing customer', [
            'qb_id' => $customer['ListID'],
            'name' => $customer['Name']
        ]);

        $this->syncCustomer($customer);

        $this->logger->debug('Customer synced', [
            'qb_id' => $customer['ListID']
        ]);
    }

    // Verify updates
    $this->logger->info('Sync complete', [
        'processed' => count($customers)
    ]);
}
```

---

## 🛠️ Debugging Tools

### Enable Debug Mode

```bash
# Set developer mode
php bin/magento deploy:mode:set developer

# Enable all logging
php bin/magento config:set dev/debug/debug_logging 1
php bin/magento config:set qbwc/general/log_requests_and_responses 1

# Clear cache
php bin/magento cache:flush
```

### Useful CLI Commands

```bash
# Test SOAP endpoint
php bin/magento qbwc:test:soap

# Test authentication
php bin/magento qbwc:test:auth --username=qbuser --password=qbpass

# Manually trigger job
php bin/magento qbwc:job:run job_name

# Debug specific session
php bin/magento qbwc:session:info ticket_value

# Clear all sessions
php bin/magento qbwc:session:cleanup --all
```

### Log Analysis

```bash
# Find errors
grep -i "error" var/log/qbwc.log

# Find specific job
grep "job_name" var/log/qbwc.log

# Count successful syncs
grep -c "statusCode=\"0\"" var/log/qbwc.log

# Find slow operations
grep -i "time:" var/log/qbwc.log | awk '{print $NF}' | sort -n
```

### Database Queries

```sql
-- Check active sessions
SELECT ticket, user, company, progress, created_at
FROM qbwc_sessions
WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR);

-- Check job status
SELECT name, enabled, worker_class, created_at
FROM qbwc_jobs
ORDER BY created_at DESC;

-- Find stuck sessions
SELECT *
FROM qbwc_sessions
WHERE updated_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)
AND progress < 100;
```

---

## ❓ FAQ

### Q: Can I run multiple jobs simultaneously?

**A:** Yes, jobs run sequentially within a session, but multiple sessions can run in parallel (different users/companies).

---

### Q: How do I reset a job?

**A:** Delete and recreate the job:

```bash
php bin/magento qbwc:job:delete job_name
php bin/magento qbwc:job:create --name=job_name --worker=WorkerClass
```

---

### Q: What's the maximum QBXML version supported?

**A:** Module supports QBXML 3.0 - 13.0. Configure via:

```bash
php bin/magento config:set qbwc/general/min_version "13.0"
```

---

### Q: Can I sync to QuickBooks Online?

**A:** Not directly. This module is for QuickBooks Desktop only. For QuickBooks Online, use their REST API.

---

### Q: How do I handle multi-currency?

**A:** QuickBooks Desktop supports multi-currency. Ensure your QBXML includes currency fields:

```php
'InvoiceAdd' => [
    'CurrencyRef' => [
        'FullName' => 'USD'
    ],
    // ...
]
```

---

### Q: What happens if QuickBooks is closed during sync?

**A:** QBWC will wait and show "Waiting for QuickBooks". Session persists in database. Sync resumes when QB reopens.

---

### Q: How do I migrate from Rails version?

**A:** See MIGRATION.md for detailed guide. Key steps:
1. Export job configuration
2. Port custom workers to PHP
3. Test thoroughly before going live

---

## 📞 Getting Help

If you're still stuck:

1. **Check Logs**:
   - `var/log/qbwc.log`
   - `var/log/system.log`
   - `var/log/exception.log`

2. **Enable Debug Mode**:
   ```bash
   php bin/magento deploy:mode:set developer
   ```

3. **Search Issues**:
   - GitHub Issues: https://github.com/vendor/module/issues
   - Stack Overflow: Tag `magento2` + `quickbooks`

4. **Contact Support**:
   - Email: support@example.com
   - Forum: https://community.example.com

5. **Professional Services**:
   - Custom development
   - Integration support
   - Training

---

**Last Updated**: 2025-11-16
