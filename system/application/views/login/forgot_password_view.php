<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
        "http://www.w3.org/TR/html4/strict.dtd">
<?php	include_once('system/application/config/version.php');
	$cfg = $this->config->item('macaw');
?>
<html lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=9" />
	<title>Forgot Password | Macaw</title>
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
				<h2>Forgot Password</h2>
				<p>Enter your username to receive a password reset link via email.</p>

				<?php echo form_open($this->config->item('base_url').'login/request_password_reset') ?>
					<div style="margin-bottom: 15px;">
						<label for="username">Username:</label><br>
						<input type="text" name="username" id="username" size="30" maxlength="32" tabindex="1" style="padding: 5px; font-size: 14px;">
					</div>

					<div style="margin-bottom: 15px;">
						<input type="submit" value="Send Reset Email" style="padding: 8px 20px; font-size: 14px;">
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
