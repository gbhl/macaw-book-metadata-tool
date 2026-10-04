<?php
	// ---------------------------
	// Perform some setup like index.php
	// ---------------------------
	if( ! ini_get('date.timezone') ){
	   date_default_timezone_set('America/New_York');
	}
	error_reporting(E_ALL & ~E_DEPRECATED);
	$system_folder = "system";	
	$application_folder = "application";
	
	if (strpos($system_folder, '/') === FALSE) {
		if (function_exists('realpath') AND @realpath(dirname(__FILE__)) !== FALSE) {
			$system_folder = realpath(dirname(__FILE__)).'/'.$system_folder;
		}
	} else {
		$system_folder = str_replace("\\", "/", $system_folder);
	}
	
	define('EXT', '.'.pathinfo(__FILE__, PATHINFO_EXTENSION));
	define('FCPATH', __FILE__);
	define('SELF', pathinfo(__FILE__, PATHINFO_BASENAME));
	define('BASEPATH', $system_folder.'/');
	
	if (is_dir($application_folder)) {
		define('APPPATH', $application_folder.'/');
	} else {
		if ($application_folder == '') {
			$application_folder = 'application';
		}
		define('APPPATH', BASEPATH.$application_folder.'/');
	}
	// ---------------------------
	// End of Setup line index.php
	// ---------------------------

	// ---------------------------
	// Read the Config File
	// ---------------------------
	$config = [];
	require(BASEPATH.$application_folder.'/config/macaw.php');

	$baseDir = $config['macaw']['data_directory'];
	$type = $_GET['type'];
	$barcode = basename($_GET['code']);
	$img = basename(urldecode($_GET['img']));
	$path = '';

	# Sanitize Barcode - Allow only alphanumeric, hyphen, underscore
	if (!preg_match('/^[a-zA-Z0-9_\-. ]+$/', $barcode)) {
		http_response_code(400);
		die("Invalid barcode format.");
	}

	# Sanitize Type (already good, but make explicit)
	if (!in_array($type, ['thumbnail', 'preview', 'original'], true)) {
		http_response_code(400);
		die("Invalid type.");
	}

	# Sanitize Img - basename() already does this, but verify no traversal
	if ($img !== basename($img) || strpos($img, '/') !== false || strpos($img, '\\') !== false) {
		http_response_code(400);
		die("Invalid filename.");
	}


	if ($type == 'thumbnail') {
		$expected_base = $baseDir . DIRECTORY_SEPARATOR . $barcode . DIRECTORY_SEPARATOR . 'thumbs';
	} elseif ($type == 'preview') {
		$expected_base = $baseDir . DIRECTORY_SEPARATOR . $barcode . DIRECTORY_SEPARATOR . 'preview';
	} elseif ($type == 'original') {
		$expected_base = $baseDir . DIRECTORY_SEPARATOR . $barcode . DIRECTORY_SEPARATOR . 'scans';
	} else {
		http_response_code(400);
		die("Invalid type.");
	}

	# Resolve actual paths and verify
	$real_base = realpath($expected_base);
	$real_file = realpath($expected_base . DIRECTORY_SEPARATOR . $img);

	if ($real_base === false || $real_file === false) {
		http_response_code(404);
		die("File not found.");
	}

	# Ensure resolved file is within the expected base directory
	if (strpos($real_file, $real_base . DIRECTORY_SEPARATOR) !== 0 && $real_file !== $real_base) {
		http_response_code(403);
		die("Access denied.");
	}

	# Now safe to serve
	if (file_exists($real_file)) {

		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		$mime = finfo_file($finfo, $real_file);
		finfo_close($finfo);
		header('Content-Type: ' . ($mime ?: 'application/octet-stream'));
		readfile($real_file);
	} else {
		http_response_code(404);
		die("File not found.");
	}
