<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Organization Model
 *
 * MACAW Metadata Collection and Workflow System
 *
 * The organization model is simply a placeholder for the an organization to which 
 * people and items belong.
 *
 * @package admincontroller
 * @author Joel Richard
 * @version 1.7 organization.php created: 2012-03-19 last-modified: 2012-03-19
 **/

class Organization extends Model {

    public $id			= '';
    public $name		= '';
    public $person		= '';
    public $email		= '';
    public $phone		= '';
    public $address		= '';
    public $address2	= '';
    public $city		= '';
    public $state		= '';
    public $postal		= '';
    public $country		= '';
    public $created		= '';
    public $modified	= '';
    public $ia_api_key = '';
    public $ia_secret_key = '';

    private $all_permissions = array('scan', 'QA', 'admin');

    function Organization()
    {
        // Call the Model constructor
        parent::Model();
    }

	/**
	 * Load the info for a organization
	 *
	 * Loads a organization's info (is this needed?)
	 *
	 * @since Version 1.7
	 */
	function load($id = '') {
		// Initialize us
		$this->_unload();

		// Did we get an id? If so, we can load the data.
		if ($id != '') {
			$this->db->where('id', $id);
			$organization = $this->db->get('organization');

			if ($organization->num_rows() < 1) {
				// No record, present an error
				throw new Exception("The organization with id \"$id\" could not be found.");

			} else {
				// Yes, get the record and assign the info to our properties.
				// NOTE: The created, modified, etc can be set, but they are not
				// saved to the database. Is there a way to make a read-only
				// property in CI or PHP?
				$row = $organization->row();

				$this->id			= $id;
				$this->name			= $row->name;
				$this->person		= $row->person;
				$this->email		= $row->email;
				$this->phone		= $row->phone;
				$this->address		= $row->address;
				$this->address2		= $row->address2;
				$this->city			= $row->city;
				$this->state		= $row->state;
				$this->postal		= $row->postal;
				$this->country		= $row->country;
				$this->created		= $row->created;
				$this->modified		= $row->modified;

				// Handle Internet Archive API Keys
				if ($this->db->table_exists('custom_internet_archive_keys')) {
					$this->db->where('org_id', $id);			
					$keys = $this->db->get('custom_internet_archive_keys');
					if ($keys->num_rows() > 0) {
						$row = $keys->row();
						$this->ia_api_key		= $row->access_key;
						$this->ia_secret_key		= $row->secret;				
					}
				}				
			}
		}
 	}

	/**
	 * Clear the organization object
	 *
	 * Clears the current organization's info from memory.
	 *
	 * @since Version 1.7
	 */
 	function _unload() {
 		$this->name		= '';
 		$this->person	= '';
 		$this->email	= '';
 		$this->phone	= '';
 		$this->address	= '';
 		$this->address2	= '';
 		$this->city		= '';
 		$this->state	= '';
 		$this->postal	= '';
 		$this->country	= '';
 		$this->created	= '';
		$this->modified	= '';
		$this->ia_api_key		= '';
		$this->ia_secret_key = '';				
 	}

	/**
	 * Save the data for an organization.
	 *
	 *
	 * @since Version 1.7
	 */

	function update() {
		// Build our array of data. The modified date is always set to the now()
		// of the database server.
		$data = array(
			'name'		=> $this->name,
			'person'	=> $this->person,
			'email'		=> $this->email,
			'phone'		=> $this->phone,
			'address'	=> $this->address,
			'address2'	=> $this->address2,
			'city'		=> $this->city,
			'state'		=> $this->state,
			'postal'	=> $this->postal,
			'country'	=> $this->country,
			'modified'	=> 'now()'
		);

		// Save to the database.
		$this->db->where('id', $this->id);
		$this->db->update('organization', $data);

		// Handle Internet Archive API Keys
		if ($this->db->table_exists('custom_internet_archive_keys')) {
			$this->db->where('org_id', $id);			
			$keys = $this->db->get('custom_internet_archive_keys');
			// Do keys exist? Yes, update them
			if ($keys->num_rows() > 0) {
				$data = array(
					'access_key'		=> $this->ia_api_key,
					'secret'	      => $this->ia_secret_key
				);
		
				// Save to the database.
				$this->db->where('org_id', $this->id);
				$this->db->update('custom_internet_archive_keys', $data);
			// Do keys exist? No, add them
			} else {
				$data = array(
					'org_id'		=> $this->id,
					'access_key'		=> $this->ia_api_key,
					'secret'	=> $this->ia_secret_key
				);
		
				// Save to the database.
				$this->db->where('org_id', $this->id);
				$this->db->insert('custom_internet_archive_keys', $data);			
			}
		
		}
	}

	/**
	 * Add an organization to the system.
	 *
	 * Creates the entry for a organization
	 *
	 * @since Version 1.7
	 */
	function add() {
		// Build our array of data. The created date is always set to
		// the now() of the database server.
		$data = array(
			'name'		=> $this->name,
			'person'	=> $this->person,
			'email'		=> $this->email,
			'phone'		=> $this->phone,
			'address'	=> $this->address,
			'address2'	=> $this->address2,
			'city'		=> $this->city,
			'state'		=> $this->state,
			'postal'	=> $this->postal,
			'country'	=> $this->country,
			'created'   => 'now()',
		);

		// Save to the database.
		$this->db->insert('organization', $data);

		// Handle Internet Archive API Keys
		if ($this->db->table_exists('custom_internet_archive_keys')) {
			$data = array(
				'org_id'		=> $this->db->insert_id(),
				'access_key'		=> $this->ia_api_key,
				'secret'	=> $this->ia_secret_key
			);
	
			// Save to the database.
			$this->db->where('org_id', $this->id);
			$this->db->insert('custom_internet_archive_keys', $data);
		}

	}

	/**
	 * Removes an organization to the system.
	 *
	 * @since Version 1.7
	 */
	function delete() {
		if (_orgid_in_use($this->id)) {
			throw new Exception("Unable to delete a contributor that is associated to one or more accounts.");		
		} else {
			$this->where('id', $this->id);
			$this->delete('organization');

			// Handle Internet Archive API Keys
			if ($this->db->table_exists('custom_internet_archive_keys')) {
				$this->where('org_id', $this->id);
				$this->delete('custom_internet_archive_keys');
			}
		}
	}

	/**
	 * Is the organization in use by someone?
	 *
	 * This performs a simple check to determine if the org is linked to oneor more organizations.
	 *
	 * @param string [$id] The id of the organization we are looking for.
	 * @since Version 1.7
	 */
	function _orgid_in_use($id) {
		$this->db->select('count(*) as tc');
		$this->db->where('org_id', $id);
		$q = $this->db->get('account');
		if ($q->row()->tc > 0) {
			return true;
		}
		return false;
	}

	/**
	 * Get all organizations in the system.
	 *
	 * @since Version 1.7
	 */
	function get_list() {
		// Simple query, get everyone, but list the fields we want. Password should always be hidden.
		$this->db->order_by('name');
		$l = $this->db->get('organization')->result();
		for ($i=0; $i < count($l); $i++) {
			$l[$i]->created = preg_replace("/\.(\d+)$/","",$l[$i]->created);
			$l[$i]->modified = preg_replace("/\.(\d+)$/","",$l[$i]->modified);
			$l[$i]->created = preg_replace("/\-/","/",$l[$i]->created);
			$l[$i]->modified = preg_replace("/\-/","/",$l[$i]->modified);
		}
		return $l;
	}
}



