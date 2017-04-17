<html lang="en">
<head>
	<meta charset="utf-8">

	<meta http-equiv="content-type" content="text/html; charset=utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=9" />
	<title>Scan | Upload | Macaw</title>

	<!-- Bootstrap styles -->
	<link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css">
	<!-- blueimp Gallery styles -->
	<link rel="stylesheet" href="/js/blueimp/Gallery/css/blueimp-gallery.min.css">
	<!-- CSS to style the file input field as button and adjust the Bootstrap progress bars -->
	<link rel="stylesheet" href="/css/jquery.fileupload.css">
	<link rel="stylesheet" href="/css/jquery.fileupload-ui.css">
	<!-- CSS adjustments for browsers with JavaScript disabled -->
	<noscript><link rel="stylesheet" href="/css/jquery.fileupload-noscript.css"></noscript>
	<noscript><link rel="stylesheet" href="/css/jquery.fileupload-ui-noscript.css"></noscript>
	<?php $this->load->view('global/head_view') ?>


</head>
<body>	
	<?php $this->load->view('global/header_view') ?>

<?php if ($free < $this->cfg['low_disk_space_cutoff']) { ?>
	<h2 style="text-align:center">Uploads are disabled because Macaw is low on disk space! (<?php echo($free.'%') ?> free)</h2>
	<h3 style="text-align:center">There are currently <?php echo($exporting) ?> items being uploaded by or processed at the Internet Archive.</h3>
	<h3 style="text-align:center">Please check back in a few hours.</h3>
	<h4 style="text-align:center;margin-top:30px;font-weight:normal;">(This message will disappear when macaw has made more space available for new items.)</h2>
<?php } elseif ($used >= $this->cfg['upload_cutoff']) { ?>
	<h2 style="text-align:center">Uploads are disabled because your organization has exceeded their allowed quota of <?php echo($this->cfg['upload_cutoff']) ?>% of usable disk space. </h2>
	<h3 style="text-align:center">Additional message...</h3>
<?php } else { ?>

	<div class="container">
	
		<?php if ($used >= $this->cfg['upload_warning']) { ?>
			<h2 style="text-align:center">Warning: <?php echo($this->cfg['upload_warning']) ?>% or more used of available disk space.</h2>
		<?php } ?>
	
			<p>
			Upload image files (<strong>PNG, TIFF, JP2</strong>) or PDFs for this item to the Macaw server.<br>
			You can <strong>drag &amp; drop</strong> files from your desktop on this webpage (Chrome, Firefox, Safari, Internet Explorer 10+).<br>
			The maximum size for each file is <strong><?php echo($upload_max_filesize) ?></strong><br>
			</p>
			
			<!-- The file upload form used as target for the file upload widget -->
			<form id="fileupload" action="/scan/do_upload/" method="POST" enctype="multipart/form-data">
					<!-- Redirect browsers with JavaScript disabled to the origin page -->
					<!-- The fileupload-buttonbar contains buttons to add/delete files and start/cancel the upload -->
					<div class="row fileupload-buttonbar">
							<div class="col-lg-7">
									<!-- The fileinput-button span is used to style the file input field as button -->
									<span class="btn btn-success fileinput-button">
											<i class="glyphicon glyphicon-plus"></i>
											<span>Add files...</span>
											<input type="file" name="files[]" multiple>
									</span>
									<button type="submit" class="btn btn-primary start">
											<i class="glyphicon glyphicon-upload"></i>
											<span>Start upload</span>
									</button>
									<button type="reset" class="btn btn-warning cancel">
											<i class="glyphicon glyphicon-ban-circle"></i>
											<span>Cancel upload</span>
									</button>
<!-- 
									<button type="button" class="btn btn-danger delete">
											<i class="glyphicon glyphicon-trash"></i>
											<span>Delete</span>
									</button>
 -->
									<!-- <input type="checkbox" class="toggle"> -->
									<!-- The global file processing state -->
									<span class="fileupload-process"></span>
									<span class="message btn" id="pdfmessage"></span>
									<button type="button" class="btn btn-metadata">
											<i class="glyphicon glyphicon-book"></i>
											<span>Enter Page Metadata</span>
									</button>
									<button type="button" class="btn btn-missing">
											<i class="glyphicon glyphicon-sort-by-attributes"></i>
											<span>Insert Missing Pages</span>
									</button>
							</div>
							<!-- The global progress state -->
							<div class="col-lg-5 fileupload-progress fade">
									<!-- The global progress bar -->
									<div class="progress progress-striped active" role="progressbar" aria-valuemin="0" aria-valuemax="100">
											<div class="progress-bar progress-bar-success" style="width:0%;"></div>
									</div>
									<!-- The extended global progress state -->
									<div class="progress-extended">&nbsp;</div>
							</div>
					</div>
					<!-- The table listing the files available for upload/download -->
					<table role="presentation" class="table table-striped"><tbody class="files"></tbody></table>
			</form>
	</div>
	<!-- The blueimp Gallery widget -->
	<div id="blueimp-gallery" class="blueimp-gallery blueimp-gallery-controls" data-filter=":even">
			<div class="slides"></div>
			<h3 class="title"></h3>
			<a class="prev">‹</a>
			<a class="next">›</a>
			<a class="close">×</a>
			<a class="play-pause"></a>
			<ol class="indicator"></ol>
	</div>
	<!-- The template to display files available for upload -->
	<script id="template-upload" type="text/x-tmpl">
	{% for (var i=0, file; file=o.files[i]; i++) { %}
			<tr class="template-upload fade">
					<td>
							<span class="preview"></span>
					</td>
					<td>
							<p class="name">{%=file.name%}</p>
							<strong class="error text-danger"></strong>
							<input type="hidden" name="counter[]" value="{%=file.counter%}" required>
							<input type="hidden" name="sequence" value="<?php echo($max_sequence) ?>" required>
					</td>
					<td>
							<p class="size">Processing...</p>
							<div class="progress progress-striped active" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"><div class="progress-bar progress-bar-success" style="width:0%;"></div></div>
					</td>
					<td>
							{% if (!i && !o.options.autoUpload) { %}
									<button class="btn btn-primary start" disabled>
											<i class="glyphicon glyphicon-upload"></i>
											<span>Start</span>
									</button>
							{% } %}
							{% if (!i) { %}
									<button class="btn btn-warning cancel">
											<i class="glyphicon glyphicon-ban-circle"></i>
											<span>Cancel</span>
									</button>
							{% } %}
					</td>
			</tr>
	{% } %}
	</script>
	<!-- The template to display files available for download -->
	<script id="template-download" type="text/x-tmpl">
	{% for (var i=0, file; file=o.files[i]; i++) { %}
			<tr class="template-download fade">
					<td>
							<span class="preview">
									{% if (file.thumbnailUrl) { %}
											<a href="{%=file.url%}" title="{%=file.name%}" download="{%=file.name%}" data-gallery><img src="{%=file.thumbnailUrl%}"></a>
									{% } %}
							</span>
					</td>
					<td>
							<p class="name">
									{% if (file.url) { %}
											<a href="{%=file.url%}" title="{%=file.name%}" download="{%=file.name%}" {%=file.thumbnailUrl?'data-gallery':''%}>{%=file.name%}</a>
									{% } else { %}
											<span>{%=file.name%}</span>
									{% } %}
							</p>
							{% if (file.error) { %}
									<div><span class="label label-danger">Error</span> {%=file.error%}</div>
							{% } %}
					</td>
					<td>
							<span class="size">{%=o.formatFileSize(file.size)%}</span>
					</td>
					<td>
							{% if (file.deleteUrl) { %}
									<button class="btn btn-danger delete" data-type="{%=file.deleteType%}" data-url="{%=file.deleteUrl%}"{% if (file.deleteWithCredentials) { %} data-xhr-fields='{"withCredentials":true}'{% } %}>
											<i class="glyphicon glyphicon-trash"></i>
											<span>Delete</span>
									</button>
									<input type="checkbox" name="delete" value="1" class="toggle">
							{% } else { %}
									<button class="btn btn-warning cancel">
											<i class="glyphicon glyphicon-ban-circle"></i>
											<span>Cancel</span>
									</button>
							{% } %}
					</td>
			</tr>
	{% } %}
	</script>
	<script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
	<!-- The jQuery UI widget factory, can be omitted if jQuery UI is already included -->
	<script src="/js/vendor/jquery.ui.widget.js"></script>
	<!-- The Templates plugin is included to render the upload/download listings -->
	<script src="/js/blueimp/JavaScript-Templates/js/tmpl.min.js"></script>
	<!-- The Load Image plugin is included for the preview images and image resizing functionality -->
	<script src="/js/blueimp/JavaScript-Load-Image/js/load-image.all.min.js"></script>
	<!-- The Canvas to Blob plugin is included for image resizing functionality -->
	<script src="/js/blueimp/JavaScript-Canvas-to-Blob/js/canvas-to-blob.min.js"></script>
	<!-- blueimp Gallery script -->
	<script src="/js/blueimp/Gallery/js/jquery.blueimp-gallery.min.js"></script>
	<!-- The Iframe Transport is required for browsers without support for XHR file uploads -->
	<script src="/js/jquery.iframe-transport.js"></script>
	<!-- The basic File Upload plugin -->
	<script src="/js/jquery.fileupload.js"></script>
	<!-- The File Upload processing plugin -->
	<script src="/js/jquery.fileupload-process.js"></script>
	<!-- The File Upload image preview & resize plugin -->
	<script src="/js/jquery.fileupload-image.js"></script>
	<!-- The File Upload audio preview plugin -->
	<script src="/js/jquery.fileupload-audio.js"></script>
	<!-- The File Upload video preview plugin -->
	<script src="/js/jquery.fileupload-video.js"></script>
	<!-- The File Upload validation plugin -->
	<script src="/js/jquery.fileupload-validate.js"></script>
	<!-- The File Upload user interface plugin -->
	<script src="/js/jquery.fileupload-ui.js"></script>
	<!-- The main application script -->
	<script src="/js/main.js"></script>
	<script>
		 var hasMissingPages = <?php echo ($book_has_missing_pages ? 'true' : 'false'); ?>;
		 var loadingPDF = false;
	</script>
	<!-- The XDomainRequest Transport is included for cross-domain file deletion for IE 8 and IE 9 -->
	<!--[if (gte IE 8)&(lt IE 10)]>
	<script src="js/cors/jquery.xdr-transport.js"></script>
	<![endif]-->
 
<?php }?>

</body>
