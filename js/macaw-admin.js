// ----------------------------
// LOG LIBRARY
//
// This module contains functions to handle logs and queues for administrator access.
//
// Revision History
//     2010/09/01 JMR - Created
// ----------------------------

(function() {

	Log = {
		tblLogList: null,
		tblLogDetails: null,
		startingFile: null,
		
		initList: function() {
			var selectRow = function(r) {
				Log.tblLogList.onEventSelectRow(r);
				// Clear any existing timeouts
				Log.loadFile(r.target.textContent);
			}

			var myColumnDefs = [
				{key:"log", label: "Logs"}
			];

			var myDataSource = new YAHOO.util.XHRDataSource(sBaseUrl+'/admin/get_log/');
			myDataSource.responseType = YAHOO.util.DataSource.TYPE_JSARRAY;
			myDataSource.responseSchema = {
				fields: ["log"]
			};

			var pgList = new YAHOO.widget.Paginator({
				rowsPerPage: 20,
				firstPageLinkLabel: '&lt;&lt;',
				previousPageLinkLabel: '&lt;',
				nextPageLinkLabel: '&gt;',
				lastPageLinkLabel: '&gt;&gt;',
				containers : [ "logs-pages" ],
				pageLinks: 6
			});

			Log.tblLogList = new YAHOO.widget.DataTable("logs", myColumnDefs, myDataSource, {
				paginator : pgList
			});
			Log.tblLogList.subscribe("rowClickEvent", selectRow);
			
			if (Log.startingFile) {
				// TODO: highlight the file in the list, but not here. The table isn't filled in yet.
		        Log.loadFile(Log.startingFile);
			}
				// Removed Back Button Code.
		},

		loadFile: function(nm) {
			if (Log.tblLogDetails) {
				Log.tblLogDetails.destroy();
			};

			// Table doesn't exist, create and fill it
			var myColumnDefs = [
				{key:"datetime", label: 'Date', minWidth:125, sortable: true}, //resizeable:"true",
				{key:"ip", label: 'IP Addr', sortable:true},
				{key:"user", label: 'Username', sortable:true},
				{key:"action", label: 'Activity', sortable:true},
				{key:"message", label: 'Details'}
			];

			nm = nm.replace(/books\//, 'books_');
			var myDataSource = new YAHOO.util.XHRDataSource(sBaseUrl+'/admin/get_log/'+nm);
			myDataSource.responseType = YAHOO.util.DataSource.TYPE_JSARRAY;
			myDataSource.maxCacheEntries = 0;
			myDataSource.responseSchema = {
				fields: ["entry", "datetime", "ip", "user", "action", "message"]
			};
			
			var pgDetails = new YAHOO.widget.Paginator({
				rowsPerPage: 20,
				firstPageLinkLabel: '&lt;&lt;',
				previousPageLinkLabel: '&lt;',
				nextPageLinkLabel: '&gt;',
				lastPageLinkLabel: '&gt;&gt;',
				containers : [ "details-pages" ]
			});

			Log.tblLogDetails = new YAHOO.widget.DataTable("details", myColumnDefs, myDataSource, {
				paginator : pgDetails
			});

			// Set up the table to automatically refresh every 2 seconds
			var pollCallback = {
				success: Log.tblLogDetails.onDataReturnReplaceRows,
				failure: null,
				scope: Log.tblLogDetails
			}
			myDataSource.setInterval(2000, null, pollCallback);
			Dom.setStyle('details-note','display','block');
		}

	};

	Queues = {
		completedLoaded: false,
		messageBox: null,
		myColumnDefs: [
			{key:"barcode", label:'Barcode', formatter:YAHOO.widget.DataTable.formatLink }, //try formatting link
			{key:"title",		label:'Title',	sortable: true  },
			{key:"author",		label:'Author',	sortable: true  },
			{key:"org_name",		label:'Contributor',	sortable: true },
			{key:"status_code",	label:'Status',	formatter: formatStatus2,  sortable: true  },
			{key:"date",	label:'Date',	sortable: true  },
			{key:"bytes",				label: "Size",				sortable:true, formatter:formatBytes, minWidth:80,  sortOptions: { sortFunction: sortBytes }},
		],
		init: function() {
			// Set up the tabs
			var myTabs = new YAHOO.widget.TabView('queues');

			var loadDataCallback = {
				success: function(o) {
					eval('var r = '+o.responseText);
					if (r.redirect) {
						window.location = r.redirect;
					} else if (r.error) {
						General.showErrorMessage(r.error);
					} else {
						// Set up the data tables
						Queues.loadTables(r.data);
					}
				},
				failure: function(o) {
					General.showErrorMessage('There was a problem loading the data for the queues. If it helps, the error was:<blockquote style="font-weight:bold;color:#990000;">'+o.statusText+"</blockquote>");
				},
				argument: []
			};

			//removed back button
           // var obtnBack = new YAHOO.widget.Button("btnBack");
           // obtnBack.on("click", function(o) {window.location = sBaseUrl+'/admin';} );
			var transaction = YAHOO.util.Connect.asyncRequest('GET', sBaseUrl+'/admin/queue_data', loadDataCallback);

		},
		loadTables: function(data) {
			
			var dsNew = new YAHOO.util.DataSource(data.new_items);
			var dsProgress = new YAHOO.util.DataSource(data.in_progress);
			var dsExporting = new YAHOO.util.DataSource(data.exporting);
			var dsCompleted = new YAHOO.util.DataSource(data.completed);
			var dsError = new YAHOO.util.DataSource(data.error);

			dsNew.responseType = YAHOO.util.DataSource.TYPE_JSARRAY;
			dsProgress.responseType = YAHOO.util.DataSource.TYPE_JSARRAY;
			dsExporting.responseType = YAHOO.util.DataSource.TYPE_JSARRAY;
			dsCompleted.responseType = YAHOO.util.DataSource.TYPE_JSARRAY;
			dsError.responseType = YAHOO.util.DataSource.TYPE_JSARRAY;

			dsNew.responseSchema 
				= dsProgress.responseSchema 
				= dsExporting.responseSchema 
				= dsCompleted.responseSchema 
				= dsError.responseSchema 
				= { fields: ["barcode","title","author","org_name","status_code","date","bytes"] };

			var tblNew       = new YAHOO.widget.DataTable("divNew", Queues.myColumnDefs, dsNew);
			var tblProgress  = new YAHOO.widget.DataTable("divInProgress", Queues.myColumnDefs, dsProgress);
			var tblExporting = new YAHOO.widget.DataTable("divExporting", Queues.myColumnDefs, dsExporting);
			var tblCompleted = new YAHOO.widget.DataTable("divCompleted", Queues.myColumnDefs, dsCompleted);
			var tblError     = new YAHOO.widget.DataTable("divErrors", Queues.myColumnDefs, dsError);

			var myTabs = new YAHOO.widget.TabView('queues');
			var tab3 = myTabs.getTab(3);
			function handleClick(e) {Queues.initCompleted();}
			tab3.addListener('click', handleClick);
		},
		initCompleted: function() {
			var loadDataCallback = {
				success: function(o) {
					eval('var r = '+o.responseText);
					Queues.messageBox.hide();
					if (r.redirect) {
						window.location = r.redirect;
					} else if (r.error) {
						General.showErrorMessage(r.error);
					} else {

						var dsCompleted = new YAHOO.util.DataSource(r.data.completed);
						dsCompleted.responseType = YAHOO.util.DataSource.TYPE_JSARRAY;
						dsCompleted.responseSchema = { fields: ["barcode","title","author","org_name","status_code","date","bytes"] };
						var tblCompleted = new YAHOO.widget.DataTable("divCompleted", Queues.myColumnDefs, dsCompleted);
						Queues.completedLoaded = true;
					}
				},
				failure: function(o) {
					Queues.messageBox.hide();
					General.showErrorMessage('There was a problem loading the data for the queues. If it helps, the error was:<blockquote style="font-weight:bold;color:#990000;">'+o.statusText+"</blockquote>");
				},
				argument: []
			};
			if (!Queues.completedLoaded) {

				Queues.messageBox = new YAHOO.widget.SimpleDialog("messageDialog", {
					width: "320px",
					visible: false,
					draggable: false,
					fixedcenter: true,
					modal: true,
					underlay: 'none',
					zIndex: 10,
					close: false
				});

				Queues.messageBox.setHeader("Please wait...");
				Queues.messageBox.setBody("Macaw is fetching the completed items.");
				Queues.messageBox.render(document.body);
				Queues.messageBox.show();

				var transaction = YAHOO.util.Connect.asyncRequest('GET', sBaseUrl+'/admin/queue_data/completed', loadDataCallback);
			}
		}
	};
	
	//derived from Queues to list items for Main
	ListItems = {
		dataTable: null,
		dataSource: null,
		init: function() {
			// Set up the tabs
			var myTabs = new YAHOO.widget.TabView('queues');
			var loadDataCallback = {
				success: function(o) {
					eval('var r = '+ o.responseText);
					if (r.redirect) {
						window.location = r.redirect;
					} else if (r.error) {
						General.showErrorMessage(r.error);
					} else {
						// Set up the data tables
						ListItems.loadTables(r.data);
					}
				},
				failure: function(o) {
					General.showErrorMessage('There was a problem loading the data for the queues. If it helps, the error was:<blockquote style="font-weight:bold;color:#990000;">'+o.statusText+"</blockquote>");
				},
				argument: []
			};

			YAHOO.util.Event.addListener("queue-filter", "change", ListItems.updateData);
			var url = (this.url ? this.url : '/admin/user_queue_data');

			var transaction = YAHOO.util.Connect.asyncRequest('GET', sBaseUrl + url, loadDataCallback);
			MessageBox.init();

		},
		updateData: function() {
			val = Dom.get('queue-filter').value;
			
			// Reset sort 
			// Get filtered data 
			var state = ListItems.dataTable.getState();
				ListItems.dataSource.sendRequest(Dom.get('queue-filter').value, { 
				success : ListItems.dataTable.onDataReturnInitializeTable, 
				failure : ListItems.dataTable.onDataReturnInitializeTable, 
				scope   : ListItems.dataTable, 
				argument: state 
			}); 
		},
		loadTables: function(data) {
			
			var myColumnDefs = [
				{key:"barcode",			label:'Barcode',			formatter:YAHOO.widget.DataTable.formatLink,	sortable: true },
				{key:"title",				label:'Title',				sortable: true },
				{key:"author",			label:'Author',				sortable: true },
				{key:"org_name",		label:'Contributor',	sortable: true },
				{key:"bytes",				label: "Size",				sortable:true, formatter:formatBytes, minWidth:80,  sortOptions: { sortFunction: sortBytes }},
				{key:"status_code",	label:'Status',				formatter: formatStatus, sortable: true }
			];

			ListItems.dataSource = new YAHOO.util.DataSource(data.exporting ? data.exporting : data.in_progress);
			ListItems.dataSource.responseType = YAHOO.util.DataSource.TYPE_JSARRAY;
			ListItems.dataSource.responseSchema = {	fields: ["barcode","title","author","org_name","bytes","status_code"] };
			ListItems.dataSource.doBeforeCallback = function (req, raw, res, cb) {
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

						} else if (req == 'completed' ) {
							if (data[i].status_code == 'reviewed') {
								filtered.push(data[i]);
							}						

						} else if (req == 'exporting' ) {
							if (data[i].status_code == 'exporting') {
								filtered.push(data[i]);
							}						
						}
					}
					res.results = filtered;
				}
				
				return res;			
			}
			ListItems.dataTable = new YAHOO.widget.DataTable("divInProgress", myColumnDefs, ListItems.dataSource, {
				sortedBy: { 
					key: 'barcode', 
					dir: YAHOO.widget.DataTable.CLASS_ASC
				}
			});
		}
	}
})();
