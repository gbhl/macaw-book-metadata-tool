<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
        "http://www.w3.org/TR/html4/strict.dtd">
<html lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=9" />
	<title>Virtual Items | Macaw</title>
	<?php $this->load->view('global/head_view') ?>
  <!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/prism/9000.0.1/themes/prism.min.css"/> -->
</head>
<body id="manualbody" class="yui-skin-sam">
	<?php $this->load->view('global/header_view') ?>
	
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
	<script type="text/javascript">
		VirtualListItems.displayMode = 'config';
		YAHOO.util.Event.onDOMReady(VirtualListItems.init);
	</script>

	<?php $this->load->view('global/footer_view') ?>
  <!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/9000.0.1/prism.min.js"></script> -->
</body>
</html>