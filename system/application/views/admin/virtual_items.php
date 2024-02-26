<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
        "http://www.w3.org/TR/html4/strict.dtd">
<html lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=9" />
	<title>Virtual Items | Macaw</title>
	<?php $this->load->view('global/head_view') ?>
  <!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/prism/9000.0.1/themes/prism.min.css"/> -->
	<?php if ($view_items) { ?>	
		<script type="text/javascript">
			VirtualListItems.displayMode = 'items';
			VirtualListItems.sourceName = '<?php echo $name; ?>';
			YAHOO.util.Event.onDOMReady(VirtualListItems.init);
		</script>
	<?php } ?>
</head>
<body id="manualbody" class="yui-skin-sam">
	<?php $this->load->view('global/header_view') ?>
  <?php if ($sources) { ?>
    <h1>Virtual Item Sources</h1>
    <div id="VirtualItemSources" class="fulltable">
      <table id="viAllSources">
        <thead>
          <tr><th>Name</th><th>Configuration Path</th><th>Number of Items</th><th>Valid</th></tr>
        </thead>
        <tbody>
          <?php foreach ($sources as $s) {
            echo "<tr>";
              echo "<td><a href=\"".$s['url']."\">".$s['name']."</a></td>";
              echo "<td><a href=\"".$s['config_url']."\">".$s['path']."</a></td>";
              echo "<td>".$s['item_count']."</td>";
              echo "<td>".($s['valid'] ? '<img src="../../images/icons/accept.png">' : '<img src="../../images/icons/exclamation.png">')."</td>";
            echo "</tr>";
          }
          ?>
        </tbody>
      </table>
    </div>
		<script type="text/javascript">
			VirtualListItems.displayMode = 'sources';
			YAHOO.util.Event.onDOMReady(VirtualListItems.init);
		</script>
  <?php } ?>
  
  <?php if ($config) { ?>
    <h1>Virtual Item Configuration: <?php echo $name ?></h1>
    <div id="VirtualItemConfig" class="fulltable">
      <table id="viConfig">
        <thead>
          <tr><th>Paramter</th><th>Value</th></tr>
        </thead>
        <tbody>
          <?php foreach ($config_params as $s) {
            echo "<tr>";
              echo "<td>".$s['paramater']."</td>";
              echo "<td>".$s['value']."</td>";
            echo "</tr>";
          }
          ?>
        </tbody>
      </table>
    </div>   
    <br>
		<div>
			<h2>Raw Configuration file</h2>
      <div style="width:908px; margin:0 auto;">
        <pre class="language-php"><code class="language-php"><?php echo($raw_config); ?></code></pre>
      </div>
		</div>
		<script type="text/javascript">
			VirtualListItems.displayMode = 'config';
			YAHOO.util.Event.onDOMReady(VirtualListItems.init);
		</script>
  <?php } ?>
  
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
  <!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/9000.0.1/prism.min.js"></script> -->
</body>
</html>