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
</head>
<body class="yui-skin-sam">

	<div id="logincontainerborder">
	<div id="logincontainer">
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
		<?php $this->load->view('global/error_messages_view') ?>

		<div id="logincontent">
			<div style="padding: 20px;">
				<h2>Reset Password</h2>
				<p>Enter your new password below.</p>

				<?php echo form_open($this->config->item('base_url').'login/process_reset_password') ?>
					<input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

					<div style="margin-bottom: 15px;">
						<label for="password">New Password:</label><br>
						<input type="password" name="password" id="password" size="30" maxlength="64" tabindex="1" style="padding: 5px; font-size: 14px;">
						<p style="font-size: 12px; color: #666;">Minimum 6 characters</p>
					</div>

					<div style="margin-bottom: 15px;">
						<label for="password_confirm">Confirm Password:</label><br>
						<input type="password" name="password_confirm" id="password_confirm" size="30" maxlength="64" tabindex="2" style="padding: 5px; font-size: 14px;">
					</div>

					<div style="margin-bottom: 15px;">
						<input type="submit" value="Reset Password" style="padding: 8px 20px; font-size: 14px;">
						<a href="<?php echo $this->config->item('base_url').'login'; ?>" style="margin-left: 10px;">Back to Login</a>
					</div>
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
