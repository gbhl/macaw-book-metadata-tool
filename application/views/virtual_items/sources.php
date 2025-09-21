<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
        "http://www.w3.org/TR/html4/strict.dtd">
<html lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=9" />
	<title>Virtual Items | Macaw</title>
	<?php $this->load->view('global/head_view') ?>
</head>
<body id="manualbody" class="yui-skin-sam">
	<?php $this->load->view('global/header_view') ?>

  <h1>Virtual Item Sources</h1>
  <p style="padding: .5rem;">
    <strong>Total Items:</strong> <?php echo $total_item_count; ?> 
    <strong>Total Pages:</strong> <?php echo $total_page_count; ?>
  </p>
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
            echo "<td>".$s['page_count']."</td>";
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

	<?php $this->load->view('global/footer_view') ?>
</body>
</html>