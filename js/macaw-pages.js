// ------------------------------
// PAGES OBJECT
//
// This represents all pages in our book. This encapsulates all of the
// functionality that operate on the pages in aggregate.
//
// Parameters
//    parent - Just in case we need it, the page object that contains us.
//    data - The data that makes up the entire metadata for this page.
//    mdModules - The list of metadata modules to use for our pages
//
// Revision History
//     2010/08/06 JMR - Created, initial coding completed.
// ------------------------------

YAHOO.macaw.Pages = function(parent, data, mdModules) {
	this.pages = new Array();
	this.parent = parent;
	this.data = data;

	// Do we need these?
	this.availablePageTypes = null;
	this.availablePieces = null;

	this.mdModules = mdModules;

	// Create our pages based on the data we receive
	// Leave the pages unrendered

	// ----------------------------
	// Function: load()
	//
	// Load the Pages object by creating individual page objects. Each page object
	// is added to a pages[] array. This means that we refer to an individual page
	// using oBook.pages.pages[1]. Not perfect, but it's the best we're going to do
	// for now.
	//
	// The page constructor is called with the the an object representing ourself,
	// the specific data for the page, and the list of metadata modules, which is
	// being carried down so as to instantiate them at the proper point.
	//
	// Arguments
	//    None
	//
	// Return Value / Effect
	//    The pages[] array is filled with one or more page objects.
	// ----------------------------
	this.load = function() {
		for (var i in this.data) {
			var pg = new YAHOO.macaw.Page(this, this.data[i], this.mdModules);
			this.pages.push(pg);
		}
	}

	// ----------------------------
	// Function: render()
	//
	// Cycle through and render all the pages in the book.
	//
	// Arguments
	//    None
	//
	// Return Value / Effect
	//    The page thumbnails should be appearing in the window
	// ----------------------------
	this.render = function() {
		// Loop through the pages, render them all
		for (var i in this.pages) {
			// Render the page
			this.pages[i].render();
		}
	}

	// ----------------------------
	// Function: selected()
	//
	// Given a page index, tell us if the page is selected. It could be
	// argued that this is unnecessary, and perhaps this function will
	// be deprecated in the future.
	//
	// Arguments
	//    idx - the index of the page in question
	//
	// Return Value / Effect
	//    returns true or false
	// ----------------------------
	this.selected = function(idx) {
		return this.pages[idx].selected;
	}


	// ----------------------------
	// Function: highlighted()
	//
	// Given a page index, tell us if the page is highlighted. It could be
	// argued that this is unnecessary, and perhaps this function will
	// be deprecated in the future.
	//
	// Arguments
	//    idx - the index of the page in question
	//
	// Return Value / Effect
	//    returns true or false
	// ----------------------------
	this.highlighted = function(idx) {
		return this.pages[idx].highlighted;
	}

	// ----------------------------
	// Function: select()
	//
	// Select a given page. To ensure that exactly one page is selected,
	// we unselect all pages first.
	//
	// Arguments
	//    idx - the index of the page in question
	//
	// Return Value / Effect
	//    The page is selected and highlighted.
	//    The metadata reflects this accordingly.
	// ----------------------------
	this.select = function(idx) {
		this.selectNone();
		if (typeof idx != 'undefined' && idx >= 0 && idx < this.length()) {
			this.pages[idx].select();
			this.setMetadataFields();
		}
	}

	// ----------------------------
	// Function: unselect()
	//
	// Marks one page as not selected.
	//
	// Arguments
	//    idx - the index of the page in question
	//
	// Return Value / Effect
	//    The page is not selected or highlighted.
	//    The metadata reflects this accordingly.
	// ----------------------------
	this.unselect = function(idx) {
		this.pages[idx].unselect();
		this.setMetadataFields();
	}

	// ----------------------------
	// Function: highlight()
	//
	// Simply marks the page as highlighted and draws the border around the
	// thumbnail on the page.
	//
	// Arguments
	//    idx - the index of the page in question
	//
	// Return Value / Effect
	//    The page is highlighted
	// ----------------------------
	this.highlight = function(idx) {
		this.pages[idx].highlight();
		this.setMetadataFields();
	}

	// ----------------------------
	// Function: unhighlight()
	//
	// Removes the highlight from a thumbnail.
	//
	// Arguments
	//    idx - the index of the page in question
	//
	// Return Value / Effect
	//    The page is not highlighed
	// ----------------------------
	this.unhighlight = function(idx) {
		this.pages[idx].unhighlight();
		this.setMetadataFields();
	}


	// ----------------------------
	// Function: selectAll()
	//
	// Cycles through all pages and highlights them all. Since only
	// one page can be selected at a time, this does not call the
	// select() method, it only calls highlight().
	//
	// Arguments
	//    None
	//
	// Return Value / Effect
	//    All thumbs are highlighted
	//    The metadata reflects this accordingly.
	// ----------------------------
	this.selectAll = function() {
		// Loop through the pages, select them all
		for (var i in this.pages) {
			this.pages[i].highlight();
		}
		this.setMetadataFields();
		this.setPreviewImage();
	}

	// ----------------------------
	// Function: selectBetween()
	//
	// Highlights the page that was clicked on and then highlights those
	// that are in between the first and last highlighed pages. The side
	// effect of this is that you can ALT-click on page 4 and then on page
	// 12 and shift-click on page 10 and pages 4 through 12 will all get
	// selected.
	//
	// Arguments
	//    None
	//
	// Return Value / Effect
	//    One or more thumbnails are selected
	//    The metadata reflects this accordingly.
	// ----------------------------
	this.selectBetween = function() {
		// Loop through the pages, select them all
		var idxA = this.firstHighlightIndex();
		var idxB = this.lastHighlightIndex();
		var i;
		for (var i in this.pages) {
			if (i >= idxA && i <= idxB)
				this.pages[i].highlight();
		}
		this.setMetadataFields();
		this.setPreviewImage();
	}

	// ----------------------------
	// Function: selectNone()
	//
	// Cycles through all pages and un-highlights them all.
	//
	// Arguments
	//    None
	//
	// Return Value / Effect
	//    No pages are highlighted
	//    The metadata reflects this accordingly.
	// ----------------------------
	this.selectNone = function() {
		// Loop through the pages, unselect all
		for (var i in this.pages) {
			if (this.pages[i]) {
				this.pages[i].unselect();
			}
		}
		this.setMetadataFields();
		this.setPreviewImage();
	}

	// ----------------------------
	// Function: selectAlternate()
	//
	// Cycles through all pages and highlights only the even-numbered pages/
	//
	// Arguments
	//    None
	//
	// Return Value / Effect
	//    Every other page is highlighted
	//    The metadata reflects this accordingly.
	// ----------------------------
	this.selectAlternate = function() {
		// Loop through the pages, select every other one
		for (var i in this.pages) {
			if (i % 2 == 1) {
				this.pages[i].highlight();
			} else {
				this.pages[i].unhighlight();
			}
		}
		this.setMetadataFields();
		this.setPreviewImage();
	}

	// ----------------------------
	// Function: selectInverse()
	//
	// Cycles through all pages and highlights those that are not highlighed
	// and un-highlights those that are highlighted.
	//
	// Arguments
	//    None
	//
	// Return Value / Effect
	//    Page selection is inverted.
	//    The metadata reflects this accordingly.
	// ----------------------------
	this.selectInverse = function() {
		// Loop through the pages, invert the selection
		for (var i in this.pages) {
			if (this.pages[i].highlighted) {
				// TODO Should this be unselect() ?
				this.pages[i].unhighlight();
			} else {
				// TODO Should this be select() ?
				this.pages[i].highlight();
			}
		}
		this.setMetadataFields();
		this.setPreviewImage();
	}

	// ----------------------------
	// Function: getSaveData()
	//
	//
	// Arguments
	//    ???
	//
	// Return Value / Effect
	//    ???
	// ----------------------------
	this.getSaveData = function() {
		// Loop through the pages, get the data to be saved.
		var data = [];
		if (typeof oBook.pages.pages != 'object') {
			 alert('For some unknown reason, the array of pages is not an object. No data has been lost, but macaw cannot save. Please 			contact your tech support person to investigate.');
		}
		if (oBook.pages.pages.length == 0) {
			 alert('For some unknown reason, the array of pages is length zero. No data has been lost, but macaw cannot save. Please 			contact your tech support person to investigate.');
		}
		for (var i in oBook.pages.pages) {
			data.push( {
				'page_id': this.pages[i].pageID,
				'metadata': this.pages[i].metadata.getSaveData(),
				'deleted': this.pages[i].deleted,
				'inserted': this.pages[i].inserted
			} );
		}
		return data;
	}

	// ----------------------------
	// Function: setMetadataFields()
	//
	// Internal Function - Update the display of the metadata based on the
	// selected index. This really calls one of three functions depending on
	// how many items are selected. Either the metadata is cleared and disabled
	// (when none are selected), it's cleared and enabled (more than one selected)
	// or it's filled in and enabled (when exactly one is selected). Also calls
	// resizeWindow() to update the page layout in case the metadata is too tall.
	//
	// Arguments
	//    None
	//
	// Return Value / Effect
	//    Metadata fields are available and filled, available and empty, or disabled.
	// ----------------------------
	this.setMetadataFields = function() {
		// Halt this procedure if we are adding missing pages
		if (this.parent.addingMissingPages) return;

		// Get a list of those that are highlighted.
		var arrHighlighted = this.arrayHighlighted();

		// Is there exactly one highligted item left?
		if (arrHighlighted.length == 1) {
			// If so, render its metadata
			arrHighlighted[0].metadata.render();
			this.enableMetadata();

		} else if (arrHighlighted.length > 1) {
			// Render metadata for multiple items selected
			this.renderMultipleMetadata()

		} else {
			// There zero selected, clear the metadata.
			this.unrenderMetadata();
		}
		Scanning.resizeWindow();
	}

	// ----------------------------
	// Function: callMetadata()
	//
	// This function is an entrypoint for the metadata fields on the page
	// to update the metadata when they change. More importantly, this is
	// needed since there are now one or more metadata modules in the system
	// and the page can't be bothered to know this.
	//
	// Arguments
	//    method - Which metadata method to call
	//    obj - The page object that initiated this activity.
	//
	// Return Value / Effect
	//    Metadata fields are available and filled, available and empty, or disabled.
	// ----------------------------
	this.updateMetadata = function(obj, method) {
		if (!method || method == '' || method == 'undefined') {
			method = 'metadataChange';
		}
		// Get the things that are selected
		var pages = oBook.pages.arrayHighlighted();
		// This will APPEND the Piece to those that are selected.
		// Other fields (except Piece) REPLACE the data that's on the object.
		for (var i in pages) {
			pages[i].metadata.callFunction(method, obj);
		}
		// Hide the dialog for selecting a pagetype
		// Resize the window to accommodate any additional height
		Scanning.resizeWindow();
		oBook._updateDataTableRecordset();
	}

	// ----------------------------
	// Function: firstHighlightIndex()
	//
	// Get the index of the FIRST page that's selected/highlighted.
	//
	// Arguments
	//     none
	//
	// Return Value / Effect
	//     Integer of an index into the Book.pages[] array. If no pages are
	//     selected, null is returned.
	// ----------------------------
	this.firstHighlightIndex = function() {
		for (var i in this.pages) {
			if (this.pages[i].highlighted) {
				return parseInt(i);
			}
		}
		return null;
	}

	// ----------------------------
	// Function: lastHighlightIndex()
	//
	// Get the index of the LAST page that's selected/highlighted. Counts
	// backwards from the end of the pages array.
	//
	// Arguments
	//     none
	//
	// Return Value / Effect
	//     Integer of an index into the Book.pages[] array. If no pages are
	//     selected, null is returned.
	// ----------------------------
	this.lastHighlightIndex = function() {
		for (var i = this.pages.length; i--; i <= 0) {
			if (this.pages[i].highlighted) {
				return parseInt(i);
			}
		}
		return null;
	}


	// ----------------------------
	// Function: arrayHighlighted()
	//
	// Get an array of those elements that are selected/highlighted.
	//
	// Arguments
	//     none
	//
	// Return Value / Effect
	//     Array of page objects
	// ----------------------------
	this.arrayHighlighted = function() {
		var arr = new Array();
		for (var i in this.pages) {
			if (this.pages[i].highlighted) {
				arr.push(this.pages[i]);
			}
		}
		return arr;
	}


	// ----------------------------
	// Function: renderMultipleMetadata()
	//
	// Special handling is necessary when multiple thing are selected, so this
	// function is called to prepare the metadata fields to accept data for
	// multiple items. In reality, we change the title of the "Metadata" tag
	// and clear but enable the metadata. Also sets our rendered flag to true.
	//
	// Arguments
	//    None
	//
	// Return Value / Effect
	//    Fields are cleared, but enabled
	// ----------------------------
	this.renderMultipleMetadata = function() {
		if (this.parent.addingMissingPages) return;

//		for (var i in this.pages) {
			this.pages[0].metadata.unrender();
//		}
		Dom.get('sequence_number').innerHTML = '[Multiple Pages Selected]';
		this.enableMetadata();
	}


	// ----------------------------
	// Function: unrenderMetadata()
	//
	// Clears the metadata from the fields and locks the metadata fields so they
	// can't be edited. Called when no items are selected on the page (such as a
	// select none operation). Clears the rendered flag, too.
	//
	// Arguments
	//    None
	//
	// Return Value / Effect
	//    Fields cleared, can't be edited.
	// ----------------------------
	this.unrenderMetadata = function() {
		if (this.parent.addingMissingPages) return;

		Dom.get('sequence_number').innerHTML = '';
		// TODO: Verify that we only need to operate on just the first page
		this.pages[0].metadata.unrender();
// TODO: Probably not needed
// 		for (var i in this.pages) {
// 			this.pages[i].metadata.unrender();
// 		}
		this.disableMetadata();
	}

	// ----------------------------
	// Function: enableMetadata
	//
	// Makes it so that the metadata can be edited by hiding an semitransparent
	// white rectangle overlay.
	//
	// Arguments
	//    none
	//
	// Return Value / Effect
	//    overlay is hidden
	// ----------------------------
	this.enableMetadata = function() {
		var el = Dom.get('metadata_overlay');
		Dom.setStyle(el, 'display', 'none');
	}

	// ----------------------------
	// Function: disableMetadata()
	//
	// Makes it so that the metadata can't be changed by showing an semitransparent
	// white rectangle overlay on top of the metaata.
	//
	// Arguments
	//    none
	//
	// Return Value / Effect
	//    overlay is shown
	// ----------------------------
	this.disableMetadata = function() {
		var el = Dom.get('metadata_overlay');
		Dom.setStyle(el, 'display', 'block');
	}

	// ----------------------------
	// Function: getTableColumns()
	//
	// Get an object representing the columns to be displayed in the table.
	// This looks to the first page to call this, but in reality any page could
	// be used. They are all the same.
	//
	// Arguments
	//    None
	//
	// Return Value / Effect
	//    An object suitable for giving to YUI's ColumnDefs of a data table
	// ----------------------------
	this.getTableColumns = function () {
		return this.pages[0].metadata.getTableColumns();
	}


	// ----------------------------
	// Function: getTableData()
	//
	// Cycles through all pages to create an array of objects containing data
	// for filling the data table (list view) of the page. This can be used
	// to get the data for one page or for all pages.
	//
	// Arguments
	//    idx - (optional) the index of the page to get
	//
	// Return Value / Effect
	//    If idx is supplied, an object containing the data. Otherwise returns
	//    an array of objects for all pages.
	// ----------------------------
	this.getTableData = function(idx) {
		if (!idx && idx != "0") {
			var data = [];
			for (var i in this.pages) {
				data.push(this.pages[i].metadata.getTableData());
			}
			return data;
		} else {
			return this.pages[idx].metadata.getTableData()
		}
	}

	// ----------------------------
	// Function: _syncArray()
	//
	// Internal Function - Updates the array of pages to reflect the new order
	// of the pages based on either the new order of the thumbnails or the new
	// order of the data table (disabled, can't drag-drop from the data table)
	// This is called after a drag-drop operation from the thumbnails. This
	// reorders by splicing out and adding to a new array the elements in the
	// Book.pages[] array. Then it adds them all back to Book.pages[]. If you can
	// think of a better way to sort one array based on the order of another,
	// show me and I'll buy you lunch.
	//
	// Arguments
	//    syncFrom - Which list on the page did we come from, "thumbs" or "thumbs_missing"
	//
	// Return Value / Effect
	//
	// ----------------------------
	// TODO: This needs to somehow be moved into the pages object
	this.reorder = function(fromDiv) {
		var newPages = new Array();

		// Get the list of LI tags on the page
		var searchthumbs = Dom.getElementsByClassName('thumb', 'li', fromDiv);

		// Cycle through the list of LI tags, remove items from our
		// array, place them into the new array in the same order as
		// the LI tags.
		var i;
		for (var i in searchthumbs) {
			// Find the index into our pages array of the page in question
			var idx = this.find('thumbnailID', searchthumbs[i].id);
			if (idx != null) {
				var spl = this.pages.splice(idx, 1);
				// Remove the page from the main list and add it to a temp list
				newPages.push(spl[0]);
			}

// IMPORTANT! Drag-Drop on the data table is disabled
// 		} else if (syncTo = 'datatable') {
// 			// Get the records in our data table
// 			var rs = this.objDataTable.getRecordSet().getRecords();
// 			// Cycle through the records in the data table, remove items
// 			// from our array, place them into the new array in the same
// 			// order as the table records.
//			var i;
// 			for (var i in rs) {
// 				var idx = this._getIndexFromDataTable(rs[i].id);
// 				newPages.push(this.pages.splice(idx, 1)[0]);
// 			}
		}
		this.pages = newPages;
	}

	// ----------------------------
	// Function: remove()
	//
	// Removes one page from the book and returns that page, just in case.
	//
	// Arguments
	//    idx - the index of the page to remove
	//
	// Return Value / Effect
	//    The page that was removed.
	// ----------------------------
	this.remove = function(idx) {
		return this.pages.splice(idx, 1);
	}

	// ----------------------------
	// Function: append()
	//
	// Adds one page to the book. The page is added to the end of the list of pages.
	//
	// Arguments
	//    obj - The page to add.
	//
	// Return Value / Effect
	//    True or false (?)
	// ----------------------------
	this.append = function(obj) {
		return this.pages.push(obj);
	}

	// ----------------------------
	// Function: length()
	//
	// How many pages does this book have?
	//
	// Arguments
	//    None
	//
	// Return Value / Effect
	//    An integer
	// ----------------------------
	this.length = function() {
		return this.pages.length;
	}

	// ----------------------------
	// Function: find()
	//
	// Returns the index of the first page matching the given query.
	//
	// Arguments
	//    field - The field we are searching on
	//    val   - The value of the field that we seek.
	//
	// Return Value / Effect
	//    The index of the page that matches the query.
	// ----------------------------
	this.find = function(field, val) {
		for (var i in this.pages) {
			if (this.pages[i]) {
				if (field == 'id') {
					if (this.pages[i].elemThumbnailLI.id == val) {
						return parseInt(i);					
					}
				} else {
					if (this.pages[i][field] == val) {
						return parseInt(i);
					}
				}
			}
		}
	}


// TODO: This is nto working!
	this.findAll = function(field, val) {
		var found = [];
		for (var i in this.pages) {
			if (this.pages[i]) {
				var md = this.pages[i].metadata;
				for (var m in md.modules) {
					if (!md.modules[m].find(field, val)) {
						found.push(this.pages[i]);
						break;
					}
				}
			}
		}
		return found;
	}

	// ----------------------------
	// Function: _setPreviewImage()
	//
	// Internal Function - Updates the preview image based on how many things
	// are seleted. If exactly one, the preview image is set for that page. If
	// zero or more than one, the preview image is cleared.
	//
	// Arguments
	//    none
	//
	// Return Value / Effect
	//    The preview image is shown or hidden as appropriate.
	// ----------------------------
	this.setPreviewImage = function () {
		// Halt this procedure if we are adding missing pages
		if (this.parent.addingMissingPages) return;

		// Get a list of those that are highlighted.
		var arrHighlighted = this.arrayHighlighted();

		// Is there exactly one highligted item left?
		if (arrHighlighted.length == 1) {
			// Set the main image
// 			if (!arrHighlighted[0].imgPreview) {
// 				var img = new Image;
// 				img.src = arrHighlighted[0].urlPreview;
// 				arrHighlighted[0].imgPreview = img;
// 			}
			Dom.get('preview_img').src = arrHighlighted[0].urlPreview;
			Dom.setStyle('preview_img', 'cursor', "url('"+sBaseUrl+"/inc/magnifier/assets/mag-cursor.gif'),auto");

		} else if (arrHighlighted.length > 1) {
			Dom.get('preview_img').src = imgMultiSelect.src;
			Dom.setStyle('preview_img', 'cursor', "default");
			Scanning.magnifier.kill();
		} else {
			// There zero or more than one, clear the metadata.
			Dom.get('preview_img').src = imgSpacer.src;
			Dom.setStyle('preview_img', 'cursor', "default");
			Scanning.magnifier.kill();
		}

	}

	this.filter = function(field, value) {
		for (var i in this.pages) {
			this.pages[i].unhide();
		}
		if (field && value) {
			var found = [];
			for (var i in this.pages) {
				if (this.pages[i]) {
					var md = this.pages[i].metadata;
					var found = 0;
					for (var m in md.modules) {
						if (md.modules[m].find(field, value)) {
							found = 1;
						}
					}
					if (!found) {
						this.pages[i].hide();
					}
				}
			}
		}
	}

	this.reHide = function(){
		for (var i in this.pages) {
			if (this.pages[i].hidden) {
				this.pages[i].hide();
			}
		}
	}
	
	this.listIDs = function() {
		ret = Array();
		for (var i in this.pages) {
			ret.push(this.pages[i].elemThumbnailLI.id);
		}
		return ret;
	}
}









