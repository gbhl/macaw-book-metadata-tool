<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Logging Library
 *
 * MACAW Metadata Collection and Workflow System
 *
 * This contains two routines for logging messages for both Macaw and
 * individual books. This does not replace codeigniters's log_message()
 * functon. The logs written her e
 *
 * @package admincontroller
 * @author Joel Richard
 * @version 1.0 admin.php created: 2010-07-07 last-modified: 2010-08-19

	Change History
	Date        By   Note
	------------------------
	2010-07-07  JMR  Created

 **/

class Logging extends Controller {

	var $CI;
	var $cfg;

	function __construct() {
		$this->CI = get_instance();
		$this->cfg = $this->CI->config->item('macaw');
	}

	/**
	 * Log something to the Macaw logs
	 *
	 * Macaw has its own log files and so we need a function to send data to them.
	 * We can log to the "access", "error" and "book" logs. The locations of these
	 * files are defined in the "logs_directory" setting in the Macaw configuration.
	 *
	 * The error and access logs are one file each and are always appended to and
	 * will rotate on a automatically if the configuration is set up to allow
	 # rotation with strftime() parameters.
	 #
	 * The book logs are all found in the /books/ subdirectory from the access and
	 * error logs. There is one log file per book as each books' log is named with
	 * the barcode of the book. Book logs won't rotate. There is no configuration
	 # parameter for it.
	 *
	 * Notes: "book" level is always logged, but requires a barcode. If no barcode is
	 * provided, then the log message will go to the access log.
	 *
	 * The Log file format for any of the log files is as follows.
	 *     [YYYY-MM-DD HH:MM:SS] 127.0.0.1 username LEVEL: "message"
	 *
	 * Parameters:
	 * @param string [$log] Which log to write to. (access|error|book|cron)
	 * @param string [$level] The severity of the log. (info|debug|trace|book)
	 * @param string [$message] The message to log to the file
	 * @param string [$barcode] Which book this log message relates to (optional)
	 * @return string The filename that was written to, if any.
	 *
	   Tests:
	      Log to the access log, make sure it exists (and contains proper strings?)
	      Log to the error log, make sure it exists (and contains proper strings?)
	      Log to the book log, make sure it exists (and contains proper strings?)
	      Log with all possible parameters, make sure that the files exist (and contains proper strings?)
	 */
	function log($log ='access', $level = 'info', $message = '', $barcode = '') {

		if ($message) {
			// Where are we working?
			$path = $this->cfg['logs_directory'];
			$config_level = $this->cfg['log_level'];
			$ok = 0;

			// Decide if we are going to log
			if ($log == 'error' || $log == 'book' || $log == 'activity') {
				// Errors, book-related and activity logs are always logged
				$ok = 1;
			} elseif ($log == 'access') {
				// If we're going to the access log, we then use the severity levels
				if ($config_level == 'info' && $level == 'info') {
					// If we are configured for info, then we only log info
					$ok = 1;

				} elseif ($config_level == 'debug' && ($level == 'info' || $level == 'debug')) {
					// If we are configured for debug, then we only log info and debug
					$ok = 1;
				}
			} elseif ($log == 'cron') {
				$ok = 1;
			} // if ($log == 'error' || $log == 'book')

			// Can we proceed and do our logging?
			if ($ok) {

				// If this is an error, record where we came from, to make it easier to track down.
				if ($log == 'error') {
					//
					$trace = debug_backtrace(false);
					$caller = array_shift($trace);

					$message .= ' ('.$caller['file'].' line '.$caller['line'].')';
				}

				$username = 'system';
				if ($this->CI->session) {
					if ($this->CI->session->userdata('username')) {
						$username = $this->CI->session->userdata('username');
					}
				}

				$ip_addr = "(local)";
				if (key_exists('REMOTE_ADDR', $_SERVER)) {
					$ip_addr = $_SERVER['REMOTE_ADDR'];
				}
				// Set up our log message
				$message =  '['.date('Y-m-d H:i:s').'] '.$ip_addr.' '.$username.' '.
							 strtoupper($level).': "'.$message.'"'."\n";


				// Build the path to the log file
				$fname = '';
				if ($barcode && $log == 'book') {
					$fname = $path.'/books/'.$barcode.'.log';
				} else {
					if ($log == 'error') {
						$fname = $path.'/macaw_error.log';
						if ($this->cfg['error_log']) {
							$fname = $path.'/'.$this->CI->common->macaw_strftime($this->cfg['error_log']);
						}
					} elseif ($log == 'activity') {
						$fname = $path.'/macaw_activity.log';
						if ($this->cfg['activity_log']) {
							$fname = $path.'/'.$this->CI->common->macaw_strftime($this->cfg['activity_log']);
						}
					} elseif ($log == 'cron') {
						$fname = $path.'/macaw_cron.log';
						if ($this->cfg['cron_log']) {
							$fname = $path.'/'.$this->CI->common->macaw_strftime($this->cfg['cron_log']);
						}
					} else { // everything else goes to the access log
						$fname = $path.'/macaw_access.log';
						if ($this->cfg['access_log']) {
							$fname = $path.'/'.$this->CI->common->macaw_strftime($this->cfg['access_log']);
						}
					}
				}
				// Append to the file
				$fh = fopen($fname, 'a') or die;
				fwrite($fh, $message);
				fclose($fh);
				return $fname;
			} // if ($ok)
		} // if ($message)
		return null;
	} // function log()
}

