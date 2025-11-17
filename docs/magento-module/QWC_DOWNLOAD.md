# QuickBooks Web Connector - QWC File Download

## Overview

The QWC (QuickBooks Web Connector) file is a configuration file that tells QuickBooks how to connect to your Magento installation.

**Cloned from Rails:** `lib/qbwc/controller.rb:62-89`

---

## Download URL

```
https://yourstore.com/qbwc/qwc/download
```

**Route configuration:** `etc/frontend/routes.xml`
**Controller:** `Controller/Qwc/Download.php`

---

## Evidence from Rails Source

```ruby
# lib/qbwc/controller.rb:62-89
def qwc
  qwc = <<QWC
<QBWCXML>
   <AppName>#{app_name}</AppName>
   <AppID></AppID>
   <AppURL>#{qbwc_action_url(:only_path => false)}</AppURL>
   <AppDescription>Quickbooks integration</AppDescription>
   <AppSupport>#{QBWC.support_site_url || root_url(:protocol => 'https://')}</AppSupport>
   <UserName>#{QBWC.username}</UserName>
   <OwnerID>#{QBWC.owner_id}</OwnerID>
   <FileID>{#{file_id}}</FileID>
   <QBType>QBFS</QBType>
   <Style>Document</Style>
   #{scheduler_block}
</QBWCXML>
QWC
  send_data qwc, :filename => "app.qwc", :content_type => 'application/x-qwc'
end
```

---

## QWC File Structure

The generated QWC file contains:

```xml
<?xml version="1.0"?>
<QBWCXML>
   <AppName>Magento QuickBooks Connector</AppName>
   <AppID></AppID>
   <AppURL>https://yourstore.com/soap/index/index</AppURL>
   <AppDescription>QuickBooks integration for Magento</AppDescription>
   <AppSupport>https://yourstore.com/support</AppSupport>
   <UserName>qbwc_user</UserName>
   <OwnerID>{90A44FB5-33D9-4815-AC85-BC87A7E7D1EB}</OwnerID>
   <FileID>{90A44FB5-33D9-4815-AC85-BC87A7E7D1EB}</FileID>
   <QBType>QBFS</QBType>
   <Style>Document</Style>
   <Scheduler>
      <RunEveryNMinutes>60</RunEveryNMinutes>
   </Scheduler>
</QBWCXML>
```

---

## Configuration

Configure these settings in Admin Panel → Stores → Configuration → Services → QuickBooks Web Connector:

| Field | Config Path | Description | Default |
|-------|-------------|-------------|---------|
| **AppName** | `qbwc/general/app_name` | Application name shown in QBWC | "Magento QuickBooks Connector" |
| **AppURL** | Auto-generated | SOAP endpoint URL | `https://yourstore.com/soap/index/index` |
| **AppDescription** | `qbwc/general/app_description` | Description shown in QBWC | "QuickBooks integration for Magento" |
| **AppSupport** | `qbwc/general/support_url` | Support website URL | Your store base URL |
| **UserName** | `qbwc/auth/username` | Username for authentication | (configure) |
| **Password** | `qbwc/auth/password` | Password for authentication | (configure) |
| **OwnerID** | `qbwc/general/owner_id` | Unique owner GUID | Auto-generated |
| **FileID** | `qbwc/general/file_id` | Unique file GUID | Auto-generated |
| **RunEveryNMinutes** | `qbwc/general/run_every_n_minutes` | Auto-run interval (optional) | null (manual only) |

---

## Implementation Details

### Controller Location
`Vendor/QuickbooksConnector/Controller/Qwc/Download.php`

### Key Methods

**1. Generate SOAP URL**
```php
$soapUrl = $this->urlBuilder->getUrl(
    'soap/index/index',
    [
        '_type' => UrlInterface::URL_TYPE_WEB,
        '_secure' => true
    ]
);
```

**2. Generate QWC Content**
```php
$qwcContent = $this->config->generateQwcFileContent($soapUrl);
```
*Location: `Model/Config.php:280-310`*

**3. Send File Download**
```php
return $this->fileFactory->create(
    $filename,
    $qwcContent,
    \Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR,
    'application/x-qwc'  // ← Rails: content_type
);
```

---

## How to Use

### Step 1: Configure Module

1. Go to Admin Panel → Stores → Configuration
2. Navigate to Services → QuickBooks Web Connector
3. Set:
   - **Username**: `qbwc_user` (or your choice)
   - **Password**: Strong password
   - **Company File**: Path to QB file (or empty for any open file)
   - **App Name**: Your app name
   - **Run Every N Minutes**: 60 (for hourly sync) or leave empty

### Step 2: Download QWC File

Visit: `https://yourstore.com/qbwc/qwc/download`

File will download: `Magento_QuickBooks_Connector.qwc`

### Step 3: Install in QuickBooks Web Connector

1. Open QuickBooks Web Connector (QBWC)
2. Click "Add an Application"
3. Browse to downloaded .qwc file
4. Click "OK"
5. Enter the **password** you configured
6. Allow access when QuickBooks prompts

### Step 4: Verify Connection

1. In QBWC, select your application
2. Click "Update Selected"
3. Check status - should show "Ok" or job counts
4. Check Magento logs: `var/log/qbwc.log`

---

## Troubleshooting

### Problem: 404 Not Found

**Cause:** Routes not registered

**Solution:**
```bash
bin/magento cache:flush
bin/magento setup:upgrade
```

### Problem: Invalid QWC File

**Cause:** Missing configuration

**Solution:** Ensure all required settings are configured in Admin Panel

### Problem: Connection Refused

**Cause:** SOAP endpoint not accessible

**Solution:**
1. Check Magento SOAP is enabled: System → Web Services → SOAP
2. Verify HTTPS is working
3. Check firewall allows connections

### Problem: Authentication Failed

**Cause:** Username/password mismatch

**Solution:** Verify username and password in config match what you enter in QBWC

---

## Advanced: Custom QWC Generation

You can customize QWC generation by preference:

```php
namespace YourVendor\YourModule\Model;

use Vendor\QuickbooksConnector\Model\Config;

class CustomConfig extends Config
{
    public function generateQwcFileContent(string $appUrl): string
    {
        // Your custom logic
        $qwc = parent::generateQwcFileContent($appUrl);

        // Modify as needed
        return $qwc;
    }
}
```

**Register in di.xml:**
```xml
<preference for="Vendor\QuickbooksConnector\Model\Config"
            type="YourVendor\YourModule\Model\CustomConfig"/>
```

---

## Scheduler Block

**Optional:** Auto-run sync every N minutes

### Without Scheduler (Manual Only)
```xml
<QBWCXML>
   <!-- No Scheduler block -->
</QBWCXML>
```

User must manually click "Update Selected" in QBWC.

### With Scheduler (Auto-Run)
```xml
<QBWCXML>
   <Scheduler>
      <RunEveryNMinutes>60</RunEveryNMinutes>
   </Scheduler>
</QBWCXML>
```

QBWC automatically runs sync every 60 minutes.

**Configure:** Set `run_every_n_minutes` in Admin Panel

---

## Security Notes

1. **HTTPS Required:** QuickBooks Web Connector requires HTTPS
2. **Strong Password:** Use strong password for authentication
3. **Unique IDs:** OwnerID and FileID should be unique per installation
4. **Access Control:** Limit QWC download to admin users if needed

---

## Testing

### Test QWC Download
```bash
curl -v https://yourstore.com/qbwc/qwc/download
```

**Expected response:**
- Status: 200 OK
- Content-Type: application/x-qwc
- Body: Valid XML starting with `<QBWCXML>`

### Validate QWC File
```bash
xmllint --format yourfile.qwc
```

Should output formatted XML without errors.

---

## Comparison with Rails

| Feature | Rails QBWC | Magento 2 Module |
|---------|-----------|------------------|
| **Route** | `def qwc` in controller | `qbwc/qwc/download` |
| **Content type** | `application/x-qwc` | `application/x-qwc` ✅ |
| **Filename** | `Rails.application.name.qwc` | `{AppName}.qwc` ✅ |
| **SOAP URL** | `qbwc_action_url` | Auto-generated from Magento |
| **Config** | `QBWC.username`, etc. | Admin Panel configuration |
| **Scheduler** | `QBWC.minutes_to_run` | `run_every_n_minutes` config ✅ |

---

## Summary

✅ **Download URL**: `https://yourstore.com/qbwc/qwc/download`
✅ **Content Type**: `application/x-qwc` (matches Rails)
✅ **Configuration**: Via Admin Panel
✅ **Scheduler Support**: Optional auto-run
✅ **Security**: HTTPS required, authentication enforced

**Next:** See `CALLBACKS.md` for callback system documentation.
