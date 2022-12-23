<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Export Model
 *
 * MACAW Metadata Collection and Workflow System
 *
 * Entry-point to the command-line-based scheduled jobs that communicate with
 * the export modules. Uses the custom Export Modules.
 *
 * This controller, called only from the command line for scheduled jobs,
 * handles the interaction with the external systems, namely Internet Archive,
 * but eventually our own Artesia DAMS system. Each of these functions will
 * ensure that they are run only from the command line.
 *
 * @package exporter
 * @author Joel Richard
 * @version 1.0 admin.php created: 2010-07-07 last-modified: 2010-08-19
 * 
 * 	Change History
 * 	Date        By   Note
 * 	------------------------
 * 	2010-07-07  JMR  Created
 * 
 **/

class Exporter extends Model {

	function __construct() {
		// Call the Model constructor
		parent::__construct();
		$this->load->helper('file');
		$this->cfg = $this->config->item('macaw');
	}

	/**
	 * Share a book with our external systems
	 *
	 * CLI: Looks to the queue for the next book that hasn't been shared to
	 * some external system and preps them to send out for export by calling
	 * the export module.
	 *
	 */
	function export() {

		// Get the list of export modules
		$config = $this->config->item('macaw'); // PHP Sucks, two step when one would suffice.
		$export_modules = $config['export_modules'];

		// Get the arguments passed in.
		$args = func_get_args();
		$args = $args[0];

		// Did we get any arguments?
		if (count($args) > 0) {
			// Decide if the first argument is a export module name, if it is, we call just that module
			// with the remainder of the arguments.
			if (in_array($args[0], $export_modules)) {
				require_once($config['plugins_directory'].'/export/'.$args[0].EXT);
				eval('$obj = new '.$args[0].'();');
				array_shift($args);
				$obj->export($args);
			} else {
				echo "Export Module not found: $args[0] (Allowed values are: ".implode(', ', $this->cfg['export_modules']).")\n";
			}
			return;
		}

		// Loop through the list, calling the share() function on each object
		foreach ($export_modules as $p) {
 			require_once($config['plugins_directory'].'/export/'.$p.EXT);
 			eval('$obj = new '.$p.'();');
 			$obj->export($args);
		}
	}

}
