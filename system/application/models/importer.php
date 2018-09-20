<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Import Model
 *
 * MACAW Metadata Collection and Workflow System
 *
 * Entry-point to the command-line-based scheduled jobs that communicate with
 * external database(s) to import items that are ready to be scanned. Interfaces
 * with the custom import plugin modules.
 *
 * @package Importer
 * @author Joel Richard
 * @version 1.0 admin.php created: 2010-07-07 last-modified: 2010-08-19

	Change History
	Date        By   Note
	------------------------
	2010-07-07  JMR  Created

 **/

class Importer extends Model {

	function __construct() {
		// Call the Model constructor
		parent::Model();
		$this->load->helper('file');
		$this->cfg = $this->config->item('macaw');
	}

	/**
	 * Import items into our database
	 *
	 * CLI: Calls the various import plugins to get their list of items that are
	 * ready to be scanned. Inserts those items into our database.
	 *
	 */
	function import() {

		// Get the list of export modules
		$config = $this->config->item('macaw'); // PHP Sucks, two step when one would suffice.
		$import_modules = $config['import_modules'];

		// Get the arguments passed in.
		$args = func_get_args();
		$args = $args[0];

		// Did we get any arguments?
		if (count($args) > 0) {
			// Decide if the first argument is the name of an Import module, if it is, we call just that module
			// with the remainder of the arguments.
			if (in_array($args[0], $import_modules)) {
				require_once($config['plugins_directory'].'/import/'.$args[0].EXT);
				eval('$obj = new '.$args[0].'();');
				// Ensure we have an argument
				$arg = null;
				if (count($args) > 1) {
					array_shift($args);
					$arg = $args[0];
				} else {
					$arg = $args[0];
				}
				$this->_import($obj->get_new_items($args), $arg);
			} else {
				echo "Import Module not found: $args[0] (Allowed values are: ".implode(', ', $import_modules).")\n";
			}

		} else {

			// Loop through the list, calling the share() function on each object
			foreach ($import_modules as $p) {
				require_once($config['plugins_directory'].'/import/'.$p.EXT);
				eval('$obj = new '.$p.'();');
				$this->_import($obj->get_new_items($args), $p);
			}
		}
	}

	function _import($items, $module) {
		// Now add anything that needs to be added
		$this->logging->log('access', 'info', 'Importing new items for module '.$module);

		foreach ($items as $i) {
			try {
				$ret = $this->book->add($i);
			} catch (Exception $e) {
				$this->logging->log('error', 'info', $e->getMessage());
			}


			if ($ret) {
				$ret = $this->book->load($i['barcode']);
				$this->logging->log('access', 'info', 'Item wtih barocde '.$this->book->barcode.' found, added, and set up.');
				$this->logging->log('book', 'info', 'Item found, added, and set up.', $this->book->barcode);
			} else {
				if (strlen($this->book->last_error) > 0) {
					echo($this->book->last_error."\n");
				}
			} // if ($ret)
		} // foreach ($add as $a)

	}
}



