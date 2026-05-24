<?php 
define('BASEPATH', 'foobar');
define('ENVIRONMENT', 'production');

require_once('application/config/database.php');

$message = '';
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
        $message = "Session table updated.";
    } else {
        $message = "Session table did not need any changes.";
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
        $message = "Session table updated.";
    } else {
        $message = "Session table did not need any changes.";
    }
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

</head>
<body class="yui-skin-sam">
	<div id="logincontainerborder">
        <div id="logincontainer">
            <div id="loginheader">
                <img id="hero" width="318" height="483" alt="Rosellas" src="images/rosellas_macaw_login.png">
                <h1>Macaw</h1>
                <h2>Metadata Collection and Workflow System</h2>
                <hr>
                    <h3>Upgrade Results</h3>
            </div>	
            <div id="logincontent"  style="color: black; font-size:125%">
                <p><strong><?php print $message; ?></strong></p>
                <p><a href="/">Continue to Login</a></p>
            </div>          
        </div>
	</div>
	<div id="credit">
		 Based on the Paginator originally created<br>
		 at the Missouri Botanical Garden.
	</div>
    <div class="clear"><!-- --></div>
</body>
</html>
