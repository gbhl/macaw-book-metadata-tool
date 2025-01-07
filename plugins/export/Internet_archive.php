<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/* ***********************************************************
 * Macaw Metadata Collection and Workflow System
 *
 * EXPORT LIBRARY
 *
 * Each destination with whom we share our book will have an export routine
 * which contains functions for sending data to the system, verifying receipt
 * of the data, and optionally pulling any derivative data, etc.
 * Each Export library corresponds to an entry in the macaw.php file.
 *
 * Each module has a library with the name "Export_Name.php". The name
 * must correspond to one of the items in the export_modules entry in the macaw.php
 * configuration file. Each must contain ax export() methox. Other functions
 * may be used if necessary.
 *
 * Each module must set a "completed" status to the export process via the
 * Book objects set_export_status() method:
 *
 *     $this->CI->book->set_export_status('completed');
 *
 * Other statuses are allowed if the exporting happens in multiple steps.
 * This module is required to maintan the statuses and eventually set a
 * status of 'completed' when it's finished exporting. Once all export
 * modules have marked the item as completed, Macaw then proceeds to archive
 * and purge the data on its own schedule, if such routines are set up.
 *
 * CONNECTION PARAMETERS
 *
 * To connect to the Internet Archive, you need your login credentials. The 
 * API Key and Secret should be set in the config/macaw.php file.
 * 
 * The variables are:
 *
 *    $config['macaw']['internet_archive_access_key'] = "";
 * 	  $config['macaw']['internet_archive_secret'] = "";
 *
 * OTHER NOTES
 *
 * Imagemagick MUST be compiled with JPEG2000 support.
 *     For macports, this is: port install imagemagick +jpeg2
 *     For standard, this is: ./configure --with-jpeg2 (and whatever other options you have)
 *
 * The Jasper JPEG200 ibrary should be installed, too. Duh.
  *********************************************************** */
include ('Archive/Tar.php');

 /* SYNOPSIS
 * Run the entire harvest/verify/export routine for all items
 * 		sudo -u www php index.php cron export Internet_archive
 *
 * Run the harvest/verify/export routine for one item (supplying the item id)
 * 		sudo -u www php index.php cron export Internet_archive 123
 *
 * Run export routine for just one file of one item
 * 		sudo -u www php index.php cron export Internet_archive 123 marc
 * 		sudo -u www php index.php cron export Internet_archive 123 scans
 * 		sudo -u www php index.php cron export Internet_archive 123 scandata
 *
 * Alternatively, we can force it to run all of the files using:
 * 		sudo -u www php index.php cron export Internet_archive 123 force
 * 
 * Or run the export for one file
 * 		sudo -u www php index.php cron export Internet_archive 123 scans force
 */
class Internet_archive extends Controller {

	// This info is from account at Internet Archive. Change one, change them all.
	private $access = '';
	private $secret = '';

	private $send_orig_jp2 = "no"; // "yes", "no", or "both" This creates faster uploads when false (larger files/slower uploads when true)
	private $timing = false; // This makes more noise about how long things are taking.
	private $download_extensions = array('_djvu.xml', '_chocr.html.gz'); // What must be online after derivation? These indicate IA is finished deriving.
	private $required_extensions = array('_jp2.zip', '_marc.xml', '_scandata.xml'); // What must be online after upload? These are things that we uploaded.

	var $curl;
	var $CI;
	var $cfg;
	var $cookie_jar;

	/* ----------------------------
	 * Function: CONSTRUCTOR
	 *
	 * Be sure to rename this from "Export_Generic" to whatever you named the
	 * class above. Othwerwise, ugly things will happen. You don't need to edit
	 * anything here, either.
	 * ---------------------------- */
	function __construct() {
		$this->CI = get_instance();
		$this->cfg = $this->CI->config->item('macaw');

		// Get our connection params if they exist in the configuration
		if (array_key_exists('internet_archive_access_key', $this->cfg)) {
			$this->access = $this->cfg['internet_archive_access_key'];
		}
		if (array_key_exists('internet_archive_secret', $this->cfg)) {
			$this->secret = $this->cfg['internet_archive_secret'];
		}
	}

	/* ----------------------------
	 * Function: export()
	 *
	 * Parameters:
	 *    $args - An array of items passed from the command line (or URL)
	 *            that are specific to this module. The Export Mode
	 *            simply passes these in as the were received.
	 *
	 * Simply calls the other functions to interact with Internet Archive.
	 * ---------------------------- */
	function export($args) {
		// We REALLY need this table to exist, but this can be run only once per session
		// due to ornery caching on the part of the DB module.
		$this->_check_custom_table();

		// Auto-upgrade for the new features
		$this->CI->db->query("update item_export_status set status_code = 'verified_upload' where status_code = 'verified';");
		$this->CI->logging->log('access', 'info', "Starting Internet_archive export.");

		// --------------------------------------
		// If we can run multiple exports, then do so
		// --------------------------------------
		$limit = 1;
		if (array_key_exists('export_concurrency_limit', $this->cfg)) {
			$limit = (int)$this->cfg['export_concurrency_limit'];
			if ($limit < 1) { $limit = 1; } // Limit the limits
		}

		// --------------------------------------
		// Are there too many sibling processes? 
		// --------------------------------------
		$found = $this->count_exports();
		if ($found == 0) {
			// If we didn't find an invocation of "php index.php cron export Internet_archive"
			// then we look for all exports and count from there.
			$found = $this->count_exports("cron export");
		}

		if ($found > ($limit-1)) { // We subtract one to account for ourself
			// No, so we quit.
			if (!getenv("MACAW_OVERRIDE")) {
				$this->CI->logging->log('access', 'info', "Too many Internet_archive children. Exiting.");
				return false;
			} else {
				$this->CI->logging->log('access', 'info', "Got override. Continuing.");
			}
		}

		// Since the Internet Archive upload does multiple things, this
		// method simply calls the other methods in order.
		$this->harvest($args);
		$this->verify_uploaded($args);
		$this->verify_derived($args);
		$this->upload($args);
	}

	/* ----------------------------
	 * Function: export()
	 *
	 * Parameters:
	 *    $args - An array of items passed from the command line (or URL)
	 *            that are specific to this module. The Export Mode
	 *            simply passes these in as the were received.
	 *
	 * Sends everything to the Internet Archive. This function is called by the
	 * export() method above.
	 * ---------------------------- */
	function upload($args) {
		$sent_id = null;

		$sent_id = (count($args) >= 1 ? $args[0] : null);
		# $file should be "marc", "scans", "scandata", "meta"
		$file = (count($args) >= 2 ? $args[1] : '');
		$force = false;
		if (count($args) > 0) {
			if ($args[count($args)-1] == 'force') {
				$force = true;
			}
		}

		// Find those items that need to be uploaded or use the ID we were given
		if ($sent_id) {
			$books = $this->CI->book->search('barcode', $sent_id, 'date_review_end');
			if (count($books) == 0) {
				$books = $this->CI->book->search('id', $sent_id, 'date_review_end');
			}
		} else {
			// Get those books that need to be uploaded by searching for those that are
			// ready to be uploaded (item.status_code = 'reviewed') and have not yet been
			// uploaded (item_export_status.status_code is blank).
			$books = $this->_get_books('NULL');
		}

		while (count($books) > 0) {
      		$b = array_pop($books);
			try {
				print "Exporting ".$b->barcode."...\n";
				$bc = $b->barcode;
				$this->CI->book->load($bc);

				// Are we suppressing this from Internet Archive
				if ($this->CI->book->get_metadata('no_ia_upload') == 1) {
					// Yes, skip it.
					// TODO: Later we need to do something where we stop seeing this item all the time. 
					// e.g. Set the item_export_status to Completed
					// e.g. Set the prefix to "NOT UPLOADED" or something like that.
					continue;
				}
				// the keys are now different for each item. We need to get them from our custom table.
				$this->_get_ia_keys($this->CI->book->org_id);
				
				// If we didn't get any keys, we're doomed! Spam the admin and skip this item.
				if ((!$this->access || !$this->secret) && !$this->cfg['testing']) {
					$this->CI->logging->log('book', 'error', 'Contributor '.$this->CI->book->org_id.' does not have IA Keys set.', $bc);
					$this->CI->common->email_error('Contributor '.$this->CI->book->org_id.' does not have IA Keys set.'."\n\n"."Identifier:    ".$bc."\n\n");
					continue;
				}
				
				// If we were given a specific ID, we only upload the book if it's been reviewed or it's already uploaded.
				// TODO: Remove this, we will unconditionall upload if an ID is provided.
				if ($sent_id) {
					if ($this->CI->book->status != 'reviewed' && $this->CI->book->status != 'exporting' && !$file) {
						echo '(export) The item with id #'.$sent_id.' is not marked as reviewed or exporting and cannot be uploaded. (status is '.$this->CI->book->status.')'."\n";
						continue;
					}
					$status = $this->CI->book->get_export_status('Internet_archive');
					if (!$force && $status && $file != 'meta') {
						echo '(export) The item with id #'.$sent_id.' cannot be exported. It has export status "'.$status.'".'."\n";
						continue;
					}
				}

				// Log that we are starting to upload the file (info)
				$this->CI->logging->log('book', 'info', 'Starting upload to internet archive.', $bc);

				// Get an identifier for this book
				$metadata = $this->_get_metadata();
				$id = null;
				if ($metadata) {
					$id = $this->identifier($b, $metadata);
					if (!$id) { print "No Identifier can be found\n"; return; }
					$metadata['x-archive-meta-identifier'] = $id;
				} else {
					$message = "Error processing export.\n\n".
						"Identifier:    ".$bc."\n\n".
						"IA Identifier: ".$id."\n\n".
						"Error Message: Could not get metadata for item with barcode ".$bc.". Check the MARC data.\n\n";
					$this->CI->common->email_error($message);
					continue;				
				}
				
				if ($id == '') {
					$this->CI->book->set_status('error');
					$this->CI->logging->log('book', 'error', 'Could not get an identifier for the book.', $bc);
					continue;
				}
				echo 'IDENTIFIER IS '.$id.' ('.$bc.")\n";
				if ($id == null || $id == '00') {
					echo '(exporting) Could not get an identifier for item with barcode '.$bc.'. Check the metadata.'."\n";

					$message = "Error processing export.\n\n".
						"Identifier:    ".$bc."\n\n".
						"IA Identifier: ".$id."\n\n".
						"Error Message: Could not get an identifier for item with barcode ".$bc.". Check the metadata.\n";
					$this->CI->common->email_error($message);
					continue;
				}

				$this->CI->logging->log('book', 'debug', 'Identifier is '.$id.'.', $bc);
        
        // Mark that this is being uploaded to keep it our of other lists.
				$this->CI->book->set_export_status('uploading', $force); 

				$archive_file_orig = '';
				$archive_file = '';
				$jp2path_orig = '';
				$jp2path = '';
				$new_filebase_orig = '';
				$new_filebase = '';

				// We're gonna need some paths.
				$basepath = $this->cfg['data_directory'].'/import_export';
				if (!file_exists($basepath)) {
					mkdir($basepath, 0775);
					$this->CI->logging->log('book', 'debug', 'Directory created: '.$basepath, $bc);
				}

				$fullpath = $basepath.'/Internet_archive/'.$id;
				$scanspath = $this->cfg['data_directory'].'/'.$bc.'/scans';
				if ($this->send_orig_jp2 == 'yes' || $this->send_orig_jp2 == 'both') {
					$jp2path_orig = $basepath.'/Internet_archive/'.$id.'/'.$id.'_orig_jp2';
					$archive_file_orig = $fullpath.'/'.$id.'_orig_jp2.tar';
				}

				if ($this->send_orig_jp2 == 'no' || $this->send_orig_jp2 == 'both') {
					$jp2path = $basepath.'/Internet_archive/'.$id.'/'.$id.'_jp2';
					$archive_file = $fullpath.'/'.$id.'_jp2.zip';
				}

				// Create (if not exists) the /books/export/Internet_archive/ folder
				if (!file_exists($basepath.'/Internet_archive')) {
					mkdir($basepath.'/Internet_archive', 0775);
					$this->CI->logging->log('book', 'debug', 'Directory created: '.$basepath.'/Internet_archive', $bc);
				}

				// Create (if not exists) the /books/export/Internet_archive/IDENTIFIER folder
				if (!file_exists($fullpath)) {
					mkdir($fullpath, 0775);
					$this->CI->logging->log('book', 'debug', 'Directory created: '.$fullpath, $bc);
				}

				// Create (if not exists) the /books/export/Internet_archive/IDENTIFIER/IDENTIFIER_orig_jp2 folder
				if ($this->send_orig_jp2 == 'yes' || $this->send_orig_jp2 == 'both') {
					if (!file_exists($jp2path_orig)) {
						mkdir($jp2path_orig, 0775);
						$this->CI->logging->log('book', 'debug', 'Directory created: '.$jp2path_orig, $bc);
					}
				}
				if ($this->send_orig_jp2 == 'no' || $this->send_orig_jp2 == 'both') {
					if (!file_exists($jp2path)) {
						mkdir($jp2path, 0775);
						$this->CI->logging->log('book', 'debug', 'Directory created: '.$jp2path, $bc);
					}
				}

				// delete the existing ZIP, since we're about to create it
				if ($archive_file_orig && file_exists($archive_file_orig) && !$id) {
					unlink($archive_file_orig);
				}
				if ($archive_file && file_exists($archive_file) && !$id) {
					unlink($archive_file);
				}
				// If we are forcing a reupload of the scans files, let's also force a re-zip/re-tar
				if ($file == 'scans' && $force) {
					@unlink($archive_file_orig);
					@unlink($archive_file);
				}

				// Tar up the scans into the IDENTIFIER_orig_jp2.tar or IDENTIFIER_jp2.zip file
				// Get the pages from the database, ordered by sequence-number
				$pages = $this->CI->book->get_pages();

				// Some things are better handled later if they are arrays
				foreach ($pages as $p) {
					if (property_exists($p, 'page_type')) {
						if (!is_array($p->page_type)) {
							$p->page_type = array($p->page_type);
						}
					}
					if (property_exists($p, 'piece')) {
						if (!is_array($p->piece)) {
							$p->piece = array($p->piece);
						}
					}
					if (property_exists($p, 'piece_text')) {
						if (!is_array($p->piece_text)) {
							$p->piece_text = array($p->piece_text);
						}
					}
				} // foreach ($pages as $p)

				$page_count = 1;
				$filenames_orig = array();
				$filenames = array();
				echo "TOTAL PAGES: ".count($pages)."\n";
				echo "JPEG-2000 Library: ".$this->imagick_jp2_library()."\n";
				foreach ($pages as $p) {
					// Reworked this to make a filename from scratch, ignoring anything that we may have seen before.
					if ($this->send_orig_jp2 == 'yes' || $this->send_orig_jp2 == 'both') {
						$new_filebase_orig = $id.'_orig_'.sprintf("%04d", $page_count);
					}
					if ($this->send_orig_jp2 == 'no' || $this->send_orig_jp2 == 'both') {
						$new_filebase = $id.'_'.sprintf("%04d", $page_count);
					}
					$page_count++;

					if ((($this->send_orig_jp2 == 'yes' || $this->send_orig_jp2 == 'both') &&
								(!file_exists($jp2path_orig.'/'.$new_filebase_orig.'.jp2') || filesize($jp2path_orig.'/'.$new_filebase_orig.'.jp2') == 0))
						 || (($this->send_orig_jp2 == 'no' || $this->send_orig_jp2 == 'both') &&
								(!file_exists($jp2path.'/'.$new_filebase.'.jp2') || filesize($jp2path.'/'.$new_filebase.'.jp2') == 0))) {
						$start_time = microtime(true);
						// Convert to JP2
						echo "SCAN ".$p->scan_filename."...";
						if ($this->timing) { echo "TIMING (start): 0.0000\n"; }
						$preview = new Imagick($scanspath.'/'.$p->scan_filename);

						if ($this->timing) { echo "TIMING (open image): ".round((microtime(true) - $start_time), 5)."\n"; }

						// TIFFs can contain multiple images, we want the largest thing in there
						$this->CI->common->get_largest_image($preview);
						if ($this->timing) { echo "TIMING (find largest): ".round((microtime(true) - $start_time), 5)."\n"; }


						// Make sure the color profiles are correct, more or less.
						if ($this->timing) { echo "TIMING (add profile start): ".round((microtime(true) - $start_time), 5)."\n"; }

						// If this is a color image, we need to handle some color profile and conversions.
						$preview->stripImage();

						if ($preview->getImageType() != Imagick::IMGTYPE_GRAYSCALE) {
							// If not, then it's grayscale and we do nothing
							$icc_rgb1 = file_get_contents($this->cfg['base_directory'].'/inc/icc/AdobeRGB1998.icc');
							$preview->setImageProfile('icc', $icc_rgb1);
							if ($this->timing) { echo "TIMING (add profile Adobe): ".round((microtime(true) - $start_time), 5)."\n"; }

							$icc_rgb2 = file_get_contents($this->cfg['base_directory'].'/inc/icc/sRGB_IEC61966-2-1_black_scaled.icc');
							$preview->profileImage('icc', $icc_rgb2);
							if ($this->timing) { echo "TIMING (add profile sRGB): ".round((microtime(true) - $start_time), 5)."\n"; }
						}

						// Disable the alpha channel on the image. Internet Archive doesn't like it much at all.
						$preview->setImageMatte(false);

						// Embed Metadata into the JP2
						// IPTC data is not valid for JP2 files, but maybe it'll get carried along if ImageMagick is smart.
						// XMP (and others?) may be associated to the TIFF container, so we re-apply the profile, just to be safe
						// when we have multiple images in the TIFF file.
						$profiles = $preview->getImageProfiles('*', false); // get profiles
						$has_xmp = (array_search('xmp', $profiles) !== false); // we're interested if ICC profile(s) exist

						if ($has_xmp === true) {
							echo "SKIPPING the xmp profile, one already exists\n";
						} else {
							$preview->setImageProfile('xmp', $this->CI->book->xmp_xml());
							if ($this->timing) { echo "TIMING (add profile XMP): ".round((microtime(true) - $start_time), 5)."\n"; }
						}

						if ($this->send_orig_jp2 == 'yes' || $this->send_orig_jp2 == 'both') {
							$preview->setImageCompression(imagick::COMPRESSION_JPEG2000);
							$preview->setImageCompressionQuality(50);
							// Write the jp2 out to the local directory
							echo " created $new_filebase_orig".".jp2";
							$preview->setImageDepth(8);
							$preview->writeImage($jp2path_orig.'/'.$new_filebase_orig.'.jp2');
							if ($this->timing) { echo "TIMING (write): ".round((microtime(true) - $start_time), 5)."\n"; }
						}
						if ($this->send_orig_jp2 == 'no' || $this->send_orig_jp2 == 'both') {
							// If the images are IA-ready, we don't recompress. 
							if (!$this->CI->book->ia_ready_images || !preg_match("/\.jp[2f]$/", $p->scan_filename) ) {
								echo " (compressing) ";
								$preview->setImageCompression(imagick::COMPRESSION_JPEG2000);
								// If we got our images from a PDF, we use the highest quality we can
								$quality = 37;
								if ($this->imagick_jp2_library() == 'OpenJPEG') {
									$quality = 30;
								}

								if ($this->CI->book->get_metadata('from_pdf') == 'yes') {
									echo '(from PDF)';
									if (isset($this->cfg['jpeg2000_quality_pdf']) && preg_match('/^[0-9]+$/', $this->cfg['jpeg2000_quality_pdf'])) {
										$quality = $this->cfg['jpeg2000_quality_pdf'];
									} else {
										$quality = 50;
										if ($this->imagick_jp2_library() == 'OpenJPEG') {
											$quality = 32;
										}
									}
								} else {
									// Use the compression level from the config, if we have it
									if (isset($this->cfg['jpeg2000_quality']) && preg_match('/^[0-9]+$/', $this->cfg['jpeg2000_quality'])) {
										$quality = $this->cfg['jpeg2000_quality'];
									} else {
										$quality = 37;
										if ($this->imagick_jp2_library() == 'OpenJPEG') {
											$quality = 30;
										}
									}
								}
								// Allow an ultimate override from the item itself.
								$tempq = $this->CI->book->get_metadata('jpeg2000_quality');
								if ($tempq) {
								$quality = (int)$tempq;
								}
								$preview->setCompressionQuality($quality);
								$preview->setImageCompressionQuality($quality);
								echo " creating $new_filebase".".jp2 (Q=$quality)";
								$preview->setImageDepth(8);
								$preview->setOption('jp2:tilewidth','256');
								$preview->setOption('jp2:tileheight','256');
								$preview->writeImage($jp2path.'/'.$new_filebase.'.jp2');

								// If the image we just created is too small, we need to recompress it at a higher rate
								$tqual = $quality;
								$fs = filesize($jp2path.'/'.$new_filebase.'.jp2');
								while ($fs < 102400) {
									$tqual = $tqual + 2;
									if ($tqual >= 100) {
										break;
									}
									print " $fs is too small (Q=$tqual)";
									unlink($jp2path.'/'.$new_filebase.'.jp2');
									$preview->setCompressionQuality($tqual);
									$preview->setImageCompressionQuality($tqual);
									$preview->writeImage($jp2path.'/'.$new_filebase.'.jp2');
									$fs = filesize($jp2path.'/'.$new_filebase.'.jp2');
								}
					
								if ($this->timing) { echo "TIMING (write): ".round((microtime(true) - $start_time), 5)."\n"; }
							} else {
								// Write the jp2 out to the local directory
								echo " copied $new_filebase".".jp2";
								copy($scanspath.'/'.$p->scan_filename, $jp2path.'/'.$new_filebase.'.jp2');
								if ($this->timing) { echo "TIMING (copy): ".round((microtime(true) - $start_time), 5)."\n"; }
							}
						}
						if ($this->timing) { echo "TIMING (set compression): ".round((microtime(true) - $start_time), 5)."\n"; }


						echo "(".round((microtime(true) - $start_time), 3)." secs)\n";
					} // if ((($this->send_orig_jp2 == 'yes' || $this->send_orig_jp2 == 'both') && ...

					// Accumulate the filenames we want, which will naturally exclude any other junk
					// that might end up in the directory, such as OS X's .DS_Store files (ugh)
					if ($this->send_orig_jp2 == 'yes' || $this->send_orig_jp2 == 'both') {
						array_push($filenames_orig, $id.'_orig_jp2/'.$new_filebase_orig.'.jp2');
					}
					if ($this->send_orig_jp2 == 'no' || $this->send_orig_jp2 == 'both') {
						array_push($filenames, $id.'_jp2/'.$new_filebase.'.jp2');
					}
				} // foreach ($pages as $p)

				$this->CI->logging->log('book', 'debug', 'Exported JP2 files for Internet Archive.', $bc);

				// Export the TAR and/or ZIP files.
				if ($file == '' || $file == 'scans') {
					if (($this->send_orig_jp2 == 'yes' || $this->send_orig_jp2 == 'both') && (!file_exists($archive_file_orig) || !$id)) {
						// Create the TAR file
						$tar = new Archive_Tar($archive_file_orig); // name of archive
						chdir($basepath.'/Internet_archive/'.$id.'/');
						// We only add things that we are interested in, a list of space-separated filenames
						$tar->create($id.'_orig_jp2/');
						$this->CI->logging->log('book', 'debug', 'Created TAR file '.$id.'_orig_jp2.tar', $bc);
					}
					if (($this->send_orig_jp2 == 'no' || $this->send_orig_jp2 == 'both') && (!file_exists($archive_file) || !$id)) {
						// Check for and prevent 0 or 77 byte files from getting to IA
						foreach ($filenames as $fn) {
							$longfn = $basepath.'/Internet_archive/'.$id.'/'.$fn;
							if (filesize($longfn) < 100) {
								$message = "File for JP2 is too small. Will continue processing later. \n\n".
									"Identifier:    ".$bc."\n".
									"IA Identifier: ".$id."\n".
									"ZIP File: ".basename($archive_file)."\n".
									"File: ".basename($fn)."\n".
									"Size: ".filesize($longfn)."\n";
								$this->CI->common->email_admin($message);
								return;
							}
						}
						// Create the ZIP object
						$zip = new ZipArchive(); // name of archive
						if ($zip->open($archive_file, ZIPARCHIVE::CREATE) !== TRUE) {
							exit("cannot open <$archive_file>\n");
						}
						// Make sure we are in the right directory
						chdir($basepath.'/Internet_archive/'.$id.'/');
						// We only add things that we are interested in, in this case, the entire directory (files AND directory)
						$zip->addEmptyDir($id.'_jp2');
						foreach ($filenames as $fn) {
							$zip->addFile($fn);
						}
						// Close and save
						$zip->close();

						// Send a warning if the file is more than about 4 GB
						$size = filesize($archive_file);
						if ($size > 4000000000) {
							$message = "JP2 file is too large. This may cause trouble at IA. \n\n".
								"Identifier:    ".$bc."\n\n".
								"IA Identifier: ".$id."\n\n".
								"Filename: ".$archive_file."\n\n".
								"Size: ".$size."\n\n";
							$this->CI->common->email_admin($message);
						}
						$this->CI->logging->log('book', 'debug', 'Created ZIP file '.$id.'_jp2.zip', $bc);
					}
				} // if ($file == '' || $file == 'scans')

				if ($file == '' || $file == 'marc') {
					// Clean up leftover files that are now in the tar file
					// create the IDENTIFIER_marc.xml file
					$marc_data = $this->_create_marc_xml();
					if ($marc_data) {
						write_file($fullpath.'/'.$id.'_marc.xml', $marc_data);
						$this->CI->logging->log('book', 'debug', 'Created '.$id.'_marc.xml', $bc);
					} else {
						// Virtual Items have no MARC XML
						$this->CI->logging->log('book', 'debug', 'No MARC XML to create _marc.xml file.', $bc);
					}
				} // if ($file == '' || $file == 'marc')

				if ($file == '' || $file == 'scandata') {
					// create the IDENTIFIER_scandata.xml file
					write_file($fullpath.'/'.$id.'_scandata.xml', $this->_create_scandata_xml($id, $this->CI->book, $pages));
					$this->CI->logging->log('book', 'debug', 'Created '.$id.'_scandata.xml', $bc);
				}

				if ($file == '' || $file == 'creators') {
					// create the IDENTIFIER_bhlcreators.xml file
					write_file($fullpath.'/'.$id.'_bhlcreators.xml', $this->_create_creators_xml($id, $this->CI->book));
					$this->CI->logging->log('book', 'debug', 'Created '.$id.'_bhlcreators.xml', $bc);
				}

				// upload the files to internet archive
				if ($file == 'meta') {
					$old_metadata = $this->_get_ia_meta_xml($b, $id);
					// Fill in any blanks from the old metadata
					foreach (array_keys($old_metadata) as $k) {
						if (preg_match("/^x-archive-meta00/", $k)) {
							$l = preg_replace("/x-archive-meta00/", "x-archive-meta", $k);
							unset($metadata[$l]);
						}
						if (!isset($metadata[$k])) {
							$metadata[$k] = $old_metadata[$k];
						}
					}
					$cmd = $this->cfg['curl_exe'];
					$cmd .= ' --location';
					$cmd .= ' --header "authorization: LOW '.$this->access.':'.$this->secret.'"';
					$cmd .= ' --header "x-archive-ignore-preexisting-bucket:1"';
					foreach (array_keys($metadata) as $k) {
						if ($k != 'x-archive-meta-identifier') {
							$cmd .= ' --header "'.$k.':'.$metadata[$k].'"';
						}
					}
					$cmd .= ' --upload-file "'.$fullpath.'/'.$id.'_scandata.xml" "https://s3.us.archive.org/'.$id.'/'.$id.'_scandata.xml" 2>&1';
					echo "\n\n".$cmd."\n\n";

					// execute the CURL command and echo back any responses
					if (!$this->cfg['testing']) {
						$output = array();
						exec($cmd, $output, $ret);
						if (count($output)) {
							foreach ($output as $o) {
								echo $o."\n";
							}
						}
						if ($ret) {
							echo "ERROR!!! Return code = $ret";
							// If we had any sort of error from exec, we log what happened and set the status to error
							$out = '';
							foreach ($output as $o) {
								$out .= $o."\n";
							}
							$this->CI->logging->log('book', 'error', 'Call to CURL returned non-zero value (' & $ret & ') for uploading metadata. Output was:'."\n".$out, $bc);

							$message = "Error processing export.\n\n".
								"Identifier: {$bc}\n\n".
								"File: (metadata)\n\n".
								"Error Message:\nCall to CURL returned non-zero value ({$ret}).\nOutput was:\n\n{$out}\n\n";
							$this->CI->common->email_error($message);

							return;
						} // if ($ret)
					} else {
						echo "IN TEST MODE. NOT UPLOADING.\n\n";
					} // if (!$this->cfg['testing'])
				} // if ($file == 'meta')

				if ($file == '' || $file == 'scandata') {
					$cmd = $this->cfg['curl_exe'];
					$cmd .= ' --location';
					$cmd .= ' --header "authorization: LOW '.$this->access.':'.$this->secret.'"';
					$cmd .= ' --header "x-archive-auto-make-bucket:1"';
					$cmd .= ' --header "x-archive-size-hint:'.sprintf("%u", filesize($fullpath.'/'.$id.'_scandata.xml')).'"';
					$cmd .= ' --header "x-archive-queue-derive:0"';
					foreach (array_keys($metadata) as $k) {
						$cmd .= ' --header "'.$k.':'.$metadata[$k].'"';
					}
					$cmd .= ' --upload-file "'.$fullpath.'/'.$id.'_scandata.xml" "https://s3.us.archive.org/'.$id.'/'.$id.'_scandata.xml" 2>&1';
					echo "\n\n".$cmd."\n\n";

					// execute the CURL command and echo back any responses
					if (!$this->cfg['testing']) {
						$output = array();
						exec($cmd, $output, $ret);
						if (count($output)) {
							foreach ($output as $o) {
								echo $o."\n";
							}
						}
						if ($ret) {
							echo "ERROR!!! Return code = $ret";
							// If we had any sort of error from exec, we log what happened and set the status to error
							$out = '';
							foreach ($output as $o) {
								$out .= $o."\n";
							}
							$message = "Error processing export.\n\n".
								"Identifier: {$bc}\n\n".
								"File: {$id}_scandata.xml\n\n".
								"Error Message:\nCall to CURL returned non-zero value ({$ret}).\nOutput was:\n\n{$out}\n\n";
							$this->CI->common->email_error($message);
							if ($ret == 56 || $ret == 52) {
								$this->CI->logging->log('book', 'error', 'Call to CURL returned non-zero value (' & $ret & ') for scandata.xml. CONTINUING UPLOAD. Output was:'."\n".$out, $bc);
								return;
							} else {
								$this->CI->book->set_status('error');
								$this->CI->logging->log('book', 'error', 'Call to CURL returned non-zero value (' & $ret & ') for scandata.xml. Output was:'."\n".$out, $bc);
								return;
							}
						} // if ($ret)
					} else {
						echo "IN TEST MODE. NOT UPLOADING.\n\n";
					} // if (!$this->cfg['testing'])
				} // if ($file == '' || $file == 'scandata')

				if ($file == '' || $file == 'pdf') {
					// Gets the names of any PDF files that were used to upload pages.
					$pdf = $this->CI->book->get_metadata('pdf_source');
					if ($pdf) {

						$files = [];

						// Copies the file(s) to the export directory and renames them.
						if (is_array($pdf)) {
							$count = 1;
							foreach ($pdf as $p) {
								if (file_exists("{$this->cfg['data_directory']}/{$bc}/{$p}")){
									copy("{$this->cfg['data_directory']}/{$bc}/{$p}", "{$fullpath}/{$id}_orig_{$count}.pdf");
									$result = $this->create_zip(array("{$id}_orig_{$count}.pdf"), "{$id}_orig_pdf_{$count}.zip", $basepath.'/Internet_archive/'.$id.'/');
									$files[] = "{$id}_orig_pdf_{$count}.zip";
									$count++;
								}
							}
						} else {
							if (file_exists("{$this->cfg['data_directory']}/{$bc}/{$pdf}")){
								copy("{$this->cfg['data_directory']}/{$bc}/{$pdf}", "{$fullpath}/{$id}_orig.pdf");
								$result = $this->create_zip(array("{$id}_orig.pdf"), "{$id}_orig_pdf.zip", $basepath.'/Internet_archive/'.$id.'/');
								$files[] = "{$id}_orig_pdf.zip";
							}
						}

						// Uses cURL to upload to the Internet Archive.
						foreach ($files as $pdf) {
							$cmd = $this->cfg['curl_exe'];
							$cmd .= ' --location';
							$cmd .= ' --header "authorization: LOW '.$this->access.':'.$this->secret.'"';
							$cmd .= ' --header "x-archive-queue-derive:0"';
							$cmd .= ' --upload-file "'.$fullpath.'/'.$pdf.'" "https://s3.us.archive.org/'.$id.'/'.$pdf.'" 2>&1';
							echo "\n\n".$cmd."\n\n";

							if (!$this->cfg['testing']) {
								// execute the CURL command and echo back any responses
								$output = array();
								exec($cmd, $output, $ret);
								if (count($output)) {
									foreach ($output as $o) {
										echo $o."\n";
									}
								}
								if ($ret) {
									echo "ERROR!!! Return code = $ret";
									// If we had any sort of error from exec, we log what happened and set the status to error
									$out = '';
									foreach ($output as $o) {
										$out .= $o."\n";
									}
									$message = "Error processing export.\n\n".
										"Identifier: {$bc}\n\n".
										"File: {$pdf}\n\n".
										"Error Message:\nCall to CURL returned non-zero value ({$ret}).\nOutput was:\n\n{$out}\n\n";
									$this->CI->common->email_error($message);
									if ($ret == 56 || $ret == 52) {
										$this->CI->logging->log('book', 'error', 'Call to CURL returned non-zero value (' & $ret & ') for '.$pdf.'. CONTINUING UPLOAD. Output was:'."\n".$out, $bc);
										return;
									} else {
										$this->CI->book->set_status('error');
										$this->CI->logging->log('book', 'error', 'Call to CURL returned non-zero value (' & $ret & ') for '.$pdf.'. Output was:'."\n".$out, $bc);
										return;
									}
								}
							} else {
								echo "IN TEST MODE. NOT UPLOADING.\n\n";
							} // if (!$this->cfg['testing'])
						} // foreach ($files as $pdf)
					} // if ($pdf)
				} // if ($file == '' || $file == 'pdf')
				
				// Pause for 1 minute and to see if the bucket exists in IA. Then it's safe to continue...
				echo "Sleeping while we wait for IA to create the bucket...";
				$bucket_found = 0;
				for ($i = 1; $i <= 15; $i++) {
					if ($this->_bucket_exists($id)) {
						$bucket_found = 1;
						break;
					} else {
						sleep(60);
						echo "$i of 15 minutes...";
					}
				} // for ($i = 1; $i <= 15; $i++)
				if (!$bucket_found) {
					$this->CI->logging->log('book', 'error', 'Bucket at Internet Archive not created after 15 minutes. Will try again later.', $bc);
					$message = "Error processing export.\n\n".
						"Identifier:    ".$bc."\n\n".
						"IA Identifier: ".$id."\n\n".
						"Error Message: Bucket at Internet Archive not created after 15 minutes. Will try again later.\n".
						"Command: \n\n".$cmd."\n\n";
					$this->CI->common->email_error($message);
					continue;
				}
				echo "\n";

				// Virtual Items have no MARC XML
				if (($file == '' || $file == 'marc') && file_exists($fullpath.'/'.$id.'_marc.xml'))  {
					$cmd = $this->cfg['curl_exe'];
					$cmd .= ' --location';
					$cmd .= ' --header "authorization: LOW '.$this->access.':'.$this->secret.'"';
					$cmd .= ' --header "x-archive-queue-derive:0"';
					$cmd .= ' --upload-file "'.$fullpath.'/'.$id.'_marc.xml" "https://s3.us.archive.org/'.$id.'/'.$id.'_marc.xml" 2>&1';
					echo "\n\n".$cmd."\n\n";

					if (!$this->cfg['testing']) {
						// execute the CURL command and echo back any responses
						$output = array();
						exec($cmd, $output, $ret);
						if (count($output)) {
							foreach ($output as $o) {
								echo $o."\n";
							}
						}
						if ($ret) {
							echo "ERROR!!! Return code = $ret";
							// If we had any sort of error from exec, we log what happened and set the status to error
							$out = '';
							foreach ($output as $o) {
								$out .= $o."\n";
							}
							$message = "Error processing export.\n\n".
								"Identifier: {$bc}\n\n".
								"File: {$id}_marc.xml\n\n".
								"Error Message:\nCall to CURL returned non-zero value ({$ret}).\nOutput was:\n\n{$out}\n\n";
							$this->CI->common->email_error($message);
							if ($ret == 56 || $ret == 52) {
								$this->CI->logging->log('book', 'error', 'Call to CURL returned non-zero value (' & $ret & ') for marc.xml. CONTINUING UPLOAD. Output was:'."\n".$out, $bc);
								return;
							} else {
								$this->CI->book->set_status('error');
								$this->CI->logging->log('book', 'error', 'Call to CURL returned non-zero value (' & $ret & ') for marc.xml. Output was:'."\n".$out, $bc);
								return;
							}
						}
					} else {
						echo "IN TEST MODE. NOT UPLOADING.\n\n";
					} // if (!$this->cfg['testing'])
				} //if ($file == '' || $file == 'marc')

				if ($file == '' || $file == 'creators')  {
					$cmd = $this->cfg['curl_exe'];
					$cmd .= ' --location';
					$cmd .= ' --header "authorization: LOW '.$this->access.':'.$this->secret.'"';
					$cmd .= ' --header "x-archive-queue-derive:0"';
					$cmd .= ' --upload-file "'.$fullpath.'/'.$id.'_bhlcreators.xml" "https://s3.us.archive.org/'.$id.'/'.$id.'_bhlcreators.xml" 2>&1';
					echo "\n\n".$cmd."\n\n";

					if (!$this->cfg['testing']) {
						// execute the CURL command and echo back any responses
						$output = array();
						exec($cmd, $output, $ret);
						if (count($output)) {
							foreach ($output as $o) {
								echo $o."\n";
							}
						}
						if ($ret) {
							echo "ERROR!!! Return code = $ret";
							// If we had any sort of error from exec, we log what happened and set the status to error
							$out = '';
							foreach ($output as $o) {
								$out .= $o."\n";
							}
							$message = "Error processing export.\n\n".
								"Identifier: {$bc}\n\n".
								"File: {$id}_bhlcreators.xml\n\n".
								"Error Message:\nCall to CURL returned non-zero value ({$ret}).\nOutput was:\n\n{$out}\n\n";
							$this->CI->common->email_error($message);
							if ($ret == 56 || $ret == 52) {
								$this->CI->logging->log('book', 'error', 'Call to CURL returned non-zero value (' & $ret & ') for creators.xml. CONTINUING UPLOAD. Output was:'."\n".$out, $bc);
								return;
							} else {
								$this->CI->book->set_status('error');
								$this->CI->logging->log('book', 'error', 'Call to CURL returned non-zero value (' & $ret & ') for creators.xml. Output was:'."\n".$out, $bc);
								return;
							}
						}
					} else {
						echo "IN TEST MODE. NOT UPLOADING.\n\n";
					} // if (!$this->cfg['testing'])
				} //if ($file == '' || $file == 'creators')

				if ($file == '' || $file == 'scans') {
					// Upload the "processed" jp2 files first.
					if ($this->send_orig_jp2 == 'no' || $this->send_orig_jp2 == 'both') {
						$cmd = $this->cfg['curl_exe'];
						$cmd .= ' --location';
						$cmd .= ' --header "authorization: LOW '.$this->access.':'.$this->secret.'"';
						if ($this->send_orig_jp2 == 'yes' || $this->send_orig_jp2 == 'both') {
							$cmd .= ' --header "x-archive-queue-derive:0"';
						} else {
							$cmd .= ' --header "x-archive-queue-derive:1"';
						}
						$cmd .= ' --header "x-archive-size-hint:'.sprintf("%u", filesize($fullpath.'/'.$id.'_jp2.zip')).'"';
						$cmd .= ' --upload-file "'.$fullpath.'/'.$id.'_jp2.zip" "https://s3.us.archive.org/'.$id.'/'.$id.'_jp2.zip" 2>&1';
						echo "\n\n".$cmd."\n\n";

						if (!$this->cfg['testing']) {
							// execute the CURL command and echo back any responses
							$output = array();
							exec($cmd, $output, $ret);
							if (count($output)) {
								foreach ($output as $o) {
									echo $o."\n";
								}
							}
							if ($ret) {
								echo "ERROR!!! Return code = $ret";
								// If we had any sort of error from exec, we log what happened and set the status to error
								$out = '';
								foreach ($output as $o) {
								$out .= $o."\n";
								}
								$message = "Error processing export.\n\n".
								"Identifier: {$bc}\n\n".
								"File: {$id} - tar or ZIP\n\n".
								"Error Message:\nCall to CURL returned non-zero value ({$ret}).\nOutput was:\n\n{$out}\n\n";
								$this->CI->common->email_error($message);
								if ($ret == 56 || $ret == 52) {
								$this->CI->logging->log('book', 'error', 'Call to CURL returned non-zero value (' & $ret & ') for tar or ZIP file. CONTINUING UPLOAD. Output was:'."\n".$out, $bc);
								return;
								} else {
								$this->CI->book->set_status('error');
								$this->CI->logging->log('book', 'error', 'Call to CURL returned non-zero value (' & $ret & ') for tar or ZIP file. Output was:'."\n".$out, $bc);
								return;
								}
							}
						} else {
							echo "IN TEST MODE. NOT UPLOADING.\n\n";
						} // if (!$this->cfg['testing'])
					} // if ($this->send_orig_jp2 == 'no' || $this->send_orig_jp2 == 'both')

					// Upload the "original" jp2 files last. Why? If we upload the orig first,
					// IA might start creating the "processed" verisons
					if ($this->send_orig_jp2 == 'yes' || $this->send_orig_jp2 == 'both') {
						$cmd = $this->cfg['curl_exe'];
						$cmd .= ' --location';
						$cmd .= ' --header "authorization: LOW '.$this->access.':'.$this->secret.'"';
						$cmd .= ' --header "x-archive-queue-derive:1"';
						$cmd .= ' --header "x-archive-size-hint:'.sprintf("%u", filesize($fullpath.'/'.$id.'_orig_jp2.tar')).'"';
						$cmd .= ' --upload-file "'.$fullpath.'/'.$id.'_orig_jp2.tar" "https://s3.us.archive.org/'.$id.'/'.$id.'_orig_jp2.tar" 2>&1';
						echo "\n\n".$cmd."\n\n";

						if (!$this->cfg['testing']) {
							// execute the CURL command and echo back any responses
							$output = array();
							exec($cmd, $output, $ret);
							if (count($output)) {
								foreach ($output as $o) {
									echo $o."\n";
								}
							}
							if ($ret) {
								echo "ERROR!!! Return code = $ret";
								// If we had any sort of error from exec, we log what happened and set the status to error
								$out = '';
								foreach ($output as $o) {
								$out .= $o."\n";
								}
								$message = "Error processing export.\n\n".
								"Identifier: {$bc}\n\n".
								"File: {$id} - tar or ZIP (2)\n\n".
								"Error Message:\nCall to CURL returned non-zero value ({$ret}).\nOutput was:\n\n{$out}\n\n";
								$this->CI->common->email_error($message);
								if ($ret == 56 || $ret == 52) {
								$this->CI->logging->log('book', 'error', 'Call to CURL returned non-zero value (' & $ret & ') for tar or ZIP file (2). CONTINUING UPLOAD. Output was:'."\n".$out, $bc);
								return;
								} else {
								$this->CI->book->set_status('error');
								$this->CI->logging->log('book', 'error', 'Call to CURL returned non-zero value (' & $ret & ') for tar or ZIP file (2). Output was:'."\n".$out, $bc);
								return;
								}
							}
						} else {
							echo "IN TEST MODE. NOT UPLOADING.\n\n";
						} // if (!$this->cfg['testing'])
					} // if ($this->send_orig_jp2 == 'yes' || $this->send_orig_jp2 == 'both')
				} // if ($file == '' || $file == 'scans')
				
				// If we got this far, we were completely successful. Yay!

				// TODO Update uploaded date field in the item table
				$this->CI->book->set_export_status('uploaded', $force);
				$this->CI->logging->log('book', 'info', 'Item successfully uploaded to internet archive.', $bc);
				$this->CI->logging->log('access', 'info', 'Item with barcode '.$bc.' uploaded to internet archive.');
				$this->CI->book->set_metadata('ia_identifier', $id);
				$this->CI->book->update();
				
				if ($id) {
					echo 'The item with id #'.$b->id.' was successfully uploaded to internet archive.'."\n";
				}
			} catch (Exception $e) {
				$backtrace = debug_backtrace();
				$message = "Error processing export.\n\n".
					"Identifier: ".$bc."\n\n".
					"Error Message:\n    ".$e->getMessage()."\n\n".
					"Stack Trace:\n\n".
					$e->getTraceAsString();
				$this->CI->common->email_error($message);
        print "\n\nError Processing. Email sent to administrator.\n";
			} // try-catch
			
			// Clear the access and secret just so we don't accidentally upload things incorrectly.
			$this->access = '';
			$this->secret = '';
			
      if (!$sent_id) {
        // Refresh the list of books. They could have changed while we were doing stuff.
        // But only if we didn't get a specific ID to work on.
        $books = $this->_get_books('NULL');
      }
    } // while (count($books))
	} // function upload($args)

	/* ----------------------------
	 * Function: verify()
	 *
	 * Parameters:
	 *    $args - An array of items passed from the command line (or URL)
	 *            that are specific to this module. The calling export
	 *            controller simply passes these in as the were received.
	 * ---------------------------- */
	function verify_uploaded($args) {
		$sent_id = (count($args) >= 1 ? $args[0] : null);

		// Find those items that need to be verified or use the ID we were given
		if ($sent_id) {
			$books = $this->CI->book->search('barcode', $sent_id, 'date_review_end');
			if (count($books) == 0) {
				$books = $this->CI->book->search('id', $sent_id, 'date_review_end');
			}
		} else {
			$books = $this->_get_books('uploaded');
		}


		// Cycle through these items
		foreach ($books as $b) {
			// Load the book
			print "Verifying Upload ".$b->barcode."...\n";
			$this->CI->book->load($b->barcode);

			// We check the ID, but we really REALLY should have one.
			$metadata = $this->_get_metadata();
			$id = null;
			if ($metadata) {
				$id = $this->identifier($b, $metadata);
				if (!$id) { print "No Identifier can be found\n"; return; }
				$metadata['x-archive-meta-identifier'] = $id;
			} else {
				$message = "Error processing export.\n\n".
					"Identifier:    ".$b->barcode."\n\n".
					"IA Identifier: ".$id."\n\n".
					"Error Message: Could not get metadata for item with barcode ".$b->barcode.". Check the MARC data.\n";
				$this->CI->common->email_error($message);
				continue;				
			}

			$status = $this->CI->book->get_export_status('Internet_archive');

			if ($id) {
				if ($status != 'uploaded') {
					echo '(verify upload) The item with id #'.$b->id.' is not marked as uploaded and cannot be verified. (status is '.$status.')'."\n";
					continue;
				}
			}

			if ($id == '' && $status) {
				$this->CI->book->set_status('error');
				$this->CI->logging->log('book', 'error', 'During Verify Upload the item does not have an identifier.', $b->barcode);
				continue;
			}
			// Log that we are checking the status at Internet Archive
			$this->CI->logging->log('book', 'info', 'Checking for status of item at Internet Archive.', $b->barcode);

			// Get a list of what was uploaded
			$urls = $this->_get_derivative_urls($id);

			// Is this a virtual item
			$is_virtual_item = false;
			if ($this->CI->book->get_metadata('bhl_virtual_titleid') || $this->CI->book->get_metadata('bhl_virtual_volume')) {
				$is_virtual_item = true;
			}

			// We check a list of all files to determine if they are there. If they
			// are, then the item was uploaded and processed successfully.
			$verified = 1;
			$error = '';
			foreach ($this->required_extensions as $ext) {
				// If this is a virtual item, we don't check for _marc.xml
				if ($ext == '_marc.xml' && $is_virtual_item) { continue; }

				if (!in_array($id.$ext, $urls[1])) {
					if (!$error) {
						$error = '('.$ext.' file not found)';
					}
					$verified = 0;
					continue;
				}
			}

			if ($verified == 1) {
				// Mark the book as verified
				try {
					$this->CI->book->set_export_status('verified_upload');
					$this->CI->logging->log('book', 'info', 'Item successfully verified at internet archive.', $b->barcode);
					$this->CI->logging->log('access', 'info', 'Item with barcode '.$b->barcode.' verified at internet archive.');
				} catch (Exception $e) {
					// Do nothing.
				}
			} else {
				$this->CI->logging->log('book', 'info', 'Item NOT verified at internet archive ('.$id.'). '.$error, $b->barcode);
				$this->CI->logging->log('access', 'info', 'Item with barcode '.$b->barcode.' ('.$id.') NOT verified at internet archive. '.$error);
				// Clear the upload status so we will try again.
				$this->CI->db->where('item_id', $this->CI->book->id);
				$this->CI->db->delete('item_export_status');
			}
		}
	}

	function verify_derived($args) {
		$sent_id = (count($args) >= 1 ? $args[0] : null);

		// Find those items that need to be verified or use the ID we were given
		if ($sent_id) {
			$books = $this->CI->book->search('barcode', $sent_id, 'date_review_end');
			if (count($books) == 0) {
				$books = $this->CI->book->search('id', $sent_id, 'date_review_end');
			}
		} else {
			$books = $this->_get_books('verified_upload');
		}

		// Cycle through these items
		foreach ($books as $b) {
			print "Verifying Derive ".$b->barcode."...\n";
			$this->CI->book->load($b->barcode);

			// We check the ID, but we really REALLY should have one.
			$metadata = $this->_get_metadata();
			$id = null;
			if ($metadata) {
				$id = $this->identifier($b, $metadata);
				if (!$id) { print "No Identifier can be found\n"; return; }
				$metadata['x-archive-meta-identifier'] = $id;
			} else {
				$message = "Error processing export.\n\n".
					"Identifier:    ".$b->barcode."\n\n".
					"IA Identifier: ".$id."\n\n".
					"Error Message: Could not get metadata for item with barcode ".$b->barcode.". Check the MARC data.\n";
				$this->CI->common->email_error($message);
				continue;				
			}

			$status = $this->CI->book->get_export_status('Internet_archive');
			if ($id) {
				if ($status != 'verified_upload') {
					echo '(verify derive) The item with id #'.$b->id.' is not marked as verified for upload and cannot be verified for derivation. (status is '.$status.')'."\n";
					continue;
				}
			}

			if ($id == '' && $status) {
				$this->CI->book->set_status('error');
				$this->CI->logging->log('book', 'error', 'During Verify Derived the item does not have an identifier.', $b->barcode);
				continue;
			}
			// Log that we are checking the status at Internet Archive
			$this->CI->logging->log('book', 'info', 'Checking for status of item at Internet Archive.', $b->barcode);

			// Get a list of what was uploaded
			$urls = $this->_get_derivative_urls($id);

			// We check a list of all files to determine if they are there. If they
			// are, then the item was uploaded and processed successfully.
			$verified = 1;
			$error = '';
			foreach ($this->download_extensions as $ext) {
				if (!in_array($id.$ext, $urls[1])) {
					if (!$error) {
						$error = '('.$ext.' file not found)';
					}
					$verified = 0;
					continue;
				}
			}

			if ($verified == 1) {
				// Mark the book as verified
				try {
					$this->CI->book->set_export_status('verified_derive');
					$this->CI->logging->log('book', 'info', 'Item successfully verified at internet archive.', $b->barcode);
					$this->CI->logging->log('access', 'info', 'Item with barcode '.$b->barcode.' verified at internet archive.');
				} catch (Exception $e) {
					// Do nothing.
				}
			} else {
				$this->CI->logging->log('book', 'info', 'Item NOT verified at internet archive ('.$id.'). '.$error, $b->barcode);
				$this->CI->logging->log('access', 'info', 'Item with barcode '.$b->barcode.' ('.$id.') NOT verified at internet archive. '.$error);

			}
			if ($id && count($args) >= 1 && $verified) {
				echo 'The item with id #'.$b->id.' was successfully verified.'."\n";
			}
			if ($id && count($args) >= 1 && !$verified) {
				echo 'The item with id #'.$b->id.' was NOT verified. '.$error."\n";
			}
		}
	}

	/* ----------------------------
	 * Function: harvest($args)
	 *
	 * Parameters:
	 *    $args - An array of items passed from the command line (or URL)
	 *            that are specific to this module. The calling export
	 *            controller simply passes these in as the were received.
	 *
	 * Copy back from the internet archive anything that they may have created that
	 * we would be interested in.
	 * ---------------------------- */
	function harvest($args) {

		$sent_id = (count($args) >= 1 ? $args[0] : null);

		// Find those items that need to be harvested or use the ID we were given
		if ($sent_id) {
			$books = $this->CI->book->search('barcode', $sent_id, 'id');
			if (count($books) == 0) {
				$books = $this->CI->book->search('id', $sent_id, 'id');
			}
		} else {
			$books = $this->_get_books('verified_derive');
		}

		// Cycle through these items
		foreach ($books as $b) {
			print "Harvesting ".$b->barcode."...\n";
			$this->CI->book->load($b->barcode);

			// We check the ID, but we really REALLY should have one.
			$metadata = $this->_get_metadata();
			$id = null;
			if ($metadata) {
				$id = $this->identifier($b, $metadata);
				if (!$id) { print "No Identifier can be found\n"; return; }
				$metadata['x-archive-meta-identifier'] = $id;
			} else { 
				$message = "Error processing export.\n\n".
					"Identifier:    ".$b->barcode."\n\n".
					"IA Identifier: ".$id."\n\n".
					"Error Message: Could not get metadata for item with barcode ".$b->barcode.". Check the MARC data.\n";
				$this->CI->common->email_error($message);
				continue;				
			}

			$status = $this->CI->book->get_export_status('Internet_archive');
			if ($id) {
				if ($status != 'verified_derive') {
					echo '(harvesting) The item with id #'.$b->id.' is not marked as verified_derive and cannot be harvested. (status is '.$status.')'."\n";
					continue;
				}
			}

			if ($id == '' && $status) {
				$this->CI->book->set_status('error');
				$this->CI->logging->log('book', 'error', 'During Harvest the item does not have an identifier.', $b->barcode);
				continue;
			}

			// Log that we are checking the status at Internet Archive
			$this->CI->logging->log('book', 'info', 'Downloading derivatives from Internet Archive.', $b->barcode);

			// Get a list of what was uploaded
			$urls = $this->_get_derivative_urls($id);

			// Did we actually get what we were hoping for?
			if (count($urls[1]) > 1) {

				// Load the book
				$this->CI->book->load($b->barcode);
				$path = $this->cfg['base_directory'].'/books/'.$b->barcode.'/';

				// Keep track of whether or not we had trouble downloading one or more of the files
				$error = false;

				// Yes, redundant, but still. In testing this was a problem, so let's handle it gracefully.
				if (file_exists($path)) {

					// Cycle through the extensions we want to download
					foreach ($this->download_extensions as $e) {

						// Make sure that it's available to download.
						if (in_array($id.$e, $urls[1])) {

							// Save the data from the URL to the file. We use some broad error trapping here, just because.
							try {
								file_put_contents($path.$b->barcode.$e, fopen($urls[0].'/'.$id.$e, 'r'));
							} catch (Exception $e) {
								// Something horrible went wrong, log it and let's have a human look at the book.
								$this->CI->logging->log('book', 'error', 'Unable to save derivarive file: '.$b->barcode.$e, $b->barcode);
								$this->CI->logging->log('error', 'debug', 'Item with barcode '.$b->barcode.' cannot be harvested from Internet Archive. File: '.$b->barcode.$e.' Message: '. $e->getMessage());
								$error = true;
							}
							$this->CI->logging->log('book', 'info', 'Saved derivative file: '.$b->barcode.$e, $b->barcode);

						} else {
							// Couldn't find the derivative. What gives? Let's set an error here, too, and have a human review the book.
							$this->CI->logging->log('book', 'error', 'Derivative file not found at Internet Archive: '.$id.$e, $b->barcode);
							$this->CI->logging->log('error', 'debug', 'Item with barcode '.$b->barcode.' cannot be harvested from Internet Archive. Derivative file not found at Internet Archive: '.$id.$e);
							$error = true;
						}
					}
				} else {
					// The path doesn't exist. This is really bad. Let's log the error and not look at this book anymore.
					$this->CI->logging->log('book', 'error', 'Cannot save derivatives. Path does not exist: '.$path, $b->barcode);
					$this->CI->logging->log('error', 'debug', 'Item with barcode '.$b->barcode.' cannot be harvested from Internet Archive. Path does not exist: '.$path);
					$error = true;
				}

				// Handle any error conditions,
				if ($error) {
					$this->CI->logging->log('book', 'info', 'Derivatives NOT successfully downloaded from Internet archive. Will try again next time.', $b->barcode);
					$this->CI->logging->log('access', 'info', 'Item with barcode '.$b->barcode.' NOT harvested from internet archive. Will try again next time.');
				} else {
					// Success!
					try {
						$this->CI->book->set_export_status('completed');
						$this->CI->logging->log('book', 'info', 'Derivatives successfully downloaded from Internet archive.', $b->barcode);
						$this->CI->logging->log('access', 'info', 'Item with barcode '.$b->barcode.' harvested from internet archive.');

						// Should we purge the IA items when we're done?
						if (isset($this->cfg['purge_ia_deriatives'])) {
							if ($this->cfg['purge_ia_deriatives']) {
								echo 'The purging IA export directory '.$id."\n";
								$cmd = 'rm -fr '.$this->cfg['data_directory'].'/import_export/Internet_archive/'.$id;
							}
						}

					} catch (Exception $e) {
						// Do nothing.
					}
				}
			} else {
				// We didn't get any URLs, so we'll log this, but we wont set an error
				$this->CI->logging->log('book', 'error', 'Did not get any URLs for ID '.$id.' We will try again on the next scheduled harvest processing.', $b->barcode);

			}
			if ($id) {
				echo 'The item with id #'.$b->id.' was successfully harvested.'."\n";
			}
		}

	}

	/* ----------------------------
	 * Function: missing()
	 *
	 * Parameters:
	 *    NONE
	 *
	 * Submits missing pages for export. If the export module doesn't accept
	 * missing pages, then this function should do nothing but return true.
	 * ----------------------------  */
	function missing() {
		// 7.	Update the Export Upload procedure to:
		// a.	If the item's date uploaded field has a value then we need to send up only the changed pages.
		//      This will be different for each export module.
		// b.	Update the system to have a method of saving a persistent data for every export module and
		//      independent of each. The form of the table would be similar to that of the metadata table and
		//      would be loaded and made available automatically to the Export Module.
		// TODO: This really needs to be addressed better.
	}
	
	/* ----------------------------
	 * Function: _create_segments_xml()
	 *
	 * Parameters:
	 *    $id: The ID of the item as determined earlier
	 *    $book: A book object
	 *    $pages: The pages from the book (assume they were gathered earlier)
	 *
	 * Returns the XML for the segments.xml file. Does not create the file.
	 * This is specific to Internet Archive but is left here as a reminder.
	 * This should call Book.get_item_metadata().
	 * ---------------------------- */
	function _create_segments_xml($id, $book, $pages) {
		// This should not be used yet. Return empty element.
		return '<bhlSegmentData></bhlSegmentData>';

		$cfg = $this->CI->config->item('macaw');
		if(!in_array('BHL_Segments', $cfg['metadata_modules'])) {
			return NULL;
		}
		
		$segment_genres = [
			1 => 'Article',
			2 => 'Book',
			3 => 'BookItem',
			4 => 'Chapter',
			8 => 'Conference',
			6 => 'Issue',
			5 => 'Journal',
			14 => 'Letter',
			9 => 'Preprint',
			7 => 'Proceeding',
			13 => 'Thesis',
			11 => 'Treatment',
			10 => 'Unknown'
		];
			
		$segment_identifiers = [
			6 => 'Abbreviation',
			31 => 'BioLib.cz',
			16 => 'BioStor',
			7 => 'BPH',
			20 => 'Catalogue of Life',
			10 => 'CODEN',
			9 => 'DDC',
			5 => 'DLC',
			18 => 'EOL',
			30 => 'EUNIS',
			28 => 'GBIF Taxonomic Backbone',
			19 => 'GNI',
			13 => 'GPO',
			24 => 'Index Fungorum',
			34 => 'Index to Organism Names',
			26 => 'Interim Reg. of Marine/Nonmarine Genera',
			3 => 'ISBN',
			2 => 'ISSN',
			22 => 'ITIS',
			36 => 'JSTOR',
			14 => 'MARC001',
			12 => 'NAL',
			17 => 'NameBank',
			23 => 'NCBI',
			11 => 'NLM',
			35 => 'OAI',
			1 => 'OCLC',
			37 => 'Soulsby',
			33 => 'The International Plant Names Index',
			8 => 'TL2',
			32 => 'Tropicos',
			25 => 'Union 4',
			15 => 'VIAF',
			21 => 'Wikispecies',
			4 => 'WonderFetch',
			27 => 'WoRMS',
			29 => 'ZooBank'
		];
			
		$segment_languages = [
			'aar' => 'Afar',
			'abk' => 'Abkhaz',
			'ace' => 'Achinese',
			'ach' => 'Acoli',
			'ada' => 'Adangme',
			'ady' => 'Adygei',
			'afa' => 'Afroasiatic (Other)',
			'afh' => 'Afrihili (Artificial language)',
			'afr' => 'Afrikaans',
			'ain' => 'Ainu',
			'-ajm' => 'Aljamía',
			'aka' => 'Akan',
			'akk' => 'Akkadian',
			'alb' => 'Albanian',
			'ale' => 'Aleut',
			'alg' => 'Algonquian (Other)',
			'alt' => 'Altai',
			'amh' => 'Amharic',
			'ang' => 'English, Old (ca. 450-1100)',
			'anp' => 'Angika',
			'apa' => 'Apache languages',
			'ara' => 'Arabic',
			'arc' => 'Aramaic',
			'arg' => 'Aragonese',
			'arm' => 'Armenian',
			'arn' => 'Mapuche',
			'arp' => 'Arapaho',
			'art' => 'Artificial (Other)',
			'arw' => 'Arawak',
			'asm' => 'Assamese',
			'ast' => 'Bable',
			'ath' => 'Athapascan (Other)',
			'aus' => 'Australian languages',
			'ava' => 'Avaric',
			'ave' => 'Avestan',
			'awa' => 'Awadhi',
			'aym' => 'Aymara',
			'aze' => 'Azerbaijani',
			'bad' => 'Banda languages',
			'bai' => 'Bamileke languages',
			'bak' => 'Bashkir',
			'bal' => 'Baluchi',
			'bam' => 'Bambara',
			'ban' => 'Balinese',
			'baq' => 'Basque',
			'bas' => 'Basa',
			'bat' => 'Baltic (Other)',
			'bej' => 'Beja',
			'bel' => 'Belarusian',
			'bem' => 'Bemba',
			'ben' => 'Bengali',
			'ber' => 'Berber (Other)',
			'bho' => 'Bhojpuri',
			'bih' => 'Bihari (Other)',
			'bik' => 'Bikol',
			'bin' => 'Edo',
			'bis' => 'Bislama',
			'bla' => 'Siksika',
			'bnt' => 'Bantu (Other)',
			'bos' => 'Bosnian',
			'bra' => 'Braj',
			'bre' => 'Breton',
			'btk' => 'Batak',
			'bua' => 'Buriat',
			'bug' => 'Bugis',
			'bul' => 'Bulgarian',
			'bur' => 'Burmese',
			'byn' => 'Bilin',
			'cad' => 'Caddo',
			'cai' => 'Central American Indian (Other)',
			'-cam' => 'Khmer',
			'car' => 'Carib',
			'cat' => 'Catalan',
			'cau' => 'Caucasian (Other)',
			'ceb' => 'Cebuano',
			'cel' => 'Celtic (Other)',
			'cha' => 'Chamorro',
			'chb' => 'Chibcha',
			'che' => 'Chechen',
			'chg' => 'Chagatai',
			'chi' => 'Chinese',
			'chk' => 'Chuukese',
			'chm' => 'Mari',
			'chn' => 'Chinook jargon',
			'cho' => 'Choctaw',
			'chp' => 'Chipewyan',
			'chr' => 'Cherokee',
			'chu' => 'Church Slavic',
			'chv' => 'Chuvash',
			'chy' => 'Cheyenne',
			'cmc' => 'Chamic languages',
			'cop' => 'Coptic',
			'cor' => 'Cornish',
			'cos' => 'Corsican',
			'cpe' => 'Creoles and Pidgins, English-based (Other)',
			'cpf' => 'Creoles and Pidgins, French-based (Other)',
			'cpp' => 'Creoles and Pidgins, Portuguese-based (Other)',
			'cre' => 'Cree',
			'crh' => 'Crimean Tatar',
			'crp' => 'Creoles and Pidgins (Other)',
			'csb' => 'Kashubian',
			'cus' => 'Cushitic (Other)',
			'cze' => 'Czech',
			'dak' => 'Dakota',
			'dan' => 'Danish',
			'dar' => 'Dargwa',
			'day' => 'Dayak',
			'del' => 'Delaware',
			'den' => 'Slavey',
			'dgr' => 'Dogrib',
			'din' => 'Dinka',
			'div' => 'Divehi',
			'doi' => 'Dogri',
			'dra' => 'Dravidian (Other)',
			'dsb' => 'Lower Sorbian',
			'dua' => 'Duala',
			'dum' => 'Dutch, Middle (ca. 1050-1350)',
			'dut' => 'Dutch',
			'dyu' => 'Dyula',
			'dzo' => 'Dzongkha',
			'efi' => 'Efik',
			'egy' => 'Egyptian',
			'eka' => 'Ekajuk',
			'elx' => 'Elamite',
			'eng' => 'English',
			'enm' => 'English, Middle (1100-1500)',
			'epo' => 'Esperanto',
			'-esk' => 'Eskimo languages',
			'-esp' => 'Esperanto',
			'est' => 'Estonian',
			'-eth' => 'Ethiopic',
			'ewe' => 'Ewe',
			'ewo' => 'Ewondo',
			'fan' => 'Fang',
			'fao' => 'Faroese',
			'-far' => 'Faroese',
			'fat' => 'Fanti',
			'fij' => 'Fijian',
			'fil' => 'Filipino',
			'fin' => 'Finnish',
			'fiu' => 'Finno-Ugrian (Other)',
			'fon' => 'Fon',
			'fre' => 'French',
			'-fri' => 'Frisian',
			'frm' => 'French, Middle (ca. 1300-1600)',
			'fro' => 'French, Old (ca. 842-1300)',
			'frr' => 'North Frisian',
			'frs' => 'East Frisian',
			'fry' => 'Frisian',
			'ful' => 'Fula',
			'fur' => 'Friulian',
			'gaa' => 'Gã',
			'-gae' => 'Scottish Gaelix',
			'-gag' => 'Galician',
			'-gal' => 'Oromo',
			'gay' => 'Gayo',
			'gba' => 'Gbaya',
			'gem' => 'Germanic (Other)',
			'geo' => 'Georgian',
			'ger' => 'German',
			'gez' => 'Ethiopic',
			'gil' => 'Gilbertese',
			'gla' => 'Scottish Gaelic',
			'gle' => 'Irish',
			'glg' => 'Galician',
			'glv' => 'Manx',
			'gmh' => 'German, Middle High (ca. 1050-1500)',
			'goh' => 'German, Old High (ca. 750-1050)',
			'gon' => 'Gondi',
			'gor' => 'Gorontalo',
			'got' => 'Gothic',
			'grb' => 'Grebo',
			'grc' => 'Greek, Ancient (to 1453)',
			'gre' => 'Greek, Modern (1453-)',
			'grn' => 'Guarani',
			'gsw' => 'Swiss German',
			'-gua' => 'Guarani',
			'guj' => 'Gujarati',
			'gwi' => "Gwich'in",
			'hai' => 'Haida',
			'hat' => 'Haitian French Creole',
			'hau' => 'Hausa',
			'haw' => 'Hawaiian',
			'heb' => 'Hebrew',
			'her' => 'Herero',
			'hil' => 'Hiligaynon',
			'him' => 'Western Pahari languages',
			'hin' => 'Hindi',
			'hit' => 'Hittite',
			'hmn' => 'Hmong',
			'hmo' => 'Hiri Motu',
			'hrv' => 'Croatian',
			'hsb' => 'Upper Sorbian',
			'hun' => 'Hungarian',
			'hup' => 'Hupa',
			'iba' => 'Iban',
			'ibo' => 'Igbo',
			'ice' => 'Icelandic',
			'ido' => 'Ido',
			'iii' => 'Sichuan Yi',
			'ijo' => 'Ijo',
			'iku' => 'Inuktitut',
			'ile' => 'Interlingue',
			'ilo' => 'Iloko',
			'ina' => 'Interlingua (International Auxiliary Language Association)',
			'inc' => 'Indic (Other)',
			'ind' => 'Indonesian',
			'ine' => 'Indo-European (Other)',
			'inh' => 'Ingush',
			'-int' => 'Interlingua (International Auxiliary Language Association)',
			'ipk' => 'Inupiaq',
			'ira' => 'Iranian (Other)',
			'-iri' => 'Irish',
			'iro' => 'Iroquoian (Other)',
			'ita' => 'Italian',
			'jav' => 'Javanese',
			'jbo' => 'Lojban (Artificial language)',
			'jpn' => 'Japanese',
			'jpr' => 'Judeo-Persian',
			'jrb' => 'Judeo-Arabic',
			'kaa' => 'Kara-Kalpak',
			'kab' => 'Kabyle',
			'kac' => 'Kachin',
			'kal' => 'Kalâtdlisut',
			'kam' => 'Kamba',
			'kan' => 'Kannada',
			'kar' => 'Karen languages',
			'kas' => 'Kashmiri',
			'kau' => 'Kanuri',
			'kaw' => 'Kawi',
			'kaz' => 'Kazakh',
			'kbd' => 'Kabardian',
			'kha' => 'Khasi',
			'khi' => 'Khoisan (Other)',
			'khm' => 'Khmer',
			'kho' => 'Khotanese',
			'kik' => 'Kikuyu',
			'kin' => 'Kinyarwanda',
			'kir' => 'Kyrgyz',
			'kmb' => 'Kimbundu',
			'kok' => 'Konkani',
			'kom' => 'Komi',
			'kon' => 'Kongo',
			'kor' => 'Korean',
			'kos' => 'Kosraean',
			'kpe' => 'Kpelle',
			'krc' => 'Karachay-Balkar',
			'krl' => 'Karelian',
			'kro' => 'Kru (Other)',
			'kru' => 'Kurukh',
			'kua' => 'Kuanyama',
			'kum' => 'Kumyk',
			'kur' => 'Kurdish',
			'-kus' => 'Kusaie',
			'kut' => 'Kootenai',
			'lad' => 'Ladino',
			'lah' => 'Lahndā',
			'lam' => 'Lamba (Zambia and Congo)',
			'-lan' => 'Occitan (post 1500)',
			'lao' => 'Lao',
			'-lap' => 'Sami',
			'lat' => 'Latin',
			'lav' => 'Latvian',
			'lez' => 'Lezgian',
			'lim' => 'Limburgish',
			'lin' => 'Lingala',
			'lit' => 'Lithuanian',
			'lol' => 'Mongo-Nkundu',
			'loz' => 'Lozi',
			'ltz' => 'Luxembourgish',
			'lua' => 'Luba-Lulua',
			'lub' => 'Luba-Katanga',
			'lug' => 'Ganda',
			'lui' => 'Luiseño',
			'lun' => 'Lunda',
			'luo' => 'Luo (Kenya and Tanzania)',
			'lus' => 'Lushai',
			'mac' => 'Macedonian',
			'mad' => 'Madurese',
			'mag' => 'Magahi',
			'mah' => 'Marshallese',
			'mai' => 'Maithili',
			'mak' => 'Makasar',
			'mal' => 'Malayalam',
			'man' => 'Mandingo',
			'mao' => 'Maori',
			'map' => 'Austronesian (Other)',
			'mar' => 'Marathi',
			'mas' => 'Maasai',
			'-max' => 'Manx',
			'may' => 'Malay',
			'mdf' => 'Moksha',
			'mdr' => 'Mandar',
			'men' => 'Mende',
			'mga' => 'Irish, Middle (ca. 1100-1550)',
			'mic' => 'Micmac',
			'min' => 'Minangkabau',
			'mis' => 'Miscellaneous languages',
			'mkh' => 'Mon-Khmer (Other)',
			'-mla' => 'Malagasy',
			'mlg' => 'Malagasy',
			'mlt' => 'Maltese',
			'mnc' => 'Manchu',
			'mni' => 'Manipuri',
			'mno' => 'Manobo languages',
			'moh' => 'Mohawk',
			'-mol' => 'Moldavian',
			'mon' => 'Mongolian',
			'mos' => 'Mooré',
			'mul' => 'Multiple languages',
			'mun' => 'Munda (Other)',
			'mus' => 'Creek',
			'mwl' => 'Mirandese',
			'mwr' => 'Marwari',
			'myn' => 'Mayan languages',
			'myv' => 'Erzya',
			'nah' => 'Nahuatl',
			'nai' => 'North American Indian (Other)',
			'nap' => 'Neapolitan Italian',
			'nau' => 'Nauru',
			'nav' => 'Navajo',
			'nbl' => 'Ndebele (South Africa)',
			'nde' => 'Ndebele (Zimbabwe)',
			'ndo' => 'Ndonga',
			'nds' => 'Low German',
			'nep' => 'Nepali',
			'new' => 'Newari',
			'nia' => 'Nias',
			'nic' => 'Niger-Kordofanian (Other)',
			'niu' => 'Niuean',
			'nno' => 'Norwegian (Nynorsk)',
			'nob' => 'Norwegian (Bokmål)',
			'nog' => 'Nogai',
			'non' => 'Old Norse',
			'nor' => 'Norwegian',
			'nqo' => "N'Ko",
			'nso' => 'Northern Sotho',
			'nub' => 'Nubian languages',
			'nwc' => 'Newari, Old',
			'nya' => 'Nyanja',
			'nym' => 'Nyamwezi',
			'nyn' => 'Nyankole',
			'nyo' => 'Nyoro',
			'nzi' => 'Nzima',
			'oci' => 'Occitan (post-1500)',
			'oji' => 'Ojibwa',
			'ori' => 'Oriya',
			'orm' => 'Oromo',
			'osa' => 'Osage',
			'oss' => 'Ossetic',
			'ota' => 'Turkish, Ottoman',
			'oto' => 'Otomian languages',
			'paa' => 'Papuan (Other)',
			'pag' => 'Pangasinan',
			'pal' => 'Pahlavi',
			'pam' => 'Pampanga',
			'pan' => 'Panjabi',
			'pap' => 'Papiamento',
			'pau' => 'Palauan',
			'peo' => 'Old Persian (ca. 600-400 B.C.)',
			'per' => 'Persian',
			'phi' => 'Philippine (Other)',
			'phn' => 'Phoenician',
			'pli' => 'Pali',
			'pol' => 'Polish',
			'pon' => 'Pohnpeian',
			'por' => 'Portuguese',
			'pra' => 'Prakrit languages',
			'pro' => 'Provençal (to 1500)',
			'pus' => 'Pushto',
			'que' => 'Quechua',
			'raj' => 'Rajasthani',
			'rap' => 'Rapanui',
			'rar' => 'Rarotongan',
			'roa' => 'Romance (Other)',
			'roh' => 'Raeto-Romance',
			'rom' => 'Romani',
			'rum' => 'Romanian',
			'run' => 'Rundi',
			'rup' => 'Aromanian',
			'rus' => 'Russian',
			'sad' => 'Sandawe',
			'sag' => 'Sango (Ubangi Creole)',
			'sah' => 'Yakut',
			'sai' => 'South American Indian (Other)',
			'sal' => 'Salishan languages',
			'sam' => 'Samaritan Aramaic',
			'san' => 'Sanskrit',
			'-sao' => 'Samoan',
			'sas' => 'Sasak',
			'sat' => 'Santali',
			'-scc' => 'Serbian',
			'scn' => 'Sicilian Italian',
			'sco' => 'Scots',
			'-scr' => 'Croatian',
			'sel' => 'Selkup',
			'sem' => 'Semitic (Other)',
			'sga' => 'Irish, Old (to 1100)',
			'sgn' => 'Sign languages',
			'shn' => 'Shan',
			'-sho' => 'Shona',
			'sid' => 'Sidamo',
			'sin' => 'Sinhalese',
			'sio' => 'Siouan (Other)',
			'sit' => 'Sino-Tibetan (Other)',
			'sla' => 'Slavic (Other)',
			'slo' => 'Slovak',
			'slv' => 'Slovenian',
			'sma' => 'Southern Sami',
			'sme' => 'Northern Sami',
			'smi' => 'Sami',
			'smj' => 'Lule Sami',
			'smn' => 'Inari Sami',
			'smo' => 'Samoan',
			'sms' => 'Skolt Sami',
			'sna' => 'Shona',
			'snd' => 'Sindhi',
			'-snh' => 'Sinhalese',
			'snk' => 'Soninke',
			'sog' => 'Sogdian',
			'som' => 'Somali',
			'son' => 'Songhai',
			'sot' => 'Sotho',
			'spa' => 'Spanish',
			'srd' => 'Sardinian',
			'srn' => 'Sranan',
			'srp' => 'Serbian',
			'srr' => 'Serer',
			'ssa' => 'Nilo-Saharan (Other)',
			'-sso' => 'Sotho',
			'ssw' => 'Swazi',
			'suk' => 'Sukuma',
			'sun' => 'Sundanese',
			'sus' => 'Susu',
			'sux' => 'Sumerian',
			'swa' => 'Swahili',
			'swe' => 'Swedish',
			'-swz' => 'Swazi',
			'syc' => 'Syriac',
			'syr' => 'Syriac, Modern',
			'-tag' => 'Tagalog',
			'tah' => 'Tahitian',
			'tai' => 'Tai (Other)',
			'-taj' => 'Tajik',
			'tam' => 'Tamil',
			'-tar' => 'Tatar',
			'tat' => 'Tatar',
			'tel' => 'Telugu',
			'tem' => 'Temne',
			'ter' => 'Terena',
			'tet' => 'Tetum',
			'tgk' => 'Tajik',
			'tgl' => 'Tagalog',
			'tha' => 'Thai',
			'tib' => 'Tibetan',
			'tig' => 'Tigré',
			'tir' => 'Tigrinya',
			'tiv' => 'Tiv',
			'tkl' => 'Tokelauan',
			'tlh' => 'Klingon (Artificial language)',
			'tli' => 'Tlingit',
			'tmh' => 'Tamashek',
			'tog' => 'Tonga (Nyasa)',
			'ton' => 'Tongan',
			'tpi' => 'Tok Pisin',
			'-tru' => 'Truk',
			'tsi' => 'Tsimshian',
			'tsn' => 'Tswana',
			'tso' => 'Tsonga',
			'-tsw' => 'Tswana',
			'tuk' => 'Turkmen',
			'tum' => 'Tumbuka',
			'tup' => 'Tupi languages',
			'tur' => 'Turkish',
			'tut' => 'Altaic (Other)',
			'tvl' => 'Tuvaluan',
			'twi' => 'Twi',
			'tyv' => 'Tuvinian',
			'udm' => 'Udmurt',
			'uga' => 'Ugaritic',
			'uig' => 'Uighur',
			'ukr' => 'Ukrainian',
			'umb' => 'Umbundu',
			'und' => 'Undetermined',
			'urd' => 'Urdu',
			'uzb' => 'Uzbek',
			'vai' => 'Vai',
			'ven' => 'Venda',
			'vie' => 'Vietnamese',
			'vol' => 'Volapük',
			'vot' => 'Votic',
			'wak' => 'Wakashan languages',
			'wal' => 'Wolayta',
			'war' => 'Waray',
			'was' => 'Washoe',
			'wel' => 'Welsh',
			'wen' => 'Sorbian (Other)',
			'wln' => 'Walloon',
			'wol' => 'Wolof',
			'xal' => 'Oirat',
			'xho' => 'Xhosa',
			'yao' => 'Yao (Africa)',
			'yap' => 'Yapese',
			'yid' => 'Yiddish',
			'yor' => 'Yoruba',
			'ypk' => 'Yupik languages',
			'zap' => 'Zapotec',
			'zbl' => 'Blissymbolics',
			'zen' => 'Zenaga',
			'zha' => 'Zhuang',
			'znd' => 'Zande languages',
			'zul' => 'Zulu',
			'zun' => 'Zuni',
			'zxx' => 'No linguistic content',
			'zza' => 'Zaza'
		];
		
		// Main XML element.
		$segments_xml = new SimpleXMLElement('<bhlSegmentData></bhlSegmentData>');

		// Query the database and check the results.
		$query = $this->CI->db->query("SELECT * FROM custom_bhl_segments WHERE item_id = {$book->id}");
		if (count($query->result()) == 0) {
				return NULL;
		}

		// Go through the results.
		foreach ($query->result() as $row) {
			$segment_xml = $segments_xml->addChild('segment');
			$segment_xml->title = $row->title;
			if ($row->translated_title) {
				$segment_xml->translatedTitle = $row->translated_title;
			}
			$segment_xml->volume = $row->volume;
			$segment_xml->issue = $row->issue;
			$segment_xml->series = $row->series;
			$segment_xml->date = $row->date;
			$segment_xml->language = $row->language;
			
			$genre_xml = $segment_xml->addChild('genre', $segment_genres[$row->genre]);
			$genre_xml->addAttribute('id', $row->genre);

			// Add the authors.
			$authors_xml = $segment_xml->addChild('authors');
			foreach (json_decode($row->author_list) as $author) {
				$author_xml = $authors_xml->addChild('author');

				// Add the name and (if applicable) dates.
				if ($author->name) {
					if ($author->dates) {
						$author_xml->addChild('name', "{$author->name}, {$author->dates}");
					} else {
						$author_xml->addChild('name', "{$author->name}");
					}
				}
				if ($author->first_name) {
					$author_xml->addChild('firstName', "{$author->first_name}");
				}
				if ($author->last_name) {
					$author_xml->addChild('lastName', "{$author->last_name}");
				}
				if ($author->start_date) {
					$author_xml->addChild('startDate', "{$author->start_date}");
				}
				if ($author->end_date) {
					$author_xml->addChild('endDate', "{$author->end_date}");
				}
				if ($author->identifier_type && $author->identifier_value) {
					$id_xml = $author_xml->addChild('identifier', "{$author->identifier_value}");
					$id_xml->addAttribute('typeId', $author->identifier_type);
				}

				// Check if the author has an ID in BHL.
				if (isset($author->source)) {
					$author_xml->addAttribute('authorId', $author->source);
				} else {
					$author_xml->addAttribute('authorId', '');
				}
			}

			// Add the pages.
			$pages_xml = $segment_xml->addChild('leafNums');
			foreach (json_decode($row->page_list) as $page) {
				$pages_xml->addChild('leafNum', $page);
			}

			$segment_xml->addChild('doi', $row->doi);
		}

		$segments_xml = str_replace('<?xml version="1.0"?>', '', $segments_xml->asXML());
		
		$xml = new DOMDocument("1.0");
		$xml->preserveWhiteSpace = false;
		$xml->formatOutput = true;
		$xml->loadXML($segments_xml);
		return str_replace('<?xml version="1.0"?>', '', $xml->saveXML());
	}

	/* ----------------------------
	 * Function: _create_scandata_xml()
	 *
	 * Parameters:
	 *    $id: The ID of the item as determined earlier
	 *    $book: A book object
	 *    $pages: The pages from the book (assume they were gathered earlier)
	 *
	 * Returns the XML for the scandata.xml file. Does not create the file.
	 * This is specific to Internet Archive but is left here as a reminder.
	 * This should call Book.get_item_metadata().
	 * ---------------------------- */
	function _create_scandata_xml($id, $book, $pages) {

		$this->CI->load->library('image_lib');

		$dpi = $this->_get_dpi($book, $pages);

		$output = '<book>'."\n";
		$output .= '  <bookData>'."\n";
		$output .= '    <bookId>'.$id.'</bookId>'."\n";
		$output .= '    <leafCount>'.count($pages).'</leafCount>'."\n";
		$output .= '    <dpi>'.$dpi.'</dpi>'."\n";
		if ($book->page_progression == 'rtl') {
			$output .= '    <globalHandedness>'."\n";
			$output .= '      <page-progression>rl</page-progression>'."\n";
			$output .= '      <scanned-right-to-left>true</scanned-right-to-left>'."\n";
			$output .= '      <scanned-upside-down>false</scanned-upside-down>'."\n";
			$output .= '      <needs-rectification>false</needs-rectification>'."\n";
			$output .= '    </globalHandedness>'."\n";
		}
		$output .= '    <pageNumData>'."\n";
		$c = 1;
		foreach ($pages as $p) {
			if (property_exists($p, 'page_number')) {
				if ($p->page_number) {
					$output .= '      <assertion>'."\n";
					$output .= '      	<leafNum>'.$c.'</leafNum>'."\n";
					$output .= '      	<pageNum>'.$p->page_number.'</pageNum>'."\n";
					$output .= '      </assertion>'."\n";
				}
			}
			$c++;
		}
		$output .= '    </pageNumData>'."\n";
		$output .= '  </bookData>'."\n";
		$output .= '  <pageData>'."\n";

		$c = 1;

		foreach ($pages as $p) {
			$output .= '    <page leafNum="'.$c.'">'."\n";
			// Basic Info
			if ($c == 1) {
				$output .= '      <bookStart>true</bookStart>'."\n";
			}
			if (property_exists($p, 'page_type')) {
				$output .= '      <pageType>'.$this->_get_pagetype($p->page_type).'</pageType>'."\n";
			} else {
				$output .= '      <pageType>Normal</pageType>'."\n";
			}
			$output .= '      <addToAccessFormats>true</addToAccessFormats>'."\n";
			$output .= '      <origWidth>'.$p->width.'</origWidth>'."\n";
			$output .= '      <origHeight>'.$p->height.'</origHeight>'."\n";

			// Crop Box
			$output .= '      <cropBox>'."\n";
			$output .= '        <x>0</x>'."\n";
			$output .= '        <y>0</y>'."\n";
			$output .= '        <w>'.$p->width.'</w>'."\n";
			$output .= '        <h>'.$p->height.'</h>'."\n";
			$output .= '      </cropBox>'."\n";

			// Page Number
			if (property_exists($p, 'page_number')) {
				if ($p->page_number) {
					if (preg_match('/(and|,)/', $p->page_number)) {
						$pagenums = preg_split('/(and|,)/', $p->page_number);
						$output .= '      <pageNumber>'.trim($pagenums[0]).'</pageNumber>'."\n";
					} else {
						$output .= '      <pageNumber>'.$p->page_number.'</pageNumber>'."\n";
					}
				}
			}

			// Alternate Page Numbers (we only have one here right now, but we can send the prefix)
			if (property_exists($p, 'page_number')) {
				$implied = false;
				if (property_exists($p, 'page_number_implicit')) {
					$implied = ($p->page_number_implicit == 1);  
				}

				$prefix = '';
				if (property_exists($p, 'page_prefix')) {
					$prefix = $p->page_prefix;
				}
				$output .= '      <altPageNumbers>'."\n";
				if (preg_match('/(and|,)/', $p->page_number)) {
					$pagenums = preg_split('/(and|,)/', $p->page_number);
					foreach ($pagenums as $pgnum) {
						$output .= '        <altPageNumber prefix="'.htmlentities($prefix,ENT_XML1).'"'.($implied ? ' implied="1"' : '').'>'.trim($pgnum).'</altPageNumber>'."\n";
					}
				} else {
					$output .= '        <altPageNumber prefix="'.htmlentities($prefix,ENT_XML1).'"'.($implied ? ' implied="1"' : '').'>'.$p->page_number.'</altPageNumber>'."\n";
				}
				$output .= '      </altPageNumbers>'."\n";
			}

			// Recto/Verso
			if (property_exists($p, 'page_side')) {
				if ($p->page_side) {
					if (preg_match('/Left/i', $p->page_side)) {
						$output .= '      <handSide>LEFT</handSide>'."\n";
					} elseif (preg_match('/Right/i', $p->page_side)) {
						$output .= '      <handSide>RIGHT</handSide>'."\n";
					}
				}
			}

			// Alternate Page Types
			if (property_exists($p, 'page_type')) {
				$page_types = $this->_get_bhl_pagetypes($p->page_type);
			} else {
				$page_types = array('Blank');
			}

			// Always send alternate page types
			$output .= '      <altPageTypes>'."\n";
			foreach ($page_types as $pt) {
				$output .= '        <altPageType>'.$pt.'</altPageType>'."\n";
			}
			$output .= '      </altPageTypes>'."\n";

			// Caption, because we can
			if (property_exists($p, 'caption')) {
				if ($p->caption) {
					$output .= '      <caption>'.$p->caption.'</caption>'."\n";
				}
			}

			// Volume
			if (property_exists($p, 'volume')) {
				if ($p->volume) {
					$output .= '      <volume>'.$p->volume.'</volume>'."\n";
				}
			}

			// Piece information (choose the most important single item, XML only supports one.)
			// These are in order of importance. Issue, Number, then Part
			if (property_exists($p, 'piece')) {
				if (count($p->piece)) {
					$i = array_search('Issue', $p->piece);
					if (isset($i)) {
						$output .= '      <piece prefix="'.$p->piece[$i].'">'.$p->piece_text[$i].'</piece>'."\n";
					} else {
						$i = array_search('No.', $p->piece);
						if (isset($i)) {
							$output .= '      <piece prefix="'.$p->piece[$i].'">'.$p->piece_text[$i].'</piece>'."\n";
						} else {
							$i = array_search('Part', $p->piece);
							if (isset($i)) {
								$output .= '      <piece prefix="'.$p->piece[$i].'">'.$p->piece_text[$i].'</piece>'."\n";
							} else {
								$i = array_search('Suppl.', $p->piece);
								if (isset($i)) {
									$output .= '      <piece prefix="'.$p->piece[$i].'">'.$p->piece_text[$i].'</piece>'."\n";
								} 
							}
						}
					}
				}
			} elseif (property_exists($p, 'piece_text')) {
				$output .= '      <piece>'.$p->piece_text[0].'</piece>'."\n";
			}

			// Year
			if (property_exists($p, 'year')) {
				if ($p->year) {
					$output .= '      <year>'.$p->year.'</year>'."\n";
				}
			}

			$output .= '    </page>'."\n";
			$c++;
		}
		$output .= '  </pageData>';
		
		// Segments.
		if ($segments = $this->_create_segments_xml($id, $book, $pages)) {
			$output .= preg_replace('/^/m','  ', $segments);
		}

		$output .= '</book>';
		return $output;
	}

	/* ----------------------------
	 * Function: _create_creators_xml()
	 *
	 * Parameters:
	 *    $id: The ID of the item as determined earlier
	 *    $book: A book object
	 *
	 * Returns the XML for the creators.xml file. Does not create the file.
	 * This is specific to Internet Archive but is left here as a reminder.
	 * ---------------------------- */
	function _create_creators_xml($id, $book) {
		$creators = json_decode($book->get_metadata('creator_ids'), JSON_OBJECT_AS_ARRAY);
		$output = "<creators>\n";

		// Note: This array is copied from the Virtual_Items_Configs.php class
		$id_types = array(
			array('mods' => 'viaf', 'bhl' => 'viaf'),
			array('mods' => 'orcid', 'bhl' => 'orcid'),
			array('mods' => 'biostor', 'bhl' => 'biostor'),
			array('mods' => 'dlc', 'bhl' => 'dlc'),
			array('mods' => 'researchgate', 'bhl' => 'researchgate profile'),
			array('mods' => 'snac ', 'bhl' => 'snac ark'),
			array('mods' => 'biostor', 'bhl' => 'biostor author id'),
			array('mods' => 'tropicos', 'bhl' => 'tropicos'),
		);
	
		if ($creators) {
			foreach ($creators as $c) {
				$output .= "  <creator>\n";
				$output .= "    <name>".$c['name']."</name>\n";
				foreach ($id_types as $id) {
					if (isset($c[$id['bhl']])) {
						$output .= "    <identifier type=\"".$id['bhl']."\">".$c[$id['bhl']]."</identifier>\n";
					}
				}
				$output .= "  </creator>\n";
			}
		}
		$output .= "</creators>\n";
		return $output;
	}
	/* ----------------------------
	 * Function: _get_bhl_pagetypes()
	 *
	 * Parameters:
	 *    $p: A page from a book in Macaw
	 *
	 * Translates the page type values for one page into an array of pagetypes
	 * suitable for BHL. This is sent in addition to the page type data for
	 * internet archive.
	 * ---------------------------- */
	function _get_bhl_pagetypes($p) {
		for ($i=0; $i < count($p); $i++) {
			if ($p[$i] == 'Appendix') { $p[$i] = 'Appendix';}
			elseif ($p[$i] == 'Article start') { $p[$i] = 'Article Start'; }
			elseif ($p[$i] == 'Article end') { $p[$i] = 'Issue End'; }
			elseif ($p[$i] == 'Blank') { $p[$i] = 'Blank'; }
			elseif ($p[$i] == 'Bibliography') { $p[$i] = 'Text'; }
			elseif ($p[$i] == 'Copyright') { $p[$i] = 'Text'; }
			elseif ($p[$i] == 'Cover') { $p[$i] = 'Cover'; }
			elseif ($p[$i] == 'Chart') { $p[$i] = 'Chart'; }
			elseif ($p[$i] == 'Fold Out') { $p[$i] = 'Foldout'; }
			elseif ($p[$i] == 'Foldout') { $p[$i] = 'Foldout'; }
			elseif ($p[$i] == 'Illustration') { $p[$i] = 'Illustration'; }
			elseif ($p[$i] == 'Index') { $p[$i] = 'Index'; }
			elseif ($p[$i] == 'Issue Start') { $p[$i] = 'Issue Start'; }
			elseif ($p[$i] == 'Issue End') { $p[$i] = 'Issue End'; }
			elseif ($p[$i] == 'Map') { $p[$i] = 'Map'; }
			elseif ($p[$i] == 'Table of Contents') { $p[$i] = 'Table of Contents'; }
			elseif ($p[$i] == 'Text') { $p[$i] = 'Text'; }
			elseif ($p[$i] == 'Title Page') { $p[$i] = 'Title Page'; }
			elseif ($p[$i] == 'Bookplate') { $p[$i] = 'Bookplate'; }
			elseif ($p[$i] == 'Drawing') { $p[$i] = 'Drawing'; }
			elseif ($p[$i] == 'List of Illustrations') { $p[$i] = 'List of Illustrations'; }
			elseif ($p[$i] == 'Photograph') { $p[$i] = 'Photograph'; }
			elseif ($p[$i] == 'Table') { $p[$i] = 'Chart'; }
			elseif ($p[$i] == 'Specimen') { $p[$i] = 'Specimen'; }
			elseif ($p[$i] == 'Suppress') { $p[$i] = 'Delete'; }
			elseif ($p[$i] == 'Tissue') { $p[$i] = 'Delete'; }
			elseif ($p[$i] == 'White card') { $p[$i] = 'Delete'; }
			elseif ($p[$i] == 'Color card') { $p[$i] = 'Delete'; }
			else { $p[$i] = 'Text'; }
		}
		return $p;
	}

	/* ----------------------------
	 * Function: _get_pagetype()
	 *
	 * Parameters:
	 *    $t: A page type from Macaw
	 *
	 * Translates a page type value into something more suitable for Internet
	 * Archive.
	 * ---------------------------- */
	function _get_pagetype($t) {
		if (in_array('Cover', $t)) {
			return 'Cover';

		} else if (in_array('Foldout', $t)) {
			return 'Fold Out';

		} else if (in_array('Fold Out', $t)) {
			return 'Fold Out';

		} else if (in_array('Title Page', $t)) {
			return 'Title';

		} else if (in_array('Map', $t)) {
			return 'Map';

		} else if (in_array('Illustration', $t)) {
			return 'Illustrations';

		} else if (in_array('Photograph', $t)) {
			return 'Illustrations';

		} else if (in_array('Drawing', $t)) {
			return 'Illustrations';

		} else if (in_array('Issue Start', $t)) {
			return 'Issue Start';

		} else if (in_array('Issue End', $t)) {
			return 'Issue End';

		} else if (in_array('Tissue', $t)) {
			return 'Tissue';

		} else if (in_array('Color Card', $t)) {
			return 'Color Card';

		} else if (in_array('White Card', $t)) {
			return 'White Card';

		} else if (in_array('Suppress', $t)) {
			return 'Delete';
		}

		return 'Normal';
	}

	/* ----------------------------
	 * Function: _get_dpi()
	 *
	 * Parameters:
	 *    $book: A book object
	 *    $pages: The pages in the book (the pages should have been retrieved already)
	 *
	 * Uses the page metadata and the MARC information to estimate the DPI of the
	 * scanned pages based on the pixel dimensions of the first image it can find
	 * and the measurement of the height of the book from the MARC record. This is
	 * quirky and really only gives a good guess as to the DPI. If all fails, we
	 * return 450.
	 * ---------------------------- */
	function _get_dpi($book, $pages) {
		// Retrieve our MARC data.
		$marc = $this->_get_marc($book->get_metadata('marc_xml'));
		
		if ($marc !== false) {
			$namespaces = $marc->getDocNamespaces();
			$ns = '';
			if (array_key_exists('marc', $namespaces)) {
				$ns = 'marc:';
			} elseif (array_key_exists('', $namespaces)) {
				// Add empty namespace because xpath is weird
				$ns = 'ns:';
				$marc->registerXPathNamespace('ns', $namespaces['']);
			}
	
			// location
			$ret = ($marc->xpath($ns."record/".$ns."datafield[@tag='300']/".$ns."subfield[@code='c']"));
			if ($ret && count($ret) > 0) {
				$height = (string)$ret[0];
				$unit = 'cm';
				// Get the height of the book
				$matches = array();
				
				if (preg_match('/(\d+) ?(cm|in)/', $height, $matches)) {
					// 45 cm.
					// 35cm.
					$height = $matches[1];
					$unit = $matches[2];
				} elseif (preg_match('/(\d+)-(\d+) ?(cm|in)/', $height, $matches)) {
					// 48-51 cm.
					// 24-26 cm. and atlases of plates (part col.) 42 cm.
					$height = $matches[2];
					$unit = $matches[3];
				} elseif (preg_match('/(\d+) ?x ?(\d+) ?(cm|in)/', $height, $matches)) {
					// 25 x 38 cm.
					// 25x38 cm.
					$height = $matches[1];
					$unit = $matches[3];
				} elseif (preg_match('/(\d+)-(\d+) ?x ?(\d+) ?(cm|in)/', $height, $matches)) {
					// 25-43 x 38 cm.
					// 25-43x38 cm.
					$height = $matches[2];
					$unit = $matches[4];
				} elseif (preg_match('/folio/', $height, $matches)) {
					$height = 48;
					$unit = 'cm';
				} elseif (preg_match('/(\d+)/', $height, $matches)) {
					// Fallback, take the first number we can find.
					$height = $matches[1];
				}
				if ($height == 0) {
					return 300;
				}

				if ($unit == 'in') {
					return round($pages[0]->height / $height);
				} else {
					return round($pages[0]->height / $height / 0.393700787);
				}

			}
		}
		// As a default, return 300 
		return 300;

	}

	function _get_ia_meta_xml($b, $id) {
		// Get the meta XML file from IA, we want to keep some of the data elements
		$urls = $this->_get_derivative_urls($id);
		// Load the book
		$this->CI->book->load($b->barcode);
		$path = $this->cfg['base_directory'].'/books/'.$b->barcode.'/';
		if (!file_exists($path)) {
			$path = '/tmp/';
			print "INFO: Saving meta file to /tmp/\n";
		}
		$filename = $path.$b->barcode."_meta.xml";
		$ch = curl_init($urls[0].'/'.$id."_meta.xml");
		$fh = fopen($filename, "w");
		curl_setopt($ch, CURLOPT_FILE, $fh);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_exec($ch);
		curl_close($ch);

		$return = array();
		$meta = simplexml_load_file($filename);
		$meta = get_object_vars($meta);
		foreach ($meta as $k => $val) {
			if (is_array($val)) {
				for ($i = 0; $i < count($val); $i++) {
					$return['x-archive-meta'.sprintf("%02d", $i).'-'.$k] = $val[$i];
				}
			} else {
				$return['x-archive-meta-'.$k] = $val;
			}
		}
		return $return;
	}

	function _get_marc($marcxml) {
		# Make sure this is a string. If it's an array, we use the first one we can find.
		if (is_array($marcxml)) {
			$marcxml = array_shift($marcxml);
		}
		$marc = simplexml_load_string($marcxml);
		if ($marc === false) {
			return false;
		} else {
			return $marc;
		}
	}
	/* ----------------------------
	 * Function: _get_metadata()
	 *
	 * Parameters:
	 *    $id: The identifer of the item in question
	 *
	 * Standard function for creating an array of internet-archive-specific
	 * metadat elements to be used when uploading an item to IA.
	 * ---------------------------- */
	function _get_metadata() {
		// Converts MARC data to MODS to retrieve certain information.
		$marc_xml = $this->CI->book->get_metadata('marc_xml');
		// Virtual Items have no MARC XML
		$marc = false;
		$mods = false;
		if ($marc_xml) {
			$marc = $this->_get_marc($marc_xml);
			$mods = $this->CI->common->marc_to_mods($marc_xml);
			$mods =  simplexml_load_string($mods);
			$namespaces = $mods->getDocNamespaces();
			$ns = '';
			$root = '';
			if (array_key_exists('mods', $namespaces)) {
				$ns = 'mods:';
			} elseif (array_key_exists('', $namespaces)) {
				// Add empty namespace because xpath is weird
				$ns = 'ns:';
				$mods->registerXPathNamespace('ns', $namespaces['']);
			}
			$namespaces = $mods->getNamespaces();
			$ret = ($mods->xpath($ns."mods"));
			if ($ret && count($ret)) {
				$root = $ns."mods/";
			}
		}
		$metadata = array();
		// This is easy, hardcoded
		$metadata['x-archive-meta-mediatype'] = 'texts';

		// Contributor: Prefer the entered metadata, then the item's organization, then the hardcoded organization
		$metadata['x-archive-meta-contributor'] = $this->CI->book->get_contributor();
		// Really ensure we don't have more than one contributor
		if (is_array($metadata['x-archive-meta-contributor'])) {
			$metadata['x-archive-meta-contributor'] = $metadata['x-archive-meta-contributor'][0];
		}

		// These are almost as easy
		$metadata['x-archive-meta-sponsor'] = $this->CI->book->get_metadata('sponsor');

		// Handle the collection(s) that the book might be in
		$collections = $this->CI->book->get_metadata('collections'); // Returns an array for multiple valies OR a string
		if (!is_array($collections)) {
			if ($collections) {
				$collections = array($collections);
			}
		}

		// Combine the "collection" (singular) metadata because, well, people get confused.
		$collection = $this->CI->book->get_metadata('collection'); // Returns an array for multiple valies OR a string
		if (!is_array($collection) && $collection) {
			$collections[] = $collection;
		} else {
			if (isset($collection)) {
				foreach ($collection as $c) {
					$collections[] = $c;
				}
			}
		}

		// Now we add the collections to the metadata
		$count = 0;
		$bhl = 0;
		foreach ($collections as $c) {
			if (strtolower($c) == 'bhl' || strtolower($c) == 'biodiversity' || strtolower($c) == 'biodiversity heritage library') {
				$metadata['x-archive-meta'.sprintf("%02d", $count).'-collection'] = 'biodiversity';
				$metadata['x-archive-meta-curation'] = '[curator]biodiversitylibrary.org[/curator][date]'.mdate('%Y%m%d%h%i%s',time()).'[/date][state]approved[/state]';
				$bhl = 1;
			} elseif (strtolower($c) == 'sil' || strtolower($c) == 'smithsonian') {
				$metadata['x-archive-meta'.sprintf("%02d", $count).'-collection'] = 'smithsonian';
			} else {
				$metadata['x-archive-meta'.sprintf("%02d", $count).'-collection'] = $c;
			}
			$count++;
		}
		
		// Handle the subcollection(s) that the book might be in
		$subcollections = $this->CI->book->get_metadata('subcollections'); // Returns an array for multiple valies OR a string
		if (!is_array($subcollections)) {
			$subcollections = array($subcollections);
		}

		// Combine the "subcollection" (singular) metadata because, well, people get confused.
		$subcollection = $this->CI->book->get_metadata('subcollection'); // Returns an array for multiple valies OR a string
		if (!is_array($subcollection) && $subcollection) {
			$subcollections[] = $subcollection;
		} else {
			if (isset($subcollection)) {
				foreach ($subcollection as $c) {
					$subcollections[] = $c;
				}
			}
		}

		// Now we add the subcollections to the metadata
		$count = 0;
		foreach ($subcollections as $s) { 
			if ($s) {
				$metadata['x-archive-meta'.sprintf("%02d", $count).'-subcollection'] = $s;
				$count++;
			}
		}
			
		// Handle right-to-left pagination
		if ($this->CI->book->page_progression == 'rtl') {
			$metadata['x-archive-meta-page-progression'] = 'rl';
		}
		
		// If we have explicitly set a CC license, let's use it.
		$cc_license = $this->CI->book->get_metadata('cc_license');
		if (isset($cc_license)) {
			$metadata['x-archive-meta-licenseurl'] = $cc_license;
		}

		// BHL Copyright guidelines: https://bhl.wikispaces.com/copyright
		// Handle copyright - Not in Copyright
    $copyright = $this->CI->book->get_metadata('copyright', false);
    if (is_array($copyright)) { $copyright = $copyright[0]; }
		if ($copyright == '0' || strtoupper($copyright) == 'F' ) {
			if ($bhl == 1) {
				$metadata['x-archive-meta-possible-copyright-status'] = "Public domain. The BHL considers that this work is no longer under copyright protection.";
				if (isset($metadata['x-archive-meta-licenseurl'])) {
					unset($metadata['x-archive-meta-licenseurl']);
				}
			} else {
				$metadata['x-archive-meta-possible-copyright-status'] = "Public domain. The Library considers that this work is no longer under copyright protection";
				if (isset($metadata['x-archive-meta-licenseurl'])) {
					unset($metadata['x-archive-meta-licenseurl']);
				}

			}

		// Handle copyright - Permission Granted to Scan
		} elseif ($copyright == '1'  || strtoupper($copyright) == 'T' ) {
			$metadata['x-archive-meta-possible-copyright-status'] = "In copyright. Digitized with the permission of the rights holder.";
			$metadata['x-archive-meta-rights'] = 'https://biodiversitylibrary.org/permissions';

		// Handle copyright - Due Dillegene Performed to determine public domain status
		} elseif ($copyright == '2') {
			$metadata['x-archive-meta-possible-copyright-status'] = "No known copyright restrictions as determined by scanning institution.";
			$metadata['x-archive-meta-due-diligence'] = 'https://biodiversitylibrary.org/permissions';
			$metadata['x-archive-meta-duediligence'] = 'https://biodiversitylibrary.org/permissions';

		// Handle copyright - Default, we hope we never hit this
		} else {
			$metadata['x-archive-meta-possible-copyright-status'] = $copyright;
		}

		// Now we use xpath to get stuff out of the mods. Fun!
		if ($mods) {
			$ret = ($mods->xpath($root.$ns."titleInfo[not(@type)]/".$ns."title"));
			if ($ret && count($ret) > 0) {
				$metadata['x-archive-meta-title'] = str_replace('"', "'", $ret[0].'');
			}

			$ret = ($mods->xpath($root.$ns."name/".$ns."role/".$ns."roleTerm[.='creator']/../../".$ns."namePart"));
			if ($ret && count($ret) > 0) {
				$metadata['x-archive-meta-creator'] = str_replace('"', "'", $ret[0]).'';
			}
			if (!isset($metadata['x-archive-meta-creator'])) {
				$ret = ($mods->xpath($root.$ns."name/".$ns."namePart"));
				if ($ret && count($ret) > 0) {
					$metadata['x-archive-meta-creator'] = str_replace('"', "'", $ret[0]).'';
				}		
			}
			
			$ret = ($mods->xpath($root.$ns."subject[@authority='lcsh']/".$ns."topic"));
			$c = 0;
			// If we didn't get anything in topic, let's check genre, not sure if this is correct
			// JMR 6/4/14 - Fixed the logic for this. 'twas backwards.
			if (!$ret || count($ret) == 0) {
				$ret = ($mods->xpath($root.$ns."subject[@authority='lcsh']/".$ns."genre"));
			}
			if (is_array($ret)) {
				foreach ($ret as $r) {
					$metadata['x-archive-meta'.sprintf("%02d", $c).'-subject'] = str_replace('"', "'", $r).'';
					$c++;
				}
			}

			// Genre
			$ret = ($mods->xpath($root.$ns."genre"));
			if ($ret && count($ret) > 0) {
				$metadata['x-archive-meta-genre'] = str_replace('"', "'", $ret[0].'');
			}

			// Abstract
			$ret = ($mods->xpath($root.$ns."abstract"));
			if ($ret && count($ret) > 0) {
				$metadata['x-archive-meta-abstract'] = str_replace('"', "'", $ret[0].'');
				$metadata['x-archive-meta-abstract'] = preg_replace('/[\r\n]/','<br/>',$metadata['x-archive-meta-abstract']);
			}

			//modified JC 4/2/12
			if ($this->CI->book->get_metadata('year')) {
				$metadata['x-archive-meta-date'] = $this->CI->book->get_metadata('year', false).'';
				$metadata['x-archive-meta-year'] = $this->CI->book->get_metadata('year', false).'';
			// LEGACY? Remove this? 
			} elseif ($this->CI->book->get_metadata('pub_date')) {
				$metadata['x-archive-meta-date'] = $this->CI->book->get_metadata('pub_date', false).'';
				$metadata['x-archive-meta-year'] = $this->CI->book->get_metadata('pub_date', false).'';
			} else {
				$ret = ($mods->xpath($root.$ns."originInfo/".$ns."dateIssued[@encoding='marc'][@point='start']"));
				if (count($ret) == 0) {
					$ret = ($mods->xpath($root.$ns."originInfo/".$ns."dateIssued"));
				}
				if ($ret && count($ret) > 0) {
					$metadata['x-archive-meta-year'] = $ret[0].'';
					$metadata['x-archive-meta-date'] = $ret[0].'';
				}
			}

			$ret = ($mods->xpath($root.$ns."originInfo/".$ns."publisher"));
			if ($ret && count($ret) > 0) {
				$metadata['x-archive-meta-publisher'] = str_replace('"', "'", $ret[0]).'';
			}

			$ret = ($mods->xpath($root.$ns."language/".$ns."languageTerm"));
			if ($ret && count($ret) > 0) {
				$metadata['x-archive-meta-language'] = $ret[0].'';
			}
		} else {
			// This is used by Virtual Items because they have no MARC data. 
			$metadata['x-archive-meta-title'] = str_replace('"', "'", $this->CI->book->get_metadata('title'));
			// Remove unprintables, just in case.
			$metadata['x-archive-meta-title'] = preg_replace('/[\x00-\x1F\x7F]/u', '', $metadata['x-archive-meta-title']);

			$creators = $this->CI->book->get_metadata('creator');
			if (is_array($creators)) {
				$c = 1;
				foreach ($creators as $creator) {
					$metadata['x-archive-meta'.sprintf("%02d", $c++).'-creator'] = str_replace('"', "'", $creator);
				}				
			} else {
				$metadata['x-archive-meta-creator'] = str_replace('"', "'", $this->CI->book->get_metadata('creator'));
			}
			
			$subjects = $this->CI->book->get_metadata('subject');
			if (is_array($subjects)) {
				$c = 1;
				foreach ($subjects as $subject) {
					$metadata['x-archive-meta'.sprintf("%02d", $c++).'-subject'] = str_replace('"', "'", $subject);
				}				
			} else {
				$metadata['x-archive-meta-subject'] = str_replace('"', "'", $this->CI->book->get_metadata('subject'));
			}

			$metadata['x-archive-meta-genre'] =                     str_replace('"', "'", $this->CI->book->get_metadata('genre'));
      $abstract = $this->CI->book->get_metadata('abstract', false);
			$abstract = preg_replace('/"/', "'", $abstract);
			$metadata['x-archive-meta-abstract'] =                  preg_replace('/[\r\n]/', "<br>",  $abstract);
			$metadata['x-archive-meta-year'] =                      str_replace('"', "'", $this->CI->book->get_metadata('year', false));
			$metadata['x-archive-meta-date'] =                      str_replace('"', "'", $this->CI->book->get_metadata('date', false));
			$metadata['x-archive-meta-publisher'] =                 str_replace('"', "'", $this->CI->book->get_metadata('publisher', false));
			$metadata['x-archive-meta-source'] =                    str_replace('"', "'", $this->CI->book->get_metadata('source', false));
			$metadata['x-archive-meta-language'] =                  str_replace('"', "'", $this->CI->book->get_metadata('language', false));
			$metadata['x-archive-meta-rights-holder'] =             str_replace('"', "'", $this->CI->book->get_metadata('rights_holder', false));
			if ($this->CI->book->get_metadata('scanning_institution')) {
				$metadata['x-archive-meta-scanning-institution'] = str_replace('"', "'", $this->CI->book->get_metadata('scanning_institution'));
			}
			if ($this->CI->book->get_metadata('copy_specific_information')) {
				$metadata['x-archive-meta-copy-specific-information'] = str_replace('"', "'", $this->CI->book->get_metadata('copy_specific_information'));
			}
			$metadata['x-archive-meta-page--range'] =str_replace('"', "'", $this->CI->book->get_metadata('page_range'));
			if ($this->CI->book->get_metadata('identifier_doi')) {
				$metadata['x-archive-meta-identifier-doi'] = str_replace('"', "'", $this->CI->book->get_metadata('identifier_doi'));
			} elseif ($this->CI->book->get_metadata('identifier-doi')) {
				$metadata['x-archive-meta-identifier-doi'] = str_replace('"', "'", $this->CI->book->get_metadata('identifier-doi'));
			} elseif ($this->CI->book->get_metadata('doi')) {
				$metadata['x-archive-meta-identifier-doi'] = str_replace('"', "'", $this->CI->book->get_metadata('doi', false));
			}
		}

		if ($this->CI->book->get_metadata('volume')) {
			$metadata['x-archive-meta-volume'] = $this->CI->book->get_metadata('volume', false).'';
		}

		if ($this->CI->book->get_metadata('series')) {
			$metadata['x-archive-meta-series'] = $this->CI->book->get_metadata('series', false).'';
		}

		if ($this->CI->book->get_metadata('issue')) {
			$metadata['x-archive-meta-issue'] = $this->CI->book->get_metadata('issue', false).'';
		}

		// Is this a Virtual Item?
		if ($this->CI->book->get_metadata('bhl_virtual_titleid') || $this->CI->book->get_metadata('bhl_virtual_volume')) {
			if ($this->CI->book->get_metadata('bhl_virtual_titleid')) {
				$metadata['x-archive-meta-bhl--virtual--titleid'] = $this->CI->book->get_metadata('bhl_virtual_titleid').'';
			}
			if ($this->CI->book->get_metadata('bhl_virtual_volume')) {
				$metadata['x-archive-meta-bhl--virtual--volume'] = $this->CI->book->get_metadata('bhl_virtual_volume').'';
			}
		}

		if ($this->CI->book->get_metadata('call_number')) {
			$val = $this->CI->book->get_metadata('call_number').'';
			$metadata['x-archive-meta-call--number'] = $val;
			$metadata['x-archive-meta-call-number'] = $val;
			$metadata['x-archive-meta-identifier-bib'] = $val;

		} elseif ($this->CI->book->get_metadata('call-number')) {
			$val = $this->CI->book->get_metadata('call-number').'';
			$metadata['x-archive-meta-call--number'] = $val;
			$metadata['x-archive-meta-call-number'] = $val;
			$metadata['x-archive-meta-identifier-bib'] = $val;

		} else {
			$val = $this->CI->book->barcode.'';
			$metadata['x-archive-meta-call--number'] = $val;
			$metadata['x-archive-meta-call-number'] = $val;
			$metadata['x-archive-meta-identifier-bib'] = $val;
		}
		
		$tm = time();
		if (isset($this->CI->book->date_review_end) && $this->CI->book->date_review_end != '0000-00-00 00:00:00') {
			$tm = strtotime($this->CI->book->date_review_end);
		} else if (isset($this->CI->book->date_created) && $this->CI->book->date_created != '0000-00-00 00:00:00') {
			$tm = strtotime($this->CI->book->date_created);		
		}
		if ($tm > 0) {
			$metadata['x-archive-scandate'] = date('YmdHis', $tm);
		}

		// Some data comes from MARC.
		if ($marc !== false) {
			$namespaces = $marc->getDocNamespaces();
			$ns = '';
			$root = '/';
			if (array_key_exists('marc', $namespaces)) {
				$ns = 'marc:';
			} elseif (array_key_exists('', $namespaces)) {
				// Add empty namespace because xpath is weird
				$ns = 'ns:';
				$marc->registerXPathNamespace('ns', $namespaces['']);
			}
			$namespaces = $marc->getNamespaces();
			$ret = ($marc->xpath($ns."marc"));
			if ($ret && count($ret)) {
					$root = '/'.$ns."marc/";
			}
	
			// location
			$ret = ($marc->xpath($root.$ns."record/".$ns."datafield[@tag='852']/".$ns."subfield[@code='a']"));
			if ($ret && count($ret) > 0) {
				$metadata['x-archive-meta-location'] = str_replace('"', "'", $ret[0].'');
			}
	
			// collection-number
			$ret = ($marc->xpath($root.$ns."record/".$ns."datafield[@tag='852']/".$ns."subfield[@code='b']"));
			if ($ret && count($ret) > 0) {
				$metadata['x-archive-meta-collection-number'] = str_replace('"', "'", $ret[0].'');
			}
	
			// sublocation
			$ret = ($marc->xpath($root.$ns."record/".$ns."datafield[@tag='852']/".$ns."subfield[@code='c']"));
			if ($ret && count($ret) > 0) {
				$metadata['x-archive-meta-sublocation'] = str_replace('"', "'", $ret[0].'');
			}

			// Rights Holder
			if ($this->CI->book->get_metadata('rights_holder')) {
				$metadata['x-archive-meta-rights-holder'] = str_replace('"', "'", $this->CI->book->get_metadata('rights_holder'));
			}
			
			// Scanning Institution
			if ($this->CI->book->get_metadata('scanning_institution')) {
				$metadata['x-archive-meta-scanning-institution'] = str_replace('"', "'", $this->CI->book->get_metadata('scanning_institution'));
			}

			// Copy Specific Information
			if ($this->CI->book->get_metadata('copy_specific_information')) {
				$metadata['x-archive-meta-copy-specific-information'] = str_replace('"', "'", $this->CI->book->get_metadata('copy_specific_information'));
			}

			// DOI 
			if ($this->CI->book->get_metadata('identifier_doi')) {
				$metadata['x-archive-meta-identifier-doi'] = str_replace('"', "'", $this->CI->book->get_metadata('identifier_doi'));
			} elseif ($this->CI->book->get_metadata('identifier-doi')) {
				$metadata['x-archive-meta-identifier-doi'] = str_replace('"', "'", $this->CI->book->get_metadata('identifier-doi'));
			} elseif ($this->CI->book->get_metadata('doi')) {
				$metadata['x-archive-meta-identifier-doi'] = str_replace('"', "'", $this->CI->book->get_metadata('doi'));
			}

			return $metadata;
		} else {
			// If we have no MARC information AND we have no indicator that this
			// is a virual item, do nothing. This is an invalid set of data.
			if (!$this->CI->book->get_metadata('bhl_virtual_volume')) {
				return null;
			}		
		}

		return $metadata;
	}

	/* ----------------------------
	 * Function: _create_marc_xml()
	 *
	 * Parameters:
	 *    NONE
	 *
	 * Returns the XML for the meta.xml file. Does not create the file. This
	 * is specific to Internet Archive but is left here as a reminder. This
	 * should call Book.get_marc_xml().
	 * ---------------------------- */
	function _create_marc_xml() {
		// Just get the MARC XML from the book and format the XML file properly
		$marc = $this->CI->book->get_metadata('marc_xml');
		return $marc;
	}

	/* ----------------------------
	 * Function: identifier()
	 *
	 * Parameters:
	 *    $book: The book record from the database (used to create a book object)
	 *
	 * Given a book, use some algorithm to create a (hopefully) unique identifier
	 * to use at Internet Archive. We'll go ahead and check here to make sure that
	 * the identifier is unique by hitting a URL at IA.
	 *
	 * Note: this is not in the Book() model because the logic is specific to
	 * internet archive. We're trying to mimic what they do:
	 * 		TITLE(16chars)NUM(2chars)AUTHOR(4chars)
	 *      example: gisassessmentofs05mack, chinaecosystemse08bubb, carbonindrylands08unep, progressreporton08cmss
	 * ---------------------------- */
	function identifier($book, $metadata) {
		$this->CI->book->load($book->barcode);

		$identifier = '';
		// 1. Do we already have an identifier for this book? If so, return it.
		$this->CI->db->where('item_id', $book->id);
		$query = $this->CI->db->get('custom_internet_archive');
		$ret = $query->result();
		if (count($ret) == 0) {
		} else {
			$identifier = $ret[0]->identifier;
			return $identifier;
		}

		// Do we aeady have an id_identifier metadata field?
		$identifier = $this->CI->book->get_metadata('ia_identifier');
		if ($identifier) {
			// Clean it
			$identifier = preg_replace('/[^A-Za-z0-9_-]/', '_', $identifier);
			// Yes, does it exist at IA?
			if (!$this->_bucket_exists($identifier)) {				
				// No, save it and use it
				$this->CI->db->insert(
					'custom_internet_archive',
					array(
						'item_id' => $book->id,
						'identifier' => $identifier
					)
				);
				return $identifier;
			} else {
				//   Yes, return an error
				$this->CI->logging->log('book', 'error', 'Metadata field ia_identifier "'.$identifier.'" could not be used. Bucket already exists at IA.', $this->CI->book->barcode);
				$message = "Error processing export.\n\n".
					"Identifier:    ".$this->CI->book->barcode."\n\n".
					"IA Identifier: ".$identifier."\n\n".
					"Error Message: IA identifier was specified on the item, but already exists at IA. Not Exporting.\n\n";
				$this->CI->common->email_error($message);
				return '';
			}	
		} 

		// A counter to help make things unique
		$count = 0;
		$count2 = 0;
		$count3 = 0;

		// Get the title and author from MODS, sometimes it's not available on the book's metadata
		// Process the title
		
		$title = iconv("UTF-8", "ASCII//TRANSLIT//IGNORE", $metadata['x-archive-meta-title']);
		$title = preg_replace('/\b(the|a|an|and|or|of|for|to|in|it|is|are|at|of|by)\b/i', '', $title);
		$title = preg_replace('/[^a-zA-Z0-9]/', '', $title);
		$title = substr($title, 0, 15);
		// Process the author
		$author = '';
		if (isset($metadata['x-archive-meta-creator'])) {
			$author = iconv("UTF-8", "ASCII//TRANSLIT//IGNORE", $metadata['x-archive-meta-creator']);
			$author = substr(preg_replace('/[^a-zA-Z0-9]/', '', $author), 0, 4);
		} elseif (isset($metadata['x-archive-meta01-creator'])) {
			$author = iconv("UTF-8", "ASCII//TRANSLIT//IGNORE", $metadata['x-archive-meta01-creator']);
			$author = substr(preg_replace('/[^a-zA-Z0-9]/', '', $author), 0, 4);
		}
		while ($count3 <= 26) {
		while ($count2 <= 26) {
			while ($count <= 26) {
				// If we got to this point, we don't have an identifier. Make a new one.
				$number = '00';
				// Get the volume number of the book
				$vol = $this->CI->book->get_metadata('volume');
				if ($vol) { $number = $vol; }
				// No volume? Get it from the pages?
				if ($number == '00') {
					$pages = $this->CI->book->get_pages();
					foreach ($pages as $p) {
						if (property_exists($p, "volume") && $p->volume) {
							$number = $p->volume;
							break;
						}
					}					
				}

				$number = substr(preg_replace('/[^a-zA-Z0-9]/', '', $number), 0, 4);
				// Make this lowercase becuse SIL (and maybe others) uses it as a URL and URLs are case-insensitive (or should be)
				$identifier = strtolower($title.$number.$author);
				if ($count3 > 0) {
					$identifier .= chr($count3+96);
				}
				if ($count2 > 0) {
					$identifier .= chr($count2+96);
				}
				if ($count > 0) {
					$identifier .= chr($count+96);
				}
	
				// Make sure the identifier doesn't already exist in our custom table
				$this->CI->db->where('identifier', $identifier);
				$this->CI->db->from('custom_internet_archive');
				if ($this->CI->db->count_all_results() == 0) {
					// We didn't find it in our database, so....
					// Make sure the identifier doesn't exist at IA
					if (!$this->_bucket_exists($identifier)) {
						// Save the identifier to the database
						$this->CI->db->insert(
							'custom_internet_archive',
							array(
								'item_id' => $book->id,
								'identifier' => $identifier
							)
						);
						// OK! Return the identifier
						return $identifier;
	
					} else {
						// Otherwise, keep looking
						$count++;
					}
	
				} else {
					// Otherwise, keep looking
					$count++;
				}
							
			}
			$count2++;
			$count = 0;
		}
		$count3++;
		$count = 0;
		$count2 = 0;
		}
		return '';
	}

	/* ----------------------------
	 * Function: _bucket_exists()
	 *
	 * Parameters:
	 *    $id: The IA identifier we are testing for
	 *
	 * Makes an attempt to determine whether or not an item exists at internet
	 * archive by checking the details page. If we get a 503 error or the string
	 * "item cannot be found" appears on the page, then we assume that the s3
	 * bucket does not exist. This is used in both making sure we aren't using an
	 * identifier that already exists as well as for checking to see if the
	 * bucket is created before uploading additional items to it.
	 * ---------------------------- */
	function _bucket_exists($id) {
		if (!isset($this->curl)) {
			$this->curl = curl_init();
		}

		echo "\nChecking https://archive.org/services/check_identifier.php?identifier=$id ...";
		curl_setopt($this->curl, CURLOPT_URL, "https://archive.org/services/check_identifier.php?identifier=".$id);
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($this->curl, CURLOPT_HTTPGET, true);
		curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, true);
		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
			// TODO Why is this here? Why does windows seem to want it?
			curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, 0);
		} 
		$output = curl_exec($this->curl);
		$output = simplexml_load_string($output);
		$attr = 'type';
		$success = (string)$output->attributes()->$attr;
		if ($success == 'success') {
			$attr = 'code';
			$code = (string)$output->attributes()->$attr;
			if ($code == 'not_available') {
				echo "Found!\n";
				return true;
			}
			if ($code == 'available') {
				echo "Not Found!\n";
				return false;
			}
		}

		// <result type="success" code="not_available">
		// <message>The identifier you have chosen is not available</message>
		// <identifier>physicalgeograp00maur</identifier>
		// </result>
		// 
		// <result type="success" code="available">
		// <message>The identifier you have chosen is available</message>
		// <identifier>physicalgeograp00maurAA</identifier>
		// </result>

	}

	/* ----------------------------
	 * Function: _get_derivative_urls()
	 *
	 * Parameters:
	 *    $id - The Internet Archive ID of the book in question
	 *
	 * Gets a list of all of the files from the IDENTIFIER_files.xml file
	 * and determines the "base" of the URL, which is now always
	 *   https://www.archive.org/download/IDENTIFIER
	 * ---------------------------- */
	function _get_derivative_urls($id) {
		$base = "https://archive.org/download/$id";
		$files = array();
		$xml = file_get_contents($base.'/'.$id."_files.xml");
		$xml = simplexml_load_string($xml);
		foreach ($xml->file as $f) {
			$attrs = $f->attributes();
			$files[] = (string)$attrs['name'];
		}
		return array($base, $files);		
	}

	/* ----------------------------
	 * Function: _get_books()
	 *
	 * Parameters:
	 *    $status: The status of the items we are interested in
	 *
	 * Get those books that need to be uploaded by searching for those that are
	 * ready to be uploaded (item.status_code = 'reviewed') and have not yet been
	 * uploaded (item_export_status.status_code is blank or <whatever $status is>).
	 * ---------------------------- */
	function _get_books($status) {
		$sql = "select i.id
			from item i
			  left outer join (select * from item_export_status where export_module = 'Internet_archive') e on i.id = e.item_id
			  left outer join custom_internet_archive cia on i.id = cia.item_id
			where (cia.item_id is null or e.status_code ".($status == 'NULL' ? "is null" : "in ('".$status."')").")
			  and i.status_code in ('reviewed','exporting');";

		if ($status == 'verified' || $status == 'uploaded') {
			$sql = "select i.id
				from item i
				  inner join (select * from item_export_status where export_module = 'Internet_archive') e on i.id = e.item_id
				  left outer join custom_internet_archive cia on i.id = cia.item_id
				where (cia.item_id is null or e.status_code ".($status == 'NULL' ? "is null" : "in ('".$status."')").")
				  and i.status_code in ('reviewed','exporting');";
		}

		$query = $this->CI->db->query($sql);
		$ids = array();
		if ($this->CI->db->count_all_results() > 0) {
			foreach ($query->result() as $row) {
				array_push($ids, $row->id);
			}
			if (count($ids)) {
				$sql = 'select * from item where id in ('.implode(',', $ids).') order by date_review_end;';

				$books = $this->CI->db->query($sql);
				return $books->result();
			}
		}
		return array();
	}

	/* ----------------------------
	 * Function: _check_custom_table()
	 *
	 * Parameters:
	 *
	 * Makes sure that the CUSTOM_INTERNET_ARCHIVE table exists in the database.
	 * ---------------------------- */
	function _check_custom_table() {
		if (!$this->CI->db->table_exists('custom_internet_archive')) {
			$this->CI->load->dbforge();
			$this->CI->dbforge->add_field(array(
				'item_id' =>    array( 'type' => 'int'),
				'identifier' => array( 'type' => 'varchar', 'constraint' => '32' )
			));
			$this->CI->dbforge->create_table('custom_internet_archive');
		}
		if (!$this->CI->db->table_exists('custom_internet_archive_keys')) {
			$this->CI->load->dbforge();
			$this->CI->dbforge->add_field(array(
				'org_id' =>    array( 'type' => 'int'),
				'access_key' => array( 'type' => 'varchar', 'constraint' => '64' ),
				'secret' => array( 'type' => 'varchar', 'constraint' => '64' )
			));
			$this->CI->dbforge->create_table('custom_internet_archive_keys');
		}
	}
	
	/* ----------------------------
	 * Function: _get_ia_keys()
	 *
	 * Parameters:
	 *
	 * Given an organization ID, go to the custom IA table and get the access keys for
	 * uploading to IA.
	 * ---------------------------- */
	function _get_ia_keys($org_id) {
		$query = $this->CI->db->query('select access_key, secret from custom_internet_archive_keys where org_id = '.$org_id);
		foreach ($query->result() as $row) {
			$this->access = $row->access_key;
			$this->secret = $row->secret;
			return;
		}
	}

	function imagick_jp2_library() {
		$m = new Imagick();
		$v = $m->getVersion();
		preg_match('/ImageMagick (\d+\.\d+\.\d+-?\d+)/', $v['versionString'], $v);
		if (count($v) == 0) {
			$v = $m->getVersion();
			preg_match('/ImageMagick (\d+\.\d+\.\d+)/', $v['versionString'], $v);
		}
		if(version_compare($v[1],'6.8.8-2') < 0){
			return "JasPer";
		} else {
			return "OpenJPEG";
		}
	
	}

	function create_zip($files = array(), $destination = '', $working_dir = '', $overwrite = false) {
		// if the zip file already exists and overwrite is false, return false
		if (file_exists($destination) && !$overwrite) {
			return false;
		}
		// We don't want giant paths!
		$curdir = getcwd();
		if ($working_dir) {
				chdir($working_dir);   
		}
		$valid_files = array();
		//if files were passed in...
		if (is_array($files)) {
			//cycle through each file
			foreach ($files as $file) {
				//make sure the file exists
				if (file_exists($file)) {
					$valid_files[] = $file;
				}
			}
		}
		// if we have good files...
		if (count($valid_files)) {
			//create the archive
			$zip = new ZipArchive();
			if ($zip->open($destination,$overwrite ? ZIPARCHIVE::OVERWRITE : ZIPARCHIVE::CREATE) !== true) {
				return false;
			}
			//add the files
			foreach ($valid_files as $file) {
				$zip->addFile($file,$file);
			}
			// close the zip -- done!
			$zip->close();
			// check to make sure the file exists
			chdir($curdir);
			return file_exists($destination);
		}	else {
			return false;
		}
	}

	function count_exports($search = null) {
		// --------------------------------------
		// Count how many are running, remember we count as one process
		// --------------------------------------
		$commands = array();
		$pid = getmypid().'';
		$found = 0;
		if (!$search) {
			$search = "export ".basename(__FILE__, '.php'); 
		}

		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
			// Windows will be always be limited to 1.
			exec("tasklist | FIND \"php\"", $commands);
			$search = "php.exe";
		} else {
			exec("ps -fe | grep -v sudo | grep php", $commands);
		}
		// print_r($commands);
		if (count($commands) > 0) {
			foreach ($commands as $command) {
				if (strpos($command, $search) > 0 && strpos($command, $pid) == 0) {
					$found++;
				}
			}
		}
		return $found;
	}
}
