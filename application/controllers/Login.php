<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Login Controller
 *
 * MACAW Metadata Collection and Workflow System
 *
 * Handles login, logout, password verification.
 *
 **/

class Login extends CI_Controller {

	/**
	 * Show the login page.
	 *
	 * This is the main page of the Macaw system. Shows an optional error or
	 * informative message at the top of the page should one exist in the
	 * session. the <Enter> key can be used to submit the form, but may not work
	 * in all browsers.
	 */
	public function index() {
		$data['username'] = '';

		$this->common->check_upgrade();
		$this->load->helper('form');

		if ($this->session->userdata('logged_in')) {
 			redirect($this->config->item('base_url').'dashboard');
		} else {
			$this->load->view('login/login_view', $data);
		}
	}

	/**
	 * See if a username and password match
	 *
	 * Takes a username and password and sends it to the proper authentication
	 * module. (either LDAP or local password.) Uses the "SimpleLoginSecure"
	 * helper. If a user fails to log in, then we clear the authentication
	 * just to be safe. Currently only "local password" authentication can be used.
	 *
	 * The username and password are taken from the POST data from the form. If a
	 * user is successfully logged in, then the SimpleLoginSecure module sets their
	 * username and an "is_logged_in" flag. The activiy of this function is logged
	 * so we know if/when someone is being bad.
	 */
	public function checklogin() {
		$user = $_POST['username'];
		$pass = $_POST['password'];

		$this->load->library('Authentication');

		if ($this->authentication->auth($user, $pass)) {
			// Check whether this account requires TOTP
			$account = $this->db->get_where('account', array('username' => $user))->row();
			if ($account && $account->totp_enabled && !empty($account->totp_secret)) {
				// Password OK but TOTP still needed — keep session data but mark as not fully logged in
				$this->session->set_userdata('logged_in', false);
				$this->session->set_userdata('totp_pending', true);
				$this->logging->log('access', 'info', 'User '.$user.' passed password, awaiting TOTP.');
				redirect($this->config->item('base_url').'login/totp');
			} else {
				$this->logging->log('access', 'info', 'User logged in.');
				redirect($this->config->item('base_url').'dashboard');
			}
		} else {
			$this->session->set_userdata('errormessage', 'You entered an incorrect username or password. Please try again.');
			$this->logging->log('access', 'info', 'User '.$user.' failed to log in.');
			$this->index();
		}
	}

	/**
	 * Show the TOTP verification page.
	 *
	 * Only accessible when a password-authenticated session is pending TOTP.
	 */
	public function totp() {
		if (!$this->session->userdata('totp_pending')) {
			redirect($this->config->item('base_url').'login');
			return;
		}
		$this->load->view('login/totp_view');
	}

	/**
	 * Verify the submitted TOTP code and complete the login.
	 */
	public function checktotp() {
		if (!$this->session->userdata('totp_pending')) {
			redirect($this->config->item('base_url').'login');
			return;
		}

		$username = $this->session->userdata('username');
		$code = $this->input->post('totp_code');

		$account = $this->db->get_where('account', array('username' => $username))->row();

		if (empty($account->totp_enabled) || empty($account->totp_secret)) {
			// TOTP no longer required — just complete the login
			$this->session->set_userdata('logged_in', true);
			$this->session->unset_userdata('totp_pending');
			redirect($this->config->item('base_url').'dashboard');
			return;
		}

		$this->load->library('Totp');
		if ($this->totp->verify($account->totp_secret, $code)) {
			$this->session->set_userdata('logged_in', true);
			$this->session->unset_userdata('totp_pending');
			$this->logging->log('access', 'info', 'User '.$username.' completed TOTP verification.');
			redirect($this->config->item('base_url').'dashboard');
		} else {
			$this->session->set_userdata('errormessage', 'Invalid verification code. Please try again.');
			$this->logging->log('access', 'info', 'User '.$username.' failed TOTP verification.');
			$this->totp();
		}
	}

	/**
	 * Log out the current user
	 *
	 * Clears the user's login status and returns to the login page. Also logs that
	 * the user logged out. In theory, this can be used even when you're logged out,
	 * but what's the point in that? Which raises the question, what username is
	 * sent to the log files when this is called while someone is logged out.
	 *
	 * This redirects to the login page when complete, too.
	 */
	public function logout() {
		$this->load->library('Authentication');
		$this->logging->log('access', 'info', 'User logged out.');

		$this->authentication->deauth();
		redirect($this->config->item('base_url').'login');
	}
}
