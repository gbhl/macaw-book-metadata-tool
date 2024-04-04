<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
        "http://www.w3.org/TR/html4/strict.dtd">
<html lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=9" />
	<title>Virtual Items | Macaw</title>
	<?php $this->load->view('global/head_view') ?>
  <!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/prism/9000.0.1/themes/prism.min.css"/> -->
</head>
<body id="manualbody" class="yui-skin-sam">
	<?php $this->load->view('global/header_view') ?>
	
	<h1>Virtual Item Spreadsheet: <?php echo $name; ?></h1>
	<h2>Uploaded by <?php echo $uploaded_by; ?> on <?php echo $created; ?></h2>
	<div id="userqueues" class="fulltable">
		<ul class="queueheading">
			<li class="selected"><a href="#tab2">Virtual Item Articles</a></li>
			<select id="queue-filter">
				<?php foreach ($filter as $f) { ?>
					<option value="<?php echo strtolower($f) ?>"><?php echo $f ?></option>
				<?php } ?>
			</select>
		</ul>
		<div class="yui-content"></div>
		<div id="divInProgress"></div>
	</div>

	<script type="text/javascript">
		VirtualListItems.displayMode = 'spreadsheetitems';
		VirtualListItems.SpreadsheetID = <?php echo $id; ?>;
		YAHOO.util.Event.onDOMReady(VirtualListItems.init);
	</script>

	<?php $this->load->view('global/footer_view') ?>
  <!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/9000.0.1/prism.min.js"></script> -->
</body>
</html>