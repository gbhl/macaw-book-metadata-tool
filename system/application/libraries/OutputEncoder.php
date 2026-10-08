<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Context-Aware Output Encoder
 *
 * Provides safe, context-aware output encoding to prevent XSS vulnerabilities.
 * This is a modern, standards-based replacement for the legacy xss_clean() function.
 *
 * @package     Macaw
 * @subpackage  Libraries
 * @category    Security
 * @author      Security Team
 */

class OutputEncoder {

    /**
     * Instance of CI
     */
    protected $CI;

    /**
     * Constructor
     */
    public function __construct() {
        $this->CI =& get_instance();
    }

    /**
     * Encode for HTML content context
     *
     * Use this when outputting user data as HTML text content.
     * This converts: < > " ' & to their HTML entities
     *
     * @param string $str The string to encode
     * @param int $flags Optional flags for htmlspecialchars (default: ENT_QUOTES)
     * @return string The encoded string safe for HTML context
     *
     * @example
     * <p><?php echo $this->outputencoder->html($username); ?></p>
     */
    public function html($str, $flags = ENT_QUOTES) {
        return htmlspecialchars($str, $flags, 'UTF-8');
    }

    /**
     * Encode for HTML attribute value context
     *
     * Use this for values inside HTML attributes.
     * Same as html() but specifically for attributes.
     *
     * @param string $str The string to encode
     * @return string The encoded string safe for HTML attributes
     *
     * @example
     * <input value="<?php echo $this->outputencoder->attr($user_data); ?>">
     * <img alt="<?php echo $this->outputencoder->attr($alt_text); ?>">
     */
    public function attr($str) {
        return htmlspecialchars($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Encode for JavaScript context
     *
     * IMPORTANT: Avoid putting user data in JavaScript if possible.
     * This is complex and error-prone. Prefer data attributes or AJAX.
     *
     * Use this when you MUST embed user data in JavaScript code.
     * Converts to valid JSON with special characters escaped.
     *
     * @param mixed $data The data to encode (string, array, object, etc.)
     * @return string Valid JSON string safe for JavaScript context
     *
     * @example
     * <script>
     * var userData = <?php echo $this->outputencoder->javascript($user_object); ?>;
     * </script>
     *
     * Better alternative - use data attributes:
     * <div data-user="<?php echo $this->outputencoder->attr(json_encode($user)); ?>"></div>
     * Then parse with: JSON.parse(element.dataset.user)
     */
    public function javascript($data) {
        // Use json_encode with hex flags to escape dangerous characters
        return json_encode(
            $data,
            JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES
        );
    }

    /**
     * Encode for URL parameter context
     *
     * Use this for query string parameters and URL building.
     *
     * @param string $str The string to encode
     * @return string The URL-encoded string
     *
     * @example
     * <a href="search.php?q=<?php echo $this->outputencoder->url($search_term); ?>">
     */
    public function url($str) {
        return rawurlencode($str);
    }

    /**
     * Encode for CSS context
     *
     * Use this when user data is placed in CSS values.
     * WARNING: Avoid if possible. This is complex.
     *
     * @param string $str The string to encode
     * @return string The CSS-safe encoded string
     *
     * @example
     * <div style="color: <?php echo $this->outputencoder->css($user_color); ?>;">
     */
    public function css($str) {
        // Escape all non-alphanumeric characters
        return preg_replace_callback(
            '/[^a-zA-Z0-9]/',
            function($matches) {
                $char = $matches[0];
                $code = ord($char);
                // Keep hyphens and forward slashes unescaped (common in CSS)
                if ($char === '-' || $char === '/') {
                    return $char;
                }
                // Use hex escape for all other characters
                if ($code < 256) {
                    return sprintf('\\%02x ', $code);
                } else {
                    return sprintf('\\%x ', $code);
                }
            },
            $str
        );
    }

    /**
     * Strip tags safely
     *
     * Remove HTML/PHP tags while being careful about what's allowed.
     * More explicit than PHP's strip_tags().
     *
     * @param string $str The string to process
     * @param string $allowed Optional comma-separated list of tags to keep
     * @return string The string with tags removed
     *
     * @example
     * $safe = $this->outputencoder->stripTags($user_input, 'b,i,u,p');
     */
    public function stripTags($str, $allowed = '') {
        return strip_tags($str, $allowed);
    }

    /**
     * Validate and encode URL
     *
     * Ensures URL is safe and allowed (no javascript: protocol, etc.)
     * Encodes for use in href attributes.
     *
     * @param string $url The URL to validate
     * @return string Safe URL or empty string if invalid
     *
     * @example
     * <a href="<?php echo $this->outputencoder->safeUrl($user_url); ?>">
     */
    public function safeUrl($url) {
        // Remove whitespace
        $url = trim($url);

        // Allow http, https, and relative URLs
        if (
            preg_match('~^https?://~i', $url) ||
            preg_match('~^/~', $url) ||
            preg_match('~^\.{1,2}/~', $url)
        ) {
            return $this->attr($url);
        }

        // Allow mailto and tel
        if (preg_match('~^(mailto|tel):~i', $url)) {
            return $this->attr($url);
        }

        // Block everything else (javascript:, data:, vbscript:, etc.)
        return '';
    }

    /**
     * Encode for database storage (input)
     *
     * When saving to database, use parameterized queries instead!
     * This is deprecated in favor of:
     * - CodeIgniter's $this->db->insert()
     * - $this->db->update()
     * - $this->db->query() with parameter binding
     *
     * Only use this for rare cases where you MUST do string concatenation.
     *
     * @param string $str The string to escape
     * @return string The escaped string
     *
     * @example
     * // GOOD: Use parameterized queries
     * $this->db->query("INSERT INTO users (name) VALUES (?)", array($name));
     *
     * // BAD: Don't do this anymore (but if you must...)
     * $safe = $this->outputencoder->database($name);
     */
    public function database($str) {
        return $this->CI->db->escape_str($str);
    }

    /**
     * Smart encode - tries to detect context automatically
     *
     * WARNING: This is a fallback. Explicit context encoding is always better.
     * This uses htmlspecialchars which is safe for most HTML contexts.
     *
     * @param string $str The string to encode
     * @return string The encoded string
     *
     * @example
     * <?php echo $this->outputencoder->smart($user_input); ?>
     */
    public function smart($str) {
        // Default to HTML context (safest for most cases)
        return $this->html($str);
    }

    /**
     * Validate input is NOT malicious (input validation)
     *
     * Check that user input doesn't contain obvious XSS attempts.
     * NOTE: This is defense-in-depth only. Output encoding is the primary defense.
     *
     * @param string $str The string to validate
     * @return bool True if clean, false if suspicious
     *
     * @example
     * if (!$this->outputencoder->isClean($user_input)) {
     *     show_error("Invalid input detected");
     * }
     */
    public function isClean($str) {
        // Check for obvious XSS patterns
        $dangerous_patterns = array(
            '/<script/i',
            '/javascript:/i',
            '/on\w+\s*=/i',           // Event handlers: onclick=, onerror=
            '/<iframe/i',
            '/<object/i',
            '/<embed/i',
            '/expression\s*\(/i',
            '/vbscript:/i',
            '/data:text\/html/i',
        );

        foreach ($dangerous_patterns as $pattern) {
            if (preg_match($pattern, $str)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Log suspicious input (security monitoring)
     *
     * Track potential XSS attempts for security monitoring.
     * Should be called when suspicious input is detected.
     *
     * @param string $input The suspicious input
     * @param string $field The field name
     * @return void
     *
     * @example
     * if (!$this->outputencoder->isClean($input)) {
     *     $this->outputencoder->logSuspicious($input, 'comment_field');
     * }
     */
    public function logSuspicious($input, $field = 'unknown') {
        $user = $this->CI->session->userdata('username') ?: 'anonymous';
        $timestamp = date('Y-m-d H:i:s');

        log_message('security',
            "Suspicious input in field [{$field}] from user [{$user}] at [{$timestamp}]: " .
            substr($input, 0, 100) // Log first 100 chars only
        );
    }
}

/* End of file OutputEncoder.php */
/* Location: ./system/application/libraries/OutputEncoder.php */
