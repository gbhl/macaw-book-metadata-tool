<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Login Controller
 *
 * MACAW Metadata Collection and Workflow System
 *
 * Handles login, logout, password verification.
 *
 **/

class Login extends Controller {

	function __construct() {
		parent::__construct();
		$this->load->library('PasswordValidator');
	}

	/**
	 * Show the login page.
	 *
	 * This is the main page of the Macaw system. Shows an optional error or
	 * informative message at the top of the page should one exist in the
	 * session. the <Enter> key can be used to submit the form, but may not work
	 * in all browsers.
	 *
	 * @todo Make sure enter works in all browsers. Hah!
	 * 
	 *  Tests:
	 *
	 */
	function index() {
		$data['username'] = '';

		$this->common->check_upgrade();
		
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
	function checklogin() {
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
	function logout() {
		$this->load->library('Authentication');
		$this->logging->log('access', 'info', 'User logged out.');

		$this->authentication->deauth();
		redirect($this->config->item('base_url').'login');
	}

	/**
	 * Show the forgot password form
	 */
	function forgot_password() {
		if ($this->session->userdata('logged_in')) {
			redirect($this->config->item('base_url').'dashboard');
		}
		$this->load->view('login/forgot_password_view');
	}

	/**
	 * Process forgot password request
	 */
	function request_password_reset() {
		$email = trim($this->input->post('email'));

		if (!$email) {
			$this->session->set_userdata('errormessage', 'Please enter your email address.');
			redirect($this->config->item('base_url').'login/forgot_password');
			return;
		}

		$account = $this->db->get_where('account', array('email' => $email))->row();

		if (!$account || !$account->email) {
			$this->session->set_userdata('message', 'If an account exists with that email address, you will receive a message with a password reset link.');
			redirect($this->config->item('base_url').'login');
			return;
		}

		$token = bin2hex(random_bytes(32));
		$expires = date('Y-m-d H:i:s', time() + 3600);

		$this->db->insert('password_reset_tokens', array(
			'account_id' => $account->id,
			'token' => $token,
			'expires' => $expires
		));

		$reset_url = $this->config->item('base_url').'login/reset_password/'.$token;

		$cfg = $this->config->item('macaw');
		$email_config = array(
			'protocol' => 'smtp',
			'mailtype' => 'html',
			'crlf' => '\r\n',
			'newline' => '\r\n',
			'smtp_host' => $cfg['email_smtp_host'],
			'smtp_port' => $cfg['email_smtp_port'],
		);
		if ($cfg['email_smtp_user']) { $email_config['smtp_user'] = $cfg['email_smtp_user']; }
		if ($cfg['email_smtp_pass']) { $email_config['smtp_pass'] = $cfg['email_smtp_pass']; }
		if ($cfg['email_smtp_crypto']) { $email_config['smtp_crypto'] = $cfg['email_smtp_crypto']; }

		$this->load->library('email');
		$this->email->initialize($email_config);
		$this->email->from($cfg['admin_email'], 'Macaw Admin');
		$this->email->to($account->email);
		$this->email->subject('[Macaw] Password Reset Request');
		$this->email->message(
			'<html><body>'.
			'<p>A password reset has been requested for your Macaw account.</p>'.
			'<p>Click the link below to reset your password (this link will expire in 1 hour):</p>'.
			'<p><a href="'.$reset_url.'">'.$reset_url.'</a></p>'.
			'<p>If you did not request this reset, please ignore this email.</p>'.
			'</body></html>'
		);

		if ($this->email->send()) {
			$this->logging->log('access', 'error', 'Sent password reset email to '.$email);
			$this->session->set_userdata('message', 'If an account exists with that email address, you will receive a message with a password reset link.');
		} else {
			$this->logging->log('access', 'error', 'Failed to send password reset email to '.$email);
			$this->session->set_userdata('errormessage', 'Failed to send password reset email.');
		}

		redirect($this->config->item('base_url').'login');
	}

	/**
	 * Show the password reset form
	 */
	function reset_password($token = '') {
		if ($this->session->userdata('logged_in')) {
			redirect($this->config->item('base_url').'dashboard');
		}

		if (!$token) {
			$this->session->set_userdata('errormessage', 'Invalid or missing reset token.');
			redirect($this->config->item('base_url').'login');
			return;
		}

		$reset = $this->db->get_where('password_reset_tokens', array('token' => $token))->row();

		if (!$reset || $reset->used || strtotime($reset->expires) < time()) {
			$this->session->set_userdata('errormessage', 'Password reset link has expired or is invalid.');
			redirect($this->config->item('base_url').'login');
			return;
		}

		$data['token'] = $token;
		$this->load->view('login/reset_password_view', $data);
	}

	/**
	 * Process password reset submission
	 */
	function process_reset_password() {
		$token = $this->input->post('token');
		$password = $this->input->post('password');
		$password_confirm = $this->input->post('password_confirm');

		if (!$token) {
			$this->session->set_userdata('errormessage', 'Invalid or missing reset token.');
			redirect($this->config->item('base_url').'login');
			return;
		}

		if (!$password || !$password_confirm) {
			$this->session->set_userdata('errormessage', 'Please enter and confirm your new password.');
			redirect($this->config->item('base_url').'login/reset_password/'.$token);
			return;
		}

		if ($password !== $password_confirm) {
			$this->session->set_userdata('errormessage', 'Passwords do not match.');
			redirect($this->config->item('base_url').'login/reset_password/'.$token);
			return;
		}

		$validation = $this->passwordvalidator->validate($password);
		if (!$validation['valid']) {
			$this->session->set_userdata('errormessage', 'Password does not meet requirements: ' . implode(' ', $validation['errors']));
			redirect($this->config->item('base_url').'login/reset_password/'.$token);
			return;
		}

		$reset = $this->db->get_where('password_reset_tokens', array('token' => $token))->row();

		if (!$reset || $reset->used || strtotime($reset->expires) < time()) {
			$this->session->set_userdata('errormessage', 'Password reset link has expired or is invalid.');
			redirect($this->config->item('base_url').'login');
			return;
		}
		require_once(APPPATH.'libraries/Authentication/phpass-0.1/PasswordHash.php');
		if (!defined('PHPASS_HASH_STRENGTH')) {
			define('PHPASS_HASH_STRENGTH', 8);
		}
		if (!defined('PHPASS_HASH_PORTABLE')) {
			define('PHPASS_HASH_PORTABLE', false);
		}
		$hasher = new PasswordHash(PHPASS_HASH_STRENGTH, PHPASS_HASH_PORTABLE);
		$hashed_password = $hasher->HashPassword($password);

		$this->db->update('account',
			array('password' => $hashed_password),
			array('id' => $reset->account_id)
		);

		$this->db->update('password_reset_tokens',
			array('used' => date('Y-m-d H:i:s')),
			array('id' => $reset->id)
		);

		$account = $this->db->get_where('account', array('id' => $reset->account_id))->row();
		$this->logging->log('access', 'info', 'User '.$account->username.' reset their password.');

		$this->session->set_userdata('message', 'Your password has been successfully reset. You can now log in with your new password.');
		redirect($this->config->item('base_url').'login');
	}
}
