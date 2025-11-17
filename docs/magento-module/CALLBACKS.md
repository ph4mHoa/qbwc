# QuickBooks Web Connector - Callback System

## Overview

The callback/hook system allows you to execute custom code at key points in the QBWC workflow.

**Cloned from Rails QBWC gem:**
- `QBWC.session_initializer` → `SessionInitializerInterface`
- `QBWC.session_complete_success` → `SessionCompleteInterface`

---

## Available Callbacks

### 1. Session Initializer

**When:** Called AFTER authentication succeeds and jobs are pending
**Rails equivalent:** `QBWC.session_initializer.call(session)`
**Location:** `lib/qbwc/controller.rb:127`

**Use cases:**
- Set custom session data
- Log session start with details
- Send notifications that sync has started
- Initialize external systems
- Warm up caches
- Prepare data for workers

**Example:**
```php
namespace YourVendor\YourModule\Model;

use Vendor\QuickbooksConnector\Api\SessionInitializerInterface;
use Vendor\QuickbooksConnector\Api\Data\SessionInterface;

class CustomSessionInitializer implements SessionInitializerInterface
{
    public function initialize(SessionInterface $session): void
    {
        // Your custom logic here
        $this->logger->info('Session started', [
            'user' => $session->getUser(),
            'company' => $session->getCompany(),
            'jobs' => $session->getPendingJobsArray()
        ]);

        // Example: Notify external system
        $this->notifyExternalSystem($session);
    }
}
```

---

### 2. Session Complete

**When:** Called WHEN session completes all jobs without errors
**Rails equivalent:** `QBWC.session_complete_success.call(self)`
**Location:** `lib/qbwc/session.rb:128-130`

**Use cases:**
- Log successful completion
- Send success notifications
- Generate reports
- Update dashboards
- Cleanup temporary data
- Trigger webhooks
- Update external systems

**Example:**
```php
namespace YourVendor\YourModule\Model;

use Vendor\QuickbooksConnector\Api\SessionCompleteInterface;
use Vendor\QuickbooksConnector\Api\Data\SessionInterface;

class CustomSessionComplete implements SessionCompleteInterface
{
    public function onComplete(SessionInterface $session): void
    {
        // Your custom logic here
        $this->logger->info('Session completed successfully', [
            'progress' => $session->getProgress(),
            'jobs_count' => $session->getInitialJobCount()
        ]);

        // Example: Send email notification
        $this->sendCompletionEmail($session);

        // Example: Update statistics
        $this->updateSyncStatistics($session);
    }
}
```

---

## How to Register Callbacks

### Step 1: Create Your Callback Class

Implement the appropriate interface:
- `Vendor\QuickbooksConnector\Api\SessionInitializerInterface`
- `Vendor\QuickbooksConnector\Api\SessionCompleteInterface`

### Step 2: Register in di.xml

Add your callback to `etc/di.xml`:

```xml
<type name="Vendor\QuickbooksConnector\Model\CallbackManager">
    <arguments>
        <!-- Session Initializers -->
        <argument name="sessionInitializers" xsi:type="array">
            <item name="my_initializer" xsi:type="object">YourVendor\YourModule\Model\CustomSessionInitializer</item>
        </argument>

        <!-- Session Complete Handlers -->
        <argument name="sessionCompleteHandlers" xsi:type="array">
            <item name="my_complete" xsi:type="object">YourVendor\YourModule\Model\CustomSessionComplete</item>
        </argument>
    </arguments>
</type>
```

### Step 3: Deploy and Test

```bash
bin/magento setup:di:compile
bin/magento cache:flush
```

---

## Callback Execution Flow

### Evidence from Rails Source:

**1. Authentication Flow:**
```ruby
# lib/qbwc/controller.rb:102-130
def authenticate
  # ... authentication logic ...

  if jobs_pending?
    session = create_session()
    QBWC.session_initializer.call(session)  # ← CALLBACK HERE
  end

  return [ticket, company_file_path]
end
```

**2. Completion Flow:**
```ruby
# lib/qbwc/session.rb:43-57
def next_request
  if finished?
    complete_with_success  # ← Calls callback
  end
end

# lib/qbwc/session.rb:128-130
def complete_with_success
  QBWC.session_complete_success.call(self) if QBWC.session_complete_success
end
```

---

## Magento Implementation:

### Callback Invocation Points:

**1. QbwcService::authenticate()** (line 179)
```php
// After session saved and jobs pending
$this->sessionRepository->save($session);
$this->callbackManager->invokeSessionInitializers($session);
```

**2. QbwcService::moveToNextJob()** (line 509-511)
```php
// When all jobs completed without errors
if (empty($pendingJobs) && !$session->hasError()) {
    $this->callbackManager->invokeSessionComplete($session);
}
```

---

## Multiple Callbacks

You can register multiple callbacks of each type. They execute in the order registered.

**Example:**
```xml
<argument name="sessionInitializers" xsi:type="array">
    <item name="logger" xsi:type="object">Vendor\QuickbooksConnector\Model\Callback\Example\LoggingSessionInitializer</item>
    <item name="notification" xsi:type="object">YourVendor\YourModule\Model\NotificationInitializer</item>
    <item name="cache" xsi:type="object">YourVendor\YourModule\Model\CacheWarmupInitializer</item>
</argument>
```

**Execution order:** logger → notification → cache

---

## Error Handling

Callbacks are **error-tolerant**:
- If one callback throws an exception, it's logged but others continue
- Session workflow is not interrupted by callback failures
- Check logs: `var/log/qbwc.log`

---

## Example Callbacks Included

### 1. LoggingSessionInitializer

**Location:** `Model/Callback/Example/LoggingSessionInitializer.php`

Logs detailed session information on initialization.

**To enable:**
```xml
<item name="logging" xsi:type="object">Vendor\QuickbooksConnector\Model\Callback\Example\LoggingSessionInitializer</item>
```

### 2. NotificationSessionComplete

**Location:** `Model/Callback/Example/NotificationSessionComplete.php`

Logs completion and provides hooks for notifications.

**To enable:**
```xml
<item name="notification" xsi:type="object">Vendor\QuickbooksConnector\Model\Callback\Example\NotificationSessionComplete</item>
```

---

## Advanced Use Cases

### Use Case 1: Email Notifications

```php
class EmailSessionComplete implements SessionCompleteInterface
{
    private $mailSender;

    public function onComplete(SessionInterface $session): void
    {
        $this->mailSender->send([
            'to' => 'admin@example.com',
            'subject' => 'QB Sync Complete',
            'body' => "Synced {$session->getInitialJobCount()} jobs for {$session->getCompany()}"
        ]);
    }
}
```

### Use Case 2: Webhook Trigger

```php
class WebhookSessionComplete implements SessionCompleteInterface
{
    private $httpClient;

    public function onComplete(SessionInterface $session): void
    {
        $this->httpClient->post('https://api.example.com/webhooks/qb-sync', [
            'company' => $session->getCompany(),
            'jobs_completed' => $session->getInitialJobCount(),
            'timestamp' => time()
        ]);
    }
}
```

### Use Case 3: Statistics Tracking

```php
class StatsSessionComplete implements SessionCompleteInterface
{
    private $statsRepository;

    public function onComplete(SessionInterface $session): void
    {
        $stat = $this->statsRepository->create();
        $stat->setCompany($session->getCompany());
        $stat->setJobCount($session->getInitialJobCount());
        $stat->setDuration($this->calculateDuration($session));
        $this->statsRepository->save($stat);
    }
}
```

---

## Debugging Callbacks

**Check if callbacks are registered:**
```php
// In your code or observer
$callbackManager = $objectManager->get(\Vendor\QuickbooksConnector\Model\CallbackManager::class);
echo "Initializers: " . $callbackManager->getInitializerCount();
echo "Complete handlers: " . $callbackManager->getCompleteHandlerCount();
```

**Check logs:**
```bash
tail -f var/log/qbwc.log | grep -i callback
```

**Sample log output:**
```
[INFO] Session initializer registered: YourVendor\YourModule\Model\CustomSessionInitializer
[INFO] Invoking 2 session initializer(s) (ticket: abc123)
[DEBUG] Invoking initializer: Vendor\QuickbooksConnector\Model\Callback\Example\LoggingSessionInitializer
[DEBUG] Initializer completed: Vendor\QuickbooksConnector\Model\Callback\Example\LoggingSessionInitializer
```

---

## Comparison with Rails

| Feature | Rails QBWC | Magento 2 Module |
|---------|-----------|------------------|
| **Session initializer** | `QBWC.session_initializer` | `SessionInitializerInterface` |
| **Session complete** | `QBWC.session_complete_success` | `SessionCompleteInterface` |
| **Registration** | Ruby block: `set_session_initializer { }` | di.xml array injection |
| **Multiple callbacks** | Single callback per type | Multiple via array |
| **Error handling** | Callback errors stop workflow | Error-tolerant, continues |
| **Type safety** | Duck typing | Interface contracts |

---

## Best Practices

1. **Keep callbacks fast** - They run in the main SOAP workflow
2. **Use async operations** - For slow tasks (email, webhooks), queue them
3. **Log important actions** - For debugging and audit trails
4. **Handle errors gracefully** - Don't throw exceptions if possible
5. **Test thoroughly** - Callbacks can affect critical sync workflow

---

## Summary

✅ **Session Initializer**: Execute code after authentication, before sync starts
✅ **Session Complete**: Execute code when all jobs finish successfully
✅ **Multiple callbacks**: Register as many as needed via di.xml
✅ **Error-tolerant**: One failure doesn't break others
✅ **Example implementations**: Included for reference

**Next:** See `MISSING_FEATURES.md` for other available features.
