<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Utilities Controller
 *
 * MACAW Metadata Collection and Workflow System
 *
 * Various utilities, usually called from the command line.
 *
 **/
 
class Cleanup extends Controller {

	var $cfg;
	var $version_dates = array(
		'1.0' => '2000-01-01'
	);

	/**
	 * Function: Constructor
	 */
	function __construct() {
		parent::Controller();
		$this->cfg = $this->config->item('macaw');
	}

	/**
	 * Function: _get_ia_keys()
	 * 
	 * Parameters: Organization ID
	 * 
	 * Go to the custom IA table and get the access keys for
	 * uploading to IA. 
	 */
	 function _get_ia_keys($org_id) {
		$query = $this->CI->db->query('select access_key, secret from custom_internet_archive_keys where org_id = '.$org_id);
		foreach ($query->result() as $row) {
			return array(
				'key' => $row->access_key,
				'secret' => $row->secret
			);
		}
	}

	/**
	 * Function: Clean IA Macaw Versions
	 * 
	 * Uses the $version_dates variable to 
	 * reset the "macaw_uploader_version" metadata element
	 * at the Internet Archive for all items that 
	 * this installation of Macaw uploaded.
	 * 
	 * Should be used once after upgrading to 
	 * this most recent version of Macaw.
	 * 
	 * Usage: 
	 *   sudo -u apache php index.php cleanup set_ia_macaw_versions
	 */
	function set_ia_macaw_versions() {
			// Get all items and IA Identifiers
			$books = $this->book->get_all_books();

			// For each item, check the "macaw_uploader_version" 
			foreach ($books as $book) {
				$id = $this->book->get_metadata('ia_identifier');
				$ia_metadata = file_get_contents("https://archive.org/metadata/${id}/metadata/macaw_uploader_version");
				$json = json_decode($ia_metadata, true);
				if (isset($json['error'])) {
					// If missing, set the macaw_uploader_version based on the $version_dates
					// Get the API key for this item
					$key = $this->_get_ia_keys($book->org_id);
					$cmd = $this->cfg['curl_exe'];
					$cmd .= " --data-urlencode -target=metadata";
					$cmd .= " --data-urlencode -patch='{\"add\":\"/macaw_uploader_version\", \"value\":\"${version}\"}'";
					$cmd .= " --data-urlencode access=".$key['access'];
					$cmd .= " --data-urlencode secret=".$key['secret'];
					$cmd .= " https://archive.org/metadata/${id} 2>&1";
					echo $cmd;
					die;

				} else {
					// Do nothing
				}
			}
	}
}

// TODO --- Need table of Macaw Versions and dates
