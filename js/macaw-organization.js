// ----------------------------
// ORGANIZATION LIBRARY
//
// This module contains functions to handle listing and editing organizations including
// a organization editing his/her own profile.
//
// Revision History
//     2012/03/19 JMR - Created
// ----------------------------

(function() {

	Organization = {
		tblOrganization: null,
		editDialog: null,

		// ----------------------------
		// Function: initList()
		//
		// Initializes the organization list page by creating and filling in the list
		// of organizations. The data is taken from the /admin/organization_list/ URL which
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
				elLiner.innerHTML = "<a href=\"#\" onClick=\"Organization.edit('"+oData+"');return false;\"><img src=\""+sBaseUrl+"/images/icons/building_edit.png\" wdith=\"16\" height=\"16\"></a>";
				elLiner.innerHTML += "&nbsp;&nbsp;<a href=\"#\" onClick=\"Organization.del('"+oData+"');return false;\"><img src=\""+sBaseUrl+"/images/icons/delete.png\" wdith=\"16\" height=\"16\"></a>";
			}

			var myColumnDefs = [
				{key:"name",		label: "Name",			sortable:true},
				{key:"person",	label: "Contact",		sortable:false},
				{key:"city",		label: "City",			sortable:false},
				{key:"state",		label: "State",			sortable:false},
				{key:"country",	label: "Country",		sortable:true},
				{key:"bytes",		label: "Space",			sortable:true, formatter:formatBytes, minWidth:80,  sortOptions: { sortFunction: sortBytes }},
				{key:"id",			label: "Actions",		formatter:formatEditLink}
			];

			var myDataSource = new YAHOO.util.XHRDataSource(sBaseUrl+'/admin/organization_list/');
			myDataSource.responseType = YAHOO.util.DataSource.TYPE_JSARRAY;
			myDataSource.responseSchema = {
				fields: ["id", "name", "person", "city", "state", "country", "bytes"]
			};

			Organization.tblOrganizations = new YAHOO.widget.DataTable("organizations", myColumnDefs, myDataSource);
		//removed back button	
        //    var obtnBack = new YAHOO.widget.Button("btnBack");
        //    obtnBack.on("click", function(o) {window.location = sBaseUrl+'/admin';} );
			
			// Initialize the Add Organization button
			var obtnAddOrganization = new YAHOO.widget.Button("btnAddOrganization");
			obtnAddOrganization.on("click", Organization.add);

		},

		// ----------------------------
		// Function: refreshList()
		//
		// Encapsulates a method to requery the datasource for the list of
		// organizations for updated data. The data source remembers what URL it last
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
			if (Organization.tblOrganizations) {
				Organization.tblOrganizations.getDataSource().sendRequest('', Organization.tblOrganizations.onDataReturnInitializeTable, Organization.tblOrganizations);
			}
		},

		// ----------------------------
		// Function: edit()
		//
		// Given a organization id, calls the server to get the content of the edit
		// form to uddate the organization's information. The server also performs
		// permission checking between the current organization and the target that is
		// being edited. If we can edit the organization, the content of the form is
		// passed to the Organization.showEditDialog() routine to actually show the form.
		//
		// Arguments
		//    id - Which organization to edit (optional. If empty, revert to add-org mode)
		//
		// Return Value / Effect
		//    Nothing (we pass control to the show dialog routine if successful)
		// ----------------------------
		editOrganizationCallback: {
			success: function (o){
				eval('var r = '+o.responseText);
				if (r.redirect) {
					window.location = r.redirect;
				} else {
					if (r.error) {
						General.showErrorMessage(r.error);
					} else {
						// If we were really successful, then we cna show the dialog
						Organization.showEditDialog(r.dialogContent);
					}
				}
			},
			failure: function (o){
				General.showErrorMessage('There was a problem loading the contributor details dialog. If it helps, the error was:<blockquote style="font-weight:bold;color:#990000;">'+o.statusText+"</blockquote>");
			}
		},

		del: function(id) {
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
							Organization.refreshList();
						}
					}
				},
				failure: function (o){
					General.showErrorMessage('There was a problem deleting the contributor. If it helps, the error was:<blockquote style="font-weight:bold;color:#990000;">'+o.statusText+"</blockquote>");
				}
			}
			if (confirm('Are you sure you want to delete this contributor?')) {
				// Call the URL to get the data
				var transaction = YAHOO.util.Connect.asyncRequest('GET', sBaseUrl+'/admin/organization_delete/'+id+'/', handleDelete, null);
			}
		},


		edit: function(id) {
			// Call the URL to get the data
			var transaction = YAHOO.util.Connect.asyncRequest('GET', sBaseUrl+'/admin/organization_edit/'+id+'/', Organization.editOrganizationCallback, null);
		},

		add: function() {
			// Call the URL to get the data
			var transaction = YAHOO.util.Connect.asyncRequest('GET', sBaseUrl+'/admin/organization_add/', Organization.editOrganizationCallback, null);
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
		//         /admin/organization_edit/ URL
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
					Organization.refreshList();
				}
			};

			// What happens when we fail to submit the data to the server.
			var handleFailure = function(o) {
				General.showErrorMessage('There was a problem saving the contributor\'s information. If it helps, the error was: <blockquote style="font-weight:bold;color:#990000;">'+o.statusText+"</blockquote>");
			};

			// Delete any div that might have contained the old dialog
			General.divDelete('dlgEdit');

			// Create a new div on the page to hold the dialog box.
			var newDiv = Dom.get(document.createElement('div'));
			newDiv.id = 'dlgEdit';
			Dom.get(document.body).insertBefore(newDiv, Dom.get('doc3'));

			// Create the dialog box
			Organization.editDialog = new YAHOO.widget.Dialog("dlgEdit", {
				fixedcenter : true,
				visible : false,
				constraintoviewport : true,
				modal: true,
				zindex: 10,
				buttons : [
					{ text:"Save", handler: handleSubmit, isDefault: true },
					{ text:"Cancel", handler: handleCancel }
				]
			});

			// Set the content of the dialog box
			Organization.editDialog.setHeader('Edit Contributor');
			Organization.editDialog.setBody(ct);

			// Validate the entries in the form to require that the passwords
			// match and that we have a full name.
			Organization.editDialog.validate = function() {
				var data = this.getData();
				if (data.name == '' || data.name == null || !data.name) {
					General.showErrorMessage('Please enter a name of the contributor.');
				} else {
					return true;
				}
			};

			// Wire up the success and failure handlers for when we submit the form
			Organization.editDialog.callback = {
				success: handleSuccess,
				failure: handleFailure
			};

			// Render the Dialog
			Organization.editDialog.render('body');
			Organization.editDialog.show();
		}

	};

})();