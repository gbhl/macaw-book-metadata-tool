<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
        "http://www.w3.org/TR/html4/strict.dtd">
<html lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8">
	<title>Insert Missing | Macaw</title>

	<?php $this->load->view('global/head_view') ?>
	<style type="text/css" id="macaw_thumbs_css">
		.thumb {
			width: 75.5px;
			height: 132px;
			overflow: hidden;
			float: left;
			margin: .2em;
			position: relative;
		}
		.thumb .info {
			width: 65.5px;
			height: 122px;
			padding: 5px;
			text-align: center;
			font-size: 1.07em;
			font-weight:bold;
			position: absolute;
		}
		.thumb img {
			width: 100%;
			border: 2px solid #FFFFFF;
		}
		.thumb .caption {
			text-align: center;
			font-size: 1.07em;
			color: grey;
			position: absolute;
			left: 0px;
			top: 115px;
		}
		.missing {
			background-color: #CEE4FF;
		}
		.missing img {
			border: 2px solid #CEE4FF;
		}
	</style>
</head>
<body class="yui-skin-sam">
	<?php $this->load->view('global/header_view') ?>
	<div class="yui-g" id="new_thumbs_header">
		<h2>Missing Pages</h2>
		<h3>Drag-drop the missing pages to their correct location below.</h3>
	</div>
	<div class="yui-g" id="new_thumbs" style="overflow:hidden;">
		<div class="yui-u first" style="width:100%;overflow:auto;">
			<div id="thumb_scroller">
				<ul id="thumbs_missing"></ul>
			</div>
		</div>
	</div>
	<h2>Existing Pages</h2>
	<div class="yui-g" id="existing_thumbs" style="overflow:hidden;">
		<div class="yui-u first" style="width:100%;overflow:hidden;">
			<ul id="thumbs"></ul>
		</div>
	</div>
	<div class="yui-g" id="controls">
		<div id="thumb_controls" class="yui-u first" style="width: 100%">
			<div id="missing_save_buttons">
				<button id="btnCancel">Cancel</button>
				<button id="btnFinished">Finished!</button>
			</div>
			<div id="slider">
				<div class="icon"><img src="<?php echo $this->config->item('base_url'); ?>images/icons/picture.png" border="0" height="16" width="16" title="Zoom Out"></div>
				<div id="sliderbg"><div id="sliderthumb"><img src="<?php echo $this->config->item('base_url'); ?>images/icons/slider-thumb-n.gif"></div></div>
				<div class="icon"><img src="<?php echo $this->config->item('base_url'); ?>images/icons/bullet_picture.png" border="0" height="16" width="16" title="Zoom In"></div>
			</div>
		</div>
	</div>

	<?php $this->load->view('global/footer_view') ?>
	<?php foreach ($metadata_modules as $m) {
		echo '<script type="text/javascript" src="'.$this->config->item('base_url').'plugins/metadata/'.$m.'.js"></script>'."\n";
	} ?>
	<script type="text/javascript">
		<?php foreach ($metadata_modules as $m) {echo 'Scanning.metadataModules.push(\''.$m.'\');'."\n";} ?>
		YAHOO.util.Event.onContentReady("btnFinished", Scanning.initInsertMissing);
		var styleSheet = YAHOO.util.StyleSheet('macaw_thumbs_css');
		MessageBox.init();
	</script>
</body>
</html>


