<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Password Validator Library
 *
 * MACAW Metadata Collection and Workflow System
 *
 * Validates password strength requirements.
 */

class PasswordValidator {

	private $min_length = 12;
	private $require_uppercase = true;
	private $require_lowercase = true;
	private $require_digit = true;
	private $require_special = true;

	/**
	 * Validate password strength
	 *
	 * Returns array with 'valid' boolean and 'errors' array of validation messages.
	 *
	 * @param string $password The password to validate
	 * @return array Validation result
	 */
	public function validate($password) {
		$errors = array();

		if (strlen($password) < $this->min_length) {
			$errors[] = 'Password must be at least ' . $this->min_length . ' characters long.';
		}

		if ($this->require_uppercase && !preg_match('/[A-Z]/', $password)) {
			$errors[] = 'Password must contain at least one uppercase letter.';
		}

		if ($this->require_lowercase && !preg_match('/[a-z]/', $password)) {
			$errors[] = 'Password must contain at least one lowercase letter.';
		}

		if ($this->require_digit && !preg_match('/[0-9]/', $password)) {
			$errors[] = 'Password must contain at least one digit.';
		}

		if ($this->require_special && !preg_match('/[!@#$%^&*()_+\-=\[\]{};:\'",.<>?\/\\|`~]/', $password)) {
			$errors[] = 'Password must contain at least one special character.';
		}

		return array(
			'valid' => count($errors) === 0,
			'errors' => $errors
		);
	}

	/**
	 * Get password requirements as a string
	 *
	 * @return string Human-readable password requirements
	 */
	public function get_requirements() {
		$reqs = array();
		$reqs[] = 'At least ' . $this->min_length . ' characters';
		if ($this->require_uppercase) {
			$reqs[] = 'At least one uppercase letter (A-Z)';
		}
		if ($this->require_lowercase) {
			$reqs[] = 'At least one lowercase letter (a-z)';
		}
		if ($this->require_digit) {
			$reqs[] = 'At least one digit (0-9)';
		}
		if ($this->require_special) {
			$reqs[] = 'At least one special character (!@#$%^&*)';
		}
		return implode(', ', $reqs);
	}
}
