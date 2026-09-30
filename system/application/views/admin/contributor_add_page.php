<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
        "http://www.w3.org/TR/html4/strict.dtd">
<html lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=9" />
	<title>Add New Contributor | Macaw</title>
	<?php $this->load->view('global/head_view') ?>
	<script type="text/javascript">
		function init() {
			MessageBox.init();
			var obtnSave = new YAHOO.widget.Button("btnSave");
			obtnSave.on('click', function() { Dom.get('edit_form').submit(); });
		}
		YAHOO.util.Event.onDOMReady(init);
	</script>

</head>
<body class="yui-skin-sam">
	<?php $this->load->view('global/header_view') ?>
	<div id="edit">
		<h1>Add New Contributor</h1>
		<?php $this->load->view('admin/contributor_edit_fields') ?>
	</div>
	<?php $this->load->view('global/footer_view') ?>
</body>
</html>
