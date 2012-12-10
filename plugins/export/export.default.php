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

	}
}
