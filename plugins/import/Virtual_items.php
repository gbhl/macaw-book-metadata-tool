<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Custom Import Module
 *
 * Provides an interface to local systems to get the bibliographic and
 * item-level metadata for a given installation of Macaw. This contains one
 * function which returns a list of things that are ready to be imported into
 * Macaw.
 *
 *
 * @package metadata
 * @author Joel Richard
 * @version 1.0 admin.php created: 2010-09-20 last-modified: 2010-08-19
 **/

class Virtual_items extends Controller {

	private $CI;
	private $cfg;
	private $vi_config;
	public $error;
	public $config_path = 'plugins/import/virtual_items';

	function __construct() {
		$this->CI = get_instance();
	}

	/**
	 * Get new items
	 *
	 * Gets an array of things that are ready to be scanned. The structure of
	 * the resulting object must be the following, but the only required field
	 * in each item is the barcode.
	 *
	 * 		$items = Array(
	 * 				[0] => Array(
	 * 					'barcode'     => '39088010037075',
	 * 					'call_number' => 'Q11 .U52Z',
	 * 					'location'    => 'FISH',
	 * 					'volume'      => 'v 1; part 2',
	 * 					'copyright'   => '1'
	 * 				)
	 * 				[1] => Array(
	 *				  	[ ... ]
	 * 				)
	 * 				[2] => Array(
	 *				  	[ ... ]
	 * 				)
	 *				[ ... ]
	 * 			)
	 * 		)
	 *
	 * If other databases need to be reached, they can be done so from other
	 * functions that you create in this module. It's expected that this will
	 * be necessary.
	 *
	 * @return array of arrays containing the information for new items.
	 */
	function get_new_items($args) {
		$id = null;
		$command = null; 
		if (isset($args[0])) { $id = $args[0]; };
		if (isset($args[1])) { $command = $args[1]; };		
		$this->check_custom_table();
		$this->CI->logging->log('access', 'info', "Virutal Items: Looking for new items");

		// Loop through the VI configs
		$dir = new DirectoryIterator($this->config_path);
		foreach ($dir as $fileinfo) {
			if ($fileinfo->isDot()) {
				continue;
			}

			if ($fileinfo->isDir()) {
				if (file_exists($fileinfo->getPathName().'/config.php')) {
					$this->CI->logging->log('access', 'info', "Virutal Items: Found Config ".$fileinfo->getPathName().'/config.php');
					$vi = [];
					require($fileinfo->getPathName().'/config.php');
					$dirs = preg_split("/[\\/]/", $fileinfo->getPathName());
					$this->process_source($dirs[count($dirs)-1], $vi, $fileinfo->getPathName(), $id, $command);
				}
			}
		}
		// Note: Macaw's import module system requires an array of new items
		// We explicitly return an empty array here because we already 
		// add the new books elsewhere in this module.
		$this->CI->logging->log('access', 'info', "(next log entry is meaningless, Virtual items imports it's own items)");
		return [];
	}

	/* ----------------------------
	 * Function: process_source()
	 *
	 * Parameters:
	 *
   * Makes sure that the config file for a virtual item is valid
	 * ----------------------------
	 */
	function process_source($name, $config, $path, $single_id = null, $command = null) {
		// Validate the configuration		
		if (!$this->check_config($config, $path)) {
			$this->CI->logging->log('access', 'error', "Virutal Items: Source: $name: Config file is not valid");
			print "Config File for ".$name." is not valid\n";
			return false;
		}
		// print "Name is $name\n";
		// print "Path is $path\n";
		// print_r($config);				
		// $marc_file = $path.'/'.$config['marc_filename'];
		// $xml = simplexml_load_file($marc_file);
		if ($config['feed_type'] == 'oai') {
			$this->vi_config = $config;
			$this->process_oai($name, $path, $single_id, $command);
		}
	}

	/* ----------------------------
	 * Function: process_oai()
	 *
	 * Parameters: $name, $path
	 *
   * Main function to handle reading an OAI-PMH feed
	 * to ingest it into macaw. This has clear knowledge
	 * of the format of an OAI-PMH feed and expect there
	 * to be metadata in Dublic Core (oai_dc) format. 
	 * ----------------------------
	 */
	function process_oai($name, $path, $single_id = null, $command = null) {
		$counter = 0;
		$this->CI->logging->log('access', 'info', "Virutal Items: Source: $name: Processing OAI Feed");
		// Set up some working folders for efficiency
		$this->vi_config['working_path'] = $path.'/working';
		$this->vi_config['cache_path'] = $path.'/cache';
		@mkdir($this->vi_config['working_path'], 0755, true);
		@mkdir($this->vi_config['cache_path'], 0755, true);
		
		// Get the OAI Feed
		$records = [];
		if (is_array($this->vi_config['feed'])) {
			foreach((array)$this->vi_config['feed'] as $url) {
				$records = $this->read_oai($url);
			}
		} else {
			$records = $this->read_oai($this->vi_config['feed']);
		}
		// Reminder: read_oai returns an array of XML fragments

		$this->CI->logging->log('access', 'info', "Virutal Items: Source: $name: Got ".count($records)." records: $name");
		$new_items = [];
		foreach ($records as $r) {
			$r = new SimpleXMLElement($r);
			$r->registerXPathNamespace('dc', 'http://purl.org/dc/elements/1.1/');
			$id = (string)$r->xpath('//header/identifier')[0];
			$title = (string)$r->xpath('//dc:title')[0];

			$barcode = preg_replace("/[\/.]/", '_', $id); // No slashes!;
			// if ($single_id && $single_id != $barcode) {
			// 	print "$barcode is not $single_id. Skipping.";
			// 	die;

			// 	continue;
			// }
			if ($this->record_exists($barcode)) {
				// Have we seen the item?
				// Yes, report and skip.
				$this->CI->logging->log('access', 'info', "Virutal Items: Source: $name: OAI Record $id already processed");
			} else {
				// pull the details and build the metadata
				$info = [];
				$info['barcode'] = $barcode; 
				$this->CI->logging->log('book', 'info', "Creating Virtual Item Segment item.", $info['barcode']);
				$info['title'] = $title;
				$info['copyright'] = $title;
				$info['holding_institution'] = (string)$r->xpath('//dc:publisher')[0];
				$info['publisher'] = (string)$r->xpath('//dc:publisher')[0];
				$info['copyright'] = $this->vi_config['copyright'];
				$info['genre'] = 'article';
				$info['collections'] = $this->vi_config['collections']; 
				$info['sponsor'] = $info['holding_institution'];
				// $info['scanning_institution'] = $info['holding_institution'];
				$info['cc_license'] = $this->vi_config['creative_commons'];
				$info['org_id'] = $this->vi_config['upload_org_id'];; 
				$info['subject'] = [];
				foreach ($r->xpath('//dc:subject') as $s) {
					$info['subject'][] = (string)$s;
				}
				$info['creator'] = [];
				foreach ($r->xpath('//dc:creator') as $a) {
					$info['creator'][] = preg_replace('/,([^ ])/', ', \1', (string)$a);
				}
				$info['language'] = $this->iso639_2to3((string)$r->xpath('//dc:language')[0]);
				$info['source'] = (string)$r->xpath('//dc:source')[0];
				$info['abstract'] = (string)$r->xpath('//dc:description')[0];
				$info['rights_holder'] = 'Copyright held by individual article author(s).';
				// Figure out the DOI
				foreach ($r->xpath('//dc:identifier') as $i) {
					if (preg_match('/^10.\d{4,9}\/[-._;()\/:A-Z0-9]+$/i', (string)$i)) {
						$info['doi'] = (string)$i;
					} elseif (preg_match('/doi/i', (string)$i)) {
						$info['doi'] = $this->normalize_doi((string)$i);
					}					
				}
				// Create Virtual Item ID
				$vi_data = [];
				$vi_data = $this->vi_config['vi_identifier_data']($r, $this->vi_config);
				$info['bhl_virtual_titleid'] = $this->vi_config['title_id'];
				$info['bhl_virtual_volume'] = $vi_data['virtual-volume'];
				$info['segment_volume'] = $vi_data['volume'];
				$info['segment_series'] = $vi_data['series'];
				$info['segment_issue'] = $vi_data['issue'];
				$info['segment_date'] = $vi_data['year'];
				$info['page_start'] = $vi_data['page-start'];
				$info['page_end'] = $vi_data['page-end'];
				$info['year'] = $vi_data['year'];
				$info['date'] = $vi_data['date'];

				// TODO Remove this for production
				// -------------------------------
				$info['noindex'] = '1';
				// -------------------------------

				// Get the PDF
				$pdf_path = $this->vi_config['get_pdf']($r, $this->vi_config);

				if (!$pdf_path) {
					$this->CI->logging->log('book', 'error', "Could not get PDF for item. Aborting.", $info['barcode']);
					$this->CI->logging->log('access', 'info', "Virutal Items: Source: $name: Could not get PDF for item with barcode ".$info['barcode'].". Aborting.");
				} else {
					// Put the PDF in the incoming folder
					$incoming_path = $this->CI->cfg['incoming_directory'].'/'.$info['barcode'];
					if (!file_exists($incoming_path)) {
						mkdir($incoming_path);
					}
					rename($pdf_path, $incoming_path.'/'.$info['barcode'].'.pdf');
					
					// Create the book
					$this->CI->logging->log('book', 'info', "Adding item to the system.", $info['barcode']);
 					$this->add_book($name, $info, $pdf_path);

					$this->CI->logging->log('book', 'info', "Recording item to custom table.", $info['barcode']);
					$this->CI->db->insert(
						'custom_virtual_items',
						array(
							'source' => $name,
							'title' => substr($title,0,128),
							'barcode' => $info['barcode'],
							'created' => date("Y-m-d H:i:s")
						)
					);
					$this->CI->logging->log('access', 'info', "Virutal Items: Source: $name: Added item with barcode ".$info['barcode']);
					// TODO Remove this when testng is complete
					$counter++;
					if ($counter >= 10) { break; }
				}

				// Questions for Mike
				// 1. I need to somehow suppply Virtual Item Volume info: v.66:no.2 (2019)
				// 2. I need to distingush this from the Segment Volumne "66" and number "2"

				// process the item and pages into macaw
				// add the item to the custom table

				// Things to keep in mind:
				// * Step 0: Add to the dbo.Item table a field for VirtualItemIdentifier (Mike to do)
				// * Step 1: add the MARC XML to BHL to get a Title ID
				// * Step 2: Save TitleID to Macaw (add to the VI Config file)
				// * Step 3: Send Volume/Issue/No whatever it is. (needs further discussion)
				// * Step 4: Create new field for bhl-virtual-identifier with a value that links multiple articles together.
				// Note, we may need a separate file for segment metadata if the generic MARC XML 
				//       overwrites the Segment metadata, either by Macaw or by internet archive.
				// Make sure to mark NOINDEX when uploading to IA for testing. Don’t add to the biodiversity collection.
				// To-Do: Need to discuss identifiers for authors coming in from Pensoft (and others?)

				// Not all OAI Feeds will be the same. Each will need to be evaluated.
				// Possible that we need a different feed for each publisher.

			}
		}
		return $new_items;
	}

		/* ----------------------------
	 * Function: process_folder()
	 *
	 * Parameters: $name, $path
	 *
   * Main function to handle reading an OAI-PMH feed
	 * to ingest it into macaw. This has clear knowledge
	 * of the format of an OAI-PMH feed and expect there
	 * to be metadata in Dublic Core (oai_dc) format. 
	 * ----------------------------
	 */
	function process_folder($name, $path) {
		$this->CI->logging->log('access', 'info', "Virutal Items: Source: $name:  Processing Local Folder");
		// Set up some working folders for efficiency
		// $this->vi_config['working_path'] = $path.'/working';
		// $this->vi_config['cache_path'] = $path.'/cache';
		// @mkdir($this->vi_config['working_path'], 0755, true);
		// @mkdir($this->vi_config['cache_path'], 0755, true);
		
		// // Get the OAI Feed
		// $records = [];
		// if (is_array($this->vi_config['feed'])) {
		// 	foreach((array)$this->vi_config['feed'] as $url) {
		// 		$records = $this->read_oai($url);
		// 	}
		// } else {
		// 	$records = $this->read_oai($this->vi_config['feed']);
		// }
		// // Reminder: read_oai returns an array of XML fragments

		// $this->CI->logging->log('access', 'info', "Virutal Items: Source: $name: Got ".count($records)." records: $name");
		// $new_items = [];
		// foreach ($records as $r) {
		// 	$r = new SimpleXMLElement($r);
		// 	$r->registerXPathNamespace('dc', 'http://purl.org/dc/elements/1.1/');
		// 	$id = (string)$r->xpath('//header/identifier')[0];
		// 	$title = (string)$r->xpath('//dc:title')[0];

		// 	if ($this->record_exists($id)) {
		// 		// Have we seen the item?
		// 		// Yes, report and skip.
		// 		$this->CI->logging->log('info', "Virutal Items: Source: $name: OAI Record $id already processed");
		// 	} else {
		// 		// pull the details and build the metadata
		// 		$info = [];
		// 		$info['barcode'] = preg_replace("/[\/.]/", '_', $id); // No slashes!
		// 		$this->CI->logging->log('access', 'info', "Virutal Items: Source: $name: Creating item for barcode ".$info['barcode']);
		// 		$this->CI->logging->log('book', 'info', "Creating Virtual Item Segment item.", $info['barcode']);
		// 		$info['title'] = $title;
		// 		$info['copyright'] = $title;
		// 		$info['holding_institution'] = (string)$r->xpath('//dc:publisher')[0];
		// 		$info['publisher'] = (string)$r->xpath('//dc:publisher')[0];
		// 		$info['copyright'] = $this->vi_config['copyright'];
		// 		$info['genre'] = 'article';
		// 		// *********************************
		// 		// TODO: Figure this out
		// 		// *********************************
		// 		$info['collection'] = 'NULL'; 
		// 		$info['sponsor'] = $info['holding_institution'];
		// 		// $info['scanning_institution'] = $info['holding_institution'];
		// 		$info['cc_license'] = $this->vi_config['creative_commons'];
		// 		// *********************************
		// 		// TODO: Figure this out
		// 		// *********************************
		// 		$info['org_id'] = 1; 
		// 		$info['subject'] = [];
		// 		foreach ($r->xpath('//dc:subject') as $s) {
		// 			$info['subject'][] = (string)$s;
		// 		}								
		// 		$info['creator'] = [];
		// 		foreach ($r->xpath('//dc:creator') as $a) {
		// 			$info['creator'][] = preg_replace('/,([^ ])/', ', \1', (string)$a);
		// 		}
		// 		$info['language'] = $this->iso639_2to3((string)$r->xpath('//dc:language')[0]);
		// 		$info['source'] = (string)$r->xpath('//dc:source')[0];
		// 		$info['abstract'] = (string)$r->xpath('//dc:description')[0];
		// 		$info['rights_holder'] = 'Copyright held by individual article author(s).';
		// 		// Figure out the DOI
		// 		foreach ($r->xpath('//dc:identifier') as $i) {
		// 			if (preg_match('/^10.\d{4,9}\/[-._;()\/:A-Z0-9]+$/i', (string)$i)) {
		// 				$info['doi'] = (string)$i;
		// 			} elseif (preg_match('/doi/i', (string)$i)) {
		// 				$info['doi'] = $this->normalize_doi((string)$i);
		// 			}					
		// 		}
		// 		// $info['marc_xml'] = file_get_contents($path.'/'.$this->vi_config['marc_filename']);
		// 		// Create Virtual Item ID
		// 		$vi_data = [];
		// 		$vi_data = $this->vi_config['vi_identifier_data']($r, $this->vi_config);
		// 		$info['bhl_virtual_titleid'] = $vi_data['virtual-id'];
		// 		$info['bhl_virtual_volume'] = $vi_data['virtual-volume'];
		// 		$info['segment_volume'] = $vi_data['volume'];
		// 		$info['segment_series'] = $vi_data['series'];
		// 		$info['segment_issue'] = $vi_data['issue'];
		// 		$info['segment_date'] = $vi_data['year'];
		// 		$info['page_start'] = $vi_data['page-start'];
		// 		$info['page_end'] = $vi_data['page-end'];
		// 		$info['year'] = $vi_data['year'];
		// 		$info['date'] = $vi_data['date'];

		// 		// TODO Remove this for production
		// 		// -------------------------------
		// 		$info['noindex'] = '1';
		// 		// -------------------------------

		// 		// Get the PDF
		// 		$pdf_path = $this->vi_config['get_pdf']($r, $this->vi_config);

		// 		if (!$pdf_path) {
		// 			$this->CI->logging->log('book', 'error', "Could not get PDF for item. Aborting.", $info['barcode']);
		// 			$this->CI->logging->log('access', 'info', "Virutal Items: Source: $name: Could not get PDF for item with barcode ".$info['barcode'].". Aborting.");
		// 		} else {
		// 			// Put the PDF in the incoming folder
		// 			$incoming_path = $this->CI->cfg['incoming_directory'].'/'.$info['barcode'];
		// 			mkdir($incoming_path);
		// 			rename($pdf_path, $incoming_path.'/'.$info['barcode'].'.pdf');
					
		// 			// Create the book
		// 			$this->CI->logging->log('book', 'info', "Adding item to the system.", $info['barcode']);
 		// 			$this->add_book($name, $info, $pdf_path);

		// 			$this->CI->logging->log('book', 'info', "Recording item to custom table.", $info['barcode']);
		// 			$this->CI->db->insert(
		// 				'custom_virtual_items',
		// 				array(
		// 					'source' => $name,
		// 					'title' => substr($title,0,128),
		// 					'barcode' => $info['barcode'],
		// 					'created' => date("Y-m-d H:i:s")
		// 				)
		// 			);
		// 		}

		// 		// Questions for Mike
		// 		// 1. I need to somehow suppply Virtual Item Volume info: v.66:no.2 (2019)
		// 		// 2. I need to distingush this from the Segment Volumne "66" and number "2"

		// 		// process the item and pages into macaw
		// 		// add the item to the custom table

		// 		// Things to keep in mind:
		// 		// * Step 0: Add to the dbo.Item table a field for VirtualItemIdentifier (Mike to do)
		// 		// * Step 1: add the MARC XML to BHL to get a Title ID
		// 		// * Step 2: Save TitleID to Macaw (add to the VI Config file)
		// 		// * Step 3: Send Volume/Issue/No whatever it is. (needs further discussion)
		// 		// * Step 4: Create new field for bhl-virtual-identifier with a value that links multiple articles together.
		// 		// Note, we may need a separate file for segment metadata if the generic MARC XML 
		// 		//       overwrites the Segment metadata, either by Macaw or by internet archive.
		// 		// Make sure to mark NOINDEX when uploading to IA for testing. Don’t add to the biodiversity collection.
		// 		// To-Do: Need to discuss identifiers for authors coming in from Pensoft (and others?)

		// 		// Not all OAI Feeds will be the same. Each will need to be evaluated.
		// 		// Possible that we need a different feed for each publisher.

		// 	}
		// }
		// return $new_items;
	}

	function add_book($name, $info, $pdf_path) {
		// Add the book
		try {
			$ret = $this->CI->book->add($info);
		} catch (Exception $e) {
			$this->CI->logging->log('book', 'error', $e->getMessage(), $info['barcode']);
			$this->CI->logging->log('access', 'error', $e->getMessage());
			return false;
		}

		if ($ret) {
			$this->CI->logging->log('book', 'info', "Loading images from PDF.", $info['barcode']);
			$ret = $this->CI->book->load($info['barcode']);
			$ret = $this->CI->book->import_images();
			$this->CI->logging->log('book', 'info', "Finished loading images from PDF.", $info['barcode']);

			// Set the page metadata
			// *********************************
			// TODO: Figure out how to set the page metadata from the PDF
			// *********************************
			$this->CI->logging->log('book', 'info', "Setting basic page metadata.", $info['barcode']);
			$ret = $this->CI->book->load($info['barcode']);
			$pages = $this->CI->book->get_pages();

			$first = true;
			$even = 0;
			$page_num = $info['page_start'];
			if (!$page_num) {
				$page_num = 1;
			}

			foreach ($pages as $p) {
				$this->CI->book->set_page_metadata($p->id, 'page_type', ($first ? 'Cover' : 'Text'));
				$this->CI->book->set_page_metadata($p->id, 'page_number', $page_num);
				$this->CI->book->set_page_metadata($p->id, 'page_number_implicit', 1);
				$this->CI->book->set_page_metadata($p->id, 'page_side', (($even++ % 2) ? 'left (verso)' : 'Right (recto)'));				

				$this->CI->book->set_page_metadata($p->id, 'year', $info['segment_date']);
				$this->CI->book->set_page_metadata($p->id, 'volume', $info['segment_volume']);
				if ($info['segment_series']) {
					$this->CI->book->set_page_metadata($p->id, 'piece', 'No.');	
					$this->CI->book->set_page_metadata($p->id, 'piece_text', $info['segment_series']);
				}
				if ($info['segment_issue']) {
					$this->CI->book->set_page_metadata($p->id, 'piece', 'Issue');	
					$this->CI->book->set_page_metadata($p->id, 'piece_text', $info['segment_issue']);
				}
				$page_num++;
				$first = false;
			}
			$this->CI->book->set_status('scanned');
			$this->CI->book->update();
			$this->CI->book->set_status('reviewing');
			$this->CI->book->update();
			$this->CI->book->set_status('reviewed');
			$this->CI->book->update();
			$this->CI->logging->log('book', 'info', "Marking item as reviewed.", $info['barcode']);
		} else {
			if (strlen($this->CI->book->last_error) > 0) {
				print $this->CI->book->last_error."\n";
			}
		}


	}

	/* ----------------------------
	 * Function: record_exists()
	 *
	 * Parameters: $id
	 *
   * Given some sort of identifier, check the custom table
	 * to see if it already exists. The identifier will be sanitized
	 * before querying, so any value applies.
	 * ----------------------------
	 */
	function record_exists($id) {
		// Query the database for $id
		$sql = "SELECT * FROM custom_virtual_items WHERE barcode = ?";
		$query = $this->CI->db->query($sql, array($id));
		if (count($query->result()) == 0) {
				return false;
		}
		return true;
	}

	/* ----------------------------
	 * Function: cache_get_xml()
	 *
	 * Parameters: $url
	 *
   * Given a URL, read it from the web only if it doesn't
	 * exist on disk.
	 * ----------------------------
	 */
	function cache_get_xml($url) {
		$md5 = md5($url);
		$cache_file = $this->vi_config['cache_path'].'/'.$md5;

		// Cache the URL if it doesn't exist
		if (!file_exists($cache_file)) {
			file_put_contents(
				$this->vi_config['cache_path'].'/'.$md5, 
				file_get_contents($url)
			);
		}
		
		$xml = simplexml_load_file($this->vi_config['cache_path'].'/'.$md5);
		$xml->registerXPathNamespace('oai-dc', 'http://www.openarchives.org/OAI/2.0/oai_dc/');
		$xml->registerXPathNamespace('dc', 'http://purl.org/dc/elements/1.1/');
		return $xml;
	}

	/* ----------------------------
	 * Function: read_oai()
	 *
	 * Parameters: $url
	 *
   * Given a URL, assume it's an OAI-PMH feed and extract the
	 * records from the feed and add them individually to an array.
	 * 
	 * Each record will be sort sort of xmlNode.
	 * ----------------------------
	 */
	function read_oai($url) {
		$records = [];
		$xmlObj = $this->cache_get_xml($url);

		// Gather all the nodes
		$xmlNode = $xmlObj->ListRecords;
		foreach ($xmlNode->record as $rNode) {
			$records[] = $rNode->asXML();
		}
		return $records;
	}

	/* ----------------------------
	 * Function: check_config()
	 *
	 * Parameters:
	 *
   * Makes sure that the config file for a virtual item is valid
	 * ----------------------------
	 */
	function check_config($config, $path) {
		if (!stream_resolve_include_path('fpdf.php')) {
			print "FPDF library not found.\n";
			return false;
		}
		// Do we have a Title ID
		if (!$config['title_id']) {
			print "No TitleID\n";
			return false;
		}
		// Do we have a feed
		if (!isset($config['feed'])) {
			print "No feed specified.\n";
			return false;
		}
		// Do we have a feed type
		if (!isset($config['feed_type'])) {
			print "Feed type not specified.\n";
			return false;
		}
		if (is_callable('$config[\'vi_identifier_data\']')) {
			print "'vi_identifier_data' is not a function.\n";
			return false;
		}
		if (is_callable('$config[\'get_pdf\']')) {
			print "'get_pdf' is not a function.\n";
			return false;
		}
		return true;
	}

	/* ----------------------------
	 * Function: check_custom_table()
	 *
	 * Parameters:
	 *
   * Makes sure that the CUSTOM_VIRTUAL_ITEMS table exists in the database.
	 * ----------------------------
	 */
	function check_custom_table() {
		try{
			if (!$this->CI->db->table_exists('custom_virtual_items')) {
				$this->CI->db->query(
					"create table custom_virtual_items (".
					  "source varchar(128), ".
					  "title varchar(128), ".
					  "barcode varchar(128), ".
					  "created timestamp, ".
					  "PRIMARY KEY(`barcode`)".
					  ") ENGINE=InnoDB CHARACTER SET=utf8 COLLATE=utf8_unicode_ci;"
				);
			}	
		} catch (Exception $e) {
			print "Error: ".$e->getMessage()."\n";
			die;
		}
	}
	

	function iso639_2to3($lang) {
		$codes = [
			'aa'=>'aar',
			'ab'=>'abk',
			'ae'=>'ave',
			'af'=>'afr',
			'ak'=>'aka',
			'am'=>'amh',
			'an'=>'arg',
			'ar'=>'ara',
			'as'=>'asm',
			'av'=>'ava',
			'ay'=>'aym',
			'az'=>'aze',
			'ba'=>'bak',
			'be'=>'bel',
			'bg'=>'bul',
			'bi'=>'bis',
			'bm'=>'bam',
			'bn'=>'ben',
			'bo'=>'bod',
			'br'=>'bre',
			'bs'=>'bos',
			'ca'=>'cat',
			'ce'=>'che',
			'ch'=>'cha',
			'co'=>'cos',
			'cr'=>'cre',
			'cs'=>'ces',
			'cu'=>'chu',
			'cv'=>'chv',
			'cy'=>'cym',
			'da'=>'dan',
			'de'=>'deu',
			'dv'=>'div',
			'dz'=>'dzo',
			'ee'=>'ewe',
			'el'=>'ell',
			'en'=>'eng',
			'eo'=>'epo',
			'es'=>'spa',
			'et'=>'est',
			'eu'=>'eus',
			'fa'=>'fas',
			'ff'=>'ful',
			'fi'=>'fin',
			'fj'=>'fij',
			'fo'=>'fao',
			'fr'=>'fra',
			'fy'=>'fry',
			'ga'=>'gle',
			'gd'=>'gla',
			'gl'=>'glg',
			'gn'=>'grn',
			'gu'=>'guj',
			'gv'=>'glv',
			'ha'=>'hau',
			'he'=>'heb',
			'hi'=>'hin',
			'ho'=>'hmo',
			'hr'=>'hrv',
			'ht'=>'hat',
			'hu'=>'hun',
			'hy'=>'hye',
			'hz'=>'her',
			'ia'=>'ina',
			'id'=>'ind',
			'ie'=>'ile',
			'ig'=>'ibo',
			'ii'=>'iii',
			'ik'=>'ipk',
			'io'=>'ido',
			'is'=>'isl',
			'it'=>'ita',
			'iu'=>'iku',
			'ja'=>'jpn',
			'jv'=>'jav',
			'ka'=>'kat',
			'kg'=>'kon',
			'ki'=>'kik',
			'kj'=>'kua',
			'kk'=>'kaz',
			'kl'=>'kal',
			'km'=>'khm',
			'kn'=>'kan',
			'ko'=>'kor',
			'kr'=>'kau',
			'ks'=>'kas',
			'ku'=>'kur',
			'kv'=>'kom',
			'kw'=>'cor',
			'ky'=>'kir',
			'la'=>'lat',
			'lb'=>'ltz',
			'lg'=>'lug',
			'li'=>'lim',
			'ln'=>'lin',
			'lo'=>'lao',
			'lt'=>'lit',
			'lu'=>'lub',
			'lv'=>'lav',
			'mg'=>'mlg',
			'mh'=>'mah',
			'mi'=>'mri',
			'mk'=>'mkd',
			'ml'=>'mal',
			'mn'=>'mon',
			'mr'=>'mar',
			'ms'=>'msa',
			'mt'=>'mlt',
			'my'=>'mya',
			'na'=>'nau',
			'nb'=>'nob',
			'nd'=>'nde',
			'ne'=>'nep',
			'ng'=>'ndo',
			'nl'=>'nld',
			'nn'=>'nno',
			'no'=>'nor',
			'nr'=>'nbl',
			'nv'=>'nav',
			'ny'=>'nya',
			'oc'=>'oci',
			'oj'=>'oji',
			'om'=>'orm',
			'or'=>'ori',
			'os'=>'oss',
			'pa'=>'pan',
			'pi'=>'pli',
			'pl'=>'pol',
			'ps'=>'pus',
			'pt'=>'por',
			'qu'=>'que',
			'rm'=>'roh',
			'rn'=>'run',
			'ro'=>'ron',
			'ru'=>'rus',
			'rw'=>'kin',
			'sa'=>'san',
			'sc'=>'srd',
			'sd'=>'snd',
			'se'=>'sme',
			'sg'=>'sag',
			'sh'=>'hbs',
			'si'=>'sin',
			'sk'=>'slk',
			'sl'=>'slv',
			'sm'=>'smo',
			'sn'=>'sna',
			'so'=>'som',
			'sq'=>'sqi',
			'sr'=>'srp',
			'ss'=>'ssw',
			'st'=>'sot',
			'su'=>'sun',
			'sv'=>'swe',
			'sw'=>'swa',
			'ta'=>'tam',
			'te'=>'tel',
			'tg'=>'tgk',
			'th'=>'tha',
			'ti'=>'tir',
			'tk'=>'tuk',
			'tl'=>'tgl',
			'tn'=>'tsn',
			'to'=>'ton',
			'tr'=>'tur',
			'ts'=>'tso',
			'tt'=>'tat',
			'tw'=>'twi',
			'ty'=>'tah',
			'ug'=>'uig',
			'uk'=>'ukr',
			'ur'=>'urd',
			'uz'=>'uzb',
			've'=>'ven',
			'vi'=>'vie',
			'vo'=>'vol',
			'wa'=>'wln',
			'wo'=>'wol',
			'xh'=>'xho',
			'yi'=>'yid',
			'yo'=>'yor',
			'za'=>'zha',
			'zh'=>'zho',
			'zu'=>'zul',
		];
		return (isset($codes[$lang]) ? $codes[$lang] : $lang);
	}

	function normalize_doi($doi) {
		$doi = preg_replace('/https?:\/\/doi.org\//', '', $doi);
		$doi = preg_replace('/https?:\/\/dx.doi.org\//', '', $doi);
		return $doi;
	}
}
