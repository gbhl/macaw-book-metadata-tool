<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
// ***********************************************************
// Macaw Metadata Collection and Workflow System
//
// EXPORT LIBRARY
//
// Each destination with whom we share our book will have an export routine
// which contains functions for sending data to the system, verifying receipt
// of the data, and optionally pulling any derivative data, etc.
// Each Export library corresponds to an entry in the macaw.php file.
//
// Each module has a library with the name "Export_Name.php". The name
// must correspond to one of the items in the export_modules entry in the macaw.php
// configuration file. Each must contain ax export() methox. Other functions
// may be used if necessary.
//
// Each module must set a "completed" status to the export process via the
// Book objects set_export_status() method:
//
//     $this->CI->book->set_export_status('completed');
//
// Other statuses are allowed if the exporting happens in multiple steps.
// This module is required to maintan the statuses and eventually set a
// status of 'completed' when it's finished exporting. Once all export
// modules have marked the item as completed, Macaw then proceeds to archive
// and purge the data on its own schedule, if such routines are set up.
//
// CONNECTION PARAMETERS
//
// To connect to the Internet Archive, you need your login credentials. The 
// API Key and Secret should be set in the config/macaw.php file.
// 
// The variables are:
//
//    $config['macaw']['internet_archive_access_key'] = "";
// 	  $config['macaw']['internet_archive_secret'] = "";
//
// OTHER NOTES
//
// Imagemagick MUST be compiled with JPEG2000 support.
//     For macports, this is: port install imagemagick +jpeg2
//     For standard, this is: ./configure --with-jpeg2 (and whatever other options you have)
//
// The Jasper JPEG200 ibrary should be installed, too. Duh.
// ***********************************************************
include ('Archive/Tar.php');

// SYNOPSIS
// Run the entire harvest/verify/export routine for all items
// 		sudo -u www php index.php cron export Internet_archive
//
// Run the harvest/verify/export routine for one item (supplying the item id)
// 		sudo -u www php index.php cron export Internet_archive 123
//
// Run export routine for just one file of one item
// 		sudo -u www php index.php cron export Internet_archive 123 marc
// 		sudo -u www php index.php cron export Internet_archive 123 scans
// 		sudo -u www php index.php cron export Internet_archive 123 scandata
//
// Alternatively, we can force it to run all of the files using:
// 		sudo -u www php index.php cron export Internet_archive 123 force


class Internet_archive extends Controller {

	// This info is from account at Internet Archive. Change one, change them all.
	private $access = '';
	private $secret = '';

	private $send_orig_jp2 = "no"; // "yes", "no", or "both" This creates faster uploads when false (larger files/slower uploads when true)
	private $timing = false; // This makes more noise about how long things are taking.
	private $download_extensions = array('_djvu.xml','_abbyy.gz'); // What must be online and we can ignore if missing? And what do we download?
	private $required_extensions = array('_jp2.zip', '_marc.xml', '_scandata.xml'); // What must be online and we can't ignore?

	var $CI;
	var $cfg;
	var $cookie_jar;

	// ----------------------------
	// Function: CONSTRUCTOR
	//
	// Be sure to rename this from "Export_Generic" to whatever you named the
	// class above. Othwerwise, ugly things will happen. You don't need to edit
	// anything here, either.
	// ----------------------------
	function Internet_archive() {
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

	// ----------------------------
	// Function: export()
	//
	// Parameters:
	//    $args - An array of items passed from the command line (or URL)
	//            that are specific to this module. The Export Mode
	//            simply passes these in as the were received.
	//
	// Simply calls the other functions to interact with Internet Archive.
	// ----------------------------
	function export($args) {
		// We REALLY need this table to exist, but this can be run only once per session
		// due to ornery caching on the part of the DB module.
		$this->_check_custom_table();

		// Auto-upgrade for the new features
		$this->CI->db->query("update item_export_status set status_code = 'verified_upload' where status_code = 'verified';");

		// Since the Internet Archive upload does multiple things, this
		// method simply calls the other methods in order.
		$this->harvest($args);
		$this->verify_uploaded($args);
		$this->verify_derived($args);
		$this->upload($args);
	}


	// ----------------------------
	// Function: export()
	//
	// Parameters:
	//    $args - An array of items passed from the command line (or URL)
	//            that are specific to this module. The Export Mode
	//            simply passes these in as the were received.
	//
	// Sends everything to the Internet Archive. This function is called by the
	// export() method above.
	// ----------------------------
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


		// Cycle through these items
		foreach ($books as $b) {
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
					$metadata['x-archive-meta-identifier'] = $id;
				} else {
					$message = "Error processing export.\n\n".
						"Identifier:    ".$bc."\n\n".
						"IA Identifier: ".$id."\n\n".
						"Error Message: Could not get metadata for item with barcode ".$bc.". Check the MARC or MODS data.\n\n".
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
									$tqual++;
									if ($tqual == 101) {
										break;
									}
									print " $fs is too small (Q=$tqual)";
									unlink($jp2path.'/'.$new_filebase.'.jp2');
									$p2 = clone $preview;
									$p2->setCompressionQuality($tqual);
									$p2->setImageCompressionQuality($tqual);
									$p2->writeImage($jp2path.'/'.$new_filebase.'.jp2');
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
						// Create the ZIP object
						$zip = new ZipArchive(); // name of archive
						if ($zip->open($archive_file, ZIPARCHIVE::CREATE) !== TRUE) {
							exit("cannot open <$archive_file>\n");
						}
						// Make sure we are in the right directory
						chdir($basepath.'/Internet_archive/'.$id.'/');
						// We only add things that we are interested in, in this case, the entire directory (files AND directory)
						$zip->addFile($id.'_jp2/');
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
					write_file($fullpath.'/'.$id.'_marc.xml', $this->_create_marc_xml());
					$this->CI->logging->log('book', 'debug', 'Created '.$id.'_marc.xml', $bc);
				} // if ($file == '' || $file == 'marc')

				// create the IDENTIFIER_IDENTIFIER_dc.xml file
				// TODO: Do we need this?
				// write_file($fullpath.'/'.$id.'_dc.xml', _create_dc_xml($b));
				// $this->CI->logging->log('book', 'debug', 'Created '.$fullpath.'/'.$id.'_dc.xml', $bc);

				if ($file == '' || $file == 'scandata') {
					// create the IDENTIFIER_scandata.xml file
					write_file($fullpath.'/'.$id.'_scandata.xml', $this->_create_scandata_xml($id, $this->CI->book, $pages));
					$this->CI->logging->log('book', 'debug', 'Created '.$id.'_scandata.xml', $bc);
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
					$cmd .= ' --upload-file "'.$fullpath.'/'.$id.'_scandata.xml" "http://s3.us.archive.org/'.$id.'/'.$id.'_scandata.xml"';
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
							echo "ERROR!!!";
							// If we had any sort of error from exec, we log what happened and set the status to error
							$out = '';
							foreach ($output as $o) {
								$out .= $o."\n";
							}
							$this->CI->book->set_status('error');
							$this->CI->logging->log('book', 'error', 'Call to CURL returned non-zero value for uploading metadata. Output was:'."\n".$out, $bc);
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
					$cmd .= ' --upload-file "'.$fullpath.'/'.$id.'_scandata.xml" "http://s3.us.archive.org/'.$id.'/'.$id.'_scandata.xml"';
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
							echo "ERROR!!!";
							// If we had any sort of error from exec, we log what happened and set the status to error
							$out = '';
							foreach ($output as $o) {
								$out .= $o."\n";
							}
							$this->CI->book->set_status('error');
							$this->CI->logging->log('book', 'error', 'Call to CURL returned non-zero value for scandata.xml. Output was:'."\n".$out, $bc);
							return;
						} // if ($ret)
					} else {
						echo "IN TEST MODE. NOT UPLOADING.\n\n";
					} // if (!$this->cfg['testing'])
				} // if ($file == '' || $file == 'scandata')

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
						"Command: \n\n".$cmd."\n\n".
						"Output: \n\n".$output_text."\n\n";
					$this->CI->common->email_error($message);
					continue;
				}
				echo "\n";

				if ($file == '' || $file == 'marc') {
					$cmd = $this->cfg['curl_exe'];
					$cmd .= ' --location';
					$cmd .= ' --header "authorization: LOW '.$this->access.':'.$this->secret.'"';
					$cmd .= ' --header "x-archive-queue-derive:0"';
					$cmd .= ' --upload-file "'.$fullpath.'/'.$id.'_marc.xml" "http://s3.us.archive.org/'.$id.'/'.$id.'_marc.xml"';
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
							echo "ERROR!!!";
							// If we had any sort of error from exec, we log what happened and set the status to error
							$out = '';
							foreach ($output as $o) {
								$out .= $o."\n";
							}
							$this->CI->book->set_status('error');
							$this->CI->logging->log('book', 'error', 'Call to CURL returned non-zero value for marc.xml. Output was:'."\n".$out, $bc);
							return;
						}
					} else {
						echo "IN TEST MODE. NOT UPLOADING.\n\n";
					} // if (!$this->cfg['testing'])
				} //if ($file == '' || $file == 'marc')

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
						$cmd .= ' --upload-file "'.$fullpath.'/'.$id.'_jp2.zip" "http://s3.us.archive.org/'.$id.'/'.$id.'_jp2.zip"';
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
								echo "ERROR!!!";
								// If we had any sort of error from exec, we log what happened and set the status to error
								$out = '';
								foreach ($output as $o) {
									$out .= $o."\n";
								}
								$this->CI->book->set_status('error');
								$this->CI->logging->log('book', 'error', 'Call to CURL returned non-zero value for tar or ZIP file. Output was:'."\n".$out, $bc);
								return;
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
						$cmd .= ' --upload-file "'.$fullpath.'/'.$id.'_orig_jp2.tar" "http://s3.us.archive.org/'.$id.'/'.$id.'_orig_jp2.tar"';
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
								echo "ERROR!!!";
								// If we had any sort of error from exec, we log what happened and set the status to error
								$out = '';
								foreach ($output as $o) {
									$out .= $o."\n";
								}
								$this->CI->book->set_status('error');
								$this->CI->logging->log('book', 'error', 'Call to CURL returned non-zero value for tar or ZIP file. Output was:'."\n".$out, $bc);
								return;
							}
						} else {
							echo "IN TEST MODE. NOT UPLOADING.\n\n";
						} // if (!$this->cfg['testing'])
					} // if ($this->send_orig_jp2 == 'yes' || $this->send_orig_jp2 == 'both')
				} // if ($file == '' || $file == 'scans')

				// If we got this far, we were completely successful. Yay!

				// TODO Update uploaded date field in the item table
				$this->CI->book->set_export_status('uploaded');
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
			
		} // foreach ($books as $b)
	} // function upload($args)

	// ----------------------------
	// Function: verify()
	//
	// Parameters:
	//    $args - An array of items passed from the command line (or URL)
	//            that are specific to this module. The calling export
	//            controller simply passes these in as the were received.
	// ----------------------------
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
				$metadata['x-archive-meta-identifier'] = $id;
			} else {
				$message = "Error processing export.\n\n".
					"Identifier:    ".$b->barcode."\n\n".
					"IA Identifier: ".$id."\n\n".
					"Error Message: Could not get metadata for item with barcode ".$b->barcode.". Check the MARC or MODS data.\n";
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

			// We check a list of all files to determine if they are there. If they
			// are, then the item was uploaded and processed successfully.
			$verified = 1;
			$error = '';
			foreach ($this->required_extensions as $ext) {
				if (!in_array($id.$ext, $urls[1])) {
					if (!$error) {
						$error = '('.$ext.' file not found)';
					}
// 					$this->CI->logging->log('book', 'info', 'Item failed to upload to internet archive. ('.$ext.' file not found)', $b->barcode);
// 					$this->CI->logging->log('access', 'info', 'Item with barcode '.$b->barcode.' failed to upload to internet archive. ('.$ext.' file not found)');
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
				$metadata['x-archive-meta-identifier'] = $id;
			} else {
				$message = "Error processing export.\n\n".
					"Identifier:    ".$b->barcode."\n\n".
					"IA Identifier: ".$id."\n\n".
					"Error Message: Could not get metadata for item with barcode ".$b->barcode.". Check the MARC or MODS data.\n";
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
// 					$this->CI->logging->log('book', 'info', 'Item NOT verified at internet archive. ('.$ext.' file not found)', $b->barcode);
// 					$this->CI->logging->log('access', 'info', 'Item with barcode '.$b->barcode.' NOT verified at internet archive. ('.$ext.' file not found)');
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


	// ----------------------------
	// Function: harvest($args)
	//
	// Parameters:
	//    $args - An array of items passed from the command line (or URL)
	//            that are specific to this module. The calling export
	//            controller simply passes these in as the were received.
	//
	// Copy back from the internet archive anything that they may have created that
	// we would be interested in.
	// ----------------------------
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
				$metadata['x-archive-meta-identifier'] = $id;
			} else { 
				$message = "Error processing export.\n\n".
					"Identifier:    ".$b->barcode."\n\n".
					"IA Identifier: ".$id."\n\n".
					"Error Message: Could not get metadata for item with barcode ".$b->barcode.". Check the MARC or MODS data.\n";
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
								system($cmd);
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

	// ----------------------------
	// Function: missing()
	//
	// Parameters:
	//    NONE
	//
	// Submits missing pages for export. If the export module doesn't accept
	// missing pages, then this function should do nothing but return true.
	// ----------------------------
	function missing() {
		// 7.	Update the Export Upload procedure to:
		// a.	If the item's date uploaded field has a value then we need to send up only the changed pages.
		//      This will be different for each export module.
		// b.	Update the system to have a method of saving a persistent data for every export module and
		//      independent of each. The form of the table would be similar to that of the metadata table and
		//      would be loaded and made available automatically to the Export Module.
		// TODO: This really needs to be addressed better.
	}

	// ----------------------------
	// Function: _create_scandata_xml()
	//
	// Parameters:
	//    $id: The ID of the item as determined earlier
	//    $book: A book object
	//    $pages: The pages from the book (assume they were gathered earlier)
	//
	// Returns the XML for the scandata.xml file. Does not create the file.
	// This is specific to Internet Archive but is left here as a reminder.
	// This should call Book.get_item_metadata().
	// ----------------------------
	function _create_scandata_xml($id, $book, $pages) {

		$this->CI->load->library('image_lib');

		$dpi = $this->_get_dpi($book, $pages);

		$output = '<book>'."\n";
		$output .= ' <bookData>'."\n";
		$output .= '  <bookId>'.$id.'</bookId>'."\n";
		$output .= '  <leafCount>'.count($pages).'</leafCount>'."\n";
		$output .= '  <dpi>'.$dpi.'</dpi>'."\n";
		if ($book->page_progression == 'rtl') {
			$output .= '  <globalHandedness>'."\n";
			$output .= '    <page-progression>rl</page-progression>'."\n";
			$output .= '    <scanned-right-to-left>true</scanned-right-to-left>'."\n";
			$output .= '    <scanned-upside-down>false</scanned-upside-down>'."\n";
			$output .= '    <needs-rectification>false</needs-rectification>'."\n";
			$output .= '  </globalHandedness>'."\n";
		}
		$output .= '  <pageNumData>'."\n";
		$c = 1;
		foreach ($pages as $p) {
			if (property_exists($p, 'page_number')) {
				if ($p->page_number) {
					$output .= '    <assertion>'."\n";
					$output .= '  	<leafNum>'.$c.'</leafNum>'."\n";
					$output .= '  	<pageNum>'.$p->page_number.'</pageNum>'."\n";
					$output .= '    </assertion>'."\n";
				}
			}
			$c++;
		}
		$output .= '  </pageNumData>'."\n";
		$output .= ' </bookData>'."\n";
		$output .= ' <pageData>'."\n";

		$c = 1;

		foreach ($pages as $p) {
			$output .= '  <page leafNum="'.$c.'">'."\n";
			// Basic Info
			if ($c == 1) {
				$output .= '    <bookStart>true</bookStart>'."\n";
			}
			if (property_exists($p, 'page_type')) {
				$output .= '    <pageType>'.$this->_get_pagetype($p->page_type).'</pageType>'."\n";
			} else {
				$output .= '    <pageType>Normal</pageType>'."\n";
			}
			$output .= '    <addToAccessFormats>true</addToAccessFormats>'."\n";
			$output .= '    <origWidth>'.$p->width.'</origWidth>'."\n";
			$output .= '    <origHeight>'.$p->height.'</origHeight>'."\n";

			// Crop Box
			$output .= '    <cropBox>'."\n";
			$output .= '      <x>0</x>'."\n";
			$output .= '      <y>0</y>'."\n";
			$output .= '      <w>'.$p->width.'</w>'."\n";
			$output .= '      <h>'.$p->height.'</h>'."\n";
			$output .= '    </cropBox>'."\n";

			// Page Number
			if (property_exists($p, 'page_number')) {
				if ($p->page_number) {
					if (preg_match('/(and|,)/', $p->page_number)) {
						$pagenums = preg_split('/(and|,)/', $p->page_number);
						$output .= '    <pageNumber>'.trim($pagenums[0]).'</pageNumber>'."\n";
					} else {
						$output .= '    <pageNumber>'.$p->page_number.'</pageNumber>'."\n";
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
				$output .= '    <altPageNumbers>'."\n";
				if (preg_match('/(and|,)/', $p->page_number)) {
					$pagenums = preg_split('/(and|,)/', $p->page_number);
					foreach ($pagenums as $pgnum) {
						$output .= '      <altPageNumber prefix="'.$prefix.'"'.($implied ? ' implied="1"' : '').'>'.trim($pgnum).'</altPageNumber>'."\n";
					}
				} else {
					$output .= '      <altPageNumber prefix="'.$prefix.'"'.($implied ? ' implied="1"' : '').'>'.$p->page_number.'</altPageNumber>'."\n";
				}
				$output .= '    </altPageNumbers>'."\n";
			}

			// Recto/Verso
			if (property_exists($p, 'page_side')) {
				if ($p->page_side) {
					if (preg_match('/Left/i', $p->page_side)) {
						$output .= '    <handSide>LEFT</handSide>'."\n";
					} elseif (preg_match('/Right/i', $p->page_side)) {
						$output .= '    <handSide>RIGHT</handSide>'."\n";
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
			$output .= '    <altPageTypes>'."\n";
			foreach ($page_types as $pt) {
				$output .= '      <altPageType>'.$pt.'</altPageType>'."\n";
			}
			$output .= '    </altPageTypes>'."\n";

			// Caption, because we can
			if (property_exists($p, 'caption')) {
				if ($p->caption) {
					$output .= '    <caption>'.$p->caption.'</caption>'."\n";
				}
			}

			// Volume
			if (property_exists($p, 'volume')) {
				if ($p->volume) {
					$output .= '    <volume>'.$p->volume.'</volume>'."\n";
				}
			}

			// Piece information (choose the most important single item, XML only supports one.)
			// These are in order of importance. Issue, Number, then Part
			if (property_exists($p, 'piece')) {
				if (count($p->piece)) {
					$i = array_search('Issue', $p->piece);
					if (isset($i)) {
						$output .= '    <piece prefix="'.$p->piece[$i].'">'.$p->piece_text[$i].'</piece>'."\n";
					} else {
						$i = array_search('No.', $p->piece);
						if (isset($i)) {
							$output .= '    <piece prefix="'.$p->piece[$i].'">'.$p->piece_text[$i].'</piece>'."\n";
						} else {
							$i = array_search('Part', $p->piece);
							if (isset($i)) {
								$output .= '    <piece prefix="'.$p->piece[$i].'">'.$p->piece_text[$i].'</piece>'."\n";
							}
						}
					}
				}
			}

			// Year
			if (property_exists($p, 'year')) {
				if ($p->year) {
					$output .= '    <year>'.$p->year.'</year>'."\n";
				}
			}

			$output .= '  </page>'."\n";
			$c++;
		}

		$output .= ' </pageData>'."\n";
		$output .= '</book>';
		return $output;
	}

	// ----------------------------
	// Function: _get_bhl_pagetypes()
	//
	// Parameters:
	//    $p: A page from a book in Macaw
	//
	// Translates the page type values for one page into an array of pagetypes
	// suitable for BHL. This is sent in addition to the page type data for
	// internet archive.
	//
	// TODO: get these translations from my notes
	// ----------------------------
	function _get_bhl_pagetypes($p) {
		for ($i=0; $i < count($p); $i++) {
			if ($p[$i] == 'Appendix') { $p[$i] = 'Appendix';}
			elseif ($p[$i] == 'Article start') { $p[$i] = 'Article Start'; }
			elseif ($p[$i] == 'Article end') { $p[$i] = 'Issue End'; }
			elseif ($p[$i] == 'Blank') { $p[$i] = 'Blank'; }
			elseif ($p[$i] == 'Bibliography') { $p[$i] = 'Text'; }
			elseif ($p[$i] == 'Copyright') { $p[$i] = 'Text'; }
			elseif ($p[$i] == 'Cover') { $p[$i] = 'Cover'; }
			elseif ($p[$i] == 'Fold Out') { $p[$i] = 'Foldout'; }
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
			elseif ($p[$i] == 'Table') { $p[$i] = 'Table'; }
			elseif ($p[$i] == 'Suppress') { $p[$i] = 'Delete'; }
			elseif ($p[$i] == 'Tissue') { $p[$i] = 'Delete'; }
			elseif ($p[$i] == 'White card') { $p[$i] = 'Delete'; }
			elseif ($p[$i] == 'Color card') { $p[$i] = 'Delete'; }
			else { $p[$i] = 'Text'; }
		}
		return $p;
	}

	// ----------------------------
	// Function: _get_pagetype()
	//
	// Parameters:
	//    $t: A page type from Macaw
	//
	// Translates a page type value into something more suitable for Internet
	// Archive.
	//
	// TODO: get these translations from my notes
	// ----------------------------
	function _get_pagetype($t) {
		if (in_array('Cover', $t)) {
			return 'Cover';

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

	// ----------------------------
	// Function: _get_dpi()
	//
	// Parameters:
	//    $book: A book object
	//    $pages: The pages in the book (the pages should have been retrieved already)
	//
	// Uses the page metadata and the MARC information to estimate the DPI of the
	// scanned pages based on the pixel dimensions of the first image it can find
	// and the measurement of the height of the book from the MARC record. This is
	// quirky and really only gives a good guess as to the DPI. If all fails, we
	// return 450.
	// ----------------------------
	function _get_dpi($book, $pages) {
		// Get our mods into something we can use. All the info we need is in there.
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
				
				print "height is $height\n";
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
				} elseif (preg_match('/(\d+)/', $height, $matches)) {
					// Fallback, take the first number we can find.
					$height = $matches[1];
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
		$marc = simplexml_load_string($marcxml);
		if ($marc === false) {
			return false;
		} else {
			return $marc;
		}
	}
	// ----------------------------
	// Function: _get_metadata()
	//
	// Parameters:
	//    $id: The identifer of the item in question
	//
	// Standard function for creating an array of internet-archive-specific
	// metadat elements to be used when uploading an item to IA.
	// ----------------------------
	function _get_metadata() {
		// Get our mods into something we can use. All the info we need is in there.
		$mods = simplexml_load_string($this->CI->book->get_metadata('mods_xml'));
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
		$metadata = array();
		// This is easy, hardcoded
		$metadata['x-archive-meta-mediatype'] = 'texts';

		// Contributor: Prefer the entered metadata, then the item's organization, then the hardcoded organization
		$metadata['x-archive-meta-contributor'] = $this->CI->book->get_contributor();

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
		if ($this->CI->book->get_metadata('copyright') == '0' || strtoupper($this->CI->book->get_metadata('copyright')) == 'F' ) {
			if ($bhl == 1) {
				$metadata['x-archive-meta-possible-copyright-status'] = "Public domain. The BHL considers that this work is no longer under copyright protection.";
				if (isset($metadata['x-archive-meta-licenseurl'])) {
					unset($metadata['x-archive-meta-licenseurl']);
				}
			} else {
				$metadata['x-archive-meta-possible-copyright-status'] = "Public domain. The Library considers that this work is no longer under copyright protection";
			}

		// Handle copyright - Permission Granted to Scan
		} elseif ($this->CI->book->get_metadata('copyright') == '1'  || strtoupper($this->CI->book->get_metadata('copyright')) == 'T' ) {
			$metadata['x-archive-meta-possible-copyright-status'] = "In copyright. Digitized with the permission of the rights holder.";
			$metadata['x-archive-meta-licenseurl'] = 'http://creativecommons.org/licenses/by-nc-sa/4.0/';
			$metadata['x-archive-meta-rights'] = 'http://biodiversitylibrary.org/permissions';

		// Handle copyright - Due Dillegene Performed to determine public domain status
		} elseif ($this->CI->book->get_metadata('copyright') == '2') {
			$metadata['x-archive-meta-possible-copyright-status'] = "No known copyright restrictions as determined by scanning institution.";
			$metadata['x-archive-meta-due-diligence'] = 'http://biodiversitylibrary.org/permissions';
			$metadata['x-archive-meta-duediligence'] = 'http://biodiversitylibrary.org/permissions';
			if (isset($metadata['x-archive-meta-licenseurl'])) {
				unset($metadata['x-archive-meta-licenseurl']);
			}

		// Handle copyright - Default, we hope we never hit this
		} else {
			$metadata['x-archive-meta-possible-copyright-status'] = $this->CI->book->get_metadata('copyright');
		}

		// Now we use xpath to get stuff out of the mods. Fun!
		$ret = ($mods->xpath($root.$ns."titleInfo[not(@type)]/".$ns."title"));
		if ($ret && count($ret) > 0) {
			$metadata['x-archive-meta-title'] = str_replace("'", "&quot;", str_replace('"', "'", $ret[0].''));
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
			$metadata['x-archive-meta-genre'] = str_replace("'", "&quot;", str_replace('"', "'", $ret[0].''));
		}

		// Abstract
		$ret = ($mods->xpath($root.$ns."abstract"));
		if ($ret && count($ret) > 0) {
			$metadata['x-archive-meta-abstract'] = str_replace("'", "&quot;", str_replace('"', "'", $ret[0].''));
		}

		//modified JC 4/2/12
		if ($this->CI->book->get_metadata('year')) {
			$metadata['x-archive-meta-date'] = $this->CI->book->get_metadata('year').'';
			$metadata['x-archive-meta-year'] = $this->CI->book->get_metadata('year').'';
		// LEGACY? Remove this? 
		} elseif ($this->CI->book->get_metadata('pub_date')) {
			$metadata['x-archive-meta-date'] = $this->CI->book->get_metadata('pub_date').'';
			$metadata['x-archive-meta-year'] = $this->CI->book->get_metadata('pub_date').'';
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

		if ($this->CI->book->get_metadata('volume')) {
			$metadata['x-archive-meta-volume'] = $this->CI->book->get_metadata('volume').'';
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

// 		$ret = ($mods->xpath($root.$ns."note"));
// 		$c = 0;
// 		if ($ret && is_array($ret)) {
// 			foreach ($ret as $r) {
// 				$str = '';
// 				if ($r['type']) {
// 					$str = $r['type'].': '.$r;
// 				} else {
// 					$str = $r.'';
// 				}
// 				if ($str) {
// 					$metadata['x-archive-meta'.sprintf("%02d", $c).'-description'] = str_replace('"', '\\"', $str);
// 					$c++;
// 				}
// 			}
// 		}
		
		$tm = time();
		if (isset($this->CI->book->date_review_end) && $this->CI->book->date_review_end != '0000-00-00 00:00:00') {
			$tm = strtotime($this->CI->book->date_review_end);
		} else if (isset($this->CI->book->date_created) && $this->CI->book->date_created != '0000-00-00 00:00:00') {
			$tm = strtotime($this->CI->book->date_created);		
		}
		if ($tm > 0) {
			$metadata['x-archive-scandate'] = date('YmdHis', $tm);
		}

		// Some data comes from MARC
		$marc = $this->_get_marc($this->CI->book->get_metadata('marc_xml'));
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
				$metadata['x-archive-meta-location'] = str_replace("'", "&quot;", str_replace('"', "'", $ret[0].''));
			}
	
			// collection-number
			$ret = ($marc->xpath($root.$ns."record/".$ns."datafield[@tag='852']/".$ns."subfield[@code='b']"));
			if ($ret && count($ret) > 0) {
				$metadata['x-archive-meta-collection-number'] = str_replace("'", "&quot;", str_replace('"', "'", $ret[0].''));
			}
	
			// sublocation
			$ret = ($marc->xpath($root.$ns."record/".$ns."datafield[@tag='852']/".$ns."subfield[@code='c']"));
			if ($ret && count($ret) > 0) {
				$metadata['x-archive-meta-sublocation'] = str_replace("'", "&quot;", str_replace('"', "'", $ret[0].''));
			}

			// Rights Holder
			if ($this->CI->book->get_metadata('rights_holder')) {
				$metadata['x-archive-meta-rights-holder'] = $this->CI->book->get_metadata('rights_holder');
			}
			
			// Scanning Institution
			if ($this->CI->book->get_metadata('scanning_institution')) {
				$metadata['x-archive-meta-scanning-institution'] = $this->CI->book->get_metadata('scanning_institution');
			}

			// Copy Specific Information
			if ($this->CI->book->get_metadata('copy_specific_information')) {
				$metadata['x-archive-meta-copy-specific-information'] = $this->CI->book->get_metadata('copy_specific_information');
			}

			return $metadata;
		} else {
			return null;
		}

		return $metadata;
	}

	// ----------------------------
	// Function: _create_marc_xml()
	//
	// Parameters:
	//    NONE
	//
	// Returns the XML for the meta.xml file. Does not create the file. This
	// is specific to Internet Archive but is left here as a reminder. This
	// should call Book.get_marc_xml().
	// ----------------------------
	function _create_marc_xml() {
		// Just get the MARC XML from the book and format the XML file properly
		$marc = $this->CI->book->get_metadata('marc_xml');
// 		if (!preg_match("/<\?xml.*?\/>/", $marc)) {
// 			return '<?xml version="1.0" encoding="UTF-8" ?'.'>'."\n".$marc;
// 		} else {
			return $marc;
// 		}
	}

	// ----------------------------
	// Function: identifier()
	//
	// Parameters:
	//    $book: The book record from the database (used to create a book object)
	//
	// Given a book, use some algorithm to create a (hopefully) unique identifier
	// to use at Internet Archive. We'll go ahead and check here to make sure that
	// the identifier is unique by hitting a URL at IA.
	//
	// Note: this is not in the Book() model because the logic is specific to
	// internet archive. We're trying to mimic what they do:
	// 		TITLE(16chars)NUM(2chars)AUTHOR(4chars)
	//      example: gisassessmentofs05mack, chinaecosystemse08bubb, carbonindrylands08unep, progressreporton08cmss
	//
	// todo: make sure we are getting the volume or year properly. Should it come from the page?
	// ----------------------------
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

		// A counter to help make things unique
		$count = 0;
		$count2 = 0;

		// Get the title and author from MODS, sometimes it's not available on the book's metadata
		// Process the title
		
		$title = $this->_utf8_clean($metadata['x-archive-meta-title']);
		$title = preg_replace('/\b(the|a|an|and|or|of|for|to|in|it|is|are|at|of|by)\b/i', '', $title);
		$title = preg_replace('/[^a-zA-Z0-9]/', '', $title);
		$title = substr($title, 0, 15);

		// Process the author
		$author = '';
		if (isset($metadata['x-archive-meta-creator'])) {
			$author = $this->_utf8_clean($metadata['x-archive-meta-creator']);
			$author = substr(preg_replace('/[^a-zA-Z0-9]/', '', $author), 0, 4);
		}
		
		while ($count2 <= 26) {
			while ($count <= 26) {			
				// If we got to this point, we don't have an identifier. Make a new one.
				$number = '00';
				$pages = $this->CI->book->get_pages();
				// Get the volume number of the book
				foreach ($pages as $p) {
					if (property_exists($p, "volume") && $p->volume) {
						$number = $p->volume;
						break;
					}
				}
				$number = substr(preg_replace('/[^a-zA-Z0-9]/', '', $number), 0, 4);
	
	//			// We didn't get a volume, so let's check for a year
	// 			if ($number == '') {
	// 				foreach ($pages as $p) {
	// 					if ($p->year) {
	// 						// Add a couple of zeros and we'll take the last two digits, just to be safe
	// 						if (preg_match('/.+(\d{2,})$/', '00'.$p->year, $m)) { // get the last two digits of the number
	// 							$number = sprintf("%02d",$m[1]);
	// 						}
	// 						break;
	// 					}
	// 				}
	// 			}
	
				// Make this lowercase becuse SIL (and maybe others) uses it as a URL and URLs are case-insensitive (or should be)
				$identifier = strtolower($title.$number.$author);
	
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
		return '';
	}

	// ----------------------------
	// Function: _bucket_exists()
	//
	// Parameters:
	//    $id: The IA identifier we are testing for
	//
	// Makes an attempt to determine whether or not an item exists at internet
	// archive by checking the details page. If we get a 503 error or the string
	// "item cannot be found" appears on the page, then we assume that the s3
	// bucket does not exist. This is used in both making sure we aren't using an
	// identifier that already exists as well as for checking to see if the
	// bucket is created before uploading additional items to it.
	// ----------------------------
	function _bucket_exists($id) {
		if (!isset($this->curl)) {
			$this->curl = curl_init();
		}

		echo "\nChecking https://archive.org/services/check_identifier.php?identifier=$id ...";
		curl_setopt($this->curl, CURLOPT_URL, "https://archive.org/services/check_identifier.php?identifier=".$id);
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($this->curl, CURLOPT_HTTPGET, true);
		curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, true);
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

	// ----------------------------
	// Function: _get_derivative_urls()
	//
	// Parameters:
	//    $id - The Internet Archive ID of the book in question
	//
	// Gets a list of all of the files from the IDENTIFIER_files.xml file
	// and determines the "base" of the URL, which is now always
	//   http://www.archive.org/download/IDENTIFIER
	// ----------------------------
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

	// ----------------------------
	// Function: _utf8_clean()
	//
	// Parameters:
	//    $input: The string to clean
	//
	// Converts a string that may contain UTF characters to pure ASCII characters. This is
	// not a perfect solution, but anything else was overly complicated. Generally this is
	// needed when creating an identifer for use at internet archive.
	// ----------------------------
	function _utf8_clean($input) {
		return strtr(utf8_decode($input),
		           utf8_decode('ŠŒŽšœžŸ¥µÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝßàáâãäåæçèéêëìíîïðñòóôõöøùúûüýÿ'),
		           'SOZsozYYuAAAAAAACEEEEIIIIDNOOOOOOUUUUYsaaaaaaaceeeeiiiionoooooouuuuyy');

	}

	// ----------------------------
	// Function: _get_books()
	//
	// Parameters:
	//    $status: The status of the items we are interested in
	//
	// Get those books that need to be uploaded by searching for those that are
	// ready to be uploaded (item.status_code = 'reviewed') and have not yet been
	// uploaded (item_export_status.status_code is blank or <whatever $status is>).
	// ----------------------------
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
				$sql = 'select * from item where id in ('.implode($ids, ',').') order by date_review_end;';

				$books = $this->CI->db->query($sql);
				return $books->result();
			}
		}
		return array();
	}

	// ----------------------------
	// Function: _check_custom_table()
	//
	// Parameters:
	//
	// Makes sure that the CUSTOM_INTERNET_ARCHIVE table exists in the database.
	// ----------------------------
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
	
	// ----------------------------
	// Function: _get_ia_keys()
	//
	// Parameters:
	//
	// Given an organization ID, go to the custom IA table and get the access keys for
	// uploading to IA.
	// ----------------------------
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
}
