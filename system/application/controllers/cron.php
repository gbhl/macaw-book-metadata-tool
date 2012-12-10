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
 * # Check every minute of every weekday for new pages
 * * * * * 1-5     /opt/local/apache2/htdocs/cron.php --run=/cron/import_pages --quiet > /dev/null
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

	function Cron() {
		parent::Controller();
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
				return false;				
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
	 * Search for newly scanned pages and add them to the system
	 *
	 * CLI: Cycles through all of the scanning servers to batch process the
	 * movement of raw scanned files from the scanning server onto the
	 * processing server, processing those files and updating the database
	 * accordingly. Calls Book.get_scanned_files(), Book.copy_scanned_file(),
	 * and Book.process_file(), but not necessarily in that order.
	 *
	 * @since Version 1.5
	 */
	function import_pages() {
		$args = func_get_args();

		if (!$this->_init('import_pages')) { return; }
		// Scan the directory and see if there are any files there
		if (count($args) > 0) {
			$barcodes = $args;
		} else {			
			$barcodes = directory_map($this->cfg['incoming_directory'], TRUE);
		}
		
		if (count($barcodes)) {
			// Assume that any directory names are the barcodes for a book
			foreach ($barcodes as $bc) {
				if ($this->book->exists($bc)) {
					try {
						// Check that the barcode actually exists
						// If it doesn't, this will throw an error
						$this->book->load($bc);
					} catch (Exception $e) {
						// If the barcode does not exist, log an error of "debug" status.
						$this->logging->log('error', 'debug', $this->book->last_error);
						continue;
					}
					$this->book->import_images();
				} else {
					echo "Item with barcode \"$bc\" not found.\n";	
				}
			}
		}
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

		// When we're done, archive the books
		$books = $this->book->search('status_code', 'completed', 'date_completed');
		foreach ($books as $b) {
			$this->book->load($b->barcode);
			$this->book->archive();
		}
	}

	function statistics() {
		if (!$this->_init('statistics')) { return; }
		// 1. Get the number of pages from yesterday
		$this->db->query(
			"insert into logging values (
				date_trunc('d', now() - interval '1 day') ,
				'pages',
				(select count(*)
				from page
				where created between date_trunc('d', now() - interval '1 day')
						          and date_trunc('d', now() + interval '1 day'))
			)"
		);

		// 2. Get the total number of pages scanned
		$this->db->query(
			"insert into logging values (
				date_trunc('d', now() - interval '1 day') ,
				'total-pages',
				(select count(*) from page)
			)"
		);

		// 3. Get the number of bytes used in the /books/ directory
		$output = array();
		$matches = array();
		exec('df -k '.$this->cfg['data_directory'], $output);
		$bytes = preg_match('/^.*? +\d+ (\d+)/', $output[1], $matches);
		$this->db->query(
			"insert into logging values (
				date_trunc('d', now() - interval '1 day') ,
				'disk-usage',
				".($matches[1] * 1024)."
			)"
		);
	}

	function index() {
		echo "No command specified.\n";
		echo "Usage:\n    php index.php cron (new_items|import_pages|export|statistics)\n";
		echo "    php index.php cron export [export-specific-arguments]\n\n";
	}

	function already_running($action) {
		// Get running processes.
		$commands = array();
		exec("ps -fe | grep -v sudo | grep php", $commands);

		// If processes are found
		$pid = getmypid().'';
		$found = 0;
		$search = "index.php cron ".$action;
		if (count($commands) > 0) {
			foreach ($commands as $command) {
				if (strpos($command, $search) > 0 && strpos($command, $pid) == 0) {
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

}
