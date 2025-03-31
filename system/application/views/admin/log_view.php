<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
        "http://www.w3.org/TR/html4/strict.dtd">
<html lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=9" />
	<title>Admin | Logs | Macaw</title>
		<!-- 29/05/12 temp insert to retain old style -->
	<link rel="stylesheet" type="text/css" href="/css/yui-combo.css"> 
	<?php $this->load->view('global/head_view') ?>
	<script type="text/javascript">
		function init() {
      <?php if ($filename) { ?>
  	  	Log.startingFile = '<?php echo $filename; ?>';
    	<?php } ?>
      YAHOO.util.Event.onDOMReady(Log.initList);
      var obtnFilter = new YAHOO.widget.Button("btnFilter");
			obtnFilter.on('click', Log.initList, 'save');
		}
		YAHOO.util.Event.onDOMReady(init);
	</script>
</head>

<body class="yui-skin-sam" id="logsbody">
	<?php $this->load->view('global/header_view') ?>
	<h1>View Logs</h1>
	<div id="log_view" class="yui-gf">
		<div class="yui-u first">
      <div id="filter" style="padding:0 1em">Filter: <input type="text" id="txtLogFilter" onKeyUp="Log.initList();return false;"><button id="btnFilter" onclick="" value="Go">Update</div>
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
