// ------------------------------
// DASHBOARD LIBRARY
//
//
//
// Revision History
//     2010/08/06 JMR - Created, initial coding completed.
// ------------------------------

(function() {

	YAHOO.macaw.Dashboard = {
		widget_count: 0,
		lastRectNode: [],
		container: null,
		zIndex: 0,
		marker: document.createElement("div"),
		widget_data: null,
		registered: new Array(),

		init: function() {
			// Get a user's widgets, probably as the page loaded
			// Get the widgets from the server

			var loadWidgets = {
				success: function (o){
					eval('var r = '+o.responseText);
					if (r.redirect) {
						window.location = r.redirect;
					} else {
						if (r.error) {
							General.showErrorMessage(r.error);
						} else {
							Dom.get('summary').innerHTML = r.widgets.summary.html;

							// Pages Per day
							var perday_ctx = document.getElementById('perday').getContext('2d');
							perday_data = {
								labels: [],
								datasets: [{
									label: 'Pages',
									data: [],
									backgroundColor: ['rgba(0, 51, 204)'],
									borderColor: ['rgba(0, 51, 204)'],
								}]
							};
							for (i in r.widgets.perday.data) {
								perday_data.labels.push(r.widgets.perday.data[i].day);
								perday_data.datasets[0].data.push(r.widgets.perday.data[i].pages);
							}
							var perdayChart = new Chart(perday_ctx, {type: 'line', data: perday_data, options: {}});

							// Disk Usage
							var disk_ctx = document.getElementById('disk').getContext('2d');
							disk_data = {
								labels: [],
								datasets: [{
									label: 'Percent (%)',
									data: [],
									backgroundColor: ['rgba(0, 51, 204)'],
									borderColor: ['rgba(0, 51, 204)'],
								}]
							};
							var min = 100;
							var max = 0;

							for (i in r.widgets.disk.data) {
								disk_data.labels.push(r.widgets.disk.data[i].day);
								disk_data.datasets[0].data.push(r.widgets.disk.data[i].value);
								if (r.widgets.disk.data[i].value < min) min = r.widgets.disk.data[i].value;
								if (r.widgets.disk.data[i].value > max) max = r.widgets.disk.data[i].value;
							}
							var opts = {
								scales: {
									y: {
										suggestedMin: Math.max(Math.round(min/10)*10-10, 0),
										suggestedMax: Math.min(Math.round(max/10)*10+10, 100)
									}
								}
							};
							var diskChart = new Chart(disk_ctx, {type: 'line', data: disk_data, options: opts});

							// Total Pages
							var pages_ctx = document.getElementById('pages').getContext('2d');
							pages_data = {
								labels: [],
								datasets: [{
									label: 'Pages',
									data: [],
									backgroundColor: ['rgba(0, 51, 204)'],
									borderColor: ['rgba(0, 51, 204)'],
								}]
							};
							var min = 999999999;
							var max = 0;
							for (i in r.widgets.pages.data) {
								pages_data.labels.push(r.widgets.pages.data[i].day);
								pages_data.datasets[0].data.push(r.widgets.pages.data[i].pages);
								if (r.widgets.pages.data[i].pages < min) min = r.widgets.pages.data[i].pages;
								if (r.widgets.pages.data[i].pages > max) max = r.widgets.pages.data[i].pages;
							}
							var opts = {
								scales: {
									y: {
										suggestedMin: Math.round(min/1000)*1000-1000,
										suggestedMax: Math.round(max/1000)*1000+1000
									}
								}
							};
							var pagesChart = new Chart(pages_ctx, {type: 'line', data: pages_data, options: opts});

							// Disk Usage
							// var dataDisk = new google.visualization.DataTable();
							// dataDisk.addColumn('string','Date');
							// dataDisk.addColumn('number','Percent (%)');
							// for (i in r.widgets.disk.data) {
							// 	dataDisk.addRow([r.widgets.disk.data[i].day,  int(r.widgets.disk.data[i].value)]);
							// }
							// var chartDisk = new google.visualization.LineChart(document.getElementById('disk'));
							// options = {legend: { position: 'bottom' }, pointSize: 5, vAxis : {minValue: 0, maxValue: 100}, fontSize: 13};
							// chartDisk.draw(dataDisk, options);

							// Pages Per day
							// var dataPages = new google.visualization.DataTable();
							// dataPages.addColumn('string','Date');
							// dataPages.addColumn('number','Pages');
							// for (i in r.widgets.pages.data) {
							// 	dataPages.addRow([r.widgets.pages.data[i].day,  int(r.widgets.pages.data[i].pages)]);
							// }
							// var chartPages = new google.visualization.LineChart(document.getElementById('pages'));
							// options = {legend: { position: 'bottom' }, pointSize: 5,  vAxis: { minValue: 0 }, fontSize: 13};
							// if (r.widgets.pages.data.length > 0) {
							//   if (int(r.widgets.pages.data[0].pages) > 30000) {
							//     options.vAxis.minValue = (r.widgets.pages.data[0].pages) - 10000;
							//   }
							// }
							// chartPages.draw(dataPages, options);

						}
					}
				},
				failure: function (o){
					General.showErrorMessage('There was a problem retrieving the metadata for the pages. Please try reloaing the page. If it helps, the error was:<blockquote style="font-weight:bold;color:#990000;">'+o.statusText+"</blockquote>");
				},
				scope: this
			};

			// Call the URL to get the data
			var transaction = YAHOO.util.Connect.asyncRequest('GET', sBaseUrl+'/dashboard/widget/summary,disk,perday,pages', loadWidgets, null);

			// Initialize the continue button
			var continueClick = function () { window.location = sBaseUrl+'/main'; }

		},

		widgetRegister: function(name) {

			// Make sure this widget isn't already on the page
			var found = 0;
			for (i in YAHOO.macaw.Dashboard.registered) {
				if (YAHOO.macaw.Dashboard.registered[i].name == name) {
					return 0;
				}
			}

			// Get the data for the widget
			var l_widget = YAHOO.macaw.Dashboard.widget_data[name];
			YAHOO.macaw.Dashboard.widget_count++;

			/* Set some variables that we'll use later to identify the widget */
			var data_id =   'Widget'+name+'_d';
			var handle_id = 'Widget'+name+'_h';

			/* Create a new DIV to hold the widget and to allow us to drag/drop it */
			var divWidget = Dom.get(document.createElement('div'));
			Dom.addClass(divWidget, 'yui-module yui-overlay yui-panel');
			Dom.setStyle(divWidget, 'visibility', 'inherit');
			divWidget.id = 'Widget'+name;

			/* Create the handle div for the widget */
			var divHandle = Dom.get(document.createElement('div'));
			Dom.addClass(divHandle, 'hdr');
			divHandle.innerHTML = l_widget.title;
			divHandle.id = handle_id;


			/* Create the body div for the widget */
			var divBody = Dom.get(document.createElement('div'));
			Dom.addClass(divBody, 'bd');

			/* Create the body div to contain the swf */
			var divSWF = Dom.get(document.createElement('div'));
			divSWF.innerHTML = l_widget.html;
			// This line for the height is absolutely necessary
			// for the chart to appear on Firefox. Sheesh!
			Dom.setStyle(divSWF, 'height', '200px');
			divSWF.id = data_id;

			/* Put it all together */
			divBody.appendChild(divSWF);
			divWidget.appendChild(divHandle);
			divWidget.appendChild(divBody);

			YAHOO.macaw.Dashboard.registered.push({
				"name": name,
				"column": column
			});

			/* Place our newly created DIV onto the page */
			var el = Dom.get('Column'+column);
			if (el && el != null) {
				el.appendChild(divWidget);
			}

			/* If we had no predefined HTML, fill it with a chart (or whatever) */

			if (l_widget.data && l_widget.type) {
				l_widget.ds = new YAHOO.util.DataSource(l_widget.data);
				l_widget.ds.responseType = l_widget.datasourcetype;
				l_widget.ds.responseSchema = {
					fields: l_widget.fields
				}
				/* Determine the kind of chart to show */
				switch (l_widget.type) {
					case 'LineChart':
						if (l_widget.div_id == 'disk_usage') {
							var percentAxis = new YAHOO.widget.NumericAxis();
							percentAxis.minimum = 0;
							percentAxis.maximum = 100;
							percentAxis.majorUnit = 20;

							l_widget.cht = new YAHOO.widget.LineChart( data_id, l_widget.ds, {
								xField: l_widget.xField,
								yField: l_widget.yField,
								yAxis: percentAxis,
								wmode: "transparent",
								expressInstall: sBaseUrl+"/inc/swf/expressInstall.swf"
							});						
						} else {
							l_widget.cht = new YAHOO.widget.LineChart( data_id, l_widget.ds, {
								xField: l_widget.xField,
								yField: l_widget.yField,
								wmode: "transparent",
								expressInstall: sBaseUrl+"/inc/swf/expressInstall.swf"
							});
						}
						break;
					case 'ColumnChart':
						l_widget.cht = new YAHOO.widget.ColumnChart( data_id, l_widget.ds, {
							xField: l_widget.xField,
							yField: l_widget.yField,
							wmode: "transparent",
							expressInstall: sBaseUrl+"/inc/swf/expressInstall.swf"
						});
						break;
				}
			}

			/* Make the widget draggable */
			var widgetDrag = new YAHOO.util.DDProxy(divWidget, "Group1");
			widgetDrag.setHandleElId(handle_id);
			widgetDrag.startDrag = YAHOO.macaw.Dashboard.hdlDragStart;
			widgetDrag.onDragEnter = YAHOO.macaw.Dashboard.hdlDragEnter;
			widgetDrag.onDragOut = YAHOO.macaw.Dashboard.hdlDragOut;
			widgetDrag.endDrag = YAHOO.macaw.Dashboard.hdlDragEnd;

			l_widget.drag = widgetDrag;

			return 1;
		},


		getLastNode: function(lastChild) {
			var id = lastChild.id;
			if (id && id.substring(0, 6) === "Widget") {
				return lastChild;
			}
			return YAHOO.macaw.Dashboard.getLastNode(lastChild.previousSibling);
		},

		isEmpty: function(el) {
			var test = function(el) {
				return ((el && el.id) ? el.id.substr(0, 6) == "Widget" : false);
			}
			var kids = Dom.getChildrenBy(el, test);
			return (kids.length == 0 ? true : false);
		},

		updateRegistered: function() {
			YAHOO.macaw.Dashboard.registered = new Array();
			// Cycle through the children of the first column
			var c = Dom.getChildren('Column1');
			for (i in c) {
				YAHOO.macaw.Dashboard.registered.push({
					"name": c[i].id.replace(/^Widget/, ''),
					"column": 1
				});
			}

			// Cycle through the children of the second column
			var c = Dom.getChildren('Column2');
			for (i in c) {
				YAHOO.macaw.Dashboard.registered.push({
					"name": c[i].id.replace(/^Widget/, ''),
					"column": 2
				});
			}
		},
		saveUserWidgets: function() {

			// Do nothing, we don't care
			var saveUserWidgetsCallback = {
				success: function (o) {},
				failure: function (o) {}
			};
			var arr = new Array();
			arr[0] = new Array();
			arr[1] = new Array();

			// Convert our array into something better for saving
			var d = YAHOO.macaw.Dashboard.registered;
			for (i = 0; i < d.length; i++) {
				arr[d[i].column - 1].push(d[i].name);
			}

			var strPOST = 'data='+YAHOO.lang.JSON.stringify(arr);
			// Connect to the server to save the data
			var transaction = YAHOO.util.Connect.asyncRequest('POST', sBaseUrl+'/dashboard/save_widgets', saveUserWidgetsCallback, strPOST);


		}
	};

})();
