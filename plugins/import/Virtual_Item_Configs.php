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

class Virtual_Item_Configs extends Controller {

	private $CI;
	private $cfg;
	private $vi_config;
	public $error;
	public $config_path = 'system/application/config/virtual_items';
	public $working_path = 'system/application/config/virtual_items';

	function __construct() {
		$this->CI = get_instance();
		$this->cfg = $this->CI->config->item('macaw');
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
		if ($config['feed-type'] == 'oai_dc') {
			$this->vi_config = $config;
			print "Processing OAI-DC\n";
			$this->process_oai_dc($name, $path, $single_id, $command);
		}
		if ($config['feed-type'] == 'oai_mods') {
			$this->vi_config = $config;
			print "Processing OAI-MODS\n";
			$this->process_oai_mods($name, $path, $single_id, $command);
		}
		if ($config['feed-type'] == 'spreadsheet') {
			$this->vi_config = $config;
			print "Processing Spreadsheet\n";
			$this->process_spreadsheet($name, $path, $single_id, $command);
		}
	}

	/* ----------------------------
	 * Function: process_oai_dc()
	 *
	 * Parameters: $name, $path
	 *
     * Main function to handle reading an OAI-PMH feed in Dublin Core format
	 * to ingest it into macaw. This has clear knowledge
	 * of the format of an OAI-PMH feed and expect there
	 * to be metadata in Dublic Core (oai_dc) format. 
	 * ----------------------------
	 */
	/* 
	function process_oai_dc($name, $path, $single_id = null, $command = null) {
		$this->CI->logging->log('access', 'info', "Virutal Items: Source: $name: Processing OAI Feed");
		// Set up some working folders for efficiency
		$this->vi_config['working-path'] = $path.'/working';
		$this->vi_config['cache-path'] = $path.'/cache';
		@mkdir($this->vi_config['working-path'], 0755, true);
		@mkdir($this->vi_config['cache-path'], 0755, true);
		
		// Get the OAI Feed
		$records = [];
		if (is_array($this->vi_config['feed'])) {
			foreach((array)$this->vi_config['feed'] as $url) {
				$records = array_merge($records, $this->read_oai($url));
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
				$info['holding_institution'] = $this->vi_config['contributor'];
				$info['publisher'] = (string)$r->xpath('//dc:publisher')[0];
				$info['sponsor'] = $info['holding_institution'];
				$info['org_id'] = $this->vi_config['upload-org-id'];; 
				$info['genre'] = 'article';
				$info['collections'] = $this->vi_config['collections']; 
				// Copyright info all comes from the config file
				// There is no mechanism to pull it from the OAI feed (yet?)
				$info['copyright'] = $this->vi_config['copyright'];
				$info['cc_license'] = $this->vi_config['creative-commons'];
				$info['rights_holder'] = $this->vi_config['rights-holder'];
				$info['possible_copyright_status'] = $this->vi_config['possible-copyright-status'];
				$info['rights'] = $this->vi_config['rights'];
				
				$info['subject'] = [];
				foreach ($r->xpath('//dc:subject') as $s) {
					$info['subject'][] = (string)$s;
				}
				$info['creator'] = [];
				foreach ($r->xpath('//dc:creator') as $a) {
					$info['creator'][] = preg_replace('/,([^ ])/', ', \1', (string)$a);
				}
				$info['language'] = $this->iso639_2to3((string)$r->xpath('//dc:language')[0]);
				$info['abstract'] = (string)$r->xpath('//dc:description')[0];
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
				$vi_data = $this->vi_config['vi-identifier-data']($this->vi_config, $info, $r);
				$info['bhl_virtual_titleid'] = $this->vi_config['title-id'];
				$info['bhl_virtual_volume'] = $vi_data['virtual-volume'];
				$info['volume'] = $vi_data['volume'];
				$info['series'] = $vi_data['series'];
				$info['issue'] = $vi_data['issue'];
				$info['segment_date'] = $vi_data['year'];
				$info['page_start'] = $vi_data['page-start'];
				$info['page_end'] = $vi_data['page-end'];
				$info['year'] = $vi_data['year'];
				$info['date'] = $vi_data['date'];
				$info['source'] = $vi_data['source'];
				$info['page_range'] = $vi_data['page-range'];

				// TODO Remove this for production
				// -------------------------------
				$info['noindex'] = '1';
				// -------------------------------

				// Get the PDF
				$pdf_path = $this->vi_config['get-pdf']($this->vi_config, $r);

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
				}
			}
		}
		return $new_items;
	}
	*/
	
	/* ----------------------------
	 * Function: process_oai_mods()
	 *
	 * Parameters: $name, $path
	 *
     * Main function to handle reading an OAI-PMH feed in MODS format
	 * to ingest it into macaw. This has clear knowledge
	 * of the format of an OAI-PMH feed and expect there
	 * to be metadata in Dublic Core (oai_dc) format. 
	 * ----------------------------
	 */

	function process_oai_mods($name, $path, $single_id = null, $command = null) {
		$this->CI->logging->log('access', 'info', "Virutal Items: Source: $name: Processing OAI Feed");
		// Set up some working folders for efficiency
		$this->vi_config['working-path'] = $path.'/working';
		$this->vi_config['cache-path'] = $path.'/cache';
		@mkdir($this->vi_config['working-path'], 0755, true);
		@mkdir($this->vi_config['cache-path'], 0755, true);
		
		// Get the OAI Feed
		$records = [];
		if (is_array($this->vi_config['feed'])) {
			foreach((array)$this->vi_config['feed'] as $url) {
				$records = array_merge($records, $this->read_oai($url));
			}
		} else {
			$records = $this->read_oai($this->vi_config['feed']);
		}
		// Reminder: read_oai returns an array of XML fragments

		$this->CI->logging->log('access', 'info', "Virutal Items: Source: $name: Got ".count($records)." records: $name");
		print  "Virutal Items: Source: $name: Got ".count($records)." records: $name\n";
		$new_items = [];
		foreach ($records as $r) {
			$r = new SimpleXMLElement($r);
			$r->registerXPathNamespace('mods', 'http://www.loc.gov/mods/v3');
			
			$id = $this->safe_xpath($r, '//header/identifier', 0);

			$barcode = preg_replace("/[\/.]/", '_', $id); // No slashes!;
			if ($this->record_exists($barcode)) {
				print "Virutal Items: Source: $name: OAI Record $id already processed\n";
				// Have we seen the item?
				// Yes, report and skip.
				$this->CI->logging->log('access', 'info', "Virutal Items: Source: $name: OAI Record $id already processed");
			} else {
				// pull the details and build the metadata
				$info = [];
				$info['barcode'] = $barcode; 
				$this->CI->logging->log('book', 'info', "Creating Virtual Item Segment item.", $info['barcode']);

				$info['journal_title'] = $this->safe_xpath($r, "//mods:relatedItem[@type='host']/mods:titleInfo/mods:title", 0);
				$info['title'] = $this->safe_xpath($r, "//mods:mods/mods:titleInfo/mods:title", 0);
				$info['volume'] = $this->safe_xpath($r, "//mods:relatedItem[@type='host']/mods:part/mods:detail[@type='volume']/mods:number", 0);
				$info['issue'] = $this->safe_xpath($r, "//mods:relatedItem[@type='host']/mods:part/mods:detail[@type='issue']/mods:number", 0);
				$info['number'] = $this->safe_xpath($r, "//mods:relatedItem[@type='host']/mods:part/mods:detail[@type='number']/mods:number", 0);
				$info['series'] = $this->safe_xpath($r, "//mods:relatedItem[@type='host']/mods:part/mods:detail[@type='series']/mods:number", 0);
				$info['page_start'] = $this->safe_xpath($r, "//mods:relatedItem[@type='host']/mods:part/mods:extent[@unit='pages']/mods:start", 0);
				$info['page_end'] = $this->safe_xpath($r, "//mods:relatedItem[@type='host']/mods:part/mods:extent[@unit='pages']/mods:end", 0);
				$matches = [];
				$info['page_range'] = null;
				if (preg_match('/(\d+)\-(\d+)/', $info['page_start'], $matches)) {
					$info['pages'] = $info['page_start'];
					$info['page_range'] = $info['page_start'];
					$info['page_start'] = $matches[1];
					$info['page_end'] = $matches[2];
				}

				// $foo = $r->xpath("//mods:relatedItem[@type='host']/mods:part/mods:date");
				// $info['date'] = $this->safe_xpath($r, "//mods:relatedItem[@type='host']/mods:part/mods:date", 0);
				// print_r($foo[0]->asXML());
				// print_r($info['date']);
				// die;


				$info['date'] = $this->safe_xpath($r, "//mods:relatedItem[@type='host']/mods:part/mods:date", 0);

				$journal_year = '';
				if (preg_match('/^\d\d\d\d$/', $info['date'])) {
					$info['year'] = $info['date'];
					$info['date'] = $this->safe_xpath($r, "//mods:extension/mods:dateAvailable", 0);
				}
				
				$info['holding_institution'] = $this->vi_config['contributor'];
				$info['publisher'] = $this->safe_xpath($r, "//mods:originInfo/mods:publisher", 0);
				$info['sponsor'] = $info['holding_institution'];
				$info['org_id'] = $this->vi_config['upload-org-id'];; 
				$info['genre'] = 'article';
				$info['collections'] = $this->vi_config['collections']; 
				// Copyright info all comes from the config file
				// There is no mechanism to pull it from the OAI feed (yet?)
				$info['copyright'] = $this->vi_config['copyright'];
				$info['cc_license'] = $this->vi_config['creative-commons'];
				$info['rights_holder'] = $this->vi_config['rights-holder'];
				// $info['possible_copyright_status'] = $this->vi_config['possible-copyright-status'];
				$info['rights'] = $this->vi_config['rights'];
				
				$info['subject'] = $this->safe_xpath($r, "//mods:subject/mods:topic");
				for ($i=0; $i < count($info['subject']); $i++) { $info['subject'][$i] = (string)$info['subject'][$i]; }

				$info['creator'] = $this->safe_xpath($r, "//mods:mods/mods:name/mods:role/mods:roleTerm[text()=\"author\"]/../../mods:namePart");
				for ($i=0; $i < count($info['creator']); $i++) { $info['creator'][$i] = (string)$info['creator'][$i]; }
				
				$creator_ids = [];
				foreach ($info['creator'] as $c) {
					$res = [];
					$res['name'] = $c;
					$orcid = $this->safe_xpath($r, "//mods:mods/mods:name/mods:role/mods:roleTerm[text()=\"author\"]/../../mods:namePart[text()=\"$c\"]/../mods:nameIdentifier[@type=\"orcid\"]");
					if (is_array($orcid) && count($orcid)) {
						$res['orcid'] = (string)$orcid[0];
					}
					$viaf = $this->safe_xpath($r, "//mods:mods/mods:name/mods:role/mods:roleTerm[text()=\"author\"]/../../mods:namePart[text()=\"$c\"]/../mods:nameIdentifier[@type=\"viaf\"]");
					if (is_array($viaf) && count($viaf)) {
						$res['viaf'] = (string)$viaf[0];
					}
					$zbaut = $this->safe_xpath($r, "//mods:mods/mods:name/mods:role/mods:roleTerm[text()=\"author\"]/../../mods:namePart[text()=\"$c\"]/../mods:nameIdentifier[@type=\"zbaut\"]");
					if (is_array($zbaut) && count($zbaut)) {
						$res['zbaut'] = (string)$zbaut[0];
					}
					$scopus = $this->safe_xpath($r, "//mods:mods/mods:name/mods:role/mods:roleTerm[text()=\"author\"]/../../mods:namePart[text()=\"$c\"]/../mods:nameIdentifier[@type=\"scopus\"]");
					if (is_array($scopus) && count($scopus)) {
						$res['scopus'] = (string)$scopus[0];
					}
					$rid = $this->safe_xpath($r, "//mods:mods/mods:name/mods:role/mods:roleTerm[text()=\"author\"]/../../mods:namePart[text()=\"$c\"]/../mods:nameIdentifier[@type=\"rid\"]");
					if (is_array($rid) && count($rid)) {
						$res['rid'] = (string)$rid[0];
					}
					$creator_ids[] = $res;
				}
				$info['creator_ids'] = json_encode($creator_ids);
				$info['abstract'] = $this->safe_xpath($r, "//mods:abstract", 0);
			
				for ($i = 0; $i < count($info['creator']); $i++) {
					$info['creator'][$i] = html_entity_decode(preg_replace('/,([^ ])/', ', \1', $info['creator'][$i]));
				}

				// Language
				$info['language'] = null;
				$tmp = $this->safe_xpath($r, "//mods:language/mods:languageTerm");
				if ($tmp) {
					$attrs = $tmp[0]->attributes();
					if ((string)$attrs['authority'] == 'rfc3066') {
						$lang = (string)$tmp[0];
						// Language identifier. e.g. en_US, es_ES, 
						$lang = preg_replace('/[-_].+$/', '', $lang);
						$info['language'] =  $this->iso639_2to3($lang);
					} else {
						$info['language'] = (string)$tmp[0];
					}
				}
				
				// Figure out the DOI
				$info['doi'] = $this->safe_xpath($r, "//mods:mods/mods:identifier[@type='doi']", 0);
				if ($info['doi']) {
					if (!preg_match('/^10.\d{4,9}\/[-._;()\/:A-Z0-9]+$/i', $info['doi'])) {
						if (preg_match('/doi/i', $tmp[0])) {
							$info['doi'] = $this->normalize_doi($info['doi']);
						}
					}					
				}

				// Create Virtual Item ID and overwrite with custom info from the config
				$vi_data = [];
				$vi_data = $this->vi_config['vi-identifier-data']($this->vi_config, $info, $r);
				$info['bhl_virtual_titleid'] = $this->vi_config['title-id'];
				$info['bhl_virtual_volume'] = $vi_data['virtual-volume'];
				$info['volume'] = $vi_data['volume'];
				$info['series'] = $vi_data['series'];
				$info['issue'] = $vi_data['issue'];
				$info['page_start'] = $vi_data['page-start'];
				$info['page_end'] = $vi_data['page-end'];
				$info['page_range'] = $vi_data['page-range'];
				$info['year'] = $vi_data['year'];
				$info['date'] = $vi_data['date'];
				$info['source'] = $vi_data['source'];

				// TODO Remove this for production
				// -------------------------------
				$info['noindex'] = '1';
				// -------------------------------
				
				// Get the PDF
				$pdf_path = $this->vi_config['get-pdf']($this->vi_config, $r);

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
							'title' => substr($info['title'],0,128),
							'barcode' => $info['barcode'],
							'created' => date("Y-m-d H:i:s")
						)
					);
					$this->CI->logging->log('access', 'info', "Virutal Items: Source: $name: Added item with barcode ".$info['barcode']);
				}
			}
		}
		return $new_items;
	}

	function process_spreadsheet($name, $path, $single_id = null, $command = null) {
	}

	function add_book($name, $info, $pdf_path) {
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
			
			// Set the creator ids
			$this->CI->book->set_metadata('creator_ids', $info['creator_ids']);

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
				$this->CI->book->set_page_metadata($p->id, 'page_number_implicit', 0);
				$this->CI->book->set_page_metadata($p->id, 'page_side', (($even++ % 2) ? 'left (verso)' : 'Right (recto)'));				

				$this->CI->book->set_page_metadata($p->id, 'year', $info['year']);
				if (isset($info['volume'])) {
					if ($info['volume']) {
						$this->CI->book->set_page_metadata($p->id, 'volume', $info['volume']);
					}
				}
				if (isset($info['series'])) {
					if ($info['series']) {
						$this->CI->book->set_page_metadata($p->id, 'piece', 'No.');	
						$this->CI->book->set_page_metadata($p->id, 'piece_text', $info['series']);
					}
				}
				if (isset($info['issue'])) {
					if ($info['issue']) {
						$this->CI->book->set_page_metadata($p->id, 'piece', 'Issue');	
						$this->CI->book->set_page_metadata($p->id, 'piece_text', $info['issue']);
					}
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
		$cache_file = $this->vi_config['cache-path'].'/'.$md5;

		// Cache the URL if it doesn't exist
		if (!file_exists($cache_file)) {
			file_put_contents(
				$this->vi_config['cache-path'].'/'.$md5, 
				file_get_contents($url)
			);
		}
		
		$xml = simplexml_load_file($this->vi_config['cache-path'].'/'.$md5);
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
		// Do we have a Title ID
		if (!$config['title-id']) {
			print "No TitleID\n";
			return false;
		}
		// Do we have a feed
		if (!isset($config['feed'])) {
			print "No feed specified.\n";
			return false;
		}
		// Do we have a feed type
		if (!isset($config['feed-type'])) {
			print "Feed type not specified.\n";
			return false;
		}
		if ($config['feed-type'] != "oai_dc" && $config['feed-type'] != "oai_mods" && $config['feed-type'] != "spreadsheet") {
			print "Feed type not recognized. Must be one of: oai_dc, oai_mods, spreadsheet\n";
			return false;
		}
		if (is_callable('$config[\'vi-identifier-data\']')) {
			print "'vi_identifier_data' is not a function.\n";
			return false;
		}
		if (is_callable('$config[\'get-pdf\']')) {
			print "'get_pdf' is not a function.\n";
			return false;
		}


		$bhl_institutions = $this->CI->bhl->get_institutions();
		$found = false;
		foreach ($bhl_institutions as $b) {
			if ($b->InstitutionName == $config['rights-holder']) {
				$found = true;
			}
		}
		if (!$found) {
			print "Rights holder is not found at BHL. (\"".$config['rights-holder']."\")\n";
			return false;
		}




		// Nake sure the contributor is valid in BHL
		// Make sure something else is valid
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
						"batch_id int,".
					  "title varchar(128), ".
					  "barcode varchar(128), ".
					  "created timestamp, ".
					  "PRIMARY KEY(`barcode`)".
					  ") ENGINE=InnoDB CHARACTER SET=utf8 COLLATE=utf8_unicode_ci;"
				);
			}	
			if (!$this->CI->db->table_exists('custom_virtual_items_batches')) {
				$this->CI->db->query(
					"create table custom_virtual_items_batches (".
						"id int(11) auto_increment NOT NULL,".
						"source_filename varchar(128), ".
					  "uploader varchar(32), ".
					  "total_items int, ".
						"created timestamp, ".
					  "PRIMARY KEY(`id`)".
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

	function safe_xpath($record, $path, $index = null) {
		$found = $record->xpath($path);
		$ret = [];
		if (is_array($found)) {
			foreach ($found as $f) {
				$ret[] = $f;
			}
		}
		if ($index !== null) {
			if (isset($ret[$index])) {
				return (string)$ret[$index];
			} else {
				return null;
			}
		}
		return $ret;
	}
}
