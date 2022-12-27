<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Scan Controller
 *
 * MACAW Metadata Collection and Workflow System
 *
 * Monitors the scan progress, shows the reorder/metadata edit page, AJAX
 * to/from the server to save metadata. Also contains functionality for
 * initialization, prepping the scanning server.
 *
 **/

class Scan extends Controller {

	var $cfg;

	/**
	 * Function: Constructor
	 */
	function __construct() {
		parent::__construct();
		$this->cfg = $this->config->item('macaw');
	}


	/**
	 * Display the main scanning page
	 *
	 * The main scanning page is really the /main/ page, so we simply redirect
	 * to that page. There is no index for the scanning page.
	 *
	 * @since Version 1.0
	 */
	function index() {
		redirect($this->config->item('base_url').'main');
	}

	/**
	 * Display the scanning review progress page
	 *
	 * Shows the main scanning monitor page. Determine which scanning server
	 * the user is on and take appropriate action. If the server is identified,
	 * we make sure we can connect to it (via the _test_server() function).
	 * If not identified, guess or ask the user to tell us which server he is
	 * on. Present an error if we can't connect. Later, we will add the
	 * ability to set up a new scanning server.
	 *
	 * @since Version 1.0
	 */
	function monitor() {
		$this->common->check_session();
		// Permission Checking
		if (!$this->user->has_permission('scan')) {
			$this->session->set_userdata('errormessage', 'You do not have permission to access that page.');
			redirect($this->config->item('base_url').'main');
			$this->logging->log('error', 'debug', 'Permission Denied to access '.uri_string());
			return;
		}

		$barcode = $this->session->userdata('barcode');
		// Get our book
		$this->book->load($barcode);
		$this->common->check_missing_metadata($this->book);

		// We found something!
		$data['item_title'] = $this->session->userdata('title');
		$data['ip_address'] = $_SERVER['REMOTE_ADDR'];
		$data['hostname'] = $this->common->_get_host($_SERVER['REMOTE_ADDR']);
		$data['incoming_path'] = $this->cfg['incoming_directory'].'/'.$barcode;
		if ($this->cfg['incoming_directory_remote'] == '') {
			$data['remote_path'] = $this->cfg['incoming_directory'].'/'.$barcode;
		} else {
			$data['remote_path'] = $this->cfg['incoming_directory_remote'].'/'.$barcode;
		}
		$status = $this->book->status;
		if ($status == 'new' || $status == 'scanning') {
			$data['book_has_missing_pages'] = false;
		} else {
			$data['book_has_missing_pages'] = true;		
		}

		// The path can be blank, so we need to handle things properly.
		if ($this->cfg['incoming_directory'] && !file_exists($data['incoming_path'])) {
			// If the folder for the new pages is not there, we add it.
			// Assume that we've already checked for writability in this folder. (which we have, in the Common library)
			mkdir($data['incoming_path']);
			$this->logging->log('access', 'info', 'Created incoming directory: '.$data['incoming_path']);
		}
		$this->logging->log('access', 'info', 'Scanning monitor loaded for '.$barcode.'.');

		$this->load->view('scan/monitor_view', $data);
	}

	/**
	 * Start the import of pages
	 *
	 * AJAX: Called from the "Start Import" button on the scanning monitor page
	 * this simply sets the status of the book to "scanning". The cron job will
	 * actually import the pages and it looks to the status of the book to decide
	 * if it can actually process the pages for the book.
	 *
	 * @since Version 1.0
	 */
	function start_import() {
		if (!$this->user->has_permission('scan')) {
			$this->common->ajax_headers();
			echo json_encode(array('errormessage' => 'You do not have permission to access that page.'));
			$this->logging->log('error', 'debug', 'Permission Denied to access '.uri_string());
			return;
		}

		try {
			$this->book->load($this->session->userdata('barcode'));
			// Reset the counters because it's a good idea. Do this BEFORE we
			// allow the import code to do it's business.
			$this->book->pages_found = 0;
			$this->book->pages_scanned = 0;
			$this->book->scan_time = 0;
			$this->book->update();

			// Set the status to tell the import code it's OK to continue
			// We've moved this into the "cron import_pages" routine (or into the book model itself. It doesn't belong here.)
			// $this->book->set_status('scanning');

			// Try to identify the PHP executable on this system
			$php_exe = PHP_BINDIR.'/php5';		
			if (!file_exists($php_exe)) {
				$php_exe = PHP_BINDIR.'/php';
			}
			
			if (!file_exists($php_exe)) {
				echo json_encode(array('error' => 'Could not find php executable (php or php5) in '.PHP_BINDIR.'.'));
				$this->logging->log('error', 'debug', 'Could not find php executable (php or php5) in '.PHP_BINDIR.'.');
				return;
			}

			$fname = $this->logging->log('cron', 'info', 'Cron job "import_pages" initiated during import pages.');
	
			$this->common->ajax_headers();
			echo json_encode(array('message' => ''));

			// Now we can spawn the cron process.
			system('MACAW_OVERRIDE=1 "'.$php_exe.'" "'.$this->cfg['base_directory'].'/index.php" cron import_pages \''.$this->book->barcode.'\' > /dev/null 2> /dev/null < /dev/null &');
			
		} catch (Exception $e) {
			$this->common->ajax_headers();
			echo json_encode(array('errormessage' => $e->getMessage()));
		}

	}

	/**
	 * Get the progress of the scanning (or initial ingest of scanned pages)
	 *
	 * AJAX: Gets the list of files for this book and their status as to being
	 * scanned and processed. The data comes from the database, which is in
	 * turn populated by the cron job. This also includes information about
	 * how many pages were found and how many are remaining to be imported.
	 *
	 * @since Version 1.0
	 */
	function progress() {
		if (!$this->common->check_session(true)) {
			return;
		}

		$this->common->ajax_headers();
		$bc = $this->session->userdata('barcode');
		$this->book->load($bc);

		// Strip out anything that's Processed. We want them to disappear from
		// the list when they are done.
		$raw_pages = $this->book->get_pages('filebase', 'asc');
		$pages = array();
		$c = 0;
		foreach ($raw_pages as $p) {
			if ($p->status != 'Processed') {
				array_push($pages, $p);
				$c++;
				if ($c == 15) {
					break;
				}
			}
		}

		// If we got no pages, let's see what's on disk.
		if (count($pages) == 0) {
			$incoming = $this->cfg['incoming_directory'];
			$files = get_filenames($incoming.'/'.$bc);
			sort($files);
			$pages = array();
			foreach ($files as $f) {
				$info = get_file_info($incoming.'/'.$bc.'/'.$f, 'size');
				$pages[] = array(
					'filebase' => preg_replace('/^(.+)\.(.*?)$/', "$1", $f),
					'size' => $info['size'],
					'status' => 'New',
				);
				if (count($pages) >= 20) {
					break;
				}
			} // foreach ($files as $f)
		}
		// Send out results
		echo json_encode(array(
			'Result' => $pages,
			'Pages_Found' => ($this->book->pages_found ? $this->book->pages_found : 0) ,
			'Pages_Imported' => ($this->book->pages_scanned ? $this->book->pages_scanned : 0),
			'Time_Start' => $this->book->scan_time,
			'Time_Now' => time(),
		));
	}

	/**
	 * Finish scanning a book
	 *
	 * Simply mark the current book as finished scanning and redirect
	 * to the review page.
	 *
	 * @since Version 1.0
	 */
	function end_scan() {
		$this->common->check_session();
		// Permission Checking
		if (!$this->user->has_permission('scan')) {
			$this->session->set_userdata('errormessage', 'You do not have permission to access that page.');
			redirect($this->config->item('base_url').'main');
			$this->logging->log('error', 'debug', 'Permission Denied to access '.uri_string());
			return;
		}

		try {
			// Get our book
			$this->book->load($this->session->userdata('barcode'));
			$this->book->set_status('scanning');
			$this->book->set_status('scanned');
			redirect($this->config->item('base_url').'scan/review');
			$this->logging->log('access', 'info', 'Scanning completed for '.$this->session->userdata('barcode').'.');

		} catch (Exception $e) {
			// Set the error and redirect to the main page
			$this->session->set_userdata('errormessage', $e->getMessage());
			$this->logging->log('error', 'debug', 'Error in end_scan()'. $e->getMessage());
			redirect($this->config->item('base_url').'main');
		} // try-catch
	}

	/**
	 * Skip scanning a book
	 *
	 * Somtimes we don't want to scan at all. Usually when we've started scanning, leave the page and then come back.
	 *
	 * @since Version 1.5
	 */
	function skip_scan() {
		$this->common->check_session();
		// Permission Checking
		if (!$this->user->has_permission('scan')) {
			$this->session->set_userdata('errormessage', 'You do not have permission to access that page.');
			redirect($this->config->item('base_url').'main');
			$this->logging->log('error', 'debug', 'Permission Denied to access '.uri_string());
			return;
		}

		try {
			// Get our book
			$this->book->load($this->session->userdata('barcode'));

			$this->book->set_status('scanning');
			$this->book->set_status('scanned');
			redirect($this->config->item('base_url').'scan/review');
			$this->logging->log('access', 'info', 'Scanning completed for '.$this->session->userdata('barcode').'.');

		} catch (Exception $e) {
			// Set the error and redirect to the main page
			$this->session->set_userdata('errormessage', $e->getMessage());
			redirect($this->config->item('base_url').'main');
		} // try-catch
	}


	/**
	 * Finish scanning missing pages
	 *
	 * Simply mark the current book as finished scanning and redirect
	 * to the review page.
	 *
	 * @since Version 1.0
	 */
	function end_missing_scan() {
		$this->common->check_session();
		// Permission Checking
		if (!$this->user->has_permission('scan')) {
			$this->session->set_userdata('errormessage', 'You do not have permission to access that page..');
			redirect($this->config->item('base_url').'main');
			$this->logging->log('error', 'debug', 'Permission Denied to access '.uri_string());
			return;
		}

		try {
			// Get our book
			$this->book->load($this->session->userdata('barcode'));

			$this->book->set_status('scanned');
			redirect($this->config->item('base_url').'scan/review');
			$this->logging->log('access', 'info', 'Scanning completed for '.$this->session->userdata('barcode').'.');

		} catch (Exception $e) {
			// Set the error and redirect to the main page
			$this->session->set_userdata('errormessage', $e->getMessage());
			$this->logging->log('error', 'debug', 'Error in end_missing_scan()'. $e->getMessage());
			redirect($this->config->item('base_url').'main');
		} // try-catch
	}

	/**
	 * Show the review page
	 *
	 * Deceptively small, this is where the all the action happens. And all of it
	 * happens in javascript on the page as well as through a few AJAX/JSON calls
	 * to the server. This just shows the page and sets some critical pieces
	 * of data that are needed on the page. The rest happens dynamically.
	 *
	 * @since Version 1.0
	 */
	function review() {
		$this->common->check_session();
		// Permission Checking
		if (!$this->user->has_permission('scan')) {
			$this->session->set_userdata('errormessage', 'You do not have permission to access that page.');
			redirect($this->config->item('base_url').'main/listitems');
			$this->logging->log('error', 'debug', 'Permission Denied to access '.uri_string());
			return;
		}

		try {
			// Get our book
			$this->book->load($this->session->userdata('barcode'));

			// This book is in the QA queue
			if ($this->book->status == 'qa-ready' || $this->book->status == 'qa-active') {
				// Only those authorized can edit QA items
				if ($this->user->has_permission('qa') || $this->user->has_permission('local_admin') || $this->user->has_permission('admin')) {
					$this->book->set_status('qa-active');
				} else {
					$this->session->set_userdata('errormessage', 'You do not have permission to edit items that are in QA.');
					redirect($this->config->item('base_url').'main/listitems');
					return;
				}
			} elseif (!$this->book->needs_qa && ($this->book->status == 'qa-ready' || $this->book->status == 'qa-active')) {
				$this->book->set_status('qa-active');
				$this->book->set_status('reviewed');
				$this->book->set_status('reviewing');
			} elseif ($this->user->has_permission('qa_required') && $this->book->status == 'reviewed') {
				$this->session->set_userdata('errormessage', 'You do not have permission to edit an item that is ready for export.');
				redirect($this->config->item('base_url').'main/listitems');
				return;
			} else {
				if ($this->book->status == 'reviewed') {
					$this->session->set_userdata('warning', 'This item <em>was</em> ready to be exported. Be sure to click <strong>Review Complete</strong> when you are done.');
				}
				$this->book->set_status('reviewing');
			}
			$this->common->check_missing_metadata($this->book);
			$data = array();
			$data['base_directory'] = $this->cfg['base_directory'];
			$data['metadata_modules'] = $this->cfg['metadata_modules'];
			$data['item_title'] = $this->session->userdata('title');
			$this->load->view('scan/review_view', $data);
			$this->logging->log('access', 'info', 'Scanning review begins for '.$this->book->barcode);
			$this->logging->log('book', 'info', 'Scanning review begins.', $this->book->barcode);

		} catch (Exception $e) {
			// Set the error and redirect to the main page
			$this->session->set_userdata('errormessage', $e->getMessage());
			$this->logging->log('error', 'debug', 'Error in review() '. $e->getMessage());
			redirect($this->config->item('base_url').'main/listitems');
		} // try-catch
	}


	/**
	 * Get the thumbnails for a book
	 *
	 * AJAX: Gathers an array of objects for the thumbnails and returns JSON.
	 *
	 * @since Version 1.0
	 */
	function get_thumbnails($filter = null) {

		if (!$this->common->check_session(true)) {
			return;
		}

		$this->common->ajax_headers();
		try {
			$this->book->load($this->session->userdata('barcode'));
		} catch (Exception $e) {
			echo json_encode(array(
				'pages' => array(),
				'page_types' => $this->cfg['page_types'],
				'piece_types' => $this->cfg['piece_types'],
			));
			return;
		} // try-catch

		// Get the pages based on the $filter passed in from outside
		$pages = null;

		if ($filter == 'missing') {
			$pages = $this->book->get_pages('', 'asc', 0, true);
		} elseif ($filter == 'non_missing') {
			$pages = $this->book->get_pages('', 'asc', 0, false, true);
		} else {
			$pages = $this->book->get_pages();
		}

		echo json_encode(array(
			'pages' => $pages
		));
	}

	/**
	 * Save metadata and page order
	 *
	 * AJAX: alled from the review page, this saves all of the metadata for all
	 * of the pages in a book. While setting each page's metadata, we also set
	 * the page order for the page. All the stuff that this does happens in a
	 * transaction so we don't lose everything if something goes wrong. Keep in
	 * mind that all of the metadata is wiped out of the database before we save
	 * and so we need to have this transaction in place.
	 *
	 * @since Version 1.0
	 */
	function save_pages() {
		if (!$this->common->check_session(true)) {
			return;
		}
		$this->common->ajax_headers();
		// Get our book
        $this->book->load($this->session->userdata('barcode'));

		// Embedded ampersands in the data cause trouble.
		$data = preg_replace('/\&/i', '&amp;', $this->input->post('data'));

		// Get the data from the page
		$data = json_decode($data, true);

		// Make sure we got stuff for the current book (??)
		if ($data['item_id'] != $this->book->id) {
			show_error('The ID of the book ('.$data['item_id'].') does not match that of your session ('.$this->book->id.'). Please go back and re-scan the barcode.');
			return;
		}
		// Delete the metadata for the book
		$this->db->trans_start();
		//$this->book->delete_page_metadata(); //No longer deleting all the metadata, only on a page by page basis

		$sequence_count = 1;
		// Cycle through the pages in the book
		foreach ($data['pages'] as $page) {
			if (isset($page['page_id']) && isset($page['metadata'])) {
				 // We only delete the metadta for pages that have content
				  $this->book->delete_page_metadata($page['page_id']);
			}			
			if ($page['deleted']) {
				$this->book->delete_page($page['page_id']);
			} else {
				// Assume we have an array of name/value pairs.
				foreach (array_keys($page['metadata']) as $key) {
					$dt = $page['metadata'];
	
					// If an value is an array
					if (is_array($dt[$key])) {
						$c = 1;
						//  cycle through that array of values
						foreach ($dt[$key] as $val) {
							// Save the values and increment the counter as we go
							$this->book->set_page_metadata($page['page_id'], $key, $val, $c);
							$c++;
						}
					} else {
						// Otherwise, just save with a counter of 1.
						$this->book->set_page_metadata($page['page_id'], $key, $dt[$key], 1);
					}
				}
			
				// Update sequence Numbers
				$this->book->set_page_sequence($page['page_id'], $sequence_count++);
				if (!$data['inserted_missing']) {
					$this->book->set_missing_flag($page['page_id'], false);
				}
				if ($page['inserted']) {
					$this->logging->log('book', 'info', 'Page inserted before sequence Number '.$sequence_count.'.', $this->session->userdata('barcode'));
				}
			} // if ($page['deleted']) else clause

		} // foreach ($data->pages as $p)

		$this->db->trans_complete();

		echo json_encode(array('message' => 'Changes saved!'));
		$this->logging->log('book', 'info', 'Page data saved.', $this->session->userdata('barcode'));
	}

	/**
	 * Finish reviewing a book
	 *
	 * AJAX: Simply mark the current book as finished reviewing and redirect
	 * to the main page. We assume that the save routine was called prior to this.
	 *
	 * @since Version 1.0
	 */
	function end_review() {
		if (!$this->common->check_session(true)) {
			return;
		}

		$this->common->ajax_headers();
		try {
			// Get our book
			$barcode = $this->session->userdata('barcode');
			$this->book->load($barcode);

			// Completing a book with missing metadata is bad.
			$missing_metadata = $this->book->get_missing_metadata(true);

			if (count($missing_metadata) > 0) {
				// Yup. Missing some metadata. Let's prevent them from finishing.
				$msg = 'Some metadata fields are missing. They must be filled in before you can complete the metadata.<br/><br/>';
				foreach ($missing_metadata as $module => $fields) {
					$msg .= 'The missing fields are: <strong>'.implode(', ', $fields).'</strong><br/>';
				}
				$msg .= '<br/>Please <a href="/main/edit">edit the Item</a> to add this metadata.';
				header("Content-Type: application/json; charset=utf-8");
				echo json_encode(array('error' => $msg));
				return;
			}
			
			if ($error = $this->common->validate_marc($this->book->get_metadata('marc_xml'))){
				header("Content-Type: application/json; charset=utf-8");
				echo json_encode(array('error' => $error));
				return;
			}

			// Do all pages have page types?
			$all_pages = $this->book->get_pages();
			$pages = array();
			foreach ($all_pages as $p) {
				if (!isset($p->page_type) || !$p->page_type) {
					$pages[] = $p->sequence_number;
				}
			}
			if (count($pages) > 0) {
				$prefix = "The ";
				if (count($pages) > 10) {
					$prefix = "Some of the ";
					$pages = array_slice($pages, 0, 10);
				}
				$msg = 'One or more pages are missing a <strong>Page Type</strong>. Please correct this before continuing.<br><br>'.$prefix.' page(s) that are missing Page Types are: '.implode(', ', $pages);
				header("Content-Type: application/json; charset=utf-8");
				echo json_encode(array('error' => $msg));
				return;
			}
		
			// Make sure each file for the page exists.
			$missing_seq = array();
			foreach ($all_pages as $p) {
				$filename = $this->cfg['data_directory'].'/'.$barcode.'/scans/'.$p->filebase.'.'.$p->extension;
				if (!file_exists($filename)) {
					$missing_seq[] = $p->filebase.'.'.$p->extension;
				}
			}			
			if (count($missing_seq) > 0) {
				$prefix = "The ";
				if (count($missing_seq) > 10) {
					$prefix = "Some of the ";
					$missing_seq = array_slice($pages, 0, 10);
				}
				$msg = 'One or more pages are missing an image file.<br>Please re-upload the missing files before continuing.<br><br>'.$prefix.' missing files are:<br> '.implode('<br>', $missing_seq);
				header("Content-Type: application/json; charset=utf-8");
				echo json_encode(array('error' => $msg));
				return;
			}
		
			// Does the book need to be QA'ed by someone?

			if ($this->user->has_permission('qa_required')) {
				// Leave the book open
				$this->book->set_status('qa-ready');
				// Email the QA staff that it needs to be reviewed
				$this->_notify_qa($this->book->org_id);
				$this->session->set_userdata('message', 'Changes saved and item sent for QA review!');

			} elseif ($this->book->needs_qa) {
				// Is the person reviewing the book a QA person?
				if ($this->user->has_permission('qa')) {
					// Only a QA person can finish a book that's marked for QA
					$this->book->set_status('reviewed');
					$this->session->set_userdata('message', 'Changes saved! ');
				} else {
					// Leave the book open
					$this->book->set_status('qa-ready');
					// Email the QA staff that it needs to be reviewed
					$this->_notify_qa($this->book->org_id);
					$this->session->set_userdata('message', 'Changes saved and item sent for QA review!');
				}
			} else {
				// Otherwise, we just treat the book normally and finish it.
				$this->book->set_status('reviewed');
				$this->session->set_userdata('message', 'Changes saved! ');
			}
			$this->book->update();

			header("Content-Type: application/json; charset=utf-8");
			echo json_encode(array('redirect' => $this->config->item('base_url').'main/listitems'));
			$this->logging->log('access', 'info', 'Scanning review completed for '.$this->session->userdata('barcode').'.');

		} catch (Exception $e) {
			// Set the error and redirect to the main page
			$this->session->set_userdata('errormessage', $e->getMessage());
			redirect($this->config->item('base_url').'main');
		} // try-catch
	}

	
	/**
	 * Notify the QA staff that something needs reviewing
	 *
	 * INTERNAL: Called when a book is finished by a non-QA person.
	 *
	 * @since Version 1.4
	 */
	function _notify_qa($org_id = -1) {

		// Get a list of all QA users and their email addresses
		$qa_users = array();
		$this->db->where('username in (select username from permission where lower(permission) = \'qa\' and org_id = '.$org_id.');');
		$this->db->select('email');
		$query = $this->db->get('account');
		foreach ($query->result() as $row) {
			array_push($qa_users, $row->email);
		}

		// If we didn't get any QA users, let's send to the local admin users
		if (count($qa_users) == 0) {
      $this->db->where('username in (select username from permission where lower(permission) = \'local_admin\' and org_id = '.$org_id.');');
      $this->db->select('email');
			$query = $this->db->get('account');
			foreach ($query->result() as $row) {
				array_push($qa_users, $row->email);
			}
		}

		// If we didn't get any QA users, let's send to the admin users
		if (count($qa_users) == 0) {
			$this->db->where('id = 1');
			$this->db->select('email');
			$query = $this->db->get('account');
			foreach ($query->result() as $row) {
				array_push($qa_users, $row->email);
			}
		}

		$this->logging->log('error', 'info', print_r($qa_users,true));

		if (count($qa_users) > 0) {
			// Generate the message we're going to send
			$this->load->library('email');
	
			$config['protocol'] = 'smtp';
			$config['mailtype'] = 'html';
			$config['crlf'] = '\r\n';
			$config['newline'] = '\r\n';
			$config['smtp_host'] = $this->cfg['email_smtp_host'];
			$config['smtp_port'] = $this->cfg['email_smtp_port'];
			if ($this->cfg['email_smtp_user']) { $config['smtp_user'] = $this->cfg['email_smtp_user']; }
			if ($this->cfg['email_smtp_pass']) { $config['smtp_pass'] = $this->cfg['email_smtp_pass']; }
			
			$this->email->initialize($config);
			$this->email->from($this->cfg['admin_email'], 'MACAW Admin');
			$this->email->to($qa_users);
			$this->email->bcc($this->cfg['admin_email']);
			$this->email->subject('[Macaw] QA Notification');
			$this->email->message(
				'<html><body><font size="+1">This is a message from the MACAW server located at: '.$this->config->item('base_url')."<br><br>\r\n\r\n".
				'The following item is now ready for QA review: '."<br><br>\r\n\r\n".
				'<strong>Title:</strong> '.$this->book->get_metadata('title')."<br>\r\n".
				'<strong>Identifier:</strong> <a href="'.$this->config->item('base_url').'/main/managebarcode/'.$this->book->barcode.'">'.$this->book->barcode."</a><br>\r\n".
				'<strong>Edited By:</strong> '.$this->session->userdata('full_name').' ('.$this->session->userdata('username').')'."<br><br>\r\n\r\n".
				'To review this item: <br><br>'.
				'<em>If you are logged in</em>, click the Identifier link above. <br>'.
				'If not, log in and enter the identifier in the search box at the top.<br>'.
				'<br><br></font></body></html>'
			);
			error_reporting(0);
			if (!$this->email->send()) {
				$this->session->set_userdata('warning', "Warning: Unable to send QA notification email. Please check your SMTP settings.");
			}
			error_reporting(E_ALL & ~E_DEPRECATED);
		} else {
			$this->session->set_userdata('warning', "Warning: Unable to send QA notification email. Could not find a QA or Admin user.");
		}
	}

	/**
	 * Reorders all of the pages alphanumericallty
	 *
	 * @since Version 2.2.0
	 */
	function reorder_all() {
		$this->common->check_session();

		// Permission Checking
		if (!$this->user->has_permission('scan')) {
			$this->session->set_userdata('errormessage', 'You do not have permission to access that page.');
			redirect($this->config->item('base_url').'main');
			$this->logging->log('error', 'debug', 'Permission Denied to access '.uri_string());
			return;
		}

		// Load the book
		$this->book->load($this->session->userdata('barcode'));
		// Get the pagesm sorted by filebase
		$pages = $this->book->get_pages('filebase');
		$seq = 1;
		foreach ($pages as $p) {
			$this->book->set_page_sequence($p->id, $seq++);
		}
		// redirect to the scan/review page
		redirect($this->config->item('base_url').'scan/review');
	}


	/**
	 * Archives items to cold storage
	 *
	 * CLI: Searches for and copied books to cold storage. This can be used any
	 * number of times on one item to accommodate missing pages that were added
	 * since the last archive().
	 *
	 * @since Version 1.???
	 */
	function archive() {

	}

	/**
	 * Display the history for a book
	 *
	 * Shows the history page for the book whose barcode is in the session.
	 * Calls Book.get_history()
	 *
	 * @since Version 1.0
	 */
	function history() {
		$this->common->check_session();
		// Permission Checking
		if (!$this->user->has_permission('scan')) {
			$this->session->set_userdata('errormessage', 'You do not have permission to access that page.');
			redirect($this->config->item('base_url').'main');
			$this->logging->log('error', 'debug', 'Permission Denied to access '.uri_string());
			return;
		}

		$this->book->load($this->session->userdata('barcode'));
		$this->common->check_missing_metadata($this->book);
		$data['item_title'] = $this->session->userdata('title');
		$data['log'] = $this->book->get_history();
		$this->load->view('scan/history_view', $data);
	}

	/**
	 * Hnadle missing pages
	 *
	 * Depending on the argument passed in, we are either 'start'ing the
	 * missing pages process or we are 'insert'ing the missing pages. We may also
	 * 'cancel' the import of missing pages, but only if the import has not
	 * started. Finally, we can 'finish' importing the missing images.
	 *
	 * When we are starting, then we set the book status (back) to scanning
	 * and set the missing_pages flag to true before redirecting to the scanning
	 * monitor page.
	 *
	 * When we are inserting the missing pages, we simply show the missing pages
	 * window. There are no settings to be made.
	 *
	 * When cancelling, the status_code of the item is recalculated from the
	 * information in the database to revert the status back to what it should be.
	 *
	 * When finishing, the book is marked as 'scanned' and we redirect the browser
	 * to the review page.
	 *
	 * @since Version 1.1
	 */
	function missing($arg) {
		if ($arg == 'start') {

			try {
				$this->common->check_session();
				// Set the status of the book to scanning
				$this->book->load($this->session->userdata('barcode'));
				$this->book->update();

				// Redirect to the scan monitor page
				redirect($this->config->item('base_url').'scan/monitor');
				$this->logging->log('access', 'info', 'Started scanning missing pages for '.$this->session->userdata('barcode').'.');
				$this->logging->log('book', 'info', 'Started scanning missing pages.', $this->session->userdata('barcode'));

			} catch (Exception $e) {
				$this->session->set_userdata('errormessage',  $e->getMessage());
				redirect($this->config->item('base_url').'main');
				return;
			}

		} elseif ($arg == 'cancel') {
			$this->common->check_session();

			// Figure out what the status should be based on the dates
			$this->book->load($this->session->userdata('barcode'));
			$this->book->set_status($this->book->get_last_status(), true);
			$this->logging->log('access', 'info', 'Cancelled import of missing pages for '.$this->session->userdata('barcode').'.');
			$this->logging->log('book', 'info', 'Cancelled import of missing pages.', $this->session->userdata('barcode'));
			redirect($this->config->item('base_url').'main');

		} elseif ($arg == 'finish') {
			if (!$this->common->check_session(true)) {
				return;
			}

			$this->common->ajax_headers();
			try {
				// Get our book
				$this->book->load($this->session->userdata('barcode'));
				if ($this->book->status == 'scanning') {
					$this->book->set_status('scanned');
				} 

				header("Content-Type: application/json; charset=utf-8");
				echo json_encode(array('redirect' => $this->config->item('base_url').'scan/review/'));
				$this->logging->log('access', 'info', 'Missing pages inserted for '.$this->session->userdata('barcode').'.');
				$this->logging->log('book', 'info', 'Missing pages inserted.', $this->session->userdata('barcode'));

			} catch (Exception $e) {
				// Set the error and redirect to the main page
				$this->session->set_userdata('errormessage', $e->getMessage());
				redirect($this->config->item('base_url').'main');
			} // try-catch


		} elseif ($arg == 'insert') {
			$this->common->check_session();
			// Show the insert missing pages
			$this->book->load($this->session->userdata('barcode'));
			$this->common->check_missing_metadata($this->book);
			if ($this->book->status == 'scanning') {
				$this->book->set_status('scanned');
			} 

			$data['item_title'] = $this->session->userdata('title');
			$data['metadata_modules'] = $this->cfg['metadata_modules'];
			$this->load->view('scan/missing_view', $data);
			$this->logging->log('access', 'info', 'Began inserting missing pages for '.$this->session->userdata('barcode').'.');
			$this->logging->log('book', 'info', 'Inserting missing pages begins.', $this->session->userdata('barcode'));
		}
	}

	/**
	 * Upload images usign the new javascript importer
	 * 
	 * This handles the display of the new JS uploader/importer, which shows what existing
	 * images are in the directory. This just shows the page. It does not do the uploading.
	 *
	 * @since Version 2.2
	 */
	function upload(){
		if (!$this->user->has_permission('scan')) {
			$this->common->ajax_headers();
			echo json_encode(array('errormessage' => 'You do not have permission to access that page.'));
			$this->logging->log('error', 'debug', 'Permission Denied to access '.uri_string());
			return;
		}
		$barcode = $this->session->userdata('barcode');
		// Get our book
		$this->book->load($barcode);
		$this->common->check_missing_metadata($this->book);

		$data['upload_max_filesize'] = ini_get('upload_max_filesize');
		$data['max_sequence'] = $this->book->max_sequence();
		$data['item_title'] = $this->session->userdata('title');
		$data['ip_address'] = $_SERVER['REMOTE_ADDR'];
		$data['hostname'] = $this->common->_get_host($_SERVER['REMOTE_ADDR']);
		$data['incoming_path'] = $this->cfg['incoming_directory'].'/'.$barcode;
		$data['free'] = (int)(disk_free_space($this->cfg['data_directory'])/disk_total_space($this->cfg['data_directory'])*100);
		$data['used'] = (int)($this->user->get_space_used()/disk_total_space($this->cfg['data_directory'])*100);
		$books = $this->book->get_all_books(true, null, array('exporting'));
		$data['exporting'] = count($books);
		
		// Make sure the path exists
		if (!file_exists($data['incoming_path'])) {
			mkdir($data['incoming_path']);
			$this->logging->log('access', 'info', 'Created incoming directory: '.$data['incoming_path']);
		}

		$data['remote_path'] = $this->cfg['incoming_directory_remote'].'/'.$barcode;
		$status = $this->book->status;

		$data['book_has_missing_pages'] = false;
		$pgs = $this->book->get_pages();
		if (count($pgs) > 0) {
			// If yes, then the imported images are "missing".
			$data['book_has_missing_pages'] = true;
		}
		$this->logging->log('book', 'info', "Loading Upload page Missing is ".$data['book_has_missing_pages']." Page count is ".count($pgs), $barcode);
		$this->load->view('scan/upload_view_jquery', $data);		
	}

	/**
	 * Upload one image
	 *
	 * Used by the new javascript importer, this handles the uploading and processing
	 * of one image. Once uploaded, the image is sent the book model for import.
	 *
	 * If no file is provided, then a list of existing files are provided that are then
	 * (presumably) displayed on the main upload page. 
	 *
	 * The resulting image info is then returned to the browser. 
	 *
	 * @since Version 2.2
	 */
	public function do_upload() {
		// Set our paths
		$barcode = $this->session->userdata('barcode');
		$this->book->load($barcode);
		$scans_dir = $this->cfg['data_directory'].'/'.$barcode.'/scans/';
		$upload_path_url = base_url().'books/'.$barcode.'/scans/';
		$upload_thumb_url = base_url().'books/'.$barcode.'/thumbs/';
		
		// Make sure the path exists
		if (!file_exists($scans_dir)) {
			mkdir($scans_dir,'0777',true);
			chmod($scans_dir,'0777');
			$this->logging->log('book', 'info', 'Created directory: '.$scans_dir, $barcode);
		}
		
		$config['max_width']    = '30000';
		$config['max_height']   = '30000';
		$config['allowed_types'] = 'tif|tiff|jpg|jpeg|jp2|jpf|gif|png|bmp';

		if (!count($_FILES)) {
			//Load the list of existing files in the upload directory
			$foundFiles = $this->_get_existing_files($scans_dir, $barcode);

			header("Content-Type: application/json; charset=utf-8");
			if ($this->book->get_metadata('processing_pdf')) {
				$total = count($foundFiles);
				$count = count($this->book->get_pages());
				echo json_encode(array('files' => $foundFiles, 'reload' => 'true', 'message' => 'Processing PDF pages ('.$count.'/'.$total.')...', 'dir' => $scans_dir));
			} else {
				echo json_encode(array('files' => $foundFiles));			
			}
		} else {

			$data = array();

			$missing = false;
			$pgs = $this->book->get_pages();
			if (count($pgs) > 0) {
				// If yes, then the imported images are "missing".
				$missing = true;
			}
			$this->logging->log('book', 'info', "Starting Import Missing is $missing Page count is ".count($pgs), $barcode);

			foreach ($_FILES as $fieldName => $file) {
				if (preg_match("/\.pdf$/i", $file['name'][0])) {
					// We got a PDF, we need to split it
					move_uploaded_file($file['tmp_name'][0], $scans_dir.strip_tags(basename($file['name'][0])));
					
					$output = '';

					$php_exe = PHP_BINDIR.'/php5';		
					if (!file_exists($php_exe)) {
						$php_exe = PHP_BINDIR.'/php';
					}

					$exec = 'MACAW_OVERRIDE=1 "'.$php_exe.'" "'.$this->cfg['base_directory'].'/index.php" utils import_pdf '.escapeshellarg($this->book->barcode).' '.escapeshellarg($file['name'][0]).'> /dev/null 2> /dev/null < /dev/null &';
					$this->logging->log('book', 'info', 'EXEC: '.$exec, $this->book->barcode);
					exec($exec, $output);
					
					$this->book->set_metadata('processing_pdf','yes');
					$this->book->update();
					
					$foundFiles = $this->_get_existing_files($scans_dir, $barcode);
					header("Content-Type: application/json; charset=utf-8");
					echo json_encode(array('files' => $foundFiles, 'reload' => 'true', 'message' => 'Processing PDF pages...'));

					return;
				} else {
					$sequence = $_POST['sequence'];
					if (!$sequence) {$sequence = 0;}

					$counter = $_POST['counter'][0];
					if (!$counter) {$counter = 1;}
					
					// We assume we only get one file. Our JS upload config is set this way. So we use the coutner variable to keep things in order.
					move_uploaded_file($file['tmp_name'][0], $scans_dir.strip_tags(basename($file['name'][0])));
					$this->book->import_one_image($file['name'][0], $counter + $sequence, $missing);
					$data['file_name'] = $file['name'][0]; 
					$data['file_type'] = $file['type'][0];
					$data['file_size'] = $file['size'][0];
				}
 			}

			//set the data for the json array
			$info = new StdClass;
			$info->name = $data['file_name'];
			$info->size = $data['file_size'];
			$info->type = $data['file_type'];
			$info->url = $upload_path_url . $data['file_name'];
			$info->thumbnailUrl = $upload_thumb_url . pathinfo($data['file_name'], PATHINFO_FILENAME).'.jpg';
			$info->deleteUrl = 'none';
			$info->deleteType = 'NONE';
			$info->error = null;
			
			$files = array();
			$files[] = $info;

			if ($this->book->status == 'new' || $this->book->status == 'scanning') {
				$this->book->set_status('scanning');
				$this->book->set_status('scanned');
			}
			
			//this is why we put this in the constants to pass only json data
			header("Content-Type: application/json; charset=utf-8");
			echo json_encode(array("files" => $files));
		}
	}
	
	function _get_existing_files($dir, $barcode) {
		$upload_path_url = base_url().'books/'.$barcode.'/scans/';
		$upload_thumb_url = base_url().'books/'.$barcode.'/thumbs/';
		$existingFiles = get_dir_file_info($dir);
		$foundFiles = array();
		$f = 0;
		foreach ($existingFiles as $fileName => $info) {
			$thumbfileName = pathinfo($fileName, PATHINFO_FILENAME).'.jpg';
			//set the data for the json array   
			$foundFiles[$f]['name'] = $fileName;
			$foundFiles[$f]['size'] = $info['size'];
			$foundFiles[$f]['url'] = $upload_path_url . $fileName;

			if (file_exists($dir.'../thumbs/'.$thumbfileName)) {
				$foundFiles[$f]['thumbnailUrl'] = $upload_thumb_url . $thumbfileName;			
			} else {
				$foundFiles[$f]['thumbnailUrl'] = '/images/spacer.gif';				
			}
			$foundFiles[$f]['deleteUrl'] = 'none';
			$foundFiles[$f]['deleteType'] = 'DELETE';
			$foundFiles[$f]['error'] = '';
			$f++;
		}
		return $foundFiles;
	}
	
}

