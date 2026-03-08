<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Totp Library
 *
 * Implements RFC 6238 Time-Based One-Time Password (TOTP) with no external
 * dependencies. Uses HMAC-SHA1 (RFC 4226 HOTP) as its underlying primitive.
 *
 * @package    Macaw
 * @subpackage Libraries
 */
class Totp {

	/**
	 * Base32 alphabet as defined in RFC 4648.
	 */
	const BASE32_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

	/**
	 * TOTP time step in seconds (RFC 6238 default).
	 */
	const TIME_STEP = 30;

	// --------------------------------------------------------------------

	/**
	 * Generate a cryptographically random base32-encoded secret.
	 *
	 * @param  int    $length  Number of base32 characters to produce (default 32).
	 *                         Each base32 character encodes 5 bits, so 32 chars = 160 bits.
	 * @return string          Uppercase base32-encoded secret.
	 */
	public function generate_secret($length = 32)
	{
		$alphabet = self::BASE32_ALPHABET;
		$secret   = '';

		// random_bytes() is available from PHP 7.0 and is cryptographically secure.
		$random_bytes = random_bytes($length);

		for ($i = 0; $i < $length; $i++)
		{
			$secret .= $alphabet[ord($random_bytes[$i]) & 31];
		}

		return $secret;
	}

	// --------------------------------------------------------------------

	/**
	 * Verify a 6-digit TOTP code against a base32-encoded secret.
	 *
	 * Checks the current time step plus/minus $window steps to allow for
	 * minor clock skew between the server and the authenticator device.
	 *
	 * @param  string $secret  Base32-encoded shared secret.
	 * @param  string $code    6-digit code supplied by the user.
	 * @param  int    $window  Number of time steps to check on each side (default 1).
	 * @return bool            TRUE if the code is valid, FALSE otherwise.
	 */
	public function verify($secret, $code, $window = 1)
	{
		// Normalise: strip whitespace, ensure string comparison is safe.
		$code = trim($code);

		if (strlen($code) !== 6 || ! ctype_digit($code))
		{
			return FALSE;
		}

		$binary_secret = $this->_base32_decode($secret);

		if ($binary_secret === FALSE)
		{
			return FALSE;
		}

		$current_counter = (int) floor(time() / self::TIME_STEP);

		for ($offset = -$window; $offset <= $window; $offset++)
		{
			$expected = $this->_get_hotp($binary_secret, $current_counter + $offset);

			// Use hash_equals() to prevent timing attacks.
			if (hash_equals($expected, $code))
			{
				return TRUE;
			}
		}

		return FALSE;
	}

	// --------------------------------------------------------------------

	/**
	 * Build the otpauth URI for import into an authenticator app.
	 *
	 * Format: otpauth://totp/ISSUER:USERNAME?secret=SECRET&issuer=ISSUER&algorithm=SHA1&digits=6&period=30
	 *
	 * @param  string $secret    Base32-encoded shared secret.
	 * @param  string $username  Account identifier (e.g. username or email).
	 * @param  string $issuer    Service name shown in the authenticator (default 'Macaw').
	 * @return string            otpauth:// URI.
	 */
	public function get_otpauth_url($secret, $username, $issuer = 'Macaw')
	{
		$label = rawurlencode($issuer) . ':' . rawurlencode($username);

		$params = http_build_query(array(
			'secret'    => $secret,
			'issuer'    => $issuer,
			'algorithm' => 'SHA1',
			'digits'    => 6,
			'period'    => self::TIME_STEP,
		));

		return 'otpauth://totp/' . $label . '?' . $params;
	}

	// --------------------------------------------------------------------
	// Private helpers
	// --------------------------------------------------------------------

	/**
	 * Decode a base32 string (RFC 4648) into raw binary.
	 *
	 * Strips whitespace, converts to uppercase, and silently skips any
	 * characters that are outside the base32 alphabet.
	 *
	 * @param  string $secret  Base32-encoded string.
	 * @return string|false    Raw binary string, or FALSE on failure.
	 */
	private function _base32_decode($secret)
	{
		// Normalise input.
		$secret = strtoupper(str_replace(' ', '', $secret));

		if (strlen($secret) === 0)
		{
			return FALSE;
		}

		$alphabet = self::BASE32_ALPHABET;
		$lookup   = array_flip(str_split($alphabet));

		$binary = '';
		$buffer = 0;
		$bits   = 0;

		for ($i = 0, $len = strlen($secret); $i < $len; $i++)
		{
			$char = $secret[$i];

			// Skip padding characters and anything not in the alphabet.
			if ($char === '=' || ! isset($lookup[$char]))
			{
				continue;
			}

			$buffer = ($buffer << 5) | $lookup[$char];
			$bits  += 5;

			if ($bits >= 8)
			{
				$bits   -= 8;
				$binary .= chr(($buffer >> $bits) & 0xFF);
			}
		}

		return $binary;
	}

	// --------------------------------------------------------------------

	/**
	 * Compute an HOTP value (RFC 4226) for the given binary secret and counter.
	 *
	 * Steps:
	 *  1. Pack the counter as a 64-bit big-endian integer.
	 *  2. Compute HMAC-SHA1 of the packed counter using the binary secret.
	 *  3. Perform dynamic truncation to derive a 31-bit integer.
	 *  4. Reduce modulo 1,000,000 and zero-pad to 6 decimal digits.
	 *
	 * @param  string $binary_secret  Raw (not base32) shared secret bytes.
	 * @param  int    $counter        HOTP counter value (current TOTP time step).
	 * @return string                 Zero-padded 6-digit OTP string.
	 */
	private function _get_hotp($binary_secret, $counter)
	{
		// Pack counter as 64-bit big-endian (PHP has no native 64-bit pack format
		// on 32-bit builds, so we split it into two 32-bit unsigned big-endian words).
		$packed_counter = pack('N', 0) . pack('N', $counter);

		// HMAC-SHA1 produces a 20-byte (160-bit) digest.
		$hash = hash_hmac('sha1', $packed_counter, $binary_secret, TRUE);

		// Dynamic truncation: use the low-order 4 bits of the last byte as offset.
		$offset = ord($hash[19]) & 0xF;

		// Extract 4 bytes at the offset and mask the most significant bit.
		$otp = (
			((ord($hash[$offset])     & 0x7F) << 24) |
			((ord($hash[$offset + 1]) & 0xFF) << 16) |
			((ord($hash[$offset + 2]) & 0xFF) <<  8) |
			 (ord($hash[$offset + 3]) & 0xFF)
		) % 1000000;

		// Zero-pad to exactly 6 digits.
		return str_pad((string) $otp, 6, '0', STR_PAD_LEFT);
	}

}
