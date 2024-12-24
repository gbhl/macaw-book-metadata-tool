<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
// ***********************************************************
// Macaw Metadata Collection and Workflow System
//
// EXPORT LIBRARY
//
// Each destination with whom we share our book will have an export routine
// which contains functions for sending data to the system, verifying receipt
// of the data, and optionally pulling any derivative data, etc.
// Each Export library corresponds to an entry in the macaw.php file.
//
// Each module has a library with the name "Export_Name.php". The name
// must correspond to one of the items in the export_modules entry in the macaw.php
// configuration file. Each must contain ax export() methox. Other functions
// may be used if necessary.
//
// Each module must set a "completed" status to the export process via the
// Book objects set_export_status() method:
//
// $this->CI->book->set_export_status('completed');
//
// Other statuses are allowed if the exporting happens in multiple steps.
// This module is required to maintan the statuses and eventually set a
// status of 'completed' when it's finished exporting. Once all export
// modules have marked the item as completed, Macaw then proceeds to archive
// and purge the data on its own schedule, if such routines are set up.
//
// Change History
// Date        By   Note
// ------------------------
// 2010-07-07  JMR  Created
// 2011-08-11  JMR  Trimmed down to include only the export() method
//
// ***********************************************************

class Export_Generic extends Controller {

	// ----------------------------
	// Configuration parameters
	//
	// Other types of information you might want to store in
	// here as you build your module. You should customize this as
	// necessary for the individual export destination.
	// ----------------------------
	// private $submission_url = "http://submit.website.com/";
	// private $submission_key = "kiDtXwew234FwkKJUDdkv3tj3RXF6uigFqET";
	// private $harvest_url    = "http://www.website.com/";
	// private $harvest_items  = array('ocr.txt','meta.xml','other-file.pdf');

	var $CI;
	var $cfg;

	// ----------------------------
	// Function: CONSTRUCTOR
	//
	// Be sure to rename this from "Export_Generic" to whatever you named the
	// class above. Othwerwise, ugly things will happen. You don't need to edit
	// anything here, either.
	// ----------------------------

	function Export_Generic() {
		$this->CI = get_instance();
		$this->cfg = $this->CI->config->item('macaw');
	}

	// ----------------------------
	// Function: export()
	//
	// Parameters:
	//    $args - An array of items passed from the command line (or URL)
	//            that are specific to this module. The Export Mode
	//            simply passes these in as the were received.
	//
	// Sends everything to the export. This function is called by the
	// Exporter model. The code in this function will be unique for each
	// export destination. If additional files need to be created for expport,
	// they are done here. This function may connect to web servers, send files
	// by FTP or whatever else might need to be done to submit data to a remote
	// system.
	// ----------------------------
	function export($args) {

		// --------------------------------------
    // If we can run multiple exports, then do so
		// --------------------------------------
    $limit = 1;
    if (array_key_exists('export_concurrency_limit', $this->cfg)) {
      $limit = (int)$this->cfg['export_concurrency_limit'];
      if ($limit < 1) { $limit = 1; } // Limit the limits
    }

		// --------------------------------------
    // Are there too many sibling processes? 
		// --------------------------------------
    $found = $this->count_exports();

    if ($found > ($limit-1)) { // We subtract one to account for ourself
      // No, so we quit.
      if (!getenv("MACAW_OVERRIDE")) {
        $this->CI->logging->log('access', 'info', "Too many Internet_archive children. Exiting.");
        return false;
      } else {
        $this->CI->logging->log('access', 'info', "Got override. Continuing.");
      }
    }

    // --------------------------------------
    // Start the export work here
    // --------------------------------------

	}

	// ----------------------------
	// Function: count_exports()
	//
	// Count how many processes like ourselves already exists.
  // 
  // Self-contained, but using this on windows will usually
  // always return 1. 
	// ----------------------------
  function count_exports() {
		// --------------------------------------
    // Count how many are running, remember we count as one process
		// --------------------------------------
		$commands = array();
		$pid = getmypid().'';
		$found = 0;
    $search = "export ".basename(__FILE__, '.php'); 

    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
      // Windows will be always be limited to 1.
			exec("tasklist | FIND \"php\"",$commands);
			$search = "php.exe";
		} else {
			exec("ps -fe | grep -v sudo | grep php", $commands);
		}
    
		if (count($commands) > 0) {
			foreach ($commands as $command) {
				if (strpos($command, $search) > 0 && strpos($command, $pid) == 0) {
					$found++;
				}
			}
		}
    return $found;
  }
}
