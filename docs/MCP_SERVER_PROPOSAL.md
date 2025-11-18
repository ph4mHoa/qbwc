# MCP Server Proposal: QuickBooks Integration Monitoring & Analysis

## 🎯 Mục Tiêu

Xây dựng một **Model Context Protocol (MCP) Server** để:

1. **Monitor real-time** QuickBooks Web Connector sessions
2. **Analyze logs** với AI-powered insights
3. **Visualize flow** của SOAP requests/responses
4. **Debug efficiently** với structured evidence
5. **Provide context** cho AI agents để hiểu business logic

---

## 🏗️ Kiến Trúc Đề Xuất

```
┌─────────────────────────────────────────────────────────────┐
│                     QuickBooks Desktop                       │
│                   (Via Web Connector)                        │
└────────────────────────┬────────────────────────────────────┘
                         │ SOAP/XML
                         ↓
┌─────────────────────────────────────────────────────────────┐
│              Rails QBWC Server (Ruby Gem)                   │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐   │
│  │Controller│→ │ Session  │→ │   Job    │→ │  Worker  │   │
│  └──────────┘  └──────────┘  └──────────┘  └──────────┘   │
│                         │                                    │
│                         ↓                                    │
│              ┌──────────────────────┐                       │
│              │  Enhanced Logger      │                       │
│              │  (Structured JSON)    │                       │
│              └──────────────────────┘                       │
└────────────────────────┬────────────────────────────────────┘
                         │
                         ↓
┌─────────────────────────────────────────────────────────────┐
│                    MCP Server (Node.js)                      │
│  ┌────────────────────────────────────────────────────────┐ │
│  │  MCP Tools (Resources & Prompts):                      │ │
│  │                                                         │ │
│  │  1. qb_sessions_list()      → List active sessions    │ │
│  │  2. qb_session_details(id)  → Get session flow        │ │
│  │  3. qb_analyze_logs(query)  → AI-powered analysis     │ │
│  │  4. qb_flow_trace(job_name) → Trace request/response  │ │
│  │  5. qb_error_diagnosis(id)  → Diagnose errors         │ │
│  │  6. qb_performance_stats()  → Performance metrics     │ │
│  └────────────────────────────────────────────────────────┘ │
│                         │                                    │
│              ┌──────────────────────┐                       │
│              │  Log Aggregator       │                       │
│              │  (Tail + Parse JSON)  │                       │
│              └──────────────────────┘                       │
│                         │                                    │
│              ┌──────────────────────┐                       │
│              │  SQLite Database      │                       │
│              │  (Session History)    │                       │
│              └──────────────────────┘                       │
└────────────────────────┬────────────────────────────────────┘
                         │
                         ↓
┌─────────────────────────────────────────────────────────────┐
│              AI Agent (Claude Desktop)                       │
│                                                              │
│  Uses MCP tools to:                                         │
│  - Check current QB sessions                                │
│  - Analyze errors automatically                             │
│  - Understand business flow                                 │
│  - Provide debugging suggestions                            │
└─────────────────────────────────────────────────────────────┘
```

---

## 📝 Quy Trình Triển Khai (Step-by-Step)

### Phase 1: Enhanced Logging (Ruby Side)

**Step 1.1: Cấu hình Structured Logging**

```ruby
# config/initializers/qbwc_enhanced_logger.rb

require 'logger'
require 'json'

class QBWCStructuredLogger < Logger
  def format_message(severity, timestamp, progname, msg)
    {
      timestamp: timestamp.utc.iso8601(3),
      severity: severity,
      progname: progname,
      message: msg,
      pid: Process.pid,
      thread: Thread.current.object_id
    }.to_json + "\n"
  end
end

# Tạo log file riêng cho QBWC
qbwc_log_path = Rails.root.join('log', 'qbwc_structured.log')
qbwc_logger = QBWCStructuredLogger.new(qbwc_log_path, 'daily')
qbwc_logger.level = Logger::DEBUG

# Configure QBWC to use structured logger
QBWC.configure do |c|
  c.logger = qbwc_logger
  c.log_requests_and_responses = true
end
```

**Step 1.2: Thêm Event Tracking vào Controller**

```ruby
# lib/qbwc/controller.rb (enhance existing methods)

module QBWC
  class Controller < ApplicationController
    # Add instrumentation
    around_action :log_soap_event

    def authenticate(username, password)
      event_data = {
        event_type: 'authentication',
        username: username,
        timestamp: Time.now.utc.iso8601(3)
      }

      result = # ... existing authentication logic

      event_data[:success] = !result[0].blank?
      event_data[:jobs_pending] = result[1].split(',') if result[1]

      QBWC.logger.info(event_data.to_json)
      result
    end

    def send_request_xml(ticket, strHCPResponse, strCompanyFileName,
                         qbXMLCountry, qbXMLMajorVers, qbXMLMinorVers)
      session = Session.get(ticket)

      event_data = {
        event_type: 'request_sent',
        ticket: ticket,
        company: strCompanyFileName,
        job: session.current_job_name,
        qbxml_version: "#{qbXMLMajorVers}.#{qbXMLMinorVers}",
        timestamp: Time.now.utc.iso8601(3)
      }

      request = session.next_request
      event_data[:request_xml] = request if QBWC.log_requests_and_responses

      QBWC.logger.info(event_data.to_json)
      request
    end

    def receive_response_xml(ticket, response, hresult, message)
      session = Session.get(ticket)

      event_data = {
        event_type: 'response_received',
        ticket: ticket,
        job: session.current_job_name,
        hresult: hresult,
        message: message,
        timestamp: Time.now.utc.iso8601(3)
      }

      if QBWC.log_requests_and_responses
        event_data[:response_xml] = response
      end

      session.response = response
      progress = session.progress

      event_data[:progress] = progress
      event_data[:status_code] = session.status_code
      event_data[:status_severity] = session.status_severity

      QBWC.logger.info(event_data.to_json)
      progress
    end

    private

    def log_soap_event
      start_time = Time.now
      yield
      duration = ((Time.now - start_time) * 1000).round(2)

      QBWC.logger.debug({
        event_type: 'soap_call',
        action: params[:action],
        duration_ms: duration
      }.to_json)
    end
  end
end
```

**Step 1.3: Database Migration cho Event Tracking**

```ruby
# db/migrate/YYYYMMDDHHMMSS_add_event_tracking_to_qbwc.rb

class AddEventTrackingToQbwc < ActiveRecord::Migration[7.0]
  def change
    create_table :qbwc_events do |t|
      t.string :event_type, null: false
      t.string :ticket
      t.string :job_name
      t.string :company
      t.text :request_xml
      t.text :response_xml
      t.integer :status_code
      t.string :status_severity
      t.float :duration_ms
      t.json :metadata
      t.timestamps
    end

    add_index :qbwc_events, :ticket
    add_index :qbwc_events, :event_type
    add_index :qbwc_events, :created_at
    add_index :qbwc_events, [:job_name, :created_at]
  end
end
```

---

### Phase 2: MCP Server Implementation (Node.js)

**Step 2.1: Project Structure**

```
qbwc-mcp-server/
├── package.json
├── tsconfig.json
├── src/
│   ├── index.ts              # MCP server entry point
│   ├── tools/
│   │   ├── sessions.ts       # Session management tools
│   │   ├── logs.ts           # Log analysis tools
│   │   ├── flow.ts           # Flow tracing tools
│   │   └── diagnostics.ts    # Error diagnosis tools
│   ├── services/
│   │   ├── logParser.ts      # Parse structured logs
│   │   ├── database.ts       # SQLite for caching
│   │   └── analyzer.ts       # AI-powered analysis
│   └── types/
│       └── qbwc.ts           # TypeScript definitions
└── README.md
```

**Step 2.2: Package Configuration**

```json
{
  "name": "@qbwc/mcp-server",
  "version": "1.0.0",
  "description": "MCP Server for QuickBooks Web Connector monitoring and analysis",
  "type": "module",
  "bin": {
    "qbwc-mcp": "./build/index.js"
  },
  "scripts": {
    "build": "tsc",
    "dev": "tsc --watch",
    "start": "node build/index.js"
  },
  "dependencies": {
    "@modelcontextprotocol/sdk": "^1.0.4",
    "better-sqlite3": "^11.0.0",
    "tail": "^2.2.6",
    "xml2js": "^0.6.2",
    "zod": "^3.23.0"
  },
  "devDependencies": {
    "@types/node": "^20.0.0",
    "@types/better-sqlite3": "^7.6.9",
    "typescript": "^5.3.0"
  }
}
```

**Step 2.3: Core MCP Server Implementation**

```typescript
// src/index.ts

import { Server } from '@modelcontextprotocol/sdk/server/index.js';
import { StdioServerTransport } from '@modelcontextprotocol/sdk/server/stdio.js';
import {
  CallToolRequestSchema,
  ListToolsRequestSchema,
  Tool
} from '@modelcontextprotocol/sdk/types.js';
import Database from 'better-sqlite3';
import { Tail } from 'tail';
import { parseStringPromise } from 'xml2js';
import path from 'path';
import fs from 'fs';

// Configuration
interface ServerConfig {
  logFilePath: string;
  dbPath: string;
  railsRoot: string;
}

const config: ServerConfig = {
  logFilePath: process.env.QBWC_LOG_PATH ||
    '/path/to/rails/log/qbwc_structured.log',
  dbPath: process.env.QBWC_DB_PATH || './qbwc_mcp.db',
  railsRoot: process.env.RAILS_ROOT || '/path/to/rails'
};

// Database setup
const db = new Database(config.dbPath);
db.exec(`
  CREATE TABLE IF NOT EXISTS events (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    timestamp TEXT NOT NULL,
    event_type TEXT NOT NULL,
    ticket TEXT,
    job_name TEXT,
    company TEXT,
    severity TEXT,
    data TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
  );

  CREATE INDEX IF NOT EXISTS idx_events_timestamp ON events(timestamp);
  CREATE INDEX IF NOT EXISTS idx_events_type ON events(event_type);
  CREATE INDEX IF NOT EXISTS idx_events_ticket ON events(ticket);
`);

// Log parser and watcher
function startLogWatcher() {
  if (!fs.existsSync(config.logFilePath)) {
    console.error(`Log file not found: ${config.logFilePath}`);
    return;
  }

  const tail = new Tail(config.logFilePath, {
    fromBeginning: false,
    follow: true,
    useWatchFile: true
  });

  tail.on('line', (line: string) => {
    try {
      const event = JSON.parse(line);

      const stmt = db.prepare(`
        INSERT INTO events (timestamp, event_type, ticket, job_name, company, severity, data)
        VALUES (?, ?, ?, ?, ?, ?, ?)
      `);

      stmt.run(
        event.timestamp,
        event.event_type || event.message?.event_type || 'unknown',
        event.ticket || null,
        event.job || event.job_name || null,
        event.company || null,
        event.severity || 'INFO',
        JSON.stringify(event)
      );
    } catch (err) {
      // Skip non-JSON lines or parsing errors
    }
  });

  tail.on('error', (error: Error) => {
    console.error('Tail error:', error);
  });
}

// MCP Server Tools
const tools: Tool[] = [
  {
    name: 'qb_sessions_list',
    description: 'List all active and recent QuickBooks sessions with their status',
    inputSchema: {
      type: 'object',
      properties: {
        limit: {
          type: 'number',
          description: 'Number of sessions to return (default: 10)',
          default: 10
        },
        active_only: {
          type: 'boolean',
          description: 'Show only active sessions',
          default: false
        }
      }
    }
  },
  {
    name: 'qb_session_details',
    description: 'Get detailed flow trace for a specific QuickBooks session',
    inputSchema: {
      type: 'object',
      properties: {
        ticket: {
          type: 'string',
          description: 'Session ticket ID'
        }
      },
      required: ['ticket']
    }
  },
  {
    name: 'qb_analyze_logs',
    description: 'Analyze QuickBooks logs with natural language queries',
    inputSchema: {
      type: 'object',
      properties: {
        query: {
          type: 'string',
          description: 'Natural language query (e.g., "show me all errors in the last hour")'
        },
        time_range: {
          type: 'string',
          description: 'Time range: 1h, 24h, 7d, 30d (default: 24h)',
          default: '24h'
        }
      },
      required: ['query']
    }
  },
  {
    name: 'qb_flow_trace',
    description: 'Trace the complete request/response flow for a specific job',
    inputSchema: {
      type: 'object',
      properties: {
        job_name: {
          type: 'string',
          description: 'Name of the QuickBooks job to trace'
        },
        include_xml: {
          type: 'boolean',
          description: 'Include full QBXML request/response data',
          default: false
        }
      },
      required: ['job_name']
    }
  },
  {
    name: 'qb_error_diagnosis',
    description: 'Diagnose QuickBooks errors with AI-powered root cause analysis',
    inputSchema: {
      type: 'object',
      properties: {
        error_code: {
          type: 'string',
          description: 'QuickBooks error code (e.g., 3100, 500)'
        },
        ticket: {
          type: 'string',
          description: 'Session ticket for context-specific diagnosis'
        }
      }
    }
  },
  {
    name: 'qb_performance_stats',
    description: 'Get performance statistics and bottleneck analysis',
    inputSchema: {
      type: 'object',
      properties: {
        time_range: {
          type: 'string',
          description: 'Time range: 1h, 24h, 7d, 30d',
          default: '24h'
        },
        group_by: {
          type: 'string',
          enum: ['job', 'company', 'event_type'],
          description: 'Group statistics by',
          default: 'job'
        }
      }
    }
  }
];

// Tool implementations
async function handleToolCall(name: string, args: any) {
  switch (name) {
    case 'qb_sessions_list':
      return listSessions(args);

    case 'qb_session_details':
      return getSessionDetails(args);

    case 'qb_analyze_logs':
      return analyzeLogs(args);

    case 'qb_flow_trace':
      return traceFlow(args);

    case 'qb_error_diagnosis':
      return diagnoseError(args);

    case 'qb_performance_stats':
      return getPerformanceStats(args);

    default:
      throw new Error(`Unknown tool: ${name}`);
  }
}

// Tool: List Sessions
function listSessions(args: { limit?: number; active_only?: boolean }) {
  const limit = args.limit || 10;

  let query = `
    SELECT
      ticket,
      job_name,
      company,
      MIN(timestamp) as started_at,
      MAX(timestamp) as last_activity,
      COUNT(*) as event_count,
      SUM(CASE WHEN event_type = 'response_received' THEN 1 ELSE 0 END) as responses,
      MAX(CASE WHEN event_type = 'response_received'
          THEN json_extract(data, '$.progress') END) as progress
    FROM events
    WHERE ticket IS NOT NULL
    GROUP BY ticket
    ORDER BY last_activity DESC
    LIMIT ?
  `;

  const sessions = db.prepare(query).all(limit);

  return {
    content: [{
      type: 'text',
      text: JSON.stringify({
        total: sessions.length,
        sessions: sessions.map((s: any) => ({
          ticket: s.ticket,
          job: s.job_name,
          company: s.company,
          started: s.started_at,
          lastActivity: s.last_activity,
          progress: s.progress || 0,
          eventCount: s.event_count,
          responseCount: s.responses
        }))
      }, null, 2)
    }]
  };
}

// Tool: Session Details
function getSessionDetails(args: { ticket: string }) {
  const events = db.prepare(`
    SELECT timestamp, event_type, severity, data
    FROM events
    WHERE ticket = ?
    ORDER BY timestamp ASC
  `).all(args.ticket);

  if (events.length === 0) {
    return {
      content: [{
        type: 'text',
        text: `No session found with ticket: ${args.ticket}`
      }]
    };
  }

  const timeline = events.map((e: any) => {
    const data = JSON.parse(e.data);
    return {
      timestamp: e.timestamp,
      eventType: e.event_type,
      severity: e.severity,
      details: {
        job: data.job || data.job_name,
        message: data.message,
        statusCode: data.status_code,
        progress: data.progress
      }
    };
  });

  return {
    content: [{
      type: 'text',
      text: JSON.stringify({
        ticket: args.ticket,
        totalEvents: events.length,
        timeline
      }, null, 2)
    }]
  };
}

// Tool: Analyze Logs
function analyzeLogs(args: { query: string; time_range?: string }) {
  const timeRange = args.time_range || '24h';
  const hours = parseTimeRange(timeRange);

  const cutoff = new Date();
  cutoff.setHours(cutoff.getHours() - hours);
  const cutoffISO = cutoff.toISOString();

  // Parse natural language query (simple implementation)
  const query = args.query.toLowerCase();
  let whereClause = `timestamp >= '${cutoffISO}'`;

  if (query.includes('error')) {
    whereClause += ` AND severity = 'ERROR'`;
  }
  if (query.includes('job')) {
    const match = query.match(/job[:\s]+(\w+)/);
    if (match) {
      whereClause += ` AND job_name = '${match[1]}'`;
    }
  }

  const results = db.prepare(`
    SELECT timestamp, event_type, severity, data
    FROM events
    WHERE ${whereClause}
    ORDER BY timestamp DESC
    LIMIT 100
  `).all();

  return {
    content: [{
      type: 'text',
      text: JSON.stringify({
        query: args.query,
        timeRange: timeRange,
        matchCount: results.length,
        events: results.map((r: any) => ({
          timestamp: r.timestamp,
          type: r.event_type,
          severity: r.severity,
          data: JSON.parse(r.data)
        }))
      }, null, 2)
    }]
  };
}

// Tool: Flow Trace
async function traceFlow(args: { job_name: string; include_xml?: boolean }) {
  const events = db.prepare(`
    SELECT timestamp, event_type, data
    FROM events
    WHERE job_name = ?
    ORDER BY timestamp DESC
    LIMIT 50
  `).all(args.job_name);

  const flow = events.map((e: any) => {
    const data = JSON.parse(e.data);
    const event: any = {
      timestamp: e.timestamp,
      eventType: e.event_type,
      statusCode: data.status_code,
      progress: data.progress
    };

    if (args.include_xml) {
      event.request = data.request_xml;
      event.response = data.response_xml;
    }

    return event;
  });

  return {
    content: [{
      type: 'text',
      text: JSON.stringify({
        jobName: args.job_name,
        flowSteps: flow.length,
        flow
      }, null, 2)
    }]
  };
}

// Tool: Error Diagnosis
function diagnoseError(args: { error_code?: string; ticket?: string }) {
  let query = `
    SELECT timestamp, event_type, data
    FROM events
    WHERE severity = 'ERROR'
  `;

  const params: any[] = [];

  if (args.error_code) {
    query += ` AND json_extract(data, '$.status_code') = ?`;
    params.push(args.error_code);
  }

  if (args.ticket) {
    query += ` AND ticket = ?`;
    params.push(args.ticket);
  }

  query += ` ORDER BY timestamp DESC LIMIT 20`;

  const errors = db.prepare(query).all(...params);

  const analysis = errors.map((e: any) => {
    const data = JSON.parse(e.data);
    return {
      timestamp: e.timestamp,
      errorCode: data.status_code,
      message: data.message,
      job: data.job || data.job_name,
      context: {
        ticket: data.ticket,
        company: data.company,
        request: data.request_xml?.substring(0, 200) + '...'
      }
    };
  });

  return {
    content: [{
      type: 'text',
      text: JSON.stringify({
        errorCode: args.error_code,
        ticket: args.ticket,
        errorCount: errors.length,
        errors: analysis,
        diagnosis: generateDiagnosis(analysis)
      }, null, 2)
    }]
  };
}

// Tool: Performance Stats
function getPerformanceStats(args: { time_range?: string; group_by?: string }) {
  const timeRange = args.time_range || '24h';
  const groupBy = args.group_by || 'job';
  const hours = parseTimeRange(timeRange);

  const cutoff = new Date();
  cutoff.setHours(cutoff.getHours() - hours);
  const cutoffISO = cutoff.toISOString();

  const stats = db.prepare(`
    SELECT
      ${groupBy === 'job' ? 'job_name' : groupBy} as category,
      COUNT(*) as event_count,
      AVG(CAST(json_extract(data, '$.duration_ms') AS REAL)) as avg_duration_ms,
      MAX(CAST(json_extract(data, '$.duration_ms') AS REAL)) as max_duration_ms,
      SUM(CASE WHEN severity = 'ERROR' THEN 1 ELSE 0 END) as error_count
    FROM events
    WHERE timestamp >= ?
      AND ${groupBy === 'job' ? 'job_name' : groupBy} IS NOT NULL
    GROUP BY category
    ORDER BY event_count DESC
  `).all(cutoffISO);

  return {
    content: [{
      type: 'text',
      text: JSON.stringify({
        timeRange,
        groupBy,
        statistics: stats
      }, null, 2)
    }]
  };
}

// Helper functions
function parseTimeRange(range: string): number {
  const match = range.match(/^(\d+)([hdwm])$/);
  if (!match) return 24;

  const value = parseInt(match[1]);
  const unit = match[2];

  switch (unit) {
    case 'h': return value;
    case 'd': return value * 24;
    case 'w': return value * 24 * 7;
    case 'm': return value * 24 * 30;
    default: return 24;
  }
}

function generateDiagnosis(errors: any[]): string {
  if (errors.length === 0) return 'No errors found';

  const errorCodes = errors.map(e => e.errorCode);
  const uniqueCodes = [...new Set(errorCodes)];

  if (uniqueCodes.includes('3100')) {
    return 'Error 3100: Name already exists. Possible duplicate record insertion.';
  } else if (uniqueCodes.includes('500')) {
    return 'Error 500: QuickBooks internal error. Check QB company file integrity.';
  } else {
    return `Found ${uniqueCodes.length} unique error codes: ${uniqueCodes.join(', ')}`;
  }
}

// Start MCP Server
async function main() {
  console.error('Starting QuickBooks MCP Server...');

  // Initialize log watcher
  startLogWatcher();
  console.error(`Watching log file: ${config.logFilePath}`);

  const server = new Server(
    {
      name: 'qbwc-mcp-server',
      version: '1.0.0',
    },
    {
      capabilities: {
        tools: {},
      },
    }
  );

  // Register tool handlers
  server.setRequestHandler(ListToolsRequestSchema, async () => ({
    tools
  }));

  server.setRequestHandler(CallToolRequestSchema, async (request) => {
    const { name, arguments: args } = request.params;
    return await handleToolCall(name, args || {});
  });

  // Start server
  const transport = new StdioServerTransport();
  await server.connect(transport);
  console.error('QuickBooks MCP Server running on stdio');
}

main().catch(console.error);
```

---

### Phase 3: Integration with Claude Desktop

**Step 3.1: Claude Desktop Configuration**

```json
// ~/Library/Application Support/Claude/claude_desktop_config.json (macOS)
// %APPDATA%/Claude/claude_desktop_config.json (Windows)

{
  "mcpServers": {
    "qbwc": {
      "command": "node",
      "args": [
        "/path/to/qbwc-mcp-server/build/index.js"
      ],
      "env": {
        "QBWC_LOG_PATH": "/path/to/rails/log/qbwc_structured.log",
        "QBWC_DB_PATH": "/path/to/qbwc-mcp-server/qbwc_mcp.db",
        "RAILS_ROOT": "/path/to/rails"
      }
    }
  }
}
```

**Step 3.2: Restart Claude Desktop**

After configuration, restart Claude Desktop to load the MCP server.

---

## 🚀 Quy Trình Sử Dụng

### 1. Khởi động Rails Server

```bash
cd /path/to/rails
bundle exec rails server -p 3000
```

### 2. Cấu hình QuickBooks Web Connector

```bash
# Download QWC file
curl http://localhost:3000/qbwc/qwc > QuickBooks.qwc

# Open in QuickBooks Web Connector
# Add password: your_configured_password
```

### 3. Monitor với AI Agent

Trong Claude Desktop, bạn có thể hỏi:

```
🤖 Prompt Examples:

1. "Show me all active QuickBooks sessions"
   → Uses: qb_sessions_list()

2. "Trace the flow of CustomerWorker job"
   → Uses: qb_flow_trace(job_name: "CustomerWorker")

3. "Analyze errors in the last hour"
   → Uses: qb_analyze_logs(query: "errors", time_range: "1h")

4. "Diagnose error code 3100"
   → Uses: qb_error_diagnosis(error_code: "3100")

5. "Show performance stats grouped by job"
   → Uses: qb_performance_stats(group_by: "job")
```

---

## 📊 Flow Visualization Example

AI Agent sẽ có thể hiểu flow như sau:

```
Session: abc123-ticket
├─ [2024-01-15 10:00:00] authentication (user: admin)
│  └─ Jobs pending: CustomerWorker, InvoiceWorker
│
├─ [2024-01-15 10:00:01] request_sent (CustomerWorker)
│  └─ QBXML: CustomerQueryRq
│  └─ Progress: 0%
│
├─ [2024-01-15 10:00:03] response_received
│  └─ Status: 0 (Success)
│  └─ Progress: 50%
│  └─ Data: 150 customers returned
│
├─ [2024-01-15 10:00:04] request_sent (InvoiceWorker)
│  └─ QBXML: InvoiceQueryRq
│  └─ Progress: 50%
│
├─ [2024-01-15 10:00:06] response_received
│  └─ Status: 3100 (Name exists)
│  └─ Severity: Error
│  └─ Progress: 50% (halted)
│
└─ [2024-01-15 10:00:07] close_connection
   └─ Reason: Error occurred
   └─ Final Progress: 50%
```

---

## 🎯 Evidence-Based Analysis

MCP Server cung cấp evidence cho AI:

### Example 1: Error Diagnosis

```
User: "Why did the invoice import fail?"

AI Agent (uses qb_error_diagnosis):
{
  "evidence": [
    {
      "timestamp": "2024-01-15T10:00:06Z",
      "errorCode": "3100",
      "message": "A name of this type already exists",
      "job": "InvoiceWorker",
      "requestXML": "<InvoiceAddRq><InvoiceAdd><CustomerRef>..."
    }
  ],
  "diagnosis": "Error 3100 indicates duplicate invoice number.
                The InvoiceWorker attempted to create an invoice with
                RefNumber='INV-001' which already exists in QuickBooks.

                Recommendation:
                1. Check invoice number generation logic
                2. Implement upsert pattern (Query -> Update or Add)
                3. Add unique constraint validation before QB sync"
}
```

### Example 2: Performance Analysis

```
User: "Which job is slowest?"

AI Agent (uses qb_performance_stats):
{
  "timeRange": "24h",
  "statistics": [
    {
      "category": "CustomerWorker",
      "eventCount": 45,
      "avgDurationMs": 1250,
      "maxDurationMs": 3500,
      "errorCount": 0
    },
    {
      "category": "InvoiceWorker",
      "eventCount": 120,
      "avgDurationMs": 4800,
      "maxDurationMs": 12000,
      "errorCount": 5
    }
  ],
  "analysis": "InvoiceWorker is 3.8x slower than CustomerWorker.

               Bottleneck: Average 4.8s per request suggests:
               - Large QBXML response size (check iterator usage)
               - Complex XML parsing
               - Network latency

               Recommendation:
               1. Enable iterator for large datasets
               2. Batch process invoices in smaller chunks
               3. Optimize XML parsing with streaming"
}
```

---

## 🔧 Maintenance & Monitoring

### Log Rotation

```ruby
# config/environments/production.rb

config.logger = ActiveSupport::Logger.new(
  Rails.root.join('log', 'qbwc_structured.log'),
  10,           # Keep 10 old files
  10.megabytes  # Rotate when file reaches 10MB
)
```

### Database Cleanup (SQLite)

```sql
-- Run weekly via cron
DELETE FROM events WHERE created_at < datetime('now', '-30 days');
VACUUM;
```

### Health Check

```bash
# Add to cron or monitoring system
curl http://localhost:3000/qbwc/action | grep -q "definitions" && echo "OK" || echo "FAIL"
```

---

## 📚 Documentation for AI Context

Tạo file `docs/AI_CONTEXT.md` để AI hiểu business logic:

```markdown
# QuickBooks Integration - AI Context

## Business Flow

### Customer Sync
1. Rails creates CustomerWorker job
2. QBWC sends CustomerQueryRq to QB
3. QB returns customer list (max 100 per request)
4. Rails processes and stores in DB
5. Next iteration continues with iterator_id

### Invoice Creation
1. Rails prepares InvoiceAddRq
2. QBWC sends to QB
3. QB validates:
   - Customer exists
   - Invoice number unique
   - Line items valid
4. Returns TxnID on success
5. Rails stores mapping

## Error Codes

- 3100: Duplicate name/number
- 500: QB internal error (file corruption)
- 3170: Permission denied
- 3180: Object not found

## Performance Benchmarks

- Customer query: ~1s per 100 records
- Invoice add: ~500ms per invoice
- Session auth: <100ms

## Known Issues

1. **Iterator Timeout**: QB expires iterator after 30min
   → Solution: Process in smaller batches

2. **Duplicate Detection**: QB doesn't support upsert
   → Solution: Query first, then Add or Mod

3. **XML Size Limit**: 10MB max QBXML size
   → Solution: Chunk large requests
```

---

## ✅ Success Criteria

Sau khi triển khai, AI Agent có thể:

✓ **Understand Flow**: Trace toàn bộ session lifecycle
✓ **Diagnose Errors**: Root cause analysis với evidence
✓ **Optimize Performance**: Identify bottlenecks
✓ **Predict Issues**: Proactive monitoring
✓ **Generate Fixes**: Code suggestions based on logs

---

## 🎓 Next Steps

1. **Phase 1**: Implement enhanced logging (1-2 days)
2. **Phase 2**: Build MCP server (2-3 days)
3. **Phase 3**: Test with Claude Desktop (1 day)
4. **Phase 4**: Production deployment (1 day)

**Total Estimate**: 5-7 days

---

## 📞 Support

- MCP SDK: https://github.com/modelcontextprotocol/sdk
- QBWC Gem: https://github.com/skryl/qbwc
- QuickBooks SDK: https://developer.intuit.com/

---

*Generated by Claude Code - MCP Integration Proposal*
*Date: 2024-01-15*
