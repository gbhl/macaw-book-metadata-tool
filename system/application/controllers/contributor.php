<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Admin Controller
 *
 * MACAW Metadata Collection and Workflow System
 *
 * Governs administrative activities such editing users and contributros, 
 * maintenance, export queues.
 *
 **/

class Contributor extends Controller {

	var $cfg;

	/* LOCAL ADMIN COMPLETED */
	function __construct() {
		parent::__construct();
		$this->cfg = $this->config->item('macaw');
	}

	function index() {
		$this->common->check_session();
		// Permission Checking
		if (!$this->user->has_permission('admin')) {
			$this->session->set_userdata('errormessage', 'You do not have permission to access that page.');
			redirect($this->config->item('base_url').'main/listitems');
			$this->logging->log('error', 'debug', 'Permission Denied to access '.uri_string());
		}

		$this->load->view('contributor/contributor_view');
	}

	/**
	 * Get a list of all organizations
	 *
	 *
	 * @since Version 1.7
	 */
	/* LOCAL ADMIN COMPLETED */
	function list() {
		// Make sure we are logged in and stuff
		if (!$this->common->check_session(true)) {
			return;
		}
		if (!$this->user->has_permission('admin')) {
			$this->common->ajax_headers();
			echo json_encode(array('error' => 'Permission denied.'));
			return;
		}

		$this->common->ajax_headers();
		echo json_encode($this->organization->get_list());
	}

		/**
	 * Edit an organization
	 *
	 *
	 * @param string [$id] The name of the organization to edit.
	 * @since Version 1.7
	 */
	/* LOCAL ADMIN COMPLETED */
	function edit($id = 0) {
		// Make sure we are logged in and stuff
		if (!$this->common->check_session(true)) {
			return;
		}

		// Allow admins to edit any contributor
		// Allow local admins to edit their own contributor
		$continue = false;
		if ($this->user->has_permission('admin')) { $continue = true; }
		if ($this->user->has_permission('local_admin') && $this->user->id == $id) { $continue = true; }
		if (!$continue) {
			$this->session->set_userdata('errormessage', "Permission denied to edit a contributor.");
			$this->logging->log('error', 'debug', 'Permission denied to edit a contributor ID='.$id);
			redirect('contributor');
		}

		// If we didn't get an ID on the URL, we assume we are editing ourself.
		if (!$id) {
			$this->session->set_userdata('errormessage', "Please select an organization to edit.");
			redirect('contributor');
		}

		// Make sure we can edit the Organization in question
		try {
			// Load the record for the organization
			$this->organization->load($id);

			// Get the data with which to fill the screen
			$datestring = "M d, Y h:i a";
			$data['new'] = false;
			$data['id'] = $this->organization->id;
			$data['name'] = $this->organization->name;
			$data['person'] = $this->organization->person;
			$data['email'] = $this->organization->email;
			$data['phone'] = $this->organization->phone;
			$data['address'] = $this->organization->address;
			$data['address2'] = $this->organization->address2;
			$data['city'] = $this->organization->city;
			$data['state'] = $this->organization->state;
			$data['postal'] = $this->organization->postal;
			$data['country'] = $this->organization->country;
			$data['created'] = $this->organization->created;
			$data['modified'] = $this->organization->modified;
			$data['show_api_keys'] = false;
			if ($this->db->table_exists('custom_internet_archive_keys')) {
				$data['show_api_keys'] = true;
				$data['api_key'] = $this->organization->ia_api_key;
				$data['secret_key'] = $this->organization->ia_secret_key;
			}
			$data['token'] = $this->session->userdata('li_token');

			// Display the page
			$this->load->view('contributor/contributor_edit_page', $data);

		} catch (Exception $e) {
			// This handles anything strange that might come across while getting the organization object.
			$this->session->set_userdata('errormessage', $e->getMessage());
			redirect('contributor');
		}
	}

	/**
	 * Add a new organization
	 *
	 * AJAX
	 *
	 * @since Version 1.7
	 */
	function add() {
		// Make sure we are logged in and stuff
		if (!$this->common->check_session(true)) {
			return;
		}
		if (!$this->user->has_permission('admin')) {
			$this->session->set_userdata('errormessage', 'Permission denied to add a contributor.');
			$this->logging->log('error', 'debug', 'Permission denied to add a contributor.');
			redirect('contributor');
		}

		$this->organization->load();
		$data['new'] = true;
		$data['name'] = '';
		$data['person'] = '';
		$data['email'] = '';
		$data['phone'] = '';
		$data['address'] = '';
		$data['address2'] = '';
		$data['city'] = '';
		$data['state'] = '';
		$data['postal'] = '';
		$data['country'] = '';
		$data['created'] = '';
		$data['modified'] = '';
		$data['show_api_keys'] = false;
		if ($this->db->table_exists('custom_internet_archive_keys')) {
			$data['show_api_keys'] = true;
			$data['api_key'] = '';
			$data['secret_key'] = '';
		}
		$data['id'] = 0;
		$data['token'] = $this->session->userdata('li_token');

		// Display the page
		$this->load->view('contributor/contributor_add_page', $data);
	}

	/**
	 * Save changes to an contributor
	 *
	 * AJAX: Gets the list of files for this book and their status as to being
	 * scanned and processed. The data comes from the database, which is in
	 * turn populated by the cron job.
	 *
	 * @since Version 1.7
	 */
	function save() {
		// Make sure we are logged in and stuff
		if (!$this->common->check_session(true)) {
			return;
		}


		if ($this->input->post('new')) { // WE ARE ADDING A NEW ORG

			// Allow admins to edit any contributor
			if (!$this->user->has_permission('admin')) {
				$this->session->set_userdata('errormessage', 'Permission denied to saving a new contributor.');
				$this->logging->log('error', 'debug', 'Permission denied to saving a new contributor.');
				redirect('contributor');
			}

			// Force the organization object to re-initialize
			$this->organization->load();

			// Set the organization's data
			$this->organization->name = $this->input->post('name');
			$this->organization->person = $this->input->post('person');
			$this->organization->email = $this->input->post('email');
			$this->organization->phone = $this->input->post('phone');
			$this->organization->address = $this->input->post('address');
			$this->organization->address2 = $this->input->post('address2');
			$this->organization->city = $this->input->post('city');
			$this->organization->state = $this->input->post('state');
			$this->organization->postal = $this->input->post('postal');
			$this->organization->country = $this->input->post('country');
			if ($this->db->table_exists('custom_internet_archive_keys')) {
				$this->organization->ia_api_key = $this->input->post('api_key');
				if (trim($this->input->post('secret_key'))) {
					// Save only if we have a value
					$this->organization->ia_secret_key = $this->input->post('secret_key');
				}
				
			}
		
			try {
				// Add the organization, with proper error handling
				$this->organization->add();

				// Redirect to organization list on success
				$this->session->set_userdata('message', 'Contributor added!');
				$this->logging->log('access', 'info', 'Added contributor '.$this->input->post('name'));
				redirect('contributor');
			} catch (Exception $e) {
				// This handles anything strange that might come across while getting the organization object.
				$this->session->set_userdata('errormessage', $e->getMessage());
				$this->logging->log('error', 'debug', 'Inside organization_save() (new): '.$e->getMessage());
				redirect('contributor/add');
			}
		} else { // WE ARE EDITING AN EXISTING ORG

			// Allow admins to edit any contributor
			// Allow local admins to edit their own contributor
			$continue = false;
			if ($this->user->has_permission('admin')) { $continue = true; }
			if ($this->user->has_permission('local_admin') && $this->user->id == $this->input->post('id')) { $continue = true; }
			if (!$continue) {
				$this->session->set_userdata('errormessage', 'Permission denied to edit the contributor.');
				$this->logging->log('error', 'debug', 'Permission denied to edit the contributor "'.$this->input->post('name'));
				redirect('contributor');
			}

			// Get the data from the POST and make it into something useful
			// Load the organization based on the id passed
			$this->organization->load($this->input->post('id'));

			// Update the data
			$this->organization->name = $this->input->post('name');
			$this->organization->person = $this->input->post('person');
			$this->organization->email = $this->input->post('email');
			$this->organization->phone = $this->input->post('phone');
			$this->organization->address = $this->input->post('address');
			$this->organization->address2 = $this->input->post('address2');
			$this->organization->city = $this->input->post('city');
			$this->organization->state = $this->input->post('state');
			$this->organization->postal = $this->input->post('postal');
			$this->organization->country = $this->input->post('country');
			if ($this->db->table_exists('custom_internet_archive_keys')) {
				if (!trim($this->input->post('api_key')) && trim($this->input->post('secret_key'))) {
					$this->session->set_userdata('errormessage', 'Both API Key and Secret Key are required.');
				} elseif (!trim($this->input->post('api_key'))) {
					// clear the secret key when the api key is empty
					$this->organization->ia_api_key = '';
					$this->organization->ia_secret_key = '';
				} else {				
					$this->organization->ia_api_key = $this->input->post('api_key');
					// save the secret only if something was supplied
					if (trim($this->input->post('secret_key'))) {
						$this->organization->ia_secret_key = $this->input->post('secret_key');
					}

				}
			}

			try {
				// Update the organization, with proper error handling
				$this->organization->update();

				// Redirect to organization list on success
				$this->session->set_userdata('message', 'Changes saved!');
				$this->logging->log('access', 'info', 'Updated Contributor: '.$this->input->post('name'). ' (id '.$this->input->post('id').')');
				redirect('contributor');
			} catch (Exception $e) {
				// This handles anything strange that might come across while getting the organization object.
				$this->session->set_userdata('errormessage', $e->getMessage());
				$this->logging->log('error', 'debug', 'Inside organization_save() (update): '.$e->getMessage());
				redirect('contributor/edit/'.$this->input->post('id'));
			}
		}
	}

	/**
	 * Delete an organization
	 *
	 * AJAX: Only admins can delete organizations. We clear the permissions table and the
	 * organizations table. That's it.
	 *
	 * @since Version 1.2
	 */
	function delete($id) {
		if (!$this->common->check_session(true)) {
			return;
		}
		// Allow admins to delete any contributor
		if (!$this->user->has_permission('admin')) {
			$this->common->ajax_headers();
			echo json_encode(array('error' => 'Permission denied.'));
			$this->logging->log('error', 'debug', 'Permission denied to delete the contributor "'.$id);
			return;
		}
		if (!isset($id)) {
			$this->common->ajax_headers();
			echo json_encode(array('error' => 'You did not supply the ID of an contributor to delete.'));
			$this->logging->log('error', 'debug', 'No contributor ID supplied for deletion.');		
		}

		$this->db->where('id', $id);
		$this->db->delete('organization');

		if ($this->db->table_exists('custom_internet_archive_keys')) {
			$this->db->where('org_id', $id);
			$this->db->delete('custom_internet_archive_keys');
		}
		
		$this->common->ajax_headers();
		echo json_encode(array('message' => 'Contributor deleted.'));
		$this->logging->log('access', 'info', 'Deleted Contributor '.$id);
	}


}

