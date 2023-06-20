<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Book Model
 *
 * Macaw Metadata Collection and Workflow System - Macaw
 *
 * The book model contains useful functions for getting and saving data about
 * a book. Typically this will save to the database and later XML will be
 * created from the database itself.
 *
 * This represents a book in the database. Function-based to update/get
 * information from the database. All data is returned in JSON format. No
 * HTML is allowed.
 *
 * @package admincontroller
 * @author Joel Richard
 * @version 1.0 admin.php created: 2010-07-07 last-modified: 2010-08-19
 * 
 * 	Change History
 * 	Date        By   Note
 * 	------------------------
 * 	2010-07-07  JMR  Created
 * 
 **/

require_once(APPPATH.'libraries/Image_IPTC.php');

class Book extends Model {

	/** 
	 * @var string [$id] the ID of the item record
	 * @var string [$barcode] The barcode of the item
	 * @var string [$status] The current status of the item
	 * @var string [$pages_found]
	 * @var string [$pages_scanned]
	 * @var string [$scan_time]
	 * @var string [$needs_qa]
	 * @var string [$ia_ready_images]
	 * @var string [$page_progression]
	 * @internal string [$cfg] The Macaw configuration object
	 */
	public $id = '';
	public $org_id = '';
	public $barcode = '';
	public $status  = '';
	public $pages_found = '';
	public $pages_scanned = '';
	public $scan_time = '';
	public $needs_qa = '';
	public $last_error = '';
	public $org_name = '';
	public $date_review_end = '';
	public $ia_ready_images = '';
	public $page_progression = '';
	public $total_mbytes = 0;
	
	var $metadata_array = array();

	public $cfg;

	function __construct() {
		// Call the Model constructor
		parent::__construct();
		$this->load->helper('file');
		$this->cfg = $this->config->item('macaw');
		$this->CI = get_instance();
		$this->CI->load->library('session');
	}

	/**
	 * Load an item from the database
	 *
	 * Given a barcode, loads a book into memory. (What gets loaded? Book info,
	 * metadata, scanning location, stuff from the database). If the book is
	 * not found in our database, we return an error (presumably so we can
	 * call the /scan/_initialize_book/ function to do some pre-scanning
	 * setup.)
	 *
	 * @param string [$barcode] The barcode of the item in question
	 */
	function load($barcode = '') {
		// Prevent Blind SQL Injection
		$barcode = $this->db->escape_str($barcode);
		if (isset($barcode)) {

			// Query the database for the barcode
			$this->db->select('item.*, organization.name as org_name');
			$this->db->where('barcode', "$barcode");
			$this->db->join('organization', 'item.org_id = organization.id', 'left');
			$item = $this->db->get('item');

			// Did we get a record?
			if (!$this->exists($barcode)) {
				// No record, present an error
				$this->last_error = "The item \"$barcode\" could not be found.";
				throw new Exception($this->last_error);

			} else {
				// Yes, get the record, but we also need to get other things
				$row = $item->row();

				$this->barcode       = $row->barcode;
				$this->status        = $row->status_code;
				$this->id            = $row->id;
				$this->org_id        = $row->org_id;
				$this->org_name      = $row->org_name;
				$this->pages_found   = $row->pages_found;
				$this->pages_scanned = $row->pages_scanned;
				$this->scan_time     = $row->scan_time;
				$this->date_review_end = $row->date_review_end;
				
				if ($row->needs_qa == 't' || $row->needs_qa == '1') { 
					$this->needs_qa = true;
				} else {
					$this->needs_qa = false;
				}
				if ($row->ia_ready_images == 't' || $row->ia_ready_images == '1') { 
					$this->ia_ready_images = true;
				} else {
					$this->ia_ready_images = false;
				}
				$this->page_progression    = $row->page_progression;
				$this->total_mbytes        = $row->total_mbytes;
				$this->metadata_array      = $this->_populate_metadata();

				// Creates the directories to store our files,
				$path = $this->cfg['data_directory'].'/'.$barcode;
				if (!file_exists($path)) { mkdir($path, 0775); }
				if (!file_exists($path.'/scans')) { mkdir($path.'/scans', 0775); }
				if (!file_exists($path.'/thumbs')) { mkdir($path.'/thumbs', 0775); }
				if (!file_exists($path.'/preview')) { mkdir($path.'/preview', 0775); }
			}

		} else {
			// TODO: Raise an error of some sort
			$this->last_error = "The barcode was not supplied.";
			throw new Exception($this->last_error);
		}
	}

	/**
	 * Determine whether an item exists
	 *
	 * Query they database for the identiifer. If we get a non-zero
	 * number of rows, the item exists. We do not check to see if there
	 * are more than 1 row because the database is supposed to prevent this.
	 *
	 * @param string [$barcode] The barcode of the item in question
	 */
	function exists($barcode) {
		$barcode = $this->db->escape_str($barcode);
		// Query the database for the barcode
		$this->db->where('barcode', "$barcode");
		$item = $this->db->get('item');

		// Did we get a record?
		if ($item->num_rows() > 0) {
			return true;
		}	
		return false;
	}


	function page_exists($filename) {
		$filebase = preg_replace('/(.+)\.(.*?)$/', "$1", $filename);

		// Query the database for the barcode
		$this->db->where('item_id', $this->id);
		$this->db->where('filebase', $filebase);
		$page = $this->db->get('page');
		
		// Did we get a record?
		if ($page->num_rows() > 0) {
			return true;
		}	
		return false;
	
	}
	
	/**
	 * Set the status of a book
	 *
	 * Sets the status of the book as the given status. If the status is
	 * invalid, an error is raised.
	 *
	 * This also manages the date fields for when various statuses are
	 * set, making sure we don't overwrite a date that's already there.
	 *
	 * @param string [$status] The new status for the book
	 * @param boolean [$override] Should we ignore the existing status entirely?
	 */
	function set_status($status = '', $override = false) {
		// Get the status
		$this->db->where('barcode', $this->barcode);
		$q = $this->db->get('item')->result();

		if ($this->_is_valid_status('book', $status)) {
			// Make sure we can move from this status to that status.
			// generally, moving backwards is bad.
			if (!$override) {
				$this->_verify_status_change($q[0]->status_code, $status);
			}
			// Only if it hasn't changed do we update it.
			if ($q[0]->status_code != $status) {
				$this->db->where('barcode', $this->barcode);
				$data = array(
					'status_code' => $status,
				);

				// mysql is stupid. So we need to handle it special when the date field is empty.				
				if ($q[0]->date_scanning_start == '0000-00-00 00:00:00') {$q[0]->date_scanning_start = null;}
				if ($q[0]->date_scanning_end == '0000-00-00 00:00:00')   {$q[0]->date_scanning_end = null;}
				if ($q[0]->date_review_start == '0000-00-00 00:00:00')   {$q[0]->date_review_start = null;}
				if ($q[0]->date_review_end == '0000-00-00 00:00:00')     {$q[0]->date_review_end = null;}
				if ($q[0]->date_qa_start == '0000-00-00 00:00:00')       {$q[0]->date_qa_start = null;}
				if ($q[0]->date_qa_end == '0000-00-00 00:00:00')         {$q[0]->date_qa_end = null;}
				if ($q[0]->date_completed == '0000-00-00 00:00:00')      {$q[0]->date_completed = null;}
				if ($q[0]->date_export_start == '0000-00-00 00:00:00')   {$q[0]->date_export_start = null;}
				if ($q[0]->date_archived == '0000-00-00 00:00:00')       {$q[0]->date_archived = null;}

				if ($status == 'scanning'  && !$q[0]->date_scanning_start) { $data['date_scanning_start'] = date('Y-m-d H:i:s'); }
				if ($status == 'scanned'   && !$q[0]->date_scanning_end)   { $data['date_scanning_end']   = date('Y-m-d H:i:s'); }
				if ($status == 'reviewing' && !$q[0]->date_review_start)   { $data['date_review_start']   = date('Y-m-d H:i:s'); }
				// QA dates will be null when no QA is needed
				if ($status == 'qa-ready'  && !$q[0]->date_review_end)     { $data['date_review_end']     = date('Y-m-d H:i:s'); }
				if ($status == 'qa-active' && !$q[0]->date_qa_start)       { $data['date_qa_start']       = date('Y-m-d H:i:s'); }
				if ($status == 'reviewed'  &&  $q[0]->date_qa_start)       { $data['date_qa_end']         = date('Y-m-d H:i:s'); }
				if ($status == 'reviewed'  && !$q[0]->date_review_end)     { $data['date_review_end']     = date('Y-m-d H:i:s'); }
				if ($status == 'completed' && !$q[0]->date_completed)      { $data['date_completed']      = date('Y-m-d H:i:s'); }
				if ($status == 'exporting' && !$q[0]->date_export_start)   { $data['date_export_start']   = date('Y-m-d H:i:s'); }
				if ($status == 'archived'  && !$q[0]->date_archived)       { $data['date_archived']       = date('Y-m-d H:i:s'); }

				$this->db->set($data);
				$this->db->update('item');
				$this->logging->log('book', 'info', 'Status set to '.$status.'.', $this->barcode);
			}
		} else {
			$this->last_error = 'The status "'.$status.'" is invalid. Is this a misspelling? Please contact tech support. (The current status is '.$q[0]->status_code.'.)';
			throw new Exception($this->last_error);
		}
	}

	/**
	 * Track the status of the various export modules
	 *
	 * Provide a place where the export module can save a status. Only when all
	 * export modules are completed do we set the status of the item to completed.
	 *
	 * Module name is not required, only the status. We figure out the name of the
	 * calling module using debug_backtrace()
	 */
	function set_export_status($status = '') {
		// Get the name of the php file that called us. That's the module name.
		$d = debug_backtrace();
		preg_match('/^\/.+\/(.*?)\.php$/', $d[0]['file'], $m);
		$module_name = $m[1];
		// Can we continue?
		if ($module_name != '' && $status != '') {
			// See if we have a record for this module in the database already
			$this->db->where('item_id', $this->id);
			$this->db->where('export_module', $module_name);
			if ($this->db->count_all_results('item_export_status') > 0) {
				// Update the record to the new status
				$data = array('status_code' => $status);
				$this->db->where('item_id', $this->id);
				$this->db->where('export_module', $module_name);
				$this->db->update('item_export_status', $data);
				$this->set_status('exporting');
			} else {
				// Add the record with the new status
				$data = array(
					'item_id'		=> $this->id,
					'status_code'	=> $status,
					'export_module'	=> $module_name,
					'date'		    => date('Y-m-d H:i:s.u')
				);
				$this->db->insert('item_export_status', $data);
				$this->set_status('exporting');
			}
			$this->logging->log('book', 'info', 'Export status set to '.$status.'.', $this->barcode);

			// Now that we are done, check the statuses of all known modules in the table.
			// If all are 'completed', then we mark the status of the item itself as "completed"
			$this->db->where('item_id', $this->id);
			$this->db->where('status_code', 'completed');
			if ($this->db->count_all_results('item_export_status') >= count($this->cfg['export_modules'])) {
				$this->set_status('completed');
			}
			//echo $this->db->last_query()."\n";
		}
	}

	/**
	 * Get the status of one export modules
	 *
	 * Given the name of an export module, return the MOST RECENT status of that module
	 * or null if there is no status at all in the database.
	 *
	 */
	function get_export_status($module_name = '') {
		if ($module_name != '') {
			// See if we have a record for this module in the database already
			$this->db->where('item_id', $this->id);
			$this->db->where('export_module', $module_name);
			$query = $this->db->get('item_export_status');
			if ($row = $query->result()) {
				return $row[0]->status_code;
			}
		}
		return null;
	}

	/**
	 * Make sure that we can change to a status
	 *
	 * The status codes operate in order. So it's not usually a good idea
	 * to move backwards in the process. Therefore we need to check that
	 * we can move from status A to status B. If we cannot change to the
	 * new status, then we throw an error in the hopes that someone upstream
	 * will catch it. Sucky way to handle errors if you ask me.
	 */
	function _verify_status_change($old_status, $status) {
		switch ($old_status) {
			case 'new':
				if ($status != 'new' && $status != 'scanning' && $status != 'error') {
					$this->last_error = 'This item is new. Please import some pages before moving on.';
					throw new Exception($this->last_error);
				}
				break;
			case 'scanning':
				if ($status != 'scanning' && $status != 'scanned' && $status != 'error') {
					$this->last_error = 'This item\'s images are being imported. This needs to be finished before moving on.';
					throw new Exception($this->last_error);
				}
				break;
			case 'scanned':
				if ($status != 'scanning' && $status != 'scanned' && $status != 'reviewing' && $status != 'error') {
					$this->last_error = 'This item\'s images have been imported. You can only start reviewing it now.';
					throw new Exception($this->last_error);
				}
				break;
			case 'reviewing':
				if ($status != 'scanning' && $status != 'reviewing' && $status != 'qa-ready' && $status != 'reviewed' && $status != 'error') {
					$this->last_error = 'This item is being reviewed. You can only finish revewing it.';
					throw new Exception($this->last_error);
				}
				break;
			case 'qa-ready':
				if ($status != 'qa-ready' && $status != 'qa-active' && $status != 'error') {
					$this->last_error = 'This item is ready for QA. You can only check it for errors and accuracy.';
					throw new Exception($this->last_error);
				}
				break;
			case 'qa-active':
				if ($status != 'reviewed' && $status != 'qa-ready' && $status != 'qa-active' && $status != 'error') {
					$this->last_error = 'This item is in QA. You can only finish checking it.';
					throw new Exception($this->last_error);
				}
				break;
			case 'reviewed':
				if ($status != 'reviewed' && $status != 'reviewing' && $status != 'exporting' && $status != 'error') {
					$this->last_error = 'Cannot set status to "'.$status.'" when item has status "reviewed".';
					throw new Exception($this->last_error);
				}
				break;
			case 'exporting':
				if ($status != 'completed' && $status != 'exporting' && $status != 'error' && !$this->CI->user->has_permission('admin')) {
					$this->last_error = 'This item is being exported. There is nothing more than you should need to do.';
					throw new Exception($this->last_error);
				}
				break;
			case 'completed':
				if ($status != 'archived' && $status != 'error') {
					$this->last_error = 'Cannot set status to "'.$status.'" when item has status "completed".';
					throw new Exception($this->last_error);
				}
				break;
			case 'archived':
				if ($status != 'archived' && $status != 'error') {
					$this->last_error = 'Cannot set status to "'.$status.'" when item has status "archived".';
					throw new Exception($this->last_error);
				}
				break;
			case 'error':
				$this->last_error = 'Cannot set status to "'.$status.'" when item has status "error".';
				throw new Exception($this->last_error);
				break;
		}
	}

	/**
	 * Is the status valid
	 *
	 * Let's just make sure that the status is one of the ones we can use for
	 * a book or item
	 *
	 * @param string [$type] What kind of status are we checking (book|item)
	 * @param string [$s] The status code to check
	 * @return boolean Whether the code was valid or not.
	 */
	function _is_valid_status($type = 'book', $s = '') {
		if ($type == 'book') {
			if ($s == 'new'      || $s == 'scanning'  || $s == 'scanned'  ||
			    $s == 'reviewing' ||  $s == 'reviewed' || $s == 'exporting' ||
			    $s == 'qa-ready' ||  $s == 'qa-active' ||
			    $s == 'completed' || $s == 'archived' || $s == 'error') {
				return true;
			}
		} elseif ($type == 'page') {
			if ($s == 'New' || $s == 'Pending' || $s == 'Processed') {
				return true;
			}
		}
		return false;
	}


	/**
	 * Get the pages in an item
	 *
	 * Gets a list of all pages in the book, along with their metadata. Used
	 * for both monitoring the list of books and for reviewing/reordering/
	 * editing metadata. The boolean brief modifier can be used to exclude
	 * the metadata for each image, leaving on the filenames and statuses.
	 * We can also opt to get only missing pages or pages that are not marked
	 * as missing. The default is to return all pages.
	 *
	 * @param string [$order] Optional. Which fieldname to sort by
	 * @param string [$dir] Default "asc". Which direction to sort (asc|desc)
	 * @param integer [$limit] Optional. How many records to return
	 * @param boolean [$only_missing] Optional. Whether to return only pages marked as missing.
	 * @param boolean [$no_missing] Optional. Whether to suppress all pages marked as missing.
	 * @return array An array of arrays of rows indexed by fieldname
	 */
	function get_pages($order = '', $dir = 'asc', $limit = 0, $only_missing = false, $no_missing = false) {
		$thumb_path = $this->get_path('thumbnail');
		$preview_path = $this->get_path('preview');
		$scans_path = $this->get_path('original');

		// Get the pages
		$this->db->where('item_id', $this->id);
		if ($only_missing) {
			$this->db->where('is_missing = true and item_id = '.$this->id);
		} elseif ($no_missing) {
			$this->db->where('is_missing = false or is_missing is null and item_id = '.$this->id);
		} else {
			$this->db->where('item_id', $this->id);
		}
		if ($order) {$this->db->order_by($order, $dir);}
		if ($limit) {$this->db->limit($limit);}
		$this->db->order_by('sequence_number');
		$query = $this->db->get('page');
		$pages = $query->result();

		// Get the metadata for this item
		$this->db->where('item_id', $this->id);
		$query2 = $this->db->get('metadata');
		$metadata = $query2->result_array();

		// Merge the data together (don't want to use a crosstab or pivot since it's DB-specific)
		foreach ($pages as $p) {
			if (preg_match('/archive\.org\/download/', $p->filebase)) {
				$p->thumbnail = $p->filebase.'_thumb.'.$p->extension;
				$p->preview = $p->filebase.'_large.'.$p->extension;
			} else {
				// Take the filebase and convert it into the proper filenames for preview and thumbnail files
				#$p->thumbnail = $thumb_path.'/'.$p->filebase.'.'.$this->cfg['thumbnail_format'];
				$filename = $p->filebase.'.'.$this->cfg['thumbnail_format'];
				$p->thumbnail = $this->config->item('base_url').'image.php?img='.urlencode($filename).'&ext='.$this->cfg['thumbnail_format'].'&code='.$this->barcode.'&type=thumbnail';
				
				#$p->preview = $preview_path.'/'.$p->filebase.'.'.$this->cfg['preview_format'];
				$p->preview = $this->config->item('base_url').'image.php?img='.urlencode($filename).'&ext='.$this->cfg['thumbnail_format'].'&code='.$this->barcode.'&type=preview';
				
				$p->scan_filename = $p->filebase.'.'.$p->extension;
				#$p->scan = $scans_path.'/'.$p->scan_filename;
				$p->scan = $this->config->item('base_url').'image.php?img='.urlencode($p->scan_filename).'&ext='.$p->extension.'&code='.$this->barcode.'&type=original';
			}
			// Make a more human readable of "250 K" or "1.5 MB"
			$p->size = ($p->bytes < 1048576
			            ? number_format($p->bytes/1024, 0).' K'
			            : number_format($p->bytes/(1024*1024), 1).' M');

			foreach ($metadata as $row) {
				// TODO: This can't be using names of fields!!
				// It needs to be smarter and make arrays when necessary
				if ($row['page_id'] == $p->id) {
					if (isset($p->{$row['fieldname']})) {
						if (is_array($p->{$row['fieldname']})) {
							array_push($p->{$row['fieldname']}, $row['value'].'');
						} else {
							$x = $p->{$row['fieldname']};
							$p->{$row['fieldname']} = array();
							if ($x != '') {
								array_push($p->{$row['fieldname']}, $x);
							}
							array_push($p->{$row['fieldname']}, $row['value'].'');
						}
					} else {
						$p->{$row['fieldname']} = $row['value'].'';
					}

				}
			}
			$p->is_missing = ($p->is_missing ? $p->is_missing == 't' : false);
		}
		return $pages;
	}

	/**
	 * Clear all metadata for all pages
	 *
	 * When we are setting the metadata for an item's pages, we need to clear out all of
	 * the existing metadata. So we made a convenience function for it. This clears
	 * all metadata for all pages. Let me repeat. This is a DESTRUCTIVE OPERATION
	 * that clears hundreds if not thousands of records of data from the "metadata"
	 * table.
	 *
	 * @todo Investigate a smarter function that updates and deletes selectively
	 */
	function delete_page_metadata($page_id = 0) {
		// We no longer delete all metadata pages. We only delete them one by one. 
		if ($page_id == 0) { 
			return;
		}
		$this->db->query(
			'delete from metadata
			where item_id = '.$this->book->id.'
			and page_id = '.$this->db->escape($page_id).'
			and page_id is not null'
		);
	}

	function delete_page($page_id) {
		$this->db->query(
			'delete from page
			where item_id = '.$this->db->escape($this->book->id).'
			and id = '.$this->db->escape($page_id)
		);
	}


	/**
	 * Add metadata element for one page
	 *
	 * When we are adding the metadata for the pages in an item, we do it
	 * one by one. There is arguably a faster way of doing this, but this is
	 * the most reliable.
	 *
	 * @param integer [$page] The ID of the page of the item.
	 * @param string [$name] The name of the metadata field.
	 * @param string [$value] The value of the metadata field
	 * @param interger [$counter] Default 1. Used when submitting more than one of the same metadata field.
	 */
	function set_page_metadata($page, $name, $value, $counter = 1) {
		if (isset($value) && $value != '') {
			$data = array(
				'item_id'   => $this->id,
				'page_id'   => $page,
				'fieldname' => strtolower($name),
				'counter'   => $counter,
				'value'     => $value
			);

			$this->db->insert('metadata', $data);
		}
	}


	/**
	 * Get the path for a file for one page
	 *
	 * Calculates the path to where the book lives on the processing server. The
	 * config file contains three paths that contain images. The BARCODE in the
	 * path is replaced with the identifying barcode of the current book
	 *
	 * @param string [$type] What kind of path are we getting?
	 *
	 */
	function get_path($type = '') {
		$path = '';
		if ($type == 'thumbnail') {
			$path = $this->cfg['thumbnail_url'];

		} elseif ($type == 'preview') {
			$path = $this->cfg['preview_url'];

		} elseif ($type == 'original') {
			$path = $this->cfg['scans_url'];

		} else {
			$this->last_error = 'Unrecognized path type supplied';
			throw new Exception($this->last_error);
			return;
		}
		$path = preg_replace('/BARCODE/', $this->barcode, $path);
		$path = preg_replace('/^\//', '', $path);
		return $path;
	}

	/**
	 * Add a page to the item
	 *
	 * Given a filename, add it to the book, making sure that we haven't
	 * already added it. Parses the filename to remove the extension, thereby
	 * giving us the filebase with which to compare. We also pass in the bytes
	 * since we don't want to have to look it up here (and we may not know how
	 * to find the file in question from this deep in the code.)
	 *
	 * @param string [$filename] The filename of the page we are adding.
	 * @param integer [$width] The width in pixels of the image file.
	 * @param integer [$height] The height in pixels of the image file.
	 * @param integer [$bytes] The size in bytes of the file we are adding.
	 * @param string [$ext] The file extension of the file (this shouldn't be passed in, really)
	 */
	function max_sequence() {
		$this->db->select('max(sequence_number) as max_seq');
		$this->db->where('item_id', $this->id);
		$q = $this->db->get('page')->result();
		$max = $q[0]->max_seq;
		if (!$max) {$max = 0;}

		return $max;	
	}

	/**
	 * Add a page to the item
	 *
	 * Given a filename, add it to the book, making sure that we haven't
	 * already added it. Parses the filename to remove the extension, thereby
	 * giving us the filebase with which to compare. We also pass in the bytes
	 * since we don't want to have to look it up here (and we may not know how
	 * to find the file in question from this deep in the code.)
	 *
	 * @param string [$filename] The filename of the page we are adding.
	 * @param integer [$width] The width in pixels of the image file.
	 * @param integer [$height] The height in pixels of the image file.
	 * @param integer [$bytes] The size in bytes of the file we are adding.
	 * @param string [$ext] The file extension of the file (this shouldn't be passed in, really)
	 */
	function add_page($filename = '', $width = 0, $height = 0, $bytes = 0, $status = 'Processed', $missing = false, $sequence = 0) {
		// Create the filebase
		$filebase = preg_replace('/(.+)\.(.*?)$/', "$1", $filename);

		// Calculate the file extension.
		$extension = pathinfo($filename, PATHINFO_EXTENSION);

		// See if it already exists
		$this->db->where('filebase', $filebase);
		$this->db->where('item_id', $this->id);
		$this->db->from('page');

		// Well, does it?
		if ($this->db->count_all_results() == 0) {
			// Get the largest sequence that's in the database
			$max = $this->max_sequence();			
			// Page doesn't exist, add it to the database
			$data = array();
			if ($this->db->dbdriver == 'mysql' || $this->db->dbdriver == 'mysqli') {
				$data = array(
					'item_id' => $this->id,
					'filebase' => $filebase,
					'status' => $status,
					'bytes' => $bytes,
					'sequence_number' => ($sequence ? $sequence : $max + 1),
					'extension' => $extension,
					'width' => $width,
					'height'=> $height,
					'is_missing' => ($missing ? '1' : '0')
				);
			} elseif ($this->db->dbdriver == 'postgre') {
				$data = array(
					'item_id' => $this->id,
					'filebase' => $filebase,
					'status' => $status,
					'bytes' => $bytes,
					'sequence_number' => ($sequence ? $sequence : $max + 1),
					'extension' => $extension,
					'width' => $width,
					'height'=> $height,
					'is_missing' => ($missing ? 't' : 'f')
				);
			}
			$this->db->set($data);
			$this->db->insert('page');

			$this->logging->log('book', 'info', 'Added page '.$filename.'.', $this->barcode);

		} else {
			// Entry exists, what do we do here? Update the bytes.
			$data = array(
				'bytes' => $bytes,
				'extension' => $extension,
				'width' => $width,
				'height' => $height,
				'status' => $status
			);
			$this->db->where('filebase', $filebase);
			$this->db->where('item_id', $this->id);
			$this->db->set($data);
			$this->db->update('page');
		}
	}

	/**
	 * Update the status of one page
	 *
	 * Updates the status of a page of a book. Presumably called when we are
	 * importing the image and creating derivatives of the file. Since this is called
	 * from a place where we are working with the files for pages, we don't necessarily
	 * know the ID of the page in question, so this function uses the filename and not
	 * the page ID number.
	 *
	 * @param string [$filename] The filename for the page
	 * @param string [$status] The new status of the page. (New|Pending|Processed)(
	 */
	function update_page_status($filename = '', $status = '') {
		if ($this->_is_valid_status('page', $status)) {
			$filebase = preg_replace('/\.(.+)$/', '', $filename);
			$data = array('status' => $status);
			$this->db->where('filebase', $filebase);
			$this->db->where('item_id', $this->id);
			$this->db->set($data);
			$this->db->update('page');
			$this->logging->log('book', 'info', 'Updated page '.$filebase.' status to '.$status.'.', $this->barcode);
		} else {
			$this->last_error = 'Invalid page status: '.$status.'.';
			throw new Exception($this->last_error);
		}
	}

	/**
	 * Set the sequence number for a page
	 *
	 * When we are saving the metadata for the entire book, we also set the order
	 * of the pages. We do this by setting the sequence_number field on the page.
	 * (as an aside, we never change the internal ID number of a page once it's
	 * added to the system). Yes, this function is called a few hundred times when
	 * saving the book, but we're running in a transaction (at least) Keep in mind
	 * that this potentially (likely) creates duplicate sequence numbers while
	 * it's saving. For this reason, we don't have a unique identifier on the table
	 * that includes sequence_number.
	 *
	 * @param integer [$page_id] The ID number of the page in question
	 * @param integer [$seq] The new sequence number of the page.
	 */
	function set_page_sequence($page_id, $seq) {
		try {
			$this->db->where('id', $page_id);
			$this->db->set(array('sequence_number' => $seq));
			$this->db->update('page');
		} catch (Exception $e) {
			$this->logging->log('book', 'info', $e->getMessage(), $this->session->userdata('barcode'));
		}
	}

	/**
	 * Set the missing flag for a page
	 *
	 * When we are saving the metadata for the entire book, sometimes we want
	 * to reset the IS_MISSING flag on the pages, and sometimes we don't. So
	 * we offer this function to allow the calling code to control when the
	 * flag gets reset. Technically this can be used to set the missing flag
	 * but in practice we don't use it.
	 *
	 * @param integer [$page_id] The ID number of the page in question
	 * @param integer [$flag] Whether or not the page is marked as missing
	 */
	function set_missing_flag($page_id, $flag = false) {
		try {
			$this->db->where('id', $page_id);
			$this->db->set(array('is_missing' => ($flag ? 't' : null)));
			$this->db->update('page');
		} catch (Exception $e) {
			$this->logging->log('book', 'info', $e->getMessage(), $this->session->userdata('barcode'));
		}
	}


	/**
	 * Get the history of an item
	 *
	 * Gets the detailed history for a book from the log file.
	 *
	 */
	function get_history() {
		return read_file($this->cfg['logs_directory'].'/books/'.$this->barcode.'.log');
	}

	/**
	 * Get all books in the system
	 *
	 * Returns a list of all barcodes that are in our system when the $all parameter is false.
	 * If the all parameter is true, then a join is done between the item and metadata table
	 * and some metadata is returned. We attempt to return the fields:
	 * 
	 *     item_id, barcode, identifier, name, title, author
	 * 
	 * It is not possible to return 
	 * This function is needed because the search() function demands a search term. 
	 *
	 * @param boolean [$all] Whether or not to return all data from item as well as the standard metadata fields (default: false)
	 */
	function get_all_books($all = false, $org_id = 0, $status = array(), $get_size = true) {
		if ($all) {
			$select = 'max(item.id) as item_id';
			$select .= ", max(item.barcode) as barcode";
			$select .= ", max(item.status_code) as status_code";
			$select .= ", max(item.pages_found) as pages_found";
			$select .= ", max(item.pages_scanned) as pages_scanned";
			$select .= ", max(item.scan_time) as scan_time";
			$select .= ", max(item.org_id) as org_id";
			$select .= ", max(organization.name) as org_name";
			$select .= ", max(item.date_created) as date_created";
			$select .= ", max(item.date_scanning_start) as date_scanning_start";
			$select .= ", max(item.date_scanning_end) as date_scanning_end";
			$select .= ", max(item.date_review_start) as date_review_start";
			$select .= ", max(item.date_qa_start) as date_qa_start";
			$select .= ", max(item.date_qa_end) as date_qa_end";
			$select .= ", max(item.date_review_end) as date_review_end";
			$select .= ", max(item.date_export_start) as date_export_start";
			$select .= ", max(item.date_completed) as date_completed";
			$select .= ", max(item.date_archived) as date_archived";
			$select .= ", max(item.total_mbytes) as total_mbytes";

			$count = 1;
			$group = array();
			// Dynamically build the query and the joins for all of the metadata fields in the configration
			// I fully expect this to slow way down when we have thousands of books in the system.
			$this->db->join('organization','organization.id = item.org_id');
			if ($org_id > 0) {
				$this->db->where('item.org_id', $org_id);
			}
			foreach ($this->cfg['metadata_fields'] as $m) {
				// Max is a bit of a hack, but we don't know what aggregate functions we find in
				// different databases. Fingers crossed this is sufficient.
				$select .= ', max(m'.$count.'.value) as '.$m;
				$this->db->join(
					'(select item_id, coalesce(value, value_large) as value
					   from metadata
					   where fieldname = \''.$m.'\'
					   and page_id is null) m'.$count,
					'item.id = m'.$count.'.item_id',
					'left outer');
				$count++;
			}

			// Limit based on the statuses provided
			if (count($status) > 0) {
				$s = array();
				foreach ($status as $st) {
					if (in_array(strtolower($st), array('new','scanning','scanned','reviewing','qa-ready','qa-active','reviewed','exporting','completed','archived','error'))) {
						$s[] = $st;
					}
				}
				$this->db->where_in('item.status_code', $s);
			}
						
			$this->db->from('item');
			$this->db->select($select);
			$this->db->group_by('item.id');
			$this->db->order_by('lower(item.barcode)');
			$query = $this->db->get();
			
			$res = $query->result();
			if ($get_size) {
        for ($i = 0; $i < count($res); $i++) {
          if (!$res[$i]->total_mbytes) {
            $res[$i]->bytes = intval($this->_dir_size($this->cfg['data_directory'].'/'.$res[$i]->barcode))*1024;
          } else {
            $res[$i]->bytes = intval($res[$i]->total_mbytes)*1024;
          }
        }
			} else {
				for ($i = 0; $i < count($res); $i++) {
					$res[$i]->bytes = 0;
				}
			}
			return $res;
		} else {
			$this->db->select('barcode');
			$query = $this->db->get('item');
			return $query->result();
		}
	}

	function _dir_size($f) {
		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
			$size = 0;
			$size = $this->_rec_dir_size($f);
			//Convert to MB
			$size=round($size/1024, 1);
			return $size;
		} else {
			// Returns size of directory in kb
			$io = popen ( '/usr/bin/du -sk \''. $f."'", 'r' );
			$size = fgets ( $io, 4096);
			$size = substr ( $size, 0, strpos ( $size, "\t" ) );
			pclose ( $io );
			return $size;	
		}
	}

	function _rec_dir_size($f){
		$size = 0;
		foreach (glob(rtrim($f, '/').'/*', GLOB_NOSORT) as $each) 
		{
				$size += is_file($each) ? filesize($each) : $this->_rec_dir_size($each);
		}
		return $size;
	}

	/**
	 * Find books in the system
	 *
	 * Returns a list of all barcodes that are in our system based on a search
	 * term and value that are provided.
	 */
	function search($field = '', $value = '', $order = '', $where = '') {
		if (($field == '' || $value == '') && $where = '') {
			$this->last_error = "Both 'field' and 'value' OR 'where' are required when searching for books.";
			throw new Exception($this->last_error);
		} else {
			if ($where == '') {
				$this->db->where($field, $value);
			} else {
				$this->db->where($where);
			}
			if ($order) {
				$this->db->order_by($order);
			}
			$query = $this->db->get('item');
			return $query->result();
		}
	}

	/**
	 * Add a book to the system
	 *
	 * Creates a new book in the database and loads it. Also calls the subroutine
	 * to get the MARC metadata. The Item metadata is passed into the
	 * function on the four parameters.
	 *
	 * @param string [$info] An array of associative arrays (fieldname and value) of 
	 * whatever metadata to add. e.g, 
	 * 
	 *    array(
	 *      'barcode'    => '390880823743',
	 *      'title'      => 'The Origin of Species',
	 *      'author'     => 'Darwin, Charles',
	 *      'collection' => 'My Fancy Library'
	 *    );
	 * 
	 * This structure only allows for one piece of data for each metadata. However if you want to 
	 * add MULTIPLE metadata elements of the same name (which is entirely possible and allowed), 
	 * the structure must be different with everything enclosed in associative arrays:
	 * 
	 *    array(
	 *       array('fieldname' => 'barcode',    'value' => '390880823743'),
	 *       array('fieldname' => 'title',      'value' => 'The Origin of Species'),
	 *       array('fieldname' => 'author',     'value' => 'Darwin, Charles')
	 *       array('fieldname' => 'collection', 'value' => 'My Fancy Library')
	 *       array('fieldname' => 'collection', 'value' => 'Biodiversity Heritage Library')
     *    );
	 * 
	 * At a minimum, "barcode" must found in the array.
	 *
	 */
	function add($info) {
		// Prevent Blind SQL Injection
		if (isset($info['barcode'])) {
			$info['barcode'] = trim($this->db->escape_str($info['barcode']));
		}
		if (isset($info['identifier'])) {
			$info['barcode'] = trim($info['identifier']);
		}
		# Holding institution and contributor are equivalent
		# but we like to use contributor, so convert it back.
		if (isset($info['holding_institution'])) {
			$info['contributor'] = trim($info['holding_institution']);
			unset($info['holding_institution']); 
		}
		# "added_by" and and "scanning_institution" are equivalent
		# but we like to use scanning_institution, so convert it back.
		if (isset($info['added_by'])) {
			$info['scanning_institution'] = trim($info['added_by']);
			unset($info['added_by']); 
		}

		if ($info['barcode']) {

			// Really. We need to make sure these exists. Always.
			// How did this ever work, really?
			$path = $this->cfg['data_directory'].'/'.$info['barcode'];
			if (!file_exists($path)) { mkdir($path, 0775); }
			if (!file_exists($path.'/scans')) { mkdir($path.'/scans', 0775); }
			if (!file_exists($path.'/thumbs')) { mkdir($path.'/thumbs', 0775); }
			if (!file_exists($path.'/preview')) { mkdir($path.'/preview', 0775); }

			// If we have a barcode, let's make sure it doesn't already exist
			$this->db->where('barcode', $info['barcode']);
			$query = $this->db->get('item');

			if ($query->num_rows > 0) {
				$this->last_error = "The barcode '".$info['barcode']."' already exists.";
				throw new Exception($this->last_error);
			} else {
				if (strlen($info['barcode']) > 32) {
					$this->last_error = "The identifier is too long.";
					throw new Exception($this->last_error);
				}

				// Default this to something in case we don't have it
				if (!array_key_exists('needs_qa', $info)) {
					$info['needs_qa'] = 0;
				} else {
					if (!$this->CI->user->org_has_qa()) {
						$info['needs_qa'] = 0;									
					}
				}
				// Default this to something in case we don't have it
				if (!array_key_exists('ia_ready_images', $info)) {
					$info['ia_ready_images'] = 0;
				}
				if (!array_key_exists('page_progression', $info)) {
					$info['page_progression'] = 'ltr';
				}
				if (strtolower($info['page_progression']) != 'ltr' && strtolower($info['page_progression']) != 'rtl') {
					$info['page_progression'] = 'ltr';				
				}

				// Handle collection/collections
				$collection = array();
				if (array_key_exists('collection', $info)) {
					if (is_array($info['collection'])) {
						$collection = $info['collection'];
					} else {
						$collection = array($info['collection']);
					}
				}
				if (array_key_exists('collections', $info)) {
					$collection = array_merge((array)$collection, (array)$info['collections']);
				}
				if (array_key_exists('collection_2', $info)) {
					$collection = array_merge((array)$collection, (array)$info['collection_2']);
				}
				unset($info['collections']);
				unset($info['collection_2']);
				$info['collection'] = $collection;
				for ($i = 0; $i < count($info['collection']); $i++) {
					if ($info['collection'][$i] == '') {
						unset($info['collection'][$i]);
					}
				}

				// Create the item record in the database
				if (!isset($this->CI->user->username) || !$this->CI->user->username) {
					$this->CI->user->load('admin');
				}
				$org_id = $this->CI->user->org_id;

				if (isset($info['org_id'])) {
					if ($info['org_id']) {
						$org_id = $info['org_id'];
					}
				}

				if ($this->db->dbdriver == 'postgre') {
					$data = array(
						'barcode' => $info['barcode'],
						'status_code' => 'new',
						'date_created' => date('Y-m-d H:i:s'),
						'org_id' => $org_id,
						'needs_qa' => (($info['needs_qa'] == 1 || substr(strtolower($info['needs_qa']),0,1) == 'y') ? 't' : 'f'),
						'ia_ready_images' => (($info['ia_ready_images'] == 1 || substr(strtolower($info['ia_ready_images']),0,1) == 'y') ? 't' : 'f'),
						'page_progression' => $info['page_progression']
					);				
				} elseif ($this->db->dbdriver == 'mysql' || $this->db->dbdriver == 'mysqli') {
					$data = array(
						'barcode' => $info['barcode'],
						'status_code' => 'new',
						'date_created' => date('Y-m-d H:i:s'),
						'org_id' => $org_id,
						'needs_qa' => (($info['needs_qa'] == 1 || substr(strtolower($info['needs_qa']),0,1) == 'y') ? 1 : 0),
						'ia_ready_images' => (($info['ia_ready_images'] == 1 || substr(strtolower($info['ia_ready_images']),0,1) == 'y') ? 1 : 0),
						'page_progression' => $info['page_progression']
					);				
				}

				$this->db->insert('item', $data);
				$item_id = $this->db->insert_id();
				$this->id = $item_id;

				$marc = array();

				// Translate the 260a into a year. Because we can.
				if (isset($info['marc260c'])) {
					$matches = array();
					if (preg_match('/^(\d\d\d\d-\d\d\d\d)$/', $info['marc260c'], $matches)) {
						// Get start date and end date, make sure exactly 4 chars
						$info['year'] = $matches[1];

					} else if (preg_match('/(\d\d\d\d)/', $info['marc260c'], $matches)) {
						// Get start date, make sure exactly 4 chars
						$info['year'] = $matches[1];
					}
				}

				// This is a simple associative array
				foreach (array_keys($info) as $i) {
					if (preg_match('/^marc/i', $i) && !preg_match('/^marc_xml$/i', $i)) {
						$marc[$i] = $info[$i];
					} else {
						if ($i != 'barcode' && $i != 'identifier' && $i != 'needs_qa' && $i != 'ia_ready_images' && $i != 'page_progression'  && $i != 'org_id' && !preg_match('/[0-9]+/', $i)) {
							
							// If we got an array of data, we loop through 
							// the items and add them to the metadata.
							if (is_array($info[$i])) {
								$c = 1;
								foreach ($info[$i] as $m) {
									$this->db->insert('metadata', array(
										'item_id'   => $item_id,
										'fieldname' => $i,
										'counter'   => $c++,
										((strlen($m) > 1000) ? 'value_large' : 'value') => ($m.'')
									));									
								}
							} else {
								$this->db->insert('metadata', array(
									'item_id'   => $item_id,
									'fieldname' => $i,
									'counter'   => 1,
									((strlen($info[$i]) > 1000) ? 'value_large' : 'value') => ($info[$i].'')
								));
							}
						}
					}
				}
				
				// Creates the directories to store our files,
				$path = $this->cfg['data_directory'].'/'.$info['barcode'];
				if (!file_exists($path)) { mkdir($path, 0775); }
				if (!file_exists($path.'/scans')) { mkdir($path.'/scans', 0775); }
				if (!file_exists($path.'/thumbs')) { mkdir($path.'/thumbs', 0775); }
				if (!file_exists($path.'/preview')) { mkdir($path.'/preview', 0775); }

				$marc_data = null;
				
				// If we get the MARC data in separate fields? we create a Marc record
				if (count($marc) > 0) {
					$x = $this->_generate_marc($marc, $info['barcode']);
					if ($x) {
						$this->db->insert('metadata', array(
							'item_id'   => $item_id,
							'fieldname' => 'marc_xml',
							'counter'   => 1,
							'value_large' => $x['marc']
						));
						$marc_data = $x['marc'];
					}
				}
				
				if ($info['marc_xml']) {
					$marc_data = $info['marc_xml'];
				}

				if ($marc_data) {
					// Create the the marc.xml and item.xml file,
					write_file($path.'/marc.xml', $marc_data);
					chmod($path.'/marc.xml', 0775);
					// Sets the title using the MARC data.
					if (!isset($info['title'])){
						$this->set_title($marc_data);
					}
					
					// Sets the author using the MARC data.
					if (!isset($info['author'])){
						$this->set_author($marc_data);
					}
				}

				return $item_id;
			}
		} else {
			$this->last_error = "A barcode was not supplied for the new item.";
			throw new Exception($this->last_error);
		}
	}
	
	function _generate_marc($marc, $barcode) {
		$marc_xml = '<'.'?'.'xml version="1.0" encoding="UTF-8" ?'.'>'."\r\n".
			'<record xmlns="http://www.loc.gov/MARC21/slim" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.loc.gov/standards/marcxml/schema/MARC21slim.xsd">'."\r\n";
		$prev_tag = 0;
		// Build the Leader
		// Default values (index is starting position in the field)
		$field_leader = array(6 => 'a', 7 => 'm', 8 => '#', 19 => '#');
		foreach ($marc as $fieldname => $value) {
			$value = trim(preg_replace('/[\x00-\x1F]/', '', $value)); // Strip all low-ascii control characters
			if (strlen($value) > 0) {
				$matches = array();
				if (preg_match('/^marcLeader\/(\d*?)$/', $fieldname, $matches)) {
					unset($marc[$fieldname]);
					$field_leader[(int)$matches[1]] = $value;
				}
			}	
		}
		$marc_xml .= '  <leader>00000n'.$field_leader[6].$field_leader[7].$field_leader[8].'a2200000u#'.$field_leader[19].'4500</leader>'."\r\n";

		// Handle the control fields, we want them at the top
		foreach ($marc as $fieldname => $value) {
			$value = trim(preg_replace('/[\x00-\x1F]/', '', $value)); // Strip all low-ascii control characters
			if (strlen($value) > 0) {
				// handle the 001-009 fields
				$matches = array();
				if (preg_match('/^marc(00[12345679])/', $fieldname, $matches)) { // Note that 8 is missing here. We don't add it ever.
					if ($matches[1] != '') {
						$marc_xml .= '  <controlfield tag="'.$matches[1].'">'.$value.'</controlfield>'."\r\n";
					}
				}
			}
		}

		// Build the 008
		$field008 = array(0 => date('ymd'), 6 => 'm', 7 => 'uuuu', 11 => 'uuuu', 15 => 'xx#', 35 => '###');
		foreach ($marc as $fieldname => $value) {
			$value = trim(preg_replace('/[\x00-\x1F]/', '', $value)); // Strip all low-ascii control characters
			if (strlen($value) > 0) {
				$matches = array();
				if (preg_match('/^marc008\/(\d*?)$/', $fieldname, $matches)) {
					unset($marc[$fieldname]);
					// Get value and save for 008
					$field008[(int)$matches[1]] = $value;
				}
				// Did we encounter a 260. If so, it overrides anything else. Maybe. 
				// (Depends on the order of the fields in the CSV.)
				if (preg_match('/^marc260c$/', $fieldname, $matches)) {
					if (preg_match('/^(.*?)-(.*?)$/', $value, $matches)) {
						// Get start date and end date, make sure exactly 4 chars
						$field008[7] = substr($matches[1].'uuuu',0,4);
						$field008[11] = substr($matches[2].'uuuu',0,4);

					} else if (preg_match('/^(.*?)$/', $value, $matches)) {
						// Get start date, make sure exactly 4 chars
						$field008[7] = substr($matches[1].'uuuu',0,4);
					}
				}
				if (preg_match('/^marc041a$/', $fieldname, $matches)) {
						$field008[35] = substr($value.'###',0,3);				
				}
			}	
		}
		$marc_xml .= '  <controlfield tag="008">'.$field008[0].$field008[6].$field008[7].$field008[11].$field008[15].'||||g######00||0|'.$field008[35].'||</controlfield>'."\r\n";

		// Handle the other fields
		foreach ($marc as $fieldname => $value) {
			$value = trim(preg_replace('/[\x00-\x1F]/', '', $value)); // Strip all low-ascii control characters
			if (strlen($value) > 0) {
				$matches = array();
				$x = preg_match('/^marc(\d{3})(.?)(-?\d*?)$/', $fieldname, $matches);

				if (!isset($matches[1])) { $matches[1] = ''; }
				if (!isset($matches[2])) { $matches[2] = ''; }
				if (!isset($matches[3])) { $matches[3] = ''; }

				// 008 and Leader handled special above. Skip 'em here.
				if (substr($matches[1],0,2) != '00' && $matches[1] != 'Leader' && $matches[1] != '') {
					if ($prev_tag != $matches[1].$matches[3]) {
						if ($prev_tag != 0) { $marc_xml .= '  </datafield>'."\r\n"; }
						$marc_xml .= '  <datafield tag="'.$matches[1].'" ind1=" " ind2=" ">'."\r\n";
					}
					$value = preg_replace('/\&/', '&amp;', $value);
					$marc_xml .= '    <subfield code="'.$matches[2].'">'.$value.'</subfield>'."\r\n";
					if ($prev_tag != $matches[1].$matches[3]) {
						$prev_tag = $matches[1].$matches[3];
					}
				}
			}
		}
		$marc_xml .= '  </datafield>'."\r\n";
		$marc_xml .= '</record>'."\r\n";
		
		return array('marc' => $marc_xml);
	}

	/**
	 * Save the data for an item.
	 *
	 * Saves the data for one item (book). It's assumed that we have already passed the
	 * permission checking and we can save the data. BARCODE is NOT changed through this
     * When saving metadata, the existing metadata is deleted and the new is re-added. 
     * This means that data will be lost if fields are deleted via the set_metadata() 
	 *
	 * @since Version 1.3
	 */
	function update() {
		// Update the ITEM table
		if ($this->db->dbdriver == 'postgre') {
			$data = array(
				'pages_found' => $this->pages_found,
				'pages_scanned' => $this->pages_scanned,
				'scan_time' => $this->scan_time,
				'needs_qa' => ($this->needs_qa ? 't' : 'f'),
				'ia_ready_images' => ($this->ia_ready_images ? 't' : 'f'),
				'page_progression' => $this->page_progression,
				'total_mbytes' => $this->total_mbytes
			);
		} elseif ($this->db->dbdriver == 'mysql' || $this->db->dbdriver == 'mysqli') {
			$data = array(
				'pages_found' => $this->pages_found,
				'pages_scanned' => $this->pages_scanned,
				'scan_time' => $this->scan_time,
				'needs_qa' => ($this->needs_qa ? 1 : 0),
				'ia_ready_images' => ($this->ia_ready_images ? 1 : 0),
				'page_progression' => $this->page_progression,
				'total_mbytes' => $this->total_mbytes
			);
		}


		// Just in case, let's reset the org_id if it's empty. 
		if (!$this->org_id) {
			$data['org_id'] = $this->CI->user->org_id;
		}

		$this->db->where('id', $this->id);
		$this->db->update('item', $data);

		// Save the Metadata
		//   (We delete all of the metadata for the item (where page_id is null)
		//   because we're about to re-add all of the metadata. Wasteful? Probably.)
		$func = function($f) {return "'".$f['fieldname']."'";};
		$this->db->query(
			'delete from metadata
			where item_id = '.$this->book->id.'
			and page_id is null'
		);

		// Re-Add the metadata
		$metadata = $this->get_metadata();
		$array_counts = array();
		foreach ($metadata as $i) {
			if (array_key_exists($i['fieldname'], $array_counts)) {
				$array_counts[$i['fieldname']]++;
			} else {
				$array_counts[$i['fieldname']] = 1;
			}

			$this->db->insert('metadata', array(
				'item_id'   => $this->id,
				'fieldname' => $i['fieldname'],
				'counter'   => $array_counts[$i['fieldname']],
				((strlen($i['value']) > 1000) ? 'value_large' : 'value') => $i['value'].''
			));
		}

		// Update the the marc.xml and item.xml files on disk
		// We're going to assume that the directory exists. If it doesn't
		// we ignore the error. It could be the case that this item has been
		// resurrected and doesn't exist on disk because it's been archived.
		// If the item is current, the directory should have been created when
		// it was first added to the database. And yes, this is a long comment. :)
		$path = $this->cfg['data_directory'].'/'.$this->barcode;
		if ($md = $this->get_metadata('marc_xml')) {
			// Doubly make sure that we don't have an array.
			if (is_array($md)) {
				$md = $md[0];
			}
			if (file_exists($path)) {
				write_file($path.'/marc.xml', $md);
				chmod($path.'/marc.xml', 0775);
			}
			
			// Sets the title using the MARC data.
			if (!$this->get_metadata('title')){
				$this->set_title($md);
			}
			
			// Sets the authors using the MARC data.
			if (!$this->get_metadata('author')){
				$this->set_author($md);
			}
		}
	}

	/**
	 * Gets one piece of metadata for the object
	 *
	 * If only KEY is provided, then the value of that element in the
	 * metadata is returned, if it's there. Otherwise an empty string is
	 * returned.
	 *
	 * @param string [$key] The fieldname of the metadata to get or set.
	 * @return string The metadata for that item or an empty string otherwise.
	 */
	function get_metadata($key = '', $allow_array = true) {
		if ($key != '') {
			$key = strtolower($key);
			$results = array();
			foreach ($this->metadata_array as $i) {
				if ($i['fieldname'] == $key) {
					array_push($results, $i['value']);
				}
			}
			if (count($results) == 0) {
				return null;
			} elseif (count($results) == 1) {
				return $results[0];
			} else {
			  if ($allow_array) {
			    return $results;
			  } else {
			    return implode(';', $results);
			  }
			}			
		} else {
			return $this->metadata_array;
		}
	}


	/**
	 * Get a list of all metadata fieldnames
	 *
	 * Gathers an array of all of the metadata fieldnames for the object.
	 * If a fieldname is duplicated, it will be returned only once.
	 *
	 * @return string An array of fieldnames (strings).
	 */
	function get_metadata_fieldnames() {
		$results = array();
		foreach ($this->metadata_array as $i) {
			array_push($results, $i['fieldname']);
		}
		return $results;
	}

	/**
	 * Set one metadata for the item
	 *
	 * This sets the value for one metadata field for the item. If the KEY is
	 * not present in the metadata array, it is added. If it already exists, 
	 * its value will be updated to the new VALUE. If OVERWRITE is false, then a
	 * new metadata entry will be created.
	 * 
     * This does not save the data to the database. Use the
	 * update() method for that.
	 *
	 * @param string [$key] The name of the metadata field
	 * @param string [$value] The value to save, may be empty string or null
	 * @param boolean [$overwrite] Whether we should overwrite existing values or not. Defaults to true.
	 */
	function set_metadata($key = '', $value = '', $overwrite = true) {
		if ($key != '') {
			$key = preg_replace('/\s/', '_', strtolower($key));
			
			// Checks for a redundant Scanning Instition and ignores it if found.
			if ($key == 'scanning_institution' && $value == $this->get_contributor()){
				$msg = 'The Added By field was not saved because it is the same as the Contributor. Please refer to the <a href="https://docs.google.com/document/d/1-_XCe2LmbroQfnOC1Y5_tNpAhzI1BOiFVjkJH_ykvak/edit">Macaw User Guide</a> for details.';
				$this->session->set_userdata('warning', $msg);
				return;
			}
			
			$replaced = false;
			if ($overwrite) {
				foreach ($this->metadata_array as &$i) {
					if ($i['fieldname'] == $key) {
						$i['value'] = $value;
						$replaced = true;
						continue;
					}
				}
			}
			if (!$overwrite || !$replaced) {
				array_push(
					$this->metadata_array, 
					// To make our lives easier, we always save the fieldname in lowercase. 
					// Let's hope no one objects. :)
					array('fieldname' => $key, 'value' => $value)
				);
			}
		}
	}
	
	/**
	 * Clear one or all metadata for the item
	 *
	 * Deletes one metadata field from the item, or deletes all metadata from 
	 * the item entirely. This is a destructive operation and the changes are
	 * saved to the database during the update() method. No return value.
	 *
	 * @param string [$key] The name of the metadata field (defaults to empty)
	 * @param boolean [$all] Whether to clear all metadata for the item
	 */
	function unset_metadata($key = '', $all = false) {
		$key = strtolower($key);
		if ($key == '' && $all == true) {
			$this->metadata_array = array();
		} elseif ($key != '') {
			if (array_key_exists($key, $this->metadata_array)) {
				unset($this->metadata_array[$key]);
			}
		}	
	}

	/**
	 * Determine whether any metadata need to be filled in
	 *
	 * Based on the Macaw Configuration parameters "export_modules" and 
	 * "export_required_fields", determine if any are empty or missing and
	 * report back an array of arrays of the form: 
	 *
	 *   array('export_name' => array('fieldname_1','fieldname_2', ... );
	 * 
	 * The calling code needs to figure out what to do with this information.
	 * If all metadata fields are filled in, an empty array is returned.
	 *
	 * If we "strict" checking is specified (which is the default) the field must
	 * be a non-empty value. If strict checking is not desired, then we ignore the 
	 * value of the metadata and only return those fields that are truly missing/
	 * 
	 * @param boolean [$strict] Default true. All metadata must have a non-empty value.
	 * @return array The fields that are missing indexed on export module name
	 */
	function get_missing_metadata($strict = true) {
	 	$return = array();
	 	// Loop through the modules with required field.
		foreach ($this->cfg['export_required_fields'] as $mod => $fields) {
			if (in_array($mod, $this->cfg['export_modules'])) {
				$tmp = array();
				$all_fields = $this->get_metadata_fieldnames();
				// Loop through the required metadata fields
				foreach ($fields as $f) {
					if ($f == 'copyright') { continue; }
					if (in_array($f, $all_fields)) {
						if ($strict) {
							// If we're strict, we demand a non-empty value
							$md = $this->get_metadata($f);
							if (is_null($md) || $md == '') {
								array_push($tmp, $f);
							}
						}
					} else {
						// Strict or not, if the field isn't there, it's missing
						array_push($tmp, $f);
					}						
				}
				if (count($tmp) > 0) {
					$return[$mod] = $tmp;
				}
			}
		}

		// Handle some optional fields
		if (!$strict) {
			if (isset($this->cfg['export_optional_fields'])) {
				foreach ($this->cfg['export_optional_fields'] as $mod => $fields) {
				
					$all_fields = $this->get_metadata_fieldnames();

					if (in_array($mod, $this->cfg['export_modules'])) {
						if (!isset($return[$mod])) {
							$return[$mod] = array();
						}
						foreach ($fields as $f) {
							if (!in_array($f, $all_fields)) {
								array_push($return[$mod], $f);
							}
						}
					}
				}
			}
		}
		
		return $return;
	}

	/**
	 * Get the XMP XML for the item
	 *
	 * A wrapper method to get the XMP data which will be embedded in an image file.
	 *
	 * @return string The XML for the item
	 * @since version 1.2
	 */
	function xmp_xml() {

		$xml = '<x:xmpmeta xmlns:x="adobe:ns:meta/" x:xmptk="XMP Core 4.4.0">'.
				  '<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#">'.
				    '<rdf:Description rdf:about="" xmlns:dc="http://purl.org/dc/elements/1.1/">'.
				      '<dc:title>'.$this->get_metadata('title', false).'</dc:title>'.
				      '<dc:identifier>'.$this->barcode.'</dc:identifier>'.
				      '<dc:rights>'.($this->get_metadata('copyright', false) ? "This image is protected by copyright." : "This image is in the public domain.").'</dc:rights>'.
				      '<dc:source>'.$this->get_metadata('xmp_source', false).'</dc:source>'.
				      '<dc:creator>'.
				        '<rdf:Seq>'.
				          ($this->get_metadata('author', false) ? '<rdf:li>'.$this->get_metadata('author', false).'</rdf:li>' : '').
				          '<rdf:li>'.$this->get_contributor().'</rdf:li>'.
				        '</rdf:Seq>'.
				      '</dc:creator>'.
				      '<dc:date>'.$this->get_metadata('year', false).'</dc:date>'.
				    '</rdf:Description>'.
				  '</rdf:RDF>'.
				'</x:xmpmeta>';
		return $xml;
	}

	/**
	 * Group items by their queues
	 *
	 * Gets a list of the counts of how many items are in each "queue". This is used
	 * for the Dashboard summary widget.
	 *
	 * @return Object Database record with the counts of each queue.
	 * @since version 1.0
	 */
	function get_status_counts() {
		if ($this->db->dbdriver == 'postgre') {
			$q = $this->db->query(
				"select (select count(*) from item where status_code = 'new') as new,
					 (select count(*) from item where status_code = 'scanning') as scanning,
					 (select count(*) from item where status_code = 'scanned') as scanned,
					 (select count(*) from item where status_code = 'reviewing') as reviewing,
					 (select count(*) from item where status_code = 'qa-ready') as qa_ready,
					 (select count(*) from item where status_code = 'qa-active') as qa_active,
					 (select count(*) from item where status_code = 'reviewed') as reviewed,
					 (select count(*) from item where status_code = 'exporting') as exporting,
					 (select count(*) from item where status_code = 'completed') as completed,
					 (select count(*) from item where status_code = 'archived') as archived,
					 (select count(*) from item where status_code = 'error') as error,
					 (select count(*) from item) as books,
					 (select count(*) from page) as pages,
					 (select to_char(avg(age(date_review_end, date_scanning_start)),'fmdd') || 'd ' ||
							 to_char(avg(age(date_review_end, date_scanning_start)),'fmhh') || 'h ' ||
							 to_char(avg(age(date_review_end, date_scanning_start)),'fmmi') || 'm' from item) as avg;"
			);
			return $q->row();
		
		} elseif ($this->db->dbdriver == 'mysql' || $this->db->dbdriver == 'mysqli') {
			$q = $this->db->query(
				"select (select count(*) from item where status_code = 'new') as new,
				(select count(*) from item where status_code = 'scanning') as scanning,
				(select count(*) from item where status_code = 'scanned') as scanned,
				(select count(*) from item where status_code = 'reviewing') as reviewing,
				(select count(*) from item where status_code = 'qa-ready') as qa_ready,
				(select count(*) from item where status_code = 'qa-active') as qa_active,
				(select count(*) from item where status_code = 'reviewed') as reviewed,
				(select count(*) from item where status_code = 'exporting') as exporting,
				(select count(*) from item where status_code = 'completed') as completed,
				(select count(*) from item where status_code = 'archived') as archived,
				(select count(*) from item where status_code = 'error') as error,
				(select count(*) from item) as books,
				(select count(*) from page) as pages,
				(select concat(
				round(avg(unix_timestamp(date_review_end) - unix_timestamp(date_scanning_start)) / 86400), 'd ', 
				round(avg(unix_timestamp(date_review_end) - unix_timestamp(date_scanning_start)) % 86400 / 3600), 'h ',
				round(avg(unix_timestamp(date_review_end) - unix_timestamp(date_scanning_start)) % 86400 % 3600 / 60), 'm'
				) from item) as avg;"
			);
			return $q->row();
		
		}
	}

	function get_last_status() {
		$this->db->where('barcode', $this->barcode);
		$row = $this->db->get('item')->row();
		if ($row->date_archived) {
			return 'archived';
		} elseif ($row->date_completed) {
			return 'completed';
		} elseif ($row->date_review_end) {
			return 'reviewed';
		} elseif ($row->date_qa_end) {
			return 'qa_finished';
		} elseif ($row->date_qa_start) {
			return 'qa_ready';
		} elseif ($row->date_review_start) {
			return 'reviewing';
		} elseif ($row->date_scanning_end) {
			return 'scanned';
		} elseif ($row->date_scanning_start) {
			return 'scanning';
		} else {
			return 'new';
		}
	}

	/**
	 * Import all images from incoming
	 *
	 * Gets a list the images int he incoming directory and imports them
	 * into the current book, making preview and thumbnail versions.
	 *
	 * @return nothing
	 * @since version 1.0
	 */
	function import_images() {
		if ($this->id > 0) {

			$incoming_dir = $this->cfg['incoming_directory'];
			$scans_dir = $this->cfg['data_directory'].'/'.$this->barcode.'/scans/';
			$book_dir = $this->cfg['data_directory'].'/'.$this->barcode.'/';
			$modified = false;
			if ($this->check_paths()) {

				// Does this book already have pages? 
				$missing = false;
				$pgs = $this->get_pages();
				if (count($pgs) > 0) {
					// If yes, then the imported images are "missing".
					$missing = true;
				}
				
				if ($this->status == 'new' || $this->status == 'scanning' || $this->status == 'scanned' || $this->status == 'reviewing' || $this->status == 'reviewed') {
					//If it does, scan the files
					// TODO: This is probably broken since PATH is often blank. We should handle it more gracefully.
					$files = get_filenames($incoming_dir.'/'.$this->barcode);
					// Filter out files we want to ignore
					setlocale(LC_ALL, 'en_US.UTF-8');
					foreach ($files as $f) {
						// Make sure the filename is ASCII, rename if necessary
						$f_clean = iconv('utf-8', 'ASCII//TRANSLIT//IGNORE', $f);							
						if ($f_clean != $f) {
							// Rename the file
							rename($incoming_dir.'/'.$this->barcode.'/'.$f, $incoming_dir.'/'.$this->barcode.'/'.$f_clean);
							$f = $f_clean;
						}
						
						if (preg_match("/\.(pdf|PDF)$/i", $f)) {							
							$fname = $incoming_dir.'/'.$this->barcode.'/'.$f;	
							$fnamenew = $book_dir.$f;
							rename($fname, $fnamenew );			
							$outname = $incoming_dir.'/'.$this->barcode.'/'.preg_replace('/\.(.+)$/', '', $f).'_%04d.png';
							$this->logging->log('book', 'info', 'About to split  '.$fnamenew.' to '.$outname.' via convert.', $this->barcode);
							$gs = 'gs';
							if (isset($this->cfg['gs_exe'])) {
								$gs = $this->cfg['gs_exe'];
							}

							// Switched to using PNG. The files are smaller. Quality is maintained compared tp jpeg2000
							$exec = "$gs -sDEVICE=png16m -r450x450 -dSAFER -dBATCH -dNOPAUSE -dTextAlphaBits=4 -dUseCropBox -sOutputFile=".escapeshellarg($outname)." ".escapeshellarg($fnamenew);
							$this->logging->log('book', 'info', 'EXEC: '.$exec, $this->barcode);
							exec($exec, $output);
							
							$this->logging->log('book', 'info', 'After splitting '.$fnamenew.', "gs" output is '.count($output), $this->barcode);
							
							$this->set_metadata('from_pdf','yes',true);
							$this->update();
						}
					}
					
					// Filter out files we want to ignore
					$good_files = array();
					$files = get_filenames($incoming_dir.'/'.$this->barcode);
					foreach ($files as $f) {
						if (preg_match("/\.(tif|tiff|jpg|jpeg|jp2|jpf|gif|png|bmp)$/i", $f)) {
							array_push($good_files, $f);
						}
					}
					$files = $good_files;
					natsort($files);
					// Now we can safely process the images
					$this->logging->log('book', 'info', 'There are now '. count($files) .' images to import', $this->barcode);	
					if (count($files)) {
						// Update the Book with the number of pages we found
						$this->pages_found = count($files);
						$this->pages_scanned = 0;
						$this->scan_time = time();
						$this->update();
	
						// Add the pages to the database (or update) with status "Pending".
						// This will make them appear in the list on the monitor page.
						foreach ($files as $f) {
							$fname = $incoming_dir.'/'.$this->barcode.'/'.$f;
							$info = get_file_info($fname, 'size');
							$this->add_page($f, 0, 0, $info['size'], 'Pending', $missing);
						} // foreach ($files as $f)
	
						// Then we process the pages, updating them as we find them again.
						$this->logging->log('access', 'info', 'Importing images for barcode '.$this->barcode.'.');
						$count = 1;
						foreach ($files as $f) {
							$fname = $incoming_dir.'/'.$this->barcode.'/'.$f;
							$info = get_file_info($fname, 'size');

							// Make sure the file exists
							if (!file_exists($fname)) {
								$this->logging->log('book', 'info', 'File '.$f.' not found while importing. Skipped.', $this->barcode);
								continue;
							}
							// Make sure the file size isn't changing (we check for 3 seconds)
							if ($this->common->is_file_stable($fname, 1, 120)) {
								if (file_exists($scans_dir.$f)) {
									unlink($scans_dir.$f);
								}
								rename($fname, $scans_dir.$f);
							} else {
								$this->logging->log('error', 'debug', 'While importing newly scanned images, file '.$fname.' did not stabilize after 2 minutes. Aborting.');
								$this->update();
								return;
							}
	
							// Create derivatives for the file (/thumbnail/ and /preview/)
							$dim = $this->_process_image($scans_dir, $this->barcode, $f);
	
							// Add the page to the book
							$this->add_page($f, $dim['width'], $dim['height'], $info['size']);
	
							// Update the Book with the number of pages we've processed
							$this->pages_scanned = $count++;
	
							// Log that we saw the file.
							$this->logging->log('book', 'info', 'Created preview and thumbnail for page '.$f.'.', $this->barcode);
	
							$this->update();
	
						} // foreach ($files as $f)
					} // if (count($files))
				} // if ($this->status == 'scanning')
				if ($this->status == 'new' || $this->status == 'scanning') {
					$this->set_status('scanning');
					$this->set_status('scanned');
				}
			} else {
				echo('Check paths failed for item with barcode "'.$this->barcode.'".'."\n");
				// The paths are not all writable or existing. Skip this book
			} // if ($this->check_paths)
		} // if ($this->id > 0)
	}

	/**
	 * Split a PDF into smaller PNG files for later processing.
	 * 
	 * PDF file is moved from the /scans/ directory (or wherever, really) into the main folder for the book.
	 * Then the PDF is split into 450 DPI PNG files in the /scans/ directory.
	 *
	 * Params
	 * $filename - Just the filename, no path. The file is assumed to be in the /scans/ directory
	 * 
	 * @return nothing
	 * @since version 2.2
	 */
	function split_pdf($filename) {
		$scans_dir = $this->cfg['data_directory'].'/'.$this->barcode.'/scans/';
		$book_dir = $this->cfg['data_directory'].'/'.$this->barcode.'/';

		if (preg_match("/\.(pdf|PDF)$/i", $filename)) {		
			// Move the PDF so we don't see it again
			rename($scans_dir.$filename, $book_dir.$filename);
			// Build the pattern for the PNGs to create
			if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
				// SCS Note - escapeshellarg in windows just removes %. need a multistage approach./
				$outname = $scans_dir.preg_replace('/^(.+)\.(.*?)$/', '$1', $filename).'_^^04d.png';
				$outname = escapeshellarg($outname);
				$outname = str_replace('_^^04d.png','_%04d.png',$outname);
			} else {
				$outname = escapeshellarg($scans_dir.preg_replace('/^(.+)\.(.*?)$/', '$1', $filename).'_%04d.png');
			}
			$this->logging->log('book', 'info', 'About to split  '.$filename.' to PNG files', $this->barcode);
			// Find ghostscript
			$gs = 'gs';
			if (isset($this->cfg['gs_exe'])) {
				$gs = $this->cfg['gs_exe'];
			}
			$output = '';
			// Build the ghostscript command
			$exec = "$gs -sDEVICE=png16m -r450x450 -dSAFER -dBATCH -dNOPAUSE -dTextAlphaBits=4 -dUseCropBox -sOutputFile=".$outname." ".escapeshellarg($book_dir.$filename);
			$this->logging->log('book', 'info', 'EXEC: '.$exec, $this->barcode);
			// Do the splitting, this takes a while and is largely uninformative
			exec($exec, $output);
			$this->logging->log('book', 'info', 'After splitting '.$filename.', "gs" output is '.count($output), $this->barcode);			
			// Done! Let's mark the images as having been derived from a PDF
			$this->set_metadata('from_pdf', 'yes', true);
			$this->set_metadata('pdf_source', $filename, false);
			$this->book->update();
		}
	}
	
	/**
	 * Import one image for the current book
	 *
	 * Given the path to a file for a page of the book, imports the image
	 * into the current book, making preview and thumbnail versions and database
	 * records.
	 *
	 * Params
	 * $filename - Just the filename, no path. The file is assumed to be in the /scans/ directory
	 *
	 * ASSUMPTION: The image to be processed is already in the /scans/ directory.
	 * It does not need to be moved. (This allows this script to be re-run if the
	 * thumbnails are missing)
	 *
	 * @return nothing
	 * @since version 2.2
	 */
	function import_one_image($filename, $counter = 1, $missing = false) {
		if ($this->id > 0) {

			$scans_dir = $this->cfg['data_directory'].'/'.$this->barcode.'/scans/';
			$book_dir = $this->cfg['data_directory'].'/'.$this->barcode.'/';

			if (!file_exists($scans_dir.$filename)) {
				$this->logging->log('book', 'info', "File not found: $scans_dir$filename", $this->barcode);
				return;
			}

			$modified = false;
			$filebase = basename($filename); 
		
			setlocale(LC_ALL, 'en_US.UTF-8');
			// Make sure the filename is ASCII, rename if necessary
			$f_clean = iconv('utf-8', 'ASCII//TRANSLIT//IGNORE', $filebase);							
			if ($f_clean != $filebase) {
				// Rename the file
				rename($scans_dir.$filebase, $scans_dir.$f_clean);
				$filebase = $f_clean;
				$filename = $scans_dir.$f_clean;
			}
			
			$info = get_file_info($scans_dir.$filename, 'size');

			// Create derivatives for the file (/thumbnail/ and /preview/)
			$dim = $this->_process_image($scans_dir, $this->barcode, $filebase);
			// Add the page to the book

			$this->add_page($filebase, $dim['width'], $dim['height'], $info['size'], 'Processed', $missing, $counter);
			// Log that we saw the file.
			$this->logging->log('book', 'info', 'Created preview and thumbnail for page '.$filename.'.', $this->barcode);
		} // if ($this->id > 0)
	}

	/**
	 * Create derivatives for an individual scan of a page
	 *
	 * INTERNAL: Given a filename, creates whatever derivatives that are immediately
	 * needed by the Macaw. Also loads in whatever metadata we have for the
	 * file. (Bytes)
	 *
	 * @param string [$path] The path to the file on the server
	 * @param string [$barcode] The barcode of the book in question
	 * @param string [$filename] The filename of the scanned page
	 * @since Version 1.0
	 */
	function _process_image($path, $barcode, $filename) {

		// Error handling
		if (!$path) {
			throw new Exception('Path not supplied to _process_file().');
		}
		if (!$filename) {
			throw new Exception('Filename not supplied to _process_file().');
		}

		// Get the base of the filename
		$filebase = preg_replace('/^(.+)\.(.*?)$/', "$1", $filename);
		$dest = $this->cfg['data_directory'].'/'.$barcode;

		// Create the preview JPEG
		$preview = new Imagick($path.'/'.$filename);
		$this->common->get_largest_image($preview);

		// get the dimensions, we're going to want them later
		$return = array();
		$info = $preview->getImageGeometry();
		$return['width'] = $info['width'];
		$return['height'] = $info['height'];

		// Create the preview image
		$preview->resizeImage(1500, 2000, Imagick::FILTER_POINT, 0);
		$preview->profileImage('xmp', $this->book->xmp_xml());
		try {
			$preview->writeImage($dest.'/preview/'.$filebase.'.jpg');
		} catch (Exception $e) {
			$this->logging->log('book', 'info', 'Exception: '.$e->getMessage(), $this->barcode);
		}

		// Set IPTC Data
		$img = new Image_IPTC($dest.'/preview/'.$filebase.'.jpg');
		$img->setTag('object_name', $this->book->barcode);
		$img->setTag('byline', $this->book->get_metadata('author'), 0);
		$img->setTag('byline', $this->get_contributor(), 1);
		$img->setTag('source', $this->book->get_metadata('xmp_source'));
		$img->setTag('copyright_string', ($this->book->get_metadata('copyright') ? "This image is protected by copyright." : "This image is in the public domain."));
		$img->setTag('special_instructions', $this->book->get_metadata('ia_special_instructions'));
		$img->setTag('created_date', $this->book->get_metadata('year'));
		$img->setTag('digital_created_date', date('Ymd'));
		$img->save();

		// Create the thumbnail image
		$preview->resizeImage(180, 300, Imagick::FILTER_POINT, 0);
		$preview->profileImage('xmp', $this->book->xmp_xml());
		$preview->writeImage($dest.'/thumbs/'.$filebase.'.jpg');

		// Set IPTC Data
		$img = new Image_IPTC($dest.'/thumbs/'.$filebase.'.jpg');
		$img->setTag('object_name', $this->book->barcode);
		$img->setTag('byline', $this->book->get_metadata('author'), 0);
		$img->setTag('byline', $this->get_contributor(), 1);
		$img->setTag('source', $this->book->get_metadata('xmp_source'));
		$img->setTag('copyright_string', ($this->book->get_metadata('copyright') ? "This image is protected by copyright." : "This image is in the public domain."));
		$img->setTag('special_instructions', $this->book->get_metadata('ia_special_instructions'));
		$img->setTag('created_date', $this->book->get_metadata('year'));
		$img->setTag('digital_created_date', date('Ymd'));
		$img->save();

		$preview->clear();
		$preview->destroy();

		return $return;
	}

	function archive() {
		// TODO: Need to be able to track the different statuses across export processes before we code this.
	}

	function check_paths() {
		$this->last_error = '';
		$paths = array();
		array_push($paths, $this->cfg['data_directory'].'/'.$this->barcode);
		array_push($paths, $this->cfg['data_directory'].'/'.$this->barcode.'/scans/');
		array_push($paths, $this->cfg['data_directory'].'/'.$this->barcode.'/thumbs/');
		array_push($paths, $this->cfg['data_directory'].'/'.$this->barcode.'/preview/');

		foreach ($paths as $p) {
			if (!file_exists($p)) {
				$this->last_error = 'Directory not found: '.$p;
				$this->logging->log('error', 'debug', $this->last_error);
			}
			if (!is_writable($p)) {
				$this->last_error = 'Permission denied to write to: '.$p;
				$this->logging->log('error', 'debug', $this->last_error);
			}
		}

		// Did we have an error?
		if ($this->last_error != '') {
			return false;
		}

		return true;
	}

	// Gets a proper contributor or organization name for the book
	//
	// First checks the contributor metadata on the item.
	// Next checks the organizaton name associated to the item.
	// Finally it returns the organization_name from the macaw.php config file.
	//
	// Since Version 1.6

	function get_contributor() {
		if ($this->get_metadata('contributor')) {
			return $this->get_metadata('contributor');

		} elseif ($this->org_name != 'Default') {
			return $this->org_name;

		} else {
			return $this->cfg['organization_name'];
		}
	}

	// Retrieves the metadata for the item
	//
	// This internal function queries the metadata table for the metadata
	// fields for this item.
	//
	// Since Version 1.6

	function _populate_metadata() {
		// Get all the records from the item's metadata records
		// Return an array of things.
		$c = $this->db->query(
			"select lower(fieldname) as fieldname, coalesce(value, value_large) as val
			from metadata
			where item_id = ".$this->id."
			  and page_id is null"
		);
		$results = array();
		foreach ($c->result() as $row) {
			array_push(
				$results, 
				array('fieldname' => $row->fieldname, 'value' => $row->val)
			);
		}

		return $results;
	}

	/**
	 * Gets a list of exporting books that are older than a week but have
	 * not completed.
	 *
	 * @param string [$org_id] Optional organization id to narrow down
	 * results.
	 */
	function get_stalled_exports($org_id = NULL){
		$query = null;
		if ($this->db->dbdriver == 'mysql' || $this->db->dbdriver == 'mysqli') {
			$query = 'SELECT i.barcode, m.value as \'title\', o.name as \'org_name\', coalesce(b.bytes, 0) as \'bytes\', i.date_export_start, s.status_code, ia.identifier '.
				'FROM item i '.
				'INNER JOIN organization o ON o.id = i.org_id '.
				'LEFT OUTER JOIN ( '.
				'	SELECT sum(p.bytes) AS bytes, max(i.id) as id, max(i.status_code) AS status_code, max(i.org_id) as org_id, count(*) as pages '.
				'	FROM page p  '.
				'	INNER JOIN item i ON p.item_id = i.id  '.
				'	WHERE i.status_code NOT IN (\'completed\', \'exporting\')  '.
				'	GROUP BY i.org_id '.
				') b ON o.id = b.org_id '.
				'LEFT JOIN metadata m ON m.item_id = i.id AND lower(m.fieldname) = \'title\' '.
				'LEFT JOIN item_export_status s ON s.item_id = i.id '.
				'LEFT JOIN custom_internet_archive ia on i.id = ia.item_id '.
				'WHERE i.status_code = \'exporting\' '.
				'AND NOT s.status_code = \'completed\' '. 
				'AND i.date_export_start < NOW() - INTERVAL 3 DAY';
		} elseif ($this->db->dbdriver == 'postgre') {
			$query = 'SELECT i.barcode, m.value as title, o.name as org_name, coalesce(b.bytes, 0) as bytes, i.date_export_start, s.status_code, ia.identifier '.
				'FROM item i '.
				'INNER JOIN organization o ON o.id = i.org_id '.
				'LEFT OUTER JOIN ( '.
				'	SELECT sum(p.bytes) AS bytes, max(i.id) as id, max(i.status_code) AS status_code, max(i.org_id) as org_id, count(*) as pages '.
				'	FROM page p  '.
				'	INNER JOIN item i ON p.item_id = i.id  '.
				'	WHERE i.status_code NOT IN (\'completed\', \'exporting\')  '.
				'	GROUP BY i.org_id '.
				') b ON o.id = b.org_id '.
				'LEFT JOIN metadata m ON m.item_id = i.id AND lower(m.fieldname) = \'title\' '.
				'LEFT JOIN item_export_status s ON s.item_id = i.id '.
				'LEFT JOIN custom_internet_archive ia on i.id = ia.item_id '.
				'WHERE i.status_code = \'exporting\' '.
				'AND NOT s.status_code = \'completed\' '. 
				'AND i.date_export_start < NOW() - INTERVAL \'3 DAY\'';
		}

		if ($org_id){
			$query .= ' AND i.org_id = '.$org_id;
		}
		$result = $this->db->query($query)->result();
		return $result;
	}
	
	/**
	 *
	 */
	function set_title($marc){
		$xml = @simplexml_load_string($marc);
		$namespaces = $xml->getNamespaces();
		foreach ($namespaces as $prefix => $namespace){
			if (in_array($prefix, array('marc', ''))){
				$xml->registerXPathNamespace('x', $namespace);
			}
		}
		$titles = NULL;
		foreach (array('a', 'b') as $code){
			if ($ret = $xml->xpath("//x:record/x:datafield[@tag=\"245\"]/x:subfield[@code=\"{$code}\"]")){
				$titles[] = trim(preg_replace('/[,.;:\/ ]+$/', '', (string)$ret[0]));
			}
		}
		$this->db->insert('metadata', array(
			'item_id'   => $this->id,
			'fieldname' => 'title',
			'counter'   => 1,
			'value'     => implode(' ', $titles)
		));	
	}
	
	/**
	 *
	 */
	function set_author($marc){
		$xml = simplexml_load_string($marc);
		if ($xml === false) {
			$this->logging->log('book', 'error', 'Could not find marc file when trying to add author', $this->barcode);
			$errors = libxml_get_errors();
			foreach ($errors as $error) {
				$this->logging->log('book', 'error', 'Marc could not be parsed - '.$error->message, $this->barcode);
			}
			return false;
		}
		$namespaces = $xml->getNamespaces();
		foreach ($namespaces as $prefix => $namespace){
			if (in_array($prefix, array('marc', ''))){
				$xml->registerXPathNamespace('x', $namespace);
			}
		}
		
		// Different XPATH queries to find an author.
		$queries = array(
			'//x:record/x:datafield[@tag="100"]/x:subfield[@code="a"][text()]',
			'//x:record/x:datafield[@tag="110"]/x:subfield[@code="a"][text()]',
			'//x:record/x:datafield[@tag="111"]/x:subfield[@code="a"][text()]',
			'//x:record/x:datafield[@tag="700" and not(subfield[@code="t"])]/x:subfield[@code="a"][text()]',
			'//x:record/x:datafield[@tag="710" and not(subfield[@code="t"])]/x:subfield[@code="a"][text()]',
			'//x:record/x:datafield[@tag="711" and not(subfield[@code="t"])]/x:subfield[@code="a"][text()]',
			'//x:record/x:datafield[@tag="720" and not(subfield[@code="t"])]/x:subfield[@code="a"][text()]'
		);
		$author = NULL;
		foreach ($queries as $query){
			$author = $xml->xpath($query);
			
			// An author was found.
			if ($author){
				$author = trim(preg_replace('/[,.;:\/ ]+$/', '', (string)$author[0]));
				$this->logging->log('book', 'log', 'Found Author '.$author, $this->barcode);
				$this->db->insert('metadata', array(
					'item_id'   => $this->id,
					'fieldname' => 'author',
					'counter'   => 1,
					'value'     => $author)
				);
				break;
			}
		}	
	}
	 
	/**
	 * Gets a list of all unique collections.
	 */	
	function get_all_collections(){
		$collections = array();
		
		$this->db->distinct();
		$this->db->select('value');
		$this->db->where('fieldname', 'collection');
		$query = $this->db->get('metadata');
		foreach ($query->result_array() as $row){
			array_push($collections, $row['value']);
		}
		return $collections;
	}

}
