<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
        "http://www.w3.org/TR/html4/strict.dtd">
<html lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8">
	<title>Scan | Monitor | Macaw</title>
	<? $this->load->view('global/head_view') ?>
</head>
<body class="yui-skin-sam">
	<? $this->load->view('global/header_view') ?>


	<div id="scan_progress">
		<div id="instructions">
			<h2>Instructions</h2>

			<div class="numberedinstructions">
				<div class="instructionrow">
					<div class="number">1</div>
				<? if ($book_has_missing_pages) { ?>
					<div class="text"><p>Scan all of your missing pages first.</p></div>
				<? } else { ?>
					<div class="text"><p>Scan all of your pages.</p></div>
				<? } ?>
				</div>
				<div class="instructionrow">
					<div class="number">2</div>
					<div class="text">
						<p>Copy the scans to this network location:</p>
						<strong><?=$remote_path?></strong>
					</div>
				</div>
				<div class="instructionrow">
					<div class="number">3</div>
					<div class="text">
						<p>Start the import:</p>
					
						<button id="btnStartImport">Start Import</button> <? if ($book_has_missing_pages) { ?><button id="btnSkipImport">Skip Import</button> <? } else { ?><button id="btnSkipImport">Skip Import</button> <? } ?>
						<br><br>
						While the scans will be copied to the server, you can monitor the progress on the right.
					</div>
				</div>
			</div>

			<div id="page_count" style="display:none;"></div>

			<div id="finished" style="display:none;">
				<h3>The import is finished!</h3>
				<? if ($book_has_missing_pages) { ?>
					<script type="text/javascript">Scanning.missingPages = true;</script>
					<button id="btnInsertMissingPages">Insert Missing Pages</button>
				<? } else {?>
					<button id="btnReviewNow">Review Now!</button>
				<? } ?>
			</div>

			<script type="text/javascript">YAHOO.util.Event.onDOMReady(Scanning.initMonitor);</script>
		</div>
		<div id="progresswrapper">
			<? if($book_has_missing_pages) { ?>
		<h1>Missing Page Import</h1>
	<? } else {?>
		<h1>Scanned Page Import</h1>
	<? }?>	</div>
		<div id="progress">	
		</div>
		
		<div class="clear"><!-- --></div>
	</div>

	<? $this->load->view('global/footer_view') ?>
	<script type="text/javascript">
		YAHOO.util.Event.onDOMReady(function () { 
			MessageBox.init();
		});
	</script>
</body>
</html>
