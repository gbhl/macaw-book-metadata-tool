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

	User = {
		tblUsers: null,
		editDialog: null,

		// ----------------------------
		// Function: initList()
		//
		// Initializes the account list page by creating and filling in the list
		// of users. The data is taken from the /admin/account_list/ URL which
		// returns a JSON array and is used to populate a YUI data source. This
		// allows us to later requery the database when the data has changed.
		//
		// Arguments
		//    None
		//
		// Return Value / Effect
		//    List is shown and is populated with data
		// ----------------------------
		initList: function() {
			var formatEditLink = function(elLiner, oRecord, oColumn, oData) {
				elLiner.innerHTML = "<a href=\"#\" onClick=\"User.edit('"+oData+"');return false;\"><img src=\""+sBaseUrl+"/images/icons/user_edit.png\" wdith=\"16\" height=\"16\"></a>";
				if (oRecord.getData('username') != 'admin') {
					elLiner.innerHTML += "&nbsp;&nbsp;&nbsp;&nbsp;<a href=\"#\" onClick=\"User.del('"+oData+"');return false;\"><img src=\""+sBaseUrl+"/images/icons/delete.png\" wdith=\"16\" height=\"16\"></a>";
				}
			}

			var formatDate = function(elLiner, oRecord, oColumn, oData) {
				if (!isBlank(oData)) {
					var dt =  new Date(oData.replace(/\.\d+$/, ''));
					elLiner.innerHTML = YAHOO.util.Date.format(dt, {'format': '%b %d, %Y %I:%M %P'}, 'en');
				}
			}

			var formatFlags = function(elLiner, oRecord, oColumn, oData) {
				var html = '';
				if (oRecord.getData('scan') == 1) {
					html += '<img src="'+sBaseUrl+'/images/icons/book_open.png" height="16" width="16" alt="User can scan and review items." title="User can scan and review items.">&nbsp;';
				}
				if (oRecord.getData('qa') == 1) {
					html += '<img src="'+sBaseUrl+'/images/icons/tick.png" height="16" width="16" alt="User can QA items." title="User can QA items.">&nbsp;';
				}
				if (oRecord.getData('local_admin') == 1) {
					html += '<img src="'+sBaseUrl+'/images/icons/building_wizard.png" height="16" width="16" alt="User is a local admin." title="User is a local admin.">&nbsp;';
				}
				if (oRecord.getData('admin') == 1) {
					html += '<img src="'+sBaseUrl+'/images/icons/wizard_hat.png" height="16" width="16" alt="User is an admin." title="User is an admin.">&nbsp;';
				}
				elLiner.innerHTML = html;
			}

			var myColumnDefs = [
				{key:"flags",        label: "Flags",         formatter:formatFlags},
				{key:"username",     label: "User Name",     sortable:false},
				{key:"full_name",    label: "Full Name",     sortable:true},
				{key:"email",        label: "Email Address", sortable:true},
				{key:"organization", label: "Organization",  sortable:true},
				{key:"last_login",   label: "Last Login",    formatter:formatDate, sortable:true},
				{key:"username",     label: "Actions",       formatter:formatEditLink}
			];

			var myDataSource = new YAHOO.util.XHRDataSource(sBaseUrl+'/admin/account_list/');
			myDataSource.responseType = YAHOO.util.DataSource.TYPE_JSARRAY;
			myDataSource.responseSchema = {
				fields: ["id", "username", "last_login", "full_name", "email", "organization", "scan", "qa", "admin", "local_admin"]
			};

			User.tblUsers = new YAHOO.widget.DataTable("accounts", myColumnDefs, myDataSource);
			// removed back button
         //   var obtnBack = new YAHOO.widget.Button("btnBack");
        //    obtnBack.on("click", function(o) {window.location = sBaseUrl+'/admin';} );
			
			// Initialize the Add User button
			var obtnAddAccount = new YAHOO.widget.Button("btnAddAccount");
			obtnAddAccount.on("click", User.add);

		},

		// ----------------------------
		// Function: refreshList()
		//
		// Encapsulates a method to requery the datasource for the list of
		// users for updated data. The data source remembers what URL it last
		// used, so we don't need to give it a new URL. We also set params to
		// reinitialize the table from scratch.
		//
		// Arguments
		//    None
		//
		// Return Value / Effect
		//    The list is updated with any new data
		// ----------------------------
		refreshList: function() {
			if (User.tblUsers) {
				User.tblUsers.getDataSource().sendRequest('', User.tblUsers.onDataReturnInitializeTable, User.tblUsers);
			}
		},

		// ----------------------------
		// Function: edit()
		//
		// Given a username, calls the server to get the content of the edit
		// form to uddate the user's information. The server also performs
		// permission checking between the current user and the target that is
		// being edited. If we can edit the user, the content of the form is
		// passed to the User.showEditDialog() routine to actually show the form.
		//
		// Arguments
		//    username - Which user to edit (optional. If empty, revert to add-user mode)
		//
		// Return Value / Effect
		//    Nothing (we pass control to the show dialog routine if successful)
		// ----------------------------
		editUserCallback: {
			success: function (o){
				eval('var r = '+o.responseText);
				if (r.redirect) {
					window.location = r.redirect;
				} else {
					if (r.error) {
						General.showErrorMessage(r.error);
					} else {
						// If we were really successful, then we cna show the dialog
						User.showEditDialog(r.dialogContent);
					}
				}
			},
			failure: function (o){
				General.showErrorMessage('There was a problem loading the user details dialog. If it helps, the error was:<blockquote style="font-weight:bold;color:#990000;">'+o.statusText+"</blockquote>");
			}
		},

		del: function(username) {
			var handleDelete = {
				success: function (o){
					eval('var r = '+o.responseText);
					if (r.redirect) {
						window.location = r.redirect;
					} else {
						if (r.error) {
							General.showErrorMessage(r.error);
						} else {
							General.showMessage(r.message);
							User.refreshList();
						}
					}
				},
				failure: function (o){
					General.showErrorMessage('There was a problem deleting the account. If it helps, the error was:<blockquote style="font-weight:bold;color:#990000;">'+o.statusText+"</blockquote>");
				}
			}
			if (confirm('Are you sure you want to delete the account for "'+username+'"?')) {
				// Call the URL to get the data
				var transaction = YAHOO.util.Connect.asyncRequest('GET', sBaseUrl+'/admin/account_delete/'+username+'/', handleDelete, null);
			}
		},


		edit: function(username) {
			// Call the URL to get the data
			var transaction = YAHOO.util.Connect.asyncRequest('GET', sBaseUrl+'/admin/account_edit/'+username+'/', User.editUserCallback, null);
		},

		add: function() {
			// Call the URL to get the data
			var transaction = YAHOO.util.Connect.asyncRequest('GET', sBaseUrl+'/admin/account_add/', User.editUserCallback, null);
		},

		// ----------------------------
		// Function: showEditDialog()
		//
		// This handles the display of the dialog, the creation of the save
		// and cancel buttons and all of the handlers to make it work, which
		// inlcudes submitting the updated data to the server and the response
		// back. Finally this also updates the list if new data is successfully
		// saved to the database.
		//
		// Arguments
		//    ct - The content of the dialog box, taken from the
		//         /admin/account_edit/ URL
		//
		// Return Value / Effect
		//    Dialog box is shown, prepped to send data to the server
		// ----------------------------
		showEditDialog: function(ct) {
			// What happens when whe click the "Save" button?
			var handleSubmit = function() {
				this.submit();
			};

			// What happens when we click the "Cancel" button?
			var handleCancel = function() {
				this.cancel();
			};

			// What happens when the saving of the data is successful
			// (from the perspective of the web server, 404/500/403/etc errors)
			var handleSuccess = function(o) {
				eval('var r = '+o.responseText);
				if (r.error) {
					General.showErrorMessage(r.error);
				} else {
					General.showMessage(r.message);
					User.refreshList();
				}
			};

			// What happens when we fail to submit the data to the server.
			var handleFailure = function(o) {
				General.showErrorMessage('There was a problem saving the user\'s information. If it helps, the error was:<blockquote style="font-weight:bold;color:#990000;">'+o.statusText+"</blockquote>");
			};

			// Delete any div that might have contained the old dialog
			General.divDelete('dlgEdit');

			// Create a new div on the page to hold the dialog box.
			var newDiv = Dom.get(document.createElement('div'));
			newDiv.id = 'dlgEdit';
			Dom.get(document.body).insertBefore(newDiv, Dom.get('doc3'));

			// Create the dialog box
			User.editDialog = new YAHOO.widget.Dialog("dlgEdit", {
				fixedcenter : true,
				visible : false,
				constraintoviewport : true,
				modal: true,
				zindex: 5,
				buttons : [
					{ text:"Save", handler: handleSubmit, isDefault: true },
					{ text:"Cancel", handler: handleCancel }
				]
			});

			// Set the content of the dialog box
			User.editDialog.setHeader('Edit Account');
			User.editDialog.setBody(ct);

			// Validate the entries in the form to require that the passwords
			// match and that we have a full name.
			User.editDialog.validate = function() {
				var data = this.getData();
				if (Dom.get('username') && (data.username == '' || data.username == null || !data.username)){
					General.showErrorMessage('Please enter a username.');
					return false;
				
				} else if ((data.password || data.password_c) && data.password != data.password_c) {
					General.showErrorMessage('The passwords you entered do not match.');
					return false;

				} else if (data.full_name == '' || data.full_name == null || !data.full_name) {
					General.showErrorMessage('Please enter a full name.');
					return false;

				} else if (data.email == '' || data.email == null || !data.email) {
					General.showErrorMessage('Please enter an email address.');
					return false;

				} else if (data.org_id[0] == '' || data.org_id[0] == null || !data.org_id[0]) {
					General.showErrorMessage('Please select an organization.');
					return false;

				} else {
					return true;
				}
			};

			// Wire up the success and failure handlers for when we submit the form
			User.editDialog.callback = {
				success: handleSuccess,
				failure: handleFailure
			};

			// Render the Dialog
			User.editDialog.render('body');
			User.editDialog.show();
		}

	};

})();