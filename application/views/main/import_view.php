<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
        "http://www.w3.org/TR/html4/strict.dtd">
<html lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=9" />
	<title>Macaw</title>
	<?php $this->load->view('global/head_view') ?>
	<script type="text/javascript">YAHOO.util.Event.onDOMReady(Import.init);</script>
</head>
<body class="yui-skin-sam">
	<?php $this->load->view('global/header_view') ?>
	<?php $this->load->view('global/error_messages_view') ?>
	<div id="import">
		<h1>Import CSV File</h1>

    <div id="csv-documentation">
      <p>
        Documentation may be found at:
        <ul>
          <li><a href="https://docs.google.com/spreadsheets/d/1h6WeUILNBjiGHxrphDzUQKui2vVSXHwl6V-H1d0AIIY/edit?usp=sharing" _target="_blank">BHL Metadata Requirements | Schema Tables  v.2</a></li>
        </ul>
      </p>
      <p>
        CSV Templates may be found at: 
        <ul>
          <li><a href="https://drive.google.com/file/d/1t4evnIuz8zAvTT4Xrc_gZpOZPE-rNDp8/view?pli=1" _target="_blank">Macaw Item Level Template.xslx</a></li>
          <li><a href="https://drive.google.com/file/d/1CkFEzLCRl5aKErpD7Dp7Kx11i5TYoJEM/view?pli=1" _target="_blank">Macaw Page Level Template.xslx</a></li>
        </ul>
        
      </p>
    </div>
		<form id="upload_form" enctype="multipart/form-data" name="upload_form">
			<input type="hidden" name="li_token" value="<?php echo($token); ?>">
			<div id="fields">
				<label for="itemdata">Item Level CSV</label> 
				<input type="file" id="itemdata" name="itemdata"><br><br>

				<label for="pagedata">Page Level CSV</label> 
				<input type="file" id="pagedata" name="pagedata"> (optional) <br><br>
			</div>
			<button id="btnImport">Go</button>
		</form>
		<div id="progress" style="display:none">
			Please wait while the file(s) are imported...
			<div id="bar"></div>
			<div id="message" class="message-overlay" style="display:none"></div>
		</div>
	</div>
	<?php $this->load->view('global/footer_view') ?>
</body>
</html>
