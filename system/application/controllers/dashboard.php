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

	function Dashboard() {
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
		$data['user_widgets'] = $this->_user_widgets();
		$this->load->view('dashboard/dashboard_view', $data);
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
		$names = split(',', $name);
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

		$data = array();
		$data['title'] = 'Summary';
		$data['html'] = '<div id="summary-widget">'.$row->new.' items ready to be scanned.<br>'.
		               $reviewing.' items in progress.<br>'.
		               $row->reviewed.' reviewed and ready to share.<br>'.
		               $row->exporting.' being exported or verified.<br>'.
		               $row->completed.' items completed.<br>'.
		               $row->archived.' items archived.<br>'.
		               $row->error.' items have errors.<br><br>'.
		               $row->pages.' pages scanned total.<br>'.
		               $row->avg.' average scanning time.</div>';
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
				"select to_char(date,'fmmm/fmdd') as day, round(value / 1024 / 1024 / 1024) as megs from logging where statistic = 'disk-usage' and date >= now() - interval '10 days' order by date"
			);
		} elseif ($this->db->dbdriver == 'mysql') {
			$q = $this->db->query(
				"select date_format(date,'%c/%d') as day, round(value / 1024 / 1024 / 1024) as megs from logging where statistic = 'disk-usage' and datediff(now(), date) <= 10 order by date"
			);
		}
		
		$rows = array();
		foreach ($q->result() as $r) {
			array_push($rows,$r);
		}

		$data = array();
		$data['title'] = 'Disk Usage (GB)';
		$data['datasourcetype'] = 'YAHOO.util.DataSource.TYPE_JSARRAY';
		$data['html'] = 'Make sure you have Adboe Flash Version 9.0.4 or better installed. If you are seeing this, you need to <a href="http://get.adobe.com/flashplayer/">install or ugprade</a>.';
		$data['fields'] = array('day','megs');
		$data['type'] = 'LineChart';
		$data['xField'] = 'day';
		$data['yField'] = 'megs';
		$data['div_id'] = 'disk_usage';
		$data['data'] = $rows;
		$data['column'] = '2';
		return $data;

	// 	{ title: 'Disk Usage',
	// 	  datasourcetype: YAHOO.util.DataSource.TYPE_JSARRAY,
	// 	  fields: [ "day","megs" ],
	// 	  type: 'LineChart',
	// 	  xField: "day",
	// 	  yField: "megs",
	// 	  div_id: 'disk_usage',
	// 	  column: 2,
	// 	  data: [ { day: '6/7', megs: '6'},
	// 			  { day: '6/8', megs: '16'},
	// 			  { day: '6/9', megs: '28'},
	// 			  { day: '6/10', megs: '32'},
	// 			  { day: '6/11', megs: '47'},
	// 			  { day: '6/12', megs: '51'},
	// 			  { day: '6/13', megs: '51'},
	// 			  { day: '6/14', megs: '75'},
	// 			  { day: '6/15', megs: '89'},
	// 			  { day: '6/16', megs: '93'}
	// 			],
	// 		html: 'Make sure you have Adboe Flash Version 9.0.4 or better installed. If you are seeing this, you need to <a href="http://get.adobe.com/flashplayer/">install or ugprade</a>.'
	// 	}

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
		} elseif ($this->db->dbdriver == 'mysql') {
			$q = $this->db->query("select date_format(date,'%c/%d') as day, value as pages from logging where statistic = 'total-pages' and datediff(now(), date) <= 10 order by date");
		}

		$rows = array();
		foreach ($q->result() as $r) {
			array_push($rows,$r);
		}

		$data = array();
		$data['title'] = 'Total Pages Scanned';
		$data['datasourcetype'] = 'YAHOO.util.DataSource.TYPE_JSARRAY';
		$data['html'] = 'Make sure you have Adboe Flash Version 9.0.4 or better installed. If you are seeing this, you need to <a href="http://get.adobe.com/flashplayer/">install or ugprade</a>.';
		$data['fields'] = array('day','pages');
		$data['type'] = 'LineChart';
		$data['xField'] = 'day';
		$data['yField'] = 'pages';
		$data['div_id'] = 'total_scanned';
		$data['data'] = $rows;
		$data['column'] = '1';
		return $data;

	// 	{ title: 'Total Pages Scanned',
	// 	  datasourcetype: YAHOO.util.DataSource.TYPE_JSARRAY,
	// 	  fields: [ "day","pages" ],
	// 	  type: 'LineChart',
	// 	  xField: "day",
	// 	  yField: "pages",
	// 	  div_id: 'total_scanned',
	// 	  column: 1,
	// 	  data: [ { day: '6/7', pages: '5'},
	// 			  { day: '6/8', pages: '15'},
	// 			  { day: '6/9', pages: '27'},
	// 			  { day: '6/10', pages: '38'},
	// 			  { day: '6/11', pages: '46'},
	// 			  { day: '6/12', pages: '50'},
	// 			  { day: '6/13', pages: '50'},
	// 			  { day: '6/14', pages: '74'},
	// 			  { day: '6/15', pages: '88'},
	// 			  { day: '6/16', pages: '100'}
	// 			],
	// 		html: 'Make sure you have Adboe Flash Version 9.0.4 or better installed. If you are seeing this, you need to <a href="http://get.adobe.com/flashplayer/">install or ugprade</a>.'
	// 	}

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
		} elseif ($this->db->dbdriver == 'mysql') {
			$q = $this->db->query("select date_format(date,'%c/%d') as day, value as pages from logging where statistic = 'pages' and datediff(now(), date) <= 10 order by date");
		}

		$rows = array();
		foreach ($q->result() as $r) {
			array_push($rows,$r);
		}

		$data = array();
		$data['title'] = 'Pages Per Day';
		$data['datasourcetype'] = 'YAHOO.util.DataSource.TYPE_JSARRAY';
		$data['html'] = 'Make sure you have Adboe Flash Version 9.0.4 or better installed. If you are seeing this, you need to <a href="http://get.adobe.com/flashplayer/">install or ugprade</a>.';
		$data['fields'] = array('day','pages');
		$data['type'] = 'ColumnChart';
		$data['xField'] = 'day';
		$data['yField'] = 'pages';
		$data['div_id'] = 'pages_per_day';
		$data['data'] = $rows;
		$data['column'] = '2';
		return $data;

	// 	{ title: 'Pages Per Day',
	// 	  datasourcetype: YAHOO.util.DataSource.TYPE_JSARRAY,
	// 	  fields: [ "day","pages" ],
	// 	  type: 'ColumnChart',
	// 	  xField: "day",
	// 	  yField: "pages",
	// 	  div_id: 'pages_per_day',
	// 	  column: 2,
	// 	  data: [ { day: '6/7', pages: '23'},
	// 			  { day: '6/8', pages: '23'},
	// 			  { day: '6/9', pages: '27'},
	// 			  { day: '6/10', pages: '24'},
	// 			  { day: '6/11', pages: '29'},
	// 			  { day: '6/12', pages: '1'},
	// 			  { day: '6/13', pages: '1'},
	// 			  { day: '6/14', pages: '35'},
	// 			  { day: '6/15', pages: '28'},
	// 			  { day: '6/16', pages: '24'}
	// 			],
	// 		html: 'Make sure you have Adboe Flash Version 9.0.4 or better installed. If you are seeing this, you need to <a href="http://get.adobe.com/flashplayer/">install or ugprade</a>.'
	// 	}

	}
}
