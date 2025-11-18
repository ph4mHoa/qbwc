# Enhanced Structured Logger for QBWC
# Place this file in: config/initializers/qbwc_enhanced_logger.rb

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

# Create dedicated log file for QBWC with structured JSON logging
qbwc_log_path = Rails.root.join('log', 'qbwc_structured.log')
qbwc_logger = QBWCStructuredLogger.new(qbwc_log_path, 'daily')
qbwc_logger.level = Logger::DEBUG

# Configure QBWC to use structured logger
QBWC.configure do |c|
  c.logger = qbwc_logger
  c.log_requests_and_responses = true  # Enable in development/staging

  # Existing configuration...
  # c.username = 'admin'
  # c.password = 'password'
  # etc...
end

Rails.logger.info "QBWC Enhanced Logger initialized: #{qbwc_log_path}"
