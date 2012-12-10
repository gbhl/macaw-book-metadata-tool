<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Authentication Library
 *
 * MACAW Metadata Collection and Workflow System
 *
 * This is a wrapper for the customizable login methods to allow us to
 * authenticate a person against a variety of schemes. Currently we only
 * support "local", but "ldap" is planned.
 *
 * NOTES: All modules must set the following variables;
 *        $user_data['user']
 *        $user_data['user_name']
 *        $user_data['logged_in']
 *
 * @package admincontroller
 * @author Joel Richard
 * @version 1.0 admin.php created: 2010-07-07 last-modified: 2010-08-19

	Change History
	Date        By   Note
	------------------------
	2010-07-07  JMR  Created

 **/

class Authentication extends Controller {

	var $CI;
	var $cfg;

	function Authentication() {
		$this->CI = get_instance();
		$this->CI->load->library('session');
		$this->cfg = $this->CI->config->item('macaw');
	}

	/**
	 * Authenticate a user
	 *
	 * Given a username and password, figure out whether or not we are logging
	 * in as the admin user or not. If we are the admin user, then we always
	 * authenticate against the local database using the SimpleLoginSecure
	 * module from the Code Igniter community. If not the admin user, then we
	 * see what kind of authentication is configured and use that method for
	 * checking the user's name and password. Currently only the "local"
	 * method is enabled.
	 *
	 * @param string [$user] The username of the person being authenticated
	 * @param string [$user] The users' password (unencrypted)
	 */
	function auth($user = '', $pass = '') {

		// Did we get a username and passowrd? If not, return false
		if(!$user || !$pass) { return false; }

		// Are we attempting to log in as the superuser?
		if (strtolower($user) == 'admin') {
			// If so, use local authentication
			$this->CI->load->library('Authentication/SimpleLoginSecure');
			return $this->CI->simpleloginsecure->login($user, $pass);

		} else {
			// Otherwise, get the auth method from the confing, load the
			// proper module and call the function there, returning whatever
			// it retrned.
			//if ($this->cfg['auth_method'] == 'local') {
				$this->CI->load->library('Authentication/SimpleLoginSecure');
				return $this->CI->simpleloginsecure->login($user, $pass);

			//} else {
			//	return false;
 			//}
		}
	}

	/**
	 * Un-Authenticate a user (log them out)
	 *
	 * Takes the currently logged in user from the session and logs them out
	 * making sure that we use the local authentication scheme for the
	 * admin user
	 */
	function deauth() {
		$this->CI->load->library('Authentication/SimpleLoginSecure');

		// Get the name of the user who is logged in.
		$user = strtolower($this->CI->session->userdata('username'));

		// Are we logged inas the superuser?
		if ($user == 'admin') {
			// If so, use local authentication to log out.
			$this->CI->load->library('Authentication/SimpleLoginSecure');
			$this->CI->simpleloginsecure->logout();

		} else {
			// Otherwise, get the auth method from the confing,
			// load the proper module and call the function there.
			//if ($this->cfg['auth_method'] == 'local') {
				$this->CI->load->library('Authentication/SimpleLoginSecure');
				$this->CI->simpleloginsecure->logout();

			//} else {
			//	return false;
			//}
		}
	}

	/** Clean out old, dead sessions
	 *
	 * Looks for sessions that are older than two weeks and deletes them from the database.
	 * We're assuming that people don't keep their browser running for more than that long.
	 */
	function clear_sessions() {
		$q = $this->CI->db->query('DELETE FROM session WHERE last_activity < (extract(epoch from now()) - (86400 * 14));');
	}

}
