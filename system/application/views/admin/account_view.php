<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
		"http://www.w3.org/TR/html4/strict.dtd">
<html lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=9" />
	<title>Admin | Accounts | Macaw</title>
			<link rel="stylesheet" type="text/css" href="http://yui.yahooapis.com/combo?2.9.0/build/reset-fonts-grids/reset-fonts-grids.css&2.9.0/build/base/base-min.css&2.9.0/build/assets/skins/sam/skin.css"> 
	<?php $this->load->view('global/head_view') ?>
	<script type="text/javascript">YAHOO.util.Event.onDOMReady(User.initList);</script>
</head>
<body class="yui-skin-sam">
	<?php $this->load->view('global/header_view') ?>
	<div id="listaccounts">
		<h1>List Accounts</h1>
		
		<div id="accounts"></div>
		<div id="key">
			<h3>Legend</h3>
			<div><img src="<?php echo $this->config->item('base_url'); ?>images/icons/book_open.png" height="16" width="16"> Edit and Review</div>
			<div><img src="<?php echo $this->config->item('base_url'); ?>images/icons/tick.png" height="16" width="16"> Quality Assurance</div>
			<div><img src="<?php echo $this->config->item('base_url'); ?>images/icons/building_wizard.png" height="16" width="16"> Local Admin</div>
			<div><img src="<?php echo $this->config->item('base_url'); ?>images/icons/wizard_hat.png" height="16" width="16"> Super Admin</div>
		</div>
		<div id="wrapper"><button id="btnAddAccount">Add Account</button></div>
	</div>
	<div id="dlgEdit" class="yui-pe-content"></div>
	<?php $this->load->view('global/footer_view') ?>
</body>
</html>
