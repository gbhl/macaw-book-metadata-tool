<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
        "http://www.w3.org/TR/html4/strict.dtd">
<html lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8">
	<title>Help | Macaw</title>
	<? $this->load->view('global/head_view') ?>
</head>
<body class="yui-skin-sam">
	<h2>Macaw Help</h2>

	<dl style="text-align: left" id="help">
	  <dt><a href="<? echo $this->config->item('base_url'); ?>help/overview/">Overview</a></dt>
	  <dd>General notes about using Macaw and it's capabilities.</dd>
	  <dt><a href="<? echo $this->config->item('base_url'); ?>help/quickstart/">Quick Start</a></dt>
	  <dd>The one-page fast track to getting started.</dd>
	  <dt><a href="<? echo $this->config->item('base_url'); ?>help/scanning/">Scanning</a></dt>
	  <dd>Instructions and tips for scanning pages onto your computer.</dd>
	  <dt><a href="<? echo $this->config->item('base_url'); ?>help/reviewing/">Reviewing</a></dt>
	  <dd>Instructions for reviewing and adding metadata for your scanned pages.</dd>
	  <dt><a href="<? echo $this->config->item('base_url'); ?>help/missing/">Missing Pages</a></dt>
	  <dd>All about inserting missing pages and what it means to submit them for export.</dd>
	  <dt><a href="<? echo $this->config->item('base_url'); ?>help/network/">Network Configuration</a></dt>
	  <dd>Macaw can copy files from your computer as you scan them. Here's how to set that up.</dd>
	  <dt><a href="<? echo $this->config->item('base_url'); ?>help/export/">Export</a></dt>
	  <dd>Want to share your scans with other systems? Look no further!</dd>
	  <dt><a href="<? echo $this->config->item('base_url'); ?>help/misc/">Miscellaneous</a></dt>
	  <dd>Everything else that's not mentioned above.</dd>
	  <dt><a href="<? echo $this->config->item('base_url'); ?>help/help_index/">Index</a></dt>
	  <dd>Everything, including the virutal kitchen sink.</dd>
	</dl>

</body>
</html>
