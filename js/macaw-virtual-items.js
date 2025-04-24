// ----------------------------
// LOG LIBRARY
//
// This module contains functions to handle logs and queues for administrator access.
//
// Revision History
//     2010/09/01 JMR - Created
// ----------------------------

(function() {
	//derived from Queues to list items for Main
	VirtualListItems = {
		dataTable: null,
		dataSource: null,
		displayMode: null,
		SpreadsheetID: null,
		init: function() {
			if (VirtualListItems.displayMode == 'sources') {
        var myDataSource = new YAHOO.util.DataSource(YAHOO.util.Dom.get("viAllSources"));
        myDataSource.responseType = YAHOO.util.DataSource.TYPE_HTMLTABLE;
        myDataSource.responseSchema = {fields: [{ key: "Name" },{ key: "Path" },{ key: "Items", parser:"number" },{ key: "Pages", parser:"number" },{ key: "Valid" }]};
        var myColumnDefs = [{ key: "Name" },{ key: "Path" },{ key: "Items" },{ key: "Pages" },{ key: "Valid" }];         
        var myDataTable = new YAHOO.widget.DataTable("VirtualItemSources", myColumnDefs, myDataSource);

			} else if (VirtualListItems.displayMode == 'config') {
        var myDataSource = new YAHOO.util.DataSource(YAHOO.util.Dom.get("viConfig"));
        myDataSource.responseType = YAHOO.util.DataSource.TYPE_HTMLTABLE;
        myDataSource.responseSchema = {fields: [{ key: "Name" },{ key: "Value" }]};
        var myColumnDefs = [{ key: "Name" },{ key: "Value" }];
        var myDataTable = new YAHOO.widget.DataTable("VirtualItemConfig", myColumnDefs, myDataSource);

			} else if (VirtualListItems.displayMode == 'items') {
        var myDataSource = new YAHOO.util.DataSource(YAHOO.util.Dom.get("viSummary"));
        myDataSource.responseType = YAHOO.util.DataSource.TYPE_HTMLTABLE;
        myDataSource.responseSchema = {fields: [{ key: "Status" },{ key: "Count", parser:"number" }]};
        var myColumnDefs = [{ key: "Status", formatter: formatStatus },{ key: "Count" }];
        var myDataTable = new YAHOO.widget.DataTable("VirtualItemSummary", myColumnDefs, myDataSource);

	 			// Set up the tabs
				var loadDataCallback = {
					success: function(o) {
						eval('var r = '+ o.responseText);
						if (r.redirect) {
							window.location = r.redirect;
						} else if (r.error) {
							General.showErrorMessage(r.error);
						} else {
							// Set up the data tables
							VirtualListItems.loadTables(r.data);
						}
					},
					failure: function(o) {
						General.showErrorMessage('There was a problem loading the data for the queues. If it helps, the error was:<blockquote style="font-weight:bold;color:#990000;">'+o.statusText+"</blockquote>");
					},
					argument: []
				}; 
				YAHOO.util.Event.addListener("queue-filter", "change", VirtualListItems.updateData);
				url = '/virtual_items/source_itemlist/' + VirtualListItems.sourceName;
				var transaction = YAHOO.util.Connect.asyncRequest('GET', sBaseUrl + url, loadDataCallback);

			} else if (VirtualListItems.displayMode == 'spreadsheet') {
	 			// Set up the tabs
				var loadDataCallback = {
					success: function(o) {
						eval('var r = '+ o.responseText);
						if (r.redirect) {
							window.location = r.redirect;
						} else if (r.error) {
							General.showErrorMessage(r.error);
						} else {
							// Set up the data tables
							VirtualListItems.loadSpreadsheets(r.data);
						}
					},
					failure: function(o) {
						General.showErrorMessage('There was a problem loading the data. If it helps, the error was:<blockquote style="font-weight:bold;color:#990000;">'+o.statusText+"</blockquote>");
					},
					argument: []
				}; 
				// YAHOO.util.Event.addListener("queue-filter", "change", VirtualListItems.updateData);
				url = '/virtual_items/source_spreadsheet_list/';
				var transaction = YAHOO.util.Connect.asyncRequest('GET', sBaseUrl + url, loadDataCallback);

			} else if (VirtualListItems.displayMode == 'spreadsheetitems') {
				// Set up the tabs
			 var loadDataCallback = {
				 success: function(o) {
					 eval('var r = '+ o.responseText);
					 if (r.redirect) {
						 window.location = r.redirect;
					 } else if (r.error) {
						 General.showErrorMessage(r.error);
					 } else {
						 // Set up the data tables
						 VirtualListItems.loadTables(r.data);
					 }
				 },
				 failure: function(o) {
					 General.showErrorMessage('There was a problem loading the data. If it helps, the error was:<blockquote style="font-weight:bold;color:#990000;">'+o.statusText+"</blockquote>");
				 },
				 argument: []
			 }; 
			 YAHOO.util.Event.addListener("queue-filter", "change", VirtualListItems.updateData);
			 url = '/virtual_items/source_itemlist/spreadsheet/' + VirtualListItems.SpreadsheetID;
			 var transaction = YAHOO.util.Connect.asyncRequest('GET', sBaseUrl + url, loadDataCallback);

		 }

			MessageBox.init();

		},
		updateData: function() {
			val = Dom.get('queue-filter').value;
			
			// Reset sort 
			// Get filtered data 
			var state = VirtualListItems.dataTable.getState();
			VirtualListItems.dataSource.sendRequest(Dom.get('queue-filter').value, { 
				success : VirtualListItems.dataTable.onDataReturnInitializeTable, 
				failure : VirtualListItems.dataTable.onDataReturnInitializeTable, 
				scope   : VirtualListItems.dataTable, 
				argument: state 
			}); 
		},
		loadSpreadsheets: function(data) {

			fmtSpreadsheetLink = function(elLiner, oRecord, oColumn, oData) { 
				var id = YAHOO.lang.escapeHTML(oData.replace(/\\'/g, "'")); 
				elLiner.innerHTML = "<a href=\"" + sBaseUrl + "/virtual_items/source/Spreadsheet/" + oRecord.getData('id') + "\">" + oData + "</a>"; 
			};
			
			var myColumnDefs = [
				{key:"id",				      label: 'ID', sortable: false },
				{key:"source_filename", label: 'Filename',    formatter:fmtSpreadsheetLink, sortable: false },
				{key:"uploader",				label: 'Uploaded by', sortable: false },
				{key:"total_items",		  label: '# Items',	    sortable: false },
				{key:"created",	        label: 'Created',		  sortable: false },
				{key:"status",	        label: 'Status',		  sortable: false }
			];

			dsSpreadsheet = new YAHOO.util.DataSource(data);
			dsSpreadsheet.responseType = YAHOO.util.DataSource.TYPE_JSARRAY;
			dsSpreadsheet.responseSchema = {fields: ["id","source_filename","uploader","total_items","created","status"] };
			VirtualListItems.dataTable = new YAHOO.widget.DataTable("divSpreadsheets", myColumnDefs, dsSpreadsheet);
		},
		loadTables: function(data) {
			
			var myColumnDefs = [
				{key:"barcode",			label:'Identifier',		formatter:YAHOO.widget.DataTable.formatLink, sortable: true, sortOptions: { sortFunction: sortNoCase } },
				{key:"title",				label:'Title',				sortable: true },
				// {key:"org_name",		label:'Contributor',	sortable: true },
				{key:"status_code",	label:'Status',				formatter: formatStatus, sortable: true, sortOptions: { sortFunction: sortStatus }}
			];

			VirtualListItems.dataSource = new YAHOO.util.DataSource(data);
			VirtualListItems.dataSource.responseType = YAHOO.util.DataSource.TYPE_JSARRAY;
			VirtualListItems.dataSource.responseSchema = {	fields: ["barcode","title","author","org_name","bytes","status_code"] };
			VirtualListItems.dataSource.doBeforeCallback = function (req, raw, res, cb) {
				// This is the filter function
				var data = res.results || [], filtered = [], i ,l;
				
				if (req) {
					req = req.toLowerCase();
					for (i = 0, l = data.length; i < l; ++i) {
						if (req == 'all') {
							filtered.push(data[i]);

						} else if (req == 'new') {
							if (data[i].status_code == 'new') {
								filtered.push(data[i]);
							}						

						} else if (req == 'in progress') {
							if (data[i].status_code == 'scanning' || data[i].status_code == 'scanned' || data[i].status_code == 'reviewing') {
								filtered.push(data[i]);
							}						

						} else if (req == 'in qa') {
							if (data[i].status_code == 'qa-ready' || data[i].status_code == 'qa-active') {
								filtered.push(data[i]);
							}

						} else if (req == 'awaiting export' ) {
							if (data[i].status_code == 'reviewed') {
								filtered.push(data[i]);
							}						

						} else if (req == 'exporting' ) {
							if (data[i].status_code == 'exporting') {
								filtered.push(data[i]);
							}

						} else if (req == 'completed' ) {
							if (data[i].status_code == 'completed') {
								filtered.push(data[i]);
								}						
						}
					}
					res.results = filtered;
				}
				
				return res;			
			}
			VirtualListItems.dataTable = new YAHOO.widget.DataTable("divInProgress", myColumnDefs, VirtualListItems.dataSource, {
				sortedBy: { 
					key: 'barcode', 
					dir: YAHOO.widget.DataTable.CLASS_ASC
				}
			});
		}
	}
})();
