<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
        "http://www.w3.org/TR/html4/strict.dtd">
<html lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8">
	<title>Set Up Macaw</title>
<?php 
	include_once('system/application/views/global/head_view.php');
	require_once('system/application/libraries/Authentication/phpass-0.1/PasswordHash.php');

	$success = 1;
	$db_created = 0;
	$step = 0;
	$paths = array();
	
	if (isset($_REQUEST['step'])) {$step = $_REQUEST['step'];}
		
	// Verify that we are installed in a relatively sane manner
	// 1. Identify base directory of the site (the location of the install.php script)
	$base_path = realpath(dirname(__FILE__));
	define("BASEPATH", $base_path);

	// 2. Identify base URL of the site
	$base_url = getBaseURL();
	
	// 3. Make sure we can write to the configuration directory and the files contained therein
	$config_path = $base_path.'/system/application/config';
	$write_perm_error = 'Please make sure that the web server has read and write permissions.<br><br>';

	$errormessage = '';
	$message = '';
	$done = 0;
	$ci_config = $config_path.'/config.php';
	$db_config = $config_path.'/database.php';
	$macaw_config = $config_path.'/macaw.php';
	$macaw_def_config = $config_path.'/macaw.default.php';

	if (is_writable($config_path)) {
		// Build our filenames

		// Make sure we have a config.php, we should.
		if (file_exists($ci_config)) {
			if (!is_writable($ci_config)) {
				$errormessage .= 'The installer cannot write to the configuration file: <blockquote style="font-weight:bold">'.$ci_config.'</blockquote>'.$write_perm_error;
			}
		} else {
			$errormessage .= 'The installer cannot find the file: <blockquote style="font-weight:bold">'.$ci_config.'</blockquote>';
		}

		// Make sure we have a database.php, we should.
		if (file_exists($db_config)) {
			if (!is_writable($db_config)) {
				$errormessage .= 'The installer cannot write to the configuration file: <blockquote style="font-weight:bold">'.$db_config.'</blockquote>'.$write_perm_error;
			}
		} else {
			$errormessage .= 'The installer cannot find the file: <blockquote style="font-weight:bold">'.$db_config.'</blockquote>';
		}

		// Make sure we have a macaw.php, if not create it from the default.
		if (file_exists($ci_config)) {
			if (!is_writable($macaw_config)) {
				$errormessage .= 'The installer cannot write to the configuration file: <blockquote style="font-weight:bold">'.$macaw_config.'</blockquote>'.$write_perm_error;
			}
		} else {
			// try to create the macaw configuration file
			if (file_exists($macaw_def_config)) {
				if (!copy($macaw_def_config, $macaw_config)) {
					$errormessage .= 'The installer was unable to create the <strong>macaw.php</strong> file from the default: <blockquote style="font-weight:bold">'.$macaw_def_config.'</blockquote>'.$write_perm_error;
				}
			} else {
				$errormessage .= 'The installer cannot find the file: <blockquote style="font-weight:bold">'.$macaw_def_config.'</blockquote>';
			}
		}
	} else {
		$errormessage .= 'The installer cannot write to the configuration directory: <blockquote style="font-weight:bold">'.$config_path.'</blockquote>'.$write_perm_error;
	}
		
	if (!$errormessage && $step == 0) {
		$step = 1;
	}
	
	echo("STEP is $step<br>");

	if (!isset($_POST['submit'])) {$_POST['submit'] = '';}
	
	if ($_POST['submit'] == '<< Back') {
		$step -= 1;
		echo("STEP is now $step<br>");
	} 	

	if ($step == 1) { // Database connection info
		
		// Get our existing info from the config file
		require_once($db_config);
		
		// Prepopulate the info on the page 
		$db_dbdriver = $db['default']['dbdriver'];
		$db_hostname = $db['default']['hostname'];
		$db_username = $db['default']['username'];
		$db_password = $db['default']['password'];
		$db_database = $db['default']['database'];
		$db_port = '';
		if (isset($db['default']['port'])) { $db_port = $db['default']['port']; }

		if (isset($_POST['submit'])) {
			if ($_POST['submit'] == 'Next >>') {
				// Save whatever data was passed in
				set_config($db_config, "\$db['default']['hostname']", $_POST['database_host']);
				set_config($db_config, "\$db['default']['username']", $_POST['database_username']);
				set_config($db_config, "\$db['default']['password']", $_POST['database_password']);
				set_config($db_config, "\$db['default']['database']", $_POST['database_name']);
				set_config($db_config, "\$db['default']['dbdriver']", $_POST['database_type']);
				set_config($db_config, "\$db['default']['port']",     $_POST['database_port']);
	
				// Now test the database connection
				if ($_POST['database_type'] == 'postgre') {
					$conn = "host=".$_POST['database_host'].
							($_POST['database_port'] ? " port=".$_POST['database_port'] : '').
							" dbname=".$_POST['database_name'].
							" user=".$_POST['database_username'].
							" password=".$_POST['database_password'];
					$conn = pg_connect($conn);
					$db_created	= 0;
					if ($conn) {
						$result = @pg_query('select count(*) from account');
						if (!$result) {
							// The account table doesn't exist, so we go ahead and create the database
							$messages = pgsql_import($conn, $base_path.'/system/application/sql/macaw-pgsql.sql');
							if (count($messages)) {
								$errormessage = implode('<br>', $messages);
								$success = 0;
							} else {
								$db_created = 1;
								$success = 1;						
								$step += 1;
								$done = 1;
							}
						} else {
							$success = 1;
							$step += 1;
							$done = 1;
						}
					} else {
						$errormessage = pg_last_error();
						$success = 0;
					} // if ($conn)
				} // if ($_POST['database_type'] == 'postgre')
			} // if ($_POST['submit'] == 'Next >>')
		} // if (isset($_POST['submit']))
	} // if ($step == 1)
	
	if ($step == 2 && !$done) { // Database initial setup

		if (isset($_POST['submit'])) {
			if ($_POST['submit'] == 'Next >>') {
				$step += 1;
				$_POST['submit'] = null;
			}
		}		
	
	}
	
	if ($step == 3 && !$done) {
		// Admin name, password, email
		
		// Get the data from the database
		require_once($db_config);
		require_once($macaw_config);

		$conn = null;

		if ($db['default']['dbdriver'] == 'postgre') {
			$conn = "host=".$db['default']['hostname'].
				($db['default']['port'] ? " port=".$db['default']['port'] : '').
				" dbname=".$db['default']['database'].
				" user=".$db['default']['username'].
				" password=".$db['default']['password'];
				
			$conn = pg_connect($conn);
			$result = pg_query('select * from account where id = 1');
			$row = pg_fetch_assoc($result);
			// Fill in the fields for the administrator
			$admin_fullname = $row['full_name'];
			$admin_username = $row['username'];
			$admin_email = $config['macaw']['admin_email'];
			$admin_password = $row['password'];
		}
		
		if (isset($_POST['submit'])) {
			if ($_POST['submit'] == 'Next >>') {
				// Save whatever data was passed in
				set_config($macaw_config, "\$config['macaw']['admin_email']", $_POST['admin_email']);
	
				// Set the data in the database
				$result = pg_query_params($conn, 'UPDATE account SET full_name = $1 WHERE id = 1', array($_POST['admin_full_name']));
	
				// Make sure that we get around the whole concept of null values and "variable not set" errors. Sheesh.
				if (!isset($_POST['admin_password'])) {$_POST['admin_password'] = '';}
				if (!isset($_POST['admin_password_c'])) {$_POST['admin_password_c'] = '';}
	
				// Make sure we get a password when we need one			
				if (!$admin_password && !$_POST['admin_password']) {
					$errormessage = "You must enter a password and confirmation password.";
				}
				// Continue only if we didn't have an error
				if (!$errormessage) {
					if ($_POST['admin_password'] || $_POST['admin_password_c']) {
						// Make sure the passwords match
						if ($_POST['admin_password'] != $_POST['admin_password_c']) {
							$errormessage = "The passwords you entered do not match.";
						} else {
							// generate the new password hash
							$hasher = new PasswordHash(8, false);
							$pass_hash = $hasher->HashPassword($_POST['admin_password']);
							// Set the data in the database
							$result = pg_query_params($conn, 'UPDATE account SET password = $1 WHERE id = 1', array($pass_hash));
						}
					}
				}
	
				$done = 1;
	
				if (!$errormessage) {
					// Now determine what the next step is and get it displayed
					$step += 1;
					$_POST['submit'] = null;
				}
			}
		}
	}

	if ($step == 4 && !$done) {

		if (isset($_POST['submit'])) {
			if ($_POST['submit'] == 'Next >>') {
				$step += 1;
				$_POST['submit'] = null;
			}
		}		
	
	}


	if ($step == 5 && !$done) {
		// Path to base, data and purge directories
		require_once($macaw_config);
		require_once($ci_config);
		
		$new_items_url = $config['macaw']['new_items_url'];
		$base_url = $config['base_url'];
		
		if (isset($_POST['submit'])) {
			if ($_POST['submit'] == 'Next >>') {
				// Save Changes
				set_config($ci_config,    "\$config['base_url']",                $_POST['base_url']);
				set_config($macaw_config, "\$config['macaw']['base_directory']", $_POST['base_path']);
				set_config($macaw_config, "\$config['macaw']['new_items_url']",  $_POST['new_items_url']);

				$config['base_url']                = $_POST['base_url'];
				$config['macaw']['base_directory'] = $_POST['base_path'];
				$config['macaw']['new_items_url']  = $_POST['new_items_url'];

				// Verify access to the paths
				array_push($paths, array(
					'name' => 'Base URL',
					'path' => $config['base_url'], 
					'success' => 1, 
					'message' => 'OK. The URL was not actually checked.'
				));


				if (!file_exists($config['macaw']['base_directory'])) {
					array_push($paths, array(
						'name' => 'Base Directory',
						'path' => $config['macaw']['base_directory'], 
						'success' => 0, 
						'message' => 'Error! The path could not be found.'
					));
				} else {
					array_push($paths, array(
						'name' => 'Base Directory',
						'path' => $config['macaw']['base_directory'], 
						'success' => 1, 
						'message' => 'Success!'
					));
				}

				if (!file_exists($config['macaw']['data_directory'])) {
					array_push($paths, array(
						'name' => 'Data Directory',
						'path' => $config['macaw']['data_directory'], 
						'success' => 0, 
						'message' => 'Error! The path could not be found.'
					));
				} else {
					if (!is_writable($config['macaw']['data_directory'])) {
						array_push($paths, array(
							'name' => 'Data Directory',
							'path' => $config['macaw']['data_directory'], 
							'success' => 0, 
							'message' => 'Error! Could not write to this path. Please make sure the web server has read/write permissions.'
						));
					} else {
						array_push($paths, array(
							'name' => 'Data Directory',
							'path' => $config['macaw']['data_directory'], 
							'success' => 1, 
							'message' => 'Success!'
						));
					}
				}

				if (!file_exists($config['macaw']['logs_directory'])) {
					array_push($paths, array(
						'name' => 'Logs Directory',
						'path' => $config['macaw']['logs_directory'], 
						'success' => 0, 
						'message' => 'Error! The path could not be found.'
					));
				} else {
					if (!is_writable($config['macaw']['logs_directory'])) {
						array_push($paths, array(
							'name' => 'Logs Directory',
							'path' => $config['macaw']['logs_directory'], 
							'success' => 0, 
							'message' => 'Error! Could not write to this path. Please make sure the web server has read/write permissions.'
						));
					} else {
						array_push($paths, array(
							'name' => 'Logs Directory',
							'path' => $config['macaw']['logs_directory'], 
							'success' => 1, 
							'message' => 'Success!'
						));
					}
				}

				if (!file_exists($config['macaw']['purge_path'])) {
					array_push($paths, array(
						'name' => 'Purge Directory',
						'path' => $config['macaw']['purge_path'], 
						'success' => 0, 
						'message' => 'Error! The path could not be found.'
					));
				} else {
					if (!is_writable($config['macaw']['purge_path'])) {
						array_push($paths, array(
							'name' => 'Purge Directory',
							'path' => $config['macaw']['purge_path'], 
							'success' => 0, 
							'message' => 'Error! Could not write to this path. Please make sure the web server has read/write permissions.'
						));
					} else {
						array_push($paths, array(
							'name' => 'Purge Directory',
							'path' => $config['macaw']['purge_path'], 
							'success' => 1, 
							'message' => 'Success!'
						));
					}
				}

				array_push($paths, array(
					'name' => 'New Items URL',
					'path' => $config['macaw']['new_items_url'], 
					'success' => 1, 
					'message' => 'OK. The URL was not actually checked.'
				));



				$done = 1;		
		
				if (!$errormessage) {
					// Now determine what the next step is and get it displayed
					$step += 1;
					$_POST['submit'] = null;
				}
			}
		}
	}
	
	if ($step == 6 && !$done) {

		if (isset($_POST['submit'])) {
			if ($_POST['submit'] == 'Next >>') {
				$step += 1;
				$_POST['submit'] = null;
			}
		}		
	
	}
	
	if ($step == 7 && !$done) {
		require_once($macaw_config);
		require_once($ci_config);
		require_once($db_config);

		if ($db['default']['dbdriver'] == 'postgre') {
			$database_driver = 'PostgreSQL';
			$conn = "host=".$db['default']['hostname'].
				($db['default']['port'] ? " port=".$db['default']['port'] : '').
				" dbname=".$db['default']['database'].
				" user=".$db['default']['username'].
				" password=".$db['default']['password'];
				
			$conn = pg_connect($conn);
			$result = pg_query('select * from account where id = 1');
			$row = pg_fetch_assoc($result);
			// Fill in the fields for the administrator
			$admin_fullname = $row['full_name'];
			$admin_username = $row['username'];
			$admin_email = $config['macaw']['admin_email'];
			$admin_password = $row['password'];
		}
	}

	echo("Finally STEP is $step<br>");
	
	// We got some information, let's figure out what it is, check it and apply it
	

	// Do the final setup, manipulting the config files:
	/* 
		js/macaw.default.js ==> js/macaw.js
			Line 12: var sBaseUrl = 'http://sil-mqp00212p5pk.si.edu';
			
		system/application/config/macaw.default.php ==> system/application/config/macaw.php
			Line 8: $config['macaw']['admin_email'] = "username@website.com";
			Line 30: $config['macaw']['base_directory'] = "/path/to/webroot/htcocs";
			Line 86: $config['macaw']['new_items_url'] = "http://website.com/soap/new_items/";				
	*/
	
	// Custom files that need to be coded
		
		// plugins/metadata/metadata.php
		// plugins/partners/partner.default.php


	function pgsql_import($conn, $filename) {

		// I make the brazen assumption that $conn is open and $filename exists
		$lines = file($filename);
		$queries = array();
		$messages = array();
		$query = '';
		
		if (is_array($lines)) { // Why wouldn't it be an array?
			foreach ($lines as $l) {
				$l = trim($l);
				if(!preg_match("'^--'", $l)) {
					if (strpos($l, ';')) {
						$query .= ' '.$l;
 						if (@pg_query($conn, $query)) {
 							$query = '';
 						} else {
 							array_push($messages, pg_last_error());
 						}
					} else {
						$query .= ' '.$l;
					}
				}
			}
		}
		return $messages;
	}

	function set_config($file, $setting, $value) {
		// Open the file, read into an array
		if (file_exists($file.'.new')) {
			$arrFile = file($file.'.new');
		} else {
			$arrFile = file($file);
		}
		
		// Open our destination file, appended with ".new"
		$fh = fopen($file.'.new', 'w');
		
		$found = 0;
		// Read lines from the file, searching for what we want. 
		foreach ($arrFile as $l) {
			$l = trim($l); 
			// We always skip comments
			if (preg_match('/^#|\s+#/', $l)) {
				fwrite($fh, $l.eol());
			} else {
				// Is our setting somewhere in there?
				$pos = strpos($l, $setting);
				if ($pos === false) {
					// No, so we output whatever line we read (blank lines, nonmatching lines, etc
					fwrite($fh, $l.eol());
				} elseif ($pos < 11) {
					// We found it, so we replace with the new setting
					fwrite($fh, $setting.' = "'.$value.'";'.eol());
					$found = 1;
				} else {
					fwrite($fh, $l.eol());
				}
			}
		}
		// If we didn't find it, we append it to the end
		if (!$found) {
			fwrite($fh, $setting.' = "'.$value.'";'.eol());
		}
		fclose($fh);
		
		// Rename the new into the old and we are done. yay!
		rename($file.".new", $file);
	}

	function getBaseURL() {
		$pageURL = 'http';
		if (isset($_SERVER["HTTPS"])) {
			if ($_SERVER["HTTPS"] == "on") {$pageURL .= "s";}
		}
		$pageURL .= "://";
		if ($_SERVER["SERVER_PORT"] != "80") {
			$pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"];
		} else {
			$pageURL .= $_SERVER["SERVER_NAME"];
		}
		return $pageURL;
	}
	
	function eol() {
		$os = get_os_type();
		if ($os == 'win') {
			return "\r\n";
		} else {
			return "\n";
		}
	}
	
	function get_os_type() {
		$ua = $_SERVER['HTTP_USER_AGENT'];
		if (strpos($ua, 'Windows')) {
			return "win";
		} elseif (strpos($ua, 'OS X')) {
			return "osx";
		} else {
			return "nix";
		}
	}
		
?>
	
	<style type="text/css">
		#bd {min-height:100px}
		.bd {min-height:300px;}
		.yui-panel {width:100%; visibility:inherit;}
		.step {font-size:120%;font-weight:bold;padding:10px;color:#999999;}
		.active {color:#000000;border:1px solid #0099CC;background-color:#ffffff;}
		.yui-skin-sam .yui-panel .hd {font-size:15px;color:#0099CC;}
		.yui-skin-sam .yui-panel .bd {background-color:#ffffff;}
		.success {font-size:16px;font-weight:bold;color:#009900;margin-bottom:10px}
		.error {border:1px solid #990000;font-weight:bold;color:#CC0000;background-color:#FFCCCC;padding:15px;}
		.warning {border:1px solid #F30;color:#333;background-color:#FC9;padding:15px;margin-bottom:10px;}
		.failure {font-size:16px;font-weight:bold;color:#990000;}
		.small {font-size:100%;}
		h3 {color:#666666;}
		.errormessage {font-size:100%;margin-bottom:10px;}
		.grey {
			color: #999999; font-size: 90%;
		}
	</style>
</head>
<body class="yui-skin-sam"> 
	<div id="doc3" class="yui-t7">
		<div id="hd" role="banner">
			<img src="/images/si_logo_large.png" alt="si_logo_large.png" width="110" height="110" border="0" align="left" id="logo">
			<div id="title">
				<h2>Smithsonian Institution Libraries</h2>
				<h3>Atherton Seidell Endowment Fund</h3>
				<h3>Metadata Collection and Workflow (Macaw) system</h3>
				<h6>Version <?php echo($version_rev); ?> / <?php echo($version_date); ?></h6>
			</div>
		</div>
		<div id="bd" role="main" style="min-height:auto">
			<div class="yui-gd">
				<div class="yui-u first">
					<div class="yui-module yui-overlay yui-panel" style="width: 100%; visibility: inherit;">
						<div class="hd">
							Installation Steps
						</div>
						<div class="bd" style="background-color: #f2f2f2;">
							<div class="step<?php if($step == 1) {echo(" active");} ?>">1. Database Connection</div>
							<div class="step<?php if($step == 2) {echo(" active");} ?>">2. Database Initialization</div>
							<div class="step<?php if($step == 3) {echo(" active");} ?>">3. Administrator Setup</div>
							<div class="step<?php if($step == 4) {echo(" active");} ?>">4. Administrator Review</div>
							<div class="step<?php if($step == 5) {echo(" active");} ?>">5. File and URL Locations</div>
							<div class="step<?php if($step == 6) {echo(" active");} ?>">6. File Locations Review</div>
							<div class="step<?php if($step == 7) {echo(" active");} ?>">7. Finished</div>
						</div>
					</div>
				</div>
				<div class="yui-u">
					<div class="yui-module yui-overlay yui-panel" <?php if ($step != 0) {echo('style="display: none;"');} ?>>
						<div class="hd">Preliminary Checkup</div>
						<div class="bd">
							<?php
							if ($errormessage) {
								echo ('<div class="errormessage">'.$errormessage.'</div>');
							} elseif ($message) {
								echo ('<div class="message">'.$message.'</div>');
							}
							?>
						</div>
					</div>
					<div class="yui-module yui-overlay yui-panel" <?php if ($step != 1) {echo('style="display: none;"');} ?>>
						<div class="hd">Step 1: Database Connection</div>
						<div class="bd">
							Now tell us about your database server. The database and the database user account need to exist already, we can't create 
							them for you.
							<br><br>
<!-- 
							If you don't have a database server set up, you can choose SQLite and we will create the database for you.
							(SQLite is not yet implemented, please use PostgreSQL.)
 -->
							<form action="install.php" method="post">
								<input type="hidden" name="step" value="1">
								<table border="0" cellspacing="0" cellpadding="2">
									<tr>
										<td>Database Type:</td>
										<td>
											<select name="database_type">
												<option value="postgre" <?php if ($db_dbdriver == 'postgre') { echo('selected'); } ?>>PostgreSQL</option>
											</select>
										</td>
									</tr>
									<tr>
										<td>Database Host:</td>
										<td><input type="text" name="database_host" value="<?php echo($db_hostname); ?>" maxlength="64"></td>
									</tr>
									<tr>
										<td>Database Port:</td>
										<td><input type="text" name="database_port" value="<?php echo($db_port); ?>" maxlength="64"> (optional)</td>
									</tr>
									<tr>
										<td>Database Name:</td>
										<td><input type="text" name="database_name" value="<?php echo($db_database); ?>" maxlength="64"></td>
									</tr>
									<tr>
										<td>Database Username:</td>
										<td><input type="text" name="database_username" value="<?php echo($db_username); ?>" maxlength="64"></td>
									</tr>
									<tr>
										<td>Database Password:</td>
										<td><input type="text" name="database_password" value="<?php echo($db_password); ?>" maxlength="64"></td>
									</tr>
								</table>
								<?php if($success) { ?> 
								<div style="float:right">
									<input type="submit" name="submit" value="Next &gt;&gt;">
								</div>
								<?php } ?> 
								<div class="clear"><!-- --></div>
							</form>
						</div>
					</div>
					<div class="yui-module yui-overlay yui-panel" <?php if ($step != 2) {echo('style="display: none;"');} ?>>
						<div class="hd">Step 2: Database Initialization</div>
						<div class="bd">
							<form action="install.php" method="post">
								<input type="hidden" name="step" value="2">
								<div style="margin-bottom: 10px">
									<?php if ($success) { ?>
										<p class="success">Success!</p>
										<?php if ($db_created) { ?>
											<p>Your database was created successfully.</p>
										<?php } else { ?>
											<p>We were able to connect to your database successfully. Your database already exists, so there's nothing
											more that we need to do here.</p>
										<?php } ?>
									<?php  } else { ?>
										<p class="failure">We had a problem...</p>
										<p>We had some trouble connecting to or creating your database. The exact message is:</p>
										<div class="errormessage">
											<?php echo($errormessage) ?>
										</div>
									<?php } ?> 
									<br>
								</div>
								<div style="float:left">
									<input type="submit" name="submit" value="&lt;&lt; Back">
								</div>
								<?php if($success) { ?> 
								<div style="float:right">
									<input type="submit" name="submit" value="Next &gt;&gt;">
								</div>
								<?php } ?> 
								<div class="clear"><!-- --></div>
							</form>
						</div>
					</div>
					<div class="yui-module yui-overlay yui-panel" <?php if ($step != 3) {echo('style="display: none;"');} ?>>
						<div class="hd">Step 3: Administrator Setup</div>
						<div class="bd">
							<?php if ($errormessage) {
									echo ('<div class="errormessage">'.$errormessage.'</div>');
								} ?>
							<p>Next, let's entering some information about the administrative user.</p>
							<form action="install.php" method="post">
								<input type="hidden" name="step" value="3">
								<table border="0" cellspacing="0" cellpadding="2">
									<tr>
										<td>Full Name:</td>
										<td><input type="text" name="admin_full_name" value="<?php echo($admin_fullname); ?>" maxlength="64"></td>
									</tr>
									<tr>
										<td>Email Address:</td>
										<td><input type="text" name="admin_email" value="<?php echo($admin_email); ?>" maxlength="64"></td>
									</tr>
									<tr>
										<td>Username:</td>
										<td><?php echo($admin_username); ?></td>
									</tr>
									<tr>
										<td>Password:</td>
										<td><input type="password" name="admin_password" value="" maxlength="64"></td>
									</tr>
									<tr>
										<td>Confirm Password:</td>
										<td><input type="password" name="admin_password_c" value="" maxlength="64"></td>
									</tr>
								</table>
								<div style="float:left">
									<input type="submit" name="submit" value="&lt;&lt; Back">
								</div>
								<?php if($success) { ?> 
								<div style="float:right">
									<input type="submit" name="submit" value="Next &gt;&gt;">
								</div>
								<?php } ?> 
								<div class="clear"><!-- --></div>
							</form>
						</div>
					</div>
					<div class="yui-module yui-overlay yui-panel" <?php if ($step != 4) {echo('style="display: none;"');} ?>>
						<div class="hd">Step 4: Administrator Review</div>
						<div class="bd">
							<form action="install.php" method="post">
								<input type="hidden" name="step" value="4">
								<div style="margin-bottom: 10px">
									<?php if ($success) { ?>
										<p class="success">Success!</p>
										<p>Administrator settings were saved correctly.</p>
									<?php  } else { ?>
										<p class="failure">We had a problem...</p>
										<p>We had some trouble saving the administrator settings.</p>
										<div class="errormessage">
											<?php echo($errormessage) ?>
										</div>
									<?php } ?> 
									<br>
								</div>
								<div style="float:left">
									<input type="submit" name="submit" value="&lt;&lt; Back">
								</div>
								<?php if($success) { ?> 
								<div style="float:right">
									<input type="submit" name="submit" value="Next &gt;&gt;">
								</div>
								<?php } ?> 
								<div class="clear"><!-- --></div>
							</form>
						</div>
					</div>
					<div class="yui-module yui-overlay yui-panel" <?php if ($step != 5) {echo('style="display: none;"');} ?>>
						<div class="hd">Step 5: File and URL Locations</div>
						<div class="bd">
							Please verify the following information.<br><br>
							<form action="install.php" method="post">
								<input type="hidden" name="step" value="5">
								<table border="0" cellspacing="0" cellpadding="2">
									<tr>
										<td valign="top">Base Path:</td>
										<td>
											<input type="text" name="base_path" value="<?php echo($base_path); ?>" maxlength="1024" style="width: 450px;">
											<div class="grey">This should already be correct and should not be changed.</div>
										</td>
									</tr>
									<tr>
										<td valign="top">Base URL:</td>
										<td>
											<input type="text" name="base_url" value="<?php echo($base_url); ?>" maxlength="1024" style="width: 450px">
											<div class="grey">Must include "http://". This is accurate but it may need to be updated to a fully-qualified domain name.</div>
										</td>
									</tr>
									<tr>
										<td valign="top">New Items URL:</td>
										<td>
											<input type="text" name="new_items_url" value="<?php echo($new_items_url); ?>" maxlength="1024" style="width: 450px">
											<div class="grey">Must include "http://". This will be checked to be sure it returns XML data, but the structure of the data will not be verified.</div>
										</td>
									</tr>
								</table>
								<div style="float:left">
									<input type="submit" name="submit" value="&lt;&lt; Back">
								</div>
								<?php if($success) { ?> 
								<div style="float:right">
									<input type="submit" name="submit" value="Next &gt;&gt;">
								</div>
								<?php } ?> 
								<div class="clear"><!-- --></div>
							</form>
						</div>
					</div>
					<div class="yui-module yui-overlay yui-panel" <?php if ($step != 6) {echo('style="display: none;"');} ?>>
						<div class="hd">Step 6: File Locations Review</div>
						<div class="bd">
							<form action="install.php" method="post">
								<input type="hidden" name="step" value="6">
								<div style="margin-bottom: 10px">
									<?php foreach ($paths as $p) { ?>
										<p>
											<strong><?php echo($p['name']) ?>:</strong> <?php echo($p['path']) ?><br>
											<strong>Status: </strong>
											<span class="<?php echo($p['success'] ? 'success' : 'failure')?> small"><?php echo($p['message']) ?></span>
										</p>
									<?php } ?>
									<br>
								</div>
								<div style="float:left">
									<input type="submit" name="submit" value="&lt;&lt; Back">
								</div>
								<?php if($success) { ?> 
								<div style="float:right">
									<input type="submit" name="submit" value="Next &gt;&gt;">
								</div>
								<?php } ?> 
								<div class="clear"><!-- --></div>
							</form>
						</div>
					</div>
					<div class="yui-module yui-overlay yui-panel" <?php if ($step != 7) {echo('style="display: none;"');} ?>>
						<div class="hd">Setup Complete!</div>
						<div class="bd">
							<div style="margin-bottom: 10px">
								<div class="success">Macaw is set up and ready to go!</div>
								<div class="warning">
									<span style="color:#990000;font-weight:bold;">IMPORTANT:</span> Please delete the file <strong>/Users/joelrichard/Sites/macaw/docs/install.php</strong> before logging in.<br><br>
									You may also now remove web server write permissions to the configuration directory: <strong><?php echo($config['macaw']['base_directory']); ?>/system/application/config</strong>
								</div>
								<p>Start working in Macaw by logging in here: <a href="<?php echo($config['base_url']); ?>"><?php echo($config['base_url']); ?></a></p>							
							
								<p>
									Below is a summary of the settings you made. Other settings may be adjusted in the <strong>/system/application/config/macaw.php</strong> file.
									<blockquote>
										<h3>Administrator Information</h3>
										<blockquote>
											<strong>Full Name:</strong> <?php echo($admin_fullname); ?><br>
											<strong>Username:</strong> admin<br>
											<strong>Password:</strong> **********
										</blockquote>
	
										<h3>Databsase Information</h3>
										<blockquote>
											<strong>Type:</strong> <?php echo($database_driver); ?><br>
											<strong>Host:</strong> <?php echo($db['default']['hostname']); ?> <br>
											<strong>Port:</strong> <?php echo($db['default']['port'] ? $db['default']['port'] : 5432); ?> <br>
											<strong>Database Name:</strong> <?php echo($db['default']['database']); ?><br>
											<strong>Username:</strong> <?php echo($db['default']['username']); ?><br>
											<strong>Password:</strong> **********
										</blockquote>
										
										<h3>Paths</h3>
										<blockquote>
											<strong>Base URL:</strong> <?php echo($config['base_url']); ?><br>
											<strong>Base Directory:</strong> <?php echo($config['macaw']['base_directory']); ?><br>
											<strong>Data Directory:</strong> <?php echo($config['macaw']['data_directory']); ?><br>
											<strong>Logs Directory:</strong> <?php echo($config['macaw']['logs_directory']); ?><br>
											<strong>Purge Directory:</strong> <?php echo($config['macaw']['purge_path']); ?><br>
											<strong>New Items URL:</strong> <?php echo($config['macaw']['new_items_url']); ?><br>
										</blockquote>
									</blockquote>
								</p>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</body>
</html>


