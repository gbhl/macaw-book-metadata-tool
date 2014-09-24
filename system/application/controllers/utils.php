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
	function Utils() {
		parent::Controller();
		$this->cfg = $this->config->item('macaw');
	}

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
	 * Export data for an item to a TAR file
	 *
	 * CLI: Given a barcode on the command line, this will export the data and images 
	 * for an item into a tar. This filecan then be imported into another installation of Macaw.
	 * Technically, the data can be extracted from the file, too.
	 *
	 * Usage: php index.php utils serialize 3908808264355
	 *
	 * Parameter: barcode 
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
		write_file($tmp.'/import_export/'.$item_export_status.'/item_export_status.dat', serialize((array)$item_export_status));

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
		$files = array('marc.xml','mods.xml', 'thumbs', 'preview');		
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
		# cleanup
		system('rm -r '.$tmp.'/import_export/'.$barcode);
		echo "Finished!\n";
	}

	/**
	 * Export data for an item to a TAR file
	 *
	 * CLI: Given a filename of a TGZ file, this extracts the barcode from the name
	 * of the file and imports the data and images for the item from that file. 
	 *
	 * Usage: php index.php utils unserialize /path/to/file/3908808264355.tgz
	 *
	 * Parameter: barcode 
	 *
	 * @since Version 1.6
	 */
	function unserialize() {
		$args = func_get_args();
	
		$fname = $args[count($args)-1];
		$filename = '/'.implode($args,'/');
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
		system('cd '.$tmp.'/import_export && tar fxz '.$barcode.'.tgz');
		system('mv -f '.$tmp.'/import_export/'.$barcode.'/marc.xml  '.$this->cfg['data_directory'].'/'.$barcode);
		system('mv -f '.$tmp.'/import_export/'.$barcode.'/mods.xml  '.$this->cfg['data_directory'].'/'.$barcode);
		system('mv -f '.$tmp.'/import_export/'.$barcode.'/thumbs/*  '.$this->cfg['data_directory'].'/'.$barcode.'/thumbs/');
		system('mv -f '.$tmp.'/import_export/'.$barcode.'/preview/* '.$this->cfg['data_directory'].'/'.$barcode.'/preview/');

		$item = unserialize(read_file($tmp.'/import_export/'.$barcode.'/item.dat'));
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
		for ($i = 0; $i < count($page); $i++) {
			$old_id = $page[$i]['id'];
			unset($page[$i]['id']);
			$page[$i]['item_id'] = $new_item_id;
			$query = $this->db->query('select * from page where item_id = ? and sequence_number = ?', array($page[$i]['item_id'], $page[$i]['sequence_number']));
			$check_page = $query->result();
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
	 * Usage: php index.php utils csvimport import-62p7ac.csv
	 *
	 * 
	 * @since Version 1.6
	 */
	function csvimport($filename, $filename2 = null, $username = 'admin') {
		// Import the file

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
		
		if (file_exists($fname)) {
			$lc = @exec("wc -l $fname");
			
			$row = 1;
			$message = '';
			$value = '';
			$fieldnames = array();
			$sep = ',';
			$ext = pathinfo($filename, PATHINFO_EXTENSION);
			$utf16 = false;
			
			if ($ext == 'txt') {
				$sep = "\t";
			}

			
			if (($infile = @fopen($fname, "r")) == FALSE) {
				// For whatever reason, couldn't open the file.
				// Umm... Didn't we just save the file?
				echo "Could not open import file for reading.\n";
				$this->_save_import_status($orig_fname, 0, 'Could not open import file for reading.');
				return;		
			} 
			// Can we get the fieldnames on the first row?
			$fieldnames = @fgets($infile, 80000);
			if ($this->common->is_utf16($fieldnames)) {
				$fieldnames = strtolower($this->common->utf16_to_utf8($fieldnames));
				$utf16 = true;
			}
			$fieldnames = str_getcsv($fieldnames, $sep, '"');

			if ($fieldnames == FALSE) {
				echo "Could not read the import file.\n";
				$this->_save_import_status($orig_fname, 0, 'Could not read the import file.');
				return;		
			}
			
			// Is the first item in the first row the word "identifier"?
			if (strtolower($fieldnames[0]) != 'identifier' && strtolower($fieldnames[0]) != 'barcode') {
				echo "Invalid format. Identifier column is not supplied.\n";
				$this->_save_import_status($orig_fname, 0, 'Invalid format. Identifier column is not supplied.');
				return;
			}
			
			// TODO: make sure no fieldnames are repeated in the $fieldnames array
			// If so, error out or we'll crash hard when the DB rejects the entry (duplicate counter)
			
			// Do the import
			$info = array();
			while (($line = fgets($infile, 80000)) !== FALSE) {
				if ($utf16) {
					$line = iconv('UTF-16', 'UTF-8//TRANSLIT', $line);
				}
				$data = str_getcsv($line, $sep, '"');
				if ($data && $data[0] != '' && strlen($data[0]) > 1) {
					if (!$this->book->exists($data[0])) {
						// Massage the data into the correct format
						array_push($info, $this->_array_combine($fieldnames, $data));
						$items_imported++;
					} else {
						$items_skipped++;
					}
					// While importing, write our progress to "$filename.txt"
				}
			}
			
			$errorcount = 0;
			$c = 1;
			$max = count($info);
			foreach ($info as $b) {
				try {
					$this->book->add($b);
					$this->_save_import_status($orig_fname, round(($c++)/$max*100), 'Loading Items...');
				} catch (Exception $e) {
					$errorcount++;
				}
			}
			
			fclose($infile);
			// When done, delete the import file
			// Do not delete the status file
			// unlink($fname);
		} else {
			echo "File not found: $fname\n";
		}


		if ($filename2) {
			$fname = $this->cfg['data_directory'].'/import_export/'.$filename2;
			if (file_exists($fname)) {
				$message = '';
				$value = '';
				$fieldnames = array();
				$utf16 = false;
				$sep = ',';
				$ext = pathinfo($filename2, PATHINFO_EXTENSION);
				if ($ext == 'txt') {
					$sep = "\t";
				}

				if (($infile = @fopen($fname, "r")) == FALSE) {
					// For whatever reason, couldn't open the file.
					// Umm... Didn't we just save the file?
					echo "Could not open pages import file for reading.\n";
					$this->_save_import_status($orig_fname, 0, 'Could not open import file for reading.');
					return;		
				} 
		
				// Can we get the fieldnames on the first row?
				$fieldnames = @fgets($infile, 80000);
				if ($this->common->is_utf16($fieldnames)) {
					$fieldnames = strtolower($this->common->utf16_to_utf8($fieldnames));
					$utf16 = true;
				}
				$fieldnames = str_getcsv($fieldnames, $sep, '"');
	
				if ($fieldnames == FALSE) {
					echo "Could not read the pagesimport file.\n";
					$this->_save_import_status($orig_fname, 0, 'Could not read the import file.');
					return;		
				}
				
				// Is the first item in the first row the word "identifier"?
				if (strtolower($fieldnames[0]) != 'identifier' && strtolower($fieldnames[0]) != 'barcode') {
					echo "Invalid format. Identifier column is not supplied.\n";
					$this->_save_import_status($orig_fname, 0, 'Invalid format. Identifier column is not supplied.');
					return;
				}
				
				// TODO: make sure no fieldnames are repeated in the $fieldnames array
				// If so, error out or we'll crash hard when the DB rejects the entry (duplicate counter)
				
				// Do the import
				$info = array();
				$c = 1;
				$max = @exec("wc -l $fname");
				while (($line = fgets($infile, 80000)) !== FALSE) {
					if ($utf16) {
						$line = iconv('UTF-16', 'UTF-8//TRANSLIT', $line);
					}
					$data = str_getcsv($line, $sep, '"');
					if ($data && $data[0] != '' && strlen($data[0]) > 1) {
				
						$data = $this->_array_combine($fieldnames, $data);
						if (isset($data['barcode'])) {
							$data['identifier'] = $data['barcode'];
						}
						
						$this->book->load($data['identifier']);
						if (!$this->book->page_exists($data['filename'])) {
							// Massage the data into the correct format
							array_push($info, $data);
							$pages_imported++;
							$this->_save_import_status($orig_fname, round(($c++)/$max*100), 'Checking for existing pages...');
						} else {
							$pages_skipped++;
						}
							
					}
				}
				
				$c = 1;
				$max = count($info);
				foreach ($info as $p) {
					try {
						$this->book->load($p['identifier']);
						$this->book->add_page($p['filename'], 0, 0, 0, 'Data loaded');
	
						$filebase = preg_replace('/(.+)\.(.*?)$/', "$1", $p['filename']);
						
						$this->db->select('id');
						$this->db->where('filebase', $filebase);
						$this->db->where('item_id', $this->book->id);
						$page = $this->db->get('page');
						$row = $page->row();
						
						foreach (array_keys($p) as $k) {
							if ($k != 'identifier' && $k != 'filename') {
								$this->book->set_page_metadata($row->id, $k, $p[$k], 1);
							}
						}
						// While importing, write our progress to "$filename.txt"
						$this->_save_import_status($orig_fname, round(($c++)/$max*100), 'Loading Pages...');
						
					} catch (Exception $e) {
						$errorcount++;
					}
				}
	
				fclose($infile);
				// When done, delete the import file
				// Do not delete the status file
				// unlink($fname);
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
			($errorcount ? '<br><br>'.$errorcount.' errors.' : ''), 
			1
		);

	}

	function _array_combine($keys, $values) {
			$result = array();
			foreach ($keys as $i => $k) {
				if (isset($values[$i])) {
					$result[$k][] = $this->common->utf16_to_utf8($values[$i]);
				}
			}
			array_walk($result, create_function('&$v', '$v = (count($v) == 1)? array_pop($v): $v;'));
			return $result;
	}

// 	function _array_combine($keys, $values) {
// 			$result = array();
// 			foreach ($keys as $i => $k) {
// 					$k = $this->common->utf16_to_utf8($k);
// 					$result[$k][] = mb_convert_encoding($values[$i], 'UTF-8');;
// 			}
// 			array_walk($result, create_function('&$v', '$v = (count($v) == 1)? array_pop($v): $v;'));
// 			return $result;
// 	}

	
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
	 * Usage: php index.php utils delete_item 3908808264355
	 *
	 * Parameter: barcode 
	 *
	 * @since Version 1.6
	 */
	function delete_item($barcode) {
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
		$record_count = $record_count + $count[0]->count;

		$query = $this->db->query('select count(*) from page where item_id = ?', array($id));
		$count = $query->result();
		$record_count = $record_count + $count[0]->count;

		$query = $this->db->query('select count(*) from metadata where item_id = ?', array($id));
		$count = $query->result();
		$record_count = $record_count + $count[0]->count;
		
		echo "You are about to delete ".count($files)." files and $record_count database records.\nAre you sure you want to continue? (y/N) ";

		if(!defined("STDIN")) {
			define("STDIN", fopen('php://stdin','r'));
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
	 * CLI: Given a path, recurse through the files and directories 
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

	/* Flatten an array of arrays into one array */	
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
	
	
// 	function fix_metadata($bc = '') {
// 		// A barcode is required. Duh.
// 		if ($bc == '') {
// 			echo "Barcode not supplied.\n";
// 			die;
// 		}
// 
// 		// If barcode does not exist, we quit
// 		if (!$this->book->exists($bc)) {
// 			echo "Item with barcode $bc not found.\n";
// 			die;
// 		}
// 		$marc = $this->cfg['data_directory'].'/'.$bc.'/marc.xml';
// 		$mods = $this->cfg['data_directory'].'/'.$bc.'/mods.xml';
// 		
// 		// We need either marc or mods
// 		if (!file_exists($marc) && !file_exists($mods)) {
// 			echo 'File not found: '.$marc."\n";
// 			echo 'File not found: '.$mods."\n";
// 			die;
// 		}
// 		$this->book->load($bc);
// 		$marc_data = '';
// 		$mods_data = '';
// 		// If MARC XML exists but not MODS
// 		if (file_exists($marc) && !file_exists($mods)) {
// 			// Convert MARC to MODS 
// 			$marc_data = read_file($marc);		
// 
// 			// Convert it to MODS XML
// 			$xml = new DOMDocument;
// 			$xsl = new DOMDocument;
// 			$proc = new XSLTProcessor;
// 
// 			$xml->loadXML($marc_data);							 	// Load the MARC XML to convert to MODS
// 			$xsl->load('inc/xslt/MARC21slim2MODS3-3.xsl');			// Get our XSL file from the LOC
// 			$proc->importStyleSheet($xsl); 							// attach the xsl rules
// 			$mods_data = $proc->transformToXML($xml);				// Transform the MARC to MODS
// 
// 			write_file($mods, $mods_data);
// 		}
// 
// // 		// If MODS file exists, 
// 		if (file_exists($mods)) {
// 			$marc_data = read_file($marc);							// Read the MARC data		
// 			$mods_data = read_file($mods);							// Read the MODS data		
// 			$mods = simplexml_load_string($mods_data);				// Parse the MODS
// 			$namespaces = $mods->getDocNamespaces();				// Create a new namespace for ease of parsing
// 			$mods->registerXPathNamespace('ns', $namespaces['']);	// Add the new namespace for XPath
// 			
// 			// Load the book
// 			$this->book->load($bc);			
// 			
// 			// Parse the data we need in the same manner as SIL_SIRIS
// 			// Get the title
// 			$ret = ($mods->xpath("/ns:mods/ns:titleInfo[not(@type)]/ns:title"));
// 			if (count($ret) > 0) {
// 				$this->book->set_metadata('title', $ret[0]."");
// 			}
// 
// 			// Get the author
// 			$ret = ($mods->xpath("/ns:mods/ns:name/ns:role/ns:roleTerm[.='creator']/../../ns:namePart"));
// 			if (count($ret) > 0) {
// 				$this->book->set_metadata('author', $ret[0]."");
// 			}
// 
// 			// Get the year of publication
// 			$ret = ($mods->xpath("/ns:mods/ns:originInfo/ns:dateIssued[@encoding='marc'][@point='start']"));
// 			if (count($ret) == 0) {
// 				$ret = ($mods->xpath("/ns:mods/ns:originInfo/ns:dateIssued"));
// 			}
// 			if (count($ret) > 0) {
// 				$this->book->set_metadata('year', $ret[0]);
// 			}
// 
// 			// Save MARC and MODS to the metadata
// 			$this->book->set_metadata('xmp_source', 'SIL-Smithsonian Institution Libraries, Smithsonian Institution');
// 			$this->book->set_metadata('marc_xml', $marc_data);
// 			$this->book->set_metadata('mods_xml', $mods_data);
// 
// 			// Get data from the picklist
// 			$url = "http://prism.si.edu/sil/Projects/Macaw/index.cfm?barcode=$bc&action=listItemsForPennsy&format=XML&fields=ibarcode,copyright_negotiated,callnum,location,collection_bhl,collection_sil,funding_source,icopy";
// 	
// 			$ch = curl_init();
// 			curl_setopt($ch, CURLOPT_URL, $url);
// 			curl_setopt($ch, CURLOPT_HEADER, 0);
// 			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
// 			$data = curl_exec($ch);
// 			curl_close($ch);
// 			$data = preg_replace('/\x18|\x19/', '', trim($data));
// 	
// 			$pick = null;
// 			if ($data != -1) {
// 				$pick = simplexml_load_string($data);
// 			}
// 			$i = $pick->item;
// 			
// 			$this->book->set_metadata('location', trim($i->location)."");
// 			$this->book->set_metadata('call_number', trim($i->callnum)."");
// 			$this->book->set_metadata('volume', trim($i->icopy)."");
// 			$this->book->set_metadata('sponsor', trim($i->funding_source)."");
// 
// 				
// 			// Calculate the copyright
// 			$this->book->set_metadata('copyright', 1);
// 			if ($i->copyright_negotiated == 0) {
// 				$this->book->set_metadata('copyright', 0);
// 			}
// 			$coll = Array();
// 			// Handle the collections
// 			if ($i->collection_bhl == 1) {
// 				array_push($coll, 'bhl');
// 			}
// 			if ($i->collection_sil == 1) {
// 				array_push($coll, 'sil');
// 			}
// 			$this->book->set_metadata('collections', $coll);
// 
// 			// Update the book
// 			// Save the book
// 			$this->book->update();
// 			echo "Metadata updated.\n";
// 		}
// 	
// 	}
// 
// 	/**
// 	 * Reload all of the metadata for a book based on the activity logs
// 	 *
// 	 * THIS IS A DESTRUCTIVE PROCESS.
// 	 * CLI: This will replace all of the metadata for the barcode passed in
// 	 * with the metadata found in the macaw_activity_log.*.log files. Be sure
// 	 * that your files are complete before running this. Since the logs are
// 	 * run in order, this will save the latest entry in the log file to the
// 	 * database. It's assumed that the logging is accurate and resuls in a
// 	 * string of the form:
// 	 *
// 	 *   Item=BARCODE, Page=PAGEID, Field=FIELDNAME, Value=VALUE
// 	 *
// 	 * Multiple PAGEIDs may be separated by Pipes. Multiple values may be
// 	 * separated by commas, but they will only be saved to multiple metadata
// 	 * elements if the fieldname exists in the second parameter passed.
// 	 *
// 	 * Parameters
// 	 * 		$bc - The barcode of the book
// 	 * 		$mfields - The list of fieldnames, comma separated that allow
// 	 *                 multiple metadata elements.
// 	 *
// 	 * @since Version 1.6
// 	 */
// 	function replay_activity_log($bc, $mfields = '') {
// 
// 		$md_fieldnames = array('alt_caption','author','call_number','caption','collections','copyright',
// 		                       'future_review','keywords','location','marc_xml','mods_xml','notes',
// 		                       'orig_caption','page_number','page_number_implicit','page_prefix','page_side',
// 		                       'page_type','piece','piece_text','sponsor','taxonomic_name','title','volume',
// 		                       'xmp_source','year','sequence_number');
// 		
// 		if (!$bc) {
// 			echo "Barcode required\n";
// 			return;
// 		}
// //
// 		$mfields = explode(',', $mfields);
// //
// 		$cmd = 'fgrep '.$bc.' '.$this->cfg['logs_directory'].'/* | fgrep macaw_activity';
// 		echo $cmd."\n";
// 		$output = array();
// 		exec($cmd, $output);
// 		$data = array();
// 
// 		$this->book->load($bc);
// 		$pages = $this->book->get_pages();
// 		foreach ($pages as $p) {
// 			foreach ($md_fieldnames as $f) {
// 				if (property_exists($p, $f)) {
// 					$data[$p->id.'|'.$f] = $p->$f;
// 				}
// 			}
// 		}
// 		//print_r($data);
// 		echo "--------------------------------------------------------------------------\n";
// 		echo "--------------------------------------------------------------------------\n";
// 		echo "--------------------------------------------------------------------------\n";
// 
// 		foreach ($output as $o) {
// 			$matches = array();
// 			if (preg_match('/"Item=(.*?), Page=(.*?), Field=(.*?), Value=(.*?)"/', $o, $matches)) {
// 				$barcode = $matches[1];
// 				$page = $matches[2];
// 				$field = $matches[3];
// 				$val = $matches[4];
// 				if ($barcode && $page && $field && $barcode == $bc) {
// 					$pages = explode('|', $page);
// 					foreach ($pages as $p) {		
// 						if (in_array($field, $mfields)) {
// 							if (array_key_exists($p.'|'.$field, $data)) {
// 								if (!is_array($data[$p.'|'.$field])) {
// 									$data[$p.'|'.$field] = array($data[$p.'|'.$field]);
// 								}
// 								array_push($data[$p.'|'.$field], $val);
// 							} else {
// 								$data[$p.'|'.$field] = array($val);
// 							}
// 						} else {
// 							$data[$p.'|'.$field] = $val;
// 						}
// 					}
// 				}
// 			}
// 		}
// 		print_r($data);
// 		die;
// 
// 		$this->book->delete_page_metadata();
// 		foreach ($data as $d => $value) {
// 			$d = explode('|', $d);
// 			$page = $d[0];
// 			$field = $d[1];
// 			if (in_array($field, $mfields)) {
// 				$c = 1;
// 				foreach (explode(',', $value) as $v) {
// 					$this->book->set_page_metadata($page, $field, $value, $c++);
// 					print "$page / $field / $c ==> $v\n";
// 				}
// 			} else {
// 				$this->book->set_page_metadata($page, $field, $value);
// 				print "$page / $field ==> $value\n";
// 			}
// 		}
// 		$this->book->save();
// 		return;
// 	}
}
