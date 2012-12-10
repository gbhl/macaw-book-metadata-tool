<? 
$CI = get_instance();
$CI->load->library('clicheck');
if ($CI->clicheck->isCli()) { 
	echo("Path not found!\n");
} else { ?>

<?php header("HTTP/1.1 404 Not Found"); ?>
<html>
<head>
	<title>404 Page Not Found</title>
	<link rel="stylesheet" type="text/css" href="http://yui.yahooapis.com/combo?2.9.0/build/reset-fonts-grids/reset-fonts-grids.css&2.9.0/build/base/base-min.css">
	<link rel="stylesheet" type="text/css" href="/css/macaw.css" id="macaw_css" />
</head>
<body class="yui-skin-sam">
<div id="doc3">
	<div id="hd">
		<img src="/images/logo.png" alt="logo.png" width="110" height="110" border="0" align="left" id="logo">
		<div id="title">
			<h2>Macaw</h2>
			<h3>Metadata Collection and Workflow System</h3>
		</div>
	</div>
	<div id="bd">
		<div id="error_content">
			<h1>I can't find the page you were looking for!</h1>

			<p>I am so terribly sorry, but I could not find the page that you are attempting to reach.
			Could there be a simple typographical error in the address? You could always try going
			back to the <a href="/">Home Page</a> and navigate from there to your destination.
			Perhaps that will help. Yes, I think that will be best.</p>
		</div>
	</div>
</div>
</body>
</html>
<? } ?>
