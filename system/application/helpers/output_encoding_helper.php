<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Output Encoding Helper Functions
 *
 * Convenience functions for safe, context-aware output encoding.
 * Provides short aliases for the OutputEncoder library.
 *
 * @package     Macaw
 * @subpackage  Helpers
 * @category    Security
 */

/**
 * Encode for HTML content - the most common use case
 *
 * @param string $str
 * @return string
 */
if (!function_exists('h')) {
    function h($str) {
        $CI =& get_instance();
        return $CI->outputencoder->html($str);
    }
}

/**
 * Encode for HTML attributes
 *
 * @param string $str
 * @return string
 */
if (!function_exists('attr')) {
    function attr($str) {
        $CI =& get_instance();
        return $CI->outputencoder->attr($str);
    }
}

/**
 * Encode for JavaScript context (use sparingly!)
 *
 * @param mixed $data
 * @return string
 */
if (!function_exists('js')) {
    function js($data) {
        $CI =& get_instance();
        return $CI->outputencoder->javascript($data);
    }
}

/**
 * Encode for URL context
 *
 * @param string $str
 * @return string
 */
if (!function_exists('url_encode')) {
    function url_encode($str) {
        $CI =& get_instance();
        return $CI->outputencoder->url($str);
    }
}

/**
 * Encode for CSS context (use sparingly!)
 *
 * @param string $str
 * @return string
 */
if (!function_exists('css_encode')) {
    function css_encode($str) {
        $CI =& get_instance();
        return $CI->outputencoder->css($str);
    }
}

/**
 * Validate and encode URL (safe for href attributes)
 *
 * @param string $url
 * @return string
 */
if (!function_exists('safe_url')) {
    function safe_url($url) {
        $CI =& get_instance();
        return $CI->outputencoder->safeUrl($url);
    }
}

/**
 * Smart encoding (defaults to HTML context)
 *
 * @param string $str
 * @return string
 */
if (!function_exists('smart_encode')) {
    function smart_encode($str) {
        $CI =& get_instance();
        return $CI->outputencoder->smart($str);
    }
}

/**
 * Check if input is clean (no obvious XSS patterns)
 *
 * @param string $str
 * @return bool
 */
if (!function_exists('is_clean')) {
    function is_clean($str) {
        $CI =& get_instance();
        return $CI->outputencoder->isClean($str);
    }
}

/* End of file output_encoding_helper.php */
/* Location: ./system/application/helpers/output_encoding_helper.php */
