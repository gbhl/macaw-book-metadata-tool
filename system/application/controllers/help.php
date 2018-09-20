<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Help Controller
 *
 * MACAW Metadata Collection and Workflow System
 *
 * General use for showing the help pages
 *
 **/

class Help extends Controller {

	var $cfg;

	function __construct() {
		parent::Controller();
		$this->cfg = $this->config->item('macaw');
	}

	/**
	 * Display the main window
	 *
	 * Shows the main page with activities that can be performed against the
	 * current book.
	 *
	 */
	function index() {
		$this->load->view('help/help_view');
	}

	function overview() {
		$this->load->view('help/overview_view');
	}

	function quickstart() {
		$this->load->view('help/quickstart_view');
	}

	function scanning() {
		$this->load->view('help/scanning_view');
	}

	function reviewing() {
		$this->load->view('help/reviewing_view');
	}

	function missing() {
		$this->load->view('help/missing_view');
	}

	function network() {
		$this->load->view('help/network_view');
	}

	function export() {
		$this->load->view('help/export_view');
	}

	function misc() {
		$this->load->view('help/misc_view');
	}

	function help_index() {
		$this->load->view('help/help_index_view');
	}

}
