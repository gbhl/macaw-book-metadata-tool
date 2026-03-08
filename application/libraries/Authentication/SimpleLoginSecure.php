<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * SimpleLoginSecure Class
 *
 * Makes authentication simple and secure.
 *
 * Simplelogin expects the following database setup. If you are not using
 * this setup you may need to do some tweaking.
 *
 *
 *   CREATE TABLE `users` (
 *     `id` int(10) unsigned NOT NULL auto_increment,
 *     `username` varchar(255) NOT NULL default '',
 *     `password` varchar(60) NOT NULL default '',
 *     `created` datetime NOT NULL default '0000-00-00 00:00:00' COMMENT 'Creation date',
 *     `modified` datetime NOT NULL default '0000-00-00 00:00:00',
 *     `last_login` datetime NULL default NULL,
 *     PRIMARY KEY  (`id`),
 *     UNIQUE KEY `username` (`username`),
 *   ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
 *
 * @package   SimpleLoginSecure
 * @version   1.0.1
 * @author    Alex Dunae, Dialect <alex[at]dialect.ca>
 * @copyright Copyright (c) 2008, Alex Dunae
 * @license   http://www.gnu.org/licenses/gpl-3.0.txt
 * @link      http://dialect.ca/code/ci-simple-login-secure/
 */
class SimpleLoginSecure {
	var $CI;

	/**
	 * Create a user account
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @param	bool
	 * @return	bool
	 */
	function create($username = '', $password = '', $auto_login = true) {
		$this->CI =& get_instance();

		//Make sure account info was sent
		if($username == '' OR $password == '') {
			return false;
		}

		//Check against user table
		$this->CI->db->where('username', $username);
		$query = $this->CI->db->get_where('account');

		if ($query->num_rows() > 0) //username already exists
			return false;

		$password_hashed = password_hash($password, PASSWORD_DEFAULT);
		//Insert account into the database
		date_default_timezone_set('America/New_York');
		$data = array(
					'username' => $username,
					'password' => $password_hashed,
					'created'  => date('c'),
					'modified' => date('c'),
				);

		$this->CI->db->set($data);

		if(!$this->CI->db->insert('account')) //There was a problem!
			return false;

		if($auto_login)
			$this->login($username, $password);

		return true;
	}

	/**
	 * Login and sets session variables
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @return	bool
	 */
	function login($username = '', $password = '') {
		$this->CI =& get_instance();

		if($username == '' OR $password == '')
			return false;

		//Check if already logged in
		if($this->CI->session->userdata('username') == $username)
			return true;

		//Check against user table
		$query = $this->CI->db->get_where('account', array('username' => $username));

		if ($query->num_rows() > 0) {
			$user_data = $query->row_array();

			// First we try with the modern way of doing things
			if (!password_verify($password, $user_data['password'])) {
				return false;
			}

			//Destroy old session
			foreach ($_SESSION as $key=>$value) {
				if (isset($_SESSION[$key])) {
					unset($_SESSION[$key]);
				}
			}
			$this->CI->db->query('UPDATE account SET last_login = NOW() WHERE id = '.$user_data['id']);

			//Set session data
			unset($user_data['password']);
			$user_data['user'] = $user_data['username']; // for compatibility with Simplelogin
			$user_data['logged_in'] = true;
			$this->CI->session->set_userdata($user_data);

			return true;
		}
		else {
			return false;
		}

	}

	/**
	 * Logout user
	 *
	 * @access	public
	 * @return	void
	 */
	function logout() {
		$this->CI =& get_instance();

		$this->CI->session->sess_destroy();
	}

	/**
	 * Delete user
	 *
	 * @access	public
	 * @param integer
	 * @return	bool
	 */
	function delete($id) {
		$this->CI =& get_instance();

		if(!is_numeric($id))
			return false;

		return $this->CI->db->delete('account', array('id' => $id));
	}

}
