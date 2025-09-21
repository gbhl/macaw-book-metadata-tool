<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once('system/application/libraries/Authentication/phpass-0.1/PasswordHash.php');
// require_once('phpass-0.1/PasswordHash.php');

if (!defined('PHPASS_HASH_STRENGTH')) {
	define('PHPASS_HASH_STRENGTH', 8);
}
if (!defined('PHPASS_HASH_PORTABLE')) {
	define('PHPASS_HASH_PORTABLE', false);
}

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
class SimpleLoginSecure
{
	var $CI;
	var $user_table = 'account';

	/**
	 * Create a user account
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @param	bool
	 * @return	bool
	 */
	function create($username = '', $password = '', $auto_login = true)
	{
		$this->CI =& get_instance();

		//Make sure account info was sent
		if($username == '' OR $password == '') {
			return false;
		}

		//Check against user table
		$this->CI->db->where('username', $username);
		$query = $this->CI->db->getwhere($this->user_table);

		if ($query->num_rows() > 0) //username already exists
			return false;

		//Hash password using phpass
		$hasher = new PasswordHash(PHPASS_HASH_STRENGTH, PHPASS_HASH_PORTABLE);
		$password_hashed = $hasher->HashPassword($password);

		//Insert account into the database
		date_default_timezone_set('America/New_York');
		$data = array(
					'username' => $username,
					'password' => $password_hashed,
					'created'  => date('c'),
					'modified' => date('c'),
				);

		$this->CI->db->set($data);

		if(!$this->CI->db->insert($this->user_table)) //There was a problem!
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
	function login($username = '', $password = '')
	{
		$this->CI =& get_instance();

		if($username == '' OR $password == '')
			return false;


		//Check if already logged in
		if($this->CI->session->userdata('username') == $username)
			return true;


		//Check against user table
		$this->CI->db->where('username', $username);
		$query = $this->CI->db->getwhere($this->user_table);


		if ($query->num_rows() > 0)
		{
			$user_data = $query->row_array();

			$hasher = new PasswordHash(PHPASS_HASH_STRENGTH, PHPASS_HASH_PORTABLE);

			if(!$hasher->CheckPassword($password, $user_data['password']))
				return false;

			//Destroy old session
			$this->CI->session->sess_destroy();

			//Create a fresh, brand new session
			$this->CI->session->sess_create();

			$this->CI->db->simple_query('UPDATE ' . $this->user_table  . ' SET last_login = NOW() WHERE id = ' . $user_data['id']);

			//Set session data
			unset($user_data['password']);
			$user_data['user'] = $user_data['username']; // for compatibility with Simplelogin
			$user_data['logged_in'] = true;
			$this->CI->session->set_userdata($user_data);

			return true;
		}
		else
		{
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
	function delete($id)
	{
		$this->CI =& get_instance();

		if(!is_numeric($id))
			return false;

		return $this->CI->db->delete($this->user_table, array('id' => $id));
	}

}
