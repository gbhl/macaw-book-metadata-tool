// ----------------------------
// USER LIBRARY
//
// This module contains functions to handle listing and editing users including
// a user editing his/her own profile.
//
// Revision History
//     2010/09/01 JMR - Created
// ----------------------------

(function() {

	Import = {
		obtnImport: null,
		progressBar: null,
		currentFilename: null,
		// ----------------------------
		// Function: init()
		//
		// Sets up the page for importing
		//
		// Arguments
		//    None
		//
		// ----------------------------
		init: function() {
            Import.obtnImport = new YAHOO.widget.Button("btnImport");
            Import.obtnImport.on("click", Import.upload);			
		},
		
		// ----------------------------
		// Function: upload()
		//
		// ----------------------------
		upload: function() {
			var uploadCallback = {
				upload: function(o) {
					eval('var r = '+o.responseText);
					if (r.redirect) {
						window.location = r.redirect;
					} else {
						if (r.error) {
							General.showErrorMessage(r.error);
						} else {
							// Start monitoring the processing of the file
							Import.disableForm();
 							Import.monitorStart(r.filename);
						}
					}
				},
				failure: function(o) {
					// Display a message to the user
					Import.enableForm();
					General.showErrorMessage('Error uploading the CSV file: '+o.statusText);
				},
				argument: []
			}
			var formObject = document.getElementById('upload_form');
			YAHOO.util.Connect.setForm(formObject, true);
			
			var cObj = YAHOO.util.Connect.asyncRequest('POST', sBaseUrl+'/main/import_upload', uploadCallback);			
		},
		
		// ----------------------------
		// Function: monitorStart()
		//
		// ----------------------------
		monitorStart: function(filename) {
			if (Import.progressBar != null) {
				Import.progressBar.destroy();
				Import.progressBar = null;
			}
			Import.currentFilename = filename;
			// Show the monitor for importing
			Dom.setStyle('progress','display','block');
			// Initialize the progress bar if necesary (if not, reset to zero)
			Import.progressBar = new YAHOO.widget.ProgressBar({
				value:0, 
				minValue:0, 
				maxValue:100,
				width:"400px",
				height:"35px"
			}).render('bar');

			// Start querying the server for information
			setTimeout("Import.updateBar()", 1000);
		},

		// ----------------------------
		// Function: updateBar()
		//
		// ----------------------------
		updateBar: function() {
			var callback = {
				success: function(o) {
					eval('var r = '+o.responseText);
					if (r.redirect) {
						window.location = r.redirect;
					} else {
						Import.progressBar.set('value', parseInt(r.value));
						Dom.get('message').innerHTML = '';
						if (r.message != '') {
							Dom.get('message').innerHTML = r.message;
						}
						if (r.finished == 1) {
							Import.enableForm();
						} else {
							setTimeout("Import.updateBar()", 1000);
						}
					}
				},
				failure: function(o) {},
				argument: []
			}
			var cObj = YAHOO.util.Connect.asyncRequest('GET', sBaseUrl+'/main/import_status/'+Import.currentFilename, callback);			
		},
		
		// ----------------------------
		// Function: enableForm()
		//
		// ----------------------------
		enableForm: function() {
			Import.obtnImport.set('disabled', false);
			Dom.get('userfile').disabled = false;
		},

		// ----------------------------
		// Function: disableForm()
		//
		// ----------------------------
		disableForm: function() {
			Import.obtnImport.set('disabled', true);
			Dom.get('userfile').disabled = true;
		}
	
	};

})();