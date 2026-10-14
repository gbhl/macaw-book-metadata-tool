<!-- TODO: Recode this to check the cookie for the book that's selected and set the breadcrumbs appropriately -->
<div id="page_top">
	
	<div id="enter_barcode">
		<input type="text" name="barcode" id="txtBarcode" maxlength="64" placeholder="Enter Item ID">
		<input type="submit" value="Go" onClick="Barcode.submit();return false">
	</div>

	<div id="login_details">
		<a href="#" onClick="User.edit('');"><?php echo h($this->session->userdata('full_name') ? $this->session->userdata('full_name') : '(no-name)') ?></a> |
		<a href="<?php echo safe_url($this->config->item('base_url')); ?>login/logout">Logout</a> |
		<a href="<?php echo safe_url($this->config->item('base_url')); ?>main/terms_view">Terms</a> |
		<a href="http://s.si.edu/BHLMacawUserGuide" target="_blank" rel="noopener noreferrer">Help</a>
	</div>

	<div class="clear"><!-- --></div>
</div>
