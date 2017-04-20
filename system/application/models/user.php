<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once(APPPATH.'libraries/Authentication/phpass-0.1/PasswordHash.php');

if (!defined('PHPASS_HASH_STRENGTH')) {
	define('PHPASS_HASH_STRENGTH', 8);
}
if (!defined('PHPASS_HASH_PORTABLE')) {
	define('PHPASS_HASH_PORTABLE', false);
}

/**
 * User Model
 *
 * MACAW Metadata Collection and Workflow System
 *
 * The user model is simply a placeholder for the user information including
 * their dashboard preferences.
 *
 **/

class User extends Model {

    public $username = '';
    public $password = '';
    public $last_login = '';
    public $created = '';
    public $modified = '';
    public $full_name = '';
    public $email = '';
    public $widgets = '';
    public $org_id = '';
    public $org_name = '';
    public $permissions = '';
    public $terms_conditions = '';

    private $all_permissions = array('scan', 'QA', 'local_admin', 'admin');

    function User()
    {
        // Call the Model constructor
        parent::Model();
    }

	/**
	 * Load the info for a user
	 *
	 * Loads a user's info (is this needed?)
	 *
	 * @since Version 1.0
	 */
	function load($username = '') {
		// Initialize us
		$this->_unload();

		// Did we get a username? If so, we can load the data.
		if ($username != '') {

			$this->db->select('account.*, organization.name as org_name');
			$this->db->join('organization', 'account.org_id = organization.id', 'left');
			$this->db->where('username', $username);
			$user = $this->db->get('account');

			if ($user->num_rows() < 1) {
				// No record, present an error
				throw new Exception("The user \"$username\" could not be found.");

			} else {
				// Yes, get the record and assign the info to our properties.
				// NOTE: The created, modified, etc can be set, but they are not
				// saved to the database. Is there a way to make a read-only
				// property in CI or PHP?
				$row = $user->row();

				$this->username					= $username;
				$this->password					= '';
				$this->last_login				= preg_replace('/\.\d+$/', '', $row->last_login);
				$this->created					= preg_replace('/\.\d+$/', '', $row->created);
				$this->modified					= preg_replace('/\.\d+$/', '', $row->modified);
				$this->widgets					= ($row->widgets ? $row->widgets : '[]');
				$this->full_name				= $row->full_name;
				$this->email						= $row->email;
				$this->org_id						= $row->org_id;
				$this->org_name					= $row->org_name;
				$this->terms_conditions	= $row->terms_conditions;
			}
		}
		// else, we've created a new, blank object WITHOUT a username
 	}

	/**
	 * Clear the user object
	 *
	 * Clears the current user's info from memory to be safe as we switch from
	 * one user to another.
	 *
	 * @since Version 1.2
	 */
 	function _unload() {
		$this->username					= '';
		$this->password					= '';
		$this->last_login				= '';
		$this->created					= '';
		$this->modified					= '';
		$this->widgets					= '';
		$this->full_name				= '';
		$this->email						= '';
		$this->org_id						= '';
	  $this->permissions			= '';
	  $this->terms_conditions = '';
 	}

	/**
	 * Save the data for a user.
	 *
	 * Saves the data for one user. It's assumed that we have already passed the
	 * permission checking and we can save the data. This includes setting a new
	 * password if the new password is set in the properties of the user object.
	 *
	 * @since Version 1.0
	 */

	function update() {
		// Build our array of data. The modified date is always set to the now()
		// of the database server.
		$data = array(
			'full_name' => $this->full_name,
			'email' => $this->email,
			'org_id' => $this->org_id,
			'modified' => date('Y-m-d H:i:s'),
			'widgets' => $this->widgets,
			'terms_conditions' => $this->terms_conditions,
		);

		// Handle the password, if it was passed in, hash it. We assume that the
		// password is accurate. Other places (javascript, our controller) make
		// sure that the password and confirmation match.
		if (isset($this->password) && $this->password != '') {
			$hasher = new PasswordHash(PHPASS_HASH_STRENGTH, PHPASS_HASH_PORTABLE);
			$pass_hash = $hasher->HashPassword($this->password);
			$data['password'] = $pass_hash;
		}

		// Save to the database.
		$this->db->where('username', $this->username);
		$this->db->update('account', $data);
	}

	/**
	 * Add a user to the system.
	 *
	 * Creates the entry for a user, but not before making sure that we got at least
	 * a username and that the username isn't already in use. Everything else is
	 * optional, including the password.
	 *
	 * @param string [$username] The name of the user we are looking for.
	 * @since Version 1.0
	 */
	function add($username = '') {
		if ($username) {
			// Make sure we're not doubling up on username,
			if ($this->_username_exists($username)) {
				throw new Exception("The username \"$username\" is already in use. Please choose another.");
			} else {

				// Build our array of data. The created date is always set to
				// the now() of the database server.
				$data = array(
					'username'  => $username,
					'full_name' => $this->full_name,
					'email'     => $this->email,
					'org_id' => $this->org_id,
					'terms_conditions' => null,
					'created'   => date('Y-m-d H:i:s'),
				);

				if ($this->widgets) {
					$data['widgets'] = $this->widgets;
				}

				// Handle the password, if it was passed in, hash it. We assume
				// that the password is accurate. Other places (javascript, our
				// controller) make sure that the password and confirmation match.
				if (isset($this->password)) {
					$hasher = new PasswordHash(PHPASS_HASH_STRENGTH, PHPASS_HASH_PORTABLE);
					$pass_hash = $hasher->HashPassword($this->password);
					$data['password'] = $pass_hash;
				}

				// Save to the database.
				$this->db->insert('account', $data);
			} // if ($this->_username_exists($username))
		} else {
			throw new Exception("A username was not supplied for the new account.");
		} // if ($username)
	}

	/**
	 * Does a username already exist?
	 *
	 * This performs a simple check to determine if the username is already in the database.
	 *
	 * @param string [$username] The name of the user we are looking for.
	 * @since Version 1.0
	 */
	function _username_exists($username) {
		$this->db->select('count(*) as tc');
		$this->db->where('username', $username);
		$q = $this->db->get('account');
		if ($q->row()->tc > 0) {
			return true;
		}
		return false;
	}

	/**
	 * Get all users in the system.
	 *
	 * Gets all the fields in the account table EXCEPT the password field. You don't need it.
	 *
	 * @since Version 1.0
	 */
	function get_list($org_id = 0) {
		// Simple query, get everyone, but list the fields we want. Password should always be hidden.
		$this->db->select(
			'account.id, account.username, account.last_login, account.created, account.modified, account.widgets, '.
			'account.full_name, account.email, organization.name as organization, '.
			'(select count(*) from permission p where permission = \'scan\' and p.username = account.username) as scan, '.
			'(select count(*) from permission p where permission = \'local_admin\' and p.username = account.username) as local_admin, '.
			'(select count(*) from permission p where permission = \'admin\' and p.username = account.username) as admin, '.
			'(select count(*) from permission p where permission = \'QA\' and p.username = account.username) as qa'
		);
		if ($org_id > 0) {
			$this->db->where('account.org_id', $org_id);
		}
		$this->db->order_by('username');
		$this->db->join('organization', 'account.org_id = organization.id', 'left');
		$l = $this->db->get('account')->result();
		for ($i=0; $i < count($l); $i++) {
			$l[$i]->last_login = preg_replace("/\.(\d+)$/","",$l[$i]->last_login);
			$l[$i]->created = preg_replace("/\.(\d+)$/","",$l[$i]->created);
			$l[$i]->modified = preg_replace("/\.(\d+)$/","",$l[$i]->modified);

			$l[$i]->last_login = preg_replace("/\-/","/",$l[$i]->last_login);
			$l[$i]->created = preg_replace("/\-/","/",$l[$i]->created);
			$l[$i]->modified = preg_replace("/\-/","/",$l[$i]->modified);
		}
		return $l;
	}

	/**
	 * Does the user have permission to do X?
	 *
	 * Given a permission, tell us whether the user has permission to do that thing or not.
	 *
	 * @param string [$perm] The name of permission in question.
	 * @since Version 1.2
	 */
	function has_permission($perm = '') {

		// Make sure we have loaded up the user's information
		if (!$this->username) {
			$this->load($this->session->userdata('username'));
		}

		// Permissions granted?
		$p = $this->get_permissions();

		// Error handling in case someone sent in garbage
		if (array_key_exists($perm, $p)) {
			// Is the permission activated
			if ($p[$perm] == 1) {
				return true;
			}
		}
		// Default: permissions DENINED! Muahaha!
		return false;
	}

	/**
	 * Lazy load the permissions
	 *
	 * Internal function to load the permissions if they aren't already laoded.
	 *
	 * @return array List of the user's permissions
	 *
	 * @since Version 1.2
	 */
	function get_permissions() {
		// Start the lazy loading process.
		if ($this->permissions == '') {
			// Get the data from the database
			$this->db->select('permission');
			$this->db->where('username', $this->username);
			$permissions = $this->db->get('permission')->result();

			// Massage it into a simple array of values
			$active_perms = array();
			foreach ($permissions as $p) {
				array_push($active_perms, $p->permission);
			}

			// Now make our final list of all permissions flagged yes or no
			$this->permissions = array();

			// Cycle through all available permissions
			foreach ($this->all_permissions as $a) {
				// Admin always has admin permissions
				if ($a == 'admin' && $this->username == 'admin') {
					$this->permissions[$a] = 1;
				} else {
					if (in_array($a, $active_perms)) {
						$this->permissions[$a] = 1;
					} else {
						$this->permissions[$a] = 0;
					} // if (in_array($a, $active_perms))
				} // if ($a == 'admin' && $this->username == 'admin')
			} // foreach ($this->all_permissions as $a)
		} // if ($this->permissions == '')
		return $this->permissions;
	}

	/**
	 * Save a user's permissions
	 *
	 * Internal function to load the permissions if they aren't already laoded.
	 *
	 * @return array List of the user's permissions
	 *
	 * @since Version 1.2
	 */
	function set_permissions($new_perms) {
		// Wipe the permissions from memory, just in case
		$this->permissions = null;

		// Delete the permissions from the database
		$this->db->where('username', $this->username);
		$this->db->delete('permission');

		// Use our list of available permissions to add what was sent in
		foreach ($this->all_permissions as $a) {
			// If an available perm matches one passed in...
			if (in_array($a, $new_perms)) {
				// ...we save to the database
				$this->db->insert('permission', array(
					'username' => $this->username,
					'permission' => $a
				));
			} // if (in_array($a, $new_perms))
		} // foreach ($this->all_permissions as $a)

		// Make sure admin always has admin privs
		if ($this->username == 'admin') {
			try {
				$this->db->insert('permission', array(
					'username' => $this->username,
					'permission' => 'admin'
				));
			} catch (exception $e) {
				/* We don't care about any likely to be duplicate key erros */
			}
		} // if ($this->username == 'admin')
	}
	
	function org_has_qa() {
		// Get a list of all QA users and their email addresses
		$qa_users = array();
		$this->db->where('username in (select username from permission where permission = \'QA\' and org_id = '.$this->org_id.');');
		$this->db->select('email');
		$query = $this->db->get('account');
		foreach ($query->result() as $row) {
			array_push($qa_users, $row->email);
		}
		return (count($qa_users) > 0);
		
	}

	/**
	 * Gets all the space used for a user's organization.
	 *
	 *
	 */	
	function get_space_used(){
		$result = $this->db->query(
			'SELECT coalesce(i.bytes, 0) as bytes '.
			'FROM organization o '.
			'LEFT OUTER JOIN ( '.
			'	SELECT sum(p.bytes) AS bytes, max(i.id) as id, max(i.status_code) AS status_code, max(i.org_id) as org_id, count(*) as pages '.
			'	FROM page p  '.
			'	INNER JOIN item i ON p.item_id = i.id  '.
			'	WHERE i.status_code NOT IN (\'completed\', \'exporting\')  '.
			'	GROUP BY i.org_id '.
			') i ON o.id = i.org_id '.
			'WHERE o.id = '.$this->org_id
		)->row()->bytes;
		return $result;
	}	
}



