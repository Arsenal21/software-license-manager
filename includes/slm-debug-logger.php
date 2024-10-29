<?php

/*
 * SLM Plus Debug Logger
 *
 * This class is responsible for logging debug data to a file in the "logs" folder.
 * It can be used to track various plugin activities and statuses, such as cron jobs, success/failure notices, etc.
 *
 * Usage Example:
 * - global $slm_debug_logger;
 * - $slm_debug_logger->log_debug("Some debug message");
 *
 * OR
 *
 * - SLM_Debug_Logger::log_debug_st("Some debug message");
 */

class SLM_Debug_Logger {
    var $log_folder_path;
    var $default_log_file = 'log.txt';
    var $default_log_file_cron = 'log-cron-job.txt';
    var $debug_enabled = false;
    var $debug_status = array('SUCCESS', 'STATUS', 'NOTICE', 'WARNING', 'FAILURE', 'CRITICAL');
    var $section_break_marker = "\n----------------------------------------------------------\n\n";
    var $log_reset_marker = "-------- Log File Reset --------\n";

    /**
     * Constructor
     * Initializes the logger by setting the log folder path and enabling debugging based on plugin settings.
     */
    function __construct() {
        $this->log_folder_path = SLM_PATH . '/logs';
        
        // Check plugin options to see if debugging is enabled
        $options = get_option('slm_plugin_options');
        if (!empty($options['enable_debug'])) {
            $this->debug_enabled = true;
        }
    }

    /**
     * Get the current timestamp for the log entry
     * 
     * @return string Timestamp in the format [m/d/Y g:i A]
     */
    function get_debug_timestamp() {
        return '[' . wp_date('m/d/Y g:i A') . '] - ';
    }

    /**
     * Get the debug status label based on the level
     * 
     * @param int $level The severity level of the log message (0-5)
     * @return string The corresponding status label (e.g., 'SUCCESS', 'FAILURE')
     */
    function get_debug_status($level) {
        $size = count($this->debug_status);
        return ($level >= $size) ? 'UNKNOWN' : $this->debug_status[$level];
    }

    /**
     * Return a section break marker if required
     * 
     * @param bool $section_break Whether to include a section break
     * @return string Section break marker or empty string
     */
    function get_section_break($section_break) {
        return $section_break ? $this->section_break_marker : "";
    }

    /**
     * Reset the log file by clearing its contents and adding a reset marker
     * 
     * @param string $file_name Optional file name to reset, defaults to main log file
     */
    function reset_log_file($file_name = '') {
        if (empty($file_name)) {
            $file_name = $this->default_log_file;
        }
        $debug_log_file = $this->log_folder_path . '/' . $file_name;
        $content = $this->get_debug_timestamp() . $this->log_reset_marker;
        $fp = fopen($debug_log_file, 'w');
        fwrite($fp, $content);
        fclose($fp);
    }

    /**
     * Append content to the specified log file
     * 
     * @param string $content The content to log
     * @param string $file_name The file to which content will be appended
     */
    function append_to_file($content, $file_name) {
        if (empty($file_name)) $file_name = $this->default_log_file;
        $debug_log_file = $this->log_folder_path . '/' . $file_name;
        $fp = fopen($debug_log_file, 'a');
        fwrite($fp, $content);
        fclose($fp);
    }

    /**
     * Log a debug message
     * 
     * @param string $message The debug message to log
     * @param int $level The debug level (default 0 - SUCCESS)
     * @param bool $section_break Whether to include a section break after the message
     * @param string $file_name Optional log file name
     */
    function log_debug($message, $level = 0, $section_break = false, $file_name = '') {
        if (!$this->debug_enabled) return;
        
        // Build the log content
        $content = $this->get_debug_timestamp(); // Add timestamp
        $content .= $this->get_debug_status($level); // Add status level
        $content .= ' : ' . $message . "\n"; // Add message
        $content .= $this->get_section_break($section_break); // Add section break if needed
        
        $this->append_to_file($content, $file_name); // Append to log file
    }

    /**
     * Log a debug message specifically for cron jobs
     * 
     * @param string $message The debug message to log for cron jobs
     * @param int $level The debug level (default 0 - SUCCESS)
     * @param bool $section_break Whether to include a section break after the message
     */
    function log_debug_cron($message, $level = 0, $section_break = false) {
        if (!$this->debug_enabled) return;
        
        // Build the log content for cron jobs
        $content = $this->get_debug_timestamp();
        $content .= $this->get_debug_status($level);
        $content .= ' : ' . $message . "\n";
        $content .= $this->get_section_break($section_break);
        
        // Log to the cron-specific log file
        $this->append_to_file($content, $this->default_log_file_cron);
    }

    /**
     * Static method to log debug messages from a static context
     * 
     * @param string $message The debug message to log
     * @param int $level The debug level (default 0 - SUCCESS)
     * @param bool $section_break Whether to include a section break after the message
     * @param string $file_name Optional log file name
     */
    static function log_debug_st($message, $level = 0, $section_break = false, $file_name = '') {
        $options = get_option('slm_plugin_options');
        if (empty($options['enable_debug'])) {
            return; // Debugging is disabled
        }
        
        // Build the log content
        $content = '[' . wp_date('m/d/Y g:i A') . '] - STATUS : ' . $message . "\n";
        $debug_log_file = SLM_PUBLIC . '/logs/log.txt';
        
        // Append the log content to the file
        $fp = fopen($debug_log_file, 'a');
        fwrite($fp, $content);
        fclose($fp);
    }
}
