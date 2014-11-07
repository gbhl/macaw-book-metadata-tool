<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Common Library
 *
 * MACAW Metadata Collection and Workflow System
 *
 * This contains a few routines that are used throughout the system. This
 * includes checking whether a user is logged in, whether a book has been
 * selected.
 *
 *
 * @package admincontroller
 * @author Joel Richard
 * @version 1.0 admin.php created: 2010-07-07 last-modified: 2010-08-19

	Change History
	Date        By   Note
	------------------------
	2010-07-07  JMR  Created
	2011-03-21  JMR  Updated get_largest_image() to look only at geometry, not everthing.

 **/

class Common extends Controller {

	var $CI;
	var $cfg;

	function Common() {
		$this->CI = get_instance();
		$this->CI->load->library('session');
		$this->cfg = $this->CI->config->item('macaw');
		// Make sure things are in order.
		$this->validate_config();
	}

	/**
	 * Check the session
	 *
	 * Makes sure a user is logged in and takes appropriate action if the user is
	 * not logged in. Sets an error message in the session if we are not logged
	 * in. If we are in an AJAX situation, we output some encoded JSON to
	 * (hopefully) cause the Javascript to redirect to the login page. If we are
	 * not in an AJAX situation, then we directly redirect to the login page. We
	 * also assume that the login page has code to display the session error
	 * message.
	 *
	 * @access public
	 * @param boolean [$ajax] Are we AJAX or not?
	 * @return boolean Whether or not the user is logged in.
	 *
	 */
	function check_session($ajax = false) {
		if (!$this->CI->session->userdata('logged_in')) {
			$this->CI->session->set_userdata('errormessage', 'Your session has expired. Please login again.');
			if ($ajax) {
				$this->ajax_headers();
				echo json_encode(array('redirect' => $this->CI->config->item('base_url').'login'));
				return false;
			} else {
				redirect($this->CI->config->item('base_url').'login');
			}
		}
		return true;
	}

	/**
	 * Get info about a book
	 *
	 * Looks to the session to get a user's current book information
	 * (author and title). Called only from the "pagetop" view.
	 *
	 * @access public
	 */
	function get_book_info($notags = false) {
		$title = $this->CI->session->userdata('title').'';
		$author = $this->CI->session->userdata('author').'';
		
		if ($title == '') {
			$title = '<em>(no title)</em>';
		}
		if ($notags) {
			return $title.' : '.$author;
		} else {
			return "<h2>$title</h2>".($author != '' ? '<h3>'.$author.'</h3>' : '');
		}
	}

	/**
	 * Get the hostname from an IP
	 *
	 * Given an ip address, get the hostname for it. This is an OS X / Linux
	 * specific command. This will not work on Windows or Solaris (my heart goes
	 * out to you.)
	 *
	 * @access private
	 * @param string [$ip] An IP Address of some remote server.
	 *
	 * @todo See if we can make this work for windows. We'd need to know we are windows, first. Ugh.
	 */
	function _get_host($ip) {
		//Make sure the input is not going to do anything unexpected
		//IPs must be in the form x.x.x.x with each x as a number
		if (preg_match('/^(?:25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)(?:[.](?:25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)){3}$/', $ip)) {
			$host = `host $ip`;
			$host = preg_replace('/\.$/', '', $host);
			return trim((($host ? end ( explode (' ', $host)) : $ip)));
		} else {
			return false;
		}
	}

	/**
	 * Validate Macaw configuration
	 *
	 * Some code to make sure that our custom configuration files are as we
	 * expect them to be. Namely this means checking the "export_modules"
	 * to be sure that the associated files we expect to find are there.
	 *
	 * @access public
	 * @throws Causes web-page error to be displayed.
	 */
	function validate_config() {

		// Clean all paths in the config to eliminate trailing slashes
		$this->cfg['base_directory'] = preg_replace('/\/+$/', '', $this->cfg['base_directory']);
		$this->cfg['logs_directory'] = preg_replace('/\/+$/', '', $this->cfg['logs_directory']);
		$this->cfg['data_directory'] = preg_replace('/\/+$/', '', $this->cfg['data_directory']);
// 		$this->cfg['purge_directory'] = preg_replace('/\/+$/', '', $this->cfg['purge_directory']);
		$this->cfg['plugins_directory'] = preg_replace('/\/+$/', '', $this->cfg['plugins_directory']);

		// Make sure that the incoming directory is not the same as "data_directory" in the general conf.
		if ($this->cfg['incoming_directory'] == $this->cfg['data_directory']) {
			show_error('The configuration parameters "incoming_directory" and "data_directory" are the same. This <b>will</b> cause trouble. Please edit the configuration and change one of them.');
		} else {
			if (!file_exists($this->cfg['incoming_directory'])) {
				show_error('Could not find the incoming directory: '.$this->cfg['incoming_directory']);
			} else {
				// Make sure that we can write to the incoming directory
				if ($this->cfg['incoming_directory'] && !is_writable($this->cfg['incoming_directory'])) {
					show_error('Cannot write to the incoming directory: '.$this->cfg['incoming_directory']);
				}
			}
		}

		// Check to be sure the metadata class is available
		if (!file_exists($this->cfg['plugins_directory'])) {
			show_error('The plugins directory "'.$this->cfg['plugins_directory'].'" could not be found and is required.');
		}
		if (!file_exists($this->cfg['plugins_directory'].'/import/')) {
			show_error('The plugin import directory "'.$this->cfg['plugins_directory'].'/import/" could not be found and is required.');
		}
		if (!file_exists($this->cfg['plugins_directory'].'/export/')) {
			show_error('The plugin export directory "'.$this->cfg['plugins_directory'].'/export/" could not be found and is required.');
		}

		// Validate the import modules
		//print_r($this->cfg);
		if (!array_key_exists('import_modules', $this->cfg)) {
//			show_error('The import module configuration parameter could not be found. Please make sure $config[\'macaw\'][\'import_modules\'] is defined.');
		} else {
			$modules = $this->cfg['import_modules'];
			foreach ($modules as $p) {
				if (!file_exists($this->cfg['plugins_directory'].'/import/'.$p.EXT)) {
					show_error('The import module "'.$p.EXT.'" is configured but could not be found: '.$this->cfg['plugins_directory'].'/import/'.$p.EXT);
				}
			}
		}


		// Validate the export modules
		if (!array_key_exists('export_modules', $this->cfg)) {
//			show_error('The export module configuration parameter could not be found. Please make sure $config[\'macaw\'][\'export_modules\'] is defined.');
		} else {
			$modules = $this->cfg['export_modules'];
			foreach ($modules as $p) {
				if (!file_exists($this->cfg['plugins_directory'].'/export/'.$p.EXT)) {
					show_error('The export module "'.$p.EXT.'" is configured but could not be found: '.$this->cfg['plugins_directory'].'/export/'.$p.EXT);
				}
			}
		}
		
		// Validate that the log files can be written to
		$ret = $this->validate_log_config();
		if ($ret) {
			show_error('Permission denied to write to file or directory: '.$ret.'. Please make sure the logs directory and all files are accesible to the web server user.');
		}
	}

	/**
	 * Validate that Macaw can write to logs directory
	 *
	 * Ugly errors happen when we can't write to the logs. Let's check this whenever we want
	 * (usually where the ugliness can happen) and optionally give it a barcode to see if we can
	 * write to that item's log file.
	 *
	 *
	 * @access public
	 * @throws Causes web-page error to be displayed.
	 */
	function validate_log_config($barcode = '') {
		// Make sure the log directories and files can be written to
		$path = $this->cfg['logs_directory'];
		if (!is_writable($path)) {
			return $path;
		}

		// Can we write to the error log?
		$fname = 'macaw_error.log';
		if ($this->cfg['error_log']) {
			$fname = strftime($this->cfg['error_log']);
		}
		if (file_exists($path.'/'.$fname)) {
			if (!is_writable($path.'/'.$fname)) { 
				return $fname;
			}
		}

		// Can we write to the activity log?
		$fname = 'macaw_activity.log';
		if ($this->cfg['activity_log']) {
			$fname = strftime($this->cfg['activity_log']);
		}
		if (file_exists($path.'/'.$fname)) {
			if (!is_writable($path.'/'.$fname)) { 
				return $fname;
			}
		}

		// Can we write to the cron log?
		$fname = 'macaw_cron.log';
		if ($this->cfg['cron_log']) {
			$fname = strftime($this->cfg['cron_log']);
		}
		if (file_exists($path.'/'.$fname)) {
			if (!is_writable($path.'/'.$fname)) { 
				return $fname;
			}
		}

		// Can we write to the access log?
		$fname = 'macaw_access.log';
		if ($this->cfg['access_log']) {
			$fname = strftime($this->cfg['access_log']);
		}
		if (file_exists($path.'/'.$fname)) {
			if (!is_writable($path.'/'.$fname)) { 
				return $fname;
			}
		}


		// Can we write to the book logs directory?
		if (!is_writable($path.'/books/')) {
			return $path.'/books/';
		}
		// If we have a barcode, can we write to the log file for that item?
		if ($barcode != '') {
			if (file_exists($path.'/books/'.$barcode.'.log')) {
				if (!is_writable($path.'/books/'.$barcode.'.log')) {
					return $path.'/books/'.$barcode.'.log';
				}
			}
		}
		return false;
	}

	/**
	 * Standard AJAX headers
	 *
	 * Sets some standard headers that are suitable for not caching the page and
	 * for pages that return JSON code.
	 *
	 * @access public
	 */
	function ajax_headers() {
        header("Content-Type: application/json; charset=utf-8");
        header("Pragma: no-cache");
        header("Cache-Control: no-cache");
        header("Expires: ".standard_date('DATE_RFC822', time()));
	}

	/**
	 * Classify a URL as global or not
	 *
	 * Some pages, like the Dashboard, Account Edit, and some Log listings, are
	 * not associated with an individual item so they shouldn't display the
	 * name of the item in the top part of the page. We compare the first part
	 * of the URL to a list of known pages to identify which is which and return
	 * whether the page is "global" or not.
	 *
	 * @access public
	 */
	function is_global_page($page, $subpage = null) {
		if ($page == 'dashboard' || $page == 'admin') {
		    return true;
		} elseif ($page == 'main' && $subpage == null) {
			return true;
		}
		return false;
	}

	function get_largest_image($img) {
		$img->setLastIterator();

		$largest = 0;
		$size = 0;
		$tmp_size = 0;
		$count = $img->getNumberImages();

		// figure out which one is the largest
		for($i = 0; $i <= $count-1; $i++) {
			$img->previousImage();
			$info = $img->getImageGeometry();
			$tmp_size = $info['width'] * $info['height'];
			if ($tmp_size > $size) {
				$largest = $i;
				$size = $tmp_size;
			}
		}

		// Now that we've identified the largest image, go to it.
		$img->setLastIterator();
		for($i = 0; $i <= $largest; $i++) {
			$img->previousImage();
		}
	}

	/**
	 * Safely determine if a file can be copied
	 *
	 * INTERNAL: If a file is changing, then we don't want to touch it until it
	 * is done. This function repeatedly compares the size of the file
	 * to return TRUE when the file size has stabilizes. By default, we
	 * will check for 60 seconds and if the file size doesn't change for
	 * 5 consecutive seconds, it will return TRUE. After 60 seconds, we
	 * return FALSE. It's up to the calling code to decide what to do.
	 *
	 * @param string [$fname] The full path+filename of the file in question
	 * @param int [$stability] Number of seconds until file is stable
	 * @param int [$timeout] Number of seconds before we give up.
	 * @since Version 1.0
	 */
	function is_file_stable($fname, $stability = 5, $timeout = 60) {
		if (file_exists($fname)) {
			$count = $timeout;
			$ok_count = 0;

			// Get the initial size of the file
			$fsize = filesize($fname);
			clearstatcache(false, $fname);

			while ($count > 1 && $ok_count < $stability) {
				// Get the size of the file
				$newsize = filesize($fname);
				clearstatcache(false, $fname);

				// Has it changed?
				if ($fsize == $newsize) {
					// File size is the same, so we start counting
					$ok_count++;
					if ($ok_count < $stability) { sleep(1); }
				} else {
					// File size has changed, start over.
					$ok_count = 0;
					$fsize = $newsize;
					if ($ok_count < $stability) { sleep(1); }
				}
				$count  = $count - 1;
			}
			if ($ok_count > ($stability-1)) {
				return true;
			} else {
				return false;
			}

		} else {
			throw new Exception('File not found!');
		}
	}

	/**
	 * Convert MARC XML to MODS XML
	 *
	 * Using the included MARC21-MODS3.3 XSL, convert MARC to MODS
	 *
	 * @param string [$text] The MARC XML to be converted
	 */
	function marc_to_mods($text) {
		$xml = new DOMDocument;
		$xsl = new DOMDocument;
		$proc = new XSLTProcessor;
		$ret = $xml->loadXML($text);    // Load the MARC XML to convert to MODS
		
		if ($ret) {
			$xsl->load($this->CI->config->item('base_url').'inc/xslt/MARC21slim2MODS3-3.xsl');	// Get our XSL file from the LOC
			$proc->importStyleSheet($xsl); 					// attach the xsl rules
			$tx = $proc->transformToXML($xml);
			return $tx;										// Transform the MARC to MODS
		} else {
			throw new Exception("Unable to parse MARCXML data");
			print "Unable to parse MARCXML data\n";
			return null;
		}
	}

	/**
	 * Determine if Macaw needs an upgrade and if so, update it.
	 *
	 * Makes sure that we have a settings table. If it's not there. It
	 * creates it. Then it looks to the settings table to see if we have 
	 * a "version" entry. If we do, then we can decide how we want to 
	 * upgrade. The upgrade instructions are in a SQL file that should be 
	 * included with Macaw
	 *
	 * No Parameters.
	 */
	function check_upgrade() {
		// Check to see if we have the settings table
		if ($this->CI->db->dbdriver == 'postgre') {
			$q = $this->CI->db->query("select * from pg_tables where tablename = 'settings';");
			$row = $q->result();
			if (count($row) == 0) {
				$this->CI->db->query("create table settings (name varchar(64), value varchar(64))");
			}		
		} elseif ($this->CI->db->dbdriver == 'mysql') {
			$q = $this->CI->db->query("SELECT * FROM information_schema.tables WHERE table_schema = '".$this->CI->db->database."' AND table_name = 'settings' LIMIT 1;");
			$row = $q->result();
			if (count($row) == 0) {
				$this->CI->db->query("create table settings (name varchar(64), value varchar(64))");
			}		
		}

		// Do we have a version?
		$q = $this->CI->db->query("select value from settings where name = 'version';");
		$row = $q->result();
		$version = '';
		if (count($row)) {
			$version = $row[0]->value;
		}

		if (!$version) {
			// We have no version, so we assume that we are version 1.7 
			// So we upgrade to 1.7 and record that as the version.
			$this->CI->db->insert('settings', array('name' => 'version', 'value' => '1.7'));
			$queries = null;
			if ($this->CI->db->dbdriver == 'postgre') {
				$queries = file_get_contents($this->cfg['base_directory'].'/system/application/sql/macaw-pgsql-1.7.sql');
			} elseif ($this->CI->db->dbdriver == 'mysql') {
				$queries = file_get_contents($this->cfg['base_directory'].'/system/application/sql/macaw-mysql-1.7.sql');
			}
			try {
				foreach (explode(';', $queries) as $q) {
					if (preg_match('/[^\s\r\n]+/', $q)) {
						$result = $this->CI->db->query($q);
					}
				}
 			} catch (Exception $e) {
 				echo "Exception";
 			}
 			
		} elseif ($version == "1.7") { 
			$queries = null;
			if ($this->CI->db->dbdriver == 'postgre') {
				$queries = file_get_contents($this->cfg['base_directory'].'/system/application/sql/macaw-pgsql-2.0.sql');
			} elseif ($this->CI->db->dbdriver == 'mysql') {
				$queries = file_get_contents($this->cfg['base_directory'].'/system/application/sql/macaw-mysql-2.0.sql');
			}
			foreach (explode(';', $queries) as $q) {
				if (preg_match('/[^\s\r\n]+/', $q)) {
					$result = $this->CI->db->query($q);
				}
			}
			$this->CI->db->where('name','version');
			$this->CI->db->set('value', '2.0');
			$this->CI->db->update('settings');

		} elseif ($version == "2.0") {
			$queries = null;
			if ($this->CI->db->dbdriver == 'postgre') {
				$queries = file_get_contents($this->cfg['base_directory'].'/system/application/sql/macaw-pgsql-2.1.sql');
			} elseif ($this->CI->db->dbdriver == 'mysql') {
				$queries = file_get_contents($this->cfg['base_directory'].'/system/application/sql/macaw-mysql-2.1.sql');
			}
			foreach (explode(';', $queries) as $q) {
				if (preg_match('/[^\s\r\n]+/', $q)) {
					$result = $this->CI->db->query($q);
				}
			}
			$this->CI->db->where('name','version');
			$this->CI->db->set('value', '2.1');
			$this->CI->db->update('settings');
		}
	}
	
	/**
	 * Warn the user about metadata that might be missing
	 *
	 * Decide if the book is missing some metadata based on the requirements
	 * specified in the configuration file. If so, we set a warning in the session.
	 *
	 * @param Book [$book] The book object we want to check
	 */
	function check_missing_metadata($book) {
		if ($this->CI->uri->segment(1) == 'scan' || $this->CI->uri->segment(1) == 'main') { 
			$missing_metadata =  $book->get_missing_metadata(true);
			$msg = '';
			if (count($missing_metadata) > 0) {
				$msg .= 'Some metadata fields are missing. They must be filled in before you can export to other systems.<br/>';
				foreach ($missing_metadata as $module => $fields) {
					$msg .= 'Export to <strong>'.str_replace('_', ' ', $module).'</strong> is missing: '.implode(', ', $fields).'<br/>';
				}
			}
			if ($msg != '') {
				$this->CI->session->set_userdata('warning', $msg);
			}
		}	
	}

	/**
	 * Export a book to a downloadable file
	 *
	 * @param string [$barcode] The barcode of the item we want to export
	 */
	function serialize($barcode) {
		if (!$barcode) {
			throw new Exception("Please supply a barcode.");
		}
		if (!$this->CI->book->exists($barcode)) {
			throw new Exception("Item not found with barcode $barcode.");
		}
		
		$tmp = $this->CI->cfg['data_directory'];
				
		if (!file_exists($tmp.'/import_export')) {
			mkdir($tmp.'/import_export');
		}
		if (!is_writable($tmp.'/import_export')) {
			throw new Exception("Permission denied to write to ".$tmp.'/import_export');
		}

		if (!file_exists($tmp.'/import_export/serialize/')) {
			mkdir($tmp.'/import_export/serialize/');
		}		
		if (!is_writable($tmp.'/import_export/serialize/')) {
			throw new Exception("Permission denied to write to ".$tmp.'/import_export/serialize/');
		}

		if (!file_exists($tmp.'/import_export/serialize/'.$barcode)) {
			mkdir($tmp.'/import_export/serialize/'.$barcode);
		}		
		if (!is_writable($tmp.'/import_export/serialize/'.$barcode)) {
			throw new Exception("Permission denied to write to ".$tmp.'/import_export/serialize/'.$barcode);
		}

		# 1. Get the item information
		$query = $this->CI->db->query('select * from item where barcode = ?', array($barcode));
		$item = $query->result();
		$id = $item[0]->id;
		write_file($tmp.'/import_export/serialize/'.$barcode.'/item.dat', serialize((array)$item[0]));

		# 2. Get the item_export_status information
		$query = $this->CI->db->query('select * from item_export_status where item_id = ?', array($id));
		$item_export_status = $query->result();
		write_file($tmp.'/import_export/serialize/'.$item_export_status.'/item_export_status.dat', serialize((array)$item_export_status));

		# 3. Get the page information
		$query = $this->CI->db->query('select * from page where item_id = ?', array($id));
		$page = $query->result();
		for ($i = 0; $i < count($page); $i++) {
			$page[$i] = (array)$page[$i];
		}
		write_file($tmp.'/import_export/serialize/'.$barcode.'/page.dat', serialize($page));

		# 4. Get the metadata information
		$query = $this->CI->db->query('select * from metadata where item_id = ?', array($id));
		$metadata = $query->result();
		for ($i = 0; $i < count($metadata); $i++) {
			$metadata[$i] = (array)$metadata[$i];
		}
		write_file($tmp.'/import_export/serialize/'.$barcode.'/metadata.dat', serialize($metadata));

		# 5. Gather the files
		$files = array('marc.xml','mods.xml', 'thumbs', 'preview', 'scans');		
		foreach ($files as $f) {
 			if (file_exists($this->CI->cfg['data_directory'].'/'.$barcode.'/'.$f)) {
				system('cp -r '.$this->CI->cfg['data_directory'].'/'.$barcode.'/'.$f.' '.$tmp.'/import_export/serialize/'.$barcode.'/.');
			} else if  (file_exists($this->CI->cfg['data_directory'].'/archive/'.$barcode.'/'.$f)) {
				system('cp -r '.$this->CI->cfg['data_directory'].'/archive/'.$barcode.'/'.$f.' '.$tmp.'/import_export/serialize/'.$barcode.'/.');
			}
		}
		# tar things up
		system('cd '.$tmp.'/import_export/serialize && tar fcz '.$barcode.'.tgz '.$barcode);
		system('rm -r '.$tmp.'/import_export/serialize/'.$barcode);
		return "$tmp/import_export/serialize/".$barcode.".tgz";
	}

	/**
	 * Email an error message to the admins
	 *
	 * @param string [$message] The message of the email. 
	 */
	function email_error($message) {
		$this->email_admin($message, 'Error Notification', true);
	}
	
	function email_admin($message = '', $subject = 'Notification', $error = false) {
		if ($message != '') {
			$this->CI->load->library('email');
	
			$config['protocol'] = 'smtp';
			$config['crlf'] = '\r\n';
			$config['newline'] = '\r\n';
			$config['smtp_host'] = $this->cfg['email_smtp_host'];
			$config['smtp_port'] = $this->cfg['email_smtp_port'];
			if ($this->cfg['email_smtp_user']) { $config['smtp_user'] = $this->cfg['email_smtp_user']; }
			if ($this->cfg['email_smtp_pass']) { $config['smtp_pass'] = $this->cfg['email_smtp_pass']; }
			
			$this->CI->email->initialize($config);
			$this->CI->email->from($this->cfg['admin_email'], 'MACAW Admin');
			$this->CI->email->to($this->cfg['admin_email']);
			$this->CI->email->subject('[Macaw] '.$subject);
			$msg = 'This is a message from the MACAW server located at: '.$this->CI->config->item('base_url')."\r\n\r\n";
			if ($error) {
				$msg .= 'The following error occurred, most likely during a cron run: '."\r\n\r\n";
			}
			$msg .= $message;
			$this->CI->email->message($msg);
			$this->CI->email->send();
		}
	}

	/**
	 * Generate yesterday's statistics
	 *
	 * This is called from multiple places, so it abstracted here.
	 * Calcualtes the number of pages scanned, pages per day and disk space used.
	 */
	function run_statistics() {

		if ($this->CI->db->dbdriver == 'postgre') {
			// Has this statistic already been generated
			$q = $this->CI->db->query("SELECT * FROM (logging) WHERE date = date(now() - interval 1 day) AND statistic = 'pages';");
			$found = false;
			foreach ($q->result() as $row) {
				$found = true; continue;
			}
			if (!$found) {
				// 1. Get the number of pages from yesterday
				$this->CI->db->query(
					"insert into logging (date, statistic, value) values (
						date_trunc('d', now() - interval '1 day') ,
						'pages',
						(select count(*)
						from page
						where created between date_trunc('d', now() - interval '1 day')
													and date_trunc('d', now() + interval '1 day'))
					)"
				);
			}
		
			// Has this statistic already been generated
			$q = $this->CI->db->query("SELECT * FROM (logging) WHERE date = date(now() - interval 1 day) AND statistic = 'total-pages';");
			$found = false;
			foreach ($q->result() as $row) {
				$found = true; continue;
			}
			if (!$found) {
				// 2. Get the total number of pages scanned
				$this->CI->db->query(
					"insert into logging (date, statistic, value) values (
						date_trunc('d', now() - interval '1 day') ,
						'total-pages',
						(select count(*) from page)
					)"
				);
			}
			
			// Has this statistic already been generated
			$q = $this->CI->db->query("SELECT * FROM (logging) WHERE date = date(now() - interval 1 day) AND statistic = 'disk-usage';");
			$found = false;
			foreach ($q->result() as $row) {
				$found = true; continue;
			}
			if (!$found) {
				// 3. Get the number of bytes used in the /books/ directory
				$output = array();
				$matches = array();
				exec('df -k '.$this->CI->cfg['data_directory'], $output);
				$pct = preg_match('/\b([0-9]+)\%/', $output[1], $matches);
				
				$this->CI->db->query(
					"insert into logging (date, statistic, value) values (
						date_trunc('d', now() - interval '1 day') ,
						'disk-usage',
						".$matches[1]."
					)"
				);
				
// Changed this to use percent, rather than actual bytes.				
// 				$bytes = preg_match('/^.*? +\d+ (\d+)/', $output[1], $matches);
// 				$this->CI->db->query(
// 					"insert into logging (date, statistic, value) values (
// 						date_trunc('d', now() - interval '1 day') ,
// 						'disk-usage',
// 						".($matches[1] * 1024)."
// 					)"
// 				);
			}
		} elseif ($this->CI->db->dbdriver == 'mysql') {
			// Has this statistic already been generated
			$q = $this->CI->db->query("SELECT * FROM (logging) WHERE date = date(now() - interval 1 day) AND statistic = 'pages';");
			$found = false;
			foreach ($q->result() as $row) {
				$found = true; continue;
			}
			if (!$found) {
				// 1. Get the number of pages from yesterday
				$this->CI->db->query(
					"insert into logging (date, statistic, value) values (
						date(now() - interval 1 day) ,
						'pages',
						(select count(*)
						from page
						where created between extract(day from now() - interval 1 day)
													and extract(day from  now() + interval 1 day))
					)"
				);			
			}

			// Has this statistic already been generated
			$q = $this->CI->db->query("SELECT * FROM (logging) WHERE date = date(now() - interval 1 day) AND statistic = 'total-pages';");
			$found = false;
			foreach ($q->result() as $row) {
				$found = true; continue;
			}
			if (!$found) {
				// 2. Get the total number of pages scanned
				$this->CI->db->query(
					"insert into logging (date, statistic, value) values (
						date(now() - interval 1 day) ,
						'total-pages',
						(select count(*) from page)
					)"
				);
			}
			
			// Has this statistic already been generated
			$q = $this->CI->db->query("SELECT * FROM (logging) WHERE date = date(now() - interval 1 day) AND statistic = 'disk-usage';");
			$found = false;
			foreach ($q->result() as $row) {
				$found = true; continue;
			}
			if (!$found) {
				// 3. Get the number of bytes used in the /books/ directory
				$output = array();
				$matches = array();
				exec('df -k '.$this->CI->cfg['data_directory'], $output);
				$pct = preg_match('/\b([0-9]+)\%/', $output[1], $matches);
				
				$this->CI->db->query(
					"insert into logging (date, statistic, value) values (
						date(now() - interval 1 day) ,
						'disk-usage',
						".$matches[1]."
					)"
				);
// 
// 				exec('df -k '.$this->CI->cfg['data_directory'], $output);
// 				$bytes = preg_match('/^.*? +\d+ (\d+)/', $output[1], $matches);
// 				$this->CI->db->query(
// 					"insert into logging (date, statistic, value) values (
// 						date(now() - interval 1 day) ,
// 						'disk-usage',
// 						".($matches[1] * 1024)."
// 					)"
// 				);
			}

		}
		$matches = array();

		exec('df -k '.$this->CI->cfg['data_directory'], $output);

		$pct = preg_match('/\b([0-9]+)\%/', $output[1], $matches);

		if ((int)$matches[1] > 80) {
			$this->email_admin('DISK USAGE IS AT '.$matches[1].' PERCENT!', 'Disk Space Notification');
		}
	}
	

	function trim_utf8_bom($data){ 
		if(substr($data, 0, 3) == pack('CCC', 239, 187, 191)) {
			return substr($data, 3);
		}
		return $data;
	}

	function trim_utf16_bom($data){ 
		$bom = pack("CCC", 0xef, 0xbb, 0xbf);
		if (0 === strncmp($data, $bom, 3)) {
				$return = substr($data, 3);
		}	
		return $data;
	}

	function trim_bom($data){ 
		if (ord(substr($data,0)) == 255 && ord(substr($data,1)) == 254) {
				$data = substr($data, 2);
		}
		return $data;
	}

	function is_utf16($str) {
		if (strlen($str) > 2) {
			$c0 = ord($str[0]);
			$c1 = ord($str[1]);
	
			if ($c0 == 0xFE && $c1 == 0xFF) {
					return true;
			} else if ($c0 == 0xFF && $c1 == 0xFE) {
					return true;
			}
		}	
		return false;
	}
	
	
	function utf16_to_utf8($str) {
		if (strlen($str) > 2) {
			$c0 = ord($str[0]);
			$c1 = ord($str[1]);
		
			if ($c0 == 0xFE && $c1 == 0xFF) {
				$be = true;
			} else if ($c0 == 0xFF && $c1 == 0xFE) {
				$be = false;
			} else {
				return $str;
			}
		
			$str = substr($str, 2);
			$len = strlen($str);
			$dec = '';
			for ($i = 0; $i < $len; $i += 2) {
				if (isset($str[$i]) && isset($str[$i + 1])) {
					$c = ($be) ? ord($str[$i]) << 8 | ord($str[$i + 1]) : ord($str[$i + 1]) << 8 | ord($str[$i]);
					if ($c >= 0x0001 && $c <= 0x007F) {
						$dec .= chr($c);
					} else if ($c > 0x07FF) {
						$dec .= chr(0xE0 | (($c >> 12) & 0x0F));
						$dec .= chr(0x80 | (($c >>  6) & 0x3F));
						$dec .= chr(0x80 | (($c >>  0) & 0x3F));
					} else {
						$dec .= chr(0xC0 | (($c >>  6) & 0x1F));
						$dec .= chr(0x80 | (($c >>  0) & 0x3F));
					}
				}
			}
			return $dec;
		} 
		return $str;
	}

}


