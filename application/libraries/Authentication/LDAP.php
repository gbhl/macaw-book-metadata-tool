<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class LDAP
{
	var $CI;

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

//
// 		//Check if already logged in
// 		if($this->CI->session->userdata('username') == $username)
// 			return true;
//
//
// 		//Check against user table
// 		$this->CI->db->where('username', $username);
// 		$query = $this->CI->db->getwhere($this->user_table);
//
//
// 		if ($query->num_rows() > 0)
// 		{
// 			$user_data = $query->row_array();
//
// 			$hasher = new PasswordHash(PHPASS_HASH_STRENGTH, PHPASS_HASH_PORTABLE);
//
// 			if(!$hasher->CheckPassword($password, $user_data['password']))
// 				return false;
//
// 			//Destroy old session
// 			$this->CI->session->sess_destroy();
//
// 			//Create a fresh, brand new session
// 			$this->CI->session->sess_create();
//
// 			$this->CI->db->simple_query('UPDATE ' . $this->user_table  . ' SET last_login = NOW() WHERE id = ' . $user_data['id']);
//
// 			//Set session data
// 			unset($user_data['password']);
// 			$user_data['user'] = $user_data['username']; // for compatibility with Simplelogin
// 			$user_data['logged_in'] = true;
// 			$this->CI->session->set_userdata($user_data);
//
// 			return true;
// 		}
// 		else
// 		{
// 			return false;
// 		}

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


}
