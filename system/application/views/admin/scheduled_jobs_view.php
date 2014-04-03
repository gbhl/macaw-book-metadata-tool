<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
        "http://www.w3.org/TR/html4/strict.dtd">
<html lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8">
	<title>Admin | Macaw</title>
	<?php $this->load->view('global/head_view') ?>
	<script type="text/javascript">
		/* Initialization script, called when the page is ready */
		function init() {
			var obtnListAccounts = new YAHOO.widget.Button("btnListAccounts");
			var obtnListOrganizations = new YAHOO.widget.Button("btnListOrganizations");
			var obtnQueues = new YAHOO.widget.Button("btnQueues");
			var obtnViewLogs = new YAHOO.widget.Button("btnViewLogs");
			var obtnCronNewItems = new YAHOO.widget.Button("btnCronNewItems");
			var obtnCronPages = new YAHOO.widget.Button("btnCronPages");
			var obtnCronExport = new YAHOO.widget.Button("btnCronExport");
			var obtnCronStats = new YAHOO.widget.Button("btnCronStats");
			var obtnBack = new YAHOO.widget.Button("btnBack");

			obtnCronNewItems.on("click", function(o) {General.runCronAction('new_items');} );
			obtnCronPages.on("click", function(o) {General.runCronAction('import_pages');} );
			obtnCronExport.on("click", function(o) {General.runCronAction('export');} );
			obtnCronStats.on("click", function(o) {General.runCronAction('statistics');} );
			obtnBack.on("click", function(o) {window.location = sBaseUrl+'/main';} );
		}
		YAHOO.util.Event.onDOMReady(init);
	</script>
</head>
<body id="manualbody" class="yui-skin-sam">
	<?php $this->load->view('global/header_view') ?>
			<div class="actionlist">
			<div class="action-one">
				<button type="button" id="btnCronNewItems">New Items</button>
				<button type="button" id="btnCronPages">Import Pages</button>
				<button type="button" id="btnCronExport">Export Items</button>
				<button type="button" id="btnCronStats">Daily Stats</button>
			</div>
		</div>

		<?php $this->load->view('global/footer_view') ?>
</body>
</html>