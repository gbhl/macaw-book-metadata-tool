<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
        "http://www.w3.org/TR/html4/strict.dtd">
<html lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=9" />
	<title>Macaw</title>
	<?php $this->load->view('global/head_view') ?>
	<script type="text/javascript">
		/* Initialization script, called when the page is ready */
		function init() {
			var obtnScan = new YAHOO.widget.Button("btnScan");
			var obtnReview = new YAHOO.widget.Button("btnReview");
			var obtnMissing = new YAHOO.widget.Button("btnMissing");
			var obtnHistory = new YAHOO.widget.Button("btnHistory");
			var obtnEditItem = new YAHOO.widget.Button("btnEditItem");
			var obtnBack = new YAHOO.widget.Button("btnBack");
			
			obtnScan.on("click", function(o) {window.location = sBaseUrl+'/scan/monitor/';} );
			obtnReview.on("click", function(o) {window.location = sBaseUrl+'/scan/review';} );
			obtnMissing.on("click", function(o) {window.location = sBaseUrl+'/scan/missing/insert';} );
			obtnHistory.on("click", function(o) {window.location = sBaseUrl+'/scan/history';} );
			obtnEditItem.on("click", function(o) {window.location = sBaseUrl+'/main/edit';} );
			obtnBack.on("click", function(o) {window.location = sBaseUrl+'/main ';} );
		}
		YAHOO.util.Event.onDOMReady(init);
	</script>
</head>
<body class="yui-skin-sam">
	<?php $this->load->view('global/header_view') ?>
	
	<!-- removed messaging and put it in the headerview -->

	<div id="main">
		<?php 		if ($this->session->userdata('barcode')) {
			$this->book->load($this->session->userdata('barcode'));
			$status = $this->book->status;
			if ($status == "reviewed"){ ?>
				<h2> Review Complete</h2>
				<?php if ($this->book->needs_qa){ ?>
					<p class="note">This item is awaiting QA </p>
				<?php } else { ?>
					<p class="note">This item is completed and will be upload or exported at the next scheduled time.</p>
				<?php } ?>
			<?php } } ?>
	</div>
	<?php $this->load->view('global/footer_view') ?>
</body>
</html>
