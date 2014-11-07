<?php
	header('Content-Type: image/' . $_GET['ext']);
	$type = $_GET['type'];
	$barcode = $_GET['code'];
	$baseDir = '/BHL_STG/books'; // to be fast, baseDir is hardcoded
        $img = urldecode($_GET['img']);
	$path = '';
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
?>
