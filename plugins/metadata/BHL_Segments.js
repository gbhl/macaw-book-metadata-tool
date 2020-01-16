// ------------------------------
// BHL SEGMENT CLASS
//
// This exists apart from individual pages.
//
// Revision History
//
// ------------------------------

/* ****************************************** */
/* CLASS VARIABLES                            */
/* ****************************************** */
var segments = [];
var SegmentComponent = null;
var AuthorComponent = null;

/* ****************************************** */
/* CLASS METHODS                              */
/* ****************************************** */

this.SegmentComponent = function() {
	var metadataFields = [
		{ id: "segment_title", display_name: "Title", type: "text", field: "title"},
		{ id: "segment_genre", display_name: "Genre", type: "select-one", field: "genre"},
		{ id: "segment_language", display_name: "Language", type: "select-one", field: "language"},
		{ id: "segment_doi", display_name: "DOI", type: "text", field: "doi"},
		{ id: "segment_volume", display_name: "Volume", type: "text", field: "volume"},
		{ id: "segment_issue", display_name: "Issue", type: "text", field: "issue"},
		{ id: "segment_series", display_name: "Series", type: "text", field: "series"},
		{ id: "segment_date", display_name: "Date", type: "text", field: "date"},
		{ id: "segment_authors", display_name: "Authors", type: "list"}
	];

	// Add a segment to the list.
	var addSegment = function() {
		var pgs = oBook.pages.arrayHighlighted();

		// Create the segment.
		var segment = {
			id: segments.length + 1,
			item_id: pgs[0].itemID,
			author_list: [],
			page_list: []
		};

		// Prefill fields from the page metadata if available.
		var fields = ["volume", "year", "piece"];
		for (var f in fields) {
			var field = pgs[0].getMetadataValue(fields[f]);
			if (field.length > 0) {
				switch (fields[f]) {
					case "year":
						segment["date"] = field.join("");
						break;
					case "piece":
						var piece = pgs[0].getMetadataValue("piece_text");
						for (var p in field) {
							if (field[p] == "Issue") {
								segment["issue"] = piece[p];
								break;
							}
						}
						break;
					default:
						segment[fields[f]] = field.join("");
				}
			}
		}

		segment.title = "New Segment " + segment.id;
		for (var p in pgs) {
			segment.page_list.push(pgs[p].pageID);
		}
		segments.push(segment);
		updateDropdown(segment);
		updateTable();
	}

	// Remove a segment from the list.
	var removeSegment = function() {
		if (confirm("Delete this segment?")) {
			var segment = getCurrentSegment();
			for (var i = 0; i < segments.length; i++) {
				if (segments[i] == segment) {
					segments.splice(i, 1);
					updateDropdown();
					updateTable();
				}
			}	
		}		
	}

	var removeAllSegments = function() {
		if (confirm("Delete all segments?")) {
			segments = [];
			updateDropdown();
			updateTable();
		}
	}

	// Update the selected pages for a segment.
	var updatePages = function() {
		var segment = getCurrentSegment();
		var pages = oBook.pages.arrayHighlighted();
		
		segment.page_list = [];
		for (var p in pages) {
			segment.page_list.push(pages[p].pageID);
		}

		selectionChanged();
	}

	// Get the currently selected segment.
	var getCurrentSegment = function() {
		var segmentList = Dom.get("segmentList");
		for (var i = 0; i < segments.length; i++) {
			if (segments[i].id == segmentList.value) {
				return segments[i];
			}
		}
	}

	// Event for when the selected segment changes, either through an event or manual trigger.
	var selectionChanged = function() {
		var segment = getCurrentSegment();

		// Load the metadata.
		for (var f in metadataFields) {
			var control = Dom.get(metadataFields[f].id);
			if (segment) {
				switch (metadataFields[f].type) {
					case "text":
					case "select-one":
						if (segment.hasOwnProperty(metadataFields[f].field)) {
							control.value = segment[metadataFields[f].field];
						} else {
							control.value = "";
						}
						control.disabled = false;
						break;
					case "list":
						var buttons = control.getElementsByTagName("img");
						for (var i = 0; i < buttons.length; i++) {
							buttons[i].style.display = "inline";
						}
						break;
				}
			} else {
				switch (metadataFields[f].type) {
					case "text":
					case "select-one":
						control.value = "";
						control.disabled = true;
						break;
					case "list":
						var buttons = control.getElementsByTagName("img");
						for (var i = 0; i < buttons.length; i++) {
							buttons[i].style.display = "none";
						}
						break;
				}
			}
		}

		// Highlight the pages if a segment is selected.
		oBook.pages.selectNone();
		if (segment) {
			for (var p in segment.page_list) {
				var page = oBook.pages.find("pageID", segment.page_list[p]);
				oBook.pages.highlight(page);
			}
		}
		AuthorComponent.refreshList();
	}

	// Event for when a segment's metdata field is changed.
	var metadataChanged = function(obj) {
		var segment = getCurrentSegment();

		// Get the metadata field and update the value.
		var metadataField = null;
		for (var m in metadataFields) {
			if (metadataFields[m].id == obj.id) {
				metadataField = metadataFields[m];
			}
		}
		segment[metadataField.field] = obj.value;

		// Refresh the form if the title changed.
		if (obj.id == "segment_title") {
			updateDropdown(segment);
		}
		updateTable();
	}

	// Update the dropdown and select an item if given.
	var updateDropdown = function(segment = null) {
		var dropdown = Dom.get("segmentList");

		// Clear the dropdown.
		for (var i = Dom.get("segmentList").children.length - 1; i > 0; i--) {
			dropdown.remove(i);
		}

		// Add the segments.
		for (var i = 0; i < segments.length; i++) {
			// Generate an ID.
			segments[i].id = i + 1;

			var option = document.createElement("option");
			option.value = segments[i].id;
			option.text = segments[i].title;
			dropdown.add(option);
		}

		// Set the selected item.
		if (segment) {
			dropdown.value = segment.id;
		} else {
			dropdown.value = "";
		}

		selectionChanged();
	}

	// Updates the extra table with a list of segments.
	var updateTable = function() {
		var selectedSegment = null;
		var div = Dom.get("extra");
		div.innerHTML = null;
		
		var columns = [];
		for (var f in metadataFields) {
			var column = metadataFields[f].display_name;
			columns.push({ key: column, sortable: true });
		}
		columns.push({ key: "Page Spans", sortable: true });
		columns.push({ key: "id" });
		

		// Generate the dataset.
		var data = [];
		for (var i = 0; i< segments.length; i++) {
			var record = [];

			var segment = SegmentComponent.getCurrentSegment();
			if (segment != null && segment.id == segments[i].id) {
				selectedSegment = data.length;
			}

			for (var f in metadataFields) {
				switch (metadataFields[f].type) {
					case "select-one":
						if (segments[i][metadataFields[f].field]) {
							var select = Dom.get(metadataFields[f].id);
							for (var o in select.options) {
								if (select.options[o].value == segments[i][metadataFields[f].field]) {
									record.push(select.options[o].innerHTML);
									break;
								}
							}
						}
						break;
					case "list":
						var authors = [];
						for (var a in segments[i].author_list) {
							authors.push(segments[i].author_list[a].name);
						}
						record.push(authors.join("; "));
						break;
					default:
						if (segments[i][metadataFields[f].field]) {
							record.push(segments[i][metadataFields[f].field]);
						}
				}
			}
			var pages = [];
			for (var p = 0; p < segments[i].page_list.length; p++) {
				var test = oBook.pages.find('pageID', segments[i].page_list[p]);
				pages.push(oBook.pages.pages[test]);
			}
			record.push(buildSequence(pages));
			record.push(segments[i].id);
			data.push(record);
		}

		var ds = new YAHOO.util.LocalDataSource(data);
		ds.responseType = YAHOO.util.LocalDataSource.TYPE_JSARRAY;
		ds.responseSchema = { fields: columns };

		var dt = new YAHOO.widget.DataTable(div, columns, ds);
		dt.hideColumn(dt.getColumn(columns.length - 1))
		dt.set("selectionMode", "single");



		// Works.
		if (selectedSegment != null) {
			dt.selectRow(selectedSegment);
		}

		dt.subscribe("rowClickEvent", function (e) {
			this.unselectAllRows();
			this.selectRow(e.target);

			var row = this.getRecord(e.target);
			var id = row._oData["id"];

			var segmentList = Dom.get("segmentList");
			segmentList.value = id;
			selectionChanged();
		})
	}

	return {
		addSegment: addSegment,
		removeSegment: removeSegment,
		removeAllSegments: removeAllSegments,
		updatePages: updatePages,
		getCurrentSegment: getCurrentSegment,
		selectionChanged: selectionChanged,
		metadataChanged: metadataChanged,
		updateDropdown: updateDropdown,
		updateTable: updateTable
	}
}

this.AuthorComponent = function() {
    var fields = [
        "name",
        "identifier_type", 
        "identifier_value",
        "dates",
		"source"
	];
	
	// Show the author dialog.
	var showDialog = function() {
		YAHOO.macaw.dlgAuthor = new YAHOO.widget.SimpleDialog("pnlAuthor", {
			visible: false, draggable: false, close: false, underlay: "none",
			Y: parseInt(Dom.getY("btnShowAuthorDlg") - 200),
			X: parseInt(Dom.getX("btnShowAuthorDlg")),
			buttons: [
				{ text: "Add", handler: function() {
					// Get the author data.
					var data = {};
					fields.forEach(function(e) {
						var el = Dom.get("segment_author_" + e);
						if (el) {
							if (e == "source" && el.href !== undefined) {
								// Special handling for the hyperlink.
								var value = el.href.match(/\d+$/);
								if (value) {
									data[e] = value[0];
								}
							} else {
								// Everything else.
								data[e] = el.value;
							}
						}
					});
					
					// Add the data to the segment.
					var segment = SegmentComponent.getCurrentSegment();
					segment.author_list.push(data);
		
					closeDialog();
					SegmentComponent.updateTable();
					refreshList();
				}, isDefault: true },
				{ text: "Cancel", handler: closeDialog }
			]
		});
		
		YAHOO.macaw.dlgAuthor.setHeader("Add an author");
		YAHOO.macaw.dlgAuthor.setBody(Dom.get("dlgAuthor").innerHTML);
		YAHOO.macaw.dlgAuthor.render("segment_authors");
		YAHOO.macaw.dlgAuthor.show();

		var request = function() {
			// Empty data source to initialize the widget. 
			var oDS = [];
			var oAC = new YAHOO.widget.AutoComplete("segment_author_name", "segment_author_name_autocomplete", new YAHOO.util.LocalDataSource(oDS));
			
			// Populates the data source whenever data is requested by the widget.
			oAC.dataRequestEvent.subscribe(function (type, args) {
				var url = "https://www.biodiversitylibrary.org/api2/httpquery.ashx?op=AuthorSearch&apikey=d230a327-c544-4f8f-826d-727cf4da24b8&format=json&name=" + args[1];
				// var url = "https://www.biodiversitylibrary.org/api3?op=AuthorSearch&apikey=d230a327-c544-4f8f-826d-727cf4da24b8&format=json&authorname=" + args[1];
				// If the value is exactly a number, assume we are searching on ID instead
				// TODO: this needs more testing. it's not exactly working.
				// if (!isNaN(args[1].trim())) {
				//   url = "https://www.biodiversitylibrary.org/api3?op=GetAuthorMetadata&apikey=d230a327-c544-4f8f-826d-727cf4da24b8&format=json&id=" + args[1].trim()				
				// }
				var response = function() {
					var http = new XMLHttpRequest();
					http.onreadystatechange = function() {
						if (http.readyState == 4 && http.status == 200) {
							var data = JSON.parse(http.responseText);
							// for (var i=0; i<data["Result"].length;i++) {
							//   data["Result"][i]['FullerForm'] = data["Result"][i]['Name'];
							// }
							oDS = new YAHOO.util.LocalDataSource(data["Result"]);
							oDS.responseSchema = {fields: ["Name", "CreatorID", "FullerForm", "Dates", "CreatorUrl"]};
							// oDS.responseSchema = {fields: ["Name", "AuthorID", "FullerForm", "Dates", "CreatorUrl"]};
							args[0].dataSource = oDS;
						}
					}
					http.open("GET", url, true);
					http.send();
				}();
			});
			oAC.itemSelectEvent.subscribe(function (type, args) {
				// Toggles the external BHL link.
				var link = Dom.get("segment_author_source");
				link.href = args[2][4];
				link.style.visibility = "visible";
				link.innerHTML = "(View on BHL: " + args[2][1] + ")";
				
				var el = Dom.get("segment_author_dates");
				el.value = args[2][3];
				
				var el = ["segment_author_identifier_type", "segment_author_identifier_value", "segment_author_dates"];
				el.forEach(function(e) {
					e = Dom.get(e);
					e.disabled = true;
					e.value = "";
				});
			});
			
			oAC.textboxKeyEvent.subscribe(function (type, args) {
				var link = Dom.get("segment_author_source");
				link.href = "";
				link.style.visibility = "hidden";
				link.innerHTML = "";
									
				var el = ["segment_author_identifier_type", "segment_author_identifier_value", "segment_author_dates"];
				el.forEach(function(e) {
					e = Dom.get(e);
					e.disabled = false;
					e.value = "";
				});
			});
			return {
				oDS: oDS,
				oAC: oAC
			};
		}();
		
		// Removes the auto-added YUI2 CSS.
		Dom.get("segment_author_name").parentElement.classList.remove("yui-ac");
		Dom.get("segment_author_name").classList.remove("yui-ac-input");
	}

	// Close the author dialog.
	var closeDialog = function() {
		YAHOO.macaw.dlgAuthor.hide();
		YAHOO.macaw.dlgAuthor.destroy();
		
		Event.removeListener(document, "click");
		if (YAHOO.macaw.dlgAuthor.id = "pnlAuthor") {
			YAHOO.macaw.dlgAuthor = null;
		}			
	}

	// Remove an author from the current segment.
	var removeAuthor = function(obj) {
		var segment = SegmentComponent.getCurrentSegment();
		segment.author_list.splice(obj.toElement.id.match(/\d+/), 1);
		SegmentComponent.updateTable();
		refreshList();
	}

	// Remove all the authors from the current segment.
	var removeAll = function() {
		if (confirm("Delete all authors for this segment?")) {
			var segment = SegmentComponent.getCurrentSegment();
			segment.author_list = [];
			SegmentComponent.updateTable();
			refreshList();
		}
	}

	// Refresh the author control according to the selected segment.
	var refreshList = function() {
		var ul = Dom.get("segment_authors_list");
		while (ul.firstChild) {
			ul.removeChild(ul.firstChild);
		}

		var segment = SegmentComponent.getCurrentSegment();
		if (segment) {
			if (!segment.author_list) {
				segment.author_list = [];
			}
			for (var a in segment.author_list) {
				var author = segment.author_list[a];

				var bhlIcon = document.createElement("span");
				bhlIcon.title = "View this author at BHL";
				bhlIcon.className = "bhl-icon";

				var deleteButton = document.createElement("a");
				deleteButton.title = "Remove this author";
				deleteButton.className = "remove-button";
				Event.addListener(deleteButton, "click", removeAuthor);
				
				var li = document.createElement("li");
				li.appendChild(deleteButton);

				var text = author["name"];
				if (author["dates"]) {
					text = text + " (" + author["dates"] + ")";
				}
				if (author["source"]) {
					text = document.createTextNode(text);

					var a = document.createElement("a");
					a.href = "http://www.biodiversitylibrary.org/creator/" + author["source"];
					a.target = "_blank";
					a.id = "author" + ul.children.length;
  				a.title = "View this author at BHL";
					a.appendChild(text);
					a.appendChild(bhlIcon);
					li.appendChild(a);
				} else {
					li.appendChild(document.createTextNode(text));
				}
				ul.appendChild(li);
			}
		}
	}

	return {
        showDialog : showDialog,
        refreshList : refreshList,
        removeAuthor : removeAuthor,
		removeAll : removeAll
	}
}

this.checkPages = function() {
	var segment = SegmentComponent.getCurrentSegment();

	if (segment) {
		var mismatch = false;
		var warningText = Dom.get("segmentWarning");
		var pageText = Dom.get("segmentPages");
		warningText.style.display = "none";
		pageText.style.display = "none";

		var pages = oBook.pages.arrayHighlighted();
		if (segment.page_list.length == pages.length) {
			for (var p in pages) {
				if (pages[p].pageID != segment.page_list[p]) {
					mismatch = true;
					break;
				}
			}
			if (!mismatch) {
				var sequences = buildSequence(pages);
				pageText.innerHTML = "Selected: " + sequences.join(", ");
				pageText.style.display = "inline";
				SegmentComponent.updateTable();
				return;
			}
		}

		// Show the warning.
		warningText.style.display = "inline";
	}
}

this.buildSequence = function(pages) {
	var sequences = [];
	for (var p = 0; p < pages.length; p++) {
		var start = pages[p];
		var end = start;
		while (p < pages.length - 1 && pages[p].metadata["sequence"] - pages[p + 1].metadata["sequence"] == -1) {
			end = pages[p + 1];
			p++;
		}

		var pagePrefix = start.getMetadataValue("page_prefix").join("");
		var startNumber = start.getMetadataValue("page_number").join("");
		if (start == end) {
			if (pagePrefix && startNumber) {
				sequences.push(pagePrefix + " " + startNumber);
			} else {
				sequences.push("Seq " + start.metadata["sequence"]);
			}
		} else {
			var endNumber = end.getMetadataValue("page_number").join("");
			if (pagePrefix && startNumber && endNumber) {
				sequences.push(pagePrefix + " " + startNumber + " - " + endNumber);
			} else {
				sequences.push("Seq " + start.metadata["sequence"] + " - " + end.metadata["sequence"]);
			}
		}
	}
	return sequences;
}


Event.onAvailable("btnSelectNone-button", function() {
	var el = document.getElementById("btnSelectNone-button");
	YAHOO.util.Event.addListener(el, "click", function() {
		SegmentComponent.refreshForm();
	});
});

/* ****************************************** */
/* This runs once the page has loaded.        */
/* ****************************************** */
Event.onDOMReady( function() {
	SegmentComponent = SegmentComponent();
	AuthorComponent = AuthorComponent();

	onPagesHighlighted.subscribe(function () {
		checkPages();
	});

	onNoPagesHighlighted.subscribe(function () {
		checkPages();
	});

	var el = document.getElementById("btnSave");
	YAHOO.util.Event.addListener(el, "click", function() {
		var callback = {
				failure: function(ob) {
					var y = 10;
				},
				scope: this
		};

		var data = "data=" + JSON.stringify({
			"itemID": oBook.itemID,
			"segments" : segments
		});
		var transaction = YAHOO.util.Connect.asyncRequest("POST", sBaseUrl + '/bhl_segments/save_segments', callback, data);
	});

	onBookLoaded.subscribe(function() {
		var callback = {
				success: function(obj) {
					if (JSON.parse(obj.responseText)) {
						segments = JSON.parse(obj.responseText);
					}
					SegmentComponent.updateDropdown();
					SegmentComponent.updateTable();
				},
				failure: function(ob) {
					var y = 10;
				},
				scope: this
		};

		var data = "data=" + JSON.stringify(oBook.itemID);	
		var transaction = YAHOO.util.Connect.asyncRequest("POST", sBaseUrl + '/bhl_segments/load_segments', callback, data);
	});
});

// ------------------------------
// CUSTOM METADATA OBJECT
//
// This represents the custom metadata for a single page in our book. Not used.
//
// Parameters
//    parent - Just in case we need it, the page object that contains us.
//    data - The data that makes up the entire metadata for this page.
//
// Revision History
//
// ------------------------------
YAHOO.macaw.BHL_Segments = function(parent, data) {
	// Intialize the fields that will hold our metadata
	this.name = "BHL_Segments"; // Should be the name of this file without the .js extension
	this.data = data;
	this.sequence = data.sequence_number;
	this.parent = parent;
	this.filebase = data.filebase;
	this.page = this.parent.parent;
	this.pageID = this.parent.parent.pageID;
	
	// ----------------------------
	// Function: getTableColumns()
	//
	// Returns a description of the data columns used for setting up a YUI data table.
	// This does not return any data. This may also create functions internally to the
	// method which can be referred to in the array of objects returned. See the YUI
	// Data Table documentation on waht this should return.
	//
	// EXPERT NOTE: Generally this won't need to be modified unless you have special formatting
	// you want to apply to the info in the data table.
	//
	// Arguments
	//     None
	//
	// Return Value / Effect
	//     An array of object suitable for YUI Data Table ColumnDefs
	// ----------------------------
	this.getTableColumns = function() {
		var cols = [];
		return cols;
	}

	// ----------------------------
	// Function: init()
	//
	// Massage and create the data structures necessary to make this thing work.
	// Generally this is a simple task but this particular module has a
	// couple of special fields that are arrays and must be handled more carefully.
	//
	// Arguments
	//     None
	//
	// Return Value / Effect
	//     A reduced object of the data in the original Page object.
	// ----------------------------
	this.init = function() {
		this.sequence = this.data.sequence_number;
		this.filebase = this.data.filebase;
		
		// this.AuthorComponent = new this.AuthorComponent();

		// This is really special. We DO NOT want to call this more than once.
		// So we set our own variable onto the oBook because we can
		// (and which is more or less global, as far as we are concerned)
		if (!oBook.initialized_BHL_Segments) {
			// Enter stuff here that should happen exactly once when the page is loaded.
			oBook.initialized_BHL_Segments = true;
		}
	}

	// ----------------------------
	// Function: getData()
	//
	// Get the data for this page. We return an object (associative array) of data. The elements
	// in the object may include simple arrays of data. Since this data is going to be saved in
	// the database, we can handle these simple arrays, but more complex objects will produce
	// unexpected results.
	//
	// Arguments
	//     None
	//
	// Return Value / Effect
	//     An object
	// ----------------------------
	this.getData = function() {
		var data = {};
		return data;
	}

	// ----------------------------
	// Function: getTableData
	//
	// Return the data of this page in a manner suitable for display in a data table. This means
	// that there is no capability to display anything other than strings. Every item returned in this
	// object must be a simple value or something like [object Object] will be displayed in the table.
	//
	// Arguments
	//     None
	//
	// Return Value / Effect
	//     A reduced object of the data in the original Page object.
	// ----------------------------
	this.getTableData = function() {
		var data = {};
		return data;
	}

	// ----------------------------
	// Function: set()
	//
	// Sets some metadata based on some rules for multi-selection. If we have
	// more than one things selected, we do not save the metadata if the new value
	// is blank. That would be bad, I think. (?) Otherwise, we save whatever
	// value was given
	//
	// Arguments
	//    field - the field being saved
	//    value - the value being saved
	//    mult  - whether or not there were multiple pages selected
	//
	// Return Value / Effect
	//    N/A
	// ----------------------------
	this.set = function(field, value, mult) {}

	// ----------------------------
	// Function: render()
	//
	// Fill the metadata fields with the data from the page. The "this" object
	// is the currently selected page.
	//
	// Arguments
	//     None
	//
	// Return Value / Effect
	//     The fields are filled in. Or not. Depends on the data.
	// ----------------------------
	this.render = function() {}

	// ----------------------------
	// Function: renderMultiple()
	//
	// This is used when special handling of the metadata fields is needed when
	// multiple pages are selected. This could be a class method, but it's not.
	//
	// Arguments
	//     None
	//
	// Return Value / Effect
	//     The fields are filled in. Or not. Depends on the data.
	// ----------------------------
	this.renderMultiple = function() {}

	// ----------------------------
	// Function: unrender()
	//
	// Empties out the metadata fields since it's called before calling renderMultiple()
	// and when no pages are seleted. This could be a class method, but it's not.
	//
	// Arguments
	//     None
	//
	// Return Value / Effect
	//     The fields are empty or otherwise initialized
	// ----------------------------
	this.unrender = function() {}
}
