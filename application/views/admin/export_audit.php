<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
		"http://www.w3.org/TR/html4/strict.dtd">
<html lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=9" />
	<title>Admin | Stalled Exports</title>
	<link rel="stylesheet" type="text/css" href="/css/yui-combo.css"> 
	<?php $this->load->view('global/head_view') ?>
	<script type="text/javascript">
		var mydata = {url:'/admin/user_export_data'};
		YAHOO.util.Event.onDOMReady(Export.init, mydata, true);
	</script>
</head>
<body class="yui-skin-sam">
	<?php $this->load->view('global/header_view') ?>
	<div id="orglist">
		<h1>Current Stalled Exports</h1>
		<div id="items"></div>
	</div>
	<?php $this->load->view('global/footer_view') ?>
</body>
</html>