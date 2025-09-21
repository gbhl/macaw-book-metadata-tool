<?php
if ($this->session->userdata('message')) {
	echo ('<div id="message" class="message-overlay"><div class="icon"></div>'.$this->session->userdata('message').'<button id="btnCloseMessage">Close</button></div>');
	$this->session->set_userdata('message', '');
}
if ($this->session->userdata('warning')) {
	echo ('<div id="warning" class="message-overlay"><div class="icon"></div>'.$this->session->userdata('warning').'<button id="btnCloseWarning">Close</button></div>');
	$this->session->set_userdata('warning', '');
}
if ($this->session->userdata('errormessage')) {
	echo ('<div id="errormessage" class="message-overlay"><div class="icon"></div>'.$this->session->userdata('errormessage').'<button id="btnCloseError">Close</button></div>');
	$this->session->set_userdata('errormessage', '');
} 
?>

