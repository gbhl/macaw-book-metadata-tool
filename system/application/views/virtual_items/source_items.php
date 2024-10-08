<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
        "http://www.w3.org/TR/html4/strict.dtd">
<html lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=9" />
	<title>Virtual Items | Macaw</title>
	<?php $this->load->view('global/head_view') ?>
	<script type="text/javascript">
		VirtualListItems.displayMode = 'items';
		VirtualListItems.sourceName = '<?php echo $name; ?>';
		YAHOO.util.Event.onDOMReady(VirtualListItems.init);
	</script>
</head>
<body id="manualbody" class="yui-skin-sam">
	<?php $this->load->view('global/header_view') ?>
	
  <?php if ($view_items) { ?>
    <h1>Virtual Item for <?php echo $name; ?></h1>
    <h2>Summary</h2>
    <div id="VirtualItemSummary" class="halftable">
      <table id="viSummary">
        <thead>
          <tr><th>Status</th><th>Count</th></tr>
        </thead>
        <tbody>
          <?php foreach ($item_summary as $s) {
            echo "<tr>";
              echo "<td>".$s['status_code']."</td>";
              echo "<td>".$s['thecount']."</td>";
            echo "</tr>";
          }
          ?>
        </tbody>
      </table>
    </div>   

    <h2>Items</h2>
	
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

  <?php } ?>

	<?php $this->load->view('global/footer_view') ?>
</body>
</html>