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

YAHOO.macaw.Standard_Metadata = function(parent, data) {

	// Intialize the fields that will hold our metadata
	this.data = data;
	this.sequence = null;
	this.pagePrefix = null;
	this.pageNumber = '';
	this.pageNumberImplicit = null;
	this.pageTypes = new Array();
	this.year = null;
	this.volume = null;
	this.notes = null;
	this.pageSide = null;
	this.pieces = new Array();
	this.parent = parent;
	this.pageID = this.parent.parent.pageID;

	// This is the part of the filename for the image. I think we use this....
	this.filebase = null;

	// We default to unrendered and clean.
	this.rendered = false;

	// ----------------------------
	// Function: getTableColumns()
	//
	// Returns a description of the data columns used for setting up a YUI data table.
	// This does not return any data. This may also create functions internally to the
	// method which can be referred to in the array of objects returned. See the YUI
	// Data Table documentation on waht this should return.
	//
	// Arguments
	//     None
	//
	// Return Value / Effect
	//     An array of object suitable for YUI Data Table ColumnDefs
	// ----------------------------
	this.getTableColumns = function() {
		// Specific to the YUI datatable formatting function for datatable
		var formatPage = function(elCell, oRecord, oColumn, oData) {
			var p = oRecord.getData('page_prefix');
			var n = oRecord.getData('page_number');
			var i = oRecord.getData('page_implicit');
			if (!isBlank(p) && !isBlank(n)) {
				elCell.innerHTML = p + '&nbsp;' + n + (i ? '&nbsp;<em>impl.</em>' : '');
			} else if (isBlank(p) && !isBlank(n)) {
				elCell.innerHTML = n + (i ? '&nbsp;<em>impl.</em>' : '');
			} else if (!isBlank(p) && isBlank(n)) {
				elCell.innerHTML = p + (i ? '&nbsp;<em>impl.</em>' : '');
			} else {
				elCell.innerHTML = '';
			}
		}
		// Specific to the YUI datatable formatting function for datatable
		var formatExtra = function(elCell, oRecord, oColumn, oData) {
			var c = '';
			var n = oRecord.getData('notes');
			var f = oRecord.getData('flag');
			if (!isBlank(n)) {
				c += '<img src="'+sBaseUrl+'/images/icons/page.png" height="16" width="16" border="0">'
			}
			if (f) {
				c += ' <img src="'+sBaseUrl+'/images/icons/flag_blue.png" height="16" width="16" border="0">'
			}

			elCell.innerHTML = c;
		}

		return [
			{key:"page_number", label:'Page', formatter:formatPage},
			{key:"page_type",   label:'Type'  },
			{key:"year",        label:'Year'  },
			{key:"volume",      label:'Vol.'  },
			{key:"piece",       label:'Piece' },
			{key:"page_side",   label:'Side'  },
			{key:"extra",       label:'',     formatter:formatExtra}
		];
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
		// Set the stuff from the data parameter into properties on us
		if (this.data.sequence_number)      this.sequence = this.data.sequence_number;
		if (this.data.page_prefix)          this.pagePrefix = this.data.page_prefix;
		if (this.data.page_number)          this.pageNumber = this.data.page_number;
		if (this.data.page_number_implicit) this.pageNumberImplicit = this.data.page_number_implicit;
		if (this.data.year)                 this.year = this.data.year;
		if (this.data.volume)               this.volume = this.data.volume;
		if (this.data.notes)                this.notes = this.data.notes;
		if (this.data.page_side)            this.pageSide = this.data.page_side;
		if (this.data.filebase)             this.filebase = this.data.filebase;

		// Set the page_type information into an array that will contain
		// the page type as well as the ID of the item on the page.
			if (typeof(this.data.page_type) == 'string') {
			this.pageTypes.push( {
				"type": this.data.page_type,
				"id" : null
			} );
		} else {
			for (var i in this.data.page_type) {
				this.pageTypes.push( {
					"type": this.data.page_type[i],
					"id" : null
				} );
			}
		}

		// Set the page_type information into an array that will contain
		// the page type as well as the ID of the item on the page.
			if (typeof(this.data.piece) == 'string') {
				this.pieces.push( {
					"type": this.data.piece,
					"text": this.data.piece_text,
					"id" : null
				} );
		} else {
			for (var i in this.data.piece) {
				this.pieces.push( {
					"type": this.data.piece[i],
					"text": this.data.piece_text[i],
					"id" : null
				} );
			}
		}

		// Set up handling for the buttons and fields on our metadata fields.

		// This is really special. We DO NOT want to call this more than once.
		// So we set our own variable onto the oBook because we can
		// (and which is more or less global, as far as we are concerned)
		if (!oBook.initialized_Standard_Metadata) {
			oBook.obtnShowPagesDlg = new Elem('btnShowPagesDlg');
			oBook.obtnShowPagesDlg.on("click", this.showPagesDialog, null, oBook);

			oBook.obtnShowAddPageTypeDlg = new Elem('btnShowAddPageTypeDlg');
			oBook.obtnShowAddPageTypeDlg.on("click", this.showAddPageTypeDialog, null, oBook);

			oBook.obtnShowAddPieceDlg = new Elem('btnShowAddPieceDlg');
			oBook.obtnShowAddPieceDlg.on("click", this.showAddPieceDialog, null, oBook);

			oBook.obtnClearYear = new Elem('btnClearYear');
			oBook.obtnClearYear.on("click", this.clearYear, null, oBook);

			oBook.obtnClearVolume = new Elem('btnClearVolume');
			oBook.obtnClearVolume.on("click", this.clearVolume, null, oBook);

			oBook.obtnClearPageSide = new Elem('btnClearPageSide');
			oBook.obtnClearPageSide.on("click", this.clearPageSide, null, oBook);

			oBook.obtnClearPageNumber = new Elem('btnClearPageNumber');
			oBook.obtnClearPageNumber.on("click", this.clearPageNumber, null, oBook);

			oBook.obtnClearPiece = new Elem('btnClearPiece');
			oBook.obtnClearPiece.on("click", this.clearPiece, null, oBook);

			oBook.obtnShowAddPieceDlg = new Elem('btnClearPageType');
			oBook.obtnShowAddPieceDlg.on("click", this.clearPageType, null, oBook);

			oBook.initialized_Standard_Metadata = true;
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
		var data = new Object();

		var pt = new Array();
		for (var p in this.pageTypes) {
			pt.push(this.pageTypes[p].type);
		}

		var pc = new Array();
		var pct = new Array();
		for (var p in this.pieces) {
			pc.push(this.pieces[p].type);
			pct.push(this.pieces[p].text);
		}

		data['page_prefix'] = this.pagePrefix;
		data['page_number'] = this.pageNumber;
		data['page_number_implicit'] = this.pageNumberImplicit;
		data['page_type'] = pt;
		data['year'] = this.year;
		data['volume'] = this.volume;
		data['notes'] = this.notes;
		data['piece'] = pc;
		data['piece_text'] = pct;
		data['page_side'] = this.pageSide;

		return data;
	}

	// ----------------------------
	// Function: toDataRow
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
		var pt = new Array();
		for (var i in this.pageTypes) {
			pt.push(this.pageTypes[i].type);
		}
		var pc = new Array();
		for (var i in this.pieces) {
			pc.push(this.pieces[i].type+' '+this.pieces[i].text);
		}

		return {
			page_prefix:   this.pagePrefix,
			page_number:   this.pageNumber,
			page_implicit: this.pageNumberImplicit,
			page_type:     pt.join(', '),
			year:          this.year,
			volume:        this.volume,
			piece:         pc.join(', '),
			page_side:     this.pageSide,
			flag:          this.flagFutureReview,
			notes:         this.notes
		}
	}

	// ----------------------------
	// Function: addPageTypeInternal()
	//
	// Adds a Page Type, both appending it to the pieces[] array and
	// and to the screen. The object passed in is assumed to have a ".value" property.
	//
	// Arguments
	//    obj - The thing on the page that caused this method to be called.
	//
	// Return Value / Effect
	//    Item is added to the metadata array and to the screen
	// ----------------------------
	this.addPageType = function (val) {
		// Make sure we aren't duplicating a page type
		var found = false;
		for (var p in this.pageTypes) {
			if (this.pageTypes[p] == val) {
				found = true;
				break;
			}
		}

		if (!found) {
			// Add the new element to the page
			var el = this._createMetadataTypeElement('page_types', val);
			if (el) Dom.get('page_types').appendChild(el);
			// Add the item to the array
			this.pageTypes.push({ "type": val, "id": el.id });
			YAHOO.macaw.Standard_Metadata.closeAddPagePieceDialog(YAHOO.macaw.dlgPageType);
		}
	}

	// ----------------------------
	// Function: addPiece()
	//
	// Adds a Piece, both appending it to the pieces[] array and
	// and to the screen.
	//
	// Arguments
	//    keycode - The key that was pressed in the box. We only respond to Enter (13).
	//
	// Return Value / Effect
	//    Item is added to the metadata array and to the screen
	// ----------------------------
	this.addPiece = function (type, value) {
		// Make sure we aren't duplicating a piece type
		var found = false;
		for (var p in this.pieces) {
			if (this.pieces[p].type == type) {
				found = true;
				break;
			}
		}

		if (!found) {
			// Add the new element to the page
			var el = this._createMetadataTypeElement('pieces', type+' '+value)
			if (el) Dom.get('pieces').appendChild(el);
			// Add the item to the array
			this.pieces.push({ "type": type, "text": value, "id": el.id });
		}
	}

	// ----------------------------
	// Function: removePageType()
	//
	// Removes a page type from the metadata and from the screen. Called from the
	// "X" button next to the Page Type. Resizes the window just in case the height
	// of the metadata changed.
	//
	// Arguments
	//    e - The YUI Event
	//    obj - The element associated to the event
	//
	// Return Value / Effect
	//    The item is removed from the array and the page
	// ----------------------------
	this.removePageType = function(e, obj) {
		var id = obj.id;
		for (var i in this.pageTypes) {
			if (this.pageTypes[i].id == id) {
				// Remove the element from the array
				type_removed = this.pageTypes[i].type
				Scanning.log(this.pageID, 'DELETE_page_type', type_removed);
				this.pageTypes.splice(i, 1);
				this._unrenderOneMetadataType('page_types',id);
				Scanning.resizeWindow();
				this.parent.changed.fire(type_removed);
			}
		}
		oBook._updateDataTableRecordset();
	}

	// ----------------------------
	// Function: removeAllPageTypes()
	//
	// Unconditionally removes all page types from the metadata. Resizes the
	// window just in case the height of the metadata changed.
	//
	// Arguments
	//    mult - Whether or not we have multiple pages selected.
	//
	// Return Value / Effect
	//    all pages types gone from the screen and from memory
	// ----------------------------
	this.removeAllPageTypes = function(mult) {
		if (!mult) {
			for (var i in this.pageTypes) {
				try {
					this._unrenderOneMetadataType('page_types', this.pageTypes[i].id);
				} catch (err) {}
			}
		}
		this.pageTypes = null;
		this.pageTypes = new Array();
		Scanning.resizeWindow();
	}

	// ----------------------------
	// Function: removePiece()
	//
	// Removes a piece from the metadata and from the screen. Called from the
	// "X" button next to the Piece. Resizes the window just in case the height
	// of the metadata changed.
	//
	// Arguments
	//    e - The YUI Event
	//    obj - The element associated to the event
	//
	// Return Value / Effect
	//    The item is removed from the array and the page
	// ----------------------------
	this.removePiece = function(e, obj) {
		var id = obj.id;
		for (var i in this.pieces) {
			if (this.pieces[i].id == id) {
				// Remove the element from the array
				Scanning.log(this.pageID, 'DELETE_piece', this.pieces[i].type);
				Scanning.log(this.pageID, 'DELETE_piece_text', this.pieces[i].text);
				this.pieces.splice(i, 1);
				this._unrenderOneMetadataType('pieces',id);
				Scanning.resizeWindow();
			}
		}
		oBook._updateDataTableRecordset();
	}

	// ----------------------------
	// Function: removeAllPieces()
	//
	// Unconditionally removes all pieces from the metadata. Resizes the
	// window just in case the height of the metadata changed.
	//
	// Arguments
	//    none
	//
	// Return Value / Effect
	//    all pieces gone from the screen and from memory
	// ----------------------------
	this.removeAllPieces = function (mult) {
		if (!mult) {
			for (var i in this.pieces) {
				try {
					this._unrenderOneMetadataType('pieces', this.pieces[i].id);
				} catch (err) {}
			}
		}
		this.pieces = null;
		this.pieces = new Array();
		Scanning.resizeWindow();
	}
	// ----------------------------
	// Function: _renderPageTypes()
	//
	// Fills in the Page Types section of the page. Also makes sure that the ID of the
	// element that is created is saved back in the .pieces[] array of the metadata
	// for future reference.
	//
	// Arguments
	//    mult - Whether or not we have multiple pages selected.
	//
	// Return Value / Effect
	//    Zero or more items are added to the Page Types section of the page
	// ----------------------------
	this._renderPageTypes = function() {
		// Loop through the page types adding them to the page
		for (var i in this.pageTypes) {
			var el = this._createMetadataTypeElement('page_types', this.pageTypes[i].type)
			this.pageTypes[i].id = el.id;
			Dom.get('page_types').appendChild(el);
		}
	}

	// ----------------------------
	// Function: _renderPieces()
	//
	// Fills in the Pieces section of the page. Also makes sure that the ID of the
	// element that is created is saved back in the .pieces[] array of the metadata
	// for future reference.
	//
	// Arguments
	//    none
	//
	// Return Value / Effect
	//    Zero or more items are added to the Pieces section of the page
	// ----------------------------
	this._renderPieces = function() {
		// Loop through the page types adding them to the page
		for (var i in this.pieces) {
			var el = this._createMetadataTypeElement('pieces', this.pieces[i].type+' '+this.pieces[i].text)
			this.pieces[i].id = el.id;
			Dom.get('pieces').appendChild(el);
		}
	}

	// ----------------------------
	// Function: _renderPageSide()
	//
	// Called from the render() function, sets the proper selected index onto
	// the page_side drop-down box.
	//
	// Arguments
	//    val - What value are we searching for in the drop-down?
	//
	// Return Value / Effect
	//    An item in the dropdown may or may not be selected. Not found == Nothing selected
	// ----------------------------
	this._renderPageSide = function(val) {
		var el = Dom.get('page_side');
		for (var i = 0; i < el.length; i++) {
			if (el[i].value == val) {
				el.selectedIndex = i;
				break;
			}
		}
	}
	// ----------------------------
	// Function: _createMetadataTypeElement()
	//
	// This adds an element to either the Page Types or Pieces section of the
	// page. Creating the necessary objects, tags, and event listeners to handle
	// the delete button. Makes sure that the element isn't already on the page.
	// We don't like duplicates, even if it doesn't affect the metadata.
	//
	// Arguments
	//    parent - To where are we adding: "page_types" or "pieces"
	//    txt - The text of the element to create
	//
	// Return Value / Effect
	//    The element is added to the page.
	// ----------------------------
	this._createMetadataTypeElement = function(parent, txt) {
		// Make sure this element doesn't exist on the page already
		// Get our top level element, so we can check the children
		var top = Dom.get(parent); // The outer DIV is plural
		var ch = top.getElementsByClassName('keytext');

		for (var i=0; i < ch.length; i++) {
			if (ch[i].innerHTML == txt) {
				return ch[i].parentNode;
			}
		}
		// If we reached this point, the item was not found, so we can add it
		var sp = Dom.get(document.createElement('span'));
		var a = Dom.get(document.createElement('a'));
		var item_id = Dom.generateId(sp);
		var t = Dom.get(document.createElement('text'));
		Dom.addClass(a, 'del-button');
		Dom.addClass(sp, 'keyword');
		Dom.addClass(t, 'keytext');
		if (parent == 'page_types') {
			Event.addListener(a, "click", this.removePageType, sp, this);
		} else {
			Event.addListener(a, "click", this.removePiece, sp, this);
		}
		a.innerHTML = 'X';
		t.innerHTML = txt;

		sp.appendChild(a);
		sp.appendChild(t);
		return sp;
	}

	// ----------------------------
	// Function: _unrenderMetadataTypes()
	//
	// This removes all of the Page Types or Pieces from the screen (but not
	// from the pages.metadata object.)
	//
	// Arguments
	//    parent - From where are we removeing "page_types" or "pieces"
	//
	// Return Value / Effect
	//    All of the elements are deleted
	// ----------------------------
	this._unrenderMetadataTypes = function (parent) {
		// Clear out any extra junk we have in there first.
		var divTypes = new Elem(parent);
		var selects = divTypes.get('childNodes');
		while (selects.length) {
			divTypes.removeChild(selects[0]);
		}
	}

	// ----------------------------
	// Function: _unrenderOneMetadataType()
	//
	// This removes one of the Page Types or Pieces from the screen (but not
	// from the pages.metadata object.)
	//
	// Arguments
	//    parent - From where are we removeing "page_types" or "pieces"
	//    id - the ID of the element to remove.
	//
	// Return Value / Effect
	//    The element is deleted
	// ----------------------------
	this._unrenderOneMetadataType = function (parent, id) {
		// Clear out any extra junk we have in there first.
		var divTypes = new Elem(parent);
		var divChild = new Elem(id);
		divTypes.removeChild(divChild);
	}


	// ----------------------------
	// Function: showPagesDialog()
	//
	// Cause the pages dialog to appear on the screen, setting it up appropriately.
	// This is a DHTML dialog, not a popup window. Those are so old skoool. Includes
	// creating listeners for the various things that can happen from within this
	// dialog.
	//
	// Arguments
	//    None
	//
	// Return Value / Effect
	//    The "dialog" is displayed.
	// ----------------------------
	this.showPagesDialog = function() {
		if (oBook.pages.arrayHighlighted().length == 0) {
			alert('Please select one or more pages before setting a page type.');
			return;
		}
		var checkPanelClick = function(e, obj) {
			// Get the element that we clicked on
			var el = YAHOO.util.Event.getTarget(e);
			// Is this the click event on the button that opened the panel? If so, exit.
			if (el.id == 'btnShowPagesDlg') return;
			if (el !== Dom.get(obj.id) && !Dom.isAncestor(obj.id, el)) {
				YAHOO.macaw.Standard_Metadata.closeAddPagePieceDialog(obj);
			}
		}

		var handleCancel = function() {
			YAHOO.macaw.Standard_Metadata.closeAddPagePieceDialog(YAHOO.macaw.dlgPageNumbering);
		};
		var handleReplace = function() {
			YAHOO.macaw.Standard_Metadata.setPageNumbering();
			YAHOO.macaw.Standard_Metadata.closeAddPagePieceDialog(YAHOO.macaw.dlgPageNumbering);
		};
		var handlePrefix = function() {
			YAHOO.macaw.Standard_Metadata.setPageNumbering(true);
			YAHOO.macaw.Standard_Metadata.closeAddPagePieceDialog(YAHOO.macaw.dlgPageNumbering);
		};
		var myButtons = [
			{ text: "Replace", handler: handleReplace, isDefault: true },
			{ text: "Prefix Only", handler: handlePrefix },
			{ text: "Cancel", handler: handleCancel }
		];

		YAHOO.macaw.dlgPageNumbering = new YAHOO.widget.SimpleDialog("pnlPageNumbering", {
			visible:false, draggable:false, close:false, underlay: "none",
			Y: parseInt(Dom.getY('btnShowPagesDlg') - 200),
			X: parseInt(Dom.getX('btnShowPagesDlg')),
			buttons: myButtons
		} );
		YAHOO.macaw.dlgPageNumbering.setHeader('Indicated Page Numbering');
		YAHOO.macaw.dlgPageNumbering.setBody(Dom.get('dlgPageNumbering').innerHTML);
		YAHOO.macaw.dlgPageNumbering.render("tdPagePrefix");
		YAHOO.macaw.dlgPageNumbering.show();
		Event.addListener(document, 'click', checkPanelClick, YAHOO.macaw.dlgPageNumbering);
	}
	// ----------------------------
	// Function: showAddPageTypeDialog()
	//
	// Displays the popup dialog box to select a page type. Includes creating
	// an event listener to follow click activities on the page to close the
	// dialog if/when the user clicks outside of the dialog box. The event
	// listener is cleared when the dialog closes.
	//
	// Arguments
	//    none
	//
	// Return Value / Effect
	//    The dialog box is displayed.
	// ----------------------------

	this.showAddPageTypeDialog = function() {
		if (oBook.pages.arrayHighlighted().length == 0) {
			alert('Please select one or more pages before setting a page type.');
			return;
		}
		var checkPanelClick = function(e, obj) {
			// Get the element that we clicked on
			var el = YAHOO.util.Event.getTarget(e);
			// Is this the click event on the button that opened the panel? If so, exit.
			if (el.id == 'btnShowAddPageTypeDlg') return;
			if (el !== Dom.get(obj.id) && !Dom.isAncestor(obj.id, el)) {
				YAHOO.macaw.Standard_Metadata.closeAddPagePieceDialog(obj);
			}
		}
		YAHOO.macaw.dlgPageType = new YAHOO.widget.Panel("pnlPageType", {
			visible:false, draggable:false, close:false, underlay: "none"
		} );
		YAHOO.macaw.dlgPageType.setBody(Dom.get('dlgHTMLSelectPageType').innerHTML);
		YAHOO.macaw.dlgPageType.render("tdPagePrefix");
		YAHOO.macaw.dlgPageType.show();
		Event.addListener(document, 'click', checkPanelClick, YAHOO.macaw.dlgPageType);
	}

	// ----------------------------
	// Function: showAddPieceDialog()
	//
	// Displays the popup dialog box to select a piece type and value. Includes
	// creating an event listener to follow click activities on the page to
	// close the dialog if/when the user clicks outside of the dialog box. The
	// event listener is cleared when the dialog closes.
	//
	// Arguments
	//    none
	//
	// Return Value / Effect
	//    The dialog is created and displayed, the event listener is set up
	// ----------------------------
	this.showAddPieceDialog = function() {
		if (oBook.pages.arrayHighlighted().length == 0) {
			alert('Please select one or more pages before entering a piece.');
			return;
		}
		var checkPanelClick = function(e, obj) {
			// Get the element that we clicked on
			var el = YAHOO.util.Event.getTarget(e);
			// Is this the click event on the button that opened the panel? If so, exit.
			if (el.id == 'btnShowAddPieceDlg') return;
			if (el !== Dom.get(obj.id) && !Dom.isAncestor(obj.id, el)) {
				YAHOO.macaw.Standard_Metadata.closeAddPagePieceDialog(obj);
			}
		}
		YAHOO.macaw.dlgPieceType = new YAHOO.widget.Panel("pnlPieceType", {
			visible:false, draggable:false, close:false, underlay: "none"
		} );
		YAHOO.macaw.dlgPieceType.setBody(Dom.get('dlgHTMLSelectPiece').innerHTML);
		YAHOO.macaw.dlgPieceType.render("tdYearVolume");
		YAHOO.macaw.dlgPieceType.show();
		Event.addListener(document, 'click', checkPanelClick, YAHOO.macaw.dlgPieceType);
	}

	// ----------------------------
	// Function: addPageType()
	//
	// Adds the page type to the metadata for the selected items.
	//
	// Arguments
	//    none
	//
	// Return Value / Effect
	//    The page type is ADDED to the existing page types for the selected pages.
	// ----------------------------
// 	this.addPageType = function(obj) {
//
// 		// Get the things that are selected
// 		var pg = oBook.pages.arrayHighlighted();
// 		// Set an array to accumulate any pageids we modify
// 		var page_ids = new Array();
// 		// This will APPEND the Piece to those that are selected.
// 		// Other fields (except Piece) REPLACE the data that's on the object.
// 		var i;
// 		for (var i in pg) {
// 			pg[i].metadata.addPageTypeInternal(obj.value);
// 			// Collect the pageids we modify
// 			page_ids.push(pg[i].pageID);
// 		}
// 		// Hide the dialog for selecting a pagetype
// 		YAHOO.macaw.Standard_Metadata.closeAddPagePieceDialog(YAHOO.macaw.dlgPageType);
// 		YAHOO.macaw.dlgPageType = null;
// 		// Resize the window to accommodate any additional height
// 		Scanning.resizeWindow();
// 		// Log all the pages that were modified at once to not spam the server
// 		Scanning.log(page_ids.join('|'), 'addPageType', obj.value);
// 		oBook._updateDataTableRecordset();
// 	}

	// ----------------------------
	// Function: addPiece()
	//
	// Adds a piece type and text to the metadata for the selected items.
	//
	// Arguments
	//    none
	//
	// Return Value / Effect
	//    The piece is ADDED to the existing pieces for the selected pages.
	// ----------------------------
// 	this.addPiece = function(obj, code) {
//
// 		if (code == 13) {
// 			// Get the things that are selected
// 			var pg = oBook.pages.arrayHighlighted();
// 			// Set an array to accumulate any pageids we modify
// 			var page_ids = new Array();
// 			// This will APPEND the Piece to those that are selected.
// 			// Other fields (except Page Type) REPLACE the data that's on the object.
// 			var i;
// 			for (var i in pg) {
// 				var el = Dom.get('selPiece');
// 				var el2 = Dom.get('txtPieceExtra');
// 				pg[i].metadata.addPieceInternal(el.value, el2.value);
// 				// Collect the pageids we modify
// 				page_ids.push(pg[i].pageID);
// 			}
// 			// Hide the dialog for selecting a piece
// 			YAHOO.macaw.Standard_Metadata.closeAddPagePieceDialog(YAHOO.macaw.dlgPieceType);
// 			YAHOO.macaw.dlgPieceType = null;
// 			// Resize the window to accommodate any additional height
// 			Scanning.resizeWindow();
// 			// Log all the pages that were modified at once to not spam the server
// 			Scanning.log(page_ids.join('|'), 'addPiece', el.value + ' ' + el2.value);
// 			oBook._updateDataTableRecordset();
// 		}
// 	}

	this.clearYear = function () {
		// Get the things that are selected
		var pg = oBook.pages.arrayHighlighted();
		var multiple = (pg.length > 1);
		// Set an array to accumulate any pageids we modify
		var page_ids = new Array();

		if (multiple) {
			if (!confirm('Are you sure you want to clear the Year for '+pg.length+' items?')) {
				return;
			}
		} else {
			Dom.get('year').value = '';
		}
		for (var i in pg) {
			pg[i].metadata.callFunction('set', 'year', '', 0);
			// Collect the pageids we modify
			page_ids.push(pg[i].pageID);
		}
		oBook._updateDataTableRecordset();
		// Log all the pages that were modified at once to not spam the server
		Scanning.log(page_ids.join('|'), 'clearYear', 'DELETED');
	}

	this.clearVolume = function () {
		// Get the things that are selected
		var pg = oBook.pages.arrayHighlighted();
		var multiple = (pg.length > 1);
		// Set an array to accumulate any pageids we modify
		var page_ids = new Array();

		if (multiple) {
			if (!confirm('Are you sure you want to clear the Volume for '+pg.length+' items?')) {
				return;
			}
		} else {
			Dom.get('volume').value = '';
		}
		for (var i in pg) {
			pg[i].metadata.callFunction('set', 'volume', '', 0);
			// Collect the pageids we modify
			page_ids.push(pg[i].pageID);
		}
		oBook._updateDataTableRecordset();
		// Log all the pages that were modified at once to not spam the server
		Scanning.log(page_ids.join('|'), 'clearVolume', 'DELETED');
	}

	this.clearPageSide = function () {
		// Get the things that are selected
		var pg = oBook.pages.arrayHighlighted();
		var multiple = (pg.length > 1);
		// Set an array to accumulate any pageids we modify
		var page_ids = new Array();

		if (multiple) {
			if (!confirm('Are you sure you want to clear the Page Side for '+pg.length+' items?')) {
				return;
			}
		} else {
			Dom.get('page_side').selectedIndex = 0;
		}
		for (var i in pg) {
			pg[i].metadata.callFunction('set', 'pageSide', '', 0);
			// Collect the pageids we modify
			page_ids.push(pg[i].pageID);
		}
		oBook._updateDataTableRecordset();
		// Log all the pages that were modified at once to not spam the server
		Scanning.log(page_ids.join('|'), 'clearPageSide', 'DELETED');
	}

	this.clearPiece = function () {
		// Get the things that are selected
		var pg = oBook.pages.arrayHighlighted();
		var multiple = (pg.length > 1);
		// Set an array to accumulate any pageids we modify
		var page_ids = new Array();

		if (multiple) {
			if (!confirm('Are you sure you want to clear the Pieces for '+pg.length+' items?')) {
				return;
			}
		}
		for (var i in pg) {
			pg[i].metadata.callFunction('removeAllPieces', multiple);
			// Collect the pageids we modify
			page_ids.push(pg[i].pageID);
		}
		oBook._updateDataTableRecordset();
		// Log all the pages that were modified at once to not spam the server
		Scanning.log(page_ids.join('|'), 'clearPiece', 'DELETED');
	}


	this.clearPageType = function () {
		// Get the things that are selected
		var pg = this.pages.arrayHighlighted();
		var multiple = (pg.length > 1);
		// Set an array to accumulate any pageids we modify
		var page_ids = new Array();

		if (multiple) {
			if (!confirm('Are you sure you want to clear the Page Types for '+pg.length+' items?')) {
				return;
			}
		}
		for (var i in pg) {
			pg[i].metadata.callFunction('removeAllPageTypes',multiple);
			// Collect the pageids we modify
			page_ids.push(pg[i].pageID);
			pg[i].metadata.changed.fire('ALL');
		}
		oBook._updateDataTableRecordset();
		// Log all the pages that were modified at once to not spam the server
		Scanning.log(page_ids.join('|'), 'clearPageType', 'DELETED');
	}


	this.clearPageNumber = function () {
			// Get the things that are selected
		var pg = oBook.pages.arrayHighlighted();
		var multiple = (pg.length > 1);
		// Set an array to accumulate any pageids we modify
		var page_ids = new Array();

		if (multiple) {
			if (!confirm('Are you sure you want to clear the Page Numbering for '+pg.length+' items?')) {
				return;
			}
		} else {
			Dom.get('page_prefix').value = '';
			Dom.get('page_number').value = '';
			Dom.get('page_number_implicit').checked = false;
		}
		for (var i in pg) {
			pg[i].metadata.callFunction('set', 'pagePrefix', '');
			pg[i].metadata.callFunction('set', 'pageNumber', '');
			pg[i].metadata.callFunction('set', 'pageNumberImplicit', false);
			// Collect the pageids we modify
			page_ids.push(pg[i].pageID);
		}
		oBook._updateDataTableRecordset();
		// Log all the pages that were modified at once to not spam the server
		Scanning.log(page_ids.join('|'), 'clearPageNumber', 'DELETED');
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
		if (field == 'pagePrefix') {
			// When we are saving multiple records at once, we DO NOT set anything if the field is empty.
			if (!mult || (mult && !isBlank(value))) {
				this.pagePrefix = value;
			}

		} else if (field == 'pageNumber') {
			if (!mult || (mult && !isBlank(value))) {
				this.pageNumber = value;
			}

		} else if (field == 'pageNumberImplicit') {
			// This alwats gets set
			this.pageNumberImplicit = value;

		} else if (field == 'year') {
			if (!mult || (mult && !isBlank(value))) {
				this.year = value;
			}

		} else if (field == 'volume') {
			if (!mult || (mult && !isBlank(value))) {
				this.volume = value;
			}

		} else if (field == 'pageSide') {
			if (!mult || (mult && !isBlank(value))) {
				this.pageSide = value;
			}

		} else if (field == 'notes') {
			this.notes = value;

		}
	}

	this.render = function() {
		if (this.pagePrefix) Dom.get('page_prefix').value = this.pagePrefix;
		if (this.pageNumber) Dom.get('page_number').value = this.pageNumber;
		Dom.get('page_number_implicit').checked = this.pageNumberImplicit;

		Dom.get('page_prefix').title = 'Page Number Prefix: ' + this.pagePrefix;
		Dom.get('page_number').title = 'Page Number Value: ' + this.pageNumber;

		Dom.get('page_prefix').disabled = false;
		Dom.get('page_number').disabled = false;
		Dom.get('page_number_implicit').disabled = false;

		Dom.removeClass('page_number_implicit_text', 'grey');
		if (this.year) Dom.get('year').value = this.year;
		if (this.volume) Dom.get('volume').value = this.volume;
		if (this.notes) Dom.get('notes').value = this.notes;
		if (this.notes) Dom.get('notes').innerHTML = this.notes;

		this._renderPageTypes();
		this._renderPieces();
		this._renderPageSide(this.pageSide);

	}

	this.renderMultiple = function() {
		Dom.get('page_prefix').title = 'Enter Page Number Prefix';
		Dom.get('page_number').title = 'Enter Page Number Value';

		Dom.get('page_prefix').disabled = true;
		Dom.get('page_number').disabled = true;
		Dom.get('page_number_implicit').disabled = true;
		Dom.addClass('page_number_implicit_text', 'grey');
	}

	this.unrender = function() {
		Dom.get('page_prefix').value = '';
		Dom.get('page_prefix').disabled = false;
		Dom.get('page_number').value = '';
		Dom.get('page_number').disabled = false;
		Dom.get('page_number_implicit').checked = false;
		Dom.get('page_number_implicit').disabled = false;
		Dom.removeClass('page_number_implicit_text', 'grey');
		Dom.get('year').value = '';
		Dom.get('volume').value = '';
		Dom.get('notes').value = '';
		Dom.get('notes').innerHTML = '';
		Dom.get('page_side').selectedIndex = 0;

		this._unrenderMetadataTypes('page_types');
		this._unrenderMetadataTypes('pieces');

	}

	this.find = function(fld, val) {
		if (typeof this[fld] == 'string') {
			if (this[fld] == val) {
				return 1;
			}
		} else if (fld == 'pageTypes') {
			for (var i in this.pageTypes) {
				if (this.pageTypes[i].type == val) {
					return 1;
				}
			}
		} else if (fld == 'pieces') {
			// Pieces is not searchable.
			return 0;
		}
		return 0;
	}

}


/* ****************************************** */
/* CLASS MEHTODS                              */
/* ****************************************** */

// ----------------------------
// Function: setPageNumbering()
//
// Take the info from the page numbering popup window and apply it to
// the selected pages.
//
// Arguments
//    prefix_only - Whether or not we are appending only the new prefix value
//                  to the existing prefix value.
//
// Return Value / Effect
//    The metadata is updated.
// ----------------------------
YAHOO.macaw.Standard_Metadata.setPageNumbering = function(prefix_only) {
	var pfx = Lang.trim(Dom.get('page_number_prefix').value);
	var sel = Dom.get('page_number_style')
	var stl = sel.options[sel.selectedIndex].value;
	var start = parseInt(Dom.get('page_number_start').value);
	var inc = parseInt(Dom.get('page_number_increment').value);
	var perimg = parseInt(Dom.get('pages_per_image').value);
	var impl = Dom.get('implicit').checked;

	var pgs = oBook.pages.arrayHighlighted();

	// ----------------------------
	// Function: romanize()
	//
	// Get a roman-numeral version of an integer value (natural numbers only)
	//
	// Arguments
	//    num - The integer to romainze, negatives not allowed
	//
	// Return Value / Effect
	//    The roman numeral string, in uppercase
	// ----------------------------
	var _romanize = function(num, lng) {
		if (!+num)
			return false;
		var	digits = String(+num).split(""),
			key = ["","C","CC","CCC","CD","D","DC","DCC","DCCC","CM",
				   "","X","XX","XXX","XL","L","LX","LXX","LXXX","XC",
				   "","I","II","III","IV","V","VI","VII","VIII","IX"],
			roman = "",
			i = 3;
		while (i--)
			roman = (key[+digits.pop() + (i * 10)] || "") + roman;
		return Array(+digits.join("") + 1).join("M") + roman;
	}

	var _romanize_long = function(num, lng) {
		if (!+num)
			return false;
		var	digits = String(+num).split(""),
			key = ["","C","CC","CCC","CCCC","D","DC","DCC","DCCC","DCCCC",
				   "","X","XX","XXX","XXXX","L","LX","LXX","LXXX","LXXXX",
				   "","I","II","III","IIII","V","VI","VII","VIII","VIIII"],
			roman = "",
			i = 3;
		while (i--)
			roman = (key[+digits.pop() + (i * 10)] || "") + roman;
		return Array(+digits.join("") + 1).join("M") + roman;
	}

	var _make_list = function(items) {
		if (items.length == 0) {
			return null;
		}
		if (items.length == 1) {
			return items[0];
		}
		if (items.length == 2) {
			return items[0]+' and '+items[1];
		}
		if (items.length == 3) {
			return items[0]+', '+items[1]+' and '+items[2];
		}
		if (items.length == 4) {
			return items[0]+', '+items[1]+', '+items[2]+' and '+items[3];
		}
	}
	
	// We are numbering automatically. Always.
	var i;
	for (var i in pgs) {
		nums = new Array();
		ct = perimg;
		while (ct > 0) {
			var n = start;
			if (stl == 'roman') {
				n = _romanize(n);
			} else if (stl == 'roman_lower') {
				n = _romanize(n).toLowerCase();
			} else if (stl == 'roman_long') {
				n = _romanize_long(n);
			} else if (stl == 'roman_lower_long') {
				n = _romanize_long(n).toLowerCase();
			}
			nums.push(n);
			ct = ct - 1;
			start = start + inc;
		}

		if (prefix_only) {
			if (!isBlank(pfx)) {
				pgs[i].metadata.callFunction('set', 'pagePrefix', pfx);
			}
		} else {
			pgs[i].metadata.callFunction('set', 'pagePrefix', pfx);
			pgs[i].metadata.callFunction('set', 'pageNumber', _make_list(nums));
			pgs[i].metadata.callFunction('set', 'pageNumberImplicit', impl);
		}
		nums = null;
	}
	oBook._updateDataTableRecordset();
	if (pgs.length == 1) {
		pgs[0].metadata.callFunction('render');
	}
}


// ----------------------------
// Function: closeAddPagePieceDialog()
//
// Close either of the Page Type or Piece Type dialog boxes. Also, cancel the
// click event listener that was created when the dialog box was opened.
//
// Arguments
//    obj - The dialog box that needs to be closed.
//
// Return Value / Effect
//    The dialog is closed and the listener is removed.
// ----------------------------
YAHOO.macaw.Standard_Metadata.closeAddPagePieceDialog = function(obj) {
	try {
		if (obj) {
			obj.hide();  // Hide the dialog
			obj.destroy(); // Remove it from memory
			// Now remove the click listener
			Event.removeListener(document, 'click');
			if (obj.id == "pnlPageType") {
				YAHOO.macaw.dlgPageType = null
			}
		}
	} catch (err) {}
}

// ----------------------------
// Function: metadataChange()
//
// Called when any of the metadata elements (except Page Type and Piece)
// are changed in the interface. This actually sets the metadata into the
// objects.
//
// Arguments
//    obj - The object that triggered the change event.
//
// Return Value / Effect
//    The data is set into the proper page object(s)
// ----------------------------
YAHOO.macaw.Standard_Metadata.metadataChange = function(obj) {
	// Get the things that are selected
	var pg = oBook.pages.arrayHighlighted();
	// All of these REPLACE the data that's on the object.
	// Only the Page Type and Pieces will APPEND.
	var i;
	// Set an array to accumulate any pageids we modify
	var page_ids = new Array();
	var multiple = (pg.length > 1);

	for (var i in pg) {
		if (obj.id == 'page_prefix') {
			pg[i].metadata.callFunction('set', 'pagePrefix', obj.value, multiple);

		} else if (obj.id == 'page_number') {
			pg[i].metadata.callFunction('set', 'pageNumber', obj.value, multiple);

		} else if (obj.id == 'page_number_implicit') {
			pg[i].metadata.callFunction('set', 'pageNumberImplicit', obj.checked, multiple);

		} else if (obj.id == 'year') {
			if (obj.value.match(/\d{4}/) || obj.value == '') {
				pg[i].metadata.callFunction('set', 'year', obj.value, multiple, multiple);
			} else {
				alert('Year must be empty or exactly four digits: YYYY');
				obj.value = pg[0].metadata.getSaveData().year;
				return;
			}

		} else if (obj.id == 'volume') {
			pg[i].metadata.callFunction('set', 'volume', obj.value, multiple, multiple);

		} else if (obj.id == 'page_side') {
			pg[i].metadata.callFunction('set', 'pageSide', obj.value, multiple, multiple);

		} else if (obj.id == 'notes') {
			pg[i].metadata.callFunction('set', 'notes', obj.value, multiple, multiple);

		}
		// Collect the pageids we modify
		page_ids.push(pg[i].pageID);
	}

	// Log all the pages that were modified at once to not spam the server
	if (obj.id != 'metadata_form') {
		if (obj.id == 'future_review') {
			Scanning.log(page_ids.join('|'), obj.id, obj.checked);
		} else if (obj.id == 'page_number_implicit') {
			Scanning.log(page_ids.join('|'), obj.id, obj.checked);
		} else {
			if (!multiple || (multiple && obj.value)) {
				Scanning.log(page_ids.join('|'), obj.id, obj.value);
			}
		}
	}

	oBook._updateDataTableRecordset();
}


// ----------------------------
// Function: addPageTypeInternal()
//
// Adds a Page Type, both appending it to the pieces[] array and
// and to the screen. The object passed in is assumed to have a ".value" property.
// Operates on all selected pages.
//
// Arguments
//    obj - The thing on the page that caused this method to be called.
//
// Return Value / Effect
//    Item is added to the metadata array and to the screen
// ----------------------------
YAHOO.macaw.Standard_Metadata.evtAddPageType = function (obj) {
	// Get the things that are selected
	var pg = oBook.pages.arrayHighlighted();
	// This will accumulate which page IDs are selected
	var page_ids = new Array();

	for (var i in pg) {
		pg[i].metadata.callFunction('addPageType', obj.value);
		page_ids.push(pg[i].pageID);
		pg[i].metadata.changed.fire(obj.value);
	}
	// Save all of pages to the server together in order to not spam the server
	Scanning.log(page_ids.join('|'), 'page_type', obj.value);

	// All done!
	YAHOO.macaw.Standard_Metadata.closeAddPagePieceDialog(YAHOO.macaw.dlgPieceType);
	oBook._updateDataTableRecordset();

}

// ----------------------------
// Function: addPiece()
//
// Adds a Piece, both appending it to the pieces[] array and
// and to the screen. Operates on all selected pages.
//
// Arguments
//    keycode - The key that was pressed in the box. We only respond to Enter (13).
//
// Return Value / Effect
//    Item is added to the metadata array and to the screen
// ----------------------------
YAHOO.macaw.Standard_Metadata.evtAddPiece = function (keycode) {
	// We only do stiff when the enter key is pressed
	if (keycode == 13) {
		var type = Dom.get('selPiece').value;
		var value = Dom.get('txtPieceExtra').value;

		// Get the things that are selected
		var pg = oBook.pages.arrayHighlighted();
		// This will accumulate which page IDs are selected
		var page_ids = new Array();
		for (var i in pg) {
			pg[i].metadata.callFunction('addPiece', type, value);
			page_ids.push(pg[i].pageID);
		}
		// Save all of pages to the server together in order to not spam the server
		Scanning.log(page_ids.join('|'), 'piece', type);
		Scanning.log(page_ids.join('|'), 'piece_text', value);

		YAHOO.macaw.Standard_Metadata.closeAddPagePieceDialog(YAHOO.macaw.dlgPieceType);
		oBook._updateDataTableRecordset();
	}
}


