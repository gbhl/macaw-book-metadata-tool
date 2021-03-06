<form action="<?php echo $this->config->item('base_url'); ?>admin/account_save/" method="post">
<input type="hidden" name="li_token" value="<?php echo($token); ?>">
<?php if ($new) { ?>
<input type="hidden" name="new" value="1">
<?php } else { ?>
<input type="hidden" name="username" value="<?php echo($username); ?>">
<?php }?>
<table border="0" cellspacing="5" cellpadding="5">
	<tr>
		<td>Username:</td>
		<td><?php if ($new) {
			echo('<input type="text" name="username" value=""  id="username" size="12">');
		} else {
			echo($username);
		}?></td>
	</tr>
	<tr>
		<td>Full Name:</td>
		<td><input type="text" name="full_name" value="<?php if (!$new) { echo($full_name); } ?>" id="full_name" size="20"></td>
		<td class="grey">Last Login:</td>
		<td class="grey"><?php if (!$new) { echo($last_login); } else { echo('N/A'); } ?></td>
	</tr>
	<tr>
		<td>New Password:</td>
		<td><input type="password" name="password" value="" size="20" id="password"></td>
		<td class="grey">Modified:</td>
		<td class="grey"><?php if (!$new) { echo($modified); } else { echo('N/A'); } ?></td>
	</tr>
	<tr>
		<td>Confirm Password:</td>
		<td><input type="password" name="password_c" value="" size="20" id="password_c"></td>
		<td class="grey">Created:</td>
		<td class="grey"><?php echo($created); ?></td>
	</tr>
	<tr>
		<td>Email:</td>
		<td colspan="3"><input type="text" name="email" value="<?php if (!$new) { echo($email); } ?>" size="60" id="email"></td>
	</tr>
	<tr>
		<td>Contributor:</td>
		<td colspan="3">
		<?php if ($locked_org_id) { ?>
			<?php echo $org_name; ?>
			<input type="hidden" id="org_id" name="org_id" value="<?php echo $org_id; ?>">
		<?php } else { ?>
			<select id="org_id" name="org_id">
				<option value="">-- Select one --</option>
				<?php foreach ($organizations as $o) {
					if ($o->id == $org_id) {
						echo('<option value="'.$o->id.'" selected>'.$o->name."</option>;");
					} else {
						echo('<option value="'.$o->id.'">'.$o->name."</option>;");
					}
				} ?>
			</select>
		<?php } ?>
		</td>
	</tr>
	<tr>
		<td valign="top">Permissions</td>
		<td colspan="3">
			<?php
				foreach (array_keys($permissions) as $p) {
					echo('<input type="checkbox" name="permissions[]" value="'.$p.'" id="perm_'.$p.'"');
					echo(($permissions[$p] ? ' checked' : ''));
					if (!$is_admin && !$is_local_admin || ($p == 'admin' && !$is_admin)) { 
						echo(' disabled'); 
					}
					if ($p == 'scan') {
						echo("> <label for=\"perm_{$p}\">Edit&nbsp;Metadata</label>&nbsp;&nbsp;");
					} elseif ($p == 'local_admin') {
						echo("> <label for=\"perm_{$p}\">Local&nbsp;Admin</label>&nbsp;&nbsp;");
					} elseif ($p == 'qa') {
						echo("> <label for=\"perm_{$p}\">QA&nbsp;Admin</label>&nbsp;&nbsp;");
					} elseif ($p == 'qa_required') {
						echo("> <label for=\"perm_{$p}\">QA&nbsp;Required</label>&nbsp;&nbsp;");
					} elseif ($p == 'admin') {
						echo("> <label for=\"perm_{$p}\">Admin</label>&nbsp;&nbsp;&nbsp;&nbsp;");
					} else {
						echo("> <label for=\"perm_{$p}\">$p</label>&nbsp;&nbsp;&nbsp;&nbsp;");
					}
				}
			?>
		</td>
	</tr>
</table>

</form>
