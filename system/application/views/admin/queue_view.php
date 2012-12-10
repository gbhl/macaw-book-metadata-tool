<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
<html lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8">
	<title>Admin | Queues | Macaw</title>
	<link rel="stylesheet" type="text/css" href="http://yui.yahooapis.com/combo?2.9.0/build/reset-fonts-grids/reset-fonts-grids.css&2.9.0/build/base/base-min.css&2.9.0/build/assets/skins/sam/skin.css"> 
	<? $this->load->view('global/head_view') ?>
	<script type="text/javascript">YAHOO.util.Event.onDOMReady(Queues.init);</script>
</head>
<body class="yui-skin-sam">
	<? $this->load->view('global/header_view') ?>
	<div id="queuewrapper">
		<h1>Queues</h1>
	
		<div id="queues" class="yui-navset">
			<ul class="yui-nav">
				<li class="selected"><a href="#tab1">New</a></li>
				<li><a href="#tab2">In Progress</a></li>
				<li><a href="#tab3">Exporting</a></li>
				<li><a href="#tab4">Completed</a></li>
				<li><a href="#tab5">Errors</a></li>
				</ul>
			<div class="yui-content">
				<div><div id="divNew"></div></div>
				<div><div id="divInProgress"></div></div>
				<div><div id="divExporting"></div></div>
				<div><div id="divCompleted"></div></div>
				<div><div id="divErrors"></div></div>
			</div>
		</div>
	</div>
	<? $this->load->view('global/footer_view') ?>
</body>
</html>
