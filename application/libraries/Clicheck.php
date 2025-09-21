<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Client Check Library
 *
 * MACAW Metadata Collection and Workflow System
 *
 * Helps us determine if we are being run from the command line or not.
 *
 *
 * @package Clicheck
 * @author Joel Richard
 * @version 1.0 clicheck.php created: 2012-11-04 last-modified: N/A
 
	Change History
	Date        By   Note
	------------------------
	2012-11-04  JMR  Created

 **/

class Clicheck extends Controller {

	var $CI;
	var $cfg;

	function __construct() {
		$this->CI = get_instance();
		$this->CI->load->library('session');
		$this->cfg = $this->CI->config->item('macaw');
	}


	/**
	 * Advanced PHP-CLI mode check.
	 * 
	 * @return boolean	Returns true if PHP is running from the CLI or else false.
	 * 
	 * @access public
	 * @static
	 */
	public static function isCli() {
		// If STDIN was not defined and PHP is running as CGI module
		// we can test for the environment variable TERM. This
		// should be a right way how to test the circumstance under 
		// what mode PHP is running.
		if(!defined('STDIN') && self::isCgi()) {
			// STDIN was not defined, but if the environment variable TERM 
			// is set, it is save to say that PHP is running from CLI.
			if(getenv('TERM')) {
				return true;
			}
			// Now return false, because TERM was not set.
			return false;
		}
		return defined('STDIN');
	}	

	/**
	 * Simple PHP-CGI mode check.
	 * 
	 * (DSO = Dynamic Shared Object)
	 * 
	 * @link http://httpd.apache.org/docs/current/dso.html DSO
	 * @link http://www.php.net/manual/en/function.php-sapi-name.php PHP_SAPI
	 * 
	 * @return boolean	Returns true if PHP is running as CGI module or else false.
	 * 
	 * @access public
	 * @static
	 */
	public static function isCgi() {
		if (substr(PHP_SAPI, 0, 3) == 'cgi') {
			return true;
		} else {
			return false;
		}
		return false;
	}

}
