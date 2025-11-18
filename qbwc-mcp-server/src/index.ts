#!/usr/bin/env node

import { Server } from '@modelcontextprotocol/sdk/server/index.js';
import { StdioServerTransport } from '@modelcontextprotocol/sdk/server/stdio.js';
import {
  CallToolRequestSchema,
  ListToolsRequestSchema,
  Tool,
} from '@modelcontextprotocol/sdk/types.js';
import Database from 'better-sqlite3';
import { Tail } from 'tail';
import path from 'path';
import fs from 'fs';

// ============================================================================
// Configuration
// ============================================================================

interface ServerConfig {
  logFilePath: string;
  dbPath: string;
  railsRoot?: string;
}

const config: ServerConfig = {
  logFilePath:
    process.env.QBWC_LOG_PATH || './log/qbwc_structured.log',
  dbPath: process.env.QBWC_DB_PATH || './qbwc_mcp.db',
  railsRoot: process.env.RAILS_ROOT,
};

// ============================================================================
// Database Setup
// ============================================================================

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
    status_code INTEGER,
    progress INTEGER,
    duration_ms REAL,
    data TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
  );

  CREATE INDEX IF NOT EXISTS idx_events_timestamp ON events(timestamp);
  CREATE INDEX IF NOT EXISTS idx_events_type ON events(event_type);
  CREATE INDEX IF NOT EXISTS idx_events_ticket ON events(ticket);
  CREATE INDEX IF NOT EXISTS idx_events_job ON events(job_name);
  CREATE INDEX IF NOT EXISTS idx_events_severity ON events(severity);
`);

console.error('✓ Database initialized:', config.dbPath);

// ============================================================================
// Log Watcher
// ============================================================================

function startLogWatcher() {
  if (!fs.existsSync(config.logFilePath)) {
    console.error(`⚠ Log file not found: ${config.logFilePath}`);
    console.error('  MCP server will wait for log file to be created...');
    return;
  }

  console.error('✓ Watching log file:', config.logFilePath);

  const tail = new Tail(config.logFilePath, {
    fromBeginning: false,
    follow: true,
    useWatchFile: true,
  });

  tail.on('line', (line: string) => {
    try {
      const event = JSON.parse(line);

      // Extract fields from structured log
      const eventData = typeof event.message === 'string'
        ? JSON.parse(event.message)
        : event.message || {};

      const stmt = db.prepare(`
        INSERT INTO events (
          timestamp, event_type, ticket, job_name, company,
          severity, status_code, progress, duration_ms, data
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
      `);

      stmt.run(
        event.timestamp || new Date().toISOString(),
        eventData.event_type || 'unknown',
        eventData.ticket || null,
        eventData.job || eventData.job_name || null,
        eventData.company || null,
        event.severity || 'INFO',
        eventData.status_code || null,
        eventData.progress || null,
        eventData.duration_ms || null,
        JSON.stringify(event)
      );
    } catch (err) {
      // Skip non-JSON lines or malformed data
    }
  });

  tail.on('error', (error: Error) => {
    console.error('⚠ Tail error:', error.message);
  });
}

// ============================================================================
// MCP Tools Definitions
// ============================================================================

const tools: Tool[] = [
  {
    name: 'qb_sessions_list',
    description:
      'List all active and recent QuickBooks sessions with their status, progress, and job information',
    inputSchema: {
      type: 'object',
      properties: {
        limit: {
          type: 'number',
          description: 'Number of sessions to return (default: 10)',
          default: 10,
        },
        active_only: {
          type: 'boolean',
          description: 'Show only active sessions (not yet closed)',
          default: false,
        },
      },
    },
  },
  {
    name: 'qb_session_details',
    description:
      'Get detailed timeline and flow trace for a specific QuickBooks session by ticket ID',
    inputSchema: {
      type: 'object',
      properties: {
        ticket: {
          type: 'string',
          description: 'Session ticket ID (SHA256 hash)',
        },
      },
      required: ['ticket'],
    },
  },
  {
    name: 'qb_analyze_logs',
    description:
      'Analyze QuickBooks logs using natural language queries. Search for errors, patterns, or specific events.',
    inputSchema: {
      type: 'object',
      properties: {
        query: {
          type: 'string',
          description:
            'Natural language query (e.g., "show me all errors in the last hour", "find failed invoice jobs")',
        },
        time_range: {
          type: 'string',
          description: 'Time range: 1h, 24h, 7d, 30d (default: 24h)',
          default: '24h',
        },
      },
      required: ['query'],
    },
  },
  {
    name: 'qb_flow_trace',
    description:
      'Trace the complete request/response flow for a specific QuickBooks job, showing all steps and timing',
    inputSchema: {
      type: 'object',
      properties: {
        job_name: {
          type: 'string',
          description: 'Name of the QuickBooks job to trace (e.g., "CustomerWorker")',
        },
        include_xml: {
          type: 'boolean',
          description: 'Include full QBXML request/response data in output',
          default: false,
        },
        limit: {
          type: 'number',
          description: 'Maximum number of events to return',
          default: 50,
        },
      },
      required: ['job_name'],
    },
  },
  {
    name: 'qb_error_diagnosis',
    description:
      'Diagnose QuickBooks errors with AI-powered root cause analysis. Provides context and recommendations.',
    inputSchema: {
      type: 'object',
      properties: {
        error_code: {
          type: 'string',
          description: 'QuickBooks error code (e.g., "3100", "500")',
        },
        ticket: {
          type: 'string',
          description: 'Optional: Session ticket for context-specific diagnosis',
        },
        time_range: {
          type: 'string',
          description: 'Time range to search: 1h, 24h, 7d (default: 24h)',
          default: '24h',
        },
      },
    },
  },
  {
    name: 'qb_performance_stats',
    description:
      'Get performance statistics, timing analysis, and bottleneck identification for QuickBooks jobs',
    inputSchema: {
      type: 'object',
      properties: {
        time_range: {
          type: 'string',
          description: 'Time range: 1h, 24h, 7d, 30d',
          default: '24h',
        },
        group_by: {
          type: 'string',
          enum: ['job_name', 'company', 'event_type'],
          description: 'Group statistics by field',
          default: 'job_name',
        },
      },
    },
  },
];

// ============================================================================
// Tool Implementations
// ============================================================================

function listSessions(args: { limit?: number; active_only?: boolean }) {
  const limit = args.limit || 10;

  const query = `
    SELECT
      ticket,
      job_name,
      company,
      MIN(timestamp) as started_at,
      MAX(timestamp) as last_activity,
      COUNT(*) as event_count,
      SUM(CASE WHEN event_type = 'response_received' THEN 1 ELSE 0 END) as responses,
      MAX(CASE WHEN event_type = 'response_received' THEN progress END) as progress,
      MAX(CASE WHEN event_type = 'close_connection' THEN 1 ELSE 0 END) as closed
    FROM events
    WHERE ticket IS NOT NULL
    GROUP BY ticket
    ORDER BY last_activity DESC
    LIMIT ?
  `;

  const sessions = db.prepare(query).all(limit) as any[];

  return {
    content: [
      {
        type: 'text',
        text: JSON.stringify(
          {
            total: sessions.length,
            sessions: sessions.map((s) => ({
              ticket: s.ticket,
              job: s.job_name,
              company: s.company,
              started: s.started_at,
              lastActivity: s.last_activity,
              progress: s.progress || 0,
              status: s.closed ? 'closed' : 'active',
              eventCount: s.event_count,
              responseCount: s.responses,
            })),
          },
          null,
          2
        ),
      },
    ],
  };
}

function getSessionDetails(args: { ticket: string }) {
  const events = db
    .prepare(
      `
    SELECT timestamp, event_type, severity, data
    FROM events
    WHERE ticket = ?
    ORDER BY timestamp ASC
  `
    )
    .all(args.ticket) as any[];

  if (events.length === 0) {
    return {
      content: [
        {
          type: 'text',
          text: `No session found with ticket: ${args.ticket}`,
        },
      ],
    };
  }

  const timeline = events.map((e) => {
    const data = JSON.parse(e.data);
    const eventData = typeof data.message === 'string'
      ? JSON.parse(data.message)
      : data.message || {};

    return {
      timestamp: e.timestamp,
      eventType: e.event_type,
      severity: e.severity,
      details: {
        job: eventData.job || eventData.job_name,
        message: eventData.message,
        statusCode: eventData.status_code,
        progress: eventData.progress,
        company: eventData.company,
      },
    };
  });

  return {
    content: [
      {
        type: 'text',
        text: JSON.stringify(
          {
            ticket: args.ticket,
            totalEvents: events.length,
            timeline,
          },
          null,
          2
        ),
      },
    ],
  };
}

function analyzeLogs(args: { query: string; time_range?: string }) {
  const timeRange = args.time_range || '24h';
  const hours = parseTimeRange(timeRange);

  const cutoff = new Date();
  cutoff.setHours(cutoff.getHours() - hours);
  const cutoffISO = cutoff.toISOString();

  // Parse natural language query (simple implementation)
  const query = args.query.toLowerCase();
  let whereClause = `timestamp >= '${cutoffISO}'`;
  const params: any[] = [];

  if (query.includes('error')) {
    whereClause += ` AND severity = 'ERROR'`;
  }

  if (query.includes('job')) {
    const match = query.match(/job[:\s]+(\w+)/);
    if (match) {
      whereClause += ` AND job_name = ?`;
      params.push(match[1]);
    }
  }

  if (query.includes('failed')) {
    whereClause += ` AND (severity = 'ERROR' OR status_code != 0)`;
  }

  const results = db
    .prepare(
      `
    SELECT timestamp, event_type, severity, job_name, status_code, data
    FROM events
    WHERE ${whereClause}
    ORDER BY timestamp DESC
    LIMIT 100
  `
    )
    .all(...params) as any[];

  return {
    content: [
      {
        type: 'text',
        text: JSON.stringify(
          {
            query: args.query,
            timeRange: timeRange,
            matchCount: results.length,
            events: results.map((r) => {
              const data = JSON.parse(r.data);
              const eventData = typeof data.message === 'string'
                ? JSON.parse(data.message)
                : data.message || {};

              return {
                timestamp: r.timestamp,
                type: r.event_type,
                severity: r.severity,
                job: r.job_name,
                statusCode: r.status_code,
                message: eventData.message || data.message,
              };
            }),
          },
          null,
          2
        ),
      },
    ],
  };
}

async function traceFlow(args: {
  job_name: string;
  include_xml?: boolean;
  limit?: number;
}) {
  const limit = args.limit || 50;

  const events = db
    .prepare(
      `
    SELECT timestamp, event_type, data, status_code, progress
    FROM events
    WHERE job_name = ?
    ORDER BY timestamp DESC
    LIMIT ?
  `
    )
    .all(args.job_name, limit) as any[];

  const flow = events.map((e) => {
    const data = JSON.parse(e.data);
    const eventData = typeof data.message === 'string'
      ? JSON.parse(data.message)
      : data.message || {};

    const event: any = {
      timestamp: e.timestamp,
      eventType: e.event_type,
      statusCode: e.status_code || eventData.status_code,
      progress: e.progress || eventData.progress,
    };

    if (args.include_xml) {
      event.request = eventData.request_xml;
      event.response = eventData.response_xml;
    }

    return event;
  });

  return {
    content: [
      {
        type: 'text',
        text: JSON.stringify(
          {
            jobName: args.job_name,
            flowSteps: flow.length,
            flow,
          },
          null,
          2
        ),
      },
    ],
  };
}

function diagnoseError(args: {
  error_code?: string;
  ticket?: string;
  time_range?: string;
}) {
  const timeRange = args.time_range || '24h';
  const hours = parseTimeRange(timeRange);

  const cutoff = new Date();
  cutoff.setHours(cutoff.getHours() - hours);
  const cutoffISO = cutoff.toISOString();

  let query = `
    SELECT timestamp, event_type, data, job_name, status_code
    FROM events
    WHERE severity = 'ERROR' AND timestamp >= ?
  `;

  const params: any[] = [cutoffISO];

  if (args.error_code) {
    query += ` AND status_code = ?`;
    params.push(args.error_code);
  }

  if (args.ticket) {
    query += ` AND ticket = ?`;
    params.push(args.ticket);
  }

  query += ` ORDER BY timestamp DESC LIMIT 20`;

  const errors = db.prepare(query).all(...params) as any[];

  const analysis = errors.map((e) => {
    const data = JSON.parse(e.data);
    const eventData = typeof data.message === 'string'
      ? JSON.parse(data.message)
      : data.message || {};

    return {
      timestamp: e.timestamp,
      errorCode: e.status_code || eventData.status_code,
      message: eventData.message || data.message,
      job: e.job_name,
      context: {
        ticket: eventData.ticket,
        company: eventData.company,
        request: eventData.request_xml?.substring(0, 200) + '...',
      },
    };
  });

  return {
    content: [
      {
        type: 'text',
        text: JSON.stringify(
          {
            errorCode: args.error_code,
            ticket: args.ticket,
            timeRange: timeRange,
            errorCount: errors.length,
            errors: analysis,
            diagnosis: generateDiagnosis(analysis),
          },
          null,
          2
        ),
      },
    ],
  };
}

function getPerformanceStats(args: {
  time_range?: string;
  group_by?: string;
}) {
  const timeRange = args.time_range || '24h';
  const groupBy = args.group_by || 'job_name';
  const hours = parseTimeRange(timeRange);

  const cutoff = new Date();
  cutoff.setHours(cutoff.getHours() - hours);
  const cutoffISO = cutoff.toISOString();

  const stats = db
    .prepare(
      `
    SELECT
      ${groupBy} as category,
      COUNT(*) as event_count,
      AVG(duration_ms) as avg_duration_ms,
      MAX(duration_ms) as max_duration_ms,
      SUM(CASE WHEN severity = 'ERROR' THEN 1 ELSE 0 END) as error_count
    FROM events
    WHERE timestamp >= ?
      AND ${groupBy} IS NOT NULL
    GROUP BY category
    ORDER BY event_count DESC
  `
    )
    .all(cutoffISO) as any[];

  return {
    content: [
      {
        type: 'text',
        text: JSON.stringify(
          {
            timeRange,
            groupBy,
            statistics: stats,
          },
          null,
          2
        ),
      },
    ],
  };
}

// ============================================================================
// Helper Functions
// ============================================================================

function parseTimeRange(range: string): number {
  const match = range.match(/^(\d+)([hdwm])$/);
  if (!match) return 24;

  const value = parseInt(match[1]);
  const unit = match[2];

  switch (unit) {
    case 'h':
      return value;
    case 'd':
      return value * 24;
    case 'w':
      return value * 24 * 7;
    case 'm':
      return value * 24 * 30;
    default:
      return 24;
  }
}

function generateDiagnosis(errors: any[]): string {
  if (errors.length === 0) return 'No errors found';

  const errorCodes = errors.map((e) => e.errorCode).filter(Boolean);
  const uniqueCodes = [...new Set(errorCodes)];

  // Common QuickBooks error codes
  if (uniqueCodes.includes('3100') || uniqueCodes.includes(3100)) {
    return 'Error 3100: Name/Reference already exists. This indicates duplicate record insertion. Check for existing records before adding new ones.';
  } else if (uniqueCodes.includes('500') || uniqueCodes.includes(500)) {
    return 'Error 500: QuickBooks internal error. Check QB company file integrity and ensure QuickBooks is running properly.';
  } else if (uniqueCodes.includes('3170') || uniqueCodes.includes(3170)) {
    return 'Error 3170: Permission denied. Check user permissions in QuickBooks.';
  } else if (uniqueCodes.includes('3180') || uniqueCodes.includes(3180)) {
    return 'Error 3180: Object not found. The referenced record does not exist in QuickBooks.';
  } else {
    return `Found ${uniqueCodes.length} unique error codes: ${uniqueCodes.join(', ')}. Review the error messages for specific details.`;
  }
}

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

// ============================================================================
// MCP Server Initialization
// ============================================================================

async function main() {
  console.error('');
  console.error('╔═══════════════════════════════════════════════════════════╗');
  console.error('║  QuickBooks Web Connector - MCP Server                   ║');
  console.error('║  AI-Powered Monitoring & Analysis                         ║');
  console.error('╚═══════════════════════════════════════════════════════════╝');
  console.error('');

  // Initialize log watcher
  startLogWatcher();

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
    tools,
  }));

  server.setRequestHandler(CallToolRequestSchema, async (request) => {
    const { name, arguments: args } = request.params;
    console.error(`→ Tool called: ${name}`);
    return await handleToolCall(name, args || {});
  });

  // Start server
  const transport = new StdioServerTransport();
  await server.connect(transport);

  console.error('✓ MCP Server ready');
  console.error('✓ 6 tools available for AI analysis');
  console.error('');
}

main().catch((error) => {
  console.error('Fatal error:', error);
  process.exit(1);
});
