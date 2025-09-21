<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
        "http://www.w3.org/TR/html4/strict.dtd">
<html lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=9" />
	<title>Macaw</title>
	<?php $this->load->view('global/head_view') ?>
</head>
<body class="yui-skin-sam">
<?php
	$cfg = $this->config->item('macaw');
?>

<?php if ($this->uri->total_segments() == 0 || $this->uri->segment(1) == 'login') { ?>
	<div id="doc3">
		<div id="bd">
<?php } else { ?>
	<div id="doc3">
		<div id="banner">
			<div id="hd">
				<div id="title" style="float: left">
					<img src="<?php echo $this->config->item('base_url'); ?>images/logo.png" alt="logo.png" width="22" height="22" border="0" align="left" id="logo">
					<div style="float:left">
					<h4 style="padding-right:10px;">Macaw Metadata Collection and Workflow System <?php if ($cfg['testing']) { echo(' <span style="color:#F90;">&nbsp;&nbsp;&nbsp;&nbsp;DEVELOPMENT VERSION</span>'); }?></h4>
					</div>
					<div class="clear"><!-- --></div>
				</div>
			</div>
		</div>
		<div class="messagediv">
			<?php $this->load->view('global/error_messages_view') ?>
		</div>	
		<div id="bd">
<?php } ?>
	
	<!-- removed messaging and put it in the headerview -->

	<div id="main" class="terms">
		<h1>Terms and Conditions</h1>
		Please review and agree to the terms of using Macaw before continuing.<br><br>
		<form id="terms" action="terms_save" method="post">
			<textarea cols="100" rows="15" disabled="disabled" style="color:black"><?php echo $terms ?></textarea><br><br>
			<input type="checkbox" name="agree" value="1" id="terms_agree"> <label for="terms_agree">I understand and agree to these terms and conditions</label><br><br>
			<input type="submit" value="Continue &gt;&gt;">
		</form>
	</div>
	<?php $this->load->view('global/footer_view') ?>
</body>
</html>
