<?php 
if (!function_exists('get_instance')) {
		header("HTTP/1.1 404 Not Found");
		echo("Path not found!\n");
} else {
	$CI = get_instance();
	$CI->load->library('clicheck');
	if ($CI->clicheck->isCli()) { 
		echo("Path not found!\n");
	} else { ?>
	
	<?php header("HTTP/1.1 404 Not Found"); ?>
	<html>
	<head>
		<title>404 Page Not Found</title>
		<link rel="stylesheet" type="text/css" href="<?php echo $CI->config->item('base_url'); ?>css/yui-combo.css">
		<link rel="stylesheet" type="text/css" href="<?php echo $CI->config->item('base_url'); ?>css/macaw.css" id="macaw_css" />
	</head>
	<body class="yui-skin-sam">
	<div id="doc3">
		<div id="hd">
			<img src="<?php echo $CI->config->item('base_url'); ?>images/logo.png" alt="logo.png" width="110" height="110" border="0" align="left" id="logo">
			<div id="title">
				<h2 style="color:white;">Macaw</h2>
				<h3 style="color:white;">Metadata Collection and Workflow System</h3>
			</div>
		</div>
		<div id="bd">
			<div id="error_content">
				<h1>I can't find the page you were looking for! (404 Not Found)</h1>
	
				<p>I am so terribly sorry, but I could not find the page that you are looking for!</p>
				
				<p>Try going to the <strong><a href="/">Home Page</a></strong> and navigate from there.</p>
			</div>
		</div>
	</div>
	</body>
	</html>
	<?php } ?>
<?php } ?>
