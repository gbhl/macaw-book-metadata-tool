<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Dashboard Controller
 *
 * MACAW Metadata Collection and Workflow System
 *
 * Handles showing, adding, removing widgets. Also handles saving of the
 * displayed widgets to a user's profile.
 *
 **/

class Dashboard extends Controller {

	function __construct() {
		parent::Controller();
	}

	/**
	 * Show the dashboard page
	 *
	 * Causes the dashboard page to be displayed. Includes a list of widgets
	 * from the user's preferences. Includes a list of available widgets that
	 * aren't already in the user's preferences.
	 *
	 * @return html The dashboard_view.php page.
	 */
	function index() {
		$this->common->check_session();
		
		$this->user->load($this->session->userdata('username'));
		if ($this->user->terms_conditions == '0000-00-00 00:00:00') {
	 		redirect($this->config->item('base_url').'main/terms');			
		} else {
			$data['user_widgets'] = $this->_user_widgets();
			$this->load->view('dashboard/dashboard_view', $data);
		}
	}

	/**
	 * Gets a user's dashboard widgets
	 *
	 * AJAX: Get the list of widgets from the current user's preferences. The
	 * results of this is a two-element array of arrays of the items in the
	 * lower array are the widgets in order for each column
	 *
	 * @access public
	 * @return json The list of widgets and which columns they belong in
	 *
	 * @todo Make this pull from the current user's settings
	 */
	function _user_widgets() {
		$this->user->load($this->session->userdata('username'));
		return '{"widgets":'.$this->user->widgets.'}';
	}

	/**
	 * Get one or more widgets
	 *
	 * AJAX: Given a name, return the info and data needed to build the widget
	 * on the page.
	 *
	 * @access public
	 * @param string [$name] The name(s) of the widget to get (comma-separated)
	 * @return json The description and data of the named widget (echoed to browser)
	 *
	 */
	function widget($name) {
		if (!$this->common->check_session(true)) {
			return;
		}

		$this->common->ajax_headers();
		$names = preg_split('/,/', $name);
		$ret = array();

		foreach ($names as $n) {
			if ($n == 'summary') {
				$ret[$n] = $this->_get_summary_widget();

			} else if ($n == 'disk') {
				$ret[$n] = $this->_get_disk_widget();

			} else if ($n == 'pages') {
				$ret[$n] = $this->_get_pages_widget();

			} else if ($n == 'perday') {
				$ret[$n] = $this->_get_perday_widget();
			}
		}
		echo json_encode(array('widgets' => $ret));
	}


	/**
	 * Save a user's widgets
	 *
	 * AJAX: Takes a list of widgets to save them to the user's preferences.
	 * Returns nothing.
	 *
	 * @param json [$objects] An object describing the widgets for the current user.
	 */
	function save_widgets($widgets) {
		$this->user->load($this->session->userdata('username'));
		if ($widgets) {
			$this->user->widgets = $widgets;
		} else {
			$this->user->widgets = $this->input->post('data');
		}
		$this->user->update();
	}


	/**
	 * Get Summary widget data
	 *
	 * AJAX: Handed off from the widget() function, gathers and outputs the data
	 * for the Summary widget
	 *
	 * @access internal
	 * @return json The description and data of the named widget (echoed to browser)
	 *
	 */
	function _get_summary_widget() {
		// 	{ title: 'Summary',
		// 	  html: '35 Books ready to be scanned<blockquote>10 at Library<br>25 at Pennsy<br></blockquote>12 Books scanned,
		//           pending check-in<br><br>62 Books scanned total<br><br>3,585 Pages scanned total<br><br>Avg Time per Page / Book:   18 s / 2.5 h<br>',
		// 	  column: 1
		// 	}
		$data = array();

		$row = $this->book->get_status_counts();
		$reviewing = $row->scanning + $row->scanned + $row->reviewing; // Things in progress
		$qa = $row->qa_ready + $row->qa_active; // Things in QA

		$data = array();
		$data['title'] = 'Summary';
		$data['html'] = '<div id="summary-widget">'.$row->new.' new item'.($row->new > 1 || $row->new == 0 ? 's' : '').'.<br>'.
		               $reviewing.' item'.($reviewing > 1 || $reviewing == 0 ? 's' : '').' in progress.<br>'.
		               $qa.' item'.($qa > 1 || $qa == 0 ? 's' : '').' in QA.<br>'.
		               $row->reviewed.' item'.($row->reviewed > 1 || $row->reviewed == 0 ? 's' : '').' ready to export.<br>'.
		               $row->exporting.' item'.($row->exporting > 1 || $row->exporting == 0 ? 's' : '').' being exported.<br>'.
		               $row->completed.' item'.($row->completed > 1 || $row->completed == 0 ? 's' : '').' completed.<br>'.
		               $row->error.' item'.($row->error > 1 || $row->error == 0 ? 's' : '').' have errors.<br><br>'.
		               $row->pages.' pages scanned total.<br>';
		$data['column'] = '1';
		return $data;
	}

	/**
	 * Get Disk Usage widget data
	 *
	 * AJAX: Handed off from the widget() function, gathers and outputs the data
	 * for the Disk Usage widget
	 *
	 * @access internal
	 * @return json The description and data of the named widget (echoed to browser)
	 *
	 */
	function _get_disk_widget() {
		$q = null;
		if ($this->db->dbdriver == 'postgre') {
			$q = $this->db->query(
				"select to_char(date,'fmmm/fmdd') as day, value from logging where statistic = 'disk-usage' and date >= now() - interval '10 days' order by date"
			);
		} elseif ($this->db->dbdriver == 'mysql' || $this->db->dbdriver == 'mysqli') {
			$q = $this->db->query(
				"select date_format(date,'%c/%d') as day, value from logging where statistic = 'disk-usage' and datediff(now(), date) <= 10 order by date"
			);
		}
		
		$rows = array();
		foreach ($q->result() as $r) {
			array_push($rows,$r);
		}

		$data = array();
		$data['fields'] = array('day','megs');
		$data['data'] = $rows;
		return $data;
	}

	/**
	 * Get Total Pages Scanned widget data
	 *
	 * AJAX: Handed off from the widget() function, gathers and outputs the data
	 * for the Total Pages Scanned widget
	 *
	 * @access internal
	 * @return json The description and data of the named widget (echoed to browser)
	 *
	 */
	function _get_pages_widget() {
		$q = null;
		if ($this->db->dbdriver == 'postgre') {
			$q = $this->db->query("select to_char(date,'fmmm/fmdd') as day, value as pages from logging where statistic = 'total-pages' and date >= now() - interval '10 days' order by date");
		} elseif ($this->db->dbdriver == 'mysql' || $this->db->dbdriver == 'mysqli') {
			$q = $this->db->query("select date_format(date,'%c/%d') as day, value as pages from logging where statistic = 'total-pages' and datediff(now(), date) <= 10 order by date");
		}

		$rows = array();
		foreach ($q->result() as $r) {
			array_push($rows,$r);
		}

		$data = array();
		$data['fields'] = array('day','pages');
		$data['data'] = $rows;
		return $data;
	}

	/**
	 * Get Pages Per Day widget data
	 *
	 * AJAX: Handed off from the widget() function, gathers and outputs the data
	 * for the Pages Per Day widget
	 *
	 * @access internal
	 * @return json The description and data of the named widget (echoed to browser)
	 *
	 */
	function _get_perday_widget() {
		$q = null;
		if ($this->db->dbdriver == 'postgre') {
			$q = $this->db->query("select to_char(date,'fmmm/fmdd') as day, value as pages from logging where statistic = 'pages' and date >= now() - interval '10 days' order by date");
		} elseif ($this->db->dbdriver == 'mysql' || $this->db->dbdriver == 'mysqli') {
			$q = $this->db->query("select date_format(date,'%c/%d') as day, value as pages from logging where statistic = 'pages' and datediff(now(), date) <= 10 order by date");
		}

		$rows = array();
		foreach ($q->result() as $r) {
			array_push($rows,$r);
		}

		$data = array();
		$data['fields'] = array('day','pages');
		$data['data'] = $rows;
		return $data;
	}
}
