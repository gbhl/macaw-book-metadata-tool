<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Custom Import Module
 *
 * Provides an interface to local systems to get the bibliographic and
 * item-level metadata for a given installation of Macaw. This contains one
 * function which returns a list of things that are ready to be imported into
 * Macaw.
 * 
 * Note: To delete and reload an item the value from "custom_internet_archive"
 * table MUST be saved and restored to avoid creating a duplicate at IA. 
 * Once saved, the Virtual Item Article should be be deleted entirely from 
 * Macaw before it will be reimported to Macaw. Then the IA ID must be restored.
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

	private $id_types = array(
		array('doi' => 'VIAF', 'mods' => 'viaf', 'bhl' => 'viaf'),
		array('doi' => 'ORCID', 'mods' => 'orcid', 'bhl' => 'orcid'),
		array('doi' => 'BioStor', 'mods' => 'biostor', 'bhl' => 'biostor'),
		array('doi' => 'DLC', 'mods' => 'dlc', 'bhl' => 'dlc'),
		array('doi' => 'ResearchGate', 'mods' => 'researchgate', 'bhl' => 'researchgate profile'),
		array('doi' => 'SNAC', 'mods' => 'snac ', 'bhl' => 'snac ark'),
		array('doi' => 'Tropicos', 'mods' => 'tropicos', 'bhl' => 'tropicos'),
	);

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
			return false;
		}
		// if ($config['feed-type'] == 'oai_dc') {
		// 	$this->vi_config = $config;
		// 	$this->process_oai_dc($name, $path, $single_id, $command);
		// }
		if ($config['feed-type'] == 'oai_mods') {
			$this->vi_config = $config;
			$this->process_oai_mods($name, $path, $single_id, $command);
		}
		if ($config['feed-type'] == 'spreadsheet') {
			$this->vi_config = $config;
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
				$info['bhl_virtual_volume'] = $vi_data['virtual-volume'];
				$info['volume'] = $vi_data['volume'];
				$info['series'] = $vi_data['series'];
				$info['issue'] = $vi_data['issue'];
				$info['segment_date'] = $vi_data['year'];
				$info['page_start'] = $vi_data['page_start'];
				$info['page_end'] = $vi_data['page_end'];
				$info['year'] = $vi_data['year'];
				$info['date'] = $vi_data['date'];
				$info['source'] = $vi_data['source'];
				$info['page_range'] = $vi_data['page_range'];

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
 					$page_count = $this->add_book($name, $info, $pdf_path);

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
					$this->CI->logging->log('access', 'info', "Virutal Items: Source: $name: Added item with barcode ".$info['barcode']." with $page_count pages.");
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
		$new_items = [];
		foreach ($records as $r) {
			$r = new SimpleXMLElement($r);
			$r->registerXPathNamespace('mods', 'http://www.loc.gov/mods/v3');
			
			$id = $this->safe_xpath($r, '//header/identifier', 0);

			$barcode = preg_replace("/[\/.]/", '_', $id); // No slashes!;
			if (!$this->record_exists($barcode)) {
				// pull the details and build the metadata
				$info = [];
				$info['barcode'] = $barcode; 
				$this->CI->logging->log('book', 'info', "Creating Virtual Item Segment item.", $info['barcode']);

				// Figure out the DOI\
				$info['doi'] = $this->safe_xpath($r, "//mods:mods/mods:identifier[@type='doi']", 0);
				$doi_details = [];

				// There are common regardless of the source of data
				$info['genre'] = 'article';
				$info['org_id'] = $this->vi_config['upload-org-id'];; 
				$info['collections'] = $this->vi_config['collections']; 
				$info['copyright'] = $this->vi_config['copyright'];
				$info['rights_holder'] = $this->vi_config['rights-holder'];
				$info['rights'] = $this->vi_config['rights'];
				$info['holding_institution'] = $this->vi_config['contributor'];
				$info['sponsor'] = $this->vi_config['contributor']; // Yes, they are the same

				// If we have a DOI, use that to fill in all of the data
				$used_doi = false;
				if ($info['doi']) {
					$info['doi'] = $this->normalize_doi($info['doi']);
					$used_doi = $this->process_doi($info);
				}

				// Fill in the blanks: fall back to the MODS when something is blank
				if (!$used_doi) {
					$this->CI->logging->log('book', 'info', "DOI not found or unavailable. Falling back to MODS.", $info['barcode']);
				}
				
				if (!isset($info['title'])) {
					$info['title'] = $this->safe_xpath($r, "//mods:mods/mods:titleInfo/mods:title", 0);
				}

				if (!isset($info['subject'])) {
					$info['subject'] = $this->safe_xpath($r, "//mods:subject/mods:topic");
					for ($i=0; $i < count($info['subject']); $i++) { $info['subject'][$i] = (string)$info['subject'][$i]; }
				}

				if (!isset($info['language'])) {
					// $info['language'] = null;
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
				}

				if (!isset($info['abstract'])) {
					$info['abstract'] = $this->safe_xpath($r, "//mods:abstract", 0);
				}
				if (!isset($info['cc_license'])) {
					// default to the config file
					$info['cc_license'] = $this->vi_config['creative-commons'];
				}
				if (!isset($info['publisher'])) {
					$info['publisher'] = $this->safe_xpath($r, "//mods:originInfo/mods:publisher", 0);
				}
				if (!isset($info['journal_title'])) {
					$info['journal_title'] = $this->safe_xpath($r, "//mods:relatedItem[@type='host']/mods:titleInfo/mods:title", 0);
				}
				if (!isset($info['volume'])) {
					$info['volume'] = $this->safe_xpath($r, "//mods:relatedItem[@type='host']/mods:part/mods:detail[@type='volume']/mods:number", 0);
				}
				if (!isset($info['issue'])) {
					$info['issue'] = $this->safe_xpath($r, "//mods:relatedItem[@type='host']/mods:part/mods:detail[@type='issue']/mods:number", 0);
				}
				if (!isset($info['number'])) {
					$info['number'] = $this->safe_xpath($r, "//mods:relatedItem[@type='host']/mods:part/mods:detail[@type='number']/mods:number", 0);
				}
				if (!isset($info['series'])) {
					$info['series'] = $this->safe_xpath($r, "//mods:relatedItem[@type='host']/mods:part/mods:detail[@type='series']/mods:number", 0);
				}
				if (!isset($info['date'])) {
					$info['date'] = $this->safe_xpath($r, "//mods:relatedItem[@type='host']/mods:part/mods:date", 0);
				}
				if (!isset($info['year']) && isset($info['date'])) {
					if (preg_match('/^\d\d\d\d$/', $info['date'])) {
						$info['year'] = $info['date'];
						$info['date'] = $this->safe_xpath($r, "//mods:extension/mods:dateAvailable", 0);
					} else {
						$matches = [];
						if (preg_match('/(\d\d\d\d)/', $info['date'], $matches)) {
							$info['year'] = $matches[1];
						}
					}
				}
				// Determine if we are an eLocator in the pages field? --  Form is "e\d+"
				// If it's an eLocator then we number the pages based on the count of pages
				// in the PDF. 1 to N.
				$page_start = $this->safe_xpath($r, "//mods:relatedItem[@type='host']/mods:part/mods:extent[@unit='pages']/mods:start", 0);
				if (preg_match("/^e\d+$/", $page_start)) {
					$info['elocator'] = $page_start;
				}

				if (!isset($info['elocator'])) {
					if (!isset($info['page_start'])) {
						$info['page_start'] = $this->safe_xpath($r, "//mods:relatedItem[@type='host']/mods:part/mods:extent[@unit='pages']/mods:start", 0);
					}
					if (!isset($info['page_end'])) {
						$info['page_end'] = $this->safe_xpath($r, "//mods:relatedItem[@type='host']/mods:part/mods:extent[@unit='pages']/mods:end", 0);
					}
					if (!isset($info['page_range']) && isset($info['page_start']) && isset($info['page_end'])) {
						$info['page_range'] = $info['page_start'].'-'.$info['page_end'];
					}
					if (!isset($info['pages']) && isset($info['page_start']) && isset($info['page_end'])) {
						$info['pages'] = (int)$info['page_end'] - (int)$info['page_start'] + 1;
					}
				}
				if (!isset($info['creator'])) {
					$info['creator'] = $this->safe_xpath($r, "//mods:mods/mods:name/mods:role/mods:roleTerm[text()=\"author\"]/../../mods:namePart");
					for ($i=0; $i < count($info['creator']); $i++) { 
						$info['creator'][$i] = html_entity_decode(preg_replace('/,([^ ])/', ', \1', (string)$info['creator'][$i]));
					}
				}
				if (!isset($info['creator_ids'])) {
					$creator_ids = [];
					foreach ($info['creator'] as $c) {
						$res = [];
						$res['name'] = $c;
						
						// Get the ID(s) from the MODS
						foreach ($this->id_types as $type) {
							$id_value = $this->safe_xpath($r, "//mods:mods/mods:name/mods:role/mods:roleTerm[text()=\"author\"]/../../mods:namePart[text()=\"$c\"]/../mods:nameIdentifier[@type=\"".$type['mods']."\"]");
							if (is_array($id_value) && count($id_value)) {
								$res[$type['bhl']] = (string)$id_value[0];
							}	
						}

						$creator_ids[] = $res;
					}
					$info['creator_ids'] = json_encode($creator_ids);
				}

				// Get the PDF
				$pdf_path = $this->vi_config['get-pdf']($this->vi_config, $r, $info);

				if ($pdf_path) {
					if (isset($info['elocator'])) {
						// Get these from the PDF
						$pdf_image = new Imagick();
						$pdf_image->pingImage($pdf_path);
						$pp = $pdf_image->getNumberImages();
	
						$info['page_start'] = 1;
						$info['page_end'] = $pp;
						$info['page_range'] = "1-".$pp;
						$info['pages'] = $pp;
					}
				}
				// Create Virtual Item ID and overwrite with custom info from the config
				// TODO Make sure the various configs are prepared for this. (dashes vs underscores)
				$vi_data = [];
				$vi_data = $this->vi_config['vi-identifier-data']($this->vi_config, $info, $r);
				$info['bhl_virtual_titleid'] = $vi_data['virtual-id'];
				$info['bhl_virtual_volume'] = $vi_data['virtual-volume'];
				$info['volume'] = $vi_data['volume'];
				$info['series'] = $vi_data['series'];
				$info['issue'] = $vi_data['issue'];
				$info['page_start'] = $vi_data['page_start'];
				$info['page_end'] = $vi_data['page_end'];
				$info['page_range'] = $vi_data['page_range'];
				$info['year'] = $vi_data['year'];
				$info['date'] = $vi_data['date'];
				$info['source'] = $vi_data['source'];

				// Clean up some potentially messy data
				$info['title'] = $this->clean_string($info['title']);
				// Test for PDF-ness
				$finfo = new finfo(FILEINFO_MIME);
				$mime_type = finfo_file($finfo, $pdf_path);
				if (!preg_match('/pdf/', $mime_type)) {
					$this->CI->logging->log('book', 'error', "Did not get a valid PDF file. (Got: $mime_type)", $info['barcode']);
					$pdf_path = null;
				}

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
					$page_count = $this->add_book($name, $info, $pdf_path);

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
					$this->CI->logging->log('access', 'info', "Virutal Items: Source: $name: Added item with barcode ".$info['barcode']." with $page_count pages.");
				}
			}
		}
		return $new_items;
	}

	/* ----------------------------
	 * Function: process_doi()
	 *
	 * Parameters: $info
	 *
     * Extract what info we can from the DOI. Preliminary
	 * searches show that DOI metadata is not standardized
	 * which means that this will grow over time to accommodate
	 * different sitautions and different metadata.
	 * 
	 * Assumes that $info['doi'] is set so that we can search
	 * the DOI in the CrossRef API.
	 * 
	 * Reference: https://data.crossref.org/reports/help/schema_doc/5.3.1/index.html
	 * ----------------------------
	 */

	function process_doi(&$info) {
		$url = "https://api.crossref.org/works/". $info['doi'];
		$doi_details = @file_get_contents($url);
		if (!$doi_details) {
			return false;
		}

		$doi_details = @json_decode($doi_details, true);
		if (!$doi_details) {
			return false;
		}
		
		if ($doi_details['status'] != 'ok') {
			return false;
		}
		$doi_details = $doi_details['message']; // derefernce for easier code

		if (isset($doi_details['container-title'])) {
			$temp = $doi_details['container-title'];
			$info['journal_title'] = is_array($temp) ? $temp[0] : $temp;
		} 

		if (isset($doi_details['title'])) {
			$temp = $doi_details['title'];
			$info['title'] = is_array($temp) ? $temp[0] : $temp;
		} 

		if (isset($doi_details['volume'])) {
			$temp = $doi_details['volume'];
			$info['volume'] = is_array($temp) ? $temp[0] : $temp;	
		}
		if (isset($doi_details['issue'])) {
			$temp = $doi_details['issue'];
			$info['issue'] = is_array($temp) ? $temp[0] : $temp;
		}
		if (isset($doi_details['number'])) {
			$temp = $doi_details['number'];
			$info['number'] = is_array($temp) ? $temp[0] : $temp;
		}
		if (isset($doi_details['series'])) {
			$temp = $doi_details['series'];
			$info['series'] = is_array($temp) ? $temp[0] : $temp;
		}
		
		if (isset($doi_details['abstract'])) {
			$info['abstract'] = preg_replace('/<[^>]+>/','',$doi_details['abstract']);
		}
		if (isset($doi_details['publisher'])) {
			$temp = $doi_details['publisher'];
			$info['publisher'] = is_array($temp) ? $temp[0] : $temp;
		}

		// subjects are missing from Pensoft DOI Data - Don't know what it looks like
		// language is missing from Pensoft DOI Data - Don't know what it looks like

		// Split the page range if necessary
		$matches = []; 
		if (isset($doi_details['page'])) { // this is a page range for pensoft
			if (preg_match('/(\d+)\-(\d+)/', $doi_details['page'], $matches)) {
				$info['page_range'] = $doi_details['page'];
				$info['page_start'] = $matches[1];
				$info['page_end'] = $matches[2];
				$info['pages'] = $info['page_end'] - $info['page_start'] + 1;
			} else {
				// Not sure this is correct when there's only one value.
				$info['page_start'] = $doi_details['page'];
			}
		}

		// Get a publication date. Or whatever we can find.
		$pub_date = null;
		if (isset($doi_details['publication_date']) && !$pub_date) { $pub_date = $doi_details['publication_date']; }
		if (isset($doi_details['published']) && !$pub_date) { $pub_date = $doi_details['published']; }
		if (isset($doi_details['published-online']) && !$pub_date) { $pub_date = $doi_details['published-online']; }
		if (isset($doi_details['issued']) && !$pub_date) { $pub_date = $doi_details['issued']; }
		if (isset($doi_details['created']) && !$pub_date) { $pub_date = $doi_details['created']; }
	
		if ($pub_date) {
			$info['date'] = $pub_date['date-parts'][0][0].'-'.
							$pub_date['date-parts'][0][1].'-'.
							$pub_date['date-parts'][0][2];
			$info['year'] = $pub_date['date-parts'][0][0];
		}
		if (isset($doi_details['license'])) {
			if (isset($doi_details['license'][0]['URL'])) {
				$info['cc_license'] = $doi_details['license'][0]['URL'];
			}
		}

		$info['creator'] = [];
		$creator_ids = [];
		if (isset($doi_details['author'])) {
			foreach ($doi_details['author'] as $author) {
				$a = [];
				if (isset($author['given']) && isset($author['family'])) {
					// We have a full name
					$a['given_name'] = $author['given'];
					$a['family_name'] = $author['family'];
					$a['name'] = $author['family'].', '.$author['given'];

					$info['creator'][] = $author['family'].', '.$author['given'];

				} elseif (isset($author['family'])) {
					// Only last name...ok.
					$a['family_name'] = $author['family'];
					$a['name'] = $author['family'];

					$info['creator'][] = $author['family'];
				} elseif (isset($author['given'])) {
					// Only first name?
					$a['given_name'] = $author['given'];
					$a['name'] = $author['given'];

					$info['creator'][] = $author['given'];
				}
				
				foreach ($this->id_types as $type) {
					if (isset($author[$type['doi']])) {
						$a[$type['bhl']] = $author[$type['doi']];
					}
				}
				$creator_ids[] = $a;
			}
		}	
		$info['creator_ids'] = json_encode($creator_ids);

		// Attempt to get the PDF
		if (isset($doi_details['link'])) {
			if (is_array($doi_details['link'])) {
				foreach ($doi_details['link'] as $link) {
					if (isset($link['content-type']) && $link['content-type'] == 'application/pdf') {
						$info['pdf_source'] = $link['URL'];
						break;
					}
				}
			}
		}


		return true;
	}

	function process_spreadsheet($name, $path, $single_id = null, $command = null) {
	}

	function add_book($name, $info, $pdf_path) {
		$page_count = -1;
		try {
			$ret = $this->CI->book->add($info);
		} catch (Exception $e) {
			print "Error: ".$e->getMessage()."\n";
			$this->CI->logging->log('book', 'error', $e->getMessage(), $info['barcode']);
			$this->CI->logging->log('error', 'info', $e->getMessage());
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
			$page_count = count($pages);

			$first = true;
			$even = 0;
			$page_num = $info['page_start'];
			if (!$page_num) {
				$page_num = 1;
			}

			foreach ($pages as $p) {
				$this->CI->book->set_page_metadata($p->id, 'page_type', $first ? 'Cover' : 'Text');
				$this->CI->book->set_page_metadata($p->id, 'page_number', $page_num);
				$this->CI->book->set_page_metadata($p->id, 'page_number_implicit', 0);
				$this->CI->book->set_page_metadata($p->id, 'page_side', ($even++ % 2) ? 'Left (verso)' : 'Right (recto)');				

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
				$this->CI->logging->log('book', 'info', $this->CI->book->last_error, $info['barcode']);
			}
		}

		return $page_count;
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

		// Delete cache if it's too old (24 hours)
		if (file_exists($cache_file)) {
			if (time()-filemtime($cache_file) > 86400) {
				unlink($cache_file);
			}
		}
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
		if (!$xmlNode) {
			$this->CI->logging->log('access', 'info', "Virutal Items: No Records found for: $url");
			return [];
		}
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
			return false;
		}
		// Do we have a feed
		if (!isset($config['feed'])) {
			return false;
		}
		// Do we have a feed type
		if (!isset($config['feed-type'])) {
			return false;
		}
		if ($config['feed-type'] != "oai_dc" && $config['feed-type'] != "oai_mods" && $config['feed-type'] != "spreadsheet") {
			return false;
		}
		if (is_callable('$config[\'vi-identifier-data\']')) {
			return false;
		}
		if (is_callable('$config[\'get-pdf\']')) {
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

	function clean_string($s) {
		$s = preg_replace("/[\r\n]+/", "", $s);

		return $s;
	}
}
