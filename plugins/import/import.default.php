<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Custom Import Module
 *
 * Provides an interface to local systems to get the bibliographic and
 * item-level metadata for a given installation of Macaw. This contains one
 * function which returns a list of things that are ready to be imported into
 * Macaw.
 *
 *
 * @package metadata
 * @author Joel Richard
 * @version 1.0 admin.php created: 2010-09-20 last-modified: 2010-08-19
 **/

class Import extends Controller {

	var $CI;
	var $cfg;
	public $error;

	function Import() {
		$this->CI = get_instance();
		$this->CI->load->library('session');
		$this->cfg = $this->CI->config->item('macaw');
	}

	/**
	 * Get new items
	 *
	 * Gets an array of things that are ready to be scanned. The structure of
	 * the resulting object must be the following, but the only required field
	 * in each item is the barcode.
	 *
	 * 		$items = Array(
	 * 				[0] => Array(
	 * 					'barcode'     => '39088010037075',
	 * 					'call_number' => 'Q11 .U52Z',
	 * 					'location'    => 'FISH',
	 * 					'volume'      => 'v 1; part 2',
	 * 					'copyright'   => '1'
	 * 				)
	 * 				[1] => Array(
	 *				  	[ ... ]
	 * 				)
	 * 				[2] => Array(
	 *				  	[ ... ]
	 * 				)
	 *				[ ... ]
	 * 			)
	 * 		)
	 *
	 * If other databases need to be reached, they can be done so from other
	 * functions that you create in this module. It's expected that this will
	 * be necessary.
	 *
	 * @return Array of arrays containing the information for new items.
	 */
	function get_new_items() {
		// $return = array()
		//
		// array_push($return, array(
		//	'barcode'     => '1234567890',
		//	'call_number' => 'Q11 .u52Z',
		//	'location'    => 'Main library stacks',
		//	'copyright'   => '1',
		// );
		//
		// array_push($return, array(
		//	'barcode'     => '1234567890',
		//	'call_number' => 'Q45 .M91X',
		//	'location'    => 'Regional branch',
		//	'copyright'   => '0',
		// );
		//
		// return $return;
	}


}












