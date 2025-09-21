<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
		"http://www.w3.org/TR/html4/strict.dtd">
<html lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=9" />
	<title>Admin | Contributors | Macaw</title>
		<link rel="stylesheet" type="text/css" href="/css/yui-combo.css"> 
	<?php $this->load->view('global/head_view') ?>
	<script type="text/javascript">YAHOO.util.Event.onDOMReady(Organization.initList);</script>
</head>
<body class="yui-skin-sam">
	<?php $this->load->view('global/header_view') ?>
	<div id="orglist">
		<h1>All Contributors</h1>
		<div id="organizations"></div>
		<div style="margin-top:10px">
			<button id="btnAddOrganization">Add Contributor</button>
		</div>
	</div>	
	<div id="dlgEdit" class="yui-pe-content"></div>
	<?php $this->load->view('global/footer_view') ?>
</body>
</html>
