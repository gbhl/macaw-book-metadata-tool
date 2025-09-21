<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * BHL Library
 *
 * MACAW Metadata Collection and Workflow System
 *
 * Provides a convenient interface to the BHL API. Uses file-based caching to limit the times
 * it hits the API for certain activities.
 *
 * Requires configuration variable: bhl_api_key
 *
 *
 * @author Joel Richard
 * @version 1.0 BHL.php created: 2016-08-25 last-modified: 2016-08-25

	Change History
	Date        By   Note
	------------------------
	2016-08-25  JMR  Created

 **/

class BHL extends Controller {

	var $CI;
	var $cfg;

	function __construct() {
		$this->CI = get_instance();
		$this->CI->load->library('session');
		$this->cfg = $this->CI->config->item('macaw');
	}

	function get_institutions() {
		// Only continue if we have an API key
		$results = array();

		if (isset($this->cfg['bhl_api_key']) && $this->cfg['bhl_api_key']) {
			// SSL options
			$arrContextOptions=array(
				"ssl"=>array(
					"verify_peer"=>false,
					"verify_peer_name"=>false,
				),
				"http" => array('timeout' => 5)
			);
			// Get the institutions
			$url = 'https://www.biodiversitylibrary.org/api2/httpquery.ashx?op=GetInstitutions&format=json&apikey='.$this->cfg['bhl_api_key'];
			$json = @file_get_contents($url, false, stream_context_create($arrContextOptions));
			if ($json) {
				$json = json_decode($json);
				if ($json->Status == 'ok') {
					$results = $json->Result;
				}
			}
		}
		
		if (!count($results)) {
			if (file_exists(BASEPATH . '/../assets/bhl-contributors.json')) {
				$json = json_decode(file_get_contents(BASEPATH . '/../assets/bhl-contributors.json'));
				$results =$json->Result;
			}
		}

		return $results;
		
	}

}


