<?php 

## ALL ITEMS IN CAPS or 000000 SHOULD BE REPLACD WITH SENSIBLE VALUES

$vi['macaw-name'] = "PUBLISHER NAME -- MUST BE UNIQUE";
$vi['title-id'] = 000000;
$vi['feed'] = array(
  'HTTPS://URL-TO-FEED.PUBLISHER.COM/SOMETHNIG/FEED.XML'
);
$vi['feed-type'] = 'oai_dc'; ## "oai_dc" or "oai_mods" or "spreadsheet" (default)
$vi['collections'] = ['biodiversity'];
$vi['upload-org-id'] = 000000; // Macaw Identifier of the Uploading Organization
$vi['contributor'] = 'PUBLISHER NAME'; // Also "Holding Institution"

# TODO - Collect Type of Segment from BHL Vocabulary list

// Copyright Info
$vi['copyright'] = 1; // In copyright, permission granted
$vi['creative-commons'] = 'http://creativecommons.org/licenses/by/4.0';
$vi['rights-holder'] = 'Copyright held by individual article author(s).';
$vi['rights'] = 'http://biodiversitylibrary.org/permissions';

// Required function to return an array containing: 
// title-id, virtual-id, virtual-volume, volume, series, issue, page-start, page-end, date, year
$vi['vi-identifier-data'] = function ($config, $oai_record) {
	$ret = array(
		'title-id' => $config['title-id'], 
		'virtual-id' => null, 
		'virtual-volume' => null,
		'volume' => null, 
		'series' => null, 
		'issue' => null, 
		'page-start' => null,
		'page-end' => null,
		'page-range' => null,
		'date' => null,
		'year' => null,
		'source' => null,
	);
	// FILL IN THE ABOVE INFORMATION SOMEHOW
	// OAI may be either Dublin Core or MODS.
	// Only this code cares about the OAI encoding
	
	// $source = (string)$oai_record->xpath('//dc:source')[0];
	// $id = (string)$oai_record->xpath('//header/identifier')[0];
	// $dt = date_create((string)$oai_record->xpath('//header/datestamp')[0]);
	// $ret['date'] = date_format($dt, 'Y-m-d');
	// $ret['year'] = date_format($dt, 'Y');
	// $matches = [];
	// if (preg_match("/^(.*?) (\d+)\((\d+)\): (\d+)-(\d+)/", $source, $matches)) {
	// 	$ret['volume'] = $matches[2];
	// 	$ret['issue'] = $matches[3];
	// 	$ret['page-start'] = $matches[4];
	// 	$ret['page-end'] = $matches[5];
	// 	$ret['page-range'] = $matches[4].'-'.$matches[5];
	// 	$ret['source'] = $matches[1].' '.$matches[2].'('.$matches[3].')';
	// }
	// $ret['virtual-volume'] = "v.".$ret['volume'].'('.$ret['issue'].')('.$ret['year'].')';

	// $x = explode('.', $id);
	// $y = array_pop($x);
	// $ret['virtual-id'] = implode('.',$x);

	return $ret;
};

// Required function to return the PDF of an article
// This is specific to an OAI Feed at this time
$vi['get-pdf'] = function($config, $oai_record) {
	// MUST RETURN A PATH ON THE FILESYSTEM FOR A PDF FILE
	// OR NULL, BUT NULL WOULD BE BAD

	// $source = '';
	// foreach ($oai_record->xpath('//dc:identifier') as $i) {
	// 	if (preg_match('/\/article\//i', (string)$i)) {
	// 		$source = (string)$i; 
	// 	}
	// }
	// // Our source of https://zse.pensoft.net/article/96986/
	// // becomes https://zse.pensoft.net/article/96986/download/pdf/
	// // to get the PDF
	// if ($source) {
	// 	$path = $config['working-path'].'/'.preg_replace("/[^A-Za-z0-9]+/", '_', $source).'.pdf';
	// 	if (!file_exists($path)) {
	// 		$ch = curl_init($source.'download/pdf');
	// 		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);		
	// 		curl_setopt($ch, CURLOPT_BINARYTRANSFER,1);
	// 		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); 
	// 		$data = curl_exec($ch);		
	// 		curl_close($ch);

	// 		$result = file_put_contents($path, $data);
	// 	}	
	// 	return $path;
	// }

	// return null;
};
