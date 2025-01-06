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
	$config = null;
	require(BASEPATH.$application_folder.'/config/macaw.php');

	header('Content-Type: image/' . $_GET['ext']);
	$type = $_GET['type'];
	$barcode = $_GET['code'];
	$img = urldecode($_GET['img']);
	$path = '';
	$baseDir = $config['macaw']['data_directory'];
	if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
		// To be fast, baseDir is hardcoded
		// But this really should come from the config file
		if ($type == 'thumbnail') {
			$path = $baseDir . '\\BARCODE\\thumbs';
		} elseif ($type == 'preview') {
			$path = $baseDir . '\\BARCODE\\preview';
		} elseif ($type == 'original') {
			$path = $baseDir . '\\BARCODE\\scans';
		} else {
			$last_error = 'Unrecognized path type supplied';
			throw new Exception($last_error);
			return;
		}
		$path = preg_replace('/BARCODE/', $barcode, $path);
		readfile( $path . '\\' . $img); 
    	// header('Warning: abs' . $path . '\\' . $img);
		
	} else {
		// To be fast, baseDir is hardcoded
		// But this really should come from the config file
	 	if ($type == 'thumbnail') {
			$path = $baseDir . '/BARCODE/thumbs';
		} elseif ($type == 'preview') {
			$path = $baseDir . '/BARCODE/preview';
		} elseif ($type == 'original') {
			$path = $baseDir . '/BARCODE/scans';
		} else {
			$last_error = 'Unrecognized path type supplied';
			throw new Exception($last_error);
			return;
		}
		$path = preg_replace('/BARCODE/', $barcode, $path);
		readfile( $path . '/' . $img); 
	}
?>
