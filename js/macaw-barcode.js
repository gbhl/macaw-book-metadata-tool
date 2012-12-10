// ------------------------------
// BARCODE LIBRARY
//
// Thought there would be more here, but this is a handler to submit a barcode
// to the system to validate it. That's all we need. Arguably this could be
// consolidated into the General library.
//
// Revision History
//     2010/08/06 JMR - Created, initial coding completed.
// ------------------------------
(function() {

	// ----------------------------
	// Function: submit()
	//
	// Takes the barcode from the page, sends it via XHR to the server,
	// processes the result. if the barcode can't be found or there's an error
	// then we present an error message. If successful, then the server gives
	// us a URL to redirect to.
	//
	// Arguments
	//    None, but this gets the barcode from the "txtBarcode" element on the page.
	//
	// Return Value / Effect
	//    redirection via window.location() or an error message popup
	// ----------------------------
	Barcode = {
		submit: function() {
			// Handle the response back from the server
			var handleCreateItem = function() {
				window.location = sBaseUrl+'/main/add/'+sBarcode;
			}
			var callback = {
				success: function(o) {
					try {
						eval('var r = '+o.responseText);
						if (r.redirect) {
							window.location = r.redirect;
						} else if (r.question) {
							Dom.get('txtBarcode').value = '';
							General.showYesNo(r.question, handleCreateItem);
						} else if (r.error) {
							Dom.get('txtBarcode').value = '';
							General.showErrorMessage(r.error);
						}
					} catch (err) {
						General.showErrorMessage('There was a problem sending the barcode to the server. If it helps, the error was:<blockquote style="font-weight:bold;color:#990000;">'+err+"</blockquote>");
					}
				},
				failure: function(o) {
					General.showErrorMessage('There was a problem sending the barcode to the server. Please try scanning the barcode again. If it helps, the error was:<blockquote style="font-weight:bold;color:#990000;">'+o.statusText+"</blockquote>");
				},
				argument: []
			}

			// Check that we actually have a barcode in the field
			var sBarcode = Dom.get('txtBarcode').value;
			if (sBarcode == '' || sBarcode == null || !sBarcode) {
				General.showMessage('Please enter a barcode.');
			} else {
				// Submit the query to the server
				var transaction = YAHOO.util.Connect.asyncRequest('GET', sBaseUrl+'/main/barcode/'+sBarcode, callback, null);
			}
		}
	};

})();
