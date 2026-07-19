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
		table{border:0;}
		td {vertical-align:top;margin:50px;border:0;}
		.section { margin: 0; padding: 15px; border: 1px solid #ccc; border-radius: 5px;}
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

	<h1>Admin Maintenance</h1>
	<table id="maintenance" style="margin: 0 auto;">
		<tr>
			<!-- Log Files Summary -->
			<td width="50%">
				<h2>Log Files Summary</h2>
				<div class="section">
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
						<p style="color: #009900;font-weight:bold">All log files are within the retention period.</p>
					<?php endif; ?>
				</div>
				<h2>Unused Item Directories</h2>
				<div class="section">
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
						<p style="color: #009900;font-weight:bold">No completed items found to clean up.</p>
					<?php endif; ?>					
				</div>
			</td>
			<!-- Test emails to the admin -->
			<td width="50%">
				<!-- <h2>Test Email Settings</h2>
				<div class="section">
					<p>Send a test email to verify that email settings are configured correctly.</p>
					<button type="button" id="btnTestEmail" class="button">Send Test Email</button>
				</div> -->
				<h2>Old Internet Archive Content</h2>
				<div class="section">
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
						<p style="color: #009900;font-weight:bold">No Internet Archive export directories found for completed items.</p>
					<?php endif; ?>
				</div>
			</td>
		</tr>
	</table>

	<?php $this->load->view('global/footer_view') ?>
</body>
</html>
