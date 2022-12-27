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

		if($this->authentication->auth($user, $pass)) {
			$this->logging->log('access', 'info', 'User logged in.');
			// Clear out any old sessions. This should be speedy.
			$this->authentication->clear_sessions();
			
			redirect($this->config->item('base_url').'dashboard');
		} else {
			$this->session->set_userdata('errormessage', 'You entered an incorrect username or password. Please try again.');
			$this->logging->log('access', 'info', 'User '.$user.' failed to logged in.');
			$this->index();
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
}
