// ----------------------------
// EXPORT LIBRARY
//
// Revision History
//     2017/04/20 Kristina Cottingham - Created
// ----------------------------

(function() {

	Export = {
		// ----------------------------
		// Function: initList()
		//
		// Initializes the page by creating and filling in the list of stalled exports.
		// This data is taken from the /admin/stalled_exports_list/ URL which returns
		// a JSON array and is used to populate a YUI data source.
		//
		// Arguments
		//    None
		//
		// Return Value / Effect
		//    List is shown and is populated with data
		// ----------------------------
		init: function() {
			var loadDataCallback = {
				success: function(obj){
					eval('var r = ' + obj.responseText);
					if (r.redirect) {
						window.location = r.redirect;
					} else if (r.error) {
						General.showErrorMessage(r.error);
					} else {
						// Set up the data tables
						Export.load(r.data);
					}
				},
				failure: function(obj) {
					General.showErrorMessage('There was a problem loading the data for the queues. If it helps, the error was:<blockquote style="font-weight:bold;color:#990000;">' + obj.statusText + "</blockquote>");
				},
				argument: []
			};
			var transaction = YAHOO.util.Connect.asyncRequest('GET', sBaseUrl + this.url, loadDataCallback);
			MessageBox.init();
		},
		load: function(data) {
			var myColumnDefs = [
				{key:"barcode",				label:'Barcode',		sortable: true, formatter: YAHOO.widget.DataTable.formatLink},
				{key:"title",				label:'Title',			sortable: true },
				{key:"identifier",			label:'IA',	sortable: true, formatter: formatIAIdentifier },
				{key:"org_name",			label:'Contributor',	sortable: true },
				{key:"bytes",				label:'Size',			sortable: true, formatter: formatBytes, minWidth: 80,  sortOptions: { sortFunction: sortBytes }},
				{key:"date_export_start",	label:'Date Started',	sortable: true },
				{key:"status_code",			label:'Status',			sortable: true, formatter: formatStatus }
			];
			
			var myDataSource = new YAHOO.util.DataSource(data.exporting);
			myDataSource.responseType = YAHOO.util.DataSource.TYPE_JSARRAY;
			myDataSource.responseSchema = {
				fields: ["barcode", "title", "org_name", "bytes", "date_export_start", "status_code", "identifier"]
			};
			
			var items = new YAHOO.widget.DataTable("items", myColumnDefs, myDataSource);	
		}
	};
})()