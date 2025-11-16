# API Documentation

## 📋 Mục Lục

- [SOAP API Reference](#soap-api-reference)
- [Repository API](#repository-api)
- [Worker API](#worker-api)
- [CLI Commands](#cli-commands)
- [Events API](#events-api)
- [Configuration API](#configuration-api)

---

## 🌐 SOAP API Reference

Base URL: `https://yourstore.com/soap/default?wsdl&services=qbwcServiceV1`

### Authentication

#### authenticate

Xác thực user và tạo session.

**Request:**

```xml
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"
                  xmlns:qbwc="http://yourstore.com/soap/qbwc">
   <soapenv:Header/>
   <soapenv:Body>
      <qbwc:authenticate>
         <strUserName>qbuser</strUserName>
         <strPassword>qbpass123</strPassword>
      </qbwc:authenticate>
   </soapenv:Body>
</soapenv:Envelope>
```

**Response (Success):**

```xml
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/">
   <SOAP-ENV:Body>
      <tns:authenticateResponse>
         <result>
            <item>a1b2c3d4e5f6...</item>  <!-- Ticket -->
            <item>C:\QB\CompanyFile.qbw</item>  <!-- Company file path -->
         </result>
      </tns:authenticateResponse>
   </SOAP-ENV:Body>
</SOAP-ENV:Envelope>
```

**Response (No Work):**

```xml
<result>
   <item></item>  <!-- Empty ticket -->
   <item>none</item>  <!-- No work available -->
</result>
```

**Response (Invalid User):**

```xml
<result>
   <item></item>  <!-- Empty ticket -->
   <item>nvu</item>  <!-- Not valid user -->
</result>
```

**PHP Client Example:**

```php
$client = new SoapClient('https://yourstore.com/soap/default?wsdl&services=qbwcServiceV1');

$result = $client->authenticate([
    'strUserName' => 'qbuser',
    'strPassword' => 'qbpass123'
]);

list($ticket, $status) = $result;

if ($status === 'nvu') {
    echo "Invalid credentials";
} elseif ($status === 'none') {
    echo "No work available";
} else {
    echo "Authenticated! Ticket: $ticket";
}
```

---

#### serverVersion

Trả về phiên bản server.

**Request:**

```xml
<qbwc:serverVersion/>
```

**Response:**

```xml
<tns:serverVersionResponse>
   <result>1.0.0</result>
</tns:serverVersionResponse>
```

---

#### clientVersion

Xác thực phiên bản client.

**Request:**

```xml
<qbwc:clientVersion>
   <strVersion>2.3.0.123</strVersion>
</qbwc:clientVersion>
```

**Response:**

```xml
<tns:clientVersionResponse>
   <result></result>  <!-- Empty = OK, "W:message" = Warning, "E:message" = Error -->
</tns:clientVersionResponse>
```

---

### Request/Response Cycle

#### sendRequestXML

Gửi QBXML request đến QuickBooks.

**Request:**

```xml
<qbwc:sendRequestXML>
   <ticket>a1b2c3d4e5f6...</ticket>
   <strHCPResponse></strHCPResponse>
   <strCompanyFilename>C:\QB\CompanyFile.qbw</strCompanyFilename>
   <qbXMLCountry>US</qbXMLCountry>
   <qbXMLMajorVers>13</qbXMLMajorVers>
   <qbXMLMinorVers>0</qbXMLMinorVers>
</qbwc:sendRequestXML>
```

**Response:**

```xml
<tns:sendRequestXMLResponse>
   <result><![CDATA[
      <?xml version="1.0"?>
      <?qbxml version="13.0"?>
      <QBXML>
         <QBXMLMsgsRq onError="stopOnError">
            <CustomerQueryRq requestID="1" iterator="Start">
               <MaxReturned>100</MaxReturned>
            </CustomerQueryRq>
         </QBXMLMsgsRq>
      </QBXML>
   ]]></result>
</tns:sendRequestXMLResponse>
```

**Empty Response (No more requests):**

```xml
<result></result>
```

---

#### receiveResponseXML

Nhận QBXML response từ QuickBooks.

**Request:**

```xml
<qbwc:receiveResponseXML>
   <ticket>a1b2c3d4e5f6...</ticket>
   <response><![CDATA[
      <?xml version="1.0"?>
      <QBXML>
         <QBXMLMsgsRs>
            <CustomerQueryRs requestID="1" statusCode="0" statusSeverity="Info"
                           iteratorRemainingCount="50" iteratorID="12345">
               <CustomerRet>
                  <ListID>80000001-1234567890</ListID>
                  <Name>John Doe</Name>
                  <FullName>John Doe</FullName>
               </CustomerRet>
            </CustomerQueryRs>
         </QBXMLMsgsRs>
      </QBXML>
   ]]></response>
   <hresult></hresult>
   <message></message>
</qbwc:receiveResponseXML>
```

**Response:**

```xml
<tns:receiveResponseXMLResponse>
   <result>50</result>  <!-- Progress percentage (0-100) -->
</tns:receiveResponseXMLResponse>
```

**Error Response:**

```xml
<qbwc:receiveResponseXML>
   <ticket>a1b2c3d4e5f6...</ticket>
   <response></response>
   <hresult>0x80040400</hresult>
   <message>QuickBooks error: Invalid request</message>
</qbwc:receiveResponseXML>
```

```xml
<result>-1</result>  <!-- -1 = Error, stop processing -->
```

---

#### closeConnection

Đóng connection và cleanup session.

**Request:**

```xml
<qbwc:closeConnection>
   <ticket>a1b2c3d4e5f6...</ticket>
</qbwc:closeConnection>
```

**Response:**

```xml
<tns:closeConnectionResponse>
   <result>OK</result>
</tns:closeConnectionResponse>
```

---

#### connectionError

Xử lý lỗi connection.

**Request:**

```xml
<qbwc:connectionError>
   <ticket>a1b2c3d4e5f6...</ticket>
   <hresult>0x80040400</hresult>
   <message>Connection lost</message>
</qbwc:connectionError>
```

**Response:**

```xml
<tns:connectionErrorResponse>
   <result>done</result>
</tns:connectionErrorResponse>
```

---

#### getLastError

Lấy thông tin lỗi cuối cùng.

**Request:**

```xml
<qbwc:getLastError>
   <ticket>a1b2c3d4e5f6...</ticket>
</qbwc:getLastError>
```

**Response:**

```xml
<tns:getLastErrorResponse>
   <result>QBWC ERROR: 500 - Invalid customer reference</result>
</tns:getLastErrorResponse>
```

---

## 📦 Repository API

### SessionRepositoryInterface

#### save()

**Method Signature:**

```php
/**
 * @param \Vendor\QuickbooksConnector\Api\Data\SessionInterface $session
 * @return \Vendor\QuickbooksConnector\Api\Data\SessionInterface
 * @throws \Magento\Framework\Exception\CouldNotSaveException
 */
public function save(SessionInterface $session);
```

**Example:**

```php
$session = $this->sessionFactory->create();
$session->setTicket('abc123');
$session->setUser('qbuser');
$session->setCompany('test.qbw');
$session->setProgress(0);

try {
    $savedSession = $this->sessionRepository->save($session);
    echo "Session saved with ID: " . $savedSession->getId();
} catch (CouldNotSaveException $e) {
    echo "Error: " . $e->getMessage();
}
```

---

#### getById()

**Method Signature:**

```php
/**
 * @param int $id
 * @return \Vendor\QuickbooksConnector\Api\Data\SessionInterface
 * @throws \Magento\Framework\Exception\NoSuchEntityException
 */
public function getById($id);
```

**Example:**

```php
try {
    $session = $this->sessionRepository->getById(123);
    echo "Session ticket: " . $session->getTicket();
} catch (NoSuchEntityException $e) {
    echo "Session not found";
}
```

---

#### getByTicket()

**Method Signature:**

```php
/**
 * @param string $ticket
 * @return \Vendor\QuickbooksConnector\Api\Data\SessionInterface
 * @throws \Magento\Framework\Exception\NoSuchEntityException
 */
public function getByTicket($ticket);
```

**Example:**

```php
$ticket = 'a1b2c3d4e5f6...';

try {
    $session = $this->sessionRepository->getByTicket($ticket);
    echo "Progress: " . $session->getProgress() . "%";
} catch (NoSuchEntityException $e) {
    echo "Invalid ticket";
}
```

---

#### delete()

**Method Signature:**

```php
/**
 * @param \Vendor\QuickbooksConnector\Api\Data\SessionInterface $session
 * @return bool
 * @throws \Magento\Framework\Exception\CouldNotDeleteException
 */
public function delete(SessionInterface $session);
```

**Example:**

```php
try {
    $this->sessionRepository->delete($session);
    echo "Session deleted successfully";
} catch (CouldNotDeleteException $e) {
    echo "Error: " . $e->getMessage();
}
```

---

#### getList()

**Method Signature:**

```php
/**
 * @param \Magento\Framework\Api\SearchCriteriaInterface $criteria
 * @return \Vendor\QuickbooksConnector\Api\Data\SessionSearchResultsInterface
 */
public function getList(SearchCriteriaInterface $criteria);
```

**Example:**

```php
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\FilterBuilder;

// Build search criteria
$filter = $this->filterBuilder
    ->setField('user')
    ->setValue('qbuser')
    ->setConditionType('eq')
    ->create();

$searchCriteria = $this->searchCriteriaBuilder
    ->addFilters([$filter])
    ->setPageSize(10)
    ->setCurrentPage(1)
    ->create();

// Get sessions
$searchResults = $this->sessionRepository->getList($searchCriteria);

foreach ($searchResults->getItems() as $session) {
    echo "Ticket: " . $session->getTicket() . "\n";
}

echo "Total: " . $searchResults->getTotalCount();
```

---

### JobRepositoryInterface

#### save()

```php
/**
 * @param \Vendor\QuickbooksConnector\Api\Data\JobInterface $job
 * @return \Vendor\QuickbooksConnector\Api\Data\JobInterface
 * @throws \Magento\Framework\Exception\CouldNotSaveException
 */
public function save(JobInterface $job);
```

**Example:**

```php
$job = $this->jobFactory->create();
$job->setName('sync_customers');
$job->setEnabled(true);
$job->setCompany('');
$job->setWorkerClass('Vendor\QuickbooksConnector\Worker\CustomerSync');
$job->setData(['last_sync' => time()]);

$this->jobRepository->save($job);
```

---

#### getByName()

```php
/**
 * @param string $name
 * @return \Vendor\QuickbooksConnector\Api\Data\JobInterface
 * @throws \Magento\Framework\Exception\NoSuchEntityException
 */
public function getByName($name);
```

**Example:**

```php
$job = $this->jobRepository->getByName('sync_customers');
echo "Worker: " . $job->getWorkerClass();
```

---

#### getPendingJobs()

```php
/**
 * @param string $company
 * @param \Vendor\QuickbooksConnector\Api\Data\SessionInterface|null $session
 * @return \Vendor\QuickbooksConnector\Api\Data\JobInterface[]
 */
public function getPendingJobs($company, $session = null);
```

**Example:**

```php
$pendingJobs = $this->jobRepository->getPendingJobs('test.qbw');

foreach ($pendingJobs as $job) {
    echo "Pending: " . $job->getName() . "\n";
}
```

---

## 🔧 Worker API

### AbstractWorker

Base class cho tất cả workers.

#### requests()

**Method Signature:**

```php
/**
 * Generate QBXML requests
 *
 * @param \Vendor\QuickbooksConnector\Api\Data\JobInterface $job
 * @param \Vendor\QuickbooksConnector\Api\Data\SessionInterface $session
 * @param mixed $data
 * @return array
 */
public function requests($job, $session, $data);
```

**Return Format:**

```php
return [
    [
        'CustomerQueryRq' => [
            'xml_attributes' => [
                'requestID' => '1',
                'iterator' => 'Start'
            ],
            'MaxReturned' => 100,
            'ActiveStatus' => 'All'
        ]
    ],
    [
        'InvoiceQueryRq' => [
            'xml_attributes' => ['requestID' => '2'],
            'MaxReturned' => 50
        ]
    ]
];
```

**Example Implementation:**

```php
class CustomerSyncWorker extends AbstractWorker
{
    public function requests($job, $session, $data)
    {
        $lastSyncTime = $data['last_sync_time'] ?? null;

        $request = [
            'CustomerQueryRq' => [
                'xml_attributes' => [
                    'requestID' => '1',
                    'iterator' => 'Start'
                ],
                'MaxReturned' => 100
            ]
        ];

        if ($lastSyncTime) {
            $request['CustomerQueryRq']['FromModifiedDate'] =
                date('Y-m-d\TH:i:s', $lastSyncTime);
        }

        return [$request];
    }
}
```

---

#### shouldRun()

**Method Signature:**

```php
/**
 * Determine if job should run
 *
 * @param \Vendor\QuickbooksConnector\Api\Data\JobInterface $job
 * @param \Vendor\QuickbooksConnector\Api\Data\SessionInterface $session
 * @param mixed $data
 * @return bool
 */
public function shouldRun($job, $session, $data);
```

**Example:**

```php
public function shouldRun($job, $session, $data)
{
    // Only run during business hours
    $hour = date('H');
    if ($hour < 8 || $hour > 18) {
        return false;
    }

    // Only run if not synced recently
    $lastSync = $data['last_sync'] ?? 0;
    $timeSinceLastSync = time() - $lastSync;

    return $timeSinceLastSync > 3600; // 1 hour
}
```

---

#### handleResponse()

**Method Signature:**

```php
/**
 * Handle QBXML response
 *
 * @param array $response Parsed QBXML response
 * @param \Vendor\QuickbooksConnector\Api\Data\SessionInterface $session
 * @param \Vendor\QuickbooksConnector\Api\Data\JobInterface $job
 * @param array $request Original request
 * @param mixed $data Job data
 * @return void
 */
public function handleResponse($response, $session, $job, $request, $data);
```

**Response Format:**

```php
$response = [
    'CustomerQueryRs' => [
        '@attributes' => [
            'requestID' => '1',
            'statusCode' => '0',
            'statusSeverity' => 'Info',
            'iteratorRemainingCount' => '50',
            'iteratorID' => '12345'
        ],
        'CustomerRet' => [
            [
                'ListID' => '80000001-1234567890',
                'Name' => 'John Doe',
                'FullName' => 'John Doe',
                'IsActive' => 'true',
                // ... more fields
            ],
            // ... more customers
        ]
    ]
];
```

**Example Implementation:**

```php
public function handleResponse($response, $session, $job, $request, $data)
{
    if (!isset($response['CustomerQueryRs'])) {
        return;
    }

    $customers = $response['CustomerQueryRs']['CustomerRet'];

    // Handle single vs multiple
    if (isset($customers['ListID'])) {
        $customers = [$customers];
    }

    foreach ($customers as $customerData) {
        $this->processCustomer($customerData);
    }

    // Update last sync time
    $job->setData('last_sync', time());
    $this->jobRepository->save($job);
}

protected function processCustomer($customerData)
{
    $qbId = $customerData['ListID'];
    $name = $customerData['Name'];

    // Find or create Magento customer
    $customer = $this->findCustomerByQbId($qbId);

    if (!$customer) {
        $customer = $this->customerFactory->create();
        $customer->setCustomAttribute('qb_list_id', $qbId);
    }

    // Update fields
    $nameParts = explode(' ', $customerData['FullName']);
    $customer->setFirstname($nameParts[0]);
    $customer->setLastname($nameParts[1] ?? '');
    $customer->setEmail($customerData['Email'] ?? '');

    // Save
    $this->customerRepository->save($customer);
}
```

---

## 💻 CLI Commands

### Job Management

#### List Jobs

```bash
php bin/magento qbwc:job:list
```

**Output:**

```
+------------------+---------+-------------+--------------------------------------+
| Name             | Enabled | Company     | Worker Class                         |
+------------------+---------+-------------+--------------------------------------+
| sync_customers   | Yes     |             | Vendor\Qbwc\Worker\CustomerSync     |
| sync_invoices    | Yes     | test.qbw    | Vendor\Qbwc\Worker\InvoiceSync      |
| sync_products    | No      |             | Vendor\Qbwc\Worker\ProductSync      |
+------------------+---------+-------------+--------------------------------------+
```

---

#### Create Job

```bash
php bin/magento qbwc:job:create \
  --name="sync_customers" \
  --worker="Vendor\QuickbooksConnector\Worker\CustomerSync" \
  --enabled=1 \
  --company=""
```

---

#### Enable/Disable Job

```bash
php bin/magento qbwc:job:enable sync_customers
php bin/magento qbwc:job:disable sync_customers
```

---

#### Delete Job

```bash
php bin/magento qbwc:job:delete sync_customers
```

---

### Session Management

#### List Sessions

```bash
php bin/magento qbwc:session:list
```

**Output:**

```
+----------+------------------+----------+----------+-----------+
| Ticket   | User             | Progress | Jobs     | Created   |
+----------+------------------+----------+----------+-----------+
| a1b2c3.. | qbuser          | 50%      | 2 of 4   | 5 min ago |
| d4e5f6.. | qbuser2         | 100%     | 3 of 3   | 1 hr ago  |
+----------+------------------+----------+----------+-----------+
```

---

#### Session Details

```bash
php bin/magento qbwc:session:info a1b2c3d4e5f6
```

**Output:**

```
Session Details:
  Ticket:       a1b2c3d4e5f6...
  User:         qbuser
  Company:      C:\QB\test.qbw
  Progress:     50%
  Current Job:  sync_invoices
  Pending Jobs: sync_products, sync_items
  Created:      2025-01-15 10:30:00
  Updated:      2025-01-15 10:35:00
```

---

#### Cleanup Old Sessions

```bash
php bin/magento qbwc:session:cleanup --days=7
```

---

### Testing Commands

#### Test Connection

```bash
php bin/magento qbwc:test:connection
```

---

#### Test SOAP Endpoints

```bash
php bin/magento qbwc:test:soap
```

---

## 🎯 Events API

### Available Events

#### qbwc_session_authenticated

**Dispatched:** After successful authentication

**Observer Registration:**

```xml
<event name="qbwc_session_authenticated">
    <observer name="my_observer"
              instance="Vendor\MyModule\Observer\SessionAuthenticated"/>
</event>
```

**Observer:**

```php
class SessionAuthenticated implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        $session = $observer->getData('session');
        $username = $observer->getData('username');

        // Your logic
    }
}
```

---

#### qbwc_request_sent

**Data:** `session`, `job`, `request`

---

#### qbwc_response_received

**Data:** `session`, `job`, `response`, `parsed_response`

---

#### qbwc_job_completed

**Data:** `job`, `session`

---

#### qbwc_session_completed

**Data:** `session`

---

#### qbwc_error_occurred

**Data:** `session`, `error`, `severity`, `status_code`

---

## ⚙️ Configuration API

### Get Config Values

```php
use Vendor\QuickbooksConnector\Model\Config;

class Example
{
    protected $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function example()
    {
        $username = $this->config->getUsername();
        $minVersion = $this->config->getMinVersion();
        $onError = $this->config->getOnError();
        $logEnabled = $this->config->isLoggingEnabled();
    }
}
```

### Set Config Values

Via Admin:
```
Stores > Configuration > Services > QuickBooks Connector
```

Via CLI:
```bash
php bin/magento config:set qbwc/general/username "qbuser"
php bin/magento config:set qbwc/general/on_error "continue"
```

---

**Last Updated**: 2025-11-16
