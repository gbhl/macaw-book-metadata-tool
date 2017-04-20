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
	
	// Disables the Cancel button from the start.
	$('.btn-warning.cancel').prop("disabled", true);

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

		// Call when we select a file to upload
		// Set the counter of the file so that we know what order we uploaded them
    $('#fileupload').bind('fileuploadchange', function (e, data) {
			data.files.forEach(function(el, idx, arr) { 
				arr[idx].counter = idx+1;
			});
    });

		// Call when files are dropped onto the page
		// Set the counter of the file so that we know what order we uploaded them
    $('#fileupload').bind('fileuploaddrop', function (e, data) {
			data.files.forEach(function(el, idx, arr) { 
				arr[idx].counter = idx+1;
			});
    });

		// Call when we start uploading a single file
		// We need to know if we are uploading a PDF so we can handle buttons and messages later.
    $('#fileupload').bind('fileuploadsend', function (e, data) {
			if (data.files["0"].name.match(/\.pdf$/)) {
				loadingPDF = true;
			}
			// Enables the Cancel button while the file is being uploaded.
			//$('.btn-warning.cancel').prop("disabled", false);
    });

		// This is called when we start uploading.
		// We pass some extra data (namely, the sequence number) with the image
    $('#fileupload').bind('fileuploadsubmit', function (e, data) {
        var inputs = data.context.find(':input');
        if (inputs.filter(function () {
                return !this.value && $(this).prop('required');
            }).first().focus().length) {
            data.context.find('button').prop('disabled', false);
            return false;
        }
        data.formData = inputs.serializeArray();
		// Enables the Cancel button while files are being uploaded.
		$('.btn-warning.cancel').prop("disabled", false);
    });

		// This is called when one file is finished uploading
		// We check to see if we need to reload the list of files
    $('#fileupload').bind('fileuploaddone', function (e, data) {
    	if (data.result) {
				if (data.result.reload) {
					loadingPDF = true;
					$('#pdfmessage')[0].innerHTML = 'Status: '+data.result.message;
					$('#pdfmessage')[0].style.display = 'inline-block';
					setTimeout(function(){
						$('.' + $("#fileupload").fileupload("option").downloadTemplateId).remove();
						initializeFiles();
					}, 3000);
				} else {
					if (loadingPDF) {
						if (hasMissingPages) {
							$('.btn-missing').css('display','inline');
						} else {
							$('.btn-metadata').css('display','inline');
						}
						loadingPDF = false;
					}
					$('#pdfmessage')[0].innerHTML = '';
					$('#pdfmessage')[0].style.display = 'none';
				}
			}
    });

		// This is called when all files is finished uploading
		// We display the buttons on the page, but only if we aren't loading a PDF
    $('#fileupload').bind('fileuploadstop', function (e, data) {
			if (!loadingPDF) {
				// Wait until we hope we are done
				if (hasMissingPages) {
					$('.btn-missing').css('display','inline');
				} else {
					$('.btn-metadata').css('display','inline');
				}
				
				// Disables the Cancel button when upload is complete.
				$('.btn-warning.cancel').prop("disabled", true);
			}
    });



    // Click handlers for Enter Page Metadata and Insert Missing Pages
    $('.btn-metadata').on("click", function(){window.location.href = '/scan/review';});
    $('.btn-missing').on("click",  function(){window.location.href = '/scan/missing/insert';});

		initializeFiles();

		function initializeFiles() {
			// Load existing files:
			$('#fileupload').addClass('fileupload-processing');
			$('.btn-missing').css('display','none');
			$('.btn-metadata').css('display','none');
			$.ajax({
					// Uncomment the following to send cross-domain cookies:
					//xhrFields: {withCredentials: true},
					url: $('#fileupload').fileupload('option', 'url'),
					dataType: 'json',
					context: $('#fileupload')[0]
			}).always(function () {
					$(this).removeClass('fileupload-processing');
			}).done(function (result) {
					$(this).fileupload('option', 'done').call(this, $.Event('done'), {result: result});
					if (result.reload) {
						setTimeout(function(){
							loadingPDF = true;
							$('#pdfmessage')[0].innerHTML = 'Status: '+result.message;
							$('#pdfmessage')[0].style.display = 'inline-block';
							$('.' + $("#fileupload").fileupload("option").downloadTemplateId).remove();
							initializeFiles();
						}, 3000);
					} else {
						if (loadingPDF) {
							if (hasMissingPages) {
								$('.btn-missing').css('display','inline');
							} else {
								$('.btn-metadata').css('display','inline');
							}
							loadingPDF = false;
						}
						$('#pdfmessage')[0].innerHTML = '';
						$('#pdfmessage')[0].style.display = 'none';
					}
			});
		}

});
