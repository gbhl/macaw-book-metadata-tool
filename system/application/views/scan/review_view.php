<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
        "http://www.w3.org/TR/html4/strict.dtd">
<html lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=9" />
	<title>Review | Macaw</title>
	<!-- 29/05/12 temp insert to retain old style -->
	<link rel="stylesheet" type="text/css" href="http://yui.yahooapis.com/combo?2.9.0/build/reset-fonts-grids/reset-fonts-grids.css&2.9.0/build/base/base-min.css&2.9.0/build/assets/skins/sam/skin.css"> 
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
			border: 2px solid #EEE;
		}
		.thumb .caption {
			text-align: center;
			font-size: 1.07em;
			color: #666;
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
<body id="bodyreview" class="yui-skin-sam">
	<?php $this->load->view('global/header_view') ?>

		<div class="yui-gc">
			<div class="yui-u first" id="metathumbsection">
				<div class="yui-gc">
					<div class="yui-u first" style="width: 100%">
						<ul id="thumbs"></ul>
						<div id="list"></div>
					</div>
				</div>
				<div class="yui-gc">
					<div id="thumb_controls" class="yui-u first" style="width: 100%">
						<div id="selects">
							<span id="title">Select:</span>
							<button id="btnSelectAll" title="Selects all pages">All</Button>
							<button id="btnSelectNone" title="Unselects all pages">None</Button>
							<button id="btnSelectAlternate" title="Selects all even-numbered pages">Alternate</Button>
							<button id="btnSelectInverse" title="Selects pages that aren't selected and unselects those that are">Inverse</Button>
						</div>
						<div id="btnToggleListThumbs" class="yui-buttongroup">
							<input id="btnToggleThumbs" type="radio" name="list_or_thumbs" value="Th" checked title="Thumbnail View"><input id="btnToggleList" type="radio" name="list_or_thumbs" value="Ls" title="List View">
						</div>
						<div id="slider">
							<div class="icon"><img src="<?php echo $this->config->item('base_url'); ?>images/icons/picture.png" border="0" height="16" width="16" title="Zoom Out"></div>
							<div id="sliderbg"><div id="sliderthumb"><img src="<?php echo $this->config->item('base_url'); ?>images/icons/slider-thumb-n.gif"></div></div>
							<div class="icon"><img src="<?php echo $this->config->item('base_url'); ?>images/icons/bullet_picture.png" border="0" height="16" width="16" title="Zoom In"></div>
						</div>
					</div>
				</div>
				<div id="metadata" class="yui-gc">
					<div class="yui-u first" style="width: 100%">
						<?php $this->load->view('scan/metadata_form_view') ?>
					</div>
				</div>
			</div>
			<div id="preview" class="yui-u" style="width: 31.5%; margin-left: 0; margin-right:.5em;">
				
				<a href="#" onClick="return General.showMagnifier();"><img src="<?php echo $this->config->item('base_url'); ?>images/spacer.gif" border="0" id="preview_img" style="height: 100%; width: 100%;"></a>
				
			</div>
			<div id="save_buttons"> 
				<button id="btnSave">Save</button>
				<button id="btnFinished">Review Complete</button>
			</div>
			<div id="splitter" class="yui-u">
				<img src="<?php echo $this->config->item('base_url'); ?>images/icons/resultset_next.png" border="0" height="16" width="16" id="btnToggle" alt="Hide preview and metadata">
			</div>
		</div>
	<?php $this->load->view('global/footer_view') ?>
	<?php foreach ($metadata_modules as $m) {
		echo '<script type="text/javascript" src="'.$this->config->item('base_url').'plugins/metadata/'.$m.'.js"></script>'."\n";
	} ?>
	<script type="text/javascript">
		<?php foreach ($metadata_modules as $m) {echo 'Scanning.metadataModules.push(\''.$m.'\');'."\n";} ?>
		YAHOO.util.Event.onContentReady("preview_img", Scanning.initReview);
		var styleSheet = YAHOO.util.StyleSheet('macaw_thumbs_css');
	</script>
</body>
</html>


