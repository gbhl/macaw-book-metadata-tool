<?php 
// Macaw/BHL Virtual Items Configuration File

// All items in CAPS or 000000 should be replaced with sensible values

// What is this source? 
//   Give a descriptive name, but it's not used anywhere but imside Macaw. 
$vi['macaw-name'] = "PUBLISHER NAME -- MUST BE UNIQUE";
// What BHL title does the data belong to?
//   This title must exist at BHL already, but we don't 
//   explicitly check it (yet).

$vi['title-id'] = 000000;
// Where do we get the data?
//   Feed will refer to OAI Feeds only at this time.
//   May include multiple URLs to OAI feeds. Metadata
//   must be in a form we recognize, currently only "mods"

// How do we get the data?
//   Allowed values are: 
//   * "oai_mods" 
//   * "spreadsheet" (not implemented)
//   * "oai_dc" (not implemented)
$vi['feed-type'] = 'oai_mods'; 

// Where do we get the data?
//   Feed will refer to OAI Feeds only at this time.
//   May include multiple URLs to OAI feeds. Metadata
//   must be in a form we recognize, currently only "mods"
$vi['feed'] = array(
  'HTTPS://URL-TO-FEED.PUBLISHER.COM/SOMETHNIG/FEED.XML'
);

// IA collection names
//   This should at least "biodiversity" but the "upload-org-id"
//   below needs to have permissions to any collections listed,
//   including "biodiversity"
$vi['collections'] = ['biodiversity']; 

// Who uploaded the content?
//   Macaw organization.id of who is uploading this content.
//   This organization's IA keys will be used to upload.
//   This has impications if the org doesn't have permissions
//   to the collections above.
$vi['upload-org-id'] = 000000; 

// Who contributed the content to BHL?
//   This will apply to all of the items for this source.
//   Also fills in the "Holding Institution" value at BHL
$vi['contributor'] = 'PUBLISHER NAME'; // Also "Holding Institution" -- Must exist in BHL's organization list

// Copyright Info: 
//   1 = In copyright, permission granted, 
//   0 = public domain
$vi['copyright'] = 1; // 1 = In copyright, permission granted, 0 = Not in Copyright, 2 = "Due Dilligence" (probably best not to use)

// Creatve Commons License
//   Applies to in-copyright items, this is the default and we will try to 
//   figure it out from the feed when possible.
$vi['creative-commons'] = 'http://creativecommons.org/licenses/by/4.0'; 

// Who owns the copyright?
//   This will apply to all of the items for this source.
$vi['rights-holder'] = 'Copyright held by individual article author(s).';

// BHL Rights statement
//   This should never be changed, but we leave it here just in case
$vi['rights'] = 'http://biodiversitylibrary.org/permissions';

/**  
 * VI IDENTIFIER DATA
 * 
 * Last chance to modify some metadata before the item is created.
 * Use this to change values based on the needs of the publisher
 * and how their info should appear in BHL and IA.
 * 
 * By default, we assume all the values have been retrieved from
 * the MODS or DOI and many of them are simply returned verbatim.
 * 
 * "source" can be used at IA to tie multiple articles together
 * under one heading
 * 
 * "virtual-id" is set for you from the config. Don't change it.
 * 
 * "virtual-volume" is set to exactly how the volume 
 * enumeration will appear in BHL and to tie multiple articles 
 * together. 
 * 
*/
$vi['vi-identifier-data'] = function ($config, $info, $oai_record) {
	// ---------------------------------------
	// FILL IN THE ABOVE INFORMATION SOMEHOW
	// ---------------------------------------
	// OAI may be either Dublin Core or MODS.
	// Only this code cares about the OAI encoding
	
	$ret = array(
		'title-id' => $config['title-id'], 
		'virtual-id' => $config['title-id'], 
		'virtual-volume' => null,
		'volume' => null, 
		'series' => null, 
		'issue' => null, 
		'page_start' => null,
		'page_end' => null,
		'page_range' => null,
		'date' => null,
		'year' => null,
		'source' => null,
	);

	$ret['volume'] = $info['volume'];
	$ret['series'] = $info['series'];
	$ret['issue'] = $info['issue'];
	$ret['page_start'] = $info['page_start'];
	$ret['page_end'] = $info['page_end'];
	$ret['page_range'] = (isset($info['page_range']) ? $info['page_range'] : $info['page_start'].'-'.$info['page_end']);
	$ret['date'] = $info['date'];	
	$ret['year'] = $info['year'];	

	// Build the volume string specific to our publisher and how 
	// their volume enumerations are displayed in BHL
	if ($info['issue']) {
		$ret['source'] = $info['journal_title'].' '.$info['volume'].'('.$info['issue'].')';
		$ret['virtual-volume'] = "v.".$info['volume'].':no.'.$info['issue'].' ('.$info['year'].')';
	} else {
		$ret['source'] = $info['journal_title'].' '.$info['volume'];
		$ret['virtual-volume'] = "v.".$info['volume'].' ('.$info['year'].')';
	}
	return $ret;
};

/**  
 * GET PDF 
 * 
 * If we have a PDF link or if we can create one, get it and return
 * the path to the file on disk.
 * 
 * This generally should not return null unless something went horribly
 * wrong. If null is returned, the item is not added to macaw at all and 
 * an error is logged.
 * 
*/
$vi['get-pdf'] = function($config, $oai_record, $info) {
	// ---------------------------------------
	// MUST RETURN A PATH ON THE FILESYSTEM FOR A PDF FILE OR NULL, 
	// BUT NULL WOULD BE BAD. MAY NEED TO DOWNLOAD FROM THE WEB. 
	//
	// If found already, "pdf_source" will be filled in.
	// ---------------------------------------
	
	$source = '';
	if (isset($info['pdf_source'])) {
		$source = $info['pdf_source'];
	} else {
		$ret = $oai_record->xpath("//mods:location/mods:url[@displayLabel='PDF']");
		if ($ret && is_array($ret)) {
			$source = (string)$ret[0].'download/pdf';
		}
	}
	if ($source) {
		$path = $config['working-path'].'/'.preg_replace("/[^A-Za-z0-9]+/", '_', $source).'.pdf';
		$ch = curl_init($source);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);		
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); 
		$data = curl_exec($ch);		
		curl_close($ch);
		
		$result = file_put_contents($path, $data);
		return $path;	
	}
	return null;
};
