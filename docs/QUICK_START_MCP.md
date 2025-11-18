# 🚀 Quick Start: MCP Server cho QuickBooks - 30 phút

## Tóm Tắt

Đây là hướng dẫn nhanh để chạy **MCP Server** giám sát QuickBooks Web Connector và cho phép AI Agent phân tích logs, trace flow step-by-step.

---

## Bước 1: Cài Đặt Enhanced Logging (10 phút)

### 1.1 Tạo Structured Logger

```bash
# Tạo initializer mới
touch config/initializers/qbwc_enhanced_logger.rb
```

Thêm nội dung:

```ruby
# config/initializers/qbwc_enhanced_logger.rb

require 'logger'
require 'json'

class QBWCStructuredLogger < Logger
  def format_message(severity, timestamp, progname, msg)
    {
      timestamp: timestamp.utc.iso8601(3),
      severity: severity,
      message: msg,
      pid: Process.pid
    }.to_json + "\n"
  end
end

qbwc_log = Rails.root.join('log', 'qbwc_structured.log')
QBWC.logger = QBWCStructuredLogger.new(qbwc_log)
QBWC.log_requests_and_responses = true
```

### 1.2 Restart Rails

```bash
bundle exec rails restart
# hoặc
touch tmp/restart.txt
```

### 1.3 Kiểm tra log

```bash
tail -f log/qbwc_structured.log
```

Bạn sẽ thấy JSON logs như:

```json
{"timestamp":"2024-01-15T10:00:00.123Z","severity":"INFO","message":"Authentication succeeded","pid":12345}
```

---

## Bước 2: Setup MCP Server (15 phút)

### 2.1 Tạo Project

```bash
cd /home/user/qbwc
mkdir qbwc-mcp-server
cd qbwc-mcp-server

npm init -y
npm install @modelcontextprotocol/sdk better-sqlite3 tail xml2js zod
npm install -D @types/node @types/better-sqlite3 typescript
```

### 2.2 TypeScript Config

```bash
cat > tsconfig.json << 'EOF'
{
  "compilerOptions": {
    "target": "ES2022",
    "module": "Node16",
    "moduleResolution": "Node16",
    "outDir": "./build",
    "rootDir": "./src",
    "strict": true,
    "esModuleInterop": true,
    "skipLibCheck": true
  },
  "include": ["src/**/*"]
}
EOF
```

### 2.3 Copy MCP Server Code

```bash
mkdir -p src
# Copy code từ docs/MCP_SERVER_PROPOSAL.md (section 2.3)
# hoặc tôi sẽ tạo file riêng
```

### 2.4 Build

```bash
npm run build
```

---

## Bước 3: Tích Hợp với Claude Desktop (5 phút)

### 3.1 Tìm config file

**macOS:**
```bash
open ~/Library/Application\ Support/Claude/
```

**Windows:**
```cmd
explorer %APPDATA%\Claude
```

### 3.2 Sửa `claude_desktop_config.json`

```json
{
  "mcpServers": {
    "qbwc": {
      "command": "node",
      "args": [
        "/home/user/qbwc/qbwc-mcp-server/build/index.js"
      ],
      "env": {
        "QBWC_LOG_PATH": "/home/user/qbwc/log/qbwc_structured.log",
        "QBWC_DB_PATH": "/home/user/qbwc/qbwc-mcp-server/qbwc_mcp.db"
      }
    }
  }
}
```

### 3.3 Restart Claude Desktop

Thoát và mở lại Claude Desktop.

---

## Bước 4: Test với AI (Ngay lập tức!)

Mở Claude Desktop và thử:

### Test 1: List Sessions

```
🤖 Prompt: "Show me all QuickBooks sessions"
```

AI sẽ dùng tool `qb_sessions_list()` và trả về:

```json
{
  "total": 3,
  "sessions": [
    {
      "ticket": "abc123...",
      "job": "CustomerWorker",
      "company": "MyCompany.qbw",
      "started": "2024-01-15T10:00:00Z",
      "progress": 100
    }
  ]
}
```

### Test 2: Trace Flow

```
🤖 Prompt: "Trace the complete flow of CustomerWorker job"
```

AI sẽ show step-by-step flow:

```
Session: abc123
├─ authentication → success
├─ request_sent → CustomerQueryRq
├─ response_received → 150 customers (progress: 50%)
├─ request_sent → CustomerQueryRq (iterator)
├─ response_received → 150 customers (progress: 100%)
└─ close_connection → success
```

### Test 3: Error Analysis

```
🤖 Prompt: "Analyze errors in the last hour"
```

AI sẽ diagnosis:

```
Found 2 errors:
1. Error 3100 at 10:05:23
   - Job: InvoiceWorker
   - Cause: Duplicate invoice number INV-001
   - Recommendation: Implement query-before-add pattern

2. Error 500 at 10:10:45
   - Job: CustomerWorker
   - Cause: QB internal error
   - Recommendation: Check QB file integrity
```

---

## Kiến Trúc Tổng Quan

```
┌────────────────┐
│  QuickBooks    │
│   Desktop      │
└───────┬────────┘
        │ SOAP/XML
        ↓
┌────────────────┐      ┌──────────────┐
│  Rails QBWC    │─────→│ Structured   │
│    Server      │      │   Logs       │
└────────────────┘      │ (JSON)       │
                        └──────┬───────┘
                               │
                        ┌──────↓───────┐
                        │  MCP Server   │
                        │  (Node.js)    │
                        │               │
                        │  - Parse logs │
                        │  - Store DB   │
                        │  - Expose     │
                        │    tools      │
                        └──────┬────────┘
                               │
                        ┌──────↓────────┐
                        │ Claude Desktop │
                        │   (AI Agent)   │
                        │                │
                        │ 6 MCP Tools:   │
                        │ - sessions     │
                        │ - details      │
                        │ - analyze      │
                        │ - trace        │
                        │ - diagnose     │
                        │ - stats        │
                        └────────────────┘
```

---

## 6 MCP Tools Available

| Tool | Purpose | Example |
|------|---------|---------|
| `qb_sessions_list` | List active sessions | "Show all sessions" |
| `qb_session_details` | Get session timeline | "Details of ticket abc123" |
| `qb_analyze_logs` | Natural language search | "Find errors in last hour" |
| `qb_flow_trace` | Trace job flow | "Trace CustomerWorker" |
| `qb_error_diagnosis` | Diagnose errors | "Why error 3100?" |
| `qb_performance_stats` | Performance metrics | "Which job is slowest?" |

---

## Evidence-Based Analysis

MCP Server cung cấp **concrete evidence** cho AI:

### Example: AI phân tích lỗi

```
User: "Tại sao invoice import bị lỗi?"

AI Agent:
📊 Evidence từ logs:
   - Timestamp: 2024-01-15 10:05:23
   - Error Code: 3100
   - Message: "Name already exists"
   - Request XML: <InvoiceAddRq><InvoiceAdd><RefNumber>INV-001</RefNumber>...

🔍 Root Cause:
   Invoice number "INV-001" đã tồn tại trong QuickBooks.
   Rails Worker cố gắng thêm duplicate record.

✅ Solution:
   1. Thêm unique validation trước khi sync
   2. Implement upsert pattern:
      - Query invoice by RefNumber
      - If exists → InvoiceModRq
      - If not exists → InvoiceAddRq

📝 Code fix:
   ```ruby
   class InvoiceWorker < QBWC::Worker
     def requests(job, session, data)
       invoice_number = data['invoice_number']

       # Query first
       query_request = {
         invoice_query_rq: {
           ref_number_filter: {
             ref_number: invoice_number
           }
         }
       }

       # Store query result, then decide Add or Mod
       [query_request]
     end
   end
   ```
```

---

## Think Step-by-Step Example

Khi bạn hỏi: **"QuickBooks session hoạt động như thế nào?"**

AI Agent với MCP sẽ trace:

```
Step 1: Authentication
├─ Tool: qb_sessions_list()
├─ Evidence: Session abc123 started at 10:00:00
└─ Finding: User 'admin' authenticated successfully

Step 2: Job Queue
├─ Tool: qb_session_details(ticket: "abc123")
├─ Evidence: 2 jobs pending: [CustomerWorker, InvoiceWorker]
└─ Finding: Jobs run sequentially

Step 3: Request Cycle
├─ Tool: qb_flow_trace(job_name: "CustomerWorker")
├─ Evidence:
│  ├─ 10:00:01 - request_sent: CustomerQueryRq
│  ├─ 10:00:03 - response_received: 150 records (progress: 50%)
│  ├─ 10:00:04 - request_sent: CustomerQueryRq (iterator_id: xyz)
│  └─ 10:00:06 - response_received: 150 records (progress: 100%)
└─ Finding: Uses iterator for pagination (150 records per batch)

Step 4: Error Handling
├─ Tool: qb_error_diagnosis(ticket: "abc123")
├─ Evidence: No errors in CustomerWorker
├─ Evidence: Error 3100 in InvoiceWorker at 10:00:07
└─ Finding: Session stopped at 50% due to invoice error

Step 5: Performance
├─ Tool: qb_performance_stats(time_range: "1h")
├─ Evidence:
│  ├─ CustomerWorker: avg 1.2s per request
│  └─ InvoiceWorker: avg 4.8s per request
└─ Finding: InvoiceWorker 4x slower → needs optimization

Conclusion:
QuickBooks session follows this flow:
1. Authenticate → Create session ticket
2. Queue jobs → Process sequentially
3. Each job → Multiple request/response cycles
4. Iterator → Handle large datasets (>100 records)
5. Error → Stop session and log diagnostics

Performance bottleneck: InvoiceWorker XML parsing
Recommendation: Enable streaming parser or batch smaller
```

---

## Troubleshooting

### MCP Server không start

```bash
# Check logs
cat ~/.config/Claude/logs/mcp-server-qbwc.log

# Test manually
cd qbwc-mcp-server
node build/index.js
# Should print: "Starting QuickBooks MCP Server..."
```

### Claude không thấy tools

```bash
# Verify config
cat ~/Library/Application\ Support/Claude/claude_desktop_config.json

# Check permissions
ls -la qbwc-mcp-server/build/index.js

# Rebuild
cd qbwc-mcp-server
npm run build
```

### Logs không xuất hiện

```bash
# Check Rails logger
tail -f log/qbwc_structured.log

# Trigger a test request
curl -X POST http://localhost:3000/qbwc/action \
  -H "Content-Type: text/xml" \
  -d '<soap:Envelope>...</soap:Envelope>'
```

---

## Next Steps

1. ✅ **Test với QuickBooks Web Connector**
   ```bash
   # Download QWC file
   curl http://localhost:3000/qbwc/qwc > test.qwc

   # Open in QBWC
   # Add password
   # Click "Update Selected"
   ```

2. ✅ **Monitor real-time trong Claude**
   ```
   "Watch QuickBooks session abc123 in real-time"
   ```

3. ✅ **Tạo custom workers**
   ```ruby
   class MyWorker < QBWC::Worker
     def requests(job, session, data)
       # AI có thể analyze flow của worker này
     end
   end
   ```

4. ✅ **Production deployment**
   - Enable log rotation
   - Setup database backup
   - Monitor MCP server uptime

---

## Tài Liệu Chi Tiết

Xem thêm trong `docs/MCP_SERVER_PROPOSAL.md` để:
- Full source code của MCP server
- Advanced configuration
- Production deployment guide
- Performance optimization

---

**Estimated Time**: 30 phút
**Difficulty**: Medium
**Prerequisites**: Rails app đã có QBWC gem, Node.js 18+

*Generated by Claude Code - Quick Start Guide*
