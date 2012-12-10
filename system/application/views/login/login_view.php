<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
        "http://www.w3.org/TR/html4/strict.dtd">
        <?	include_once('system/application/config/version.php');
	$cfg = $this->config->item('macaw');
	if ($this->session->userdata('barcode')) {
	$this->book->load($this->session->userdata('barcode'));
	$status = $this->book->status;}
			?>
<html lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8">
	<title>Login | Macaw</title>
	<? $this->load->view('global/head_view') ?>
	<script type="text/javascript">
	    YAHOO.util.Event.onDOMReady(Login.init);
	</script>
</head>
<body class="yui-skin-sam">

	<div id="logincontainerborder">
	<div id="logincontainer">
		<div id="loginheader">
		<img id="hero" width="318" height="483" alt="Rosellas" src="/images/rosellas_macaw_login.png">
				<h1>Macaw</h1>
				<h2>Metadata Collection and Workflow System</h2>
				<hr>
				<? if ($version_rev == 'VERSION_GOES_HERE') { ?>
					<h3>Demo / Development Version</h3>
				<? } else { ?>
					<h3>Version <? echo($version_rev); ?> / <? echo($version_date); ?></h3>
				<? } ?>
		</div>
		<? $this->load->view('global/error_messages_view') ?>	
		
		<div id="logincontent">
			
		</div>
		
		<div id="logincontenttemplate" style="display:none;visibility:hidden">
		
			<? echo form_open($this->config->item('base_url').'login/checklogin', array('id' => 'loginform')) ?>
			
			
						<span class="loginlabel">
						<? echo form_label('User Name:','username') ?></span>
						<span class="loginfield"><? echo form_input(array('name' => 'username', 'id' => 'username', 'size' => '20', 'maxlength' => '32', 'tabindex' => '1'), $username) ?></span>
					<span class="loginlabel">
						<? echo form_label('Password:','password') ?></span>
						<span class="loginfield"><? echo form_password(array('name' => 'password', 'id' => 'password', 'size' => '20', 'maxlength' => '32', 'tabindex' => '2')) ?></span>
					
				</table>
				
				
			<? echo form_close() ?>
		</div>
	
	</div>
	</div>
	<div id="credit">
		 Based on the Paginator originally created<br>
		 at the Missouri Botanical Garden.
	</div>
	<? $this->load->view('global/footer_view') ?>
</body>
</html>
