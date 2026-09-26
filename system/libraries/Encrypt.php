<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 4.3.2 or newer
 *
 * @package		CodeIgniter
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2008, EllisLab, Inc.
 * @license		http://codeigniter.com/user_guide/license.html
 * @link		http://codeigniter.com
 * @since		Version 1.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * CodeIgniter Encryption Class
 *
 * Provides secure AES-256-GCM encryption with built-in authentication.
 *
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @category	Libraries
 * @author		ExpressionEngine Dev Team
 * @link		http://codeigniter.com/user_guide/libraries/encryption.html
 */
class CI_Encrypt {

	var $CI;
	var $encryption_key = '';

	const CIPHER = 'aes-256-gcm';
	const KEY_LENGTH = 32;
	const NONCE_LENGTH = 12;
	const TAG_LENGTH = 16;

	function __construct()
	{
		if (!extension_loaded('openssl')) {
			show_error('OpenSSL extension is required for encryption functionality.');
		}

		$this->CI =& get_instance();
		log_message('debug', "Encrypt Class Initialized");
	}

	/**
	 * Derives a cryptographic key from the encryption key using PBKDF2.
	 * OpenSSL requires a consistent key length (32 bytes for AES-256).
	 */
	function get_key($key = '')
	{
		if ($key == '')
		{
			if ($this->encryption_key != '')
			{
				$key = $this->encryption_key;
			}
			else
			{
				$CI =& get_instance();
				$key = $CI->config->item('encryption_key');

				if ($key === FALSE)
				{
					show_error('In order to use the encryption class requires that you set an encryption key in your config file.');
				}
			}
		}

		return hash_pbkdf2('sha256', $key, 'codeigniter-encryption', 100000, self::KEY_LENGTH, true);
	}

	/**
	 * Set the encryption key
	 */
	function set_key($key = '')
	{
		$this->encryption_key = $key;
	}

	/**
	 * Encrypt a string using AES-256-GCM.
	 * Returns base64-encoded: nonce + ciphertext + authentication tag
	 */
	function encode($string, $key = '')
	{
		$key = $this->get_key($key);
		$nonce = openssl_random_pseudo_bytes(self::NONCE_LENGTH);
		$tag = '';

		$ciphertext = openssl_encrypt(
			$string,
			self::CIPHER,
			$key,
			OPENSSL_RAW_DATA,
			$nonce,
			$tag
		);

		if ($ciphertext === false) {
			log_message('error', 'Encryption failed: ' . openssl_error_string());
			return FALSE;
		}

		return base64_encode($nonce . $ciphertext . $tag);
	}

	/**
	 * Decrypt a string encrypted with encode().
	 */
	function decode($string, $key = '')
	{
		$key = $this->get_key($key);

		if (preg_match('/[^a-zA-Z0-9\/\+=]/', $string))
		{
			return FALSE;
		}

		$data = base64_decode($string, true);
		if ($data === false || strlen($data) < self::NONCE_LENGTH + self::TAG_LENGTH)
		{
			return FALSE;
		}

		$nonce = substr($data, 0, self::NONCE_LENGTH);
		$tag = substr($data, -self::TAG_LENGTH);
		$ciphertext = substr($data, self::NONCE_LENGTH, -self::TAG_LENGTH);

		$plaintext = openssl_decrypt(
			$ciphertext,
			self::CIPHER,
			$key,
			OPENSSL_RAW_DATA,
			$nonce,
			$tag
		);

		if ($plaintext === false) {
			log_message('error', 'Decryption failed: ' . openssl_error_string());
			return FALSE;
		}

		return $plaintext;
	}

}

// END CI_Encrypt class

/* End of file Encrypt.php */
/* Location: ./system/libraries/Encrypt.php */