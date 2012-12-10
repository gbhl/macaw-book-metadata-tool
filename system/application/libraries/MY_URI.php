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
 * MY_URI Class
 *
 * Parses URIs and determines routing
 *
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @category	URI
 * @author		ExpressionEngine Dev Team
 * @modified    Philip Sturgeon
 * @link		http://codeigniter.com/user_guide/libraries/uri.html
 */
class MY_URI extends CI_URI {

    /**
	 * Get the URI String, with added support for command line
	 *
	 * @access	private
	 * @return	string
	 */
	function _fetch_uri_string()
	{
		if (strtoupper($this->config->item('uri_protocol')) == 'AUTO')
		{
			// If the URL has a question mark then it's simplest to just
			// build the URI string from the zero index of the $_GET array.
			// This avoids having to deal with $_SERVER variables, which
			// can be unreliable in some environments
			if (is_array($_GET) && count($_GET) == 1 && trim(key($_GET), '/') != '')
			{
				$this->uri_string = key($_GET);
				return;
			}

			// Is there a PATH_INFO variable?
			// Note: some servers seem to have trouble with getenv() so we'll test it two ways
			$path = (isset($_SERVER['PATH_INFO'])) ? $_SERVER['PATH_INFO'] : @getenv('PATH_INFO');
			if (trim($path, '/') != '' && $path != "/".SELF)
			{
				$this->uri_string = $path;
				return;
			}

			// No PATH_INFO?... What about QUERY_STRING?
			$path =  (isset($_SERVER['QUERY_STRING'])) ? $_SERVER['QUERY_STRING'] : @getenv('QUERY_STRING');
			if (trim($path, '/') != '')
			{
				$this->uri_string = $path;
				return;
			}

			// No QUERY_STRING?... Maybe the ORIG_PATH_INFO variable exists?
			$path = (isset($_SERVER['ORIG_PATH_INFO'])) ? $_SERVER['ORIG_PATH_INFO'] : @getenv('ORIG_PATH_INFO');
			if (trim($path, '/') != '' && $path != "/".SELF)
			{
				// remove path and script information so we have good URI data
				$this->uri_string = str_replace($_SERVER['SCRIPT_NAME'], '', $path);
				return;
			}

            // Has arguments and no server name, must be command line
            if(isset($_SERVER['argv']) && !isset($_SERVER['SERVER_NAME']))
            {
                $this->uri_string = $this->_parse_cli_args();
                return;
            }

			// We've exhausted all our options...
			$this->uri_string = '';
		}
        elseif(strtoupper($this->config->item('uri_protocol')) == 'CLI')
        {
            $this->uri_string = $this->_parse_cli_args();
        }
        else
        {
            $uri = strtoupper($this->config->item('uri_protocol'));

            if ($uri == 'REQUEST_URI')
            {
                $this->uri_string = $this->_parse_request_uri();
                return;
            }

            $this->uri_string = (isset($_SERVER[$uri])) ? $_SERVER[$uri] : @getenv($uri);
        }

		// If the URI contains only a slash we'll kill it
		if ($this->uri_string == '/')
		{
			$this->uri_string = '';
		}
	}

    // Convert arguments into a
    function _parse_cli_args() {

        // Get all arguments from command line
        $args = $_SERVER['argv'];

        // Remove the first, its the file name
        unset($args[0]);

        return '/'.implode('/', $args);
    }

}
// END MY_URI Class

/* End of file URI.php */
/* Location: ./application/libraries/MY_URI.php */