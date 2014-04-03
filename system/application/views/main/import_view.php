<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
        "http://www.w3.org/TR/html4/strict.dtd">
<html lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8">
	<title>Macaw</title>
	<?php $this->load->view('global/head_view') ?>
	<script type="text/javascript">YAHOO.util.Event.onDOMReady(Import.init);</script>
</head>
<body class="yui-skin-sam">
	<?php $this->load->view('global/header_view') ?>
	<?php $this->load->view('global/error_messages_view') ?>
	<div id="import">
		<h1>Import CSV File</h1>
		<form id="upload_form" enctype="multipart/form-data" name="upload_form">
			<input type="file" id="userfile" name="userfile"><button id="btnImport">Go</button>
		</form>
		<div id="progress" style="display:none">
			Please wait while the file is imported...
			<div id="bar"></div>
			<div id="message"></div>
		</div>
	</div>
	<?php $this->load->view('global/footer_view') ?>
</body>
</html>
