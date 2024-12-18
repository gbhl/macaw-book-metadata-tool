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
	this.name = 'Standard_Metadata'; // Should be the name of this file without the .js extension
	this.data = data;
	this.sequence = null;
	this.pagePrefix = null;
	this.pageNumber = '';
	this.pageNumberImplicit = null;
	this.pageTypes = new Array();
	this.year = null;
	this.volume = null; // Level 1
	this.piece_text = null; // Level 1
	this.notes = null;
	this.pageSide = null;
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
			{key:"volume",      label:'Lvl 1' },
			{key:"piece_text",  label:'Lvl 2' },
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
		if (this.data.piece_text)           this.piece_text = this.data.piece_text;
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

		// Set up handling for the buttons and fields on our metadata fields.

		// This is really special. We DO NOT want to call this more than once.
		// So we set our own variable onto the oBook because we can
		// (and which is more or less global, as far as we are concerned)
		if (!oBook.initialized_Standard_Metadata) {
			oBook.obtnShowPagesDlg = new Elem('btnShowPagesDlg');
			oBook.obtnShowPagesDlg.on("click", this.showPagesDialog, null, oBook);

			oBook.obtnShowAddPageTypeDlg = new Elem('btnShowAddPageTypeDlg');
			oBook.obtnShowAddPageTypeDlg.on("click", this.showAddPageTypeDialog, null, oBook);

			oBook.obtnClearYear = new Elem('btnClearYear');
			oBook.obtnClearYear.on("click", this.clearYear, null, oBook);

			oBook.obtnClearVolume = new Elem('btnClearVolume');
			oBook.obtnClearVolume.on("click", this.clearVolume, null, oBook);

			oBook.obtnClearPageSide = new Elem('btnClearPageSide');
			oBook.obtnClearPageSide.on("click", this.clearPageSide, null, oBook);

			oBook.obtnClearPageNumber = new Elem('btnClearPageNumber');
			oBook.obtnClearPageNumber.on("click", this.clearPageNumber, null, oBook);

			oBook.obtnClearPieceText = new Elem('btnClearPieceText');
			oBook.obtnClearPieceText.on("click", this.clearPieceText, null, oBook);

			oBook.obtnClearPageType = new Elem('btnClearPageType');
			oBook.obtnClearPageType.on("click", this.clearPageType, null, oBook);

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

		data['page_prefix'] = this.pagePrefix;
		data['page_number'] = this.pageNumber;
		data['page_number_implicit'] = this.pageNumberImplicit;
		data['page_type'] = pt;
		data['year'] = this.year;
		data['volume'] = this.volume;
		data['notes'] = this.notes;
		data['piece_text'] = this.piece_text;
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

		return {
			page_prefix:   this.pagePrefix,
			page_number:   this.pageNumber,
			page_implicit: this.pageNumberImplicit,
			page_type:     pt.join(', '),
			year:          this.year,
			volume:        this.volume,
			piece_text:    this.piece_text,
			page_side:     this.pageSide,
			flag:          this.flagFutureReview,
			notes:         this.notes
		}
	}

	// ----------------------------
	// Function: addPageTypeInternal()
	//
	// Adds a Page Type, both appending it to the page_types[] array and
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
			YAHOO.macaw.Standard_Metadata.closeAddPageDialog(YAHOO.macaw.dlgPageType);
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
// 		var id = obj.id;
// 		for (var i in this.pageTypes) {
// 			if (this.pageTypes[i].id == id) {
// 				// Remove the element from the array
// 				type_removed = this.pageTypes[i].type
// 				Scanning.log(this.pageID, 'DELETE_page_type', type_removed);
// 				this.pageTypes.splice(i, 1);
// 				this._unrenderOneMetadataType('page_types',id);
// 				Scanning.resizeWindow();
// 				this.parent.changed.fire(type_removed);
// 			}
// 		}
// 		oBook._updateDataTableRecordset();

		// Get the title of what we are deleting:
		type_removed = obj.children[1].innerHTML; // Not pretty, but it should work
		
		var pg = oBook.pages.arrayHighlighted();
		var multiple = (pg.length > 1);
		// Set an array to accumulate any pageids we modify

		if (multiple) {
			if (!confirm('Are you sure you want to remove "'+type_removed+'" for '+pg.length+' items?')) {
				return;
			}
		}
		id = null;
		var page_ids = new Array();
		removed = false;
		for (var i in pg) {
			// Find the object metadata for this page. 
			for (var m in pg[i].metadata.modules) {
				if (pg[i].metadata.modules[m].name == 'Standard_Metadata') {
					for (var t in pg[i].metadata.modules[m].pageTypes) {
						if (pg[i].metadata.modules[m].pageTypes[t].type == type_removed) {
							// Remove the element from the array
							pg[i].metadata.modules[m].pageTypes.splice(t, 1);
							// Collect the pageids we modify
							page_ids.push(pg[i].pageID);
							removed = true;
						}
					}
				}
			}
		}
		if (removed) {
			this._unrenderOneMetadataType('page_types', obj.id);
		}
		Scanning.log(page_ids.join('|'), 'DELETE_page_type', type_removed);
		Scanning.resizeWindow();
		oBook._updateDataTableRecordset();
		// Log all the pages that were modified at once to not spam the server
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
	// Function: _renderPageTypes()
	//
	// Fills in the Page Types section of the page. Also makes sure that the ID of the
	// element that is created is saved back in the .page_types[] array of the metadata
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
	// This adds an element to either the Page Types section of the
	// page. Creating the necessary objects, tags, and event listeners to handle
	// the delete button. Makes sure that the element isn't already on the page.
	// We don't like duplicates, even if it doesn't affect the metadata.
	//
	// Arguments
	//    parent - To where are we adding: "page_types"
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
	// This removes all of the Page Types from the screen (but not
	// from the pages.metadata object.)
	//
	// Arguments
	//    parent - From where are we removeing "page_types"
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
	// This removes one of the Page Types from the screen (but not
	// from the pages.metadata object.)
	//
	// Arguments
	//    parent - From where are we removeing "page_types"
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
				YAHOO.macaw.Standard_Metadata.closeAddPageDialog(obj);
			}
		}

		var handleCancel = function() {
			YAHOO.macaw.Standard_Metadata.closeAddPageDialog(YAHOO.macaw.dlgPageNumbering);
		};
		var handleReplace = function() {
			// Verify that we got numbers
			if (Dom.get('page_number_start').value.match(/[^0-9]/) 
			    || Dom.get('page_number_increment').value.match(/[^0-9]/) 
			    || Dom.get('pages_per_image').value.match(/[^0-9]/)) {
				General.showMessage('Please enter only numbers for <strong>Start Counting At</strong>, <strong>Increment By</strong>, and <strong>Pages per Image</strong>.');
				return;
			}
			YAHOO.macaw.Standard_Metadata.setPageNumbering();
			YAHOO.macaw.Standard_Metadata.closeAddPageDialog(YAHOO.macaw.dlgPageNumbering);
		};
		var handlePrefix = function() {
			YAHOO.macaw.Standard_Metadata.setPageNumbering(true);
			YAHOO.macaw.Standard_Metadata.closeAddPageDialog(YAHOO.macaw.dlgPageNumbering);
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
				YAHOO.macaw.Standard_Metadata.closeAddPageDialog(obj);
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
			if (!confirm('Are you sure you want to clear Level 1 for '+pg.length+' items?')) {
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

  this.clearPieceText = function () {
		// Get the things that are selected
		var pg = oBook.pages.arrayHighlighted();
		var multiple = (pg.length > 1);
		// Set an array to accumulate any pageids we modify
		var page_ids = new Array();

		if (multiple) {
			if (!confirm('Are you sure you want to clear Level 2 for '+pg.length+' items?')) {
				return;
			}
		} else {
			Dom.get('piece_text').value = '';
		}
		for (var i in pg) {
			pg[i].metadata.callFunction('set', 'piece_text', '', 0);
			// Collect the pageids we modify
			page_ids.push(pg[i].pageID);
		}
		oBook._updateDataTableRecordset();
		// Log all the pages that were modified at once to not spam the server
		Scanning.log(page_ids.join('|'), 'piece_text', 'DELETED');
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

		} else if (field == 'piece_text') {
			if (!mult || (mult && !isBlank(value))) {
				this.piece_text = value;
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
		if (this.piece_text) Dom.get('piece_text').value = this.piece_text;
		if (this.notes) Dom.get('notes').value = this.notes;
		if (this.notes) Dom.get('notes').innerHTML = this.notes;

		this._renderPageTypes();
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
		Dom.get('piece_text').value = '';
		Dom.get('notes').value = '';
		Dom.get('notes').innerHTML = '';
		Dom.get('page_side').selectedIndex = 0;

		this._unrenderMetadataTypes('page_types');

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
// Function: closeAddPageDialog()
//
// Close either of the Page Type dialog boxes. Also, cancel the
// click event listener that was created when the dialog box was opened.
//
// Arguments
//    obj - The dialog box that needs to be closed.
//
// Return Value / Effect
//    The dialog is closed and the listener is removed.
// ----------------------------
YAHOO.macaw.Standard_Metadata.closeAddPageDialog = function(obj) {
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
// Called when any of the metadata elements (except Page Type)
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
	// Only the Page Type will APPEND.
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

    } else if (obj.id == 'piece_text') {
			pg[i].metadata.callFunction('set', 'piece_text', obj.value, multiple, multiple);

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
// Adds a Page Type, both appending it to the page_ids[] array and
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
	oBook._updateDataTableRecordset();
  
}



