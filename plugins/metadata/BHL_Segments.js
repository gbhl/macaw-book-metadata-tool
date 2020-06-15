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
		{ id: "segment_translated_title", display_name: "Translated Title", type: "text", field: "translated_title" },
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
			glowPages(segment.page_list);
		} else {
			glowPages([]);
		}
		AuthorComponent.refreshList();
	}

	var glowPages = function(segment_pages) {
		// Unglow all the pages
		for (var p in oBook.pages.pages) {
			el = oBook.pages.pages[p].elemINFO;
			el.style.opacity = '0';
			el.style.backgroundColor = 'none';
		}
		// Glow those that are selected
		for (var p in segment_pages) {
			var page = oBook.pages.find("pageID", segment_pages[p]);
			el = oBook.pages.pages[page].elemINFO;
			el.style.opacity = '.3';
			el.style.backgroundColor = 'green';			
		}
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

		if (!dropdown) { return; } // We are probably on the missing pages page.
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
	// TODO
	var updateTable = function() {
		var selectedSegment = null;
		var div = Dom.get("extra");
		if (!div) { return; } // We are probably on the missing pages page.
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
						var select = Dom.get(metadataFields[f].id);
						for (var o in select.options) {
							if (select.options[o].value == segments[i][metadataFields[f].field]) {
								record.push(select.options[o].innerHTML);
								break;
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
						record.push(segments[i][metadataFields[f].field]);
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

	var validateSegments = function () {
		var errors = '';
		for (var i = 0; i < segments.length; i++) {
			if (!segments[i].title) {
				errors += '   * Title is required for each segment.\n';
				break;
			}
		}
		// for (var i = 0; i < segments.length; i++) {
		// 	if (!segments[i].date) {
		// 		errors += '   * Date is required for each segment.\n';
		// 		break;
		// 	}
		// }
		for (var i = 0; i < segments.length; i++) {
			if (!segments[i].volume && !segments[i].issue) { // Either is required
				errors += '   * Volume and/or Issue are required and must be numeric.\n';
				break;
			}
		}
		for (var i = 0; i < segments.length; i++) {
			var volume = segments[i].volume + "";
			if (volume != "null" && !volume.match(/^\d+$/)) { // Volume must be numeric
				errors += '   * Volume must be numeric.\n';
				break;
			}
		}
		for (var i = 0; i < segments.length; i++) {
			var issue = segments[i].issue + "";
			if (issue != "null" && !issue.match(/^\d+$/)) { // Issue must be numeric
				errors += '   * Issue must be numeric.\n';
			}
		}
		for (var i = 0; i < segments.length; i++) {
			var series = segments[i].series + "";
			if (series != "null" && !series.match(/^\d+$/)) { // Issue must be numeric
				errors += '   * Series must be numeric.\n';
			}
		}
		for (var i = 0; i < segments.length; i++) {
			var date = segments[i].date + "";
			if (date != "null" && !date.match(/^\d\d\d\d$/)) { // Issue must be numeric
				errors += '   * Date must be a four digit year (YYYY).\n';
			}
		}
		for (var i = 0; i < segments.length; i++) {
			if (!segments[i].genre) {
				errors += '   * Genre is required for each segment. ';
				break;
			}
		}
		return errors;
	}

	var saveSegments = function () {
		if (window.location.toString().match(/\/scan\/missing\/insert/)) {
			// We don't save segments whew inserting pages.
			return;
		}
		var callback = {
			failure: function (ob) {
				var y = 10;
			},
			scope: this
		};

		segmentErrors = validateSegments();
		if (segmentErrors != "") {
			window.setTimeout(function () { oBook.modified = true; }, 5000) 
			alert('One or more segments had errors. Please correct them before saving: \n\n' + segmentErrors);
		}

		var data = "data=" + encodeURIComponent(JSON.stringify({
			"itemID": oBook.itemID,
			"segments": segments
		}));

		// Get the CSRF Token and add it to the data
		//	<input type="hidden" name="li_token" value="e024ddb2a0b7222fa6eb296e9b0c9def">
		token_name = 'li_token';
		token_value = 'NULL';
		els = document.getElementsByTagName("meta");
		for (i = 0; i < els.length; i++) {
			if (els[i].name == 'csrf-name') { token_name = els[i].content; }
			if (els[i].name == 'csrf-token') { token_value = els[i].content; }
		}
		data = data + "&" + token_name + "=" + token_value;
		var transaction = YAHOO.util.Connect.asyncRequest("POST", sBaseUrl + '/bhl_segments/save_segments', callback, data);
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
		saveSegments: saveSegments,
		updateTable: updateTable,
		glowPages: glowPages
	}
}

this.AuthorComponent = function() {
	// Track whether the author dialog box is open
	var authorDialogOpen = false;
	var fields = [
		"last_name",
		"first_name",
		"identifier_type", 
		"identifier_value",
		"start_date",
		"end_date",
		"source"
	];
	// Show the author dialog.
	var showDialog = function() {
		YAHOO.macaw.dlgAuthor = new YAHOO.widget.SimpleDialog("pnlAuthor", {
			visible: false, draggable: false, close: false, underlay: "none",
			Y: parseInt(Dom.getY("btnShowAuthorDlg") - 200),
			X: parseInt(Dom.getX("btnShowAuthorDlg")),
			buttons: [
				{
					text: "Add", handler: function () {
						// Get the author data.
						var data = {};
						fields.forEach(function (e) {
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
						// Validation
						if (!data['last_name'].trim()) {
							alert('A last name is required for each author.')
						} else {
							// Add the data to the segment.
							var segment = SegmentComponent.getCurrentSegment();
							data['name'] = data["last_name"]
								+ (data["first_name"] ? ', ' + data["first_name"] : '')
								+ ((data["start_date"] || data["end_date"]) ? ' (' + data["start_date"] + '-' + data["end_date"] + ')' : '');
							segment.author_list.push(data);

							closeDialog();
							SegmentComponent.updateTable();
							refreshList();
						}

					}, isDefault: true
				},
				{ text: "Cancel", handler: closeDialog }
			]
		});

		YAHOO.macaw.dlgAuthor.setHeader("Add an author");
		YAHOO.macaw.dlgAuthor.setBody(Dom.get("dlgAuthor").innerHTML);
		YAHOO.macaw.dlgAuthor.render("segment_authors");
		YAHOO.macaw.dlgAuthor.show();
		this.authorDialogOpen = true;

		var apiKey = 'd230a327-c544-4f8f-826d-727cf4da24b8';
		var apiVersion = 2;
		var dsBHL;
		// Are we using API version 2 or 3, if so, the calls and return 
		// structure are different.
		if (apiVersion == 2) {
			// API v2 uses CREATORID
			dsBHL = new YAHOO.util.XHRDataSource("https://www.biodiversitylibrary.org/");
			dsBHL.responseSchema = {
				resultsList: "Result",
				fields: ["Name", "CreatorID", "FullerForm", "Dates", "CreatorUrl"],
				metaFields: {
					status: "Status",
					error: "ErrorMessage"
				}
			};
		} else if (apiVersion == 3) {
			// API v3 uses AUTHORID
			dsBHL = new YAHOO.util.XHRDataSource("https://www.biodiversitylibrary.org/");
			dsBHL.responseSchema = {
				resultsList : "Result",
				fields : ["Name", "AuthorID", "FullerForm", "Dates", "CreatorUrl"],
				metaFields : {
					status : "Status",
					error : "ErrorMessage"
				}
			};
		}
		dsBHL.responseType = YAHOO.util.XHRDataSource.TYPE_JSON;

		var oAC = new YAHOO.widget.AutoComplete("segment_author_last_name", "segment_author_name_autocomplete", dsBHL);
		oAC.generateRequest = function (sQuery) {
			if (sQuery == undefined) {
				sQuery = "";
			}
			// Is the query string numeric?
			if (sQuery.match(/^[0-9]+$/)) {
				// Assume this is an ID number and search by ID .. And we only need API3 to do it
				return 'api3?op=GetAuthorMetadata&apikey=' + apiKey + '&format=json&id=' + sQuery;
			} else {

				var fname = Dom.get('segment_author_first_name');
				var lname = Dom.get('segment_author_last_name');
				if (lname.value && fname.value) {
					sQuery = lname.value + ", " + fname.value;
				} else if (lname.value && !fname.value) {
					sQuery = lname.value;
				} else if (!lname.value && fname.value) {
					sQuery = fname.value;
				}

				// Not purely numeric, use the appropriate version of the API to searcgh
				if (apiVersion == 2) {
					return 'api2/httpquery.ashx?op=AuthorSearch&apikey=' + apiKey + '&format=json&name=' + sQuery;
				} else if (apiVersion == 3) {
					return 'api3?op=AuthorSearch&apikey=' + apiKey + '&format=json&authorname=' + sQuery;
				}
			}
		};
		oAC.animSpeed = 0.1
		oAC.minQueryLength = 2;
		oAC.allowBrowserAutocomplete = false;
		oAC.formatResult = function (oResultData, sQuery, sResultMatch) {
			if (oResultData[3]) {
				return (sResultMatch + " (" + oResultData[3] + ")");
			} else {
				return (sResultMatch)
			}

		};

		oAC.itemSelectEvent.subscribe(function (type, args) {
			// Toggles the external BHL link.
			var link = Dom.get("segment_author_source");
			link.href = args[2][4];
			link.style.visibility = "visible";
			link.innerHTML = "(View on BHL: " + args[2][1] + ")";

			if (args[2][3]) {
				var elStart = Dom.get("segment_author_start_date");
				var elEnd = Dom.get("segment_author_end_date");
				var dates = args[2][3].split('-');
				elStart.value = dates[0];
				elEnd.value = dates[1];
			}

			var elFirst = Dom.get('segment_author_first_name');
			var elLast = Dom.get('segment_author_last_name');
			var name = args[2][0].split(',');
			elLast.value = name[0].trim();
			if (typeof name[1] != 'undefined') { elFirst.value = name[1].trim(); }

		});

		// Make the first name field trigger the search, too.
		var fNameSearch = function() {
			var req = oAC.generateRequest();
			oAC.sendQuery(req);
		}
		YAHOO.util.Event.addListener("segment_author_first_name", "keyup", fNameSearch);

	}

	// Close the author dialog.
	var closeDialog = function() {
		YAHOO.macaw.dlgAuthor.hide();
		YAHOO.macaw.dlgAuthor.destroy();
		this.authorDialogOpen = true;
		
		Event.removeListener(document, "click");
		if (YAHOO.macaw.dlgAuthor.id = "pnlAuthor") {
			YAHOO.macaw.dlgAuthor = null;
		}			
	}

	// Remove an author from the current segment.
	var removeAuthor = function(obj) {
		var segment = SegmentComponent.getCurrentSegment();
		segment.author_list.splice(obj.target.id.match(/\d+/), 1);
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
			var count = 0;
			for (var a in segment.author_list) {
				var author = segment.author_list[a];

				var bhlIcon = document.createElement("span");
				bhlIcon.title = "View this author at BHL";
				bhlIcon.className = "bhl-icon";

				var deleteButton = document.createElement("a");
				deleteButton.title = "Remove this author";
				deleteButton.className = "remove-button";
				deleteButton.id = "authorIndex-"+count;
				Event.addListener(deleteButton, "click", removeAuthor);
				
				var li = document.createElement("li");
				li.appendChild(deleteButton);

				var text = author["last_name"] + (author["first_name"] ? ', ' + author["first_name"] : '') 
				if (author["start_date"] || author["end_date"]) {
					text = text + " (" + author["start_date"] + '-' + author["end_date"]+ ")";
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
				count++;
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
	var pageText = Dom.get("segmentPages");
	var warningText = Dom.get("segmentWarning");
	warningText.style.display = "none";
	pageText.style.display = "none";


	// Decide if there's a mismatch in the selected pages.
	if (segment) {
		// Update the display of segment pages.
		var sequences = buildSegmentSequence(segment.page_list);
		pageText.innerHTML = "Pages: " + sequences.join(", ");
		pageText.style.display = "inline";
		SegmentComponent.updateTable();

		var pages = oBook.pages.arrayHighlighted();

		// Basic check, if there are more or less pages, it's a warning
		if (pages.length != segment.page_list.length) {
			// Show the warning.
			warningText.style.display = "inline";
			return;
		}

		// If the lenghts are the same, we compare the page IDs
		for (var p in pages) {
			if (pages[p].pageID != segment.page_list[p]) {
				// Show the warning.
				warningText.style.display = "inline";
				return;
			}
		}
	} else {
		pageText.innerHTML = '';
	}
}

this.buildSegmentSequence = function(segment_page_ids) {
	pgs = [];
	for (i=0; i<segment_page_ids.length;i++) {
		idx = oBook.pages.find('pageID', segment_page_ids[i])
		pgs.push(oBook.pages.pages[idx]);
	}
	return buildSequence(pgs);
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
Event.onDOMReady(function() {
	SegmentComponent = SegmentComponent();
	AuthorComponent = AuthorComponent();

	onPagesHighlighted.subscribe(function () {
		checkPages();
	});

	onNoPagesHighlighted.subscribe(function () {
		checkPages();
	});

	onBookLoaded.subscribe(function() {
		var callback = {
				success: function(obj) {
					if (JSON.parse(obj.responseText)) {
						segments = JSON.parse(obj.responseText);
					}
					SegmentComponent.updateDropdown();
					SegmentComponent.updateTable();

					var elbtnSave = document.getElementById("btnSave");
					YAHOO.util.Event.addListener(elbtnSave, "click", SegmentComponent.saveSegments);

					var elbtnFinished = document.getElementById("btnFinished");
					YAHOO.util.Event.addListener(elbtnFinished, "click", SegmentComponent.saveSegments);


				},
				failure: function(ob) {
					var y = 10;
				},
				scope: this
		};

		var data = "data=" + JSON.stringify(oBook.itemID); 
		var transaction = YAHOO.util.Connect.asyncRequest("GET", sBaseUrl + '/bhl_segments/load_segments/' + oBook.itemID, callback);
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
	this.unrender = function() {
		// When we have no pages selexted, make sure the author
		// search dialog doesn't remain open.
		if (AuthorComponent.authorDialogOpen) {
			AuthorComponent.closeDialog();
		};
	}
}
