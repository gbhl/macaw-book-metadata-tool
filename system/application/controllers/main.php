<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Main Controller
 *
 * MACAW Metadata Collection and Workflow System
 *
 * General use for showing the main page, accepting a barcode and setting it in
 * the session, showing the help and history for a book.
 *
 **/

class Main extends Controller {

	var $cfg;

	function Main() {
		parent::Controller();
		$this->cfg = $this->config->item('macaw');
	}

	/**
	 * Display the main window
	 *
	 * Shows the main page with activities that can be performed against the
	 * current book.
	 *
	 * SCS - Changed the logic due to new workflow.
	 *  If no barcode, go to in Pro
	 */
	function index() {
		$this->common->check_session();

		// Get our book
		//SCS 05 July 2012
		//Changing to redirect based on status.
		
		$data = array();
		if ($this->session->userdata('barcode')) {
			$this->book->load($this->session->userdata('barcode'));
			$status = $this->book->status;
	
		 	if ($status == 'new' || $status == 'scanning') {
		 		redirect($this->config->item('base_url').'scan/monitor');
		 	}
		 	if ($status == 'scanned' || $status == 'reviewing') {
		 		redirect($this->config->item('base_url').'scan/review');	
		 			}
		 	if ($status == 'exporting' || $status == 'completed' || $status == 'archived' || $status == 'reviewed') {
		 	 	redirect($this->config->item('base_url').'scan/history');
		 	}
		} else {
		 	redirect($this->config->item('base_url').'main/listitems');
		}
	}

	function terms() {
		$data['terms'] = read_file($this->cfg['base_directory'].'/terms.txt');
		$this->load->view('main/terms_view', $data);	
	}
	
	function terms_save() {
		$agree = false;
		if (isset($_POST['agree'])) {
			if ($_POST['agree'] == 1) {
				$agree = true;
			}
		}
		
		if ($agree) {
			// If they agreed to the terms, we mark them as such and redirect
			$this->user->load($this->session->userdata('username'));
			$this->user->terms_conditions = date('Y-m-d H:i:s');
			$this->user->update();

 			redirect($this->config->item('base_url').'dashboard');
 			return;
		} else {
			// If not, we send the back to the login page
 			redirect($this->config->item('base_url').'login/logout');
 			return;
		}
	
	}

	/**
	 * Obselete function. Now we just redirect to main which handles redirecting to somewhere appropriate.
	 **/
	function manage() {
		redirect($this->config->item('base_url').'main');
	}

	/**
	 * Handle a barcode entered from the main page
	 *
	 * AJAX: Accepts a barcode from the browser. Checks that the barcode exists and is in a
	 * state in which it can be used. Sets the barcode and book title into
	 * the user's session. Sends JSON back with the redirect URL or an error message.
	 *
	 * @param string [$barcode] The barcode that we received from the user
	 *
	 */
	function barcode($value) {
		if (!$this->common->check_session(true)) {
			return;
		}

		$this->common->ajax_headers();

		// Make sure we got a barcode from the form
		if (!isset($value)) {
			echo json_encode(array('error' => 'Please enter a barcode.'));
			return;
		}

		// Get the barcode from the form
		$barcode = $value; // TODO: Validate this as numbers only

		$ret = $this->common->validate_log_config($barcode);
		if ($ret) {
			echo json_encode(array('error' => 'Permission denied to write to file or directory: '.$ret.'. Please make sure the logs directory and all files are accesible to the web server user.'));
			return;
		}
		
		if (!$this->book->exists($barcode)) {
			// We don't want to be left with a situation that makes the user thinks they
			// have a valid book when it was invalid. Reset the session variables.
			$this->session->set_userdata('barcode', '');
			$this->session->set_userdata('title', '');
			$this->session->set_userdata('author', '');

			// Give our response to the user.
			echo json_encode(array('question' => "The item \"$barcode\" could not be found.<br/><br/>Would you like to create it?"));		
			return;
		}

		// Query the database for the barcode
		try {
			// Get the book
			$this->book->load($barcode);
			
			// Do we have permission to view this item? 
			// People with the same org or super admins can access it, everyone else gets denied
			$this->user->load($this->session->userdata('username'));
			if ($this->book->org_id != $this->user->org_id && !$this->user->has_permission('admin')) {
				// Give our response to the user.
				echo json_encode(array('error' => "You do not have permission to use that item."));		
				return;						
			}

			// Set the barcode and other info in the session
			$this->session->set_userdata('barcode', $this->book->barcode);
			$this->session->set_userdata('title', $this->book->get_metadata('title'));
			$this->session->set_userdata('author', $this->book->get_metadata('author'));

			// Redirect to the main activities page
			echo json_encode(array('redirect' => $this->config->item('base_url').'main'));
			$this->logging->log('access', 'info', 'Barcode '.$barcode.' scanned successfully.');
			$this->logging->log('book', 'info', 'Barcode scanned successfully.', $barcode);

		} catch (Exception $e) {
			// We don't want to be left with a situation that makes the user thinks they
			// have a valid book when it was invalid. Reset the session variables.
			$this->session->set_userdata('barcode', '');
			$this->session->set_userdata('title', '');
			$this->session->set_userdata('author', '');

			// Redirect to the main activities page
			echo json_encode(array('error' => $e->getMessage()));
			$this->logging->log('error', 'error', 'Barcode scan for '.$barcode.' error: '.$e->getMessage());
			$this->logging->log('book', 'error', 'Barcode scan returned error: '.$e->getMessage(), $barcode);
		}
	}
	
	/**
	 * Accept a barcode from the URL
	 *
	 * Accepts a barcode from the browser via the URL. Checks that the barcode exists and is in a
	 * state in which it can be used. Sets the barcode and book title into
	 * the user's session. Sets messages and redirects as necessary.
	 *
	 * @param string [$barcode] The barcode that we received from the user
	 *
	 */
	function managebarcode($barcode){
		if (!$this->common->check_session(true)) {
			return;
		}

		// Make sure we got a barcode from the form
		if (!isset($barcode)) {
			$this->session->set_userdata('errormessage', 'Please enter an identifier.');
			redirect($this->config->item('base_url').'main/listitems');
			return;
		}

		// Get the barcode from the form		
		if (!$this->book->exists($barcode)) {
			// We don't want to be left with a situation that makes the user thinks they
			// have a valid book when it was invalid. Reset the session variables.
			$this->session->set_userdata('barcode', '');
			$this->session->set_userdata('title', '');
			$this->session->set_userdata('author', '');

			// Give our response to the user.
			$this->session->set_userdata('errormessage', 'The item with identifier "'.$barcode.'" could not be found.');
			redirect($this->config->item('base_url').'main/listitems');
			return;
		}

		// Query the database for the barcode
		try {
			// Get the book
			$this->book->load($barcode);

			// Do we have permission to view this item? 
			// People with the same org or super admins can access it, everyone else gets denied
			$this->user->load($this->session->userdata('username'));
			if ($this->book->org_id != $this->user->org_id && !$this->user->has_permission('admin')) {
				// Give our response to the user.
				$this->session->set_userdata('errormessage', 'You do not have permission to use that item.');
				redirect($this->config->item('base_url').'main/listitems');

			} else {
				// Set the barcode and other info in the session
				$this->session->set_userdata('barcode', $this->book->barcode);
				$this->session->set_userdata('title', $this->book->get_metadata('title'));
				$this->session->set_userdata('author', $this->book->get_metadata('author'));
	
				// Redirect depending on status
				if ($this->book->status == 'new' || $this->book->status == 'scanning'){
					redirect($this->config->item('base_url').'scan/upload');
	
				} elseif ($this->book->status == 'scanned' || $this->book->status == 'reviewing'){
					redirect($this->config->item('base_url').'scan/review');
	
				} elseif ($this->book->status == 'reviewed' || $this->book->status == 'completed' || $this->book->status == 'exporting' || $this->book->status == 'archived'){			
					if ($this->book->status == 'reviewed' && $this->user->has_permission('admin')) {
						redirect($this->config->item('base_url').'scan/review');					
					} else {
						$this->session->set_userdata('warning', 'This item can no longer be edited. You are seeing the item\'s history instead.');
						redirect($this->config->item('base_url').'scan/history');
					}
					
				} else {
					redirect($this->config->item('base_url').'main');
				}
			}
			
		} catch (Exception $e) {
			// We don't want to be left with a situation that makes the user thinks they
			// have a valid book when it was invalid. Reset the session variables.
			$this->session->set_userdata('barcode', '');
			$this->session->set_userdata('title', '');
			$this->session->set_userdata('author', '');

			// Redirect to the main activities page
			$this->session->set_userdata('errormessage', 'This item could not be loaded. The error is: '.$e->getMessage());
			$this->logging->log('error', 'error', 'Barcode scan for '.$barcode.' error: '.$e->getMessage());
			$this->logging->log('book', 'error', 'Barcode scan returned error: '.$e->getMessage(), $barcode);
			redirect($this->config->item('base_url').'scan/history');
		}

	
	}
	
	/**
	 * Display the help page
	 *
	 * Loads the help window based on the help ID provided. Shows help index
	 * if ID is not provided.
	 *
	 */
	function help() {
		$this->common->check_session();
		$this->load->view('main/help_view');
	}

	/**
	 * Display the help page
	 *
	 * Loads the help window based on the help ID provided. Shows help index
	 * if ID is not provided.
	 *
	 */
	function js_config() {
        header("Content-Type:application/javascript");
        header("Cache-Control:max-age=290304000, public");

		echo "var sBaseUrl = '".preg_replace('/\/$/','',$this->config->item('base_url'))."';";
	}

	/**
	 * Display the Edit page for an Item
	 *
	 * Build the page for editing the item-level metadata for an item. 
	 * Since there are any number of fields available for entering 
	 * metadata, this function has to create an appropriate array of metadata 
	 * fields and values to send to the view, which then creates the data
	 * entry fields accordingly.
	 * 
	 * This action operates on the current item, therefore an identifer is not
	 * needed.
	 *
	 */
	function edit() {
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

		// Fill in the data
		$data['new'] = false;		
		$data['identifier'] = $barcode;
		$metadata = $this->book->get_metadata();

		$md = array();
		$array_counts = array();
		foreach ($metadata as $i) {
			if (array_key_exists($i['fieldname'], $array_counts)) {
				$array_counts[$i['fieldname']]++;
			} else {
				$array_counts[$i['fieldname']] = 1;
			}
			if ($i['fieldname'] != 'needs_qa' && $i['fieldname'] != 'ia_ready_images' && $i['fieldname'] != 'page_progression' && $i['fieldname'] != 'copyright' && $i['fieldname'] != 'cc_license') {
				array_push($md, array(
					'fieldnamex' => $i['fieldname'].'_'.$array_counts[$i['fieldname']],
					'fieldname' => $i['fieldname'],
					'value' => $i['value']
				));
			}
		}
		$data['bhl_institutions'] = $this->bhl->get_institutions();
		
		$data['is_local_admin'] = $this->user->has_permission('local_admin');
		$data['is_admin'] = $this->user->has_permission('admin');
		$data['item_title'] = $this->session->userdata('title');
		$data['missing_metadata'] = $this->book->get_missing_metadata(false);
		$data['metadata'] = $md;
		$data['is_qa_user'] = false;
		if ($this->user->has_permission('qa') || $this->user->has_permission('admin') || $this->user->has_permission('local_admin')) {
			$data['is_qa_user'] = true;
		}
		$data['needs_qa'] = $this->book->needs_qa;
		$data['ia_ready_images'] = $this->book->ia_ready_images;
		$data['page_progression'] = $this->book->page_progression;
		$data['id'] = $this->book->id;
		$data['organization'] = $this->book->org_name;
		if (isset($this->cfg['copyright_values'])) {
			$data['copyright_values'] = $this->cfg['copyright_values'];		
		} else {
			$data['copyright_values'] = array(
				array('title' => 'Not in Copyright', 'value' => 0),
				array('title' => 'In Copyright, Permission Granted', 'value' => 1),
				array('title' => 'In Copyright, Due Dilligence', 'value' => 2)
			);		
		}
		$copyright = $this->book->get_metadata('copyright');
		if (is_array($copyright)) {
			$copyright = $copyright[0];		
		}
		$data['copyright'] = $copyright;
		if (isset($this->cfg['cc_licenses'])) {
			$data['cc_licenses'] = $this->cfg['cc_licenses'];
		} else {
			$data['cc_licenses'] = array(
				array('title' => '(none)', 			'value' => ''),
				array('title' => 'CC BY', 			'value' => 'http://creativecommons.org/licenses/by/3.0/'),
				array('title' => 'CC BY-SA', 		'value' => 'http://creativecommons.org/licenses/by-sa/3.0/'),
				array('title' => 'CC BY-ND', 		'value' => 'http://creativecommons.org/licenses/by-nd/3.0/'),
				array('title' => 'CC BY-NC', 		'value' => 'http://creativecommons.org/licenses/by-nc/3.0/'),
				array('title' => 'CC BY-NC-SA', 'value' => 'http://creativecommons.org/licenses/by-nc-sa/3.0/'),
				array('title' => 'CC BY-NC-ND', 'value' => 'http://creativecommons.org/licenses/by-nc-nd/3.0/'),
			);
		}
		$data['cc_license'] = $this->book->get_metadata('cc_license');

		$data['org_has_qa'] = false;
		if ($this->user->org_has_qa()) {
			$data['org_has_qa'] = true;
		}
		$this->load->view('main/edit_view', $data);
	}
	
	/**
	 * Save the results of the Edit page
	 *
	 * Gather the metadata fields supplied by the edit page and 
	 * save them ot the database. This function deletes all item-level 
	 * metadata before saving it to the database. Hopefully we won't
	 * have any errors while saving.
	 *
	 * Again, this action operates on the current item, therefore an 
	 * identifer is not needed.
	 *
	 */
	function edit_save() {
		$this->common->check_session();
		
		// Permission Checking
		if (!$this->user->has_permission('scan')) {
			$this->session->set_userdata('errormessage', 'You do not have permission to access that page.');
			redirect($this->config->item('base_url').'main');
			$this->logging->log('error', 'debug', 'Permission Denied to access '.uri_string());
			return;
		}

		if ($_REQUEST['action'] == 'delete') {
			return $this->delete_confirm();
		}
				
		// Get our book
		$barcode = $this->session->userdata('barcode');
		$this->book->load($barcode);

		// Who are we?
		$is_local_admin = $this->user->has_permission('local_admin');
		$is_admin = $this->user->has_permission('admin');
			
		// Apply the metadata
		$this->book->unset_metadata('', true); // Wipe all all item metadata
		
		foreach ($_REQUEST as $field => $val) {			
			if ($field != 'id' && $field != 'needs_qa' && $field != 'ia_ready_images' && $field != 'page_progression' && $field != 'idenitifer' &&
			    $field != 'scanning_institution[]' && $field != 'scanning_institution_other' && $field != 'rights_holder[]' && $field != 'rights_holder_other') {
				$matches = array();
				if (preg_match('/^new_fieldname_(\d+)$/', $field, $matches)) {
					$c = $matches[1];
					if (isset($_REQUEST['new_fieldname_'.$c]) && isset($_REQUEST['new_value_'.$c]) && $_REQUEST['new_value_'.$c] != '') {						
						// We got a value from the plain text field.
						$this->book->set_metadata(trim($_REQUEST['new_fieldname_'.$c]), $_REQUEST['new_value_'.$c], false);
					} elseif ($_REQUEST['new_fieldname_'.$c] && array_key_exists('new_value_'.$c.'_file', $_FILES)) {
						// We didn't get a value, so let's see if we got a file upload
						// Make sure the file exists, dummy!
						if (file_exists($_FILES['new_value_'.$c.'_file']['tmp_name'])) {
							$string = read_file($_FILES['new_value_'.$c.'_file']['tmp_name']);
							$this->book->set_metadata(trim($_REQUEST['new_fieldname_'.$c]), $string, false);
						}
					}
				} else {
					if (is_array($val)) {
						foreach ($val as $v) {
							// Don't save an empty value.
							if (isset($v) && $v != '') {
								$this->book->set_metadata(trim($field), $v, false);
							}
						}
					}
				}
			}
		}
		
		$collection = $this->book->get_metadata('collection');
		$collections = $this->book->get_all_collections();
		if ($collection && $collections) {
			$new = array();
			foreach($collection as $c) {
				if (!in_array($c, $collections)){
					$new[] = $c;
				}
			}
			if (count($new) > 0) {
				$this->session->set_userdata('errormessage', "It looks like the following collection(s) are new. Please verify the spelling and accuracy. <strong>".implode(', ', $new)."</strong>");
			}
		}
		
		if (array_key_exists('scanning_institution', $_POST)) {
			if ($_POST['scanning_institution'] != '(other)') {
				$this->book->set_metadata('scanning_institution', $_POST['scanning_institution'], false);
			} else {
				if (array_key_exists('scanning_institution_other', $_POST)) {
					$this->book->set_metadata('scanning_institution', $_POST['scanning_institution_other'], false);
				}
			}
		}

		$rights_holder = null;
		if (array_key_exists('rights_holder', $_POST)) {
			if ($_POST['rights_holder'] != '(other)') {
				$this->book->set_metadata('rights_holder', $_POST['rights_holder'], false);
				$rights_holder = $_POST['rights_holder'];
			} else {
				if (array_key_exists('rights_holder_other', $_POST)) {
					$this->book->set_metadata('rights_holder', $_POST['rights_holder_other'], false);
					$rights_holder = $_POST['rights_holder_other'];
				}
			}
		}
		
		if (array_key_exists('copyright', $_POST)) {
			if (isset($_POST['copyright'][0])) {
				if ($_POST['copyright'][0] == '1' && !$rights_holder) {
					$this->session->set_userdata('warning', "<strong>Rights Holder</strong> is required when the item is <strong>In Copyright</strong>.");
				}
			}
		}
		
 		$this->book->needs_qa = (array_key_exists('needs_qa', $_POST) ? true : false);
 		$this->book->ia_ready_images = (array_key_exists('ia_ready_images', $_POST) ? true : false);
 		$this->book->page_progression = $_POST['page_progression'][0];

		// If we got marc_xml but not mods_xml, convert it to mods and save that, too
		$marc = $this->book->get_metadata('marc_xml');
		$marc = $this->common->clean_marc($marc);
		if ($msg = $this->common->validate_marc($marc)){
			$this->session->set_userdata('errormessage', $msg);
		} else {
			$this->book->set_metadata('marc_xml', $marc);
			if ($marc && !$this->book->get_metadata('mods_xml')) {
				try {
					$mods = $this->common->marc_to_mods($marc);
				} catch (Exception $e) {
					$this->session->set_userdata('errormessage', "Error converting MARCXML to MODS: ".$e->getMessage());
				}
				
				if ($mods) {
					$this->book->set_metadata('mods_xml', $mods, true);
					$ret = $this->book->_read_mods($mods);
					
					if (isset($ret['title'])) {
						$this->book->set_metadata('title', $ret['title']);
					}
					if (isset($ret['author'])) {
						$this->book->set_metadata('author', $ret['author']);
					}
				} else {
					$this->session->set_userdata('errormessage', "Error converting MARCXML to MODS: Unable to parse MARC data in the <strong>marc_xml</strong> field.");			
				}
			}		
		}


 		$this->book->update();

		// Set the barcode and other info in the session
		$this->session->set_userdata('barcode', $this->book->barcode);
		$this->session->set_userdata('title', $this->book->get_metadata('title'));
		$this->session->set_userdata('author', $this->book->get_metadata('author'));

		$this->session->set_userdata('message', 'Changes saved!');
		//Changed redirect to review with new style and workflow
		redirect($this->config->item('base_url').'main/edit');	
	}
	
	/**
	 * Confirm that we want to delete the item
	 *
	 * Present the admin with some information about what is about to
	 * be deleted. The item name, organization, how many files and 
	 * database records are about to get deleted. Allow them to optionally
	 * backup the item before it gets deleted.
	 *
	 * Again, this action operates on the current item, therefore an 
	 * identifer is not needed.
	 *
	 * This is availble only to admins or local_admins.
	 */
	function delete_confirm() {
		$this->common->check_session();

		// Get our book
		$this->book->load($this->session->userdata('barcode'));
		$barcode = $this->session->userdata('barcode');

		# 1. Count up the number of files
		$files = $this->_getFilesFromDir($this->cfg['data_directory'].'/'.$barcode);
		
		# 2. Get the item information
		$query = $this->db->query('select * from item where barcode = ?', array($barcode));
		$item = $query->result();
		$id = $item[0]->id;

		# 3. Count the number of records we're going to delete.
		$record_count = 1; 

		$query = $this->db->query('select count(*) as thecount from item_export_status where item_id = ?', array($id));
		$count = $query->result();
		
		$record_count = $record_count + $count[0]->thecount;

		$query = $this->db->query('select count(*) as thecount from page where item_id = ?', array($id));
		$count = $query->result();
		$record_count = $record_count + $count[0]->thecount;

		$query = $this->db->query('select count(*) as thecount from metadata where item_id = ?', array($id));
		$count = $query->result();
		$record_count = $record_count + $count[0]->thecount;	

		// Get the amount of information that is about to deleted
		$data['item_title'] = $this->session->userdata('title');
		$data['database_rows'] = $record_count;
		$data['file_count'] = count($files);
		$data['identifier'] = $barcode;
		$data['id'] = $this->book->id;
		$data['organization'] = $this->book->org_name;
		$data['title'] = $this->book->get_metadata('title');

		$this->load->view('main/delete_confirm_view', $data);		
	}

	/**
	 * Delete the item
	 *
	 * Backup the item if needed, then purge the item from the database
	 * and from disk. The backup file remains and will need to ultimately
	 * be deleted later as it still uses up disk space.
	 *
	 * Again, this action operates on the current item, therefore an 
	 * identifer is not needed.
	 *
	 * This is availble only to admins or local_admins.
	 */
	function delete() {
		$this->common->check_session();
		
		if ($_REQUEST['action'] == 'cancel') {
			return $this->edit();
		}
		// Get our book and current user
		$barcode = $this->session->userdata('barcode');
		$this->book->load($barcode);
		$this->user->load($this->session->userdata('username'));

		// Make sure we can access this item
		// (either we are admin or we are local admin and the book is in our org_id)
		if ($this->user->org_id != $this->book->org_id && !$this->user->has_permission('admin')) {
			$this->session->set_userdata('errormessage', 'Unable to delete the item with identifier "'.$barcode.'". It does not belong to your organization!');
			redirect($this->config->item('base_url').'main/listitems');
			return;		
		}

		// Do we want to backup the item?
		$path = '';
		if (isset($_REQUEST['backup'])) {
			if ($_REQUEST['backup'] == 1) {
				try {
					// Back up the item. Use the serialize code in the utils controller
					$path = $this->common->serialize($barcode);
				} catch (Exception $e) {
					// Whoops! 
					$this->session->set_userdata('errormessage', 'Unable to backup the item before deleting. The error was: '.$e->getMessage());
					redirect($this->config->item('base_url').'main/delete_confirm');
					return;
				}
			}
		}
		
		$id = $this->book->id;

		// Delete the data
		$query = $this->db->query('delete from metadata where item_id = ?', array($id));
		$query = $this->db->query('delete from page where item_id = ?', array($id));
		$query = $this->db->query('delete from item_export_status where item_id = ?', array($id));
		$query = $this->db->query('delete from item where id = ?', array($id));
		// Delete the files
		if (file_exists($this->cfg['data_directory'].'/'.$barcode)) {
			try {
				delete_files($this->cfg['data_directory'].'/'.$barcode, TRUE);
				rmdir($this->cfg['data_directory'].'/'.$barcode);
			} catch (Exception $e) {
				$this->session->set_userdata('errormessage', 'Unable to delete the the item.<br>'.$e->getMessage());			
			}
		}

		if (file_exists($this->cfg['incoming_directory'].'/'.$barcode)) {
			delete_files($this->cfg['incoming_directory'].'/'.$barcode, TRUE);
			rmdir($this->cfg['incoming_directory'].'/'.$barcode);
		}

		if ($path) {
			$data['path'] = str_replace($this->cfg['base_directory'], '', $path);
			$data['filename'] = basename($path);
			$this->load->view('main/delete_download_view', $data);						
			
		} else {
			$this->session->set_userdata('message', 'The item was deleted successfully.');
			redirect($this->config->item('base_url').'main/listitems');	
		}

	}

	/**
	 * Display the one-off add item form
	 *
	 * Displays a blank form for creating an item in Macaw. 
	 *
	 */
	function add($barcode = '') {
		$this->common->check_session();
		// Permission Checking
		if (!$this->user->has_permission('scan')) {
			$this->session->set_userdata('errormessage', 'You do not have permission to access that page.');
			redirect($this->config->item('base_url').'main');
			$this->logging->log('error', 'debug', 'Permission Denied to access '.uri_string());
			return;
		}

		$data['bhl_institutions'] = $this->bhl->get_institutions();
		$data['is_local_admin'] = $this->user->has_permission('local_admin');
		$data['is_admin'] = $this->user->has_permission('admin');
		$data['new'] = true;		
		$data['metadata'] = array();		
		$data['identifier'] = $barcode;
		$data['missing_metadata'] = $this->book->get_missing_metadata(false);
		$data['needs_qa'] = false;
		$data['ia_ready_images'] = false;
		$data['page_progression'] = 'ltr';
		if (array_key_exists('identifier', $_REQUEST)) {
			$data['identifier'] = $_REQUEST['identifier'];
		}
		$data['is_qa_user'] = false;
		if ($this->user->has_permission('qa') || $this->user->has_permission('admin')) {
			$data['is_qa_user'] = true;
		}
		$data['organization'] = $this->user->org_name;		
		if (isset($this->cfg['copyright_values'])) {
			$data['copyright_values'] = $this->cfg['copyright_values'];		
		} else {
			$data['copyright_values'] = array(
				array('title' => 'Not in Copyright', 'value' => 0),
				array('title' => 'In Copyright, Permission Granted', 'value' => 1),
				array('title' => 'In Copyright, Due Dilligence', 'value' => 2)
			);		
		}
		$data['copyright'] = 0;
		if (isset($this->cfg['cc_licenses'])) {
			$data['cc_licenses'] = $this->cfg['cc_licenses'];
		} else {
			$data['cc_licenses'] = array(
				array('title' => '(none)', 			'value' => ''),
				array('title' => 'CC BY', 			'value' => 'http://creativecommons.org/licenses/by/3.0/'),
				array('title' => 'CC BY-SA', 		'value' => 'http://creativecommons.org/licenses/by-sa/3.0/'),
				array('title' => 'CC BY-ND', 		'value' => 'http://creativecommons.org/licenses/by-nd/3.0/'),
				array('title' => 'CC BY-NC', 		'value' => 'http://creativecommons.org/licenses/by-nc/3.0/'),
				array('title' => 'CC BY-NC-SA', 'value' => 'http://creativecommons.org/licenses/by-nc-sa/3.0/'),
				array('title' => 'CC BY-NC-ND', 'value' => 'http://creativecommons.org/licenses/by-nc-nd/3.0/'),
			);
		}
		$data['cc_license'] = '';
		$data['org_has_qa'] = false;
		if ($this->user->org_has_qa()) {
			$data['org_has_qa'] = true;
		}
		$this->load->view('main/edit_view', $data);
	}

	/**
	 * Save the results of the Add item page
	 *
	 * Gather the metadata fields supplied by the add item page and 
	 * save them to the database. 
	 *
	 * This also selects the item as the current item in use in the session. 
	 * (i.e. the user doesn't need to enter the barcode again)
	 * 
	 */
	function add_save() {
		$this->common->check_session();
		// Permission Checking
		if (!$this->user->has_permission('scan')) {
			$this->session->set_userdata('errormessage', 'You do not have permission to access that page.');
			redirect($this->config->item('base_url').'main');
			$this->logging->log('error', 'debug', 'Permission Denied to access '.uri_string());
			return;
		}

		$info = array();
		
		// Get our book
		$info['barcode'] = $_POST['identifier'];
		try {
			$this->book->add($info);
		} catch (Exception $e) {
			$data['new'] = true;		
			$data['metadata'] = array();
			$data['identifier'] = '';
			$data['needs_qa'] = false;
			$data['ia_ready_images'] = false;
			$data['page_progression'] = 'ltr';
			$data['is_qa_user'] = false;
			$this->session->set_userdata('errormessage', $e->getMessage().' Please go back and try again.');
			$data['org_has_qa'] = false;
			if ($this->user->org_has_qa()) {
				$data['org_has_qa'] = true;
			}
			$this->load->view('main/edit_view', $data);
			return;
		}
		$this->book->load($info['barcode']);

		// TODO: Redo this sto send in the $info['metadata'] instead.
		// TODO: This is doubling up the code. 
		
		// Apply the metadata
		foreach ($_REQUEST as $field => $val) {
			if ($field != 'id' && $field != 'needs_qa' && $field != 'ia_ready_images' && $field != 'page_progression' && $field != 'idenitifer') {
				$matches = array();
				if (preg_match('/^new_fieldname_(\d+)$/', $field, $matches)) {
					$c = $matches[1];
					if (isset($_REQUEST['new_fieldname_'.$c]) && isset($_REQUEST['new_value_'.$c]) && $_REQUEST['new_value_'.$c] != '') {
						if ($_REQUEST['new_fieldname_'.$c] == 'scanning_institution' || $_REQUEST['new_fieldname_'.$c] == 'rights_holder') {
							if ($_REQUEST['new_value_'.$c] == '(other)') {
								$this->book->set_metadata(trim($_REQUEST['new_fieldname_'.$c]), $_REQUEST['new_value_'.$c.'_other'], false);
							} else {
								$this->book->set_metadata(trim($_REQUEST['new_fieldname_'.$c]), $_REQUEST['new_value_'.$c], false);
							}
						} else {
							// We got a value from the plain text field.
							$this->book->set_metadata(trim($_REQUEST['new_fieldname_'.$c]), $_REQUEST['new_value_'.$c], false);
						}
					} elseif ($_REQUEST['new_fieldname_'.$c] && array_key_exists('new_value_'.$c.'_file', $_FILES)) {
						// We didn't get a value, so let's see if we got a file upload
						$string = read_file($_FILES['new_value_'.$c.'_file']['tmp_name']);
						$this->book->set_metadata(trim($_REQUEST['new_fieldname_'.$c]), $string, false);
					}
				} else {
					if (is_array($val)) {
						foreach ($val as $v) {
							$this->book->set_metadata(trim($field), $v, false);
						}
					}
				}
			}
		}
		
 		$this->book->needs_qa = ((array_key_exists('needs_qa', $_POST) && $_POST['needs_qa'] == 1) ? true : false);
 		$this->book->ia_ready_images = ((array_key_exists('ia_ready_images', $_POST) && $_POST['ia_ready_images'] == 1) ? true : false);
 		$this->book->page_progression = $_POST['page_progression'][0];

		// If we got marc_xml but not mods_xml, convert it to mods and save that, too
		$marc = $this->book->get_metadata('marc_xml');
		$marc = $this->common->clean_marc($marc);
		if ($msg = $this->common->validate_marc($marc)){
			$this->session->set_userdata('errormessage', $msg);
		}
		
		$this->book->set_metadata('marc_xml',$marc);

		if ($marc && !$this->book->get_metadata('mods_xml')) {
			$mods = $this->common->marc_to_mods($marc);
			$this->book->set_metadata('mods_xml', $mods, true);
			$this->book->_read_mods($mods);
		}

 		$this->book->update();
		$this->session->set_userdata('message', 'Item Added!');

		// Set the barcode and other info in the session
		$this->session->set_userdata('barcode', $this->book->barcode);
		$this->session->set_userdata('title', $this->book->get_metadata('title'));
		$this->session->set_userdata('author', $this->book->get_metadata('author'));
		
		// Log our stuffs
		$this->logging->log('access', 'info', 'Barcode '.$info['barcode'].' scanned successfully.');
		$this->logging->log('book', 'info', 'Barcode scanned successfully.', $info['barcode']);

		// SCS Change to redirect to /scan/monitor as this is a new item*/
		redirect($this->config->item('base_url').'scan/upload');
	}

	/**
	 * Display the CSV import page
	 *
	 * This function simply displays the CSV import form.
	 *
	 */
	function import() {
		if (!$this->user->has_permission('scan')) {
			$this->session->set_userdata('errormessage', 'You do not have permission to access that page.');
			redirect($this->config->item('base_url').'main');
			$this->logging->log('error', 'debug', 'Permission Denied to access '.uri_string());
			return;
		}
		
		$data = array();
		$data['token'] = $this->session->userdata('li_token');
		$this->load->view('main/import_view', $data);
	}

	/**
	 * Import the CSV file 
	 *
	 * AJAX: This receives via a POST, the CSV file that is being imported. 
	 * It intiates the import and returns an identifier that can be used
	 * to monitor the progress of the import process. This is a 
	 *
	 */
	function import_upload() {
		if (!$this->common->check_session(true)) {
			return;
		}
		
		// Make sure we have somewhere to save the file.
		$dir = $this->cfg['data_directory'].'/import_export';
		if (!file_exists($dir)) {
			mkdir($dir);
		}

		// Receive the CSV file 
		$config['upload_path'] = $dir;
		$config['allowed_types'] = 'csv|text|txt';
		$config['overwrite'] = true; // We do this because the file already exists from tempnam()

		$this->load->library('upload', $config);

		$this->common->ajax_headers();
		if (!$this->upload->do_upload('itemdata')) {			
			// Something bad happened	
			echo json_encode(array(
				'error' => $this->upload->display_errors(),
			));
		} else {
			$data = $this->upload->data();
			// Get a temporary filename
			$ext = pathinfo($data['file_name'], PATHINFO_EXTENSION);	
			$tempfilename = tempnam($dir, 'import-item-');
			rename($tempfilename, $tempfilename.'.'.$ext);
			$tempfilename .= '.'.$ext;

			// Rename the file we uploaded
			rename($data['full_path'], $tempfilename);
			$fname = basename($tempfilename);

			$fname2 = 'null';
			if ($this->upload->do_upload('pagedata')) {	
				$data = $this->upload->data();
				// Get a temporary filename
				$ext = pathinfo($data['file_name'], PATHINFO_EXTENSION);	
				$tempfilename = tempnam($dir, 'import-page-'); // this creates a file
				rename($tempfilename, $tempfilename.'.'.$ext);
				$tempfilename .= '.'.$ext;
	
				// Rename the file we uploaded
				rename($data['full_path'], $tempfilename);
				$fname2 = basename($tempfilename);
			}

			$username = $this->session->userdata('username');
			// Spawn the import process (php index.php utils csv_import FILENAME.CSV)
			chdir($this->cfg['base_directory']);
			$cmd = PHP_BINDIR.'/php index.php utils csvimport '.$fname.' '.$fname2.' '.$username.' > /dev/null 2>&1 &'; 
			$this->logging->log('access', 'info', 'Importing CSV file(s): '.$fname.' and '.$fname2);
			$this->logging->log('access', 'info', 'Command: '.$cmd);
			system($cmd);

			// Give the filename back to the page.
			echo json_encode(array(
				'filename' => $fname,
				'cmd' => $cmd
			));
		}
	}

	/**
	 * Monitor the progress while importing the CSV 
	 *
	 * JSON: Given the identifier of provided by import_upload(),
	 * returns a value (from 0 to 100) of how far long we are in
	 * the import process. Also returns an optional message that
	 * may be used to indicate some status to the user during import.
	 * When the value returned is equal to 100, the import process
	 * is complete.
	 *
	 */
	function import_status($filename) {
		if (!$this->common->check_session(true)) {
			return;
		}
		
		$dir = $this->cfg['data_directory'].'/import_export';
		$fname = $dir.'/'.$filename.'.log';

		// Get the progress of the file
		$string = read_file($fname); 
		if ($string) {
			$data = json_decode($string);
	
			// If the progres of the file is 100%, then it's safe to delete the progress file
			// (By this time, the import file has already been cleaned out)
			if ($data->finished == 1) {
				unlink($fname);
			}
			// Send back the text
		} else {
			$string = '{"message":"","value":"","finished":"0"}';
		} 
		$this->common->ajax_headers();
		echo $string;		
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
	function listitems() {
		$data['filter'] = array('all', 'new', 'in progress', 'completed');
		$this->load->view('main/queue_view', $data);
	}

	/**
	 * Recursively get a single array of all files
	 *
	 * CLI: Given a path, recurse through the files and directories 
	 * and return a single list of full paths that are contained in and
	 * below it. 
	 *
	 */
	function _getFilesFromDir($dir) { 
		$files = array(); 
		if (file_exists($dir)) {
			if ($handle = opendir($dir)) { 
				while (false !== ($file = readdir($handle))) { 
					if ($file != "." && $file != "..") { 
						if(is_dir($dir.'/'.$file)) { 
							$dir2 = $dir.'/'.$file; 
							$files[] = $this->_getFilesFromDir($dir2); 
						} else { 
							$files[] = $dir.'/'.$file; 
						} 
					} 
				} 
				closedir($handle); 
			} 		
		}
		return $this->_array_flat($files); 
	} 

	/** 
	 * Flatten an array of arrays into one array 
	 **/	
	function _array_flat($array) { 
		$tmp = array();
		foreach($array as $a) { 
			if(is_array($a)) { 
				$tmp = array_merge($tmp, $this->_array_flat($a)); 
			} else { 
				$tmp[] = $a; 
			} 
		} 
		return $tmp; 
	} 

}
