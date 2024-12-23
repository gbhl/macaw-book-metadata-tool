<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
        "http://www.w3.org/TR/html4/strict.dtd">
        <?php	include_once('system/application/config/version.php');
	$cfg = $this->config->item('macaw');
	if ($this->session->userdata('barcode')) {
	$this->book->load($this->session->userdata('barcode'));
	$status = $this->book->status;}
			?>
<html lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=9" />
	<title>Login | Macaw</title>
	<?php $this->load->view('global/head_view') ?>
	<script type="text/javascript">
	    YAHOO.util.Event.onDOMReady(Login.init);
	</script>
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
				<a href="https://docs.google.com/document/d/18TD8BkHbuP6hTKUKb0OV1UzlZ4Qcx0MdOkJt_cjcWL8/edit?usp=sharing" target="_blank">Changes and Release Notes</a>
			<?php } ?>
		</div>
		<?php $this->load->view('global/error_messages_view') ?>	
		
		<div id="logincontent">
			<p>If you can see this, ensure that <em>mod_rewrite</em> is enabled and <em>AllowOverride All</em> is set and the base URL is correct..</p>
		</div>
		
		<div id="logincontenttemplate" style="display:none;visibility:hidden">
		
			<?php echo form_open($this->config->item('base_url').'login/checklogin', array('id' => 'loginform')) ?>
			
			
						<span class="loginlabel">
						<?php echo form_label('User Name:','username') ?></span>
						<span class="loginfield"><?php echo form_input(array('name' => 'username', 'id' => 'username', 'size' => '20', 'maxlength' => '32', 'tabindex' => '1'), $username) ?></span>
					<span class="loginlabel">
						<?php echo form_label('Password:','password') ?></span>
						<span class="loginfield"><?php echo form_password(array('name' => 'password', 'id' => 'password', 'size' => '20', 'maxlength' => '32', 'tabindex' => '2')) ?></span>
					
				</table>
				
				
			<?php echo form_close() ?>
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
