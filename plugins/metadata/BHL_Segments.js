// ------------------------------
// CUSTOM METADATA OBJECT
//
// This represents the custom metadata for a single page in our book. This module is
// tricky in that it's purely custom and Macaw can operate without it. A copy of this
// object is created for each page in the book, but there are a few function that are
// run globally. This is tricky business. You'd better know your javascript to use this.
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
	this.pageID = this.parent.parent.pageID;
	
	this.AuthorComponent = null;
	this.IdentifierComponent = null;
	this.KeywordComponent = null;
	
	// These correspond exactly to the id attributes in the php file
	// The "Type" specifier gives clues to render() and unrender() about
	// how to handle different types of fields.
	YAHOO.macaw.BHL_Segments.metadataFields = [
		{ id: 'segment_title', display_name: 'Title', type: 'text'},
		{ id: 'segment_genre', display_name: 'Genre', type: 'select-one'},
		{ id: 'segment_doi', display_name: 'DOI', type: 'text'},
		{ id: 'segment_external_url', display_name: 'External URL', type: 'text'},
		{ id: 'segment_download_url', display_name: 'Download URL', type: 'text'},
		{ id: 'segment_authors', display_name: 'Authors', type: 'list'},
		{ id: 'segment_identifiers', display_name: 'Identifiers', type: 'list'},
		{ id: 'segment_keywords', display_name: 'Keywords', type: 'list'}
	];
	
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
		var fields = YAHOO.macaw.BHL_Segments.metadataFields;
		for (f in fields) {
			cols.push({
				'key':   fields[f].id,
				'label': fields[f].display_name
			});
		}
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
		// Copy the data from the data object into ourself.
		var fields = YAHOO.macaw.BHL_Segments.metadataFields;
		for (f in fields) {
			if (typeof this.data[fields[f].id] != 'undefined') {
				this[fields[f].id] = this.data[fields[f].id];
			} else {
				this[fields[f].id] = null;
			}
		}
		this.sequence = this.data.sequence_number;
		this.filebase = this.data.filebase;
		
		this.AuthorComponent = new this.AuthorComponent();
		this.IdentifierComponent = new this.IdentifierComponent();
		this.KeywordComponent = new this.KeywordComponent();

		// This is really special. We DO NOT want to call this more than once.
		// So we set our own variable onto the oBook because we can
		// (and which is more or less global, as far as we are concerned)
		if (!oBook.initialized_BHL_Segments) {
			// Enter stuff here that should happen exactly once when the page is loaded.
			
			oBook.initialized_BHL_Segments = true;
			
			oBook.obtnShowAuthorDlg = new Elem('btnShowAuthorDlg');
			oBook.obtnShowAuthorDlg.on("click", this.AuthorComponent.showDialog, null, oBook);
			
			oBook.obtnClearAuthorType = new Elem('btnClearAuthorType');
			oBook.obtnClearAuthorType.on("click", this.AuthorComponent.removeAllAuthors, null, oBook);
			
			oBook.obtnShowIdentifierDlg = new Elem('btnShowIdentifierDlg');
			oBook.obtnShowIdentifierDlg.on("click", this.IdentifierComponent.showDialog, null, oBook);
			
			oBook.obtnClearIdentifierType = new Elem('btnClearIdentifierType');
			oBook.obtnClearIdentifierType.on("click", this.IdentifierComponent.removeAllIdentifiers, null, oBook);

			oBook.obtnShowKeywordDlg = new Elem('btnShowKeywordDlg');
			oBook.obtnShowKeywordDlg.on("click", this.KeywordComponent.showDialog, null, oBook);
			
			oBook.obtnClearKeywordType = new Elem('btnClearKeywordType');
			oBook.obtnClearKeywordType.on("click", this.KeywordComponent.removeAllKeywords, null, oBook);
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
		var fields = YAHOO.macaw.BHL_Segments.metadataFields;
		for (f in fields) {
			data[fields[f].id] = this[fields[f].id];
		}
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
		
		var fields = YAHOO.macaw.BHL_Segments.metadataFields;
		for (f in fields) {
			if (fields[f].type == 'long-text') {
				if (this[fields[f].id] != '' && this[fields[f].id] != null) {
					data[fields[f].id] = '<em>(text)</em>';
				}
			} else if (fields[f].type == 'list') {
				if (this[fields[f].id]){
					var list = this[fields[f].id];
					data[fields[f].id] = [];
					
					if (!Array.isArray(list)){
						list = [list];
					}
					
					list.forEach(function(item) {
						// Attempts to parse the object as JSON.
						try {
							item = JSON.parse(item);
						} catch (err) {}
						
						if (item) {
							switch(fields[f].id) {
								case 'segment_authors':
									var text = item['segment_author_name'];
									data[fields[f].id].push(text);
									break;
								case 'segment_identifiers':
									// Defaults the type to 'Undefined' in case of bad data.
									var text = 'Undefined';
									var el = Dom.get('segment_identifier_type')
									for (var i = 0; i < el.options.length; i++) {
										if (el.options[i].value == item['segment_identifier_type']) {
											// Gets the descriptive text for the identifier type.
											text = el.options[i].text;
											break;
										}
									}
									text = text + ': ' + item['segment_identifier_value'];			
									data[fields[f].id].push(text);
									break;
								case 'segment_keywords':
									data[fields[f].id].push(item);
									break;
							}
						}
					});
					data[fields[f].id] = data[fields[f].id].join('; ');
				}
			} else {
				data[fields[f].id] = this[fields[f].id];
			}
		}
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
	this.set = function(field, value, mult) {
		if (field == 'segment_authors' || field == 'segment_identifiers' || field == 'segment_keywords'){
			if (!this[field]){
				this[field] = [];
			} else if (!Array.isArray(this[field])) {
				this[field] = [this[field]];
			}
			this[field].push(value);
		} else if (!mult || (mult && !isBlank(value))) {
			this[field] = value;
		}
	}

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
	this.render = function() {
		var fields = YAHOO.macaw.BHL_Segments.metadataFields;
		for (f in fields) {
			// Text boxes and Textareas are easy
			if (fields[f].type == 'text' || fields[f].type == 'long-text') {
				Dom.get(fields[f].id).value = this[fields[f].id]

			// Select boxes are more difficult, but select-one is pretty simple.
			} else if (fields[f].type == 'select-one') {
				var el = document.getElementById(fields[f].id);
				el.selectedIndex = -1;
				if (this[fields[f].id] && this[fields[f].id] != null && typeof(this[fields[f].id]) != undefined) {
					for (i=0; i < el.options.length; i++) {
						if (el.options[i].value == this[fields[f].id]) {
							el.selectedIndex = i;
							break;
						}
					}
				}
			} else if (fields[f].type == 'list') {
				if (this[fields[f].id]){
					var list = this[fields[f].id];
					
					// Ensures that the list is actually an array.
					if (!Array.isArray(list)){
						list = [list];
					}
					
					// Loops through the items and adds them to the HTML lists.
					// NOTE: We cannot use a forEach here due to scope issues.
					for (i = 0; i < list.length; i++) {
						switch(fields[f].id) {
							case 'segment_authors':
								this.AuthorComponent.addAuthor(list[i]);
								break;
							case 'segment_identifiers':
								this.IdentifierComponent.addIdentifier(list[i]);
								break;
							case 'segment_keywords':
								this.KeywordComponent.addKeyword(list[i]);
								break;
						}
					}
				}
			}
		}
	}

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
	this.renderMultiple = function() {
		// For this demo, this can be left empty (but the function definition is required)
	}

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
		var fields = YAHOO.macaw.BHL_Segments.metadataFields;
		for (f in fields) {
			if (fields[f].type == 'text' || fields[f].type == 'long-text') {
				Dom.get(fields[f].id).value = '';
			} else if(fields[f].type == 'select-one') {
				Dom.get(fields[f].id).selectedIndex = -1;
			} else if(fields[f].type == 'list') {
				var el = Dom.get(fields[f].id);
				el.innerHTML = '';
			}
		}
	}
	
	// ----------------------------
	// Function: AuthorComponent()
	//
	// A closure that contains all functionality related to the Author section.
	//
	// Arguments
	//     None
	// ----------------------------
	this.AuthorComponent = function() {
		var fields = [
			'segment_author_name',
			'segment_author_identifier_type', 
			'segment_author_identifier_value',
			'segment_author_dates',
			'segment_author_source'];

		// Displays the Add Author dialog.
		var showDialog = function() {

			var pgs = oBook.pages.arrayHighlighted();
			if (pgs.length == 0) {
				alert('Please select one or more pages before adding an author.');
				return;
			}
			
			var myButtons = [
				{ text: "Add", handler: function() {
					var multiple = (pgs.length > 1);
					var json = {};
					fields.forEach(function(e) {
						var el = Dom.get(e);
						if (el) {
							if (e == 'segment_author_source' && el.href !== undefined) {
								// Special handling for the hyperlink.
								var value = el.href.match(/\d+$/);
								if (value) {
									json[e] = value[0];
								}
							} else {
								// Everything else.
								json[e] = el.value;
							}
						}
					});
					
					json = JSON.stringify(json);
					addAuthor(json);
					
					pgs.forEach(function(e) {
						e.metadata.callFunction('set', 'segment_authors', json, multiple);
					});
					
					oBook._updateDataTableRecordset();	
					closeDialog();
				}, isDefault: true },
				{ text: "Cancel", handler: closeDialog }
			];
			
			YAHOO.macaw.dlgAuthor = new YAHOO.widget.SimpleDialog("pnlAuthor", {
				visible: false, draggable: false, close: false, underlay: "none",
				Y: parseInt(Dom.getY('btnShowAuthorDlg') - 200),
				X: parseInt(Dom.getX('btnShowAuthorDlg')),
				buttons: myButtons
			} );
			
			YAHOO.macaw.dlgAuthor.setHeader('Add an author');
			YAHOO.macaw.dlgAuthor.setBody(Dom.get('dlgAuthor').innerHTML);
			YAHOO.macaw.dlgAuthor.render("tdAuthor");
			YAHOO.macaw.dlgAuthor.show();
		
			// Functionality for the YUI2 AutoComplete control. 
			var request = function() {
				
				// Empty data source to initialize the widget. 
				var oDS = [];
				var oAC = new YAHOO.widget.AutoComplete("segment_author_name", "segment_author_name_autocomplete", new YAHOO.util.LocalDataSource(oDS));
				
				// Populates the data source whenever data is requested by the widget.
				oAC.dataRequestEvent.subscribe(function (type, args) {
					var url = "https://www.biodiversitylibrary.org/api2/httpquery.ashx?op=AuthorSearch&apikey=d230a327-c544-4f8f-826d-727cf4da24b8&format=json&name=" + args[1];
					var response = function() {
						var http = new XMLHttpRequest();
						http.onreadystatechange = function() {
							if (http.readyState == 4 && http.status == 200) {
								var data = JSON.parse(http.responseText);
								oDS = new YAHOO.util.LocalDataSource(data['Result']);
								oDS.responseSchema = {fields: ["Name", "CreatorID", "FullerForm", "Dates", "CreatorUrl"]};
								args[0].dataSource = oDS;
							}
						}
						http.open("GET", url, true);
						http.send();
					}();
				});
				oAC.itemSelectEvent.subscribe(function (type, args) {
					// Toggles the external BHL link.
					var link = Dom.get('segment_author_source');
					link.href = args[2][4];
					link.style.visibility = "visible";
					link.innerHTML = "(View on BHL: " + args[2][1] + ")";
					
					var el = Dom.get('segment_author_dates');
					el.value = args[2][3];
					
					var el = ['segment_author_identifier_type', 'segment_author_identifier_value', 'segment_author_dates'];
					el.forEach(function(e) {
						e = Dom.get(e);
						e.disabled = true;
						e.value = '';
					});
				});
				
				oAC.textboxKeyEvent.subscribe(function (type, args) {
					var link = Dom.get('segment_author_source');
					link.href = "";
					link.style.visibility = "hidden";
					link.innerHTML = "";
										
					var el = ['segment_author_identifier_type', 'segment_author_identifier_value', 'segment_author_dates'];
					el.forEach(function(e) {
						e = Dom.get(e);
						e.disabled = false;
						e.value = '';
					});
				});
				return {
					oDS: oDS,
					oAC: oAC
				};
			}();
			
			// Removes the auto-added YUI2 CSS.
			Dom.get('segment_author_name').parentElement.classList.remove('yui-ac');
			Dom.get('segment_author_name').classList.remove('yui-ac-input');
		}

		// Hides the dialog, removes it from memory, and removes the click event listener.
		var closeDialog = function() {
			try {
				YAHOO.macaw.dlgAuthor.hide();
				YAHOO.macaw.dlgAuthor.destroy();
				
				Event.removeListener(document, 'click');
				if (YAHOO.macaw.dlgAuthor.id = 'pnlAuthor') {
					YAHOO.macaw.dlgAuthor = null;
				}
			} catch (err) {}	
		}	
		
		// Adds the author to the HTML list.
		var addAuthor = function(author) {
			author = JSON.parse(author);
			
			// Formats the list item.
			var text = author['segment_author_name'];
			if (author['segment_author_dates']) {
				text = text + " (" + author['segment_author_dates'] + ")";
			}
				
			var ul = Dom.get('segment_authors');
			var li = document.createElement("li");
			var del_button = document.createElement('a');
			
			var id = ul.children.length + 1;
			
			del_button.className = 'remove-button';
			Event.addListener(del_button, "click", removeAuthor);
			
			li.id = 'author' + id;
			li.appendChild(del_button);
			
			// Creates a hyperlink if a CreatorID is present.
			if (author['segment_author_source']) {
				var a = document.createElement('a');
				a.href = 'http://www.biodiversitylibrary.org/creator/' + author['segment_author_source'];
				a.target = '_blank';
				a.innerHTML = text;
				li.appendChild(a);
			} else {
				li.appendChild(document.createTextNode(text));
			}
			ul.appendChild(li);
		}
		
		// Removes the author from the list.
		var removeAuthor = function(e) {
			var li = this.parentElement,
				pgs = oBook.pages.arrayHighlighted();
			
			for (var p in pgs) {
				for (var m in pgs[p].metadata.modules) {
					if (pgs[p].metadata.modules[m].name == 'BHL_Segments'){
						var index = li.id.match(/\d+$/)[0];
						
						// Checks if the field is an array or not and handles it accordingly.
						// If it is, the index is nulled out. If not, the entire field is nulled out.
						// We do it this way so the other indexes stay the same until the next load.
						if (Array.isArray(pgs[p].metadata.modules[m].segment_authors)) {
							pgs[p].metadata.modules[m].segment_authors[index - 1] = null;
						} else {
							pgs[p].metadata.modules[m].segment_authors = null;
						}
					}
				}
			}
			
			// Removes the list item from the HTML list.
			li.parentNode.removeChild(li);
			oBook._updateDataTableRecordset();
		}
		
		// Removes all authors from the list.
		var removeAllAuthors = function() {
			var pgs = pgs = oBook.pages.arrayHighlighted();
			var multiple = (pgs.length > 1);
			
			if (multiple) {
				if (!confirm('Are you sure you want to clear the authors for '+pg.length+' items?')) {
					return;
				}
			}
			
			// Sets the array to null.
			pgs.forEach(function(pg) {
				pg.metadata.modules.forEach(function(m) {
					if (m.name == 'BHL_Segments') {
						m.segment_authors = null;
					}
				});
			});
			
			// Removes all items from the HTML list.
			var el = Dom.get('segment_authors');
			el.innerHTML = '';
			oBook._updateDataTableRecordset();
		}
		
		return {
			showDialog : showDialog,
			addAuthor : addAuthor,
			removeAuthor : removeAuthor,
			removeAllAuthors : removeAllAuthors
		}
	}

	this.IdentifierComponent = function() {
		var fields = [
			'segment_identifier_type', 
			'segment_identifier_value'];

		// Displays the Add Identifier dialog.
		var showDialog = function() {
			var pgs = oBook.pages.arrayHighlighted();
			if (pgs.length == 0) {
				alert('Please select one or more pages before adding an identifier.');
				return;
			}
			
			var myButtons = [
				{ text: "Add", handler: function() {
					var multiple = (pgs.length > 1);
					var json = {};
					fields.forEach(function(e) {
						var value = Dom.get(e);						
						if (value) { json[e] = value.value; }
					});
					
					json = JSON.stringify(json);
					addIdentifier(json);
					
					pgs.forEach(function(e) {
						e.metadata.callFunction('set', 'segment_identifiers', json, multiple);
					});
					oBook._updateDataTableRecordset();	
					closeDialog();
				}, isDefault: true },
				{ text: "Cancel", handler: closeDialog }
			];
			
			YAHOO.macaw.dlgIdentifier = new YAHOO.widget.SimpleDialog("pnlIdentifier", {
				visible: false, draggable: false, close: false, underlay: "none",
				Y: parseInt(Dom.getY('btnShowIdentifierDlg') - 200),
				X: parseInt(Dom.getX('btnShowIdentifierDlg')),
				buttons: myButtons
			} );
			YAHOO.macaw.dlgIdentifier.setHeader('Add an identifier');
			YAHOO.macaw.dlgIdentifier.setBody(Dom.get('dlgIdentifier').innerHTML);
			YAHOO.macaw.dlgIdentifier.render("tdIdentifier");
			YAHOO.macaw.dlgIdentifier.show();
		}

		// Hides the dialog, removes it from memory, and removes the click event listener.
		var closeDialog = function() {
			try {
				YAHOO.macaw.dlgIdentifier.hide();
				YAHOO.macaw.dlgIdentifier.destroy();
				
				Event.removeListener(document, 'click');
				if (YAHOO.macaw.dlgIdentifier.id = 'pnlIdentifier') {
					YAHOO.macaw.dlgIdentifier = null;
				}
			} catch (err) {}	
		}		
		
		// Adds the author to the HTML list.
		var addIdentifier = function(identifier) {
			identifier = JSON.parse(identifier);
			
			// Formats the list item.
			// Defaults the type to 'Undefined' in case of bad data.
			var text = 'Undefined';
			var el = Dom.get('segment_identifier_type')
			for (var i = 0; i < el.options.length; i++) {
				if (el.options[i].value == identifier['segment_identifier_type']) {
					// Gets the descriptive text for the identifier type.
					text = el.options[i].text;
					break;
				}
			}
			text = text + ': ' + identifier['segment_identifier_value'];
			
			var ul = Dom.get('segment_identifiers');
			var li = document.createElement("li");
			var del_button = document.createElement('a');
			
			var id = ul.children.length + 1;
			
			del_button.className = 'remove-button';
			Event.addListener(del_button, "click", removeIdentifier);
			
			li.id = 'keyword' + id;
			li.appendChild(del_button);
			li.appendChild(document.createTextNode(text));
			ul.appendChild(li);
		}
		
		// Removes the author from the list.
		var removeIdentifier = function(e) {
			var li = this.parentElement,
				pgs = oBook.pages.arrayHighlighted();
			
			for (var p in pgs) {
				for (var m in pgs[p].metadata.modules) {
					if (pgs[p].metadata.modules[m].name == 'BHL_Segments'){
						var index = li.id.match(/\d+$/)[0];
						
						// Checks if the field is an array or not and handles it accordingly.
						// If it is, the index is nulled out. If not, the entire field is nulled out.
						// We do it this way so the other indexes stay the same until the next load.
						if (Array.isArray(pgs[p].metadata.modules[m].segment_identifiers)) {
							pgs[p].metadata.modules[m].segment_identifiers[index - 1] = null;
						} else {
							pgs[p].metadata.modules[m].segment_identifiers = null;
						}
					}
				}
			}
			
			// Removes the list item from the HTML list.
			li.parentNode.removeChild(li);
			oBook._updateDataTableRecordset();
		}
		
		// Removes all authors from the list.
		var removeAllIdentifiers = function() {
			var pgs = pgs = oBook.pages.arrayHighlighted();
			var multiple = (pgs.length > 1);
			
			if (multiple) {
				if (!confirm('Are you sure you want to clear the authors for ' + pgs.length + ' items?')) {
					return;
				}
			}
			
			// Sets the array to null.
			pgs.forEach(function(pg) {
				pg.metadata.modules.forEach(function(m) {
					if (m.name == 'BHL_Segments') {
						m.segment_identifiers = null;
					}
				});
			});
			
			// Removes all items from the HTML list.
			var el = Dom.get('segment_identifiers');
			el.innerHTML = '';
			oBook._updateDataTableRecordset();
		}
		
		return {
			showDialog : showDialog,
			addIdentifier : addIdentifier,
			removeIdentifier : removeIdentifier,
			removeAllIdentifiers : removeAllIdentifiers
		}
	}

	this.KeywordComponent = function() {
		
		// Displays the Add Keyword dialog.
		var showDialog = function() {
			var pgs = oBook.pages.arrayHighlighted();
			if (pgs.length == 0) {
				alert('Please select one or more pages before adding a keyword.');
				return;
			}
			
			var myButtons = [
				{ text: "Add", handler: function() {
					var multiple = (pgs.length > 1);
						
					var value = Dom.get('segment_keyword');
					value = keyword = value.value;
					
					addKeyword(value);
					
					pgs.forEach(function(e) {
						e.metadata.callFunction('set', 'segment_keywords', value, multiple);
					});
					oBook._updateDataTableRecordset();	
					closeDialog();
				}, isDefault: true },
				{ text: "Cancel", handler: closeDialog }
			];
			
			YAHOO.macaw.dlgKeyword = new YAHOO.widget.SimpleDialog("pnlKeyword", {
				visible: false, draggable: false, close: false, underlay: "none",
				Y: parseInt(Dom.getY('btnShowIdentifierDlg') - 200),
				X: parseInt(Dom.getX('btnShowIdentifierDlg')),
				buttons: myButtons
			} );
			YAHOO.macaw.dlgKeyword.setHeader('Add a keyword');
			YAHOO.macaw.dlgKeyword.setBody(Dom.get('dlgKeyword').innerHTML);
			YAHOO.macaw.dlgKeyword.render("tdKeyword");
			YAHOO.macaw.dlgKeyword.show();
		}

		// Hides the dialog, removes it from memory, and removes the click event listener.
		var closeDialog = function() {
			try {
				YAHOO.macaw.dlgKeyword.hide();
				YAHOO.macaw.dlgKeyword.destroy();
				
				Event.removeListener(document, 'click');
				if (YAHOO.macaw.dlgKeyword.id = 'pnlKeyword') {
					YAHOO.macaw.dlgKeyword = null;
				}
			} catch (err) {}	
		}		
		
		// Adds the author to the HTML list.
		var addKeyword = function(keyword) {
			if (!keyword || keyword == '' || keyword == null) {
				return;
			}
			var ul = Dom.get('segment_keywords');
			var li = document.createElement("li");
			var del_button = document.createElement('a');
			
			del_button.className = 'remove-button';
			Event.addListener(del_button, "click", removeKeyword);
			
			var id = ul.children.length + 1;
			
			li.id = 'keyword' + id;
			li.appendChild(del_button);
			li.appendChild(document.createTextNode(keyword));
			ul.appendChild(li);
		}
		
		// Removes the keyword from the list.
		var removeKeyword = function(e) {
			var li = this.parentElement,
				pgs = oBook.pages.arrayHighlighted();
			
			for (var p in pgs) {
				for (var m in pgs[p].metadata.modules) {
					if (pgs[p].metadata.modules[m].name == 'BHL_Segments'){
						var index = li.id.match(/\d+$/)[0];
						
						// Checks if the field is an array or not and handles it accordingly.
						// If it is, the index is nulled out. If not, the entire field is nulled out.
						// We do it this way so the other indexes stay the same until the next load.
						if (Array.isArray(pgs[p].metadata.modules[m].segment_keywords)) {
							pgs[p].metadata.modules[m].segment_keywords[index - 1] = null;
						} else {
							pgs[p].metadata.modules[m].segment_keywords = null;
						}
					}
				}
			}
			
			// Removes the list item from the HTML list.
			li.parentNode.removeChild(li);
			oBook._updateDataTableRecordset();
		}
		
		// Removes all authors from the list.
		var removeAllKeywords = function() {
			var pgs = pgs = oBook.pages.arrayHighlighted();
			var multiple = (pgs.length > 1);
			
			if (multiple) {
				if (!confirm('Are you sure you want to clear the authors for ' + pgs.length + ' items?')) {
					return;
				}
			}
			
			// Sets the array to null.
			pgs.forEach(function(pg) {
				pg.metadata.modules.forEach(function(m) {
					if (m.name == 'BHL_Segments') {
						m.segment_keywords = null;
					}
				});
			});
			
			// Removes all items from the HTML list.
			var el = Dom.get('segment_keywords');
			el.innerHTML = '';
			oBook._updateDataTableRecordset();
		}
		
		return {
			showDialog : showDialog,
			addKeyword : addKeyword,
			removeKeyword : removeKeyword,
			removeAllKeywords : removeAllKeywords
		}		
	}

}

/* ****************************************** */
/* CLASS METHODS                              */
/* These exist apart from individual pages    */
/* ****************************************** */

// ----------------------------
// Function: metadataChange()
//
// Called when any of the metadata elements are changed in the form. Because this is called from
// the metadata form AND because one or more pages may be selected, this function when called does
// not know which pages are selected.

// objects.
//
// Arguments
//    obj - The object that triggered the change event.
//
// Return Value / Effect
//    The data is set into the proper page object(s)
//
// NEEDS MODIFICATIONS: YES
//
// ----------------------------
YAHOO.macaw.BHL_Segments.metadataChange = function(obj) {
	// Get the things that are selected
	var pg = oBook.pages.arrayHighlighted();
	
	// All of these REPLACE the data that's on the object.
	// Only the Page Type and Pieces will APPEND.
	var i;
	
	// Set an array to accumulate any pageids we modify
	var page_ids = new Array();
	var multiple = (pg.length > 1);
	var fields = YAHOO.macaw.BHL_Segments.metadataFields;

	for (var i in pg) {
		for (f in fields) {
			if (obj.id == fields[f].id) {
				// This works for both text boxes and textareas
				pg[i].metadata.callFunction('set', fields[f].id, obj.value, multiple);
			}
		}
		// Collect the pageids we modify
		page_ids.push(pg[i].pageID);
	}

	// TODO: BIG NOTE. This is terribly convoluted. I think we could eliminate the convoluted-ness of
	// this IF the metadata object's "metadataModules" array was changed into an object. Then we could
	// simply call
	//
	//     pg[i].metadata.modules.SAMPLE.set(fields[f].id, obj.value);
	//
	// We CANNOT do this:  this.set(fields[f].id, obj.value)   (The "this" object refers to something else entirely)
	// We CANNOT do this:  this[fields[f].id] = obj.value      (The "this" object refers to something else entirely)
	// We CANNOT do this:  pg[i].set(fields[f].id, obj.value)

	// Log all the pages that were modified at once to not spam the server
	if (obj.id != 'metadata_form') {
		if (!multiple || (multiple && obj.value)) {
			Scanning.log(page_ids.join('|'), obj.id, obj.value);
		}
	}
	oBook._updateDataTableRecordset();
}