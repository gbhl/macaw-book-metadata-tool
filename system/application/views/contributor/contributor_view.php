<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
		"http://www.w3.org/TR/html4/strict.dtd">
<html lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=9" />
	<title>Contributors | Macaw</title>
		<link rel="stylesheet" type="text/css" href="/css/yui-combo.css"> 
	<?php $this->load->view('global/head_view') ?>
	<script type="text/javascript">
		function init() {
			MessageBox.init();
			<?php if ($is_admin) { ?>
				Organization.initList(true);
			<?php } else { ?>
				Organization.initList(false);
			<?php } ?>
		}
		YAHOO.util.Event.onDOMReady(init);
	</script>
</head>
<body class="yui-skin-sam">
	<?php $this->load->view('global/header_view') ?>
	<div id="orglist">
		<h1>All Contributors</h1>
		<div id="organizations"></div>
		<?php if ($is_admin) { ?>
		<div style="margin-top:10px">
			<button id="btnAddOrganization">Add Contributor</button>
		</div>
		<?php } ?>
	</div>	
	<div id="dlgEdit" class="yui-pe-content"></div>
	<?php $this->load->view('global/footer_view') ?>
</body>
</html>
