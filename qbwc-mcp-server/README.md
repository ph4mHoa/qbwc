# QuickBooks Web Connector - MCP Server

AI-powered monitoring and analysis for QuickBooks Web Connector integration.

## 🎯 Overview

This MCP (Model Context Protocol) server enables AI agents like Claude to:

- ✅ **Monitor** QuickBooks sessions in real-time
- ✅ **Trace** complete request/response flows step-by-step
- ✅ **Analyze** logs with natural language queries
- ✅ **Diagnose** errors with root cause analysis
- ✅ **Optimize** performance with statistical insights
- ✅ **Understand** business logic through evidence-based analysis

## 🏗️ Architecture

```
QuickBooks Desktop
    ↓ (SOAP/XML)
Rails QBWC Server → Structured JSON Logs
    ↓
MCP Server (Node.js)
    ├─ Log Parser (tail + JSON)
    ├─ SQLite Database (event storage)
    └─ 6 MCP Tools for AI
         ↓
Claude Desktop (AI Agent)
```

## 📦 Installation

### Prerequisites

- Node.js 18+
- Rails application with QBWC gem
- Claude Desktop (for AI integration)

### Step 1: Install Dependencies

```bash
cd qbwc-mcp-server
npm install
```

### Step 2: Build TypeScript

```bash
npm run build
```

### Step 3: Configure Environment

```bash
# Set environment variables
export QBWC_LOG_PATH="/path/to/rails/log/qbwc_structured.log"
export QBWC_DB_PATH="./qbwc_mcp.db"
export RAILS_ROOT="/path/to/rails"
```

### Step 4: Test Run

```bash
npm start
```

You should see:

```
╔═══════════════════════════════════════════════════════════╗
║  QuickBooks Web Connector - MCP Server                   ║
║  AI-Powered Monitoring & Analysis                         ║
╚═══════════════════════════════════════════════════════════╝

✓ Database initialized: ./qbwc_mcp.db
✓ Watching log file: /path/to/rails/log/qbwc_structured.log
✓ MCP Server ready
✓ 6 tools available for AI analysis
```

## 🔧 Claude Desktop Configuration

Add to your Claude Desktop config:

**macOS:** `~/Library/Application Support/Claude/claude_desktop_config.json`

**Windows:** `%APPDATA%/Claude/claude_desktop_config.json`

```json
{
  "mcpServers": {
    "qbwc": {
      "command": "node",
      "args": ["/absolute/path/to/qbwc-mcp-server/build/index.js"],
      "env": {
        "QBWC_LOG_PATH": "/absolute/path/to/rails/log/qbwc_structured.log",
        "QBWC_DB_PATH": "/absolute/path/to/qbwc-mcp-server/qbwc_mcp.db"
      }
    }
  }
}
```

**Important:** Use absolute paths, not relative paths.

Restart Claude Desktop after configuration.

## 🛠️ Available MCP Tools

### 1. `qb_sessions_list`

List all QuickBooks sessions with status and progress.

**Example Usage in Claude:**
```
"Show me all active QuickBooks sessions"
```

**Output:**
```json
{
  "total": 3,
  "sessions": [
    {
      "ticket": "abc123...",
      "job": "CustomerWorker",
      "company": "MyCompany.qbw",
      "started": "2024-01-15T10:00:00Z",
      "lastActivity": "2024-01-15T10:05:23Z",
      "progress": 100,
      "status": "closed",
      "eventCount": 15,
      "responseCount": 5
    }
  ]
}
```

---

### 2. `qb_session_details`

Get detailed timeline for a specific session.

**Example Usage:**
```
"Show details of session abc123"
```

**Output:**
```json
{
  "ticket": "abc123",
  "totalEvents": 15,
  "timeline": [
    {
      "timestamp": "2024-01-15T10:00:00Z",
      "eventType": "authentication",
      "severity": "INFO",
      "details": {
        "job": "CustomerWorker",
        "message": "Authentication succeeded"
      }
    },
    {
      "timestamp": "2024-01-15T10:00:01Z",
      "eventType": "request_sent",
      "severity": "INFO",
      "details": {
        "job": "CustomerWorker",
        "progress": 0
      }
    }
  ]
}
```

---

### 3. `qb_analyze_logs`

Natural language log analysis.

**Example Queries:**
```
"Show me all errors in the last hour"
"Find failed invoice jobs"
"What happened with CustomerWorker today?"
```

**Output:**
```json
{
  "query": "show me all errors in the last hour",
  "timeRange": "1h",
  "matchCount": 2,
  "events": [
    {
      "timestamp": "2024-01-15T10:05:23Z",
      "type": "response_received",
      "severity": "ERROR",
      "job": "InvoiceWorker",
      "statusCode": 3100,
      "message": "Name already exists"
    }
  ]
}
```

---

### 4. `qb_flow_trace`

Trace complete flow of a specific job.

**Example Usage:**
```
"Trace the flow of CustomerWorker job"
```

**Output:**
```json
{
  "jobName": "CustomerWorker",
  "flowSteps": 10,
  "flow": [
    {
      "timestamp": "2024-01-15T10:00:01Z",
      "eventType": "request_sent",
      "statusCode": null,
      "progress": 0
    },
    {
      "timestamp": "2024-01-15T10:00:03Z",
      "eventType": "response_received",
      "statusCode": 0,
      "progress": 50
    }
  ]
}
```

With XML included (`include_xml: true`):
```
"Trace CustomerWorker with full XML data"
```

---

### 5. `qb_error_diagnosis`

AI-powered error diagnosis with recommendations.

**Example Usage:**
```
"Diagnose error code 3100"
"Why did session abc123 fail?"
```

**Output:**
```json
{
  "errorCode": "3100",
  "errorCount": 5,
  "errors": [
    {
      "timestamp": "2024-01-15T10:05:23Z",
      "errorCode": 3100,
      "message": "Name already exists",
      "job": "InvoiceWorker",
      "context": {
        "ticket": "abc123",
        "company": "MyCompany.qbw",
        "request": "<InvoiceAddRq><InvoiceAdd><RefNumber>INV-001</RefNumber>..."
      }
    }
  ],
  "diagnosis": "Error 3100: Name/Reference already exists. This indicates duplicate record insertion. Check for existing records before adding new ones."
}
```

---

### 6. `qb_performance_stats`

Performance analysis and bottleneck identification.

**Example Usage:**
```
"Show performance stats for the last 24 hours"
"Which job is slowest?"
```

**Output:**
```json
{
  "timeRange": "24h",
  "groupBy": "job_name",
  "statistics": [
    {
      "category": "CustomerWorker",
      "event_count": 45,
      "avg_duration_ms": 1250,
      "max_duration_ms": 3500,
      "error_count": 0
    },
    {
      "category": "InvoiceWorker",
      "event_count": 120,
      "avg_duration_ms": 4800,
      "max_duration_ms": 12000,
      "error_count": 5
    }
  ]
}
```

## 📊 AI Analysis Examples

### Example 1: Understand Flow

**User:** "How does a QuickBooks session work?"

**AI Response (using MCP tools):**

```
Step 1: Authentication
├─ Evidence: qb_sessions_list() shows session abc123
├─ Started: 2024-01-15 10:00:00
└─ Jobs pending: CustomerWorker, InvoiceWorker

Step 2: Job Processing
├─ Evidence: qb_flow_trace(job_name: "CustomerWorker")
├─ Request sent: CustomerQueryRq at 10:00:01
├─ Response received: 150 customers at 10:00:03 (progress: 50%)
├─ Iterator used for pagination
└─ Completed at 10:00:06 (progress: 100%)

Step 3: Error Handling
├─ Evidence: qb_error_diagnosis(error_code: "3100")
├─ InvoiceWorker failed at 10:00:07
├─ Error: Duplicate invoice number
└─ Session stopped at 50% progress

Recommendation: Implement query-before-add pattern to avoid duplicates.
```

### Example 2: Diagnose Performance

**User:** "Why is my QuickBooks sync slow?"

**AI Response:**

```
Performance Analysis (using qb_performance_stats):

InvoiceWorker is 3.8x slower than CustomerWorker:
- Average: 4.8s per request (vs 1.2s)
- Maximum: 12s peak latency
- Error rate: 4.2% (5 errors / 120 requests)

Root Cause:
1. Large QBXML responses not using iterator
2. Sequential processing of 500+ invoices
3. Network latency accumulation

Evidence-based recommendations:
1. Enable iterator for datasets >100 records
2. Batch invoices in chunks of 50
3. Add progress logging every 10 records
4. Implement retry logic for timeout errors

Code fix:
```ruby
class InvoiceWorker < QBWC::Worker
  def requests(job, session, data)
    # Use iterator for large datasets
    {
      invoice_query_rq: {
        max_returned: 100,
        iterator: 'Start'
      }
    }
  end
end
```

## 🔍 Troubleshooting

### MCP Server not starting

```bash
# Check Node.js version
node --version  # Should be 18+

# Rebuild
npm run clean
npm run build

# Test manually
node build/index.js
```

### Claude doesn't see the tools

1. Check config file path:
   ```bash
   # macOS
   cat ~/Library/Application\ Support/Claude/claude_desktop_config.json
   ```

2. Verify absolute paths (no `~` or relative paths)

3. Restart Claude Desktop completely

4. Check Claude logs:
   ```bash
   # macOS
   tail -f ~/Library/Logs/Claude/mcp*.log
   ```

### No events in database

1. Check Rails structured logging:
   ```bash
   tail -f /path/to/rails/log/qbwc_structured.log
   ```

2. Verify JSON format:
   ```bash
   tail -1 log/qbwc_structured.log | jq .
   ```

3. Trigger a test request:
   ```bash
   curl http://localhost:3000/qbwc/action
   ```

### Database locked errors

```bash
# Check for other processes
lsof qbwc_mcp.db

# Reset database
rm qbwc_mcp.db
npm start  # Will recreate
```

## 📝 Development

### Project Structure

```
qbwc-mcp-server/
├── src/
│   ├── index.ts          # Main MCP server
│   ├── tools/            # Tool implementations (future)
│   ├── services/         # Business logic (future)
│   └── types/            # TypeScript types (future)
├── build/                # Compiled JavaScript
├── package.json
├── tsconfig.json
└── README.md
```

### Build Commands

```bash
npm run build     # Compile TypeScript
npm run dev       # Watch mode (auto-rebuild)
npm start         # Run server
npm run clean     # Remove build artifacts
```

### Adding New Tools

1. Define tool in `tools` array
2. Implement handler in `handleToolCall()`
3. Add database queries as needed
4. Update README with examples

## 🔒 Security Notes

- MCP server runs locally (stdio transport)
- No network ports exposed
- Database is SQLite (file-based)
- Logs may contain sensitive QB data
- Configure `log_requests_and_responses` carefully in production

## 📚 Resources

- [MCP SDK Documentation](https://github.com/modelcontextprotocol/sdk)
- [QBWC Gem](https://github.com/skryl/qbwc)
- [QuickBooks SDK](https://developer.intuit.com/)
- [Claude Desktop](https://claude.ai/desktop)

## 📄 License

MIT

## 🤝 Contributing

Contributions welcome! Please:

1. Fork the repository
2. Create a feature branch
3. Add tests if applicable
4. Submit a pull request

## 📞 Support

For issues or questions:
- GitHub Issues: [qbwc-mcp-server](https://github.com/your-repo/qbwc-mcp-server)
- QBWC Gem Issues: [qbwc](https://github.com/skryl/qbwc)

---

**Built with:** TypeScript, MCP SDK, SQLite, Node.js

**Compatible with:** Claude Desktop, QuickBooks Desktop, Rails 5.0+
