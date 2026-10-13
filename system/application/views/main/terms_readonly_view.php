<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
        "http://www.w3.org/TR/html4/strict.dtd">
<html lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=9" />
	<title>Macaw</title>
	<?php $this->load->view('global/head_view') ?>
</head>
<body class="yui-skin-sam">
	<?php 
		$this->load->view('global/header_view');
		$this->load->view('global/error_messages_view');
		$base_url = $this->config->item('base_url');
	?>

	<h1>Terms and Conditions</h1>
	<div id="main" class="terms">
		
		<div id="terms-view" style="width: 70%;text-align: left;margin-left: auto; margin-right: auto;font-family: monospace;">
			<?php echo ($terms); ?>
		</div>
	</div>
	<?php $this->load->view('global/footer_view') ?>
</body>
</html>
