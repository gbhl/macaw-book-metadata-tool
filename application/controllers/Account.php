<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Account Controller
 *
 * MACAW Metadata Collection and Workflow System
 *
 * Handles viewing and editing user account settings. Any user may view and
 * edit their own account. Admins and local admins may also edit other users
 * within their scope and add new accounts.
 *
 **/

class Account extends CI_Controller {

	var $cfg;

	public function __construct() {
		parent::__construct();
		$this->cfg = $this->config->item('macaw');
	}

	public function index() {
		redirect('account/settings');
	}

	/**
	 * Show an account settings page
	 *
	 * With no argument, shows the current user's own settings.
	 * With 'new', shows the add-account form (admins/local admins only).
	 * With a username, shows that user's settings (permission-checked).
	 *
	 * @access public
	 * @param string [$username] Username to edit, 'new' to add, or empty for self.
	 * @since Version 1.8
	 */
	public function settings($username = '') {
		if (!$this->common->check_session()) {
			return;
		}

		$current_user = $this->session->userdata('username');

		// Capture current user's permissions before any user object reloads
		$is_admin = $this->user->has_permission('admin');
		$is_local_admin = $this->user->has_permission('local_admin');

		$new = ($username === 'new');

		$datestring = "M d, Y h:i a";

		if ($new) {
			// Only admins and local admins can add accounts
			if (!$is_admin && !$is_local_admin) {
				$this->session->set_userdata('errormessage', 'You do not have permission to add accounts.');
				redirect('admin/account');
				return;
			}

			// Load a blank user object for the permissions list
			$this->user->load();
			$data['new'] = true;
			$data['is_self'] = false;
			$data['username'] = '';
			$data['full_name'] = '';
			$data['email'] = '';
			$data['created'] = '';
			$data['modified'] = '';
			$data['last_login'] = '';
			$data['permissions'] = $this->user->get_permissions();

			if ($is_admin) {
				$data['locked_org_id'] = false;
				$data['organizations'] = $this->organization->get_list();
				$data['org_name'] = '';
				$data['org_id'] = -1;
			} else {
				// local_admin: lock new user to their own org
				$this->user->load($current_user);
				$data['locked_org_id'] = true;
				$data['organizations'] = array();
				$data['org_name'] = $this->user->org_name;
				$data['org_id'] = $this->user->org_id;
			}

		} else {
			// Editing an existing account
			if (!$username) {
				$username = $current_user;
			}

			if (!$this->_can_edit($current_user, $username)) {
				$this->session->set_userdata('errormessage', 'You do not have permission to edit that account.');
				redirect('account/settings');
				return;
			}

			// _can_edit() reloads $this->user with current user — re-read permissions
			$is_admin = $this->user->has_permission('admin');
			$is_local_admin = $this->user->has_permission('local_admin');

			// Now load the target user
			$this->user->load($username);

			$data['new'] = false;
			$data['is_self'] = ($username === $current_user);
			$data['username'] = $username;
			$data['full_name'] = $this->user->full_name;
			$data['email'] = $this->user->email;
			$data['created'] = date($datestring, strtotime($this->user->created));
			$data['modified'] = date($datestring, strtotime($this->user->modified));
			$data['last_login'] = date($datestring, strtotime($this->user->last_login));
			$data['permissions'] = $this->user->get_permissions();
			$data['org_name'] = $this->user->org_name;
			$data['org_id'] = $this->user->org_id;

			if ($is_admin) {
				$data['locked_org_id'] = false;
				$data['organizations'] = $this->organization->get_list();
			} else {
				$data['locked_org_id'] = true;
				$data['organizations'] = array();
			}
		}

		$data['token'] = $this->session->userdata('li_token');
		$data['is_admin'] = $is_admin;
		$data['is_local_admin'] = $is_local_admin;
		$data['totp_enabled'] = (!$new && $data['is_self']) ? $this->user->totp_enabled : false;

		$this->load->view('account/settings_view', $data);
	}

	/**
	 * Show the TOTP setup page for the current user.
	 *
	 * Generates a new secret, stores it in the session pending verification,
	 * and renders a page with a QR code and manual-entry key.
	 *
	 * @access public
	 * @since Version 1.8
	 */
	public function totp_setup() {
		if (!$this->common->check_session()) {
			return;
		}

		$this->load->library('Totp');
		$this->load->library('QrCode');
		$secret = $this->totp->generate_secret();
		$this->session->set_userdata('totp_pending_secret', $secret);

		$username = $this->session->userdata('username');
		$otpauth = $this->totp->get_otpauth_url($secret, $username);
		$data['qr_svg'] = QrCode::svg($otpauth, 4, 1); // code, cell size, margin
		$data['secret'] = $secret;
		$data['token'] = $this->session->userdata('li_token');

		$this->load->view('account/totp_setup_view', $data);
	}

	/**
	 * Verify the submitted code and enable TOTP for the current user.
	 *
	 * @access public
	 * @since Version 1.8
	 */
	public function totp_enable() {
		if (!$this->common->check_session()) {
			return;
		}

		$secret = $this->session->userdata('totp_pending_secret');
		if (!$secret) {
			$this->session->set_userdata('errormessage', 'Setup session expired. Please try again.');
			redirect('account/totp_setup');
			return;
		}

		$this->load->library('Totp');
		if (!$this->totp->verify($secret, $this->input->post('totp_code'))) {
			$this->session->set_userdata('errormessage', 'Invalid verification code. Please try again.');
			redirect('account/totp_setup');
			return;
		}

		$username = $this->session->userdata('username');
		$this->db->where('username', $username);
		$this->db->update('account', array('totp_secret' => $secret, 'totp_enabled' => 1));
		$this->session->unset_userdata('totp_pending_secret');

		$this->session->set_userdata('message', 'Two-factor authentication has been enabled.');
		$this->logging->log('access', 'info', 'User ' . $username . ' enabled TOTP.');
		redirect('account/settings');
	}

	/**
	 * Disable TOTP for the current user.
	 *
	 * @access public
	 * @since Version 1.8
	 */
	public function totp_disable() {
		if (!$this->common->check_session()) {
			return;
		}

		$username = $this->session->userdata('username');
		$this->db->where('username', $username);
		$this->db->update('account', array('totp_secret' => null, 'totp_enabled' => 0));

		$this->session->set_userdata('message', 'Two-factor authentication has been disabled.');
		$this->logging->log('access', 'info', 'User ' . $username . ' disabled TOTP.');
		redirect('account/settings');
	}

	/**
	 * Save account settings (full-page form POST)
	 *
	 * Handles both adding a new user and updating an existing one.
	 * Redirects back with a success or error flash message.
	 *
	 * @access public
	 * @since Version 1.8
	 */
	public function settings_save() {
		if (!$this->common->check_session()) {
			return;
		}

		$current_user = $this->session->userdata('username');
		$is_admin = $this->user->has_permission('admin');
		$is_local_admin = $this->user->has_permission('local_admin');

		$full_name = $this->input->post('full_name');
		$email = $this->input->post('email');
		$password = $this->input->post('password');
		$password_c = $this->input->post('password_c');

		if (!$full_name || !$email) {
			$this->session->set_userdata('errormessage', 'Full name and email are required.');
			redirect($this->_return_url());
			return;
		}

		if ($password !== $password_c) {
			$this->session->set_userdata('errormessage', 'Passwords do not match.');
			redirect($this->_return_url());
			return;
		}

		if ($this->input->post('new')) {
			// ---- ADDING A NEW ACCOUNT ----
			if (!$is_admin && !$is_local_admin) {
				$this->session->set_userdata('errormessage', 'You do not have permission to add accounts.');
				redirect('main/listitems');
				return;
			}

			$new_username = $this->input->post('username');
			if (!$new_username) {
				$this->session->set_userdata('errormessage', 'A username is required.');
				redirect('account/settings/new');
				return;
			}

			$this->user->load();
			$this->user->full_name = $full_name;
			$this->user->email = $email;
			$this->user->org_id = $this->input->post('org_id');
			$this->user->password = $password;

			try {
				$this->user->add($new_username);
				$this->user->load($new_username);

				$perms = array();
				$posted_perms = $this->input->post('permissions');
				if (is_array($posted_perms)) {
					foreach ($posted_perms as $perm) {
						if ($perm == 'admin' && !$is_admin) {
							continue;
						}
						$perms[] = $perm;
					}
				}
				if ($perms) {
					$this->user->set_permissions($perms);
				}

				$this->session->set_userdata('message', 'Account created.');
				$this->logging->log('access', 'info', 'Added account: ' . $new_username);
				redirect('admin/account');

			} catch (Exception $e) {
				$this->session->set_userdata('errormessage', 'Error creating account: ' . $e->getMessage());
				$this->logging->log('error', 'debug', 'settings_save() new: ' . $e->getMessage());
				redirect('account/settings/new');
			}

		} else {
			// ---- EDITING AN EXISTING ACCOUNT ----
			$username = $this->input->post('username');

			if (!$this->_can_edit($current_user, $username)) {
				$this->session->set_userdata('errormessage', 'You do not have permission to edit that account.');
				redirect('account/settings');
				return;
			}

			// _can_edit() reloads $this->user with current user — re-read permissions
			$is_admin = $this->user->has_permission('admin');
			$is_local_admin = $this->user->has_permission('local_admin');

			try {
				$this->user->load($username);
				$this->user->full_name = $full_name;
				$this->user->email = $email;
				$this->user->org_id = $this->input->post('org_id');
				$this->user->password = $password;
				$this->user->update();

				if ($is_admin || $is_local_admin) {
					$perms = array();
					$posted_perms = $this->input->post('permissions');
					if (is_array($posted_perms)) {
						foreach ($posted_perms as $perm) {
							if ($perm == 'admin' && !$is_admin) {
								continue;
							}
							$perms[] = $perm;
						}
					}
					$this->user->set_permissions($perms);
				}

				// Keep session in sync when editing self
				if ($username === $current_user) {
					$this->session->set_userdata('full_name', $full_name);
					$this->session->set_userdata('email', $email);
				}

				$this->session->set_userdata('message', 'Account settings saved.');
				$this->logging->log('access', 'info', 'Updated account: ' . $username);

				redirect($username === $current_user ? 'account/settings' : 'admin/account');

			} catch (Exception $e) {
				$this->session->set_userdata('errormessage', 'Error saving account: ' . $e->getMessage());
				$this->logging->log('error', 'debug', 'settings_save() edit: ' . $e->getMessage());
				redirect('account/settings/' . $username);
			}
		}
	}

	/**
	 * Check whether the logged-in user may edit a target account.
	 *
	 * Mirrors the logic in Admin::_can_edit_account(). Side-effect: reloads
	 * $this->user with the current (logged-in) user's data.
	 *
	 * @param string $user   The logged-in username.
	 * @param string $target The username to be edited.
	 * @return bool Whether the person may edit the account or not
	 */
	private function _can_edit($user, $target) {
		$target_user = new User;
		$target_user->load($target);

		$this->user->load($this->session->userdata('username'));

		return ($user == 'admin' ||
		        $user == $target ||
		        $this->user->has_permission('admin') ||
		        ($this->user->has_permission('local_admin') &&
		         $this->user->org_id == $target_user->org_id &&
		         $target != 'admin'));
	}

	/**
	 * Determine where to redirect on validation failure.
	 * Uses the hidden `return_to` POST field if present, otherwise own settings.
	 */
	private function _return_url() {
		$rt = $this->input->post('return_to');
		return $rt ? $rt : 'account/settings';
	}
}
