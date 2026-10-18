<form action="<?php echo $this->config->item('base_url'); ?>contributor/save" method="post" id="edit_form">
	<?php if ($new) { ?>
		<input type="hidden" name="new" value="1">
	<?php } else { ?>
		<input type="hidden" name="id" value="<?php echo attr($id); ?>">
	<?php }?>
	<table border="1" cellspacing="5" cellpadding="5">
		<?php if (!$new) { ?>
			<tr class="row">
				<td class="fieldname">Contributor ID:</td>
				<td><?php echo $id; ?></td>
			</tr>
		<?php } ?>
		<tr class="row">
			<td class="fieldname">Contributor Name:</td>
			<td><input type="text" id="org_name" name="name" value="<?php echo attr($name); ?>" size="25"> (required)</td>
		</tr>
		<tr class="row">
			<td class="fieldname">Contact Person:</td>
			<td><input type="text" name="person" value="<?php echo attr($person); ?>" size="25"></td>
		</tr>
		<tr class="row">
			<td class="fieldname">Contact Email:</td>
			<td><input type="text" name="email" value="<?php echo attr($email); ?>" size="25"></td>
		</tr>
		<tr class="row">
			<td class="fieldname">Contact Phone:</td>
			<td><input class="short" type="text" name="phone" value="<?php echo attr($phone); ?>" size="25"></td>
		</tr>
		<tr class="row">
			<td class="fieldname">Address:</td>
			<td>
				<input type="text" name="address" value="<?php echo attr($address); ?>" size="25"><br>
				<input type="text" name="address2" value="<?php echo attr($address2); ?>" size="25">
			</td>
		</tr>
		<tr class="row">
			<td class="fieldname">City:</td>
			<td><input class="short" type="text" name="city" value="<?php echo attr($city); ?>" size="25"></td>
		</tr>
		<tr class="row">
			<td class="fieldname">State/Province:</td>
			<td><input class="short" type="text" name="state" value="<?php echo attr($state); ?>" size="25"></td>
		</tr>
		<tr class="row">
			<td class="fieldname">ZIP/Postal Code:</td>
			<td><input class="short" type="text" name="postal" value="<?php echo attr($postal); ?>" size="25"></td>
		</tr>
		<tr class="row">
			<td class="fieldname">Country:</td>
			<td><input class="short" type="text" name="country" value="<?php echo attr($country); ?>" size="25"></td>
		</tr>
		<?php if ($show_api_keys) { ?>
			<tr class="row">
				<td colspan="2">Internet Archive API Keys (optional):</td>
			</tr>
			<tr class="row">
				<td class="fieldname">API Key:</td>
				<td><input class="short" type="text" name="api_key" autocomplete="false" value="<?php echo attr($api_key); ?>" size="25" maxlength="64"> (max: 64 chars)</td>
			</tr>
			<tr class="row">
				<td class="fieldname">Secret Key:</td>
				<td><input class="short" type="password" name="secret_key" autocomplete="false" value="" size="25" maxlength="64"> (max: 64 chars)</td>
			</tr>
		<?php } ?>
	</table>
</form>
<div class="savebutton">
	<button id="btnSave">Save</button>
</div>
