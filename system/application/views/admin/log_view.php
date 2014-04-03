<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
        "http://www.w3.org/TR/html4/strict.dtd">
<html lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8">
	<title>Admin | Logs | Macaw</title>
		<!-- 29/05/12 temp insert to retain old style -->
	<link rel="stylesheet" type="text/css" href="http://yui.yahooapis.com/combo?2.9.0/build/reset-fonts-grids/reset-fonts-grids.css&2.9.0/build/base/base-min.css&2.9.0/build/assets/skins/sam/skin.css"> 
	<?php $this->load->view('global/head_view') ?>
	<script type="text/javascript">
	<?php if ($filename) { ?>
		Log.startingFile = '<?php echo $filename; ?>';
	<?php } ?>
		YAHOO.util.Event.onDOMReady(Log.initList);
	</script>
</head>
<body class="yui-skin-sam" id="logsbody">
	<?php $this->load->view('global/header_view') ?>
	<h1>View Logs</h1>
	<div id="log_view" class="yui-gf">
		<div class="yui-u first">
			<div id="logs"></div>
			<div id="logs-pages" style="text-align:center"></div>
		</div>
		<div class="yui-u" style="position:relative">
			<div id="details-note" style="position: absolute; top: -20px; display:none">Log automatically refreshes every 2 seconds.</div>
			<div id="details-pages" style="text-align:right; margin-top:-26px; height:20px;"></div>
			<div id="details"><div style="text-align:center;margin-top: 100px;">Select a log file on the left.</div></div>
		</div>
	</div>
	<?php $this->load->view('global/footer_view') ?>
</body>
</html>
