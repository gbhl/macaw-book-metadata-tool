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
        <textarea rows="35" cols="80" id="log_listing"><?php echo($log) ?></textarea>
        <div id="history_status">
          <table>
            <tbody>
              <tr><td>Status</td><td><?php echo($item['status_code']) ?></td></tr>
              <tr><td>Created</td><td><?php echo($item['date_created']) ?></td></tr>
              <tr><td>Scan Start</td><td><?php echo($item['date_scanning_start']) ?></td></tr>
              <tr><td>Scan End</td><td><?php echo($item['date_scanning_end']) ?></td></tr>
              <tr><td>Review Start</td><td><?php echo($item['date_review_start']) ?></td></tr>
              <tr><td>Review End</td><td><?php echo($item['date_review_end']) ?></td></tr>
              <tr><td>Export Start</td><td><?php echo($item['date_export_start']) ?></td></tr>
              <tr><td>Completed</td><td><?php echo($item['date_completed']) ?></td></tr>
              <tr><td>Export Status</td><td></td></tr>
              <?php foreach ($item['export_status'] as $k => $v) { ?>
                <tr><td style="font-weight: normal"><?php echo $k; ?></td><td><?php echo $v['status_code'].'<br>'.$v['date']; ?></td></tr>
              <?php } ?>
            </tbody>
          </table>
        </div>
        <div class="clear"></div>
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
