<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Utilities Controller
 *
 * MACAW Metadata Collection and Workflow System
 *
 * Various utilities, usually called from the command line.
 *
 **/
 
class Utils extends Controller {

	var $cfg;

	/**
	 * Function: Constructor
	 */
	function __construct() {
		parent::__construct();
		$this->cfg = $this->config->item('macaw');
	}

		/**
	 * Reset page image dimensions in the database
	 *
	 * CLI: For all books, reset the image sizes in the database 
	 * based on the images that are on disk, if they exist. This 
	 * is probably not used or needed anymore. Best not to use it.
	 * 
	 * Usage: 
	 *   sudo -u apache php index.php utils image_sizes 
	 *
	 * @since Version 1.1
	 */
	function image_sizes() {

		// Get the books
		$books = $this->book->get_all_books();

		// Loop through the books
		foreach ($books as $b) {
			$scanspath = $this->cfg['data_directory'].'/'.$b->barcode.'/scans';
			// Load the book
			$this->book->load($b->barcode);

			// get the pages
			$pages = $this->book->get_pages();

			// Loop through the pages
			foreach ($pages as $p) {
				$file = $scanspath.'/'.$p->scan_filename;
				echo "FILE IS ".$file."\n";
				if (!$p->width) {

					// Read each image, set the dimensions in the database
					if (file_exists($file)) {
						echo "...Updating"."\n";
						$preview = new Imagick($scanspath.'/'.$p->scan_filename);
						$this->common->get_largest_image($preview);

						$info = $preview->identifyImage();

						$data = array(
							'width' => $info['geometry']['width'],
							'height' => $info['geometry']['height'],
						);
						$this->db->where('id', $p->id);
						$this->db->update('page', $data);
					} else {
						echo "File doesn't exist!!!"."\n";
					}
				} else {
					echo "Already done."."\n";
				}
			}
		}
	}

	/**
	 * Log activity while reviewing
	 *
	 * AJAX: While scanning a book, it becomes useful to know what a user has done
	 * in detail in order to offer something for forensic analysis should
	 * something horrible go wrong. To this end, we've added a JS function that
	 * calls this /scan/log/ function to track a user's activities. The data
	 * sent here is passed directly to the standard Macaw logging function.
	 *
	 * POST Parameters are: pageid, field, value
	 *
	 * @since Version 1.1
	 */
	function log() {
		$data = json_decode($this->input->post('data'));

		$this->logging->log(
			'activity',
			'info',
			'Item='.$this->session->userdata('barcode').', Page='.$data->pageid.', Field='.$data->field.', Value='.$data->value
		);
		echo "Ok";
	}

	/**
	 * Reopen/reset an item
	 *
	 * CLI: Given a barcode on the command line, this will reset the item so that it can be
	 * re-uploaded to the internet archive. Options include those listed below to download JP2s from the
	 * Internet Archive or a local file to extract images from.
	 *
	 * Usage: 
	 *    sudo -u apache php index.php utils reset_item 3908808264355 
	 *    sudo -u apache php index.php utils reset_item 3908808264355 /path/to/SomeFilename_orig_tiff.tar
	 *    sudo -u apache php index.php utils reset_item 3908808264355 sftp://server/path/to/file.tar
	 *    sudo -u apache php index.php utils reset_item 3908808264355 internet_archive_pdf
	 *    sudo -u apache php index.php utils reset_item 3908808264355 internet_archive
	 * 
	 * @since Version 2.2
	 */
	function reset_item($barcode) {
		if (!$barcode) {
			echo "Please supply a barcode\n";
			die;
		}
		function reset_usage() {
			print "USAGE: sudo -u WWW_USER php index.php utils reset_item IDENTIFIER\n";
			print "       sudo -u WWW_USER php index.php utils reset_item IDENTIFIER PATH\n";
			print "       sudo -u WWW_USER php index.php utils reset_item IDENTIFIER SFTP://SERVER/PATH/TO/FILE\n";
			print "       sudo -u WWW_USER php index.php utils reset_item IDENTIFIER internet_archive_pdf\n";
			print "       sudo -u WWW_USER php index.php utils reset_item IDENTIFIER internet_archive\n";
			print "\n";
			print "IDENTIFIER is a Macaw Identifier, not Intenet Archive.\n";
			print "FILENAME can be a ZIP file or TAR archive of sequentially numbered files.\n";
			print "PATH must provide a directory with files that are sequentially numbered.\n";
		}
		if (strtolower($barcode) == 'help' || strtolower($barcode) == '--help'|| strtolower($barcode) == '-h') {
			reset_usage();
			return;
		}

		if (!$this->book->exists($barcode)) {
			echo "Item not found with barcode $barcode.\n";
			die;
		}

		$this->logging->log('book', 'info', 'Item reset from the command line.', $barcode);
		$db_true = (($this->db->dbdriver == 'postgre') ? "'t'" : '1');
		$db_false = (($this->db->dbdriver == 'postgre') ? "'f'" : '0');

		// Set the status to reviewing
		$this->book->load($barcode);
		echo "Setting status back to reviewing...\n";
		$this->db->query("update item set status_code = 'reviewing' where id = ".$this->book->id);

		// Delete the IA Export status
		echo "Clearing IA Export status...\n";
		$this->db->query("delete from item_export_status where item_id = ".$this->book->id." and export_module = 'Internet_archive'");

		// Do we need to copy metadata?
		$this->db->select('count(*) as thecount');
		$this->db->where('item_id', $this->book->id);
		$query = $this->db->get('metadata');
		$row = $query->row();
		if ($row->thecount == 0) {
			echo "Restoring metadata...\n";
			$this->db->query("insert into metadata (item_id, page_id, fieldname, counter, value, value_large) select item_id, page_id, fieldname, counter, value, value_large from metadata_archive where item_id = ".$this->book->id);
		} else {
			echo "Item has metadata, not restoring...\n";
		}

		// Delete the IA derived images on disk
		$this->db->select('identifier');
		$this->db->where('item_id', $this->book->id);
		$query = $this->db->get('custom_internet_archive');
		if (!$query->num_rows) {
			print "Could not get an IA Identifier!\n";
			return;
		}
		$identifier = '';
		if ($row = $query->row()) {
			if ($row->identifier) {
				$identifier = $row->identifier;
				if (file_exists($this->cfg['data_directory'].'/import_export/Internet_archive/'.$row->identifier)) {
					echo "Clearing IA Export files...\n";
					$cmd = 'rm -fr '.$this->cfg['data_directory'].'/import_export/Internet_archive/'.$row->identifier;
					`$cmd`;
				} else {
					echo "No IA Export files to clear...\n";
				}
			}
		}

		// Restore the directories that Macaw expects
 		echo "Recreating the directory structure ...\n";
 		$pth = $this->cfg['data_directory'].'/'.$barcode;
		if (!file_exists($pth)) {
			mkdir($pth);
			echo "books/$barcode created...\n";
		} else {
			echo "books/$barcode already exists...\n";
		}
		if (!file_exists($pth.'/thumbs')) {
			mkdir($pth.'/thumbs');
			echo "books/$barcode/thumbs created...\n";
		} else {
			echo "books/$barcode/thumbs already exists...\n";
		}
		if (!file_exists($pth.'/preview')) {
			mkdir($pth.'/preview');
			echo "books/$barcode/preview created...\n";
		} else {
			echo "books/$barcode/preview already exists...\n";
		}
		if (!file_exists($pth.'/scans')) {
			mkdir($pth.'/scans');
			echo "books/$barcode/scans created...\n";
		} else {
			echo "books/$barcode/scans already exists...\n";
		}

		// Processs the remaining command line arguments
		$args = func_get_args();
		$x = array_shift($args);
		$ia_or_filename = implode('/', $args);
		$restored_images = false;
		$fileext = '';

		// If so, download the images from the Internet Archive
		if (strtolower($ia_or_filename) == 'internet_archive') {
			if ($identifier) {
				print "Restoring from the Internet Archive...\n";

				// Download the JP2 ZIP file from IA
				$this->logging->log('book', 'info', 'Downloading images from the Internet Archive.', $barcode);
				$fname = "{$identifier}_jp2.zip";
				$zipfile = "{$pth}/{$fname}";
				$fileext = 'jp2';
				$url = "https://archive.org/download/{$identifier}/{$fname}";
				echo "Downloading $fname from the Internet Archive...\n";
				`wget -O $pth/$fname $url`;

				// Extract the images to the scans folder
				$this->logging->log('book', 'info', 'Extracting images from the Internet Archive.', $barcode);
				$zip = new ZipArchive;
				print "$zipfile\n";
				$x = $zip->open($zipfile);
				$numfiles = $zip->numFiles;
				if ($x) {
					for($i = 0; $i < $numfiles; $i++) {
						$filename = $zip->getNameIndex($i);
						$fileinfo = pathinfo($filename);
						if (substr($fileinfo['basename'],-4,4) == '.jp2') {
							copy("zip://{$zipfile}#{$filename}", "{$pth}/scans/{$fileinfo['basename']}");
						}
					}
					$zip->close();                  
				}
				$restored_images = true;

			} else {
				print "!!! WARNING: No identifier was found. Unable to restore scanned files.\n";
				return;
			}
		} elseif (strtolower($ia_or_filename) == 'internet_archive_pdf') {
			// Download the PDF from the Internet Archive aand use that
			print "Getting PDF from IA.\n";

			$files = json_decode(file_get_contents("https://archive.org/metadata/$identifier/files"));
			$pdfs = [];
			foreach ($files->result as $f) {
				if (preg_match('/_orig_pdf/', $f->name)) {
					$pdfs[] = $f->name;
				}
			}
			// If there is more than one, throw an error or ask for which one to use
			if (count($pdfs) > 1) {
				print "More than one PDF was found. Please address this manually.\n";
			} else {
				// Download the PDF(s) from the internet archive: IDENTIFIER_orig_pdf.zip or IDENTIFIER_orig_pdf_##.zip
				$url = "https://archive.org/download/$identifier/".$pdfs[0];
				$dest = $pth.'/'.$pdfs[0];
				$fileext = 'png';
				if (!file_exists($dest)) {
					file_put_contents($dest, file_get_contents($url));
				}

				// Extract the PDF from the ZIP
				$zip = new ZipArchive;
				$x = $zip->open($dest);
				$zip->extractTo($pth);
				$zip->close();                  
				// Making an assumption here about the name of the PDF
				$dest = $pth.'/'.$identifier.'_orig.pdf';

				// Cycle through the page prefixes in the pages for the item and extract them
				$pages = $this->book->get_pages(); 
				$c = 0;
				foreach ($pages as $p) {
					print chr(13)."Extracting images...(".($c++)."/".count($pages).")";
					$outname = $pth.'/scans/'.$p->filebase.'.png';
					$exec = $this->cfg['gs_exe']." -sDEVICE=png16m -r450x450 -dSAFER -dBATCH -dNOPAUSE ".
											"-dFirstPage=".$p->sequence_number." -dLastPage=".$p->sequence_number." -dTextAlphaBits=4 ".
											"-dUseCropBox -sOutputFile=".escapeshellarg($outname)." ".
											escapeshellarg($dest);
					exec($exec);
				}
				print chr(13)."Extracting images...Done!        \n";
				$restored_images = true;
			}

		} else {
			
			// Make sure we got a filename of some sort
			if (!$ia_or_filename) {
				print "The item has been reset. No images have been restored.\n\n";
				return;
			}
			
			// Does the path start with sftp://?
			if ((int)strpos($ia_or_filename,'sftp:/') > 0) {
				$this->logging->log('book', 'info', 'Downloading images from the SFTP.', $barcode);
				// It does, see if we can grab the file and then treat it as a regular file.
				print "Downloading via SCP/SFTP...\n";
				$ret = $this->_get_ssh_file($ia_or_filename);
				if ($ret === false) {
					print "Unable to connect via ssh. Check server and username.\n";
					return;
				}
				if ($ret === null) {
					print "unable to copy file from SFTP\n";
					return;
				}
				$filename = $ret;
			} else {
				$filename = '/'.$ia_or_filename;
			}

			// try looking for the file 
			if (!file_exists($filename)) {
				print "Path not found: $filename\n\n";
				reset_usage();
				return;
			}

			// Extract the images to the scans folder
			$this->logging->log('book', 'info', 'Extracting images from the file or path provided.', $barcode);
			$numfiles = 0;

			// Did we get a folder or a file
			if (is_dir($filename)) { 
				// It's a folder
				print "Extracting images from a PATH.\n";
				$files = array_diff(scandir($filename), array('..', '.', 'Thumbs.db'));
				$numfiles = count($files);
				foreach ($files as $f) {
					$fi = pathinfo($filename.'/'.$f);
					$bn = $fi['basename'];
					if (!$fileext) { $fileext = $fi['extension']; }
					copy($filename."/".$f, "{$pth}/scans/{$bn}");
				}
				$restored_images = true;

			} else { 
				// It's a file. Is it a ZIP or TAR?

				if (substr($filename, -3, 3) == 'zip') {
					// It's a zip file
					print "Extracting images from a ZIP file.\n";
					$zip = new ZipArchive;
					$x = $zip->open($filename);
					$numfiles = $zip->numFiles;

					for($i = 0; $i < $numfiles; $i++) {
						$fn = $zip->getNameIndex($i);
						$fi = pathinfo($fn);
						$bn = $fi['basename'];
						if (!$fileext) { $fileext = $fi['extension']; }
						copy("zip://".$filename."#".$fn, "{$pth}/scans/{$barcode}/".$bn);
						print '.';
					}
					$zip->close();
					print "Done!\n";               
					$restored_images = true;
				} elseif (substr($filename, -3, 3) == 'tar') {
					// It's a the Tar file
					print "Got a TAR file!\n";
					require_once 'Archive/Tar.php';
					$tar = new Archive_Tar($filename);
					$files = $tar->listContent();

					$numfiles = 0;
					// Get the file extension and other bits
					foreach ($files as $f) {
						$fi = pathinfo($f['filename']);
						if ($fi['basename'] == $fi['filename']) {
							continue;
						}
						if (!$fileext) { $fileext = $fi['extension']; }
						$tar->extractList(array($f['filename']), "{$pth}/scans", $fi['dirname']);
						$numfiles++;
						print chr(13)."Extracting images from a TAR file (".$numfiles."/".count($files).")";
					}				
					print chr(13)."Extracting images from a TAR file...Done!       \n";
					$restored_images = true;
				}
			}
		}

		if ($fileext == 'jp2') {
			// We dont (re)compress JP2s
			$this->db->query("update item set ia_ready_images = $db_true where id = ".$this->book->id);
		} else {
			// We dont compress everything else to JP2.
			$this->db->query("update item set ia_ready_images = $db_false where id = ".$this->book->id);
		}

		// If we got images from either IA or a local file,
		// let's create the the thumbnails and preview images.
		// NOTE: This makes assumptions about what files we have.
		if ($restored_images) {
			$this->logging->log('book', 'info', 'Creating thumb/preview derivative images and updating database.', $barcode);
			// Update the name and extension in the pages table
			// Get the existing pages
			$pages = [];
			$query = $this->db->query('select * from page where item_id = ? order by sequence_number', array($this->book->id));
			$pages = $query->result();
			$c = 0;
			foreach ($pages as $p) {
				print chr(13)."Creating thumbs/previews and updating database...(".($c++)."/".count($pages).")";
				$files = glob("{$pth}/scans/*_".sprintf('%04d', $p->sequence_number).".$fileext");

				if (count($files) == 0 ) {
					$files = glob("{$pth}/scans/*_".sprintf('%03d', $p->sequence_number).".$fileext");
					if (count($files) == 0 ) {
						print "Could not find a file for sequence number ".$p->sequence_number."\n";
						continue;
					}
				}
				if (count($files) > 1 ) {
					print "Found more than one file for sequence number ".$p->sequence_number."\n";
					continue;
				}
				$fileinfo = pathinfo($files[0]);

				// Create the preview JPEG
				$preview = new Imagick($files[0]);
				$this->common->get_largest_image($preview);

				// get the dimensions, we're going to want them later
				$dimensions = $preview->getImageGeometry();

				// Create the preview image
				$preview->resizeImage(1500, 2000, Imagick::FILTER_POINT, 0);
				try {
					$preview->writeImage("{$pth}/preview/".$fileinfo['filename'].".jpg");
				} catch (Exception $e) {
					print '  Exception (preview image): '.$e->getMessage()."\n";
				}

				// Create the thumbnail image
				$preview->resizeImage(180, 300, Imagick::FILTER_POINT, 0);
				try {
					$preview->writeImage("{$pth}/thumbs/".$fileinfo['filename'].".jpg");
				} catch (Exception $e) {
					print '  Exception (thumbnail image): '.$e->getMessage()."\n";
				}

				$preview->clear();
				$preview->destroy();

				$data = [];
				$data[] = $fileinfo['filename'];
				$data[] = filesize($files[0]);
				$data[] = $fileext;
				$data[] = $dimensions['width'];
				$data[] = $dimensions['height'];
				$data[] = $this->book->id;
				$data[] = $p->id;
				$query = $this->db->query('update page set filebase = ?, bytes = ?, extension = ?, width = ?, height = ? where item_id = ? and id = ?', $data);
			}
			print chr(13)."Creating thumbs/previews and updating database...Done!      \n";
			$this->logging->log('book', 'info', 'Item images restored successfully.', $barcode);
		}
		// Give command to start re-uploading the item
		$this->logging->log('book', 'info', 'Item status was reset from the command line.', $barcode);
		print "Item has been reset. Please make your changes and use the following command to re-upload to the Internet Archive.\n";
		print "    sudo -u WWW_USER php index.php cron export Internet_archive ".$barcode."\n\n";

		print "Use the following to send only the images to IA.\n";
		print "    sudo -u WWW_USER php index.php cron export Internet_archive ".$barcode." scans force\n\n";

		print "Use the following to mark the item as complete.\n";
		print "    sudo -u WWW_USER php index.php utils reset_item_complete ".$barcode."\n";
	}

	/**
	 * Re-close an item
	 *
	 * CLI: Given a barcode on the command line, this will set the item as complete. Meant
	 * to be used together with reset_item when automating the re-upload of poorly-compressed
	 * images.
	 *
	 * Usage: 
	 *   sudo -u apache php index.php utils reset_item_complete 3908808264355 
	 *
	 * @since Version 2.8
	 */
	function reset_item_complete($barcode) {
		if (!$barcode) {
			echo "Please supply a barcode\n";
			die;
		}
		if (!$this->book->exists($barcode)) {
			echo "Item not found with barcode $barcode.\n";
			die;
		}

		$this->logging->log('book', 'info', 'Item reset from the command line back to complete.', $barcode);
		// Set the status to reviewing
		$this->book->load($barcode);
		echo "Setting status to complete...\n";

		$this->db->query("update item set status_code = 'completed' where id = ".$this->book->id);
		print "Item has been set to complete. To send new images, use the following\n";
		print "    sudo -u WWW_USER php index.php cron export Internet_archive ".$barcode." scans force\n\n";
	}

	/**
	 * Cleanup after having reset an item
	 *
	 * CLI: Given a barcode on the command line, this will clean up the files on disk for the
	 * item. Checks that the item is complete for all Export Modules before continuing.
	 *
	 * Usage: 
	 *    sudo -u apache php index.php utils reset_item_clean 3908808264355 
	 *
	 * @since Version 2.8
	 */
	function reset_item_cleanup($barcode) {

		if (!$barcode) {
			echo "Please supply a barcode\n";
			die;
		}
		if (!$this->book->exists($barcode)) {
			echo "Item not found with barcode $barcode.\n";
			die;
		}
		$this->book->load($barcode);

		if ($this->book->status != 'completed') {
			print "Can't continue. Book status_code is '".$this->book->status."'\n";
			return;
		}

		// Get the list of export modules, verify all are completed in the database
		$continue = true;
		foreach ($this->cfg['export_modules'] as $m) {
			$query = $this->db->query(
				'select * from item_export_status where item_id = ? and export_module = ?', 
			  array($this->book->id, $m)
			);
			$status = $query->result();
			if (count($status) == 0) { 
				print "Item is not completed for export module: $m\n";
				$continue = false; 
			} else {
				if ($status[0]->status_code != 'completed') {
					print "Item is not completed for export module: $m\n";
					$continue = false; 
				}
			}
		}
		if (!$continue) {
			return;
		}
		print "Clearing files...\n";
		$path = $this->cfg['data_directory'].'/'.$this->book->barcode;
		print "Deleting $path...\n";
		$this->_delete_all($path);

		// Get the IA identifier
		$query = $this->db->query('select * from custom_internet_archive where item_id = ?', array($this->book->id));
		$ia_id = $query->result();
		if (count($ia_id) > 0) {
			$ia_id = $ia_id[0]->identifier;
			$path = $this->cfg['data_directory'].'/import_export/Internet_archive/'.$ia_id;
			print "Deleting $path...\n";
			$this->_delete_all($path);
		}

		$this->logging->log('book', 'info', 'Item cleaned up using reset_item_cleanup.', $barcode);
		print "Cleaning files complete.\n";
	}
	/**
	 * Export data for an item to a TAR file
	 *
	 * CLI: Given a barcode on the command line, this will export the data and images 
	 * for an item into a tar. This filecan then be imported into another installation of Macaw.
	 * Technically, the data can be extracted from the file, too.
	 *
	 * Usage: 
	 *   sudo -u apache php index.php utils serialize 3908808264355
	 *
	 * @since Version 1.6
	 */
	function serialize($barcode) {
		if (!$barcode) {
			echo "Please supply a barcode\n";
			die;
		}
		if (!$this->book->exists($barcode)) {
			echo "Item not found with barcode $barcode.\n";
			die;
		}
		
		$tmp = $this->cfg['data_directory'];
				
		if (!file_exists($tmp.'/import_export')) {
			mkdir($tmp.'/import_export');
		}
		if (!is_writable($tmp.'/import_export')) {
			echo "Permission denied to write to ".$tmp.'/import_export'.".\n";
			die;			
		}
		if (!file_exists($tmp.'/import_export/serialize')) {
			mkdir($tmp.'/import_export/serialize');
		}
		if (!is_writable($tmp.'/import_export/serialize')) {
			echo "Permission denied to write to ".$tmp.'/import_export/serialize'.".\n";
			die;
		}
		if (!file_exists($tmp.'/import_export/'.$barcode)) {
			mkdir($tmp.'/import_export/'.$barcode);
		}		
		if (!is_writable($tmp.'/import_export/'.$barcode)) {
			echo "Permission denied to write to ".$tmp.'/import_export/'.$barcode.".\n";
			die;			
		}

		# 1. Get the item information
		echo "Exporting... Item... ";
		$query = $this->db->query('select * from item where barcode = ?', array($barcode));
		$item = $query->result();
		$id = $item[0]->id;
		write_file($tmp.'/import_export/'.$barcode.'/item.dat', serialize((array)$item[0]));

		# 2. Get the item_export_status information
		$query = $this->db->query('select * from item_export_status where item_id = ?', array($id));
		$item_export_status = $query->result();
		write_file($tmp.'/import_export/'.$barcode.'/item_export_status.dat', serialize((array)$item_export_status));

		# 3. Get the page information
		echo "Pages... ";
		$query = $this->db->query('select * from page where item_id = ?', array($id));
		$page = $query->result();
		for ($i = 0; $i < count($page); $i++) {
			$page[$i] = (array)$page[$i];
		}
		write_file($tmp.'/import_export/'.$barcode.'/page.dat', serialize($page));

		# 4. Get the metadata information
		echo "Metadata... ";
		$query = $this->db->query('select * from metadata where item_id = ?', array($id));
		$metadata = $query->result();
		for ($i = 0; $i < count($metadata); $i++) {
			$metadata[$i] = (array)$metadata[$i];
		}
		write_file($tmp.'/import_export/'.$barcode.'/metadata.dat', serialize($metadata));

		# 5. Gather the files
		$files = array('marc.xml', 'thumbs', 'preview', 'scans');
		foreach ($files as $f) {
			echo "$f... ";
			if (file_exists($this->cfg['data_directory'].'/'.$barcode.'/'.$f)) {
				system('cp -r '.$this->cfg['data_directory'].'/'.$barcode.'/'.$f.' '.$tmp.'/import_export/'.$barcode.'/.');
			} else if  (file_exists($this->cfg['data_directory'].'/archive/'.$barcode.'/'.$f)) {
				system('cp -r '.$this->cfg['data_directory'].'/archive/'.$barcode.'/'.$f.' '.$tmp.'/import_export/'.$barcode.'/.');
			}
		}
		echo "\n";
		# tar things up
		echo "Creating $tmp/import_export/".$barcode.".tgz ...\n";
		system('cd '.$tmp.'/import_export && tar fcz '.$barcode.'.tgz '.$barcode);
		rename("$tmp/import_export/".$barcode.".tgz", "$tmp/import_export/serialize/".$barcode.".tgz");
		# cleanup
		system('rm -r '.$tmp.'/import_export/'.$barcode);
		echo "Finished!\n";
	}

	/**
	 * Import data for an item from a TAR file
	 *
	 * CLI: Given a filename of a TGZ file, this extracts the barcode from the name
	 * of the file and imports the data and images for the item from that file. 
	 *
	 * Usage: 
	 *   sudo -u apache php index.php utils unserialize /path/to/file/3908808264355.tgz
	 *
	 * @since Version 1.6
	 */
	function unserialize() {
		$args = func_get_args();
	
		$fname = $args[count($args)-1];
		$filename = '/'.implode('/', $args);
		if (!file_exists($filename)) {
			echo "File not found! ($filename)\n";
			die;			
		}
		$tmp = $this->cfg['data_directory'];
		if (!file_exists($tmp.'/import_export')) {
			mkdir($tmp.'/import_export');
		}
		if (!is_writable($tmp.'/import_export')) {
			echo "Permission denied to write to ".$tmp.'/import_export'.".\n";
			die;			
		}
		rename($filename, $tmp.'/import_export/'.$fname);
		$filename = preg_match('/(.*?)\.tgz/', $fname, $matches);
		if (count($matches) <= 1) {
			echo "Could not identify barcode from filename.\n";
			die;			
		}
		$barcode = $matches[1];
		
		if (!file_exists($tmp.'/import_export/'.$barcode.'.tgz')) {
			echo "File not found: $tmp/import_export/".$barcode.".tgz\n";
			die;
		}
		echo "Barcode is $barcode\n";
		# Create the directory		
		if (!file_exists($this->cfg['data_directory'].'/'.$barcode)) {
			mkdir($this->cfg['data_directory'].'/'.$barcode);
		}
		if (!file_exists($this->cfg['data_directory'].'/'.$barcode.'/thumbs')) {
			mkdir($this->cfg['data_directory'].'/'.$barcode.'/thumbs');
		}
		if (!file_exists($this->cfg['data_directory'].'/'.$barcode.'/preview')) {
			mkdir($this->cfg['data_directory'].'/'.$barcode.'/preview');
		}
		if (!file_exists($this->cfg['data_directory'].'/'.$barcode.'/scans')) {
			mkdir($this->cfg['data_directory'].'/'.$barcode.'/scans');
		}
		system('cd '.$tmp.'/import_export && tar fxz '.$barcode.'.tgz');
		if (file_exists($tmp.'/import_export/'.$barcode.'/marc.xml')) {
			system('mv -f '.$tmp.'/import_export/'.$barcode.'/marc.xml  '.$this->cfg['data_directory'].'/'.$barcode);
		}
		if (file_exists($tmp.'/import_export/'.$barcode.'/thumbs')) {
			system('mv -f '.$tmp.'/import_export/'.$barcode.'/thumbs/*  '.$this->cfg['data_directory'].'/'.$barcode.'/thumbs/');
		}
		if (file_exists($tmp.'/import_export/'.$barcode.'/preview')) {
			system('mv -f '.$tmp.'/import_export/'.$barcode.'/preview/* '.$this->cfg['data_directory'].'/'.$barcode.'/preview/');
		}
		if (file_exists($tmp.'/import_export/'.$barcode.'/scans')) {
			system('mv -f '.$tmp.'/import_export/'.$barcode.'/scans/* '.$this->cfg['data_directory'].'/'.$barcode.'/scans/');
		}

		$item = unserialize(read_file($tmp.'/import_export/'.$barcode.'/item.dat'));
		if ($this->db->dbdriver == 'mysql' || $this->db->dbdriver == 'mysqli') {
			if (!$item['needs_qa']) { $item['needs_qa'] = '0'; }
			if ($item['needs_qa'] == 't') { $item['needs_qa'] = '1'; }
			if ($item['needs_qa'] == 'f') { $item['needs_qa'] = '0'; }

			if (!$item['ia_ready_images']) { $item['ia_ready_images'] = 'f'; }
			if ($item['ia_ready_images'] == 't') { $item['ia_ready_images'] = '1'; }
			if ($item['ia_ready_images'] == 'f') { $item['ia_ready_images'] = '0'; }
		} elseif ($this->db->dbdriver == 'postgre') {
			if (!$item['needs_qa']) { $item['needs_qa'] = 'f'; }
			if ($item['needs_qa'] == '1') { $item['needs_qa'] = 't'; }
			if ($item['needs_qa'] == '0') { $item['needs_qa'] = 'f'; }
			
			if (!$item['ia_ready_images']) { $item['ia_ready_images'] = 'f'; }
			if ($item['ia_ready_images'] == '1') { $item['ia_ready_images'] = 't'; }
			if ($item['ia_ready_images'] == '0') { $item['ia_ready_images'] = 'f'; }
		}
		
		$query = $this->db->query('select * from item where barcode = ?', array($item['barcode']));
		$check_item = $query->result();
		unset($item['id']);
		if (count($check_item) > 0) {
			$new_item_id = $check_item[0]->id;
			$this->db->where('id', $new_item_id);
			$this->db->update('item', $item);
			echo "Item record updated! (id=".$new_item_id.")\n";
		} else {
			$this->db->insert('item', $item);
			$new_item_id = $this->db->insert_id();
			echo "Item record added! (id=".$new_item_id.")\n";
		}
		
		$page = unserialize(read_file($tmp.'/import_export/'.$barcode.'/page.dat'));
		$page_map = array();
		$this->db->trans_start();
		for ($i = 0; $i < count($page); $i++) {

			$old_id = $page[$i]['id'];
			unset($page[$i]['id']);
			$page[$i]['item_id'] = $new_item_id;
			$query = $this->db->query('select * from page where item_id = ? and sequence_number = ?', array($page[$i]['item_id'], $page[$i]['sequence_number']));
			$check_page = $query->result();
			if ($this->db->dbdriver == 'mysql' || $this->db->dbdriver == 'mysqli') {
				$page[$i]['created'] = substr($page[$i]['created'], 0, 19);
			}

			if (count($check_page) > 0) {
				$page_map["$old_id"] = $check_page[0]->id;
				$this->db->where('item_id', $page[$i]['item_id']);
				$this->db->where('sequence_number', $page[$i]['sequence_number']);
				$this->db->update('page', $page[$i]);
				echo "Page record updated! (itemid=".$page[$i]['item_id'].",seq=".$page[$i]['sequence_number'].")\n";
			} else {
				$this->db->insert('page', $page[$i]);
				echo "Page record added! (itemid=".$page[$i]['item_id'].",seq=".$page[$i]['sequence_number'].")\n";
				$page_map["$old_id"] = $this->db->insert_id();
			}
		}
		$this->db->trans_complete();

		$metadata = unserialize(read_file($tmp.'/import_export/'.$barcode.'/metadata.dat'));

		// Verify we have new page numbers for all metadata items
		for ($i = 0; $i < count($metadata); $i++) {	
			if ($metadata[$i]['page_id']) {
				if (!key_exists($metadata[$i]['page_id'], $page_map)) {
					print "Can't translate page ".$metadata[$i]['page_id']."\n";
				}
			}
		}

		for ($i = 0; $i < count($metadata); $i++) {
			 # translate the page_id
			$new_page_id = ($metadata[$i]['page_id'] ? $page_map[$metadata[$i]['page_id']] : null);
			$metadata[$i]['item_id'] = $new_item_id;
			$metadata[$i]['page_id'] = $new_page_id;
			$check_metadata = null;
			if ($new_page_id) {
				$query = $this->db->query(
					'select * from metadata where item_id = ? and page_id = ? and fieldname = ? and counter = ?', 
					array($metadata[$i]['item_id'], $new_page_id, $metadata[$i]['fieldname'], $metadata[$i]['counter'])
				);
			} else {
				$query = $this->db->query(
					'select * from metadata where item_id = ? and page_id is null and fieldname = ? and counter = ?', 
					array($metadata[$i]['item_id'], $metadata[$i]['fieldname'], $metadata[$i]['counter'])
				);
			}
			$check_metadata = $query->result();
			if (count($check_metadata) > 0) {
				$this->db->where('item_id',   $metadata[$i]['item_id']);
				if ($new_page_id) {
					$this->db->where('page_id',   $new_page_id);
				} else {
					$this->db->where('page_id is null');
				}
				$this->db->where('fieldname', $metadata[$i]['fieldname']);
				$this->db->where('counter',   $metadata[$i]['counter']);
				$this->db->update('metadata', $metadata[$i]);
				echo "Metadata record updated! (itemid=".$metadata[$i]['item_id'].",pageid=".($new_page_id ? $new_page_id : 'NULL').",fname=".$metadata[$i]['fieldname'].",c=".$metadata[$i]['counter'].")\n";
			} else {				
				$this->db->insert('metadata', $metadata[$i]);
				echo "Metadata record added! (itemid=".$metadata[$i]['item_id'].",pageid=".($new_page_id ? $new_page_id : 'NULL').",fname=".$metadata[$i]['fieldname'].",c=".$metadata[$i]['counter'].")\n";
			}
		}		
		# cleanup
		system('rm -r '.$tmp.'/import_export/'.$barcode);
	}
	
	/**
	 * Import data for items from a CSV file
	 *
	 * CLI: Given a filename of a CSV file (which should exist in the /import_export/ directory
	 * create items from the CSV. Do not update items if they already exist. Do not warn if
	 * items already exist. Create a .txt file to report on the progress (JSON) 
	 * 
	 * May also send a tab-separated file with the file extension .txt
	 *
	 * Usage: 
	 *   sudo -u apache php index.php utils csvimport import-62p7ac.csv
	 * 
	 * @since Version 1.6
	 */
	function csvimport($filename, $filename2 = null, $username = 'admin') {
		// Import the file
		$errors = array();
		
		$fname = $this->cfg['data_directory'].'/import_export/'.$filename;

		// Load the effective user. This is probably a huge security hole.
		$this->user->load($username);

		// Just in case we didn't have a second filename make sure it's null and not "null"
		if ($filename2 == 'null') {
			$filename2 = null;
		}

		$items_imported = 0;
		$items_skipped = 0;
		$pages_imported = 0;
		$pages_skipped = 0;
		$orig_fname = $fname;

		$errorcount = 0;
		
		if (file_exists($fname)) {
			// Read the entire file to check the encoding
			$all_file = file_get_contents($fname);
			// Clean up the 'marc6552' column which is often mangled by Excel into a date
			if (preg_match('/,Mar-52,/',$all_file)) {
				$all_file = preg_replace('/,Mar\-52,/', ',marc6552,', $all_file);
				file_put_contents($fname, $all_file);
			}
			$allowed_encodings = array(
				'ASCII',
				'7bit',
				'8bit',
				'UTF-8',
				'UTF-16',
				'UTF-16BE',
				'UTF-16LE',
				'UTF-32',
				'UTF-32BE',
				'UTF-32LE',
				'ISO-8859-1',
				'ISO-8859-2',
				'ISO-8859-3',
				'ISO-8859-4',
				'ISO-8859-5',
				'ISO-8859-6',
				'ISO-8859-7',
				'ISO-8859-8',
				'ISO-8859-9',
				'ISO-8859-10',
				'ISO-8859-13',
				'ISO-8859-14',
				'ISO-8859-15',
				'ISO-8859-16',
				'Windows-1252',
				'Windows-1254',
				'Windows-1251',
			);
			
			$encoding = mb_detect_encoding($all_file, $allowed_encodings, true);
			unset($all_file);
			
			$row = 1;
			$message = '';
			$value = '';
			$ext = pathinfo($fname, PATHINFO_EXTENSION);

			// Parse the CSV file
			include APPPATH . 'classes/ParseCSV.php';
			$csv = new parseCSV();		
	    $csv->delimiter = ",";
	    $csv->allow_duplicate_headers = true;
			if ($ext == 'txt') {
		    $csv->delimiter = "\t";
			}			
			$csv->encoding($encoding, 'UTF-8');
			$csv->parse($fname);

			# make sure the titles are lowercase
			$titles = $csv->titles;
			$changed = false;
			for ($i = 0; $i < count($titles); $i++) {
				if ($titles[$i] != strtolower($titles[$i])) {
					$changed = true;
					$titles[$i] = strtolower($titles[$i]);
				}
			}
			if ($changed) {
				$csv->fields = $titles;
				$csv->parse($fname);
			}
			
			$info = $csv->data;

			// Insert the data into the database
			$c = 1;
			$max = count($info);
			foreach ($info as $b) {
				// Is this book already in our database?
				if (!$this->book->exists($b['identifier'])) {
					try {			
						// Add the book
						$this->book->add($b);
						$this->logging->log('book', 'info', 'Item created from CSV Import.', $b['identifier']);
						$items_imported++;
						$this->_save_import_status($orig_fname, round(($c++)/$max*100), 'Loading Items...');
					} catch (Exception $e) {
						$errors[] = 'Error creating item '.$b['identifier'].' from CSV Import: '.$e->getMessage();
						$this->logging->log('access', 'info', 'Error creating item '.$b['identifier'].' from CSV Import: '.$e->getMessage());
						$errorcount++;
					}
				} else {
					$items_skipped++;
				}
			}
		} else {
			echo "File not found: $fname\n";
		}


		if ($filename2) {
			$fname = $this->cfg['data_directory'].'/import_export/'.$filename2;
			if (file_exists($fname)) {
				$message = '';
				$value = '';
				$fieldnames = array();

				// Read the entire file to check the encoding
				$all_file = file_get_contents($fname);
				$encoding = mb_detect_encoding($all_file, mb_list_encodings(), true);
				unset($all_file);

				// Parse the CSV file
				$csv = new parseCSV();		
				$csv->delimiter = ",";
				if ($ext == 'txt') {
					$csv->delimiter = "\t";
				}			
				$csv->encoding($encoding, 'UTF-8');
				$csv->parse($fname);
				$info = $csv->data;

				// Process the lines from the CSV
				$c = 1;
				$max = count($info);
				foreach ($info as $p) {
					try {
						$this->book->load($p['identifier']);
						if (!$this->book->page_exists($p['filename'])) {

							$this->book->add_page($p['filename'], 0, 0, 0, 'Data loaded');
							$this->logging->log('book', 'info', 'Page '.$p['filename'].' added from CSV Import.', $b['identifier']);
							$pages_imported++;
		
							$filebase = preg_replace('/(.+)\.(.*?)$/', "$1", $p['filename']);
							
							$this->db->select('id');
							$this->db->where('filebase', $filebase);
							$this->db->where('item_id', $this->book->id);
							$page = $this->db->get('page');
							$row = $page->row();
							
							foreach (array_keys($p) as $k) {
								if ($k != 'identifier' && $k != 'filename') {
									if ($k == 'page_type') {
										$page_types = explode(',', $p[$k]);
										$cpt = 1;
										foreach ($page_types as $pt) {
											$this->book->set_page_metadata($row->id, $k, trim($pt), $cpt++);
										}
									} else {
										$this->book->set_page_metadata($row->id, $k, trim($p[$k]), 1);
									}
								}
							}
							// While importing, write our progress to "$filename.txt"
							$this->_save_import_status($orig_fname, round(($c++)/$max*100), 'Loading Pages...');
						} else {
							$pages_skipped++;						
						}
					} catch (Exception $e) {
						$errors[] = 'Error adding page '.$p['filename'].' from CSV Import: '.$e->getMessage();
						$this->logging->log('access', 'info', 'Error adding page '.$p['filename'].' from CSV Import: '.$e->getMessage());
						$errorcount++;
					}
				}
			} else {
				echo "File not found: $fname\n";
			}
		}
		$this->_save_import_status(
			$orig_fname, 
			100, 
			'<h4 class="finished">Import complete!</h4>'.
			$items_imported.' items imported. ('.$items_skipped.' skipped)<br>'.
			$pages_imported.' pages imported. ('.$pages_skipped.' skipped)'.
			($errorcount ? '<br><br>'.$errorcount.' errors. <br><br>'.implode('<br>', $errors) : ''), 
			1
		);

	}
	
	/**
	 * Log CSV Import activity
	 *
	 * UTILITY/INTERNAL: Log things that happen during a CSV import to be displayed later.
	 * 
	 * @since Version 2.1.14
	 */
	function _save_import_status($file = '', $value = 1, $message = '', $finished = 0) {
		if ($file != '') {
			write_file($file.'.log', 
				json_encode(array(
					'message' => $message,
					'finished' => $finished,
					'value' => $value
				))
			);
		}
	}

	/**
	 * Delete an entire item from Macaw
	 *
	 * CLI: Given a barcode on the command line, this will delete all information
	 * files and directories for an item in macaw. Confirmation must be given by the user
	 * after being warned about how much will be deleted.
	 *
	 * Usage: 
	 *   sudo -u apache php index.php utils delete_item 3908808264355
	 *
	 * @since Version 1.6
	 */
	function delete_item($barcode, $confirm = null) {
		if (!$barcode) {
			echo "Please supply a barcode\n";
			die;
		}
		if (!$this->book->exists($barcode)) {
			echo "Item not found with barcode $barcode.\n";
			die;
		}
		
		$tmp = $this->cfg['data_directory'];
		
		# 1. Count up the number of files
		$files = $this->_getFilesFromDir($tmp.'/'.$barcode);
		
		# 2. Get the item information
		$query = $this->db->query('select * from item where barcode = ?', array($barcode));
		$item = $query->result();
		$id = $item[0]->id;

		# 3. Count the number of records we're going to delete.
		$record_count = 1; 

		$query = $this->db->query('select count(*) from item_export_status where item_id = ?', array($id));
		$count = $query->result();
		if (isset($count[0]->count)) {
			$record_count = $record_count + $count[0]->count;
		} 
		$query = $this->db->query('select count(*) from page where item_id = ?', array($id));
		$count = $query->result();
		if (isset($count[0]->count)) {
			$record_count = $record_count + $count[0]->count;
		} 

		$query = $this->db->query('select count(*) from metadata where item_id = ?', array($id));
		$count = $query->result();
		if (isset($count[0]->count)) {
			$record_count = $record_count + $count[0]->count;
		} 
		if (strtolower($confirm) == 'yes') {
			$stdin = 'y';
		} else {
			echo "You are about to delete ".count($files)." files and $record_count database records.\nAre you sure you want to continue? (y/N) ";

			if(!defined("STDIN")) {
				define("STDIN", fopen('php://stdin','r'));
			}
		}
		$stdin = fread(STDIN, 80); // Read up to 80 characters or a newline
		if (preg_match("/y/i", $stdin)) {
			echo "Deleting!\n";
			$query = $this->db->query('delete from metadata where item_id = ?', array($id));
			$query = $this->db->query('delete from page where item_id = ?', array($id));
			$query = $this->db->query('delete from item_export_status where item_id = ?', array($id));
			$query = $this->db->query('delete from item where id = ?', array($id));
			delete_files($tmp.'/'.$barcode, TRUE);
			rmdir($tmp.'/'.$barcode);
		} else {
			echo "Aborting!\n";
		}
		
	}

	/**
	 * Recursively get a single array of all files
	 *
	 * UTILITY/INTERNAL: Given a path, recurse through the files and directories 
	 * and return a single list of full paths that are contained in and
	 * below it. 
	 *
	 */
	function _getFilesFromDir($dir) { 
		$files = array(); 
		if ($handle = opendir($dir)) { 
			while (false !== ($file = readdir($handle))) { 
				if ($file != "." && $file != "..") { 
					if(is_dir($dir.'/'.$file)) { 
						$dir2 = $dir.'/'.$file; 
						$files[] = $this->_getFilesFromDir($dir2); 
					} else { 
						$files[] = $dir.'/'.$file; 
					} 
				} 
			} 
			closedir($handle); 
		} 		
		return $this->_array_flat($files); 
	} 

	/**
	 * Flatten an array of arrays into one array
	 * UTILITY/INTERNAL
	 */ 
	function _array_flat($array) { 
		$tmp = array();
		foreach($array as $a) { 
			if(is_array($a)) { 
				$tmp = array_merge($tmp, $this->_array_flat($a)); 
			} else { 
				$tmp[] = $a; 
			} 
		} 
		return $tmp; 
	} 
	
	/**
	 * Import a PDF as an entire item
	 *
	 * CLI: Given an identifier and a PDF, use ghostscript to split
	 * the PDF pages into images and import those images into the item
	 * identified on the command line
	 *
	 * Usage: 
	 *   sudo -u apache php index.php utils import_pdf 3908808264355 /path/to/file.pdf
	 *
	 * @since Version 2.1.20
	 */	
	function import_pdf($barcode = null, $filename = null) {
		if (!$barcode) {
			print "Barcode is requred!\n";
			die;
		}

		if (!$filename) {
			print "Filename is required!\n";
			die;
		}

		// Looks like this is being encoded on the way in
		$filename = html_entity_decode($filename);

		$scans_dir = $this->cfg['data_directory'].'/'.$barcode.'/scans/';
		$book_dir = $this->cfg['data_directory'].'/'.$barcode.'/';
		$this->book->load($barcode);		

		if (!$this->book->check_paths()) {
			echo('Could not write to one or more paths for item with barcode "'.$this->barcode.'".'."\n");
		} // if ($this->check_paths)

		if (!file_exists($scans_dir.$filename)) {
			print "File not found: $scans_dir$filename\n";
			die;
		}
		
		// Mark that we are processing the PDF, but don't use built-in functions
		// We might get a race condition, and this will create multiple records
		// for each pdf that is processing (if there are more than one uploaded 
		// at the same time)
		$this->db->insert('metadata', array(
			'item_id'   => $this->book->id,
			'fieldname' => 'processing_pdf',
			'counter'   => 1,
			'value' => 'yes'
		));


		$missing = false;
		$pgs = $this->book->get_pages();
		if (count($pgs) > 0) {
			// If yes, then the imported images are "missing".
			$missing = true;
		}

		// Clean the filename. RedHat doesn't like accents. Ubuntu doesn't care.
		$fn_clean = iconv('utf-8', 'ASCII//TRANSLIT//IGNORE', $filename);							
		$scans_dir = $this->cfg['data_directory'].'/'.$barcode.'/scans/';
		rename($scans_dir.$filename, $scans_dir.$fn_clean);
		$filename = $fn_clean;
		
		$this->book->split_pdf($filename);

		// Now that the files are split, they need to be processed
		$existingFiles = get_dir_file_info($scans_dir);
		$existingFiles = $this->_dedupe_files($existingFiles);

		$pdf_info = pathinfo($filename);
			
		$seq = $this->book->max_sequence() + 1;
		foreach ($existingFiles as $fileName => $info) {
			if (strpos($fileName, $pdf_info['filename']) !== false) {
				$this->book->import_one_image($fileName, $seq++, $missing);
			}
		}
		// Reorder the pages, just to be safe.
		// Get the pagesm sorted by filebase
		$pages = $this->book->get_pages('filebase');
		$seq = 1;
		foreach ($pages as $p) {
			$this->book->set_page_sequence($p->id, $seq++);
		}

		// Indicate that we are done processing the PDF
		// But we delete only one record just in case
		// we are processing more than one PDF at a time
		$this->db->query(
			'delete from metadata
			where item_id = '.$this->book->id.'
			and page_id is null and fieldname = \'processing_pdf\' limit 1'
		);
		if ($this->book->status == 'new' || $this->book->status == 'scanning') {
			$this->book->set_status('scanning');
			$this->book->set_status('scanned');
		}
	}

	/**
	 * Remove duplicates from a list of files
	 * 
	 * INTERNAL/UTILITY: Used in import_pdf when splitting a PDF into PNGs. 
	 */	
	function _dedupe_files($files) {
		$good_files = [];
		foreach ($files as $fname => $data) {
			$pi = pathinfo($fname);
			$bn = $pi['filename'];
			$name = $pi['basename'];
			if (isset($good_files[$bn])) {
				if ($data['date'] > $good_files[$bn]['date']) {
					$good_files[$bn] = $data;
				}
			} else {
				$data['name'] = $name;
				$good_files[$bn] = $data;
			}
		} // foreach ($files as $f)
		$files = [];
		foreach ($good_files as $f => $data) {
			$files[$data['name']] = $data;
		}
		return $files;
	}

	/**
	 * Get Item Counts for all contributors
	 *
	 * CLI: Get a fixed-width chart of the counts of items, pages, etc from
	 * all contributor organizations in Macaw. Suitable for piping ot an email
	 * program. May optionally hide the API key for the contributor.
	 *
	 * Usage: 
	 *   sudo -u apache php index.php utils contributor_stats HIDE_KEY
	 *
	 * @since Version 2.7.0
	 */	
	function contributor_stats($hidekey = null) {
		setlocale(LC_CTYPE, 'en_US');
		$format = "%-50s %5s %6s %11s %-40s\n";
		printf($format, 'CONTRIBUTOR', 'ITEMS', 'PAGES', 'LAST', 'IA EMAIL');

		// Get a list of contributors
		$orgs = $this->db->query(
			'SELECT id, substr(name,1,45) as name, access_key '.
			'FROM organization o INNER JOIN ('.
			'select max(access_key) as access_key, org_id '.
			'FROM custom_internet_archive_keys '.
			'GROUP BY org_id) '.
			'as k ON o.id = k.org_id '.
			'ORDER BY o.name'
		)->result();

		for ($i=0; $i < count($orgs); $i++) {
			// Get a count of completed items for each contributor
			$item_count = $this->db->query(
				'SELECT count(*) as c FROM item i WHERE i.org_id = '.$orgs[$i]->id.
				' AND i.status_code IN (\'completed\', \'exporting\') '
			)->result();
			$orgs[$i]->item_count = $item_count[0]->c;

			// Get a count of pages for each completed item for each contributor
			$page_count = $this->db->query(
				'SELECT count(*) as c FROM page p INNER JOIN item i ON p.item_id = i.id WHERE i.org_id = '.$orgs[$i]->id.
				' AND i.status_code IN (\'completed\', \'exporting\') '
			)->result();
			$orgs[$i]->page_count = $page_count[0]->c;

			// Get the IA ID of the most recent completed item for the contributor
			$last_item = $this->db->query(
				'SELECT id, COALESCE(date_completed, date_export_start, 0) as date_completed FROM item i WHERE i.org_id = '.$orgs[$i]->id.
				' AND i.status_code IN (\'completed\', \'exporting\') '.
				' ORDER BY COALESCE(date_completed, date_export_start, 0) desc'
			)->result();

			if (count($last_item) > 0) {
				$ia = $this->db->query(
					'SELECT identifier FROM custom_internet_archive WHERE item_id = '.$last_item[0]->id
				)->result();

				// Get the email address from IA's Metadata API for the most recent completed item
				$url = 'https://archive.org/metadata/'.$ia[0]->identifier.'/metadata/uploader';
				$uploader = @file_get_contents($url);
				if ($uploader) {
					$uploader = json_decode($uploader);
					if (isset($uploader->result)) {
						$orgs[$i]->key_user = $uploader->result;
					} else {
						$orgs[$i]->key_user = "(Error?: ".$ia[0]->identifier.")";
					}
					$orgs[$i]->last_date = substr($last_item[0]->date_completed,0,10);
				} else {
					$orgs[$i]->last_date = '';
					$orgs[$i]->key_user = 'ERROR?';
				}
			} else {
				$orgs[$i]->last_date = '';
				$orgs[$i]->key_user = 'UNKNOWN';
			}

			// Spit it out in a pretty format
			printf($format, iconv('UTF-8', 'ASCII//TRANSLIT', $orgs[$i]->name), $orgs[$i]->item_count, $orgs[$i]->page_count, $orgs[$i]->last_date, $orgs[$i]->key_user);
		}
	}
	
		/**
	 * Validate MARC data for incomplete items
	 * 
	 * CLI: For all items that are not complete, determine the validity
	 * of the MARC XML daa we have in the database. Print a tab-separated
	 * ouput with the results.
	 *
	 * Usage: 
	 *   sudo -u apache php index.php utils check_all_marc
	 *
	 * @since Version 2.7.0
	 */	
	function check_all_marc() {
		$books = $this->book->get_all_books();
		
		// Loop through the books
		print "Barcode\tIA Identifier\tContributor\tDate Completed\tMessage\n";
		foreach ($books as $b) {
			try {
				$this->book->load($b->barcode);
				if ($this->book->status != 'completed') { continue; }
				$marc = $this->book->get_metadata('marc_xml');
				if (is_array($marc)) {
					$marc = $marc[0];
				}
				if ($marc) {
					$ret = $this->common->validate_marc($marc);
					if ($ret) {
						$this->db->where('barcode', $b->barcode);
						$row = $this->db->get('item')->row();				
						print "{$b->barcode}\t";
						$id = $this->book->get_metadata('ia_identifier');
						print (is_array($id) ? $id[0] :  $id)."\t";
						$c = $this->book->get_contributor();
						print (is_array($c) ? implode(' | ', $c)  :  $c)."\t";
						print "{$row->date_completed}\t";
						print preg_replace('/[\r\n]/','',$ret)."\n";
					}
				}
			} catch (Exception $e) {

			}
		}
	}
	
	/**
	 * Download a file over SSH/SCP
	 * 
	 * INTERNAL/UTILITY: Used during reset_item to get the _orig_tiff.tar file over the network. 
	 */	
	function _get_ssh_file($filename) {
		$ssh_user_and_host = '';
		$ssh_path = '';

		$matches = [];
		if (preg_match('|^sftp://?(.*?)/(.*?)$|',$filename, $matches)) {
			$ssh_user_and_host = $matches[1];
			$ssh_path = '/'.$matches[2];
		}
		$temp = $this->cfg['data_directory'].'/import_export/'.basename($filename);
		$cmd = "scp -q {$ssh_user_and_host}:/{$ssh_path} {$temp}";
		`$cmd`;
		if (file_exists($temp)) {
			return $temp;
		}
		return false;
	}

	/**
	 * Delete all files and directories for a given path
	 * 
	 * INTERNAL/UTILITY: Used during reset_item_cleanup. 
	 */	
	function _delete_all($p) {
		if (is_file($p)) {
			return unlink($p);
		} elseif (is_dir($p)) {
			$scan = glob(rtrim($p, '/').'/*');
			foreach($scan as $index => $path) {
			$this->_delete_all($path);
			}
			return @rmdir($p);
		}
	}

	/**
	 * Reset a user's password
	 * 
	 * Usage:
	 *   sudo -u apache php index.php utils set_password richardjm joelpassword214!
	 */	
	function set_password ($username, $password) {
		if (!$username) {
			print "User is requried\nUSAGE: php index.php utils set_password USERNAME PASSWORD\n";
			return;
		}
		if (!$password) {
			print "Password is requried\nUSAGE: php index.php utils set_password USERNAME PASSWORD\n";
			return;
		}
		$target_user = new User;
		$target_user->load($username);
		if ($target_user->username) {
			$target_user->password = $password;
			$target_user->update();
			print "Password updated for $target_user->username\n";
		}
	}

	/**
	 * RE-Import XML from a a marc.xml file from disk
	 *
	 * CLI: If "identifier/marc.xml" exists, add it to the metadata
	 * for the given book.
	 * Usage: 
	 *   sudo -u apache php index.php utils reload_marcxml 3908801242356
	 * 
	 * @since Version 2.9.0
	 */
	function reload_marcxml($barcode) {
		if (!$barcode) {
			echo "Please supply a barcode\n";
			die;
		}
		if (!$this->book->exists($barcode)) {
			echo "Item not found with barcode $barcode.\n";
			die;
		}

		// Set the status to reviewing
		$this->book->load($barcode);
		$marcfile = $this->cfg['data_directory'].'/'.$barcode.'/marc.xml';

		if (file_exists($marcfile)) {
			$data = file_get_contents($marcfile);
			$this->book->set_metadata('marc_xml', $data);
			$this->book->update();
		}
	}

	/**
	 * RE-Import data for items from a CSV file
	 *
	 * CLI: Given a filename of a CSV file, update the metadata for the
	 * item if existing metadata is not already there.
	 * 
	 *
	 * Usage: 
	 *   sudo -u apache php index.php utils csv_reimport import-62p7ac.csv
	 * 
	 * @since Version 2.9.0
	 */
	function csv_reimport($filename, $username = 'admin') {
		// Import the file
		$errors = array();
		
		$fname = $this->cfg['data_directory'].'/import_export/'.$filename;

		// Load the effective user. This is probably a huge security hole.
		$this->user->load($username);

		// Just in case we didn't have a second filename make sure it's null and not "null"
		$items_imported = 0;
		$items_skipped = 0;
		$pages_imported = 0;
		$pages_skipped = 0;
		$orig_fname = $fname;

		$errorcount = 0;
		
		if (file_exists($fname)) {
			// Read the entire file to check the encoding
			$all_file = file_get_contents($fname);
			// Clean up the 'marc6552' column which is often mangled by Excel into a date
			if (preg_match('/,Mar-52,/',$all_file)) {
				$all_file = preg_replace('/,Mar\-52,/', ',marc6552,', $all_file);
				file_put_contents($fname, $all_file);
			}
			$allowed_encodings = array(
				'ASCII',
				'7bit',
				'8bit',
				'UTF-8',
				'UTF-16',
				'UTF-16BE',
				'UTF-16LE',
				'UTF-32',
				'UTF-32BE',
				'UTF-32LE',
				'ISO-8859-1',
				'ISO-8859-2',
				'ISO-8859-3',
				'ISO-8859-4',
				'ISO-8859-5',
				'ISO-8859-6',
				'ISO-8859-7',
				'ISO-8859-8',
				'ISO-8859-9',
				'ISO-8859-10',
				'ISO-8859-13',
				'ISO-8859-14',
				'ISO-8859-15',
				'ISO-8859-16',
				'Windows-1252',
				'Windows-1254',
				'Windows-1251',
			);
			
			$encoding = mb_detect_encoding($all_file, $allowed_encodings, true);
			unset($all_file);
			
			$row = 1;
			$message = '';
			$value = '';
			$ext = pathinfo($fname, PATHINFO_EXTENSION);

			// Parse the CSV file
			include APPPATH . 'classes/ParseCSV.php';
			$csv = new parseCSV();		
		    $csv->delimiter = ",";
		    $csv->allow_duplicate_headers = true;
			if ($ext == 'txt') { $csv->delimiter = "\t"; }
			$csv->encoding($encoding, 'UTF-8');
			$csv->parse($fname);

			# make sure the titles are lowercase
			$titles = $csv->titles;
			$changed = false;
			for ($i = 0; $i < count($titles); $i++) {
				if ($titles[$i] != strtolower($titles[$i])) {
					$changed = true;
					$titles[$i] = strtolower($titles[$i]);
				}
			}
			if ($changed) {
				$csv->fields = $titles;
				$csv->parse($fname);
			}
			
			$info = $csv->data;

			// Insert the data into the database
			$c = 1;
			$max = count($info);
			foreach ($info as $b) {
				// Is this book already in our database?
				if ($this->book->exists($b['identifier'])) {
					// Yes
					// Load the book
					print "Processing item ".$b['identifier']."\n";
					$set_fields = [];
					$already_fields = [];
					$this->book->load($b['identifier']);
					foreach (array_keys($b) as $k) {
						// Skip this, it's not a metadata field
						if ($k == 'identifier') { continue; }
						// print "Checking metadata $k...\n";
						$d = $this->book->get_metadata($k);
						if (isset($d)) {
							$already_fields[]= $k;
						} else {
							if (is_array($b[$k])) {
								foreach ($b[$k] as $val) {
									$set_fields[] = $k;
									$this->book->set_metadata($k, $val, false);	
								}
							} else {
								$set_fields[] = $k;
								$this->book->set_metadata($k, $b[$k]);	
							}
						}
					}					
					$this->book->update();
					print "  ALREADY SET: ".implode(', ', $already_fields)."\n";
					print "  UPDATED:     ".implode(', ', $set_fields)."\n";
				} else {
					print "Barcode not found: ".$b['identifier']."\n";
				}
			}
		} else {
			echo "File not found: $fname\n";
		}
	}

}

