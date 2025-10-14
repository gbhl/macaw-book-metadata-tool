<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
        "http://www.w3.org/TR/html4/strict.dtd">
<html lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=9" />
	<title>Admin | Config | Macaw</title>
		<!-- 29/05/12 temp insert to retain old style -->
	<link rel="stylesheet" type="text/css" href="/css/yui-combo.css"> 
	<?php $this->load->view('global/head_view') ?>
  <?php if ($all) { ?>
    <style type="text/css">

      #phpinfo {}
      #phpinfo pre {}
      #phpinfo a:link {}
      #phpinfo a:hover {}
      #phpinfo table {}
      #phpinfo .center {}
      #phpinfo .center table {}
      #phpinfo .center th {}
      #phpinfo td, th {}
      #phpinfo h1 {}
      #phpinfo h2 {}
      #phpinfo .p {}
      #phpinfo .e {}
      #phpinfo .h {}
      #phpinfo .v {}
      #phpinfo .vr {}
      #phpinfo img {}
      #phpinfo hr {}      
    </style>

  <?php } ?>
</head>
<body class="yui-skin-sam" id="logsbody">
	<?php $this->load->view('global/header_view') ?>
	<h1>Macaw Configuration</h1>
  
  <div id="SimpleTable" class="fulltable">
    <table id="simpleTable">
      <thead>
        <tr><th>Paramter</th><th>Value</th></tr>
      </thead>
      <tbody>
        <?php 
        foreach ($config_params as $s) {
          echo "<tr>";
            echo "<td>".$s['paramater']."</td>";
            echo "<td>".$s['value']."</td>";
          echo "</tr>";
        }
        if ($all) {
          echo "<tr>";
          echo "<td><strong>PHP Configuration</strong></td>";
          echo "<td>##############################################################</td>";
        echo "</tr>";
      foreach (array_keys($phpinfo) as $section) {
            echo "<tr>";
              echo "<td><strong>".$section."</strong></td>";
              echo "<td>##############################################################</td>";
            echo "</tr>";
            foreach ($phpinfo[$section] as $k => $v) {
              echo "<tr>";
                echo "<td>&nbsp;&nbsp;&nbsp;&nbsp;".$k."</td>";
                echo "<td>".print_r($v,true)."</td>";
              echo "</tr>";
            }
          }

        }
        ?>
      </tbody>
    </table>
  </div>
	<script type="text/javascript">
		YAHOO.util.Event.onDOMReady(SimpleTable.init);
	</script>

<?php $this->load->view('global/footer_view') ?>
</body>
</html>
