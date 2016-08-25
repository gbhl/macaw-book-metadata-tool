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

	function BHL() {
		$this->CI = get_instance();
		$this->CI->load->library('session');
		$this->cfg = $this->CI->config->item('macaw');
	}

	function get_institutions() {
		// Only continue if we have an API key
		if (!isset($this->cfg['bhl_api_key'])) {
			return array();
		}
		if (!$this->cfg['bhl_api_key']) {
			return array();
		}
		
		// Get the institutions
		$url = 'http://www.biodiversitylibrary.org/api2/httpquery.ashx?op=GetInstitutions&format=json&apikey='.$this->cfg['bhl_api_key'];
		$json = file_get_contents($url);
		$json = json_decode($json);
		if ($json->Status == 'ok') {
			return $json->Result;
		}
		return array(); 
		
	}

}


