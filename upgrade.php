<?php 
define('BASEPATH', 'foobar');
define('ENVIRONMENT', 'production');

require_once('application/config/database.php');

$messages = [];
$continue = true;
if ($db['default']['dbdriver'] == 'mysqli') {
    # Connect
    $dbh = new mysqli(
        $db['default']['hostname'], 
        $db['default']['username'], 
        $db['default']['password'], 
        $db['default']['database']
    );
    
    # Get the stucture of the session table
    $result = $dbh->query("select COLUMN_NAME from information_schema.columns where table_name = 'session'");
    $old = false;
    foreach ($result as $row) {
        if ($row['COLUMN_NAME'] == 'session_id' || $row['COLUMN_NAME'] == 'user_agent' || 
            $row['COLUMN_NAME'] == 'last_activity' || $row['COLUMN_NAME'] == 'user_data') {
            $old = true;
        }
    }

    # Does it need updating
    if ($old) {
        # Update the strucutre of table
        $dbh->query("ALTER TABLE session CHANGE COLUMN `session_id` `id` varchar(128);");
        $dbh->query("ALTER TABLE session CHANGE COLUMN `ip_address` `ip_address` varchar(45);");
        $dbh->query("ALTER TABLE session CHANGE COLUMN `user_data` `data` blob;");
        $dbh->query("ALTER TABLE session CHANGE COLUMN `last_activity` `timestamp` int(10);");

        $dbh->query("ALTER TABLE session DROP COLUMN `user_agent`;");

        $dbh->query("CREATE INDEX idx_session_timestamp on session(timestamp);");
        $messages[] = "Session table updated.";
    } else {
        $messages[] = "Session table did not need any changes.";
    }
} elseif ($db['default']['dbdriver'] == 'postgre') {
    # Connect
    $dbh = pg_connect(
        'host='.$db['default']['hostname'].' '. 
        'dbname='.$db['default']['database'].' '.
        'user='.$db['default']['username'].' '.
        'password='.$db['default']['password']
    );
    
    # Get the stucture of the session table
    $result = $dbh->query("select column_name from information_schema.columns where table_name = 'session'");
    $old = false;
    foreach ($result as $row) {
        if ($row['column_name'] == 'session_id' || $row['column_name'] == 'user_agent' || 
            $row['column_name'] == 'last_activity' || $row['column_name'] == 'user_data') {
            $old = true;
        }
    }

    # Does it need updating
    if ($old) {
        # Update the strucutre of table
        $dbh->query('ALTER TABLE session RENAME COLUMN "session_id" TO "id";');
        $dbh->query('ALTER TABLE session RENAME COLUMN "last_activity" TO "timestamp";');
        $dbh->query('ALTER TABLE session RENAME COLUMN "user_data" TO "data";');

        $dbh->query('ALTER TABLE session ALTER COLUMN "id" TYPE varchar(128);');
        $dbh->query('ALTER TABLE session ALTER COLUMN "ip_address" TYPE varchar(45);');
        $dbh->query('ALTER TABLE session ALTER COLUMN `timestamp` TYPE bigint;');
        $dbh->query('ALTER TABLE session ALTER COLUMN "data" TYPE blob');

        $dbh->query('ALTER TABLE session DROP COLUMN `user_agent`;');

        $dbh->query('CREATE INDEX idx_session_timestamp on session(timestamp);');
        $messages[] = "Session table updated.";
    } else {
        $messages[] = "Session table did not need any changes.";
    }
}

# Check the configs
$errors = [];
require_once('application/config/config.php');
require_once('application/config/macaw.php');
if (!isset($config['sess_driver']) || $config['sess_driver'] != 'database') { 
    $errors[] = "config.php value <code>sess_driver</code> should be <code>database</code>";
}
if (!isset($config['sess_cookie_name']) || $config['sess_cookie_name'] != 'macaw_session') { 
    $errors[] = "config.php value <code>sess_cookie_name</code> should be <code>macaw_session</code>";
}
if (!isset($config['sess_samesite'])) { 
    $errors[] = "config.php value <code>sess_samesite</code> should be <code>Strict</code> or <code>Lax</code>";
}
if (!isset($config['sess_expiration'])) { 
    $errors[] = "config.php value <code>sess_expiration</code> is not set.";
}
if (!isset($config['sess_save_path']) || $config['sess_save_path'] != 'session') { 
    $errors[] = "config.php value <code>sess_save_path</code> should be <code>session</code>";
}
if (!isset($config['sess_match_ip']) || !$config['sess_match_ip']) { 
    $errors[] = "config.php value <code>sess_match_ip</code> should be <code>TRUE</code>";
}
if (!isset($config['sess_time_to_update'])) { 
    $errors[] = "config.php value <code>sess_time_to_update</code> is not set.";
}
if (!isset($config['sess_regenerate_destroy'])) { 
    $errors[] = "config.php value <code>sess_regenerate_destroy</code> is not set.";
}
if (!isset($config['cookie_secure'])) { 
    $errors[] = "config.php value <code>cookie_secure</code> is not set.";
}
if (!isset($config['cookie_httponly'])) { 
    $errors[] = "config.php value <code>cookie_httponly</code> is not set.";
}
if (!isset($config['cookie_samesite']) || $config['cookie_samesite'] != 'Strict') { 
    $errors[] = "config.php value <code>cookie_samesite</code> should be <code>Strict</code>";
}


if (preg_match('/system\//', $config['macaw']['logs_directory'])) {
    $errors[] = "macaw.php value <code>logs_directory</code> should be not contain <code>system/</code>";
}
if (!isset($config['macaw']['export_concurrency_limit'])) { 
    $errors[] = "config.php value <code>export_concurrency_limit</code> is missing.";
}
if (!isset($config['macaw']['interet_archive_tag'])) { 
    $errors[] = "config.php value <code>interet_archive_tag</code> is missing.";
}
?>


<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
        "http://www.w3.org/TR/html4/strict.dtd">
<html lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=9" />
	<title>Upgrade | Macaw</title>
    <link rel="stylesheet" type="text/css" href="css/yui-combo.css?v=2.10.0">  
    <link rel="stylesheet" type="text/css" href="css/macaw.css?v=2.10.0" id="macaw_css" />
    <link rel="stylesheet" type="text/css" href="inc/magnifier/assets/image-magnifier.css?v=2.10.0" />
    <style>
        #logincontainerborder {width: 700px;}
        #logincontainer {width: 698px;}
        #logincontent {color: black; text-align: left; font-size: 110%;}
        code {font-weight: bold;}
    </style>
</head>
<body class="yui-skin-sam">
	<div id="logincontainerborder">
        <div id="logincontainer">
            <div id="loginheader">
                <h1>Macaw</h1>
                <h2>Upgrade Results</h2>
            </div>	
            <div id="logincontent">
                <?php foreach ($messages as $m) { ?>
                    <p><?php print $m; ?></p>
                <?php } ?>
                <?php foreach ($errors as $m) { ?>
                    <p><?php print $m; ?></p>
                <?php } ?>
                <?php if (count($errors)) { ?>
                    <p>Fix these errors and reload.</p>
                <?php } else { ?>
                    <p><a href="/">Continue to Login</a></p>
                <?php } ?>
            </div>          
        </div>
	</div>
    <div class="clear"><!-- --></div>
</body>
</html>
