<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
        "http://www.w3.org/TR/html4/strict.dtd">
<html lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=9" />
	<title>Virtual Items | Macaw</title>
	<?php $this->load->view('global/head_view') ?>
	<script type="text/javascript">
		/* Initialization script, called when the page is ready */
    <?php if ($sources) { ?>
  		function init() {
        var myDataSource = new YAHOO.util.DataSource(YAHOO.util.Dom.get("viAllSources"));
        myDataSource.responseType = YAHOO.util.DataSource.TYPE_HTMLTABLE;
        myDataSource.responseSchema = {fields: [{ key: "Name" },{ key: "Path" },{ key: "Count", parser:"number" },{ key: "Valid" }]};
        var myColumnDefs = [{ key: "Name" },{ key: "Path" },{ key: "Count" },{ key: "Valid" }];         
        var myDataTable = new YAHOO.widget.DataTable("VirtualItemSources", myColumnDefs, myDataSource);
      }
  		YAHOO.util.Event.onDOMReady(init);
    <?php } ?>
    <?php if ($config) { ?>
  		function init() {
        var myDataSource = new YAHOO.util.DataSource(YAHOO.util.Dom.get("viConfig"));
        myDataSource.responseType = YAHOO.util.DataSource.TYPE_HTMLTABLE;
        myDataSource.responseSchema = {fields: [{ key: "Name" },{ key: "Value" }]};
        var myColumnDefs = [{ key: "Name" },{ key: "Value" }];
        var myDataTable = new YAHOO.widget.DataTable("VirtualItemConfig", myColumnDefs, myDataSource);
      }
  		YAHOO.util.Event.onDOMReady(init);
    <?php } ?>
    <?php if ($items) { ?>
  		function init() {
        var myDataSource = new YAHOO.util.DataSource(YAHOO.util.Dom.get("viSummary"));
        myDataSource.responseType = YAHOO.util.DataSource.TYPE_HTMLTABLE;
        myDataSource.responseSchema = {fields: [{ key: "Status" },{ key: "Count", parser:"number" }]};
        var myColumnDefs = [{ key: "Status", formatter: formatStatus },{ key: "Count" }];
        var myDataTable = new YAHOO.widget.DataTable("VirtualItemSummary", myColumnDefs, myDataSource);

        var myDataSource = new YAHOO.util.DataSource(YAHOO.util.Dom.get("viLastTen"));
        myDataSource.responseType = YAHOO.util.DataSource.TYPE_HTMLTABLE;
        myDataSource.responseSchema = {fields: [{ key: "Identifier" },{ key: "Title" },{ key: "Created" },{ key: "Status" }]};
        var myColumnDefs = [{ key: "Identifier" },{ key: "Title" },{ key: "Created", formatter: formatDateAge },{ key: "Status", formatter: formatStatus }];
        var myDataTable = new YAHOO.widget.DataTable("VirtualItemLastTen", myColumnDefs, myDataSource);
      }
  		YAHOO.util.Event.onDOMReady(init);
    <?php } ?>

  </script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/prism/9000.0.1/themes/prism.min.css"/>

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
  <?php } ?>
  
  <?php  if ($config) { ?>
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

  <?php } ?>
  
  <?php if ($items) { ?>
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

    <h2>Last Ten Items</h2>
    <div id="VirtualItemLastTen" class="fulltable">
      <table id="viLastTen">
        <thead>
          <tr><th>Identifier</th><th>Title</th><th>Created</th><th>Status</th></tr>
        </thead>
        <tbody>
          <?php foreach ($items as $i) {
            echo "<tr>";
              echo "<td>".$i['barcode']."</td>";
              echo "<td>".$i['title']."</td>";
              echo "<td style=\"white-space: nowrap;\">".$i['created']."</td>";
              echo "<td style=\"white-space: nowrap;\">".$i['status_code']."</td>";
            echo "</tr>";
          }
          ?>
        </tbody>
      </table>
    </div>   

  <?php } ?>

	<?php $this->load->view('global/footer_view') ?>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/9000.0.1/prism.min.js"></script>
</body>
</html>