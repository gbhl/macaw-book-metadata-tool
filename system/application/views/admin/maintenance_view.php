<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
        "http://www.w3.org/TR/html4/strict.dtd">
<html lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=9" />
	<title>Admin Maintenance | Macaw</title>
	<?php $this->load->view('global/head_view') ?>
	<script type="text/javascript">
		function init() {
			YAHOO.util.Event.on('btnDeleteLogs', 'click', function() {
				if (confirm('Are you sure you want to delete log files older than <?php echo $keep_log_days; ?> days?')) {
					General.makeRequest('admin/delete_old_logs', {}, function(o) {
						var result = YAHOO.lang.JSON.parse(o.responseText);
						if (result.success) {
							alert('Deleted ' + result.deleted + ' log files');
							location.reload();
						} else {
							alert('Error: ' + result.error);
						}
					});
				}
			});

			YAHOO.util.Event.on('btnDeleteDirs', 'click', function() {
				var checkboxes = document.querySelectorAll('input[name="unused_dirs"]:checked');
				if (checkboxes.length === 0) {
					alert('Please select at least one directory to delete');
					return;
				}
				if (confirm('Are you sure you want to delete ' + checkboxes.length + ' completed item directories? This cannot be undone.')) {
					var barcodes = [];
					for (var i = 0; i < checkboxes.length; i++) {
						barcodes.push(checkboxes[i].value);
					}
					General.makeRequest('admin/delete_directories', {barcodes: barcodes}, function(o) {
						var result = YAHOO.lang.JSON.parse(o.responseText);
						if (result.success) {
							alert('Deleted ' + result.deleted + ' directories');
							location.reload();
						} else {
							alert('Error: ' + result.error);
						}
					});
				}
			});

			YAHOO.util.Event.on('btnDeleteIADirs', 'click', function() {
				var checkboxes = document.querySelectorAll('input[name="ia_dirs"]:checked');
				if (checkboxes.length === 0) {
					alert('Please select at least one directory to delete');
					return;
				}
				if (confirm('Are you sure you want to delete ' + checkboxes.length + ' Internet Archive export directories? This cannot be undone.')) {
					var barcodes = [];
					for (var i = 0; i < checkboxes.length; i++) {
						barcodes.push(checkboxes[i].value);
					}
					General.makeRequest('admin/delete_ia_directories', {barcodes: barcodes}, function(o) {
						var result = YAHOO.lang.JSON.parse(o.responseText);
						if (result.success) {
							alert('Deleted ' + result.deleted + ' directories');
							location.reload();
						} else {
							alert('Error: ' + result.error);
						}
					});
				}
			});

			YAHOO.util.Event.on('btnTestEmail', 'click', function() {
				General.makeRequest('admin/test_email', {}, function(o) {
					var result = YAHOO.lang.JSON.parse(o.responseText);
					if (result.success) {
						alert(result.message);
					} else {
						alert('Error: ' + result.error);
					}
				});
			});
		}
		YAHOO.util.Event.onDOMReady(init);
	</script>
	<style>
		.section { margin: 20px 0; padding: 15px; border: 1px solid #ccc; border-radius: 5px; }
		.section h2 { margin-top: 0; }
		.directory-list { max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; }
		.directory-item { padding: 5px; border-bottom: 1px solid #eee; }
		.directory-item:last-child { border-bottom: none; }
		.directory-item input { margin-right: 10px; }
		.button { padding: 8px 15px; margin: 5px 5px 5px 0; }
	</style>
</head>
<body id="manualbody" class="yui-skin-sam">
	<?php $this->load->view('global/header_view') ?>

	<div style="padding: 20px;">
		<h1>Admin Maintenance</h1>

		<!-- Log Files Summary -->
		<div class="section">
			<h2>Log Files Summary</h2>
			<p>Current log files in the system:</p>
			<ul>
				<li>Macaw Access Logs: <?php echo $log_summary['macaw_access']; ?> files</li>
				<li>Macaw Activity Logs: <?php echo $log_summary['macaw_activity']; ?> files</li>
				<li>Macaw Cron Logs: <?php echo $log_summary['macaw_cron']; ?> files</li>
				<li>Macaw Error Logs: <?php echo $log_summary['macaw_error']; ?> files</li>
				<li>Book Logs: <?php echo $log_summary['books']; ?> files <em>(never deleted)</em></li>
			</ul>
			<p><strong>Retention Policy:</strong> Log files older than <?php echo $keep_log_days; ?> days will be automatically managed. Book logs are retained indefinitely.</p>
			<?php if ($log_summary['old_files'] > 0): ?>
				<p style="color: #d9534f;"><strong><?php echo $log_summary['old_files']; ?> log file(s) are older than <?php echo $keep_log_days; ?> days and can be deleted.</strong></p>
				<button type="button" id="btnDeleteLogs" class="button">Delete Old Log Files</button>
			<?php else: ?>
				<p style="color: #5cb85c;"><strong>All log files are within the retention period.</strong></p>
			<?php endif; ?>
		</div>

		<!-- Unused Item Directories -->
		<div class="section">
			<h2>Find Unused Item Directories</h2>
			<p>The following directories are for completed items and can be deleted to free up space:</p>
			<?php if (count($unused_directories) > 0): ?>
				<p><strong><?php echo count($unused_directories); ?> completed item(s) found.</strong></p>
				<div class="directory-list">
					<?php foreach ($unused_directories as $dir): ?>
						<div class="directory-item">
							<input type="checkbox" name="unused_dirs" value="<?php echo htmlspecialchars($dir['barcode']); ?>">
							<strong><?php echo htmlspecialchars($dir['barcode']); ?></strong>:
							<?php echo htmlspecialchars($dir['title']); ?>
							(<?php echo $dir['size_display']; ?>)
						</div>
					<?php endforeach; ?>
				</div>
				<button type="button" id="btnDeleteDirs" class="button">Delete Selected Directories</button>
			<?php else: ?>
				<p>No completed items found to clean up.</p>
			<?php endif; ?>
		</div>

		<!-- Internet Archive Export Content -->
		<div class="section">
			<h2>Find Internet Archive Export Content for Completed Items</h2>
			<p>The following directories contain Internet Archive export data for completed items and can be deleted:</p>
			<?php if (count($ia_directories) > 0): ?>
				<p><strong><?php echo count($ia_directories); ?> export(s) found.</strong></p>
				<div class="directory-list">
					<?php foreach ($ia_directories as $dir): ?>
						<div class="directory-item">
							<input type="checkbox" name="ia_dirs" value="<?php echo htmlspecialchars($dir['barcode']); ?>">
							<strong><?php echo htmlspecialchars($dir['barcode']); ?></strong>:
							<?php echo htmlspecialchars($dir['title']); ?>
							(<?php echo $dir['size_display']; ?>)
						</div>
					<?php endforeach; ?>
				</div>
				<button type="button" id="btnDeleteIADirs" class="button">Delete Selected Export Directories</button>
			<?php else: ?>
				<p>No Internet Archive export directories found for completed items.</p>
			<?php endif; ?>
		</div>

		<!-- Test Email Settings -->
		<div class="section">
			<h2>Test Email Settings</h2>
			<p>Send a test email to verify that email settings are configured correctly.</p>
			<button type="button" id="btnTestEmail" class="button">Send Test Email</button>
		</div>

		<!-- Run Cron Jobs -->
		<div class="section">
			<h2>Run Cron Jobs</h2>
			<p>Manually trigger scheduled cron jobs:</p>
			<table id="cron_jobs">
				<tr>
					<td width="30%"><button type="button" id="btnCronNewItems">New Items</button></td>
					<td width="70%">
						Scan for new items that are to be created in Macaw. Not used for versions of Macaw that are used for BHL.
					</td>
				</tr>
				<tr>
					<td><button type="button" id="btnCronExport">Export Items</button></td>
					<td>
						Initiate the process for exporting items using Macaw's various export modules.
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
						Delete all items, files, and data related to the Contributor that is marked as the Demo contributor.
					</td>
				</tr>
			</table>
			<script>
				function initCronButtons() {
					var obtnCronNewItems = new YAHOO.widget.Button("btnCronNewItems");
					var obtnCronExport = new YAHOO.widget.Button("btnCronExport");
					var obtnCronStats = new YAHOO.widget.Button("btnCronStats");
					var obtnCronResetDemo = new YAHOO.widget.Button("btnCronResetDemo");

					obtnCronNewItems.on("click", function(o) {General.runCronAction('new_items');} );
					obtnCronExport.on("click", function(o) {General.runCronAction('export');} );
					obtnCronStats.on("click", function(o) {General.runCronAction('statistics');} );
					obtnCronResetDemo.on("click", function(o) {General.runCronAction('clean_demo');} );
				}
				YAHOO.util.Event.onDOMReady(initCronButtons);
			</script>
		</div>
	</div>

	<?php $this->load->view('global/footer_view') ?>
</body>
</html>
