<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
        "http://www.w3.org/TR/html4/strict.dtd">
<html lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8">
	<title>Dashboard | Macaw</title>
	<? $this->load->view('global/head_view') ?>
	<script type="text/javascript">
		eval('var user_widgets = <? echo($user_widgets) ?>;');

		YAHOO.util.Event.onDOMReady(YAHOO.macaw.Dashboard.init);
	</script>
</head>
<body class="yui-skin-sam">
	<? $this->load->view('global/header_view') ?>
	<h1>Statistics</h1>
	<div id="dashboard">

		<div id="Column1">
		</div>
		<div id="Column2">
		</div>
		<div class="clear"><!-- --></div>
	</div>

	<? $this->load->view('global/footer_view') ?>
	<script type="text/javascript">
		YAHOO.util.Dom.get("txtBarcode").focus();
	</script>

</body>
</html>



