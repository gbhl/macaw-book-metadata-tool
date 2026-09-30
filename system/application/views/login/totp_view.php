<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
	"http://www.w3.org/TR/html4/strict.dtd">
<html lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=9" />
	<title>Two-Factor Authentication | Macaw</title>
	<?php $this->load->view('global/head_view') ?>
	<script type="text/javascript">
		function init() {
			MessageBox.init();
			var obtnVerify = new YAHOO.widget.Button("btnVerify");
			obtnVerify.on('click', function() { Dom.get('totpform').submit(); });			
		}
	    YAHOO.util.Event.onDOMReady(init);
	</script>
</head>
<body class="yui-skin-sam">

	<div id="logincontainerborder">
		<div id="logincontainer">
			<div id="loginheader">
				<img id="hero" width="318" height="483" alt="Rosellas" src="<?php echo $this->config->item('base_url'); ?>images/rosellas_macaw_login.png">
				<h1>Macaw</h1>
				<h2>Metadata Collection and Workflow System</h2>
			</div>
			<?php $this->load->view('global/error_messages_view') ?>

			<div id="logincontent">
				<h3>Two-Factor Authentication</h3>

				<p>Enter the 6-digit code<br>from your<br>authenticator app.</p>

				<form action="<?php echo $this->config->item('base_url'); ?>login/checktotp" method="post" id="totpform">
					<span class="loginlabel"><label for="totp_code">Code:</label></span>
					<span class="loginfield">
						<input type="text" name="totp_code" id="totp_code"
							size="10" maxlength="6" placeholder="000000"
							autocomplete="one-time-code" autofocus="autofocus" style="font-size:120%">
					</span>
					
					<span class="loginfield" id="login">
						<button style="width:120px; padding: 1em;" id="btnVerify">Verify Code</button>
					</span>
				</form>

				<p>
					<a href="<?php echo $this->config->item('base_url'); ?>login/logout">Cancel and return to login</a>
				</p>
			</div>
		</div>
	</div>
	<?php $this->load->view('global/footer_view') ?>
</body>
</html>
