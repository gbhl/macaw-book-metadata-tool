<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
        "http://www.w3.org/TR/html4/strict.dtd">
<html lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=9" />
	<title>Dashboard | Macaw</title>
	<?php $this->load->view('global/head_view') ?>
	<script type="text/javascript" src="https://www.google.com/jsapi"></script>
	<script type="text/javascript">
		google.load("visualization", "1", {packages:["corechart"]});
		google.setOnLoadCallback(YAHOO.macaw.Dashboard.init);
	</script>
</head>
<body class="yui-skin-sam">
	<?php $this->load->view('global/header_view') ?>
	<h1>Statistics</h1>
	<table id="dashboard" style="margin: 0 auto;">
		<tr>
			<td width="50%" class="widget"><h3>Summary</h3><div id="summary"></div></td>
			<td width="50%" class="widget"><h3>Disk Usage (%)</h3><div id="disk"></div></td>
		</tr>
		<tr>
			<td class="widget"><h3>Pages Scanned Per Day</h3><div id="perday"></div></td>
			<td class="widget"><h3>Total Pages Scanned</h3><div id="pages"></div></td>
		</tr>	
	</table>

	<?php $this->load->view('global/footer_view') ?>
	<script type="text/javascript">
		YAHOO.util.Dom.get("txtBarcode").focus();
	</script>

</body>
</html>



