<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
	"http://www.w3.org/TR/html4/strict.dtd">
<html lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=9" />
	<title>Enable Two-Factor Authentication | Macaw</title>
	<?php $this->load->view('global/head_view') ?>

	<script type="text/javascript">
		function init() {
			MessageBox.init();
			var obtnVerify = new YAHOO.widget.Button("btnVerify");
			obtnVerify.on('click', function() { Dom.get('totp_setup_form').submit(); });
		}
		YAHOO.util.Event.onDOMReady(init);
	</script>

</head>
<body class="yui-skin-sam">
	<?php $this->load->view('global/header_view') ?>
	<div id="edit">
		<h1>Enable Two-Factor Authentication</h1>
		<form action="<?php echo $this->config->item('base_url'); ?>account/totp_enable/" method="post" id="totp_setup_form">
		<input type="hidden" name="li_token" value="<?php echo htmlspecialchars($token); ?>">

		<table border="0" cellspacing="5" cellpadding="5">
			<tr class="row">
				<td colspan="2" style="font-size: 125%;">
					<strong><a style="color: #3588A8;" href="<?php echo $this->config->item('base_url'); ?>account/settings">&laquo; Cancel and return to Account Settings</a></strong>
				</td>
			</tr>
			<tr class="row">
				<td class="fieldname" valign="top">Instructions</td>
				<td>
					<p>
					Scan the QR code below with an authenticator app such as<br>
					<strong>Google Authenticator</strong>, <strong>Authy</strong>, or any other
					app that supports TOTP (RFC 6238).
					</p>
					<p>
					After scanning, enter the 6-digit code shown in the app to confirm the<br>
					setup and enable two-factor authentication on your account.
					</p>
				</td>
			</tr>
			<tr class="row">
				<td class="fieldname" valign="top">QR Code:</td>
				<td>
					<?php echo $qr_svg; ?>
				</td>
			</tr>
			<tr class="row">
				<td class="fieldname" valign="top">Manual Entry Key:</td>
				<td>
					<code style="font-family: monospace; font-size: 1.5em; letter-spacing: 0.1em;"><?php echo htmlspecialchars($secret); ?></code>
					<br>
					<small>Enter this key manually if you cannot scan the QR code.</small>
				</td>
			</tr>
			<tr class="row">
				<td class="fieldname">
					<label for="totp_code">Verification Code:</label>
				</td>
				<td>
					<input type="text" name="totp_code" id="totp_code"
							size="10" maxlength="6" placeholder="123456"
							autocomplete="one-time-code">
				</td>
			</tr>
		</table>
		</form>
		<div class="savebutton">
			<button style="width:140px;" id="btnVerify">Verify and Enable</button>
		</div>

	</div>
	<?php $this->load->view('global/footer_view') ?>
</body>
</html>
