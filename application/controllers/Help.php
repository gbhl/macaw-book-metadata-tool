<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Help Controller
 *
 * MACAW Metadata Collection and Workflow System
 *
 * General use for showing the help pages
 *
 **/

class Help extends CI_Controller {

	var $cfg;

	public function __construct() {
		parent::__construct();
		$this->cfg = $this->config->item('macaw');
	}

	/**
	 * Display the main window
	 *
	 * Shows the main page with activities that can be performed against the
	 * current book.
	 *
	 */
	public function index() {
		$this->load->view('help/help_view');
	}

	public function overview() {
		$this->load->view('help/overview_view');
	}

	public function quickstart() {
		$this->load->view('help/quickstart_view');
	}

	public function scanning() {
		$this->load->view('help/scanning_view');
	}

	public function reviewing() {
		$this->load->view('help/reviewing_view');
	}

	public function missing() {
		$this->load->view('help/missing_view');
	}

	public function network() {
		$this->load->view('help/network_view');
	}

	public function export() {
		$this->load->view('help/export_view');
	}

	public function misc() {
		$this->load->view('help/misc_view');
	}

	public function help_index() {
		$this->load->view('help/help_index_view');
	}

}
