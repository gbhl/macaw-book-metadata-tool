<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
        "http://www.w3.org/TR/html4/strict.dtd">
<?php	include_once('system/application/config/version.php');
	$cfg = $this->config->item('macaw');
?>
<html lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=9" />
	<title>Reset Password | Macaw</title>
	<?php $this->load->view('global/head_view') ?>
	<script type="text/javascript">
		LostPwd.title = "Reset Password";
		LostPwd.button = "Reset Password";
	    YAHOO.util.Event.onDOMReady(LostPwd.init);
	</script>
</head>
<body class="yui-skin-sam">

	<div id="logincontainerborder">
	<div id="logincontainer">
		<?php $this->load->view('global/error_messages_view') ?>
		<div id="loginheader">
			<img id="hero" width="318" height="483" alt="Rosellas" src="<?php echo $this->config->item('base_url'); ?>images/rosellas_macaw_login.png">
			<h1>Macaw</h1>
			<h2>Metadata Collection and Workflow System</h2>
			<hr>
			<?php if ($version_rev == 'VERSION_GOES_HERE') { ?>
				<h3>Demo / Development Version</h3>
			<?php } else { ?>
				<h3>Version <?php echo($version_rev); ?> / <?php echo($version_date); ?></h3>
			<?php } ?>
		</div>

		<div id="logincontent">
			<div id="logincontenttemplate" style="width:30px; display:none;visibility:hidden">
				<p>Enter your new password below.</p>
				<?php echo form_open($this->config->item('base_url').'login/process_reset_password', array('id' => 'lostpwdform')) ?>

				<input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

				<div style="margin-bottom: 15px;">
					<span class="loginlabel"><label for="password">New Password:</label></span>
					<span class="loginfield"><input type="password" name="password" id="password" size="15" maxlength="64" tabindex="1"></span>
				</div>

				<div style="margin-bottom: 15px;">
					<span class="loginlabel"><label for="password_confirm">Confirm Password:</label></span>
					<span class="loginfield"><input type="password" name="password_confirm" id="password_confirm" size="15" maxlength="64" tabindex="2"></span>
				</div>
				<p style="color: #666;">
					<strong>Password Requirements:</strong> Minimum 12 characters, must include
					uppercase, lowercase, digits, and special characters.
				</p>
				<?php echo form_close() ?>
			</div>
		</div>

	</div>
	</div>
	<div id="credit">
		 Based on the Paginator originally created<br>
		 at the Missouri Botanical Garden.
	</div>
	<?php $this->load->view('global/footer_view') ?>
</body>
</html>
