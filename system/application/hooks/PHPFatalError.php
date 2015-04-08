<?php
 
class PHPFatalError {
	public function setHandler() 	{
		register_shutdown_function('handleShutdown');
	}
}
 
function handleShutdown() {
	if (($error = error_get_last())) {
		$buffer = ob_get_contents();
		if ($buffer) {
			ob_clean();
		}
		echo $buffer."\n";
		echo 'ERROR: '.$error['message']."\n";
		echo 'FILE: '.$error['file']."\n";
		echo 'LINE: '.$error['line']."\n";
		
// 		# raport the event, send email etc.
// 		
// 		$msg = $buffer;
// 		$um = 'We have found some error please try again later.';
// 		ob_start();
// 		$data = array();
// 		$CI = get_instance();
// 		if (!$CI->config->item('DEBUG_PRINT')) {
// 			$msg='';
// 		}
// 		$data['print_msg'] = $msg;
// 		$data['um'] = $um;
// 		
// 		load_page('exception', $data);
// 		$buffer = ob_get_contents();
// 		ob_end_clean();
// 		echo $buffer; 
		exit();
		# from /error-capture, you can use another redirect, to e.g. home page
	}
}