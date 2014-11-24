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
							YAHOO.macaw.Dashboard.widget_data = r.widgets;
							var uw = user_widgets.widgets;
							for (c = 0; c <= uw.length; c++) {
								for (i in uw[c]) {
									YAHOO.macaw.Dashboard.widgetRegister(uw[c][i], c+1);
								}
							}
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

// 			var oAddWidgetButton = new YAHOO.widget.Button("addWidgetButtton", {
//                                         type: "menu",
//                                         menu: "addWidgetSelect" });
// 
// 			var onWidgetButtonClick = function (p_sType, p_aArgs) {
// 				var oEvent = p_aArgs[0],	//	DOM event
// 					oMenuItem = p_aArgs[1];	//	MenuItem instance that was the
// 											//	target of the event
// 				if (oMenuItem) {
// 					YAHOO.macaw.Dashboard.addWidget(oMenuItem.value, 1);
// 				}
// 			};
// 
// 			var onWidgetButtonMenuLoad = function (p_sType, p_aArgs) {
// 				Dom.setStyle('yui-gen0', 'z-index', '100');
// 			}
// 
// 			//	Add a "click" event listener for the Button's Menu
// 			oAddWidgetButton.getMenu().subscribe("click", onWidgetButtonClick);
// 			oAddWidgetButton.getMenu().subscribe("show", onWidgetButtonMenuLoad);
// 
			// Make the columns drag-enterable
			var column1Drop = new YAHOO.util.DDTarget('Column1', "Group1");
			var column2Drop = new YAHOO.util.DDTarget('Column2', "Group1");

			// Initialize the continue button
			var continueClick = function () { window.location = sBaseUrl+'/main'; }
          // Continue Button removed from Dashboard page
          //  var obtnContinue = new YAHOO.widget.Button("btnContinue");
          //  obtnContinue.on("click", continueClick);

		},

		widgetRegister: function(name, column) {
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

			/* Create the close button */
// 			var aClose = Dom.get(document.createElement('a'));
// 			Dom.addClass(aClose, 'container-close');
// 			Dom.setStyle(aClose, 'text-indent', '-10000em');
// 			aClose.href = "#";
// 			aClose.id = "Widget-close-"+name;
// 			YAHOO.util.Event.addListener(aClose, "click", YAHOO.macaw.Dashboard.removeWidget, name);


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
// 			divWidget.appendChild(aClose);

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

		hdlDragStart: function(x, y) {
			if (this.id == 'Column1' || this.id == 'Column2') {
				return;
			}
			var dragEl = this.getDragEl();
			var el = this.getEl();
			YAHOO.macaw.Dashboard.container = el.parentNode;

			el.style.display = "none";
			dragEl.style.zIndex = ++YAHOO.macaw.Dashboard.zIndex;
			dragEl.innerHTML = el.innerHTML;
			dragEl.style.color = "#ebebeb";
			dragEl.style.backgroundColor = "#fff";
			dragEl.style.textAlign = "center";
			YAHOO.macaw.Dashboard.marker.style.display = "none";
			YAHOO.macaw.Dashboard.marker.style.height = Dom.getStyle(dragEl, "height");
			YAHOO.macaw.Dashboard.marker.style.width = Dom.getStyle(dragEl, "width");
			YAHOO.macaw.Dashboard.marker.style.margin = "5px";
			YAHOO.macaw.Dashboard.marker.style.marginBottom = "20px";
			YAHOO.macaw.Dashboard.marker.style.border = "2px dashed #7e7e7e";
			YAHOO.macaw.Dashboard.marker.style.display= "block";
			YAHOO.macaw.Dashboard.container.insertBefore(YAHOO.macaw.Dashboard.marker, el);
		},

		hdlDragEnter: function(e, id) {
			var el = document.getElementById(id);
			if (id.substr(0, 6)	=== "Column") {
				el.appendChild(YAHOO.macaw.Dashboard.marker);
			} else {
				YAHOO.macaw.Dashboard.container = el.parentNode;
				YAHOO.macaw.Dashboard.container.insertBefore(YAHOO.macaw.Dashboard.marker, el);
			}
		},

		hdlDragOut: function(e, id) {
			var el = document.getElementById(id);
			YAHOO.macaw.Dashboard.lastRectNode[YAHOO.macaw.Dashboard.container.id] = YAHOO.macaw.Dashboard.getLastNode(YAHOO.macaw.Dashboard.container.lastChild);

			if (el.id === YAHOO.macaw.Dashboard.lastRectNode[YAHOO.macaw.Dashboard.container.id].id) {
				YAHOO.macaw.Dashboard.container.appendChild(YAHOO.macaw.Dashboard.marker);
			}
		},

		hdlDragEnd: function(e, id) {
			var el = this.getEl();
			try {
				YAHOO.macaw.Dashboard.marker = YAHOO.macaw.Dashboard.container.replaceChild(el, YAHOO.macaw.Dashboard.marker);
			} catch(err) {
				YAHOO.macaw.Dashboard.marker = YAHOO.macaw.Dashboard.marker.parentNode.replaceChild(el, YAHOO.macaw.Dashboard.marker);
			}
			el.style.display = "block";
			YAHOO.macaw.Dashboard.updateRegistered();
			YAHOO.macaw.Dashboard.saveUserWidgets();
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
// 
// 		addWidget: function (v, col) {
// 			// Make sure we're adding someting
// 			if (v != '' && v != null && v) {
// 				// Make sure the widget isn't already on the page
// 				if (!YAHOO.macaw.Dashboard.widgetRegister(v, col)) {
// 					General.showErrorMessage('That widget is already in the dashboard.');
// 				}
// 			}
// 			YAHOO.macaw.Dashboard.saveUserWidgets();
// 		},
// 
// 		removeWidget: function(e, id) {
// 			// find the item in the array of registered widgets
// 			var info = null;
// 			for (i = 0; i < YAHOO.macaw.Dashboard.registered.length; i++) {
// 				if (YAHOO.macaw.Dashboard.registered[i].name == id) {
// 					info = YAHOO.macaw.Dashboard.registered[i]
// 					break;
// 				}
// 			}
// 
// 			if (info != null) {
// 				// id = "Widget"+id;
// 				var ch = new Elem('Widget' + id);
// 				var parent = new Elem('Column' + info.column);
// 				// Delete the object and all its children
// 				parent.removeChild(ch);
// 
// 				// Remove the name from the list of registered widgets
// 				YAHOO.macaw.Dashboard.registered.splice(i, 1);
// 				YAHOO.macaw.Dashboard.saveUserWidgets();
// 
// 			} else {
// 				General.showErrorMessage('Couldn\'t find the widget to delete. Strange...');
// 			}
// 			return false; // Prevent the A tag from clicking through to reload the page.
// 		},
// 
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
