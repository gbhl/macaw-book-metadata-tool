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
				if (oRecord.getData('qa_required') == 1) {
					html += '<img src="'+sBaseUrl+'/images/icons/book_open_tick.png" height="16" width="16" alt="User can QA items." title="QA is required for this user.">&nbsp;';
				}
				if (oRecord.getData('qa') == 1) {
					html += '<img src="'+sBaseUrl+'/images/icons/tick.png" height="16" width="16" alt="User can QA items." title="User can QA items.">&nbsp;';
				}
				if (oRecord.getData('local_admin') == 1) {
					html += '<img src="'+sBaseUrl+'/images/icons/building_wizard.png" height="16" width="16" alt="User is a local admin." title="User is a local admin.">&nbsp;';
				}
				if (oRecord.getData('admin') >= 1) {
					html += '<img src="'+sBaseUrl+'/images/icons/wizard_hat.png" height="16" width="16" alt="User is an admin." title="User is an admin.">&nbsp;';
				}
				elLiner.innerHTML = html;
			}

			var myColumnDefs = [
				{key:"flags",        label: "Flags",         formatter:formatFlags},
				{key:"username",     label: "User Name",     sortable:false},
				{key:"full_name",    label: "Full Name",     sortable:true},
				{key:"email",        label: "Email Address", sortable:true},
				{key:"organization", label: "Contributor",  sortable:true},
				{key:"last_login",   label: "Last Login",    formatter:formatDate, sortable:true},
				{key:"username",     label: "Actions",       formatter:formatEditLink}
			];

			var myDataSource = new YAHOO.util.XHRDataSource(sBaseUrl+'/admin/account_list/');
			myDataSource.responseType = YAHOO.util.DataSource.TYPE_JSARRAY;
			myDataSource.responseSchema = {
				fields: ["id", "username", "last_login", "full_name", "email", "organization", "scan", "qa_required", "qa", "admin", "local_admin"]
			};

			User.tblUsers = new YAHOO.widget.DataTable("accounts", myColumnDefs, myDataSource);
			
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
			window.location = sBaseUrl + '/account/settings/' + username;
		},

		add: function() {
			window.location = sBaseUrl + '/account/settings/new';
		},

	};

})();
