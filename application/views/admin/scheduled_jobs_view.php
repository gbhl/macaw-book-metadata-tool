<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
        "http://www.w3.org/TR/html4/strict.dtd">
<html lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=9" />
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
			var obtnCronExport = new YAHOO.widget.Button("btnCronExport");
			var obtnCronStats = new YAHOO.widget.Button("btnCronStats");
			var obtnCronResetDemo = new YAHOO.widget.Button("btnCronResetDemo");

			obtnCronNewItems.on("click", function(o) {General.runCronAction('new_items');} );
			obtnCronExport.on("click", function(o) {General.runCronAction('export');} );
			obtnCronStats.on("click", function(o) {General.runCronAction('statistics');} );
			obtnCronResetDemo.on("click", function(o) {General.runCronAction('clean_demo');} );
		}
		YAHOO.util.Event.onDOMReady(init);
	</script>
</head>
<body id="manualbody" class="yui-skin-sam">
	<?php $this->load->view('global/header_view') ?>
	<table id="admin_scheduled_jobs">
		<tr>
			<td width="30%"><button type="button" id="btnCronNewItems">New Items</button></td>
			<td width="70%">
				Scan for new items that are to be created in Macaw. Not used for versions of Macaw that are used for BHL. 
			</td>
		</tr>
<!-- 
		<tr>
			<td><button type="button" id="btnCronPages">Import Pages</button></td>
			<td>
				Force start or restart importing pages. Not used for versions of Macaw that are used for BHL. 
			</td>
		</tr>
 -->
		<tr>
			<td><button type="button" id="btnCronExport">Export Items</button></td>
			<td>
				Initiate the process for exporting items using Macaw's various export modules. The currently configured export
				modules are <strong><?php echo $export_modules; ?></strong>
			</td>
		</tr>
		<tr>
			<td><button type="button" id="btnCronStats">Daily Stats</button></td>
			<td>
				Refresh the daily stats shown on the dashboard page after logging into Macaw. This will not overwrite 
				the current day's stats. This is useful if there is no scheduled job to automatically gather the statistics
				every night.
			</td>
		</tr>
		<tr>
			<td><button type="button" id="btnCronResetDemo">Reset Demo Items</button></td>
			<td>
				Delete all items, files, and data related to the Contributor that is marked as the Demo contributor. The 
				demo contributor is currently configured as <strong><?php echo $demo_org; ?></strong>.
			</td>
		</tr>
	</table>
	<?php $this->load->view('global/footer_view') ?>
</body>
</html>