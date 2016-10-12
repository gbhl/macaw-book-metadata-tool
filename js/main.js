/*
 * jQuery File Upload Plugin JS Example
 * https://github.com/blueimp/jQuery-File-Upload
 *
 * Copyright 2010, Sebastian Tschan
 * https://blueimp.net
 *
 * Licensed under the MIT license:
 * http://www.opensource.org/licenses/MIT
 */

/* global $, window */

$(function () {
    'use strict';

    // Initialize the jQuery File Upload widget:
    $('#fileupload').fileupload({
        // Uncomment the following to send cross-domain cookies:
        //xhrFields: {withCredentials: true},
      url: '/scan/do_upload/',
      sequentialUploads: false,
      limitConcurrentUploads: 3,
      autoUpload: false,
      maxFileSize: 536870912
    });

    // Enable iframe cross-domain access via redirect option:
    $('#fileupload').fileupload(
        'option',
        'redirect',
        window.location.href.replace(
            /\/[^\/]*$/,
            '/cors/result.html?%s'
        )
    );
    
    $('#fileupload').bind('fileuploadsubmit', function (e, data) {
        var inputs = data.context.find(':input');
        if (inputs.filter(function () {
                return !this.value && $(this).prop('required');
            }).first().focus().length) {
            data.context.find('button').prop('disabled', false);
            return false;
        }
        data.formData = inputs.serializeArray();
    });

    $('#fileupload').bind('fileuploaddone', function (e, data) {
    	if (data.result) {
				if (data.result.reload) {
					$('#pdfmessage')[0].innerHTML = 'Status: '+data.result.message;
					$('#pdfmessage')[0].style.display = 'inline-block';
					setTimeout(function(){
						$('.' + $("#fileupload").fileupload("option").downloadTemplateId).remove();
						initializeFiles();
					}, 3000);
				} else {
					$('#pdfmessage')[0].innerHTML = '';
					$('#pdfmessage')[0].style.display = 'none';
				}
			}
    });

		initializeFiles();
	
		function initializeFiles() {
			// Load existing files:
			$('#fileupload').addClass('fileupload-processing');
			$.ajax({
					// Uncomment the following to send cross-domain cookies:
					//xhrFields: {withCredentials: true},
					url: $('#fileupload').fileupload('option', 'url'),
					dataType: 'json',
					context: $('#fileupload')[0]
			}).always(function () {
					$(this).removeClass('fileupload-processing');
			}).done(function (result) {
					$(this).fileupload('option', 'done')
							.call(this, $.Event('done'), {result: result});
					if (result.reload) {
						setTimeout(function(){
							$('#pdfmessage')[0].innerHTML = 'Status: '+result.message;
							$('#pdfmessage')[0].style.display = 'inline-block';
							$('.' + $("#fileupload").fileupload("option").downloadTemplateId).remove();
							initializeFiles();
						}, 3000);
					} else {
						$('#pdfmessage')[0].innerHTML = '';
						$('#pdfmessage')[0].style.display = 'none';
					}
			});		
		}

});
