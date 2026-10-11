<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Cron Controller
 *
 * MACAW Metadata Collection and Workflow System
 *
 * Contains all of the command-line tasks that are scheduled to run at regular intervals
 *
 * To use this, add the following entries to the web server user's crontab.
 * This MUST run as the web server user or the proper logging will not take
 * place. You will need the full path to all file.
 * 
 * # sudo crontab -e -u www
 * 
 * # Check every hour of every weekday for new books
 * 0 * * * 1-5     /opt/local/apache2/htdocs/cron.php  --run=/cron/new_items --quiet > /dev/null
 * 
 * # Once per weekday at 2:17am, export/verify/harvest/archive/etc
 * 17 2 * * 1-5   /opt/local/apache2/htdocs/cron.php --run=/cron/export --quiet > /dev/null
 * 
 * # Once per day at 2:00am, update the Dashboard statistics
 * 0 2 * * *       /opt/local/apache2/htdocs/cron.php --run=/cron/statistics --quiet > /dev/null
 * 
 **/

class Cron extends Controller {

	var $cfg;

	function __construct() {
		parent::__construct();
		$this->cfg = $this->config->item('macaw');
	}


	/**
	 * Set our environment
	 *
	 * command-line activities will fall apart if we aren't in the base directory.
	 * Also, these scripts can run for a long time, so we run them for up to
	 * one day. And finally, we don't want to run this twice, so we stop two
	 * identical activities from running simultaneously.
	 *
	 * @since Version 1.5
	 */
	function _init($method) {
		// We exit only if we didn't get the override environment variable
		// This variable is set in controllers/admin.php cron() function when
		// making the system() call.
		if ($this->already_running($method)) {
			if (!getenv("MACAW_OVERRIDE")) {
				// Allow multiple exports, but the export needs
				// to be aware of other exports of the same type.
				if ($method != 'export') {
					return false;
				}
			} else {
				$this->logging->log('access', 'info', "Cron command $method is already running, but we continue anyway due to override.");
			}
		}

		set_time_limit(86400);
		chdir($this->cfg['base_directory']);
		return true;
	}

	/**
	 * Set up a new book
	 *
	 * CLI: Initiates the process of searching for new books in the
	 * various importer modules. Calls the importer model to do all the work.
	 * We also include an alias "import". 
	 *
	 * @since Version 1.5
	 */
	function new_items() {
		if (!$this->_init('new_items')) { return; }
		$args = func_get_args();
		$this->importer->import($args);
	}

	function import() {
		if (!$this->_init('import')) { return; }
		$args = func_get_args();
		$this->importer->import($args);
	}

	/**
	 * Export and archive completed books
	 *
	 * CLI: Runs the export modules via the Exporter model. Then it
	 * runs through all of the books that have been completed and
	 * makes sure that they are properly archived.
	 *
	 * @since Version 1.5
	 */
	function export() {
		if (!$this->_init('export')) { return; }

		$args = func_get_args();

		$this->exporter->export($args);
	}

	function daily() {

		if ($this->_init('daily')) { 
			$this->common->clean_logs();
			$this->common->run_statistics();
		}

	}
	function statistics() {
		if (!$this->_init('statistics')) { return; }
		$this->common->run_statistics();
	}

	function index() {
		echo "No command specified.\n";
		echo "Usage:\n    php index.php cron (new_items|export|statistics|daily)\n";
		echo "    php index.php cron export [export-specific-arguments]\n\n";
	}

	function already_running($action) {
		// Get running processes.
		$commands = array();
		if (PHP_OS_FAMILY == 'Windows') {
			exec("tasklist | FIND \"php\"",$commands);
		} else {
			exec("ps -fe | grep -v sudo | grep php", $commands);
		}

		// If processes are found
		$pid = getmypid().'';
		$found = 0;
		if (PHP_OS_FAMILY == 'Windows') {
			// This is rather limited in that it will allow 
			// only one php.exe to be running at a time. Activities
			// external to Macaw may impact macaw's ability to do 
			// things like cron jobs.
			$search = "php.exe";
		} else {
			$search = "index.php cron ".$action;
		}		
		if (count($commands) > 0) {
			foreach ($commands as $command) {
				if (strpos($command, $search) !== false && 
					strpos($command, $pid) !== false) {
					$found++;
				}
			}
		}

		// Are we running more than once?
		if ($found > 1) {
			return true;
		}
		return false;
	}

	function calculate_sizes() {
		// Loop through the items in the books folder
		if ($h = opendir($this->cfg['data_directory'])) {
			while (false != ($f = readdir($h))) {
				try {
					if ($f != '.' && $f != '..' && $f != 'import_export' && $f != 'archive') {
						if (!is_file($f) && !preg_match('/_delete/', $f)) {
							// Count the size
							$this->book->load($f);
							$this->book->total_mbytes = $this->book->_dir_size($this->cfg['data_directory'].'/'.$f);
							// Save the size to the book
							$this->book->update();
						}
					}
				} catch (Exception $e) {
					// Nothing
				}
			}
		}
	}

}
