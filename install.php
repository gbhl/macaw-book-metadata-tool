<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
        "http://www.w3.org/TR/html4/strict.dtd">
<html lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8">
	<title>Set Up Macaw</title>
<?php
	include_once('system/application/config/version.php');
?>
	<link rel="stylesheet" type="text/css" href="/css/yui-combo.css">
	<link rel="stylesheet" type="text/css" href="/css/macaw.css" id="macaw_css" />
	<link rel="stylesheet" type="text/css" href="/inc/magnifier/assets/image-magnifier.css" />
	
	<!-- Combo-handled YUI JS files: -->
	<script type="text/javascript" src="/js/yui-combo.js"></script>
	<!-- http://developer.yahoo.com/yui/articles/hosting/?animation&autocomplete&base&button&charts&connection&container&datasource&datatable&dom&dragdrop&event&fonts&grids&json&layout&logger&menu&paginator&progressbar&reset&resize&slider&stylesheet&swf&tabview&treeview&yahoo&MIN -->
	
	<script type="text/javascript" src="/inc/magnifier/image-magnifier.js"></script>
	<script type="text/javascript" src="/inc/swf/swfobject.js"></script>
	<script type="text/javascript" src="/main/js_config"></script>
	<script type="text/javascript" src="/js/macaw.js"></script>
	<script type="text/javascript" src="/js/macaw-barcode.js"></script>
	<script type="text/javascript" src="/js/macaw-scanning.js"></script>
	<script type="text/javascript" src="/js/macaw-dashboard.js"></script>
	<script type="text/javascript" src="/js/macaw-general.js"></script>
	<script type="text/javascript" src="/js/macaw-book.js"></script>
	<script type="text/javascript" src="/js/macaw-pages.js"></script>
	<script type="text/javascript" src="/js/macaw-page.js"></script>
	<script type="text/javascript" src="/js/macaw-metadata.js"></script>
	<script type="text/javascript" src="/js/macaw-user.js"></script>
	<script type="text/javascript" src="/js/macaw-admin.js"></script>
	<script type="text/javascript" src="/js/macaw-import.js"></script>

<?php
	require_once('system/application/libraries/Authentication/phpass-0.1/PasswordHash.php');

	error_reporting(E_ALL & ~E_NOTICE); 
	ini_set('display_errors', '1');

	# Initialize some variables
	$db_created = 0;
	$step = 0;
	$paths = [];
	$admin_info = [];
	$path_info = [];
	$errors = [];
	$messages = [];
	$paths_verified = true;
	$done = 0;
	$dbh = null;


	# get the current step, if there is one.
	if (isset($_REQUEST['step'])) {
		$step = $_REQUEST['step'];
	}
	
	# Sanity checks
	$config_path = __DIR__.'/system/application/config';
	
	if (!is_writable(__DIR__) && $step < 6) {
		$errors[] = "Permission denied to write to <b>".__DIR__."</b> for user ".get_current_user().". Please make sure that the web server user has read and write permissions.";
	}
	if (!is_writable($config_path) && $step < 6) {
		$errors[] = "Permission denied to write to <b>$config_path</b> for user ".get_current_user().". Please make sure that the web server user has read and write permissions.";
	}

	if (!$errors) {
		create_config('config');
		create_config('database');
		create_config('macaw');	
	}	
	$db_config = "$config_path/database.php";
	$macaw_config = "$config_path/macaw.php";
	$ci_config = "$config_path/config.php";

	if (!$errors) {
		verify_php();
	}

	define('BASEPATH', __DIR__);

	# Try to determine if we are already installed.
	# This has the secondary effect of connecting to the database if 
	# there are database settings.
	if (file_exists($macaw_config)) {
		require_once($macaw_config);
	}
	if (file_exists($db_config)) {
		require_once($db_config);
	}

	if (!$errors && $step == 0) {
		if ($db['default']['hostname'] && $db['default']['database'] && $db['default']['username'] && $db['default']['dbdriver']) {
			$dbh = connect_database(
				$db['default']['hostname'],
				$db['default']['database'],
				$db['default']['username'],
				$db['default']['password'],
				$db['default']['port'],
			);
			if (!$dbh) {
				$step = 1;
			}
		}
		if ($dbh) {
			is_installed($dbh);	
		}
	}
	
	if (!$errors && $step == 0) {
		$step = 1;
	}

	if (!isset($_POST['submit'])) {$_POST['submit'] = '';}

	if ($_POST['submit'] == '<< Back' || $_POST['submit'] == 'Retry >>') {
		$step -= 1;
	}

	# Sanity Checks complete, let's see about the database info
	if ($step == 1) { // Gathering Database connection info
		if ($_POST['submit'] == 'Next >>') {
			if (!$_POST['database_username']) { $errors[] = "Database username is required."; }
			if (!$_POST['database_password']) { $errors[] = "Database password is required."; }
			if (!$_POST['database_name']) { $errors[] = "Database name is required."; }
			if (!$_POST['database_host']) { $errors[] = "Database host is required."; }

			// Save whatever data was passed in
			set_config($db_config, "\$db['default']['hostname']", $_POST['database_host']);
			set_config($db_config, "\$db['default']['username']", $_POST['database_username']);
			set_config($db_config, "\$db['default']['password']", $_POST['database_password']);
			set_config($db_config, "\$db['default']['database']", $_POST['database_name']);
			set_config($db_config, "\$db['default']['dbdriver']", $_POST['database_type']);
			set_config($db_config, "\$db['default']['port']",     $_POST['database_port']);
			sleep(3);

			$db['default']['hostname'] = $_POST['database_host'];
			$db['default']['username'] = $_POST['database_username'];
			$db['default']['password'] = $_POST['database_password'];
			$db['default']['database'] = $_POST['database_name'];
			$db['default']['port']     = $_POST['database_port'];

			if (!$errors) {
				$dbh = connect_database(
					$_POST['database_host'], 
					$_POST['database_name'], 
					$_POST['database_username'], 
					$_POST['database_password'],
					$_POST['database_port'],
				);
				if (!$dbh) {
					$errors[] = "Please check your database settings.";
				} else {
					create_database($dbh);

					// Put these back in memory to be used later. Maybe.
					$db['default']['hostname'] = $_POST['database_host'];
					$db['default']['username'] = $_POST['database_username'];
					$db['default']['password'] = $_POST['database_password'];
					$db['default']['database'] = $_POST['database_name'];
					$db['default']['port']     = $_POST['database_port'];
		
					$step++;
					$_POST['submit'] = null;
				}
			}
		} 
	} // if ($step == 1)

	if ($step == 2) { // Database initial setup complete
		if (isset($_POST['submit'])) {
			if ($_POST['submit'] == 'Next >>') {
				$step += 1;
				$_POST['submit'] = null;
			}
		}

	} // if ($step == 2)

	if ($step == 3) { // Gathering Administrator Login Info
		$dbh = connect_database(
			$db['default']['hostname'],
			$db['default']['database'],
			$db['default']['username'],
			$db['default']['password'],
			$db['default']['port']
		);

		// Get what we can from the database
		$stmt = $dbh->query('select * from account where id = 1');
		$admin_info = $stmt->fetch();

		$stmt = $dbh->query('select * from organization where id = 1');
		$org_info = $stmt->fetch();
		$admin_info['organization_name'] = $org_info['name'];

		if (isset($_POST['submit'])) {
			if ($_POST['submit'] == 'Next >>') {

				// Make sure that we get around the whole concept of null values and "variable not set" errors. Sheesh.
				if (!isset($_POST['admin_password'])) {$_POST['admin_password'] = '';}
				if (!isset($_POST['admin_password_c'])) {$_POST['admin_password_c'] = '';}

				// Make sure we get a password when we need one
				if (!$admin_info['password'] && !$_POST['admin_password']) {
					$errors[] = "You must enter a password and confirmation password.";
				} else {
					if ($_POST['admin_password'] || $_POST['admin_password_c']) {
						if ($_POST['admin_password'] != $_POST['admin_password_c']) {
							$errors[] = "The passwords you entered do not match.";
						}
					}
				}

				$admin_info['full_name'] = $_POST['admin_full_name'];
				$admin_info['username'] = $_POST['admin_username'];
				$admin_info['password'] = $_POST['admin_password'];
				$admin_info['email'] = $_POST['admin_email'];
				$admin_info['organization_name'] = $_POST['organization_name'];

				// Continue only if we didn't have an error
				if (!$errors) {
					// Save whatever data was passed in
					set_config($macaw_config, "\$config['macaw']['admin_email']", $admin_info['email']);
					set_config($macaw_config, "\$config['macaw']['organization_name']", $admin_info['organization_name']);
					sleep(3);

					// generate the new password hash
					$hasher = new PasswordHash(8, false);
					$pass_hash = $hasher->HashPassword($admin_info['password']);

					// Set up the organization?
					create_organization(
						$admin_info['organization_name'],
						$admin_info['full_name'],
						$admin_info['email']
					);
					
					// Set the data in the database
					$stmt = $dbh->prepare(
						'UPDATE account SET full_name = :fullname, username = :username, password = :passhash, 
						 email = :email WHERE id = 1'
					);
					$stmt->execute(array(
						':fullname' => $admin_info['full_name'],
						':username' => $admin_info['username'],
						':passhash' => $pass_hash,
						':email' => $admin_info['email']
					));
					$messages[] = 'Administrator settings were saved successfully.';
				}

				if (!$errors) {
					// Now determine what the next step is and get it displayed
					$step += 1;
					$_POST['submit'] = null;
				}
			}
		}
	} // if ($step == 3)

	if ($step == 4) { // Administrator Login setup complete

		if (isset($_POST['submit'])) {
			if ($_POST['submit'] == 'Next >>') {
				if (!$errors) {
					$step += 1;
					$_POST['submit'] = null;
				}
			}
		}
	} // if ($step == 4)
	
	if ($step == 5) {
		// Path to base, data and purge directories
		require_once($macaw_config);
		require_once($ci_config);

		// Get what might already be saved, or defaut to something 
		$path_info['base_path'] = ($config['macaw']['base_directory'] != '/path/to/webroot/htdocs' ? $config['macaw']['base_directory'] : __DIR__);
		$path_info['base_url'] = ($config['base_url'] != 'http://localhost/' ? $config['base_url'] : getBaseURL().'/');
		$path_info['incoming_path'] = ($config['macaw']['incoming_directory'] ? $config['macaw']['incoming_directory'] : __DIR__.'/incoming');

		if (isset($_POST['submit'])) {
			$paths_verified = true;
			
			if ($_POST['submit'] == 'Next >>' || $_POST['submit'] == 'Retry >>') {
				
				// Save Changes
				set_config($ci_config,    "\$config['base_url']",                    $_POST['base_url']);
				set_config($macaw_config, "\$config['macaw']['base_directory']",     $_POST['base_path']);
				set_config($macaw_config, "\$config['macaw']['incoming_directory']", $_POST['incoming_path']);
				set_config($macaw_config, "\$config['macaw']['incoming_directory_remote']", $_POST['incoming_path']);
				sleep(3);

				// Set these in memory because we sort of need them for the next steps.
				$config['base_url']                    = $_POST['base_url'];
				$config['macaw']['base_directory']     = $_POST['base_path'];
				$config['macaw']['incoming_directory'] = $_POST['incoming_path'];
				$config['macaw']['incoming_directory_remote'] = $_POST['incoming_path'];
				$config['macaw']['data_directory']     = $_POST['base_path'].'/books';
				$config['macaw']['logs_directory']     = $_POST['base_path'].'/system/application/logs';
			}
			if ($_POST['submit'] == 'Next >>' || $_POST['submit'] == 'Retry >>') {
				// Verify access to the paths
				if (!preg_match('/^https?:\/\//', $config['base_url'])) {
					array_push($paths, array(
						'name' => 'Base URL',
						'path' => $config['base_url'],
						'success' => 0,
						'message' => 'Error! The Base URL must begin with http:// or https://.'
					));
					$errors[] = 'The Base URL must begin with http:// or https://.';
					$paths_verified = false;
				} else {
					array_push($paths, array(
						'name' => 'Base URL',
						'path' => $config['base_url'],
						'success' => 1,
						'message' => 'OK.'
					));	
				}

				if (!file_exists($config['macaw']['base_directory'])) {
					array_push($paths, array(
						'name' => 'Base Directory',
						'path' => $config['macaw']['base_directory'],
						'success' => 0,
						'message' => 'Error! The path could not be found.'
					));
					$paths_verified = false;
				} else {
					array_push($paths, array(
						'name' => 'Base Directory',
						'path' => $config['macaw']['base_directory'],
						'success' => 1,
						'message' => 'Success!'
					));
				}
				
				if (!file_exists($config['macaw']['data_directory'])) {
					if (!@mkdir($config['macaw']['data_directory'], 0755)) {
						array_push($paths, array(
							'name' => 'Data Directory',
							'path' => $config['macaw']['data_directory'],
							'success' => 0,
							'message' => 'Error! The path could not be created.'
						));
						$paths_verified = false;
					} else {
						array_push($paths, array(
							'name' => 'Data Directory',
							'path' => $config['macaw']['data_directory'],
							'success' => 1,
							'message' => 'Success!'
						));					
					}
				} else {
					if (!is_writable($config['macaw']['data_directory'])) {
						array_push($paths, array(
							'name' => 'Data Directory',
							'path' => $config['macaw']['data_directory'],
							'success' => 0,
							'message' => 'Error! Could not write to this path. Please make sure the web server has read/write permissions.'
						));
						$paths_verified = false;
					} else {
						array_push($paths, array(
							'name' => 'Data Directory',
							'path' => $config['macaw']['data_directory'],
							'success' => 1,
							'message' => 'Success!'
						));
					}
				}

				if (!file_exists($config['macaw']['data_directory'].'/export')) {
					if (!@mkdir($config['macaw']['data_directory'].'/export', 0755)) {
						array_push($paths, array(
							'name' => 'Data Export Directory',
							'path' => $config['macaw']['data_directory'].'/export',
							'success' => 0,
							'message' => 'Error! The path could not be created.'
						));
						$paths_verified = false;
					} else {
						array_push($paths, array(
							'name' => 'Data Export Directory',
							'path' => $config['macaw']['data_directory'].'/export',
							'success' => 1,
							'message' => 'Success!'
						));					
					}
				} else {
					if (!is_writable($config['macaw']['data_directory'].'/export')) {
						array_push($paths, array(
							'name' => 'Data Export Directory',
							'path' => $config['macaw']['data_directory'].'/export',
							'success' => 0,
							'message' => 'Error! Could not write to this path. Please make sure the web server has read/write permissions.'
						));
						$paths_verified = false;
					} else {
						array_push($paths, array(
							'name' => 'Data Export Directory',
							'path' => $config['macaw']['data_directory'].'/export',
							'success' => 1,
							'message' => 'Success!'
						));
					}
				}

				if (!file_exists($config['macaw']['logs_directory'])) {
					mkdir($config['macaw']['logs_directory'], 0755, true);
				}
				if (!file_exists($config['macaw']['logs_directory'].'/books')) {
					mkdir($config['macaw']['logs_directory'].'/books', 0755, true);
				}
				if (!file_exists($config['macaw']['logs_directory'])) {
					array_push($paths, array(
						'name' => 'Logs Directory',
						'path' => $config['macaw']['logs_directory'],
						'success' => 0,
						'message' => 'Error! The path could not be found.'
					));
					$paths_verified = false;
				} else {
					if (!is_writable($config['macaw']['logs_directory'])) {
						array_push($paths, array(
							'name' => 'Logs Directory',
							'path' => $config['macaw']['logs_directory'],
							'success' => 0,
							'message' => 'Error! Could not write to this path. Please make sure the web server has read/write permissions.'
						));
						$paths_verified = false;
					} else {
						array_push($paths, array(
							'name' => 'Logs Directory',
							'path' => $config['macaw']['logs_directory'],
							'success' => 1,
							'message' => 'Success!'
						));
					}
				}

				if (!file_exists($config['macaw']['incoming_directory'])) {
					if (!@mkdir($config['macaw']['incoming_directory'], 0755)) {
						array_push($paths, array(
							'name' => 'Incoming Directory',
							'path' => $config['macaw']['incoming_directory'],
							'success' => 0,
							'message' => 'Error! The path could not be created.'
						));
						$success = false;
					} else {
						array_push($paths, array(
							'name' => 'Incoming Directory',
							'path' => $config['macaw']['incoming_directory'],
							'success' => 1,
							'message' => 'Success!'
						));					
					}
				} else {
					if (!is_writable($config['macaw']['incoming_directory'])) {
						array_push($paths, array(
							'name' => 'Incoming Directory',
							'path' => $config['macaw']['incoming_directory'],
							'success' => 0,
							'message' => 'Error! Could not write to this path. Please make sure the web server has read/write permissions.'
						));
						$success = false;
					} else {
						array_push($paths, array(
							'name' => 'Incoming Directory',
							'path' => $config['macaw']['incoming_directory'],
							'success' => 1,
							'message' => 'Success!'
						));
					}
				}

				$done = 1;

				if ($paths_verified) {
					// Now determine what the next step is and get it displayed
					$step += 1;
					$_POST['submit'] = null;
				}
			}
		}
	}	// if ($step == 5)
	
	if ($step == 6) {
		if (isset($_POST['submit'])) {
			if ($_POST['submit'] == 'Next >>' || $_POST['submit'] == 'Retry >>') {
				$step += 1;
				$_POST['submit'] = null;
			}
		}
	} // if ($step == 6)
	
	if ($step == 7) {
		require_once($macaw_config);
		require_once($ci_config);
		require_once($db_config);

		if (!$dbh) {
			$dbh = connect_database(
				$db['default']['hostname'],
				$db['default']['database'],
				$db['default']['username'],
				$db['default']['password'],
				$db['default']['port']
			);
		}

		// Get what we can from the database
		$stmt = $dbh->query('select * from account where id = 1');
		$admin_info = $stmt->fetch();

		$stmt = $dbh->query('select * from organization where id = 1');
		$org_info = $stmt->fetch();
		$admin_info['organization_name'] = $org_info['name'];

		// Get what might already be saved, or defaut to something 
		$path_info['base_path'] = ($config['macaw']['base_directory'] ? $config['macaw']['base_directory'] : __DIR__);
		$path_info['base_url'] = ($config['base_url'] ? $config['base_url'] : getBaseURL().'/');
		$path_info['incoming_path'] = ($config['macaw']['incoming_directory'] ? $config['macaw']['incoming_directory'] : __DIR__.'/incoming');
		$install_file = $path_info['base_path'].'/install.php';

		rename($install_file, $install_file.'.delete');
		chmod($macaw_config, 0440);
		chmod($ci_config, 0440);
		chmod($db_config, 0440);
		chmod($config_path, 0555);
	} // if ($step == 7)
	
?>

	<style>
	  #hd #title h1, #hd #title h2, #hd #title h3 {text-align:center;color:white;}
		#hd #logo {position:absolute;left:0;top:0;height: 300px;width: auto;}
		#bd {min-height:100px;}
		.bd {min-height:300px;}
		.yui-panel {width:100%; visibility:inherit;}
		.step {font-size:120%;font-weight:bold;padding:14px 10px 10px 10px;color:#888888;}
		.active {color:#000000;border:1px solid #0099CC;background-color:#ffffff;}
		.yui-gd {margin: 0 40px;}
		.yui-skin-sam .yui-panel .hd {padding-top:5px;font-size:20px;color:#0099CC;background: rgba(250,250,250,0.8);}
		.yui-skin-sam .yui-panel .bd {background-color:#ffffff;background: rgba(255,255,255,0.8);}
		.success {font-size:16px;font-weight:bold;color:#009900;margin-bottom:10px}
		.error {border:1px solid #990000;font-weight:bold;color:#CC0000;background-color:#FFCCCC;padding:15px;}
		.warning {border:1px solid #F30;color:#333;background-color:#FC9;padding:15px;margin-bottom:10px;}
		.failure {font-size:16px;font-weight:bold;color:#990000;}
		.small {font-size:100%;}
		.errormessage {font-size:120%;margin-bottom:10px;color:#990000;}
		.message {font-size:120%;margin-bottom:10px;color:#009900;}
		.grey {color: #999999; font-size: 90%;}
	</style>
</head>
<body class="yui-skin-sam">
	<div id="doc3" class="yui-t7">
		<div id="hd" role="banner">
			<img src="images/rosellas_macaw_login.png" id="logo">
			<div id="title">
				<h1>Macaw</h1>
				<h2>Metadata Collection and Workflow System</h2>
				<h3>Version <?php echo($version_rev); ?> / <?php echo($version_date); ?></h3>
			</div>
		</div>
		<div id="bd" role="main" style="min-height:auto">
			<div class="yui-gd">
				<div class="yui-u first">
					<div class="yui-module yui-overlay yui-panel" style="width: 100%; visibility: inherit;">
						<div class="hd">
							Installation Steps
						</div>
						<div class="bd">
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
					<div class="yui-module yui-overlay yui-panel"?>
												
						<div class="hd">
							<?php if ($step == 0) { echo "Preliminary Checkup"; }?>
							<?php if ($step == 1) { echo "Database Connection"; }?>
							<?php if ($step == 2) { echo "Database Initialization"; }?>
							<?php if ($step == 3) { echo "Administrator Setup"; }?>
							<?php if ($step == 4) { echo "Administrator Review"; }?>
							<?php if ($step == 5) { echo "File and URL Locations"; }?>
							<?php if ($step == 6) { echo "File Locations Review"; }?>
							<?php if ($step == 7) { echo "Finished"; }?>
						</div>

						<div class="bd">
							<?php
							if (count($messages) > 0) {
								echo ('<div class="message">'.implode('<br><br>', $messages).'</div>');
							}
							if (count($errors) > 0) {
								echo ('<div class="errormessage">'.implode('<br><br>', $errors).'</div>');
							}
							
							if ($step == 1) { step_1(); }
							if ($step == 2) { step_2(); }
							if ($step == 3) { step_3(); }
							if ($step == 4) { step_4(); }
							if ($step == 5) { step_5(); }
							if ($step == 6) { step_6(); }
							if ($step == 7) { step_7(); }
							
							?>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</body>
</html>


<?php

	function create_config($base) {
		global $config_path;
		global $errors;
		global $messages;

		$filename = $base.'.php';
		$default_filename = $base.'.default.php';
		
		if (!file_exists("$config_path/$filename")) {
			if (!copy("$config_path/$default_filename", "$config_path/$filename")) {
				$errors[] = "Unable to copy <b>$default_filename</b> to <b>$filename</b>.";
			}
		}
	}

	function connect_database($host, $db, $user, $pass, $port = '3306') {
		global $errors;
		global $messages;
				
		$options = [
			\PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
			\PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
			\PDO::ATTR_EMULATE_PREPARES   => false,
		];
		$dsn = "mysql:host=$host;dbname=$db;port=$port";
		$pdo = null;
		try {
			$pdo = new \PDO($dsn, $user, $pass, $options);
		} catch (\PDOException $e) {
			$errors[] = $e->getMessage();
		}
		return $pdo;
	}

	function is_installed($dbh) {
		global $errors;
		global $messages;

		$stmt = $dbh->query("SELECT count(*) as thecount FROM information_schema.tables WHERE table_schema = '$db';");
		$row = $stmt->fetch();
		if ($row['thecount'] == 0) {
			return;
		}

		$stmt = $dbh->query("select * from settings where name = 'installed'");
		$row = $stmt->fetch();
		
		// Do we have a setting
		if (isset($row)) {
			// Is it 1?
			if (isset($row['value']) && $row['value'] == 1) {
				$url = getBaseURL().'/';
				$errors[] = "Macaw has already been installed!";
				$errors[] = "Start using Macaw at <a href=\"$url\">$url</a>!";
			}
		}
	}

	function verify_php() {
		global $errors;
		global $messages;
				
		$matches = array();
		$version = explode('.', PHP_VERSION);
		$version_id =  ($version[0] * 10000 + $version[1] * 100);
		if ($version_id < 70200) {
			$errors[] = 'PHP must be version 7.2 or higher. Current version is "'.PHP_VERSION.'".';
		}
	
		// PHP ZIP
		$extensions = get_loaded_extensions();
		if (!in_array('zip', $extensions)) {
			$errors[] = 'PHP <strong>zip</strong> extension not found.';
		}
	
		// PHP Archive_Tar
		if (!include('Archive/Tar.php')) {
			$errors[] = 'PHP <strong>Archive_Tar</strong> extension not found..';
		}
	
		// PHP XSL
		if (!in_array('xsl', $extensions)) {
			$errors[] = 'PHP <strong>xsl</strong> extension not found.';
		}
	
		// PHP Imagick
		if (!in_array('imagick', $extensions)) {
			$errors[] = 'PHP <strong>imagick</strong> extension not found.';
		}
	}

	function create_database($dbh) {
		global $errors;
		global $messages;

		$db_created	= false;
		try {
			$result = $dbh->query('select count(*) from account');
			if ($result) {
				if ($result->fetchColumn() > 0) {
					$db_created = true;
				}
			}	
		} catch(Exception $e) {
			$db_created	= false;
		}

		if ($db_created == true) {
			$messages[] = 'Your database already exists, so there\'s nothing more that we need to do here.';
		} else {
			// The account table doesn't exist, so we go ahead and create the database
			$fh = fopen(__DIR__.'/system/application/sql/macaw-mysql.sql', 'r');
			$query = '';

			while ($line = fgets($fh, 1024000)) {
				if (substr($line, 0, 2) == '--' || trim($line) == '') {
					continue;
				}
				$query .= $line;
				if (substr(trim($query), -1) == ';' ) {
					try {
						$result = $dbh->exec($query);
					} catch (Exception $e) {
						$errors[] = 'Error performing query "<strong>'.$query.'</strong>": '.$e->getMessage();
					}
					$query = '';
				}
			}			
			if ($errors) {
				return;
			} else {
				$messages[] = 'The database was created successfully.';
			}
		}
	}

	function create_organization($name, $person, $email) {

		global $dbh; 
		global $messages; 
		global $errors; 

		try{
			$stmt = $dbh->prepare('update organization set name = :orgname, person = :fullname, email = :email where id = 1');
			$stmt->execute(array(
				':orgname' => $name,
				':fullname' => $person,
				':email' => $email
			));
			$messages[] = 'Organization info updated.';
	
		} catch (Exception $e) {
			$errors[] = 'Error setting up the organization';
			$errors[] = $e->getMessage();
		}
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
		$path = $_SERVER['REQUEST_URI'];
		$path = preg_replace('/\/install.php/', '', $path);
		return $pageURL.$path;
	}

	function eol() {
		if (PHP_OS_FAMILY == 'Windows') {
			return "\r\n";
		} else {
			return "\n";
		}
	}

	function step_1() {
		global $db; 
		$db_dbdriver = $db['default']['dbdriver'];
		$db_hostname = $db['default']['hostname'];
		$db_username = $db['default']['username'];
		$db_password = $db['default']['password'];
		$db_database = $db['default']['database'];
		$db_port = '';
		if (isset($db['default']['port'])) { $db_port = $db['default']['port']; }

		?>
		<p>Now tell us about your database server. The database and the database user account need to exist already, we can't create
		them for you.</p>

		<p>Example:</p>
<pre>
create database macaw;
create user 'macaw'@'localhost' identified by 'PASSWORD';
grant all on macaw.* to 'macaw'@'localhost';

</pre>

		<form action="install.php" method="post">
			<input type="hidden" name="step" value="1">
			<table >
				<tr>
					<td>Database Type:</td>
					<td>
						Only MySQL is supported.
						<input type="hidden" name="database_type" value="mysqli">
					</td>
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
					<td><input type="password" name="database_password" value="<?php echo($db_password); ?>" maxlength="64"></td>
				</tr>
				<tr>
					<td>Host:</td>
					<td><input type="text" name="database_host" value="<?php echo($db_hostname); ?>" maxlength="64"> (optional)</td>
				</tr>
				<tr>
					<td>Port:</td>
					<td><input type="text" name="database_port" value="<?php echo($db_port); ?>" maxlength="64"> (optional)</td>
				</tr>
			</table>
			<div style="float:right">
				<input type="submit" name="submit" value="Next &gt;&gt;">
			</div>
			<div class="clear"><!-- --></div>
		</form>

		<?php		
	}

	function step_2() {
		global $errors; 
		?>
		<form action="install.php" method="post">
			<input type="hidden" name="step" value="2">
			<div style="float:left">
				<input type="submit" name="submit" value="&lt;&lt; Back">
			</div>
			<?php if(!$errors) { ?>
				<div style="float:right">
					<input type="submit" name="submit" value="Next &gt;&gt;">
				</div>
			<?php } ?>
			<div class="clear"><!-- --></div>
		</form>
		<?php		
	}

	function step_3() {
		global $admin_info;
		global $errors; 

		?>
		<p>Next, let's set up the administrator account.</p>
		<form action="install.php" method="post">
			<input type="hidden" name="step" value="3">
			<table cellspacing="0" cellpadding="2">
				<tr>
					<td>Organization Name:</td>
					<td><input type="text" name="organization_name" value="<?php echo($admin_info['organization_name']); ?>" maxlength="64"></td>
				</tr>
				<tr>
					<td>Full Name:</td>
					<td><input type="text" name="admin_full_name" value="<?php echo($admin_info['full_name']); ?>" maxlength="64"></td>
				</tr>
				<tr>
					<td>Email Address:</td>
					<td><input type="text" name="admin_email" value="<?php echo($admin_info['email']); ?>" maxlength="64"></td>
				</tr>
				<tr>
					<td>Username:</td>
					<td><input type="text" name="admin_username" value="<?php echo($admin_info['username']); ?>" maxlength="20"></td>
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
			<?php if(!$errors) { ?>
				<div style="float:right">
					<input type="submit" name="submit" value="Next &gt;&gt;">
				</div>
			<?php } ?>
			<div class="clear"><!-- --></div>
		</form>
		<?php		
	}

	function step_4() {
		global $errors; 
		?>
		<form action="install.php" method="post">
			<input type="hidden" name="step" value="4">
			<div style="float:left">
				<input type="submit" name="submit" value="&lt;&lt; Back">
			</div>
			<?php if(!$errors) { ?>
				<div style="float:right">
					<input type="submit" name="submit" value="Next &gt;&gt;">
				</div>
			<?php } ?>
			<div class="clear"><!-- --></div>
		</form>
		<?php		
	}

	function step_5() {
		global $path_info;
		global $errors; 
		?>
		Please verify the following information.<br><br>
		<form action="install.php" method="post">
			<input type="hidden" name="step" value="5">
			<table border="0" cellspacing="0" cellpadding="2">
				<tr>
					<td valign="top">Base Path:</td>
					<td>
						<input type="text" name="base_path" value="<?php echo($path_info['base_path']); ?>" maxlength="1024" style="width: 450px;">
						<div class="grey">This was identified automatically and should not be changed.</div>
					</td>
				</tr>
				<tr>
					<td valign="top">Base URL:</td>
					<td>
						<input type="text" name="base_url" value="<?php echo($path_info['base_url']); ?>" maxlength="1024" style="width: 450px">
						<div class="grey">Must include "http://" or "https://". This was identified automatically but it may need to be updated to a fully-qualified domain name.</div>
					</td>
				</tr>
				<tr>
					<td valign="top">Incoming Directory:</td>
					<td>
						<input type="text" name="incoming_path" value="<?php echo($path_info['incoming_path']); ?>" maxlength="1024" style="width: 450px">
						<div class="grey">This is where Macaw will look for new pages for books. Must be an absolute path on the server.</div>
					</td>
				</tr>
			</table>

			<div style="float:left">
				<input type="submit" name="submit" value="&lt;&lt; Back">
			</div>
			<?php if(!$errors) { ?>
				<div style="float:right">
					<input type="submit" name="submit" value="Next &gt;&gt;">
				</div>
			<?php } ?>
			<div class="clear"><!-- --></div>
		</form>
		<?php		
	}

	function step_6() {
		global $paths;
		global $paths_verified;
		?>
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
			<?php if($paths_verified) { ?>
				<div style="float:right">
					<input type="submit" name="submit" value="Next &gt;&gt;">
				</div>
			<?php } else {?>
				<div style="float:right">
					<input type="submit" name="submit" value="Retry &gt;&gt;">
				</div>		
			<?php } ?>
			<div class="clear"><!-- --></div>
		</form>
		<?php		
	}

	function step_7() {
		global $admin_info;
		global $path_info;
		global $db; 
		global $config; 
		?>
		<div style="margin-bottom: 10px">
			<div class="success">Macaw is set up and ready to go!</div>

			<h1><a href="<?php echo($config['base_url']); ?>"><?php echo($config['base_url']); ?></a></h1>

			<p>
				Below is a summary of your settings. Other settings may be adjusted in the <strong>/system/application/config/macaw.php</strong> file.
				<blockquote>
					<h3>Administrator Information</h3>
					<blockquote>
						<strong>Full Name:</strong> <?php echo($admin_info['full_name']); ?><br>
						<strong>Organization:</strong> <?php echo($admin_info['organization_name']); ?><br>
						<strong>Username:</strong> <?php echo($admin_info['username']); ?><br>
						<strong>Password:</strong> **********
					</blockquote>

					<h3>Databsase Information</h3>
					<blockquote>
						<strong>Type:</strong> <?php echo($db['default']['dbdriver']); ?><br>
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
						<strong>Incoming Directory:</strong> <?php echo($config['macaw']['incoming_directory']); ?><br>
						<strong>Logs Directory:</strong> <?php echo($config['macaw']['logs_directory']); ?><br>
					</blockquote>
				</blockquote>
			</p>

			<div class="warning">
				<span style="color:#990000;font-weight:bold;">IMPORTANT:</span> The file <strong><?php echo($config['macaw']['base_directory']); ?>/install.php</strong> has been deleted for security reasons.
			</div>
		</div>
		<?php		
	}


?>