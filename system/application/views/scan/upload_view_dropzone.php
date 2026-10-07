<?php include('system/application/config/version.php'); ?>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta http-equiv="content-type" content="text/html; charset=utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=9" />
	<title>Scan | Upload | Macaw</title>

	<!-- Dropzone styles -->
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/min/dropzone.min.css">
	<link rel="stylesheet" href="/css/dropzone-custom.css">
	<?php $this->load->view('global/head_view') ?>
	<script type="text/javascript">
		function init() {
			MessageBox.init();
		}
		window.addEventListener('DOMContentLoaded', init);
	</script>
</head>
<body>
	<?php $this->load->view('global/header_view') ?>

	<?php if ($free < $this->cfg['low_disk_space_cutoff']) { ?>
		<div class="container">
			<h2 style="text-align:center">Uploads are disabled because Macaw is low on disk space! (<?php echo($free.'%') ?> free)</h2>
			<h3 style="text-align:center">There are currently <?php echo($exporting) ?> items being uploaded by or processed at the Internet Archive.</h3>
			<h3 style="text-align:center">Please check back in a few hours.</h3>
			<h4 style="text-align:center;margin-top:30px;font-weight:normal;">(This message will disappear when macaw has made more space available for new items.)</h4>
		</div>
	<?php } elseif ($used >= $this->cfg['upload_cutoff']) { ?>
		<div class="container">
			<script type="text/javascript">
				var mydata = {url:'/admin/user_export_data'};
				YAHOO.util.Event.onDOMReady(ListItems.init, mydata, true);
			</script>
			<h2 id="errormessage" class="message-static">Uploads are disabled because your organization has exceeded their allowed quota of <?php echo($this->cfg['upload_cutoff']) ?>% of usable disk space. </h2>
			<h3 style="text-align:center">Your organization is using <?php echo $used ?>% of disk space.<br><br>You will need to wait 6 to 12 hours until your items are finished exporting before uploading new files.</h3>
			<div id="userqueues" class="fulltable">
				<ul class="queueheading">
					<li class="selected"><a href="#tab2">Items Being Exported</a></li>
				</ul>
				<div id="divInProgress"></div>
			</div>
		</div>
	<?php } else { ?>
		<div class="container">
			<div style="width: 75%; margin-left: auto;margin-right:auto;">
			<?php if ($used >= $this->cfg['upload_warning']) { ?>
				<h2 id="warning" class="message-static">Your organization is using <?php echo($this->cfg['upload_warning']) ?>% or more of available disk space.</h2>
			<?php } ?>

			<p>
				Upload image files (<strong>PNG, TIFF, JP2</strong>) or PDFs for this item to the Macaw server.<br>
				You can <strong>drag &amp; drop</strong> files from your desktop on this webpage (all modern browsers).<br>
				The maximum size for each file is <strong><?php echo($upload_max_filesize) ?></strong><br>
				<span style="font-weight:bold;color:#900;">Please note: Thumbnails for existing image files are no longer displayed.</span>
			</p>
			</div>

			<div class="upload-area">
				<div id="existingFiles" class="existing-files">
					<h3>Existing Files</h3>
					<div id="filesList"></div>
				</div>

				<form id="dropzoneForm" class="dropzone" action="/scan/do_upload/" method="POST" enctype="multipart/form-data">
					<input type="hidden" id="sequence" name="sequence" value="<?php echo($max_sequence) ?>">
					<div class="dz-message">
						<div class="big-icon">📁</div>
						<p><strong>Drop files here or click to select</strong></p>
						<p style="font-size: 12px; margin-top: 10px;">Supports drag and drop</p>
					</div>
				</form>

				<div class="upload-controls" style="margin-top: 20px;">
					<div class="progress" id="uploadProgress" style="display:none;">
						<div class="progress-bar progress-bar-success" id="progressBar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%">
							<span id="progressText">0%</span>
						</div>
					</div>
					<div id="pdfmessage" class="btn btn-info" style="display:none; margin-top: 10px;">
						Processing PDF pages...
					</div>
					<div style="margin-top: 10px;">
						<button type="button" class="btn btn-primary" id="startUpload" disabled>
							<i class="glyphicon glyphicon-upload"></i> Start Upload
						</button>
						<button type="button" class="btn btn-warning" id="cancelUpload" disabled>
							<i class="glyphicon glyphicon-ban-circle"></i> Cancel Upload
						</button>
						<button type="button" class="btn btn-metadata" style="display:none;">
							<i class="glyphicon glyphicon-book"></i> Enter Page Metadata
						</button>
						<button type="button" class="btn btn-missing" style="display:none;">
							<i class="glyphicon glyphicon-sort-by-attributes"></i> Insert Missing Pages
						</button>
					</div>
				</div>
			</div>
		</div>
	<?php } ?>

	<!-- jQuery (modern version) -->
	<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
	<!-- Dropzone -->
	<script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/min/dropzone.min.js"></script>
	<!-- Dropzone initialization -->
	<script src="/js/main-dropzone.js?v=<?php echo $version_rev; ?>"></script>
	<script>
		var hasMissingPages = <?php echo ($book_has_missing_pages ? 'true' : 'false'); ?>;
		var maxSequence = <?php echo($max_sequence) ?>;
	</script>

</body>
</html>
