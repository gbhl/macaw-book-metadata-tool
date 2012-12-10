<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
<html lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8">
	<title>Main | Items | Macaw</title>
	<? $this->load->view('global/head_view') ?>
	<script type="text/javascript">YAHOO.util.Event.onDOMReady(ListItems.init);</script>
</head>
<body class="yui-skin-sam">
	<? $this->load->view('global/header_view') ?>
	
	<div id="userqueues" class="fulltable">
		<ul class="queueheading">
			<li class="selected"><a href="#tab2">In Progress</a></li>
			<select id="queue-filter">
				<? foreach ($filter as $f) { ?>
					<option value="<? echo $f ?>"><? echo $f ?></option>
				<? } ?>
			</select>
		</ul>
		<div class="yui-content"><div>
		<div id="divInProgress"></div>
	</div>

	</div>
	</div>
	<? $this->load->view('global/footer_view') ?>
</body>
</html>
