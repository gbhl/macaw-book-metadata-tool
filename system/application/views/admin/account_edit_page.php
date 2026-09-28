<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
        "http://www.w3.org/TR/html4/strict.dtd">
<html lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=9" />
	<title>Edit User Account | Macaw</title>
	<?php $this->load->view('global/head_view') ?>
</head>
<body class="yui-skin-sam">
	<?php $this->load->view('global/header_view') ?>
	<div class="content-wrapper" style="padding: 20px;">
		<h1>Edit User Account</h1>
		<hr />
		<?php $this->load->view('admin/account_edit_view') ?>
		<hr />
		<p>
			<a href="<?php echo $this->config->item('base_url'); ?>admin/users/" class="button">Back to Users</a>
		</p>
	</div>
	<?php $this->load->view('global/footer_view') ?>
</body>
</html>
