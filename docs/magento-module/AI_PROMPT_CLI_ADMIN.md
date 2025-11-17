# AI Prompt: Implement CLI Commands và Admin UI cho QBWC Magento 2 Module

## Context Summary

**Project:** QuickBooks Web Connector (QBWC) for Magento 2.4.8
**Source:** Cloned from Rails QBWC gem (https://github.com/qbwc/qbwc)
**Location:** `/home/user/qbwc/magento-module/Vendor/QuickbooksConnector/`
**Branch:** `claude/describe-stt-handling-016CYokTzQ5P8eMcCwgB6TAh`

## Current Status: 97% Complete

### ✅ Already Implemented (Commits: e7263cf, 5d629ee, 1b458e0):
- Core Models: Session.php (360 lines), Job.php (486 lines)
- Repositories: SessionRepository.php (280 lines), JobRepository.php (340 lines)
- ResourceModels: Session, Job + Collections (580 lines total)
- SOAP Service: QbwcService.php (520 lines) - 8 endpoints complete
- Utilities: Config.php (310 lines), QbxmlParser.php (320 lines)
- Callbacks System: CallbackManager.php (280 lines) - Rails parity 100%
- QWC Download: Controller/Qwc/Download.php (130 lines)
- Database: db_schema.xml with qbwc_sessions and qbwc_jobs tables
- Documentation: 8 files (~2,500 lines)

### ⚠️ TODO (3% - Your Task):
1. **CLI Commands** (7 commands) - 0% complete
2. **Admin UI** (system.xml, menu, grids) - 0% complete

---

## Task 1: CLI Commands (Priority High)

### Goal
Implement 7 CLI commands for job and session management.

### Evidence
Rails QBWC has programmatic methods (lib/qbwc.rb:82-103):
```ruby
QBWC.add_job(name, enabled, company, klass, requests, data)
QBWC.get_job(name)
QBWC.delete_job(name)
QBWC.pending_jobs(company, session)
QBWC.clear_jobs
QBWC.jobs  # List all
```

These need Magento CLI equivalents.

### Files to Create

#### A. Job Commands (5 files)

**1. Console/Command/Job/ListCommand.php**
- Command: `bin/magento qbwc:job:list [--company=COMPANY]`
- Action: List all jobs with status (name, company, enabled, worker_class)
- Table output with columns: ID, Name, Company, Enabled, Worker Class, Created
- Use: `JobRepositoryInterface->getList()`
- Example Rails equivalent: `QBWC.jobs`

**2. Console/Command/Job/CreateCommand.php**
- Command: `bin/magento qbwc:job:create <name> <worker-class> [--company=] [--enabled=1]`
- Action: Create new job
- Validate: Worker class exists and extends AbstractWorker
- Use: `JobInterfaceFactory->create()`, `JobRepositoryInterface->save()`
- Example Rails equivalent: `QBWC.add_job(name, enabled, company, klass)`

**3. Console/Command/Job/EnableCommand.php**
- Command: `bin/magento qbwc:job:enable <name>`
- Action: Enable job by name
- Use: `JobRepositoryInterface->enableJob(name)`
- Example Rails equivalent: `job.enable`

**4. Console/Command/Job/DisableCommand.php**
- Command: `bin/magento qbwc:job:disable <name>`
- Action: Disable job by name
- Use: `JobRepositoryInterface->disableJob(name)`
- Example Rails equivalent: `job.disable`

**5. Console/Command/Job/DeleteCommand.php**
- Command: `bin/magento qbwc:job:delete <name> [--force]`
- Action: Delete job by name (with confirmation)
- Use: `JobRepositoryInterface->deleteByName(name)`
- Example Rails equivalent: `QBWC.delete_job(name)`

#### B. Session Commands (2 files)

**6. Console/Command/Session/ListCommand.php**
- Command: `bin/magento qbwc:session:list [--active] [--company=]`
- Action: List sessions
- Filters: --active (progress < 100), --company
- Table output: Ticket, User, Company, Progress, Current Job, Created
- Use: `SessionRepositoryInterface->getActiveSessions()` or `getByCompany()`

**7. Console/Command/Session/CleanupCommand.php**
- Command: `bin/magento qbwc:session:cleanup [--hours=24]`
- Action: Delete old sessions
- Use: `SessionRepositoryInterface->deleteOldSessions(hours)`
- Output: "Deleted X sessions older than Y hours"

### Implementation Pattern

Each command should follow Magento standard:

```php
<?php
declare(strict_types=1);

namespace Vendor\QuickbooksConnector\Console\Command\Job;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Vendor\QuickbooksConnector\Api\JobRepositoryInterface;

class ListCommand extends Command
{
    private $jobRepository;

    public function __construct(
        JobRepositoryInterface $jobRepository,
        string $name = null
    ) {
        $this->jobRepository = $jobRepository;
        parent::__construct($name);
    }

    protected function configure()
    {
        $this->setName('qbwc:job:list')
            ->setDescription('List all QBWC jobs')
            ->addOption('company', 'c', InputOption::VALUE_REQUIRED, 'Filter by company');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $company = $input->getOption('company');

        // Get jobs
        $jobs = $company
            ? $this->jobRepository->getByCompany($company)
            : $this->jobRepository->getList($searchCriteria)->getItems();

        // Display table
        $table = new Table($output);
        $table->setHeaders(['ID', 'Name', 'Company', 'Enabled', 'Worker Class', 'Created']);

        foreach ($jobs as $job) {
            $table->addRow([
                $job->getEntityId(),
                $job->getName(),
                $job->getCompany(),
                $job->getEnabled() ? 'Yes' : 'No',
                $job->getWorkerClass(),
                $job->getCreatedAt()
            ]);
        }

        $table->render();
        return Command::SUCCESS;
    }
}
```

### di.xml Already Configured

The commands are already declared in `etc/di.xml` lines 87-100, just need implementation.

---

## Task 2: Admin UI (Priority Medium)

### Goal
Create admin panel for QBWC configuration and management.

### Files to Create

#### A. Configuration Panel (2 files)

**1. etc/adminhtml/system.xml**

Create admin configuration at: Stores → Configuration → Services → QuickBooks Web Connector

**Sections:**
```xml
<config>
    <system>
        <section id="qbwc" translate="label" sortOrder="400" showInDefault="1" showInWebsite="1" showInStore="1">
            <label>QuickBooks Web Connector</label>
            <tab>service</tab>
            <resource>Vendor_QuickbooksConnector::config</resource>

            <!-- General Settings -->
            <group id="general" translate="label" sortOrder="10" showInDefault="1">
                <label>General Settings</label>
                <field id="enabled" translate="label" type="select" sortOrder="10">
                    <label>Enabled</label>
                    <source_model>Magento\Config\Model\Config\Source\Yesno</source_model>
                </field>
                <field id="app_name" translate="label" type="text" sortOrder="20">
                    <label>Application Name</label>
                    <comment>Shown in QuickBooks Web Connector</comment>
                </field>
                <field id="company_file" translate="label" type="text" sortOrder="30">
                    <label>Company File Path</label>
                    <comment>Path to QuickBooks company file (empty for any open file)</comment>
                </field>
                <field id="run_every_n_minutes" translate="label" type="text" sortOrder="40">
                    <label>Run Every N Minutes</label>
                    <comment>Auto-run interval (leave empty for manual only)</comment>
                </field>
            </group>

            <!-- Authentication -->
            <group id="auth" translate="label" sortOrder="20" showInDefault="1">
                <label>Authentication</label>
                <field id="username" translate="label" type="text" sortOrder="10">
                    <label>Username</label>
                </field>
                <field id="password" translate="label" type="obscure" sortOrder="20">
                    <label>Password</label>
                    <backend_model>Magento\Config\Model\Config\Backend\Encrypted</backend_model>
                </field>
            </group>

            <!-- Advanced -->
            <group id="advanced" translate="label" sortOrder="30" showInDefault="1">
                <label>Advanced Settings</label>
                <field id="continue_on_error" translate="label" type="select" sortOrder="10">
                    <label>Continue On Error</label>
                    <source_model>Magento\Config\Model\Config\Source\Yesno</source_model>
                </field>
                <field id="log_requests_and_responses" translate="label" type="select" sortOrder="20">
                    <label>Log Requests and Responses</label>
                    <source_model>Magento\Config\Model\Config\Source\Yesno</source_model>
                </field>
            </group>
        </section>
    </system>
</config>
```

**2. etc/adminhtml/menu.xml**

Add menu: QuickBooks → Jobs, Sessions, Configuration

```xml
<config>
    <menu>
        <add id="Vendor_QuickbooksConnector::qbwc" title="QuickBooks" module="Vendor_QuickbooksConnector" sortOrder="80" resource="Vendor_QuickbooksConnector::qbwc"/>

        <add id="Vendor_QuickbooksConnector::jobs" title="Jobs" module="Vendor_QuickbooksConnector" sortOrder="10" parent="Vendor_QuickbooksConnector::qbwc" action="qbwc/job/index" resource="Vendor_QuickbooksConnector::jobs"/>

        <add id="Vendor_QuickbooksConnector::sessions" title="Sessions" module="Vendor_QuickbooksConnector" sortOrder="20" parent="Vendor_QuickbooksConnector::qbwc" action="qbwc/session/index" resource="Vendor_QuickbooksConnector::sessions"/>

        <add id="Vendor_QuickbooksConnector::config" title="Configuration" module="Vendor_QuickbooksConnector" sortOrder="30" parent="Vendor_QuickbooksConnector::qbwc" action="adminhtml/system_config/edit/section/qbwc" resource="Vendor_QuickbooksConnector::config"/>
    </menu>
</config>
```

#### B. Job Management Grid (4 files)

**3. Controller/Adminhtml/Job/Index.php**
- Display job grid
- Use UI Component

**4. Ui/Component/Listing/Job/DataProvider.php**
- Data provider for job grid
- Use JobRepository

**5. view/adminhtml/ui_component/qbwc_job_listing.xml**
- UI Component definition
- Columns: ID, Name, Company, Enabled, Worker Class, Actions
- Mass actions: Enable, Disable, Delete
- Filters: Company, Enabled

**6. view/adminhtml/layout/qbwc_job_index.xml**
- Layout for job grid page

#### C. Session Monitoring Grid (4 files)

**7. Controller/Adminhtml/Session/Index.php**
- Display session grid

**8. Ui/Component/Listing/Session/DataProvider.php**
- Data provider for session grid

**9. view/adminhtml/ui_component/qbwc_session_listing.xml**
- Columns: Ticket, User, Company, Progress, Current Job, Created
- Filters: Company, Progress, User
- Actions: View Details, Delete

**10. view/adminhtml/layout/qbwc_session_index.xml**
- Layout for session grid page

#### D. ACL & Routes (2 files)

**11. etc/acl.xml**
```xml
<config>
    <acl>
        <resources>
            <resource id="Magento_Backend::admin">
                <resource id="Vendor_QuickbooksConnector::qbwc" title="QuickBooks" sortOrder="80">
                    <resource id="Vendor_QuickbooksConnector::jobs" title="Jobs" sortOrder="10"/>
                    <resource id="Vendor_QuickbooksConnector::sessions" title="Sessions" sortOrder="20"/>
                    <resource id="Vendor_QuickbooksConnector::config" title="Configuration" sortOrder="30"/>
                </resource>
            </resource>
        </resources>
    </acl>
</config>
```

**12. etc/adminhtml/routes.xml**
```xml
<config>
    <router id="admin">
        <route id="qbwc" frontName="qbwc">
            <module name="Vendor_QuickbooksConnector"/>
        </route>
    </router>
</config>
```

---

## Implementation Requirements

### Must Follow:
1. **PSR-12 Coding Standards**
2. **Magento Best Practices**:
   - Use dependency injection
   - Use repositories (never direct models)
   - Use service contracts
   - Proper error handling
   - Logging

3. **CLI Commands**:
   - Return `Command::SUCCESS` or `Command::FAILURE`
   - Use Symfony Console components
   - Table formatting for lists
   - Confirmation for destructive operations

4. **Admin UI**:
   - Use UI Components (not deprecated grid)
   - ACL permissions
   - Proper form validation
   - Mass actions for grids

### Code Comments:
Add comments like:
```php
/**
 * List QBWC Jobs
 *
 * Magento CLI equivalent of Rails: QBWC.jobs
 * Rails source: lib/qbwc.rb:82-84
 */
```

---

## File Locations Summary

```
Vendor/QuickbooksConnector/
├── Console/Command/
│   ├── Job/
│   │   ├── ListCommand.php          ⚠️ CREATE
│   │   ├── CreateCommand.php        ⚠️ CREATE
│   │   ├── EnableCommand.php        ⚠️ CREATE
│   │   ├── DisableCommand.php       ⚠️ CREATE
│   │   └── DeleteCommand.php        ⚠️ CREATE
│   └── Session/
│       ├── ListCommand.php          ⚠️ CREATE
│       └── CleanupCommand.php       ⚠️ CREATE
│
├── Controller/Adminhtml/
│   ├── Job/
│   │   └── Index.php                ⚠️ CREATE
│   └── Session/
│       └── Index.php                ⚠️ CREATE
│
├── Ui/Component/Listing/
│   ├── Job/
│   │   └── DataProvider.php         ⚠️ CREATE
│   └── Session/
│       └── DataProvider.php         ⚠️ CREATE
│
├── view/adminhtml/
│   ├── ui_component/
│   │   ├── qbwc_job_listing.xml     ⚠️ CREATE
│   │   └── qbwc_session_listing.xml ⚠️ CREATE
│   └── layout/
│       ├── qbwc_job_index.xml       ⚠️ CREATE
│       └── qbwc_session_index.xml   ⚠️ CREATE
│
└── etc/
    ├── acl.xml                       ⚠️ CREATE
    └── adminhtml/
        ├── system.xml                ⚠️ CREATE
        ├── menu.xml                  ⚠️ CREATE
        └── routes.xml                ⚠️ CREATE
```

**Total: 19 files to create**

---

## Expected Output

After implementation:

### CLI Usage:
```bash
# List jobs
bin/magento qbwc:job:list
bin/magento qbwc:job:list --company="MyCompany.qbw"

# Create job
bin/magento qbwc:job:create sync_customers "Vendor\\Module\\Worker\\CustomerSync" --company="MyCompany.qbw"

# Enable/disable
bin/magento qbwc:job:enable sync_customers
bin/magento qbwc:job:disable sync_customers

# Delete
bin/magento qbwc:job:delete sync_customers --force

# Sessions
bin/magento qbwc:session:list --active
bin/magento qbwc:session:cleanup --hours=48
```

### Admin UI:
- **Menu**: QuickBooks → Jobs, Sessions, Configuration
- **Jobs Grid**: Manage jobs with enable/disable/delete
- **Sessions Grid**: Monitor active sessions
- **Config Panel**: Stores → Configuration → Services → QuickBooks Web Connector

---

## Testing Checklist

After implementation, verify:

### CLI:
- [ ] All 7 commands registered
- [ ] `bin/magento list` shows qbwc:* commands
- [ ] Job list displays correctly
- [ ] Job create works with validation
- [ ] Enable/disable toggles enabled field
- [ ] Delete removes job
- [ ] Session list shows active sessions
- [ ] Cleanup deletes old sessions

### Admin:
- [ ] Menu appears in admin sidebar
- [ ] Configuration page loads
- [ ] Jobs grid displays with data
- [ ] Sessions grid displays with data
- [ ] Mass actions work
- [ ] ACL permissions enforced

---

## Git Workflow

When complete:

```bash
git add Console/ Controller/Adminhtml/ Ui/ view/adminhtml/ etc/acl.xml etc/adminhtml/
git commit -m "Implement CLI Commands and Admin UI

Created 19 files:
- 7 CLI commands (job and session management)
- 12 Admin UI files (grids, config, menus, ACL)

Features:
- Job management: list, create, enable, disable, delete
- Session management: list, cleanup
- Admin grids with UI Components
- Configuration panel in admin
- ACL permissions

Total: ~2,000 lines of code
Module now 100% complete"

git push
```

---

## Final Status After Your Implementation

- Core Implementation: 100% ✅
- Callbacks System: 100% ✅
- QWC Download: 100% ✅
- **CLI Commands: 100% ✅** (your task)
- **Admin UI: 100% ✅** (your task)

**Overall: 100% Complete** 🎉

---

## Tips

1. **Start with CLI** (easier, faster testing)
2. **Test each command** before moving to next
3. **Admin UI** can use existing Repository methods
4. **UI Components** are preferred over deprecated grid
5. **Copy patterns** from Magento core modules (Catalog, Customer)
6. **Check ACL** - test with restricted admin user

Good luck! 🚀
