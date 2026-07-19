<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Admin Controller
 *
 * MACAW Metadata Collection and Workflow System
 *
 * Governs administrative activities such editing users and contributros, 
 * maintenance, export queues.
 *
 **/

class Admin extends Controller {

	var $cfg;

	/* LOCAL ADMIN COMPLETED */
	function __construct() {
		parent::__construct();
		$this->cfg = $this->config->item('macaw');
	}

	/**
	 * Load the main admin page
	 *
	 * Simply makes sure the user is logged in and shows the admin main page.
	 */
	/* LOCAL ADMIN COMPLETED */
	function index() {
		$this->common->check_session();

		// Permission Checking
		if (!$this->user->has_permission('admin') && !$this->user->has_permission('local_admin')) {
			$this->session->set_userdata('errormessage', 'You do not have permission to access that page.');
			redirect($this->config->item('base_url').'main/listitems');
			$this->logging->log('error', 'debug', 'Permission Denied to access '.uri_string());
		}

		$data['admin'] = ($this->session->userdata('username') == 'admin');
		$this->load->view('admin/admin_view', $data);
	}

	/**
	 * View a summary of the queues
	 *
	 * Lists the items that are in a given queue along with title and date they
	 * entered that status. If no queue is given, returns an array of all
	 * queues and the number of things in them.
	 * Status:
	 *
	 * @access public
	 * @param string [$status] Which statuses to show. (What the heck does this do?)
	 * @since Version 1.0
	 */
	function queues() {
		$this->common->check_session();

		// Permission Checking
		if (!$this->user->has_permission('admin') && !$this->user->has_permission('local_admin')) {
			$this->session->set_userdata('errormessage', 'You do not have permission to access that page.');
			redirect($this->config->item('base_url').'main/listitems');
			$this->logging->log('error', 'debug', 'Permission Denied to access '.uri_string());
		}

		$data['admin'] = ($this->session->userdata('username') == 'admin');
		$this->load->view('admin/queue_view', $data);
	}

	/**
	 * Get the data for the queues
	 *
	 * AJAX: Gets an array of five arrays listing all of the items in each
	 * of five groups. The groups are, loosely: new items, items being handled
	 * by the users, items being exported, items completed, and items
	 * with errors.
	 *
	 * @access public
	 * @since Version 1.2
	 */
	function queue_data($completed = false) {
		if (!$this->common->check_session(true)) {
			return;
		}

		// Create an array of subarrays to subdivide the data
		$data = array(
			'new_items' => array(),
			'in_progress' => array(),
			'qa' => array(),
			'export_ready' => array(),
			'exporting' => array(),
			'completed' => array(),
			'error' => array()
		);

		// Get all books in the system along with their data
		$is_local_admin = $this->user->has_permission('local_admin');
		$is_admin = $this->user->has_permission('admin');
		$org_id = 0;
		if ($is_local_admin && !$is_admin) {
			$this->user->load($this->session->userdata('username'));
			$org_id = $this->user->org_id;
		}
		$books = null;
		if ($completed) {
			$books = $this->book->get_all_books(true, $org_id, array('completed'), false);
		} else {
			$books = $this->book->get_all_books(true, $org_id, array('new','scanning','scanned','reviewing','qa-ready','qa-active','reviewed','exporting','error'));
		}

		// Sort our records into the subarrays
		foreach ($books as $b) {
			if ($b->status_code == 'new') {
				$b->date = preg_replace('/ \d\d:\d\d:\d\d/','',$b->date_created);
				if ($b->date == '0000-00-00') { $b->date = ''; }
				array_push($data['new_items'], $b);

			} elseif ($b->status_code == 'scanning' || $b->status_code == 'scanned' || $b->status_code == 'reviewing') {
				$b->date = '';
				if (isset($b->date_review_end) && $b->date_review_end != '0000-00-00 00:00:00' && $b->date_review_end != '') {
					$b->date = preg_replace('/ \d\d:\d\d:\d\d/','',$b->date_review_end);
				} elseif (isset($b->date_review_start) && $b->date_review_start != '0000-00-00 00:00:00' && $b->date_review_start != '') {
					$b->date = preg_replace('/ \d\d:\d\d:\d\d/','',$b->date_review_start);
				} elseif (isset($b->date_scanning_end) && $b->date_scanning_end != '0000-00-00 00:00:00' && $b->date_scanning_end != '') {
					$b->date = preg_replace('/ \d\d:\d\d:\d\d/','',$b->date_scanning_end);
				} elseif (isset($b->date_scanning_start) && $b->date_scanning_start != '0000-00-00 00:00:00' && $b->date_scanning_start != '') {
					$b->date = preg_replace('/ \d\d:\d\d:\d\d/','',$b->date_scanning_start);
				}
				if ($b->date == '0000-00-00') { $b->date = ''; }
				array_push($data['in_progress'], $b);

			} elseif ($b->status_code == 'qa-ready' || $b->status_code == 'qa-active') {
				$b->date = preg_replace('/ \d\d:\d\d:\d\d/','',$b->date_qa_start);
				if ($b->date == '0000-00-00') { $b->date = ''; }
				array_push($data['qa'], $b);

			} elseif ($b->status_code == 'reviewed') {
				$b->date = preg_replace('/ \d\d:\d\d:\d\d/','',$b->date_qa_end);
				if ($b->date == '0000-00-00') { $b->date = ''; }
				array_push($data['export_ready'], $b);

			} elseif ($b->status_code == 'exporting') {
				$b->date = preg_replace('/ \d\d:\d\d:\d\d/','',$b->date_export_start);
				if ($b->date == '0000-00-00') { $b->date = ''; }
				array_push($data['exporting'], $b);

			} elseif ($b->status_code == 'completed') {
				$b->date = preg_replace('/ \d\d:\d\d:\d\d/','',$b->date_completed);
				if ($b->date == '0000-00-00') { $b->date = ''; }
				array_push($data['completed'], $b);

			} elseif ($b->status_code == 'error') {
				$b->date = preg_replace('/ \d\d:\d\d:\d\d/','',$b->date_created);
				if ($b->date == '0000-00-00') { $b->date = ''; }
				array_push($data['error'], $b);

			}
		}
		// Send the data back to the browser
		$this->common->ajax_headers();
		echo json_encode(array('data' => $data));
	}

	/**
	 * Get the data for the queues for users
	 *
	 * AJAX: Returns an array containing: new items and items being handled
	 * by the users, 	 *
	 * @access public
	 * @since Version 1.2
	 */
	function user_queue_data() {
		if (!$this->common->check_session(true)) {
			return;
		}

		// Create an array of subarrays to subdivide the data
		$data = array(
		  'all_items' => array(),
		  'in_progress' => array(),
		  'qa' => array(),
		  'export_ready' => array()
		);

		// Get all books in the system along with their data
		$books = $this->book->get_all_books(true, 0, array('new','scanning','scanned','reviewing','qa-ready','qa-active','reviewed','exporting'));

		// Sort our records into the subarrays
		foreach ($books as $b) {
      if ($this->user->has_permission('admin')) {
        array_push($data['all_items'], $b);
      } elseif ($this->user->org_id == $b->org_id) {
        array_push($data['all_items'], $b);
      }				

			if (in_array($b->status_code, array('new', 'scanning', 'scanned', 'reviewing'))) {
				if ($this->user->has_permission('admin')) {
					array_push($data['in_progress'], $b);
				} elseif ($this->user->org_id == $b->org_id) {
					array_push($data['in_progress'], $b);
				}				
			} elseif (in_array($b->status_code, array('qa-ready', 'qa-active'))) {
				if ($this->user->has_permission('admin')) {
					array_push($data['qa'], $b);
				} elseif ($this->user->org_id == $b->org_id) {
					array_push($data['qa'], $b);
				}
			} elseif (in_array($b->status_code, array('reviewed'))) {
				if ($this->user->has_permission('admin')) {
					array_push($data['export_ready'], $b);
				} elseif ($this->user->org_id == $b->org_id) {
					array_push($data['export_ready'], $b);
				}
			} 
		}
		
		// Send the data back to the browser
		$this->common->ajax_headers();
		echo json_encode(array('data' => $data));
	}
	
	/**
	 * Get the data for all exporting books for an organization.
	 *
	 * AJAX: Returns an array containing: exporting books for an organization
	 **/
	function user_export_data(){
		if (!$this->common->check_session(TRUE)){
			return;
		}

		// Get all books for the current organization that are exporting.
		$books = $this->book->get_all_books(TRUE, $this->user->org_id, array('exporting'));
		$data = array('exporting' => $books);
		
		// Send the data back to the browser.
		$this->common->ajax_headers();
		echo json_encode(array('data' => $data));
	}
	
	/**
	 * Get the data for all items being exported that have stalled, limited to organization
	 * if not an admin.
	 *
	 * AJAX: Returns an array containing: exporting books
	 **/
	function export_audit_data(){
		if (!$this->common->check_session(TRUE)){
			return;
		}
		if ($this->user->has_permission('admin')){
			$books = $this->book->get_stalled_exports();
		} else {
			$books = $this->book->get_stalled_exports($this->user->org_id);
		}
		
		// Send the data back to the browser.
		$data = array('exporting' => $books);
		$this->common->ajax_headers();
		echo json_encode(array('data' => $data));
	}

	/**
	 * Show the manual functions
	 **/
	/* LOCAL ADMIN COMPLETED */
	function scheduled_jobs() {
		$this->common->check_session();

		// Permission Checking
		if (!$this->user->has_permission('admin')) {
			$this->session->set_userdata('errormessage', 'You do not have permission to access that page.');
			redirect($this->config->item('base_url').'main/listitems');
			$this->logging->log('error', 'debug', 'Permission Denied to access '.uri_string());
		}

			
		$data['export_modules'] = implode(', ',$this->cfg['export_modules']);
		if (isset($this->cfg['demo_organization'])) {
			$data['demo_org'] = $this->cfg['demo_organization'];
		} else {
			$data['demo_org'] = "none"; 
		}
		$data['admin'] = ($this->session->userdata('username') == 'admin');
		$this->load->view('admin/scheduled_jobs_view.php', $data);
	}

	/**
	 * Admin Maintenance Page
	 *
	 * Provides tools for admins to perform occasional administrative tasks
	 * including log management, finding unused directories, and testing email.
	 *
	 * @access public
	 * @since Version 1.8
	 */
	/* LOCAL ADMIN COMPLETED */
	function maintenance() {
		$this->common->check_session();

		// Permission Checking
		if (!$this->user->has_permission('admin')) {
			$this->session->set_userdata('errormessage', 'You do not have permission to access that page.');
			redirect($this->config->item('base_url').'main/listitems');
			$this->logging->log('error', 'debug', 'Permission Denied to access '.uri_string());
		}

		// Get log file summary
		$data['title'] = 'Maintenance | Macaw';
		$data['log_summary'] = $this->_get_log_summary();
		$data['keep_log_days'] = $this->cfg['keep_log_days'];

		// Get completed items for cleanup checks
		$this->load->model('book');
		$completed_books = $this->book->get_all_books(true, 0, array('completed'));
		$data['completed_items'] = $completed_books;

		// Check for unused directories
		$data['unused_directories'] = $this->_find_unused_directories($completed_books);

		// Check for Internet Archive export content
		$ia_path = $this->cfg['data_directory'] . '/import_export/Internet_archive';
		$data['ia_directories'] = $this->_find_ia_export_directories($ia_path, $completed_books);

		$data['admin'] = ($this->session->userdata('username') == 'admin');
		$this->load->view('admin/maintenance_view', $data);
	}

	/**
	 * Get a summary of log files
	 *
	 * @access private
	 * @return array Summary of log files by type
	 */
	function _get_log_summary() {
		$logs_dir = $this->cfg['logs_directory'];
		$keep_days = $this->cfg['keep_log_days'];
		$cutoff_time = time() - ($keep_days * 24 * 60 * 60);

		$summary = array(
			'macaw_access' => 0,
			'macaw_activity' => 0,
			'macaw_cron' => 0,
			'macaw_error' => 0,
			'books' => 0,
			'old_files' => 0
		);

		if (is_dir($logs_dir)) {
			$files = scandir($logs_dir);
			foreach ($files as $file) {
				if ($file == '.' || $file == '..' || is_dir($logs_dir . '/' . $file)) {
					continue;
				}

				$file_path = $logs_dir . '/' . $file;
				$is_macaw = false;

				if (strpos($file, 'macaw_access') === 0) {
					$summary['macaw_access']++;
					$is_macaw = true;
				} elseif (strpos($file, 'macaw_activity') === 0) {
					$summary['macaw_activity']++;
					$is_macaw = true;
				} elseif (strpos($file, 'macaw_cron') === 0) {
					$summary['macaw_cron']++;
					$is_macaw = true;
				} elseif (strpos($file, 'macaw_error') === 0) {
					$summary['macaw_error']++;
					$is_macaw = true;
				}

				if ($is_macaw && filemtime($file_path) < $cutoff_time) {
					$summary['old_files']++;
				}
			}
		}

		// Count books logs (not included in retention policy)
		$books_dir = $logs_dir . '/books';
		if (is_dir($books_dir)) {
			$summary['books'] = count(scandir($books_dir)) - 2; // -2 for . and ..
		}

		return $summary;
	}

	/**
	 * Find directories for completed items that can be deleted
	 *
	 * @access private
	 * @param array $completed_books Array of completed book objects
	 * @return array Array of directory information for completed items
	 */
	function _find_unused_directories($completed_books) {
		$dirs = array();
		$data_dir = $this->cfg['data_directory'];

		foreach ($completed_books as $book) {
			$item_dir = $data_dir . '/' . $book->barcode;
			if (is_dir($item_dir)) {
				$size = $this->_get_directory_size($item_dir);
				$title = "(unknown)";
				if (property_exists($book, 'title')) {
					$title = $book->title;
				}
				$dirs[] = array(
					'barcode' => $book->barcode,
					'title' => $title,
					'path' => $item_dir,
					'size' => $size,
					'size_display' => $this->_format_bytes($size)
				);
			}
		}

		return $dirs;
	}

	/**
	 * Find Internet Archive export directories for completed items
	 *
	 * @access private
	 * @param string $ia_path Path to Internet Archive export directory
	 * @param array $completed_books Array of completed book objects
	 * @return array Array of directory information
	 */
	function _find_ia_export_directories($ia_path, $completed_books) {
		$dirs = array();

		if (!is_dir($ia_path)) {
			return $dirs;
		}

		$completed_barcodes = array();
		foreach ($completed_books as $book) {
			$completed_barcodes[$book->barcode] = $book;
		}

		$items = scandir($ia_path);
		foreach ($items as $item) {
			if ($item == '.' || $item == '..') {
				continue;
			}

			$item_path = $ia_path . '/' . $item;
			if (is_dir($item_path) && isset($completed_barcodes[$item])) {
				$size = $this->_get_directory_size($item_path);
				$dirs[] = array(
					'barcode' => $item,
					'title' => $completed_barcodes[$item]->title,
					'path' => $item_path,
					'size' => $size,
					'size_display' => $this->_format_bytes($size)
				);
			}
		}

		return $dirs;
	}

	/**
	 * Recursively get the size of a directory
	 *
	 * @access private
	 * @param string $path Directory path
	 * @return int Size in bytes
	 */
	function _get_directory_size($path) {
		$size = 0;

		if (!is_dir($path)) {
			return filesize($path);
		}

		$items = scandir($path);
		foreach ($items as $item) {
			if ($item == '.' || $item == '..') {
				continue;
			}

			$item_path = $path . '/' . $item;
			if (is_dir($item_path)) {
				$size += $this->_get_directory_size($item_path);
			} else {
				$size += filesize($item_path);
			}
		}

		return $size;
	}

	/**
	 * Format bytes to human readable format
	 *
	 * @access private
	 * @param int $bytes Size in bytes
	 * @return string Formatted size string
	 */
	function _format_bytes($bytes) {
		$units = array('B', 'KB', 'MB', 'GB', 'TB');

		$bytes = max($bytes, 0);
		$pow = floor(($bytes ? log($bytes) : 0) / log(1024));
		$pow = min($pow, count($units) - 1);
		$bytes /= (1 << (10 * $pow));

		return round($bytes, 2) . ' ' . $units[$pow];
	}

	/**
	 * Delete old log files
	 *
	 * AJAX: Deletes log files older than keep_log_days setting
	 * Note: Book log files are never deleted
	 *
	 * @access public
	 */
	function delete_old_logs() {
		$this->common->ajax_headers();

		if (!$this->user->has_permission('admin')) {
			echo json_encode(array('error' => 'Permission denied.'));
			$this->logging->log('error', 'debug', 'Permission Denied to access '.uri_string());
			return;
		}
		$deleted_count = $this->common->clean_logs();

		$this->logging->log('activity', 'info', 'Deleted '.$deleted_count.' old log files');
		echo json_encode(array('success' => true, 'deleted' => $deleted_count));
	}

	/**
	 * Delete selected directories
	 *
	 * AJAX: Deletes selected item directories
	 *
	 * @access public
	 */
	function delete_directories() {
		$this->common->ajax_headers();

		if (!$this->user->has_permission('admin')) {
			echo json_encode(array('error' => 'Permission denied.'));
			return;
		}

		$barcodes = isset($_POST['barcodes']) ? $_POST['barcodes'] : array();
		if (!is_array($barcodes)) {
			$barcodes = array($barcodes);
		}

		$this->load->helper('file');
		$deleted_count = 0;
		$data_dir = $this->cfg['data_directory'];

		foreach ($barcodes as $barcode) {
			$dir_path = $data_dir . '/' . $barcode;
			if (is_dir($dir_path)) {
				delete_files($dir_path, true, 1);
				$deleted_count++;
				$this->logging->log('access', 'info', 'Deleted directory for completed item: '.$barcode);
			}
		}

		echo json_encode(array('success' => true, 'deleted' => $deleted_count));
	}

	/**
	 * Delete Internet Archive export directories
	 *
	 * AJAX: Deletes selected Internet Archive export directories
	 *
	 * @access public
	 */
	function delete_ia_directories() {
		$this->common->ajax_headers();

		if (!$this->user->has_permission('admin')) {
			echo json_encode(array('error' => 'Permission denied.'));
			return;
		}

		$barcodes = isset($_POST['barcodes']) ? $_POST['barcodes'] : array();
		if (!is_array($barcodes)) {
			$barcodes = array($barcodes);
		}

		$this->load->helper('file');
		$deleted_count = 0;
		$ia_path = $this->cfg['data_directory'] . '/import_export/Internet_archive';

		foreach ($barcodes as $barcode) {
			$dir_path = $ia_path . '/' . $barcode;
			if (is_dir($dir_path)) {
				delete_files($dir_path, true);
				$deleted_count++;
				$this->logging->log('activity', 'info', 'Deleted Internet Archive export directory for: '.$barcode);
			}
		}

		echo json_encode(array('success' => true, 'deleted' => $deleted_count));
	}

	/**
	 * Test email settings
	 *
	 * AJAX: Sends a test email to the admin email address
	 *
	 * @access public
	 */
	function test_email() {
		$this->common->ajax_headers();
		$to = $this->cfg['admin_email'];
		$subject = 'Macaw Email Test';
		$message = 'This is a test email from Macaw Maintenance page. If you received this, email is configured correctly.';		
		if ($this->common->email_admin($message, $subject, false)) {
			$this->logging->log('activity', 'info', 'Test email sent to '.$to);
			echo json_encode(array('success' => true, 'message' => 'Test email sent to '.$to));
		} else {
			$this->logging->log('error', 'info', 'Failed to send test email:');
			echo json_encode(array('error' => 'Failed to send email. Check error logs for details.'));
		}


		// if (!$this->user->has_permission('admin')) {
		// 	echo json_encode(array('error' => 'Permission denied.'));
		// 	return;
		// }

		// $this->load->library('email');

		// $config = array(
		// 	'protocol' => 'smtp',
		// 	'smtp_host' => $this->cfg['email_smtp_host'],
		// 	'smtp_port' => $this->cfg['email_smtp_port'],
		// 	'smtp_user' => $this->cfg['email_smtp_user'],
		// 	'smtp_pass' => $this->cfg['email_smtp_pass'],
		// 	'mailtype' => 'html',
		// 	'charset' => 'utf-8'
		// );

		// $this->email->initialize($config);

		// $this->email->from($this->cfg['email_smtp_user'], 'Macaw');
		// $this->email->to($to);
		// $this->email->subject($subject);
		// $this->email->message($message);

		// if ($this->email->send()) {
		// 	$this->logging->log('activity', 'info', 'Test email sent to '.$to);
		// 	echo json_encode(array('success' => true, 'message' => 'Test email sent to '.$to));
		// } else {
		// 	$this->logging->log('error', 'info', 'Failed to send test email: '.$this->email->print_debugger());
		// 	echo json_encode(array('error' => 'Failed to send email. Check error logs for details.'));
		// }
	}

	/**
	 * List all user accounts
	 *
	 * Accessible only to the admin, this lists all of the user accounts in the
	 * system, along with a link to edit the user.
	 *
	 * @access public
	 * @param string [$username] The name of the user to edit.
	 * @since Version 1.0
	 */
	/* LOCAL ADMIN COMPLETED */
	function account() {
		$this->common->check_session();
		// Permission Checking
		if (!$this->user->has_permission('admin') && !$this->user->has_permission('local_admin')) {
			$this->session->set_userdata('errormessage', 'You do not have permission to access that page.');
			redirect($this->config->item('base_url').'main/listitems');
			$this->logging->log('error', 'debug', 'Permission Denied to access '.uri_string());
		}

		$this->load->view('admin/account_view');
	}

	/**
	 * Get a list of all accounts
	 *
	 * AJAX: Returns an array of all user accounts in the system
	 *
	 * @access public
	 * @param string [$username] The name of the user to edit.
	 * @since Version 1.0
	 */
	/* LOCAL ADMIN COMPLETED */
	function account_list() {
		// Make sure we are logged in and stuff
		if (!$this->common->check_session(true)) {
			return;
		}

		$this->common->ajax_headers();
		$org_id = 0;
		if ($this->user->has_permission('local_admin')) {
			$org_id = $this->user->org_id;
		}
		echo json_encode($this->user->get_list($org_id));
	}

	/**
	 * Edit a user's account
	 *
	 * AJAX: Allows the admin to edit an account, or allows a user to edit his or her
	 * own account. Also makes sure that the logged in user has permission to
	 * edit the acccount.
	 *
	 * @access public
	 * @param string [$username] The name of the user to edit.
	 * @since Version 1.0
	 */
	/* LOCAL ADMIN COMPLETED */
	function account_edit($username = '') {
		// Make sure we are logged in and stuff
		if (!$this->common->check_session(true)) {
			return;
		}

		// If we didn't get a username on the URL, we assume we are editing ourself.
		if (!$username) {
			$username = $this->session->userdata('username');
		}

		// Make sure we can edit the user in question
		if ($this->_can_edit_account($this->session->userdata('username'), $username)) {
			try {
				// Record whether or not we are an admin
				$is_local_admin = $this->user->has_permission('local_admin');
				$data['is_local_admin'] = $is_local_admin;

				$is_admin = $this->user->has_permission('admin');
				$data['is_admin'] = $is_admin;

				// Load the record for the user
				$this->user->load($username);

				// Get the data with which to fill the screen
				$datestring = "M d, Y h:i a";
				$data['new'] = false;
				$data['username'] = $username;
				$data['full_name'] = $this->user->full_name;
				$data['email'] = $this->user->email;
				$data['created'] = date($datestring, strtotime($this->user->created));
				$data['modified'] = date($datestring, strtotime($this->user->modified));
				$data['last_login'] = date($datestring, strtotime($this->user->last_login));
				$data['permissions'] = $this->user->get_permissions();
				$data['token'] = $this->session->userdata('li_token');

				if ($is_admin) {
					$data['locked_org_id'] = false;
					$data['organizations'] = $this->organization->get_list();

				} elseif ($is_local_admin) {
					$data['locked_org_id'] = true;
					$data['organizations'] = array();

				} else {
					$data['locked_org_id'] = true;
					$data['organizations'] = array();
				}

				$data['org_name'] = $this->user->org_name;
				$data['org_id'] = $this->user->org_id;
				
				// Display the page
				$content = $this->load->view('admin/account_edit_view', $data, true);

				echo json_encode(array('dialogContent' => $content));

			} catch (Exception $e) {
				// This handles anything strange that might come across while getting the user object.
				$this->common->ajax_headers();
	    	    echo json_encode(array('error' => $e->getMessage()));
			}
		} else {
			// if we can't edit the user, then we bounce back to their own edit page with a slap on the wrist.
			$this->common->ajax_headers();
			echo json_encode(array('error' => 'You do not have permission to edit that account.'));
		}
	}

	/**
	 * Add a new user account
	 *
	 * AJAX: Allows the admin to edit an account, or allows a user to edit his or her
	 * own account. Also makes sure that the logged in user has permission to
	 * edit the acccount.
	 *
	 * @access public
	 * @param string [$username] The name of the user to edit.
	 * @since Version 1.2
	 */
	/* LOCAL ADMIN COMPLETED */
	function account_add() {
		// Make sure we are logged in and stuff
		if (!$this->common->check_session(true)) {
			return;
		}

		$admin_user = new User;
		$admin_user->load($this->session->userdata('username'));

		// Record whether or not we are an admin
		$is_admin = $admin_user->has_permission('admin');
		$data['is_admin'] = $is_admin;

		$is_local_admin = $admin_user->has_permission('local_admin');
		$data['is_local_admin'] = $is_local_admin;

		// Fill the page
		$this->user->load();
		$data['new'] = true;
		$datestring = "M d, Y h:i a";
		$data['created'] = date($datestring, time());
		$data['permissions'] = $this->user->get_permissions();
		$data['token'] = $this->session->userdata('li_token');
		
		if ($is_admin) {
			$data['locked_org_id'] = false;
			$data['organizations'] = $this->organization->get_list();
			$data['org_name'] = '';
			$data['org_id'] = -1;

		} elseif ($is_local_admin) {
			$data['locked_org_id'] = true;
			$data['organizations'] = array();
			$data['org_name'] = $admin_user->org_name;
			$data['org_id'] = $admin_user->org_id;
		}

		// Display the page
		$content = $this->load->view('admin/account_edit_view', $data, true);
		echo json_encode(array('dialogContent' => $content));
	}

	/**
	 * Save changes to an account
	 *
	 * AJAX: Gets the list of files for this book and their status as to being
	 * scanned and processed. The data comes from the database, which is in
	 * turn populated by the cron job.
	 *
	 * @since Version 1.0
	 */
	/* LOCAL ADMIN COMPLETED */
	function account_save() {
		// Make sure we are logged in and stuff
		if (!$this->common->check_session(true)) {
			return;
		}

		$is_admin = $this->user->has_permission('admin');
		$is_local_admin = $this->user->has_permission('local_admin');

		if ($this->input->post('new')) { // WE ARE ADDING A NEW ACCOUNT
			// Only admins (or local admins) can add accounts
			if ($is_admin || $is_local_admin) {
				// Force the user object to re-initialize
				$this->user->load();

				// Set the user's data
				$this->user->full_name = $this->input->post('full_name');
				$this->user->email = $this->input->post('email');
				$this->user->org_id = $this->input->post('org_id');
				$this->user->password  = $this->input->post('password');

				try {
					// Add the user, with proper error handling
					$this->user->add($this->input->post('username'));

					// Reload the user, else the save permissions will fail.
					$this->user->load($this->input->post('username'));
					
					// Filter the permissions. Only full admins can set the admin flag. 
					$perms = array();
					if ($this->input->post('permissions')) {
						if (count($this->input->post('permissions')) > 0) {
							foreach ($this->input->post('permissions') as $perm) {
								if ($perm == 'admin') {
									if ($is_admin) {
										$perms[] = $perm;
									}
								} else {
									$perms[] = $perm;						
								}						
							}
							$this->user->set_permissions($perms);						
						}
					}
				} catch (Exception $e) {
					// This handles anything strange that might come across while getting the user object.
					$this->common->ajax_headers();
	    		    echo json_encode(array('error' => $e->getMessage()));
					$this->logging->log('error', 'debug', 'Inside account_save() (new): '.$e->getMessage());
					return;
				}

				// Send a nominal response back to the browser
				$this->common->ajax_headers();
				echo json_encode(array('message' => 'Account added!'));
				$this->logging->log('access', 'info', 'Added account: '.$this->input->post('username'));

			} else {
				$this->common->ajax_headers();
				$this->session->set_userdata('errormessage', 'You do not have permission to add an account');
				echo json_encode(array('redirect' => $this->config->item('base_url').'main/listitems'));
				$this->logging->log('error', 'debug', 'Permission denied to add a new account.');
			}

		} else { // WE ARE EDITING AN EXISTING ACCOUNT
			// Get the data from the POST and make it into something useful
			// Make sure we are being good little users.
			$username = $this->input->post('username');
			if ($this->_can_edit_account($this->session->userdata('username'), $username)) {
				try {
					// Load the user based on the username passed
					$this->user->load($username);

					// Update the data
					$this->user->full_name = $this->input->post('full_name');
					$this->user->email = $this->input->post('email');
					$this->user->org_id = $this->input->post('org_id');
					$this->user->password = $this->input->post('password');
					$this->user->update();

					// Only admins (or local admins) can save permissions, even if they are is removing their own permission.
					if ($is_admin || $is_local_admin) {
						// Filter the permissions. Only full admins can set the admin flag. 
						$perms = array();
						foreach ($this->input->post('permissions') as $perm) {
							if ($perm == 'admin') {
								if ($is_admin) {
									$perms[] = $perm;
								}
							} else {
								$perms[] = $perm;						
							}						
						}
	
						$this->user->set_permissions($perms);
					}

					// Send a nominal response back to the browser
					$this->common->ajax_headers();
					echo json_encode(array('message' => 'Changes saved!'));
					$this->logging->log('access', 'info', 'Upadted user: '.$this->input->post('username'));

					// Update the session, but only if we are editing ourself.
					if ($username == $this->session->userdata('username')) {
						$this->session->set_userdata('full_name', $this->input->post('full_name'));
						$this->session->set_userdata('email', $this->input->post('email'));
					}

				} catch (Exception $e) {
					// This handles anything strange that might come across while getting the user object.
					$this->common->ajax_headers();
	    		    echo json_encode(array('error' => $e->getMessage()));
					$this->logging->log('error', 'debug', 'Inside account_save() (edit): '.$e->getMessage());
				}
			} else {
				// if we can't edit the user, then we bounce back to their own edit page with a slap on the wrist.
				$this->common->ajax_headers();
				$this->session->set_userdata('errormessage', 'You do not have permission to edit the account "'.$username.'". Here is the page to edit your own account instead.');
				echo json_encode(array('redirect' => $this->config->item('base_url').'admin/account_edit/'));
				$this->logging->log('error', 'debug', 'Permission denied to edit the account "'.$username);
			}
		}
	}

	/**
	 * Delete an account
	 *
	 * Only admins can delete accounts. We clear the permissions table and the
	 * accounts table. That's it.
	 *
	 * @since Version 1.2
	 */
	/* LOCAL ADMIN COMPLETED */
	function account_delete($username = null) {
		$target_user = new User;
		$target_user->load($username);

		$this->user->load($this->session->userdata('username'));
		if (!isset($username)) {
			$this->common->ajax_headers();
			echo json_encode(array('error' => 'You did not supply the name of an account to delete.'));
			$this->logging->log('error', 'debug', 'No account name supplied for deletion.');

		} else {
			if ($this->user->has_permission('admin') || 
					($this->user->has_permission('local_admin') && 
					 $this->user->org_id == $target_user->org_id && 
					 $username != 'admin' && 
					 $username != $this->session->userdata('username'))
				) {
				$this->db->where('username', $username);
				$this->db->delete('permission');

				$this->db->where('username', $username);
				$this->db->delete('account');
				
				$this->common->ajax_headers();
				echo json_encode(array('message' => 'Account deleted.'));
				$this->logging->log('access', 'info', 'Deleted account: '.$username);

			} else {
				$this->common->ajax_headers();
				echo json_encode(array('error' => 'Permission denied.'));
				$this->logging->log('error', 'debug', 'Permission denied to delete the account "'.$username);
			}
		}
	}

	/**
	 * Do we have permission to edit a user
	 *
	 * Admin can edit anyone and anyone can edit themselves. Otherwise, fuggedaboutit!
	 *
	 * @since Version 1.1
	 */
	/* LOCAL ADMIN COMPLETED */
	function _can_edit_account($user, $target) {
		$target_user = new User;
		$target_user->load($target);

		$this->user->load($this->session->userdata('username'));

		if ($user == 'admin' || 
		    $user == $target || 
		    $this->user->has_permission('admin') || 
		    ($this->user->has_permission('local_admin') && 
		     $this->user->org_id == $target_user->org_id && 
		     $target != 'admin'
		    )
		   ) {
			return true;
		}
		return false;
	}
	
	function view_config($all = '') {
		$this->common->check_session();
		// Permission Checking
		if (!$this->user->has_permission('admin')) {
			$this->session->set_userdata('errormessage', 'You do not have permission to access that page.');
			redirect($this->config->item('base_url').'main/listitems');
			$this->logging->log('error', 'debug', 'Permission Denied to access '.uri_string());
		}

		$data = [];
		$data['config_params'] = [];

		foreach (array_keys($this->cfg) as $k) {
			$val = '';
			if (preg_match('/(pass|api_key|secret)/', $k)) {
				$val = '<em>[REDACTED]</em>';
			} else {
				$val = $this->cfg[$k];
			}
			if (is_array($this->cfg[$k])) {
				$data['config_params'][] = array(
					'paramater' => $k, 
					'value' => preg_replace('/\t/', '&nbsp;&nbsp;', print_r($val, true))
				);
			} else {
				$data['config_params'][] = array(
					'paramater' => $k, 
					'value' => $val
				);
			}
		}
		$data['all'] = ($all == 'all');
		$data['phpinfo'] = $this->_phpinfo_array();
		$data['phpvars'] = $this->_php_vars();
		unset($data['phpinfo']['General']['Configure Command']);
		unset($data['phpinfo']['PHP Variables']);
		$this->load->view('admin/config_view', $data);
	}

	function _php_vars() {
		$vars = array();
		$vars['PHP_VERSION'] = PHP_VERSION;
		$vars['PHP_OS'] = PHP_OS;
		$vars['PHP_OS_FAMILY'] = PHP_OS_FAMILY;
		$vars['PHP_BINARY'] = PHP_BINARY;
		$vars['PHP_PREFIX'] = PHP_PREFIX;
		$vars['PHP_BINDIR'] = PHP_BINDIR;
		$vars['PHP_LIBDIR'] = PHP_LIBDIR;
		$vars['FOUND_PHP_EXE'] = $this->common->get_php_exe();
		return $vars;
	}

	function _phpinfo_array() {
		ob_start();
		phpinfo();
		$info_arr = array();
		$info_lines = explode("\n", strip_tags(ob_get_clean(), "<tr><td><h2>"));
		$cat = "General";
		foreach($info_lines as $line) {
			// new cat?
			preg_match("~<h2>(.*)</h2>~", $line, $title) ? $cat = $title[1] : null;
			if(preg_match("~<tr><td[^>]+>([^<]*)</td><td[^>]+>([^<]*)</td></tr>~", $line, $val)) {
				$info_arr[trim($cat)][trim($val[1])] = $val[2];
			} elseif(preg_match("~<tr><td[^>]+>([^<]*)</td><td[^>]+>([^<]*)</td><td[^>]+>([^<]*)</td></tr>~", $line, $val)) {
				$info_arr[trim($cat)][trim($val[1])] = array("local" => $val[2], "master" => $val[3]);
			}
		}
		return $info_arr;
	}

	/**
	 * List all organizations
	 *
	 * @since Version 1.7
	 */
	/* LOCAL ADMIN COMPLETED */
	function organization() {
		$this->common->check_session();
		// Permission Checking
		if (!$this->user->has_permission('admin')) {
			$this->session->set_userdata('errormessage', 'You do not have permission to access that page.');
			redirect($this->config->item('base_url').'main/listitems');
			$this->logging->log('error', 'debug', 'Permission Denied to access '.uri_string());
		}

		$this->load->view('admin/organization_view');
	}

	/**
	 * Get a list of all organizations
	 *
	 *
	 * @since Version 1.7
	 */
	/* LOCAL ADMIN COMPLETED */
	function organization_list() {
		// Make sure we are logged in and stuff
		if (!$this->common->check_session(true)) {
			return;
		}
		if (!$this->user->has_permission('admin')) {
			$this->common->ajax_headers();
			echo json_encode(array('error' => 'Permission denied.'));
			return;
		}

		$this->common->ajax_headers();
		echo json_encode($this->organization->get_list());
	}

	/**
	 * Edit an organization
	 *
	 *
	 * @param string [$id] The name of the organization to edit.
	 * @since Version 1.7
	 */
	/* LOCAL ADMIN COMPLETED */
	function organization_edit($id = 0) {
		// Make sure we are logged in and stuff
		if (!$this->common->check_session(true)) {
			return;
		}

		if (!$this->user->has_permission('admin')) {
			$this->common->ajax_headers();
			echo json_encode(array('error' => 'Permission denied.'));
			return;
		}

		// If we didn't get an ID on the URL, we assume we are editing ourself.
		if (!$id) {
			echo json_encode(array('error' => 'Please select an organization to edit.'));
			return;
		}

		// Make sure we can edit the Organization in question
		try {
			// Load the record for the organization
			$this->organization->load($id);

			// Get the data with which to fill the screen
			$datestring = "M d, Y h:i a";
			$data['new'] = false;
			$data['id'] = $this->organization->id;
			$data['name'] = $this->organization->name;
			$data['person'] = $this->organization->person;
			$data['email'] = $this->organization->email;
			$data['phone'] = $this->organization->phone;
			$data['address'] = $this->organization->address;
			$data['address2'] = $this->organization->address2;
			$data['city'] = $this->organization->city;
			$data['state'] = $this->organization->state;
			$data['postal'] = $this->organization->postal;
			$data['country'] = $this->organization->country;
			$data['created'] = $this->organization->created;
			$data['modified'] = $this->organization->modified;
			$data['show_api_keys'] = false;
			if ($this->db->table_exists('custom_internet_archive_keys')) {
				$data['show_api_keys'] = true;
				$data['api_key'] = $this->organization->ia_api_key;
				$data['secret_key'] = $this->organization->ia_secret_key;
			}
			$data['token'] = $this->session->userdata('li_token');

			// Display the page
			$content = $this->load->view('admin/organization_edit_view', $data, true);

			echo json_encode(array('dialogContent' => $content));

		} catch (Exception $e) {
			// This handles anything strange that might come across while getting the organization object.
			$this->common->ajax_headers();
			echo json_encode(array('error' => $e->getMessage()));
		}
	}

	/**
	 * Add a new organization
	 *
	 * AJAX
	 *
	 * @since Version 1.7
	 */
	/* LOCAL ADMIN COMPLETED */
	function organization_add() {
		// Make sure we are logged in and stuff
		if (!$this->common->check_session(true)) {
			return;
		}
		if (!$this->user->has_permission('admin')) {
			$this->common->ajax_headers();
			echo json_encode(array('error' => 'Permission denied.'));
			return;
		}

		$this->organization->load();
		$data['new'] = true;
		$data['name'] = '';
		$data['person'] = '';
		$data['email'] = '';
		$data['phone'] = '';
		$data['address'] = '';
		$data['address2'] = '';
		$data['city'] = '';
		$data['state'] = '';
		$data['postal'] = '';
		$data['country'] = '';
		$data['created'] = '';
		$data['modified'] = '';
		$data['show_api_keys'] = false;
		if ($this->db->table_exists('custom_internet_archive_keys')) {
			$data['show_api_keys'] = true;
			$data['api_key'] = '';
			$data['secret_key'] = '';
		}
		$data['id'] = 0;
		$data['token'] = $this->session->userdata('li_token');

		// Display the page
		$content = $this->load->view('admin/organization_edit_view', $data, true);
		echo json_encode(array('dialogContent' => $content));
	}

	/**
	 * Save changes to an organization
	 *
	 * AJAX: Gets the list of files for this book and their status as to being
	 * scanned and processed. The data comes from the database, which is in
	 * turn populated by the cron job.
	 *
	 * @since Version 1.7
	 */
	/* LOCAL ADMIN COMPLETED */
	function organization_save() {
		// Make sure we are logged in and stuff
		if (!$this->common->check_session(true)) {
			return;
		}
		if (!$this->user->has_permission('admin')) {
			$this->common->ajax_headers();
			echo json_encode(array('error' => 'Permission denied.'));
			$this->logging->log('error', 'debug', 'Permission denied to save the contributor "'.$this->input->post('name'));
			return;
		}


		if ($this->input->post('new')) { // WE ARE ADDING A NEW ORG
			// Force the organization object to re-initialize
			$this->organization->load();

			// Set the organization's data
			$this->organization->name = $this->input->post('name');
			$this->organization->person = $this->input->post('person');
			$this->organization->email = $this->input->post('email');
			$this->organization->phone = $this->input->post('phone');
			$this->organization->address = $this->input->post('address');
			$this->organization->address2 = $this->input->post('address2');
			$this->organization->city = $this->input->post('city');
			$this->organization->state = $this->input->post('state');
			$this->organization->postal = $this->input->post('postal');
			$this->organization->country = $this->input->post('country');
			if ($this->db->table_exists('custom_internet_archive_keys')) {
				$this->organization->ia_api_key = $this->input->post('api_key');
				$this->organization->ia_secret_key = $this->input->post('secret_key');
			}
		
			try {
				// Add the organization, with proper error handling
				$this->organization->add();
			} catch (Exception $e) {
				// This handles anything strange that might come across while getting the organization object.
				$this->common->ajax_headers();
				echo json_encode(array('error' => $e->getMessage()));
				$this->logging->log('error', 'debug', 'Inside organization_save() (new): '.$e->getMessage());
				return;
			}

			// Send a nominal response back to the browser
			$this->common->ajax_headers();
			echo json_encode(array('message' => 'Contributor added!'));
			$this->logging->log('access', 'info', 'Added contributor '.$this->input->post('name'));
		} else { // WE ARE EDITING AN EXISTING ORG
			// Get the data from the POST and make it into something useful
			// Load the organization based on the id passed
			$this->organization->load($this->input->post('id'));

			// Update the data
			$this->organization->name = $this->input->post('name');
			$this->organization->person = $this->input->post('person');
			$this->organization->email = $this->input->post('email');
			$this->organization->phone = $this->input->post('phone');
			$this->organization->address = $this->input->post('address');
			$this->organization->address2 = $this->input->post('address2');
			$this->organization->city = $this->input->post('city');
			$this->organization->state = $this->input->post('state');
			$this->organization->postal = $this->input->post('postal');
			$this->organization->country = $this->input->post('country');
			if ($this->db->table_exists('custom_internet_archive_keys')) {
				$this->organization->ia_api_key = $this->input->post('api_key');
				$this->organization->ia_secret_key = $this->input->post('secret_key');
			}

			try {
				// Add the organization, with proper error handling
				$this->organization->update();
			} catch (Exception $e) {
				// This handles anything strange that might come across while getting the organization object.
				$this->common->ajax_headers();
				echo json_encode(array('error' => $e->getMessage()));
				$this->logging->log('error', 'debug', 'Inside organization_save() (update): '.$e->getMessage());
				return;
			}

			// Send a nominal response back to the browser
			$this->common->ajax_headers();
			echo json_encode(array('message' => 'Changes saved!'));
			$this->logging->log('access', 'info', 'Upadted Contributor: '.$this->input->post('name'). ' (id '.$this->input->post('id').')');
		}
	}

	/**
	 * Delete an organization
	 *
	 * AJAX: Only admins can delete organizations. We clear the permissions table and the
	 * organizations table. That's it.
	 *
	 * @since Version 1.2
	 */
	/* LOCAL ADMIN COMPLETED */
	function organization_delete($id) {
		if (!$this->user->has_permission('admin')) {
			$this->common->ajax_headers();
			echo json_encode(array('error' => 'Permission denied.'));
			$this->logging->log('error', 'debug', 'Permission denied to delete the contributor "'.$id);
			return;
		}
		if (!isset($id)) {
			$this->common->ajax_headers();
			echo json_encode(array('error' => 'You did not supply the ID of an contributor to delete.'));
			$this->logging->log('error', 'debug', 'No contributor ID supplied for deletion.');		
		}

		$this->db->where('id', $id);
		$this->db->delete('organization');

		if ($this->db->table_exists('custom_internet_archive_keys')) {
			$this->db->where('org_id', $id);
			$this->db->delete('custom_internet_archive_keys');
		}
		
		$this->common->ajax_headers();
		echo json_encode(array('message' => 'Contributor deleted.'));
		$this->logging->log('access', 'info', 'Deleted Contributor '.$id);
	}

	/**
	 * Monthly repoert
	 *
	 * Only admins and local admins can see this grouping of which organization contributed how many items and pages
	 * per month. Only shows completed items. Local admins will see only their stuff.
	 *
	 * @since Version 2.2
	 */
	function monthly_report() {
		if (!$this->user->has_permission('admin') && !$this->user->has_permission('local_admin')) {
			$data['results'] = array();
			$this->session->set_userdata('errormessage', 'You do not have permission to access that page.');
			$content = $this->load->view('admin/monthly_report', $data);
			return;
		}

		$sql = 'SELECT count(*) as items, max(o.name) as contributor, o.id, concat(monthname(date_completed), \' \', year(date_completed)) as month, sum(pages_found) as pages '.
		       'FROM (select * from item where date_completed <> \'0000-00-00 00:00:00\') i '.
		       'INNER JOIN organization o ON o.id = i.org_id '.
		       'GROUP BY EXTRACT(YEAR_MONTH FROM date_completed), org_id '.
		       'ORDER BY EXTRACT(YEAR_MONTH FROM date_completed) DESC, o.name';
				
		if ($this->user->has_permission('local_admin')) {
			$this->user->load($this->session->userdata('username'));
			$org_id = $this->user->org_id;

			$sql = 'SELECT count(*) as items, max(o.name) as contributor, o.id, concat(monthname(date_completed), \' \', year(date_completed)) as month, sum(pages_found) as pages '.
			       'FROM (select * from item where date_completed <> \'0000-00-00 00:00:00\' and org_id = '.$org_id.') i '.
			       'INNER JOIN organization o ON o.id = i.org_id '.
			       'GROUP BY EXTRACT(YEAR_MONTH FROM date_completed), org_id '.
			       'ORDER BY EXTRACT(YEAR_MONTH FROM date_completed) DESC, o.name';
		}

		$query = $this->db->query($sql);
		$data['results'] = array();
		foreach ($query->result() as $row) {
			$data['results'][] = array(
				'month' => $row->month,
				'contributor' => $row->contributor,
				'org_id' => $row->id,
				'items' => $row->items,
				'pages' => $row->pages
			);
		}
		$data['local_admin'] = ($this->user->has_permission('local_admin') ? true : false);
		$content = $this->load->view('admin/monthly_report', $data);
	}

	function stalled_exports(){
		$content = $this->load->view('admin/stalled_exports');
	}

}
