<?php
	header('Content-Type: image/' . $_GET['ext']);

	$type = $_GET['type'];
	$barcode = $_GET['code'];
	$img = urldecode($_GET['img']);
	$path = '';
	if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
		// To be fast, baseDir is hardcoded
		// But this really should come from the config file
		$baseDir = 'e:\\books';
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
    header('Warning: abs' . $path . '\\' . $img);
		
	} else {
		// To be fast, baseDir is hardcoded
		// But this really should come from the config file
		$baseDir = 'books';
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
