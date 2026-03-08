<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
		"http://www.w3.org/TR/html4/strict.dtd">
<html lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=9" />
	<title><?php if ($new) { echo 'Add Account'; } elseif ($is_self) { echo 'Account Settings'; } else { echo 'Edit Account'; } ?> | Macaw</title>
	<?php $this->load->view('global/head_view') ?>
	<script type="text/javascript">
		function init() {
			MessageBox.init();
			var obtnSave = new YAHOO.widget.Button("btnSave");
			obtnSave.on('click', function() { Dom.get('edit_form').submit(); });

			var obtnDisableTOTP = new YAHOO.widget.Button("btnDisableTOTP");
			obtnDisableTOTP.on('click', function() {
				if (confirm('Disable two-factor authentication?')) {
					Dom.get('totp_disable_form').submit();
				}
			});
		}
		YAHOO.util.Event.onDOMReady(init);
	</script>
	<style>
		form#totp_disable_form {margin-top:-1em;}
		#btnDisableTOTP {
			padding:8px;
			font-size: 120%;
		}
	</style>
</head>
<body class="yui-skin-sam">
	<?php $this->load->view('global/header_view') ?>
	<div id="edit">
		<h1><?php if ($new) { echo 'Add Account'; } elseif ($is_self) { echo 'Account Settings'; } else { echo 'Edit Account: ' . htmlspecialchars($username); } ?></h1>

		<form action="<?php echo $this->config->item('base_url'); ?>account/settings_save/" method="post" id="edit_form">
			<input type="hidden" name="li_token" value="<?php echo $token; ?>">
			<?php if ($new) { ?>
				<input type="hidden" name="new" value="1">
				<input type="hidden" name="return_to" value="account/settings/new">
			<?php } else { ?>
				<input type="hidden" name="username" value="<?php echo htmlspecialchars($username); ?>">
				<input type="hidden" name="return_to" value="account/settings/<?php echo htmlspecialchars($username); ?>">
			<?php } ?>
			<table border="0" cellspacing="5" cellpadding="5">
				<?php if (!$is_self) { ?>
				<tr class="row">
					<td colspan="2" style="font-size: 125%;">
						<strong><a style="color: #3588A8;" href="<?php echo $this->config->item('base_url'); ?>admin/account">&laquo; Back to Account List</a></strong>
					</td>
				</tr>
				<?php } ?>
				<tr class="row">
					<td class="fieldname">Username:</td>
					<td><?php if ($new) { ?>
						<input type="text" name="username" value="" id="username" size="20">
					<?php } else { ?>
						<?php echo htmlspecialchars($username); ?>
					<?php } ?></td>
				</tr>
				<?php if (!$new) { ?>
					<tr class="row">
						<td class="fieldname">Last Login:</td>
						<td><?php echo $last_login; ?></td>
					</tr>
					<tr class="row">
						<td class="fieldname">Account Created / Last Modified:</td>
						<td><?php echo $created; ?> / <?php echo $modified; ?></td>
					</tr>
				<?php } ?>
				<tr class="row">
					<td class="fieldname">Full Name:</td>
					<td><input type="text" name="full_name" value="<?php echo htmlspecialchars($full_name); ?>" id="full_name" size="30"></td>
				</tr>
				<tr class="row">
					<td class="fieldname">New Password:</td>
					<td><input type="password" name="password" value="" size="20" id="password"></td>
				</tr>
				<tr class="row">
					<td class="fieldname">Confirm Password:</td>
					<td><input type="password" name="password_c" value="" size="20" id="password_c"></td>
				</tr>
				<tr class="row">
					<td class="fieldname">Email:</td>
					<td><input type="text" name="email" value="<?php echo htmlspecialchars($email); ?>" size="60" id="email"></td>
				</tr>
				<tr class="row">
					<td class="fieldname">Contributor:</td>
					<td>
					<?php if ($locked_org_id) { ?>
						<?php echo htmlspecialchars($org_name); ?>
						<input type="hidden" id="org_id" name="org_id" value="<?php echo $org_id; ?>">
					<?php } else { ?>
						<select id="org_id" name="org_id">
							<option value="">-- Select one --</option>
							<?php foreach ($organizations as $o) {
								if ($o->id == $org_id) {
									echo('<option value="'.$o->id.'" selected>'.htmlspecialchars($o->name).'</option>');
								} else {
									echo('<option value="'.$o->id.'">'.htmlspecialchars($o->name).'</option>');
								}
							} ?>
						</select>
					<?php } ?>
					</td>
				</tr>
				<?php if ($is_admin || $is_local_admin) { ?>
				<tr class="row">
					<td class="fieldname" valign="top">Permissions:</td>
					<td>
						<?php foreach (array_keys($permissions) as $p) {
							echo('<input type="checkbox" name="permissions[]" value="'.$p.'" id="perm_'.$p.'"');
							echo(($permissions[$p] ? ' checked' : ''));
							if ($p == 'admin' && !$is_admin) {
								echo(' disabled');
							}
							if ($p == 'scan') {
								echo('> <label for="perm_'.$p.'">Edit Metadata</label><br>');
							} elseif ($p == 'local_admin') {
								echo('> <label for="perm_'.$p.'">Local Admin</label><br>');
							} elseif ($p == 'qa') {
								echo('> <label for="perm_'.$p.'">QA Admin</label><br>');
							} elseif ($p == 'qa_required') {
								echo('> <label for="perm_'.$p.'">QA Required</label><br>');
							} elseif ($p == 'admin') {
								echo('> <label for="perm_'.$p.'">Admin</label><br>');
							}
						} ?>
					</td>
				</tr>
				<?php } ?>
			</table>
		</form>
		<?php if ($is_self) { ?>
			<form action="<?php echo $this->config->item('base_url'); ?>account/totp_disable/" method="post" id="totp_disable_form">
				<table border="0" cellspacing="5" cellpadding="5">
					<tr class="row">
						<td class="fieldname" valign="top">Two-Factor Authentication</td>
						<td>
							<div id="totp-section">
								<?php if ($totp_enabled) { ?>
									<p>Two-factor authentication is currently <strong>enabled</strong> for your account.</p>
										<input type="hidden" name="li_token" value="<?php echo $token; ?>">
										<button style="width:240px; padding:10px;" id="btnDisableTOTP">Disable Two-Factor Authentication</button>
								<?php } else { ?>
									<p>Two-factor authentication is currently <strong>disabled</strong> for your account.</p>
									<p><a style="color: #3588A8;" href="<?php echo $this->config->item('base_url'); ?>account/totp_setup">Enable Two-Factor Authentication</a></p>
								<?php } ?>
							</div>
						</td>
					</tr>
				</table>
			</form>
		<?php } ?>
		<div class="savebutton">
			<button style="width:140px;" id="btnSave"><?php echo $new ? 'Create Account' : 'Save'; ?></button>
		</div>
	</div>
	<?php $this->load->view('global/footer_view') ?>
</body>
</html>
