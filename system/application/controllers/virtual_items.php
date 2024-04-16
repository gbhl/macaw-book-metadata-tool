<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Virtual Items Controller
 *
 * MACAW Metadata Collection and Workflow System
 *
 * Governs administrative activities for Virtual Items
 *
 **/

class Virtual_Items extends Controller {

	var $cfg;

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

		$content = $this->load->view('virtual_items/index', []);

	}

	function sources() {
		$this->common->check_session();

		// Permission Checking
		if (!$this->user->has_permission('admin') && !$this->user->has_permission('local_admin')) {
			$this->session->set_userdata('errormessage', 'You do not have permission to access that page.');
			redirect($this->config->item('base_url').'main/listitems');
			$this->logging->log('error', 'debug', 'Permission Denied to access '.uri_string());
		}

		// ----------------------------------------
		// List all sources and configuration files
		// ----------------------------------------
		if (file_exists($this->cfg['plugins_directory'].'/import/Virtual_Item_Configs.php')) {
			require_once($this->cfg['plugins_directory'].'/import/Virtual_Item_Configs.php');
			$vi_config = new Virtual_Item_Configs();

			$data = [];
			// Find and process the Virtual Item Sources
			$data['sources'] = [];
			$dir = new DirectoryIterator($vi_config->config_path);
			foreach ($dir as $fileinfo) {
				if ($fileinfo->isDot()) {
					continue;
				}

				if ($fileinfo->isDir()) {
					$source_path = $fileinfo->getPathName().'/config.php';

					$dirs = preg_split("/[\\/]/", $source_path);
					$source = array(
						'url' => $this->config->item('base_url')."virtual_items/source/".$dirs[4],
						'config_url' => $this->config->item('base_url')."virtual_items/view_config/".$dirs[4],
						'name' => $dirs[4],
						'path' => $source_path,
						'item_count' => 0,
						'valid' => false,
					);

					if (file_exists($source_path)) {
						// Load one Virtual Item Source configuration
						$vi = []; require($source_path);
						$source['valid'] = $vi_config->check_config($vi, $fileinfo->getPathName());

						// Count the items
						$query = $this->db->query("select count(*) as total from custom_virtual_items where source = ".$this->db->escape($dirs[4]));
						$row = $query->result();					
						$source['item_count'] = $row[0]->total;
					}
					$data['sources'][] = $source;
				}
			}
		}

		$content = $this->load->view('virtual_items/sources', $data);
	}

	function source($name, $id = null) {
		$this->common->check_session();

		// Permission Checking
		if (!$this->user->has_permission('admin') && !$this->user->has_permission('local_admin')) {
			$this->session->set_userdata('errormessage', 'You do not have permission to access that page.');
			redirect($this->config->item('base_url').'main/listitems');
			$this->logging->log('error', 'debug', 'Permission Denied to access '.uri_string());
		}

		// ----------------------------------------
		// List the articles and statuses for one source
		// ----------------------------------------

		if ($name == 'Spreadsheet') {
			$this->source_spreadsheet($id);
			return;
		}

		$data['name'] = $name;

		$sql = "select count(*) as thecount, status_code from ".
		  "custom_virtual_items vi inner join item i on vi.barcode = i.barcode ".
			"where vi.source = ".$this->db->escape($data['name'])." group by status_code order by status_code;";
		$query = $this->db->query($sql);
		$rows = $query->result_array();
		$data['item_summary'] = $rows;
		$data['filter'] = array('All', 'Awaiting Export', 'Exporting', 'Completed');
		unset($data['items']);
		$data['view_items'] = 1;

		$content = $this->load->view('virtual_items/source_items', $data);

	}

	function source_spreadsheet($id = null) {
		$this->common->check_session();

		// Permission Checking
		if (!$this->user->has_permission('admin') && !$this->user->has_permission('local_admin')) {
			$this->session->set_userdata('errormessage', 'You do not have permission to access that page.');
			redirect($this->config->item('base_url').'main/listitems');
			$this->logging->log('error', 'debug', 'Permission Denied to access '.uri_string());
		}

		if ($id) {
			$id = preg_replace("/[^0-9]/", '', $id);
			$sql = "select * from custom_virtual_items_batches where id = $id";
			$query = $this->db->query($sql);
			$rows = $query->result_array();
			$data = [];
			$data['name'] = $rows[0]['source_filename'];
			$data['uploaded_by'] = $rows[0]['uploader'];
			$data['created'] = $rows[0]['created'];
			$data['id'] = $id;
			$data['filter'] = array('All', 'Awaiting Export', 'Exporting', 'Completed');
			$content = $this->load->view('virtual_items/source_spreadsheet_items', $data);
		} else {
			$content = $this->load->view('virtual_items/source_spreadsheet', []);
		}
	}

	function source_spreadsheet_list() {
		if (!$this->common->check_session(true)) {
			return;
		}
		$is_local_admin = $this->user->has_permission('local_admin');
		$is_admin = $this->user->has_permission('admin');
		$org_id = $this->user->org_id;

		// List all the spreadsheets that have been uploaded, and their statuses
		$sql = "SELECT *, 'Unknown' as status from custom_virtual_items_batches ORDER BY created desc";
		$query = $this->db->query($sql);
		$rows = $query->result_array();

		$data = [];
		foreach ($rows as $r) {
		//	if ($is_admin || $org_id == $r->org_id) {
				$data[] = $r;
		//	}				
		}

		$this->common->ajax_headers();
		echo json_encode(array('data' => $data));

	}

	function source_itemlist($name, $id=null) {
		if (!$this->common->check_session(true)) {
			return;
		}
		$is_local_admin = $this->user->has_permission('local_admin');
		$is_admin = $this->user->has_permission('admin');
		$org_id = $this->user->org_id;
		$id = preg_replace("/[^0-9]/", '', $id);

		$sql = "select vi.*, i.status_code from ".
			"custom_virtual_items vi inner join item i on vi.barcode = i.barcode ".
			"where vi.source = ".$this->db->escape($name)." and vi.batch_id ".($id ? '= '.$id : "is null")." and i.status_code <> 'completed' ".
			" UNION ".
			"(select vi.*, i.status_code from ".
			"custom_virtual_items vi inner join item i on vi.barcode = i.barcode ".
			"where vi.source = ".$this->db->escape($name)." and vi.batch_id ".($id ? '= '.$id : "is null")." and i.status_code = 'completed' limit 100) order by created desc";
		$query = $this->db->query($sql);
		$rows = $query->result_array();

		// Sort our records into the subarrays
		$data = [];
		foreach ($rows as $r) {
			if ($is_admin || $org_id == $r->org_id) {
				$data[] = $r;
			}				
		}
		$this->common->ajax_headers();
		echo json_encode(array('data' => $data));
	}

	function view_config($name) {
		$this->common->check_session();

		// Permission Checking
		if (!$this->user->has_permission('admin') && !$this->user->has_permission('local_admin')) {
			$this->session->set_userdata('errormessage', 'You do not have permission to access that page.');
			redirect($this->config->item('base_url').'main/listitems');
			$this->logging->log('error', 'debug', 'Permission Denied to access '.uri_string());
		}
		$data = [];
		$data['name'] = $name;

		// ----------------------------------------
		// List the details of a source's configuration file
		// ----------------------------------------
		$macaw_config = $this->config->item('macaw'); // PHP Sucks, two step when one would suffice.
		require_once($this->cfg['plugins_directory'].'/import/Virtual_Item_Configs.php');
		$vi_config = new Virtual_Item_Configs();
		$source_path = $vi_config->config_path."/$name/config.php";
		if (file_exists($source_path)) {
			$vi = [];
			require($source_path);
			$vi['vi-identifier-data'] = "function()";
			$vi['get-pdf'] = "function()";

			$data['config'] = true;
			$query = $this->db->query('SELECT * FROM organization WHERE id = ?', array($vi['upload-org-id']));
			$organization = $query->result();
			$org_name = $organization[0]->name;

			$feed = '';
			if (is_array($vi['feed'])) {
				$feed = implode("<br>", $vi['feed']);
			} else {
				$feed = $vi['feed'];
			}

			$copyright = 'NOT SET';
			foreach ($this->cfg['copyright_values'] as $v) {
				if ($v['value'] == $vi['copyright']) {
					$copyright = $v['title'];
				}
			}
			
			$data['config_params'] = array(
				array('paramater' => 'Title ID', 'value' => $vi['title-id']),
				array('paramater' => 'Feed Type', 'value' => $vi['feed-type']),
				array('paramater' => 'Feed', 'value' => $feed),
				array('paramater' => 'Collection', 'value' => implode($vi['collections'])),
				array('paramater' => 'Uploaded by', 'value' => $organization[0]->name),
				array('paramater' => 'Contributor', 'value' => $vi['contributor']),
				array('paramater' => 'In Copyright', 'value' => $copyright),
				array('paramater' => 'Creative Commons', 'value' => $vi['creative-commons']),
				array('paramater' => 'Rights Holder', 'value' => $vi['rights-holder']),
				array('paramater' => 'Rights', 'value' => $vi['rights']),
			);

		} else {
			$this->session->set_userdata('errormessage', 'Virtual Item with Source '.strip_tags($name).' not found.');
			redirect($this->config->item('base_url').'virtual_items/sources');					
		}

		$content = $this->load->view('virtual_items/source_config', $data);

	}


}
