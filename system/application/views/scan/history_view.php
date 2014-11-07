<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
        "http://www.w3.org/TR/html4/strict.dtd">
<html lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=9" />
	<title>History | Macaw</title>
	<?php $this->load->view('global/head_view') ?>
	<!--removed init script -->

</head>
<body class="yui-skin-sam">
	<?php $this->load->view('global/header_view') ?>

		<div id="historyholder">
				<h1>History</h1>
			<div id="history">
				<textarea rows="35" cols="120" id="log_listing"><?php echo($log) ?></textarea>
				</div>
		</div>
	<?php $this->load->view('global/footer_view') ?>
	<script type="text/javascript">
		YAHOO.util.Event.onDOMReady(function () { 
			MessageBox.init();
		});
	</script>
</body>
</html>
