<?php
if ($this->session->userdata('message') || 
	$this->session->userdata('warning') || 
	$this->session->userdata('errormessage')) { 
		$class = "message ";
		if ($this->session->userdata('warning')) { $class = "warning "; }
		if ($this->session->userdata('errormessage')) { $class = "error"; }
?>
<div id="alert" class="message-overlay <?php echo $class; ?>">
	<?php if ($this->session->userdata('message')) { ?>
		<div class="message-wrap">
			<div class="icon">✅</div><div class="message"><?php echo ($this->session->userdata('message')); ?></div>
		</div>
	<?php } ?>
	<?php if ($this->session->userdata('warning')) { ?>
		<div class="message-wrap">
			<div class="icon">⚠️</div><div class="message"><?php echo ($this->session->userdata('warning')); ?></div>
		</div>
	<?php } ?>
	<?php if ($this->session->userdata('errormessage')) { ?>
		<div class="message-wrap">
			<div class="icon">⛔</div><div class="message"><?php echo ($this->session->userdata('errormessage')); ?></div>
		</div>
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
