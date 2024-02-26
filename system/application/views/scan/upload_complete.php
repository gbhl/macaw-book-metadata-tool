<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
        "http://www.w3.org/TR/html4/strict.dtd">
<html lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=9" />
	<title>Review | Macaw</title>
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
<body class="yui-skin-sam">
	<hidden name="bookid" value="<?php echo $id ?>"/>
	<?php $this->load->view('global/header_view') ?>
	<h1><?php echo $incomingpath?></h1>
</body>
</html>