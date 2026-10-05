<?php
if ($this->session->userdata('message') || 
	$this->session->userdata('warning') || 
	$this->session->userdata('errormessage')) { 
		$class = '';
		if ($this->session->userdata('message')) { $class = "message "; }
		if ($this->session->userdata('warning')) { $class = "warning "; }
		if ($this->session->userdata('errormessage')) { $class = "error"; }
	?>
	<div id="alert" class="message-overlay <?php echo $class; ?>">
		<?php if ($this->session->userdata('message')) { ?>
			<p><span class="icon">✅</span><?php echo htmlentities($this->session->userdata('message')); ?></p>
		<?php } ?>
		<?php if ($this->session->userdata('warning')) { ?>
			<p><span class="icon">⚠️</span><?php echo htmlentities($this->session->userdata('warning')); ?></p>
		<?php } ?>
		<?php if ($this->session->userdata('errormessage')) { ?>
			<p><span class="icon">⛔</span><?php echo htmlentities($this->session->userdata('errormessage')); ?></p>
		<?php } ?>
		<button id="btnCloseMessage">Close</button>
	</div>
<?php 
}
// Clear the errors
$this->session->set_userdata('message', '');
$this->session->set_userdata('warning', '');
$this->session->set_userdata('errormessage', '');
?>
