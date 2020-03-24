// ------------------------------
// BOOK OBJECT
//
// This is the central entry point to the review / metadata page. This object
// is mostly designed to be standalone, but it likes to work with a page. It has
// not been tested to work in-memory, but in theory, you can get away with setting
// metadata and reordering pages without ever render()ing the book. But why would
// you want to do that? :)
//
// Revision History
//     2010/08/06 JMR - Created, initial coding completed.
// ------------------------------

// ------------------------------
// TODO LIST - Things that are broke that need a-fixin'
// 2010/08/06 JMR - Add a copyright/license usage to all of our scripts everywhere.
// 2010/08/09 JMR - Code the "Saving" overlay with the spinner (hide and unhide)
//
// DONE LIST
// 2010/08/06 JMR - Make sure the SAVE button lives on top of the overlay so it's always available. DONE 2010/08/09
// 2010/08/06 JMR - Can't select something in data table. Need to select one or more rows.
//                  In fact, the entire click/multi-click event handling needs to be done
//                  on the data table. DONE 2010/08/09
// 2010/08/06 JMR - Things in the data table don't update when the metadata changes. DONE 2010/08/09
// 2010/08/09 JMR - Add in a checkmark for the "Flag for review" checkbox in the data table. DONE 2010/08/10
// 2010/08/09 JMR - Add an icon to the datatable for notes (and caption?) DONE 2010/08/10
// 2010/08/09 JMR - Allow a deselect in the data table when clicking something that's highlighted - SKIP (press ALT to deselct)
// 2010/08/09 JMR - Code the FINISH button. DONE 2010/08/10
// 2012/06/28 SCS - Redirect FINISH button from /main to 
// ------------------------------


YAHOO.macaw.Book = function() {
	this.itemID = 0;
	this.pages; // object controlling aggregate activities on the array of pages
	this.DTDrags = new Array();
	this.objDataTable = null;
	this.elemThumbnails = null;
	this.elemDataTable = null;
	this.loaded = false;
	this.addingMissingPages = (window.location.pathname.match(/scan\/missing\/insert/));
	this.metadataModulesGlobal = new Array();
	this.contextMenu = null;
	this.modified = 0;
	
	// ----------------------------
	// Function: load()
	//
	// Takes the data from the caller and cycles through the pages, creating a
	// new page object for each page element found.
	//
	// Arguments
	//    data - An Object that contains a pages[] array of data from the server.
	//           See the main documentation for the structure of the object.
	//
	// Return Value / Effect
	//    The Book object's pages are filled in (and, presumably, their metadata)
	// ----------------------------
	this.load = function(data, skip_buttons) {
		// TODO: What else is in the data object, do we need to save anything on ourself?
		this.itemID = data.pages[0].item_id;
		this.pages = new YAHOO.macaw.Pages(this, data.pages, Scanning.metadataModules);
		this.pages.load();
		this.loaded = true;
		
		if (!skip_buttons) {
			// These can't be done before the pages object is ready.
			// So we'll load them here.
			var obtnSelectAll = new YAHOO.widget.Button("btnSelectAll");
			obtnSelectAll.on("click", oBook.pages.selectAll, null, oBook.pages);
	
			var obtnSelectNone = new YAHOO.widget.Button("btnSelectNone");
			obtnSelectNone.on("click", oBook.pages.selectNone, null, oBook.pages);
	
			var obtnSelectAlternate = new YAHOO.widget.Button("btnSelectAlternate");
			obtnSelectAlternate.on("click", oBook.pages.selectAlternate, null, oBook.pages);
	
			var obtnSelectInverse = new YAHOO.widget.Button("btnSelectInverse");
			obtnSelectInverse.on("click", oBook.pages.selectInverse, null, oBook.pages);

			var obtnAlphaReorder = new YAHOO.widget.Button("btnAlphaReorder");
			obtnAlphaReorder.on("click", oBook.reSortAll);

			window.onbeforeunload = function () {
				if (oBook.modified) { // "this" refers to the window
					return "You have unsaved changes.";
				}
			}
			
		}
	}

	// ----------------------------
	// Function: save()
	//
	// Prepares and sends the data of the book back to the server. Only saves
	// metadata that has changed and the order of the pages as they appear in the
	// thumbnails on the screen.
	//
	// Arguments
	//    e - The YUI event object
	//    finishing - Are we completing editing the book? If so, we won't display
	//                any success messages, call the finish() method instead.
	//
	// Return Value / Effect
	//    No return value. Assume that the data is saved, present an error if not.
	// ----------------------------
	this.save = function(e, finishing, finishing_later, suppress_dialog, resort_pages) {
		// This is the callback to handle the saving of the data.
		var saveCallback = {
			success: function (o){
				eval('var r = '+o.responseText);
				if (r.redirect) {
					window.location = r.redirect;
				} else {
					if (r.error) {
						General.showErrorMessage(r.error);
						this.hideSavingIndicator();
					} else if (r.message) {
						this.modified = 0;
						if (finishing) {
							this._doFinish();
						} else if (finishing_later) {
							//Changed direction to /scan/review. Finish later removed from review page. This only used with missing pages
							if (resort_pages) {
								window.location = sBaseUrl+'/scan/reorder_all/';
							} else {
								window.location = sBaseUrl+'/scan/review/';
							}
						} else {
							if (!suppress_dialog) {
								General.showMessage(r.message);
							}
							this.hideSavingIndicator(finishing, finishing_later);
						}
					}
				}
			},
			failure: function (o){
				General.showErrorMessage('There was a problem saving the metadata for the pages. If it helps, the error was:<blockquote style="font-weight:bold;color:#990000;">'+o.statusText+"</blockquote>");
			},
			scope: this
		};

		// Set an indicator on the page to show we are saving.
		this.showSavingIndicator(finishing, finishing_later);

		// Gather the data into an object so we can send it in via AJAX/JSON
		var objData = {
			"item_id": this.itemID,
			"inserted_missing": ((finishing_later || this.addingMissingPages) ? 1 : 0),
			"pages": this.pages.getSaveData()
		};

		if (objData.pages.length == 0) {
			 alert('For some unknown reason, the data being saved is empty. No data has been lost. Please do not close this window and 			contact your tech support person to investigate.');
		}


		var strPOST = 'data='+YAHOO.lang.JSON.stringify(objData);
		strPOST = strPOST.replace(/\&/g,'%26')

		// Connect to the server to save the data
				// Get the CSRF Token and add it to the data
				//	<input type="hidden" name="li_token" value="e024ddb2a0b7222fa6eb296e9b0c9def">
				token_name = 'li_token';
				token_value = 'NULL';
				els = document.getElementsByTagName("meta");
				for (i = 0; i < els.length; i++) {
					if (els[i].name == 'csrf-name') { token_name = els[i].content; }
					if (els[i].name == 'csrf-token') { token_value = els[i].content; }
				}
				strPOST = strPOST + "&" + token_name + "=" + token_value;

		var transaction = YAHOO.util.Connect.asyncRequest('POST', sBaseUrl+'/scan/save_pages', saveCallback, strPOST);
	}


	// ----------------------------
	// Function: finish()
	//
	// Event Handler - Called from the finish button. Calls the save() method
	// with a flag to indicate that we are finishing the book.
	//
	// Arguments
	//    e - The YUI event object
	//
	// Return Value / Effect
	//    No return value. Assume that the data is saved and redirect.
	// ----------------------------
	this.finish = function(e) {
		this.save(e, true, false);
	}

	this.finishLater = function(e) {
		this.save(e, false, true);
	}


	// ----------------------------
	// Function: _doFinish()
	//
	// Finishes reviewing the book by calling save() one last time and then
	// calling the /end_review/ URL to mark the book as finished. When done, this
	// will redirect the browser to the main page.
	//
	// Arguments
	//    None
	//
	// Return Value / Effect
	//    No return value. Assume that the data is saved and redirect.
	// ----------------------------
	this._doFinish = function() {
		var finishCallback = {
			success: function(o) {
				eval('var r = '+o.responseText);
				if (r.redirect) {
					this.hideSavingIndicator(true);
					window.location = r.redirect;
				} else if (r.error) {
					this.hideSavingIndicator(true);
					General.showErrorMessage(r.error);
				}
			},
			failure: function(o) {
				General.showErrorMessage('There was a problem finishing up the book. Please try again, maybe. If it helps, the error was:<blockquote style="font-weight:bold;color:#990000;">'+o.statusText+"</blockquote>");
			},
			scope: oBook
		};
		if (this.addingMissingPages) {
			var transaction = YAHOO.util.Connect.asyncRequest('GET', sBaseUrl+'/scan/missing/finish', finishCallback);
		} else {
			var transaction = YAHOO.util.Connect.asyncRequest('GET', sBaseUrl+'/scan/end_review', finishCallback);
		}
	}

	// ----------------------------
	// Function: showSavingIndicator()
	//
	// Causes the saving indicator/spinner to appear on the screen.
	//
	// Arguments
	//    finishing - Did we click the "Finish" button?
	//    later - Did we click the "Finish Later" button?
	//
	// Return Value / Effect
	//    The saving indicator/spinner is displayed
	// ----------------------------
	this.showSavingIndicator = function(finishing, later) {
		if (finishing) {
			Dom.addClass('btnFinished-button','spin');

		} else if (later) {
			Dom.addClass('btnFinishLater-button','spin');

		} else {
			Dom.addClass('btnSave-button','spin');

		}
	}


	// ----------------------------
	// Function: hideSavingIndicator()
	//
	// Causes the saving indicator/spinner to disappear from the display.
	//
	// Arguments
	//    finishing - Did we click the "Finish" button?
	//    finishing_later - Did we click the "Finish Later" button?
	//
	// Return Value / Effect
	//    The saving indicator/spinner is hidden
	// ----------------------------
	this.hideSavingIndicator = function(finishing, later) {
		if (finishing) {
			Dom.removeClass('btnFinished-button','spin');

		} else if (later) {
			Dom.removeClass('btnFinishLater-button','spin');

		} else {
			Dom.removeClass('btnSave-button','spin');

		}
	}


	// ----------------------------
	// Function: render()
	//
	// Cause the book to appear in the page. "Draw" the pages onto the screen.
	//
	// Arguments
	//    divThumbs - Which <div> on the page will contain the thumbnail images.
	//    divDataTable - Which <div> will contain the data table list of pages.
	//
	// Return Value / Effect
	//    The book is drawn on the page, the render flag in the book/pages is set,
	//    the data table is filled in.
	// ----------------------------
	this.render = function(divThumbs, divDataTable) {
		// Save where we are going to place the thumbnail images
		if (divThumbs) this.elemThumbnails = Dom.get(divThumbs);

		// Make the thumbnail images drag-droppable
		new YAHOO.util.DDTarget(this.elemThumbnails);

		// Save the div in which the DataTable will reside
		if (divDataTable) {
			this.elemDataTable = Dom.get(divDataTable);
			// Make sure the data table is ready to accept new rows
			this._initDataTable();
		}
		this.pages.render();

		if (divDataTable) {
			// Render the datatable
			this._renderDataTable();
		}

		// Create the popup menu, later we'll associate it to the thumbnail images
		this.contextMenu = new YAHOO.widget.ContextMenu(
			'pagecontextmenu', {
			trigger: this.pages.listIDs(),
			itemdata: ['Delete Page'],
			lazyload: true
		});
		contextMenuClick = function(p_sType, p_aArgs) {
			var oItem = p_aArgs[1];
			var oTarget = this.contextMenu.contextEventTarget;
			var oLI;
			if (oItem) {
				oLI = oTarget.nodeName.toUpperCase() == "LI" ? oTarget : YAHOO.util.Dom.getAncestorByTagName(oTarget, "LI");
				switch (oItem.index) {
					case 0: oBook.deletePage(oLI.id);
					break;
				}
			}		
		}
		function renderContextMenu(p_sType, p_aArgs) {
			this.contextMenu.subscribe("click", contextMenuClick, null, oBook);
		}
		this.contextMenu.subscribe("render", renderContextMenu, null, oBook);
	}
	
	this.deletePage = function(LIid) {
		
		if (oBook.elemThumbnails.children.length < 2) {
			General.showErrorMessage('An item must have at least one page.');
			return;
		}
		
		// Verify with the user that they want to delete
		function doDelete() {
			// Hide the YesNo box
			General.message.cancel();
			General.message.destroy();
			General.divDelete('message_mask');
			General.divDelete('message_c');
			
			// Get the index into the array of the page we are deleting
			var idx = oBook.pages.find('id', LIid);
			// Remove the elements from the page. This also marks it as deleted.
			oBook.pages.pages[idx].delete();
			// When we save, the page will be deleted from the database.
		}
		General.showYesNo('Are you sure you want to delete this page? This cannot be undone.', doDelete);
		
	}

	this.reSortAll = function(e) {
		// Verify with the user that they want to delete
		function doReSortAll() {
			// Save our changes, but don't finish the book
			YAHOO.util.Selector.query('#messageDialog div.bd')[0].innerHTML = "<h2>Saving your changes...</h2>";
			YAHOO.util.Selector.query('#messageDialog div.ft')[0].style.display = "none";
			oBook.save(e, false, true, true, true);
		}
		General.showYesNo(
			'<strong>Are you sure you want to reorder all of the pages?</strong><br><br>The pages will be <em>reordered</em> alpha-numerically based on the original filename of the TIFF file that was uploaded. This cannot be undone. All of your metadata will be saved. Only the order of the pages will be changed.',
			doReSortAll
		);
	}

	// ----------------------------
	// Function: zoom()
	//
	// Make the thumbnails larger or smaller based on the value supplied. The
	// value is a factor and is used in a formula to create calculate the
	// actual pixels of the thumbnails. Zoom factor defaults (elsewhere) to 27.
	//
	// Arguments
	//    zoomFactor - a number from 0 to 100
	//
	// Return Value / Effect
	//    the thumbnails get bigger or smaller on the page based on the factor
	// ----------------------------
	this.zoom = function(zoomFactor) {
		// This is here because we are only modifying the styles of the page
		// not the actual thumbnail objects themselves.
		var fontPx = 8;
		var aspect = 1.75;
		var infoOffset = 10; // What is the CSS width of the padding inside the overlay? (both sides)

		var width = (zoomFactor * 1.5) + 35;                            // Width is calculated from the slider
		var height = width * aspect;                                    // Total eight of the thumbnail
		var fontSize = (new Number(height / 75 * fontPx)).toFixed(0);   // Font size
		var imgHeight = (height) - fontSize - 5;                        // Image height (subtracts font height)

		// Set the height/width of the thumbnail itself
		styleSheet.set('.thumb', {
			width: width + 'px',
			height: height + 'px'
		});

		// Set the height/width of the overlay div (same as the element itself)
		styleSheet.set('.thumb .info', {
			width: (width - infoOffset) + 'px',
			height: (height - infoOffset) + 'px',
			fontSize: (fontSize / 14) + 'em'
		});

		// Set the height/width of the img object
		styleSheet.set('.thumb img', {
			width: (width - 4) + 'px',
			height: (imgHeight - 4) + 'px'
		});

		// Set the font size of the caption (13px == 75px)
		styleSheet.set('.thumb .caption', {
			fontSize: (fontSize / 14) + 'em',
			width: width + 'px',
			top: (imgHeight + 1) + 'px'
		});

		var mt = Dom.get('thumbs_missing');
		if (mt) {
			mt.style.height = int(height - infoOffset + 12) + 'px';
		}
	}


	// ----------------------------
	// Function: thumbsClick()
	//
	// Handle the click event on the space that contains the thumbnails, but not
	// a thumbnail itself. This calls the selectNone() function.
	//
	// Arguments
	//    e - The YUI event object
	//    obj - The object that caused the event
	//
	// Return Value / Effect
	//    Nothing ... or ... the thumbs are all unselected.
	// ----------------------------
	this.thumbsClick = function(e, obj) {
		// Get the element that we clicked on
		var el = YAHOO.util.Event.getTarget(e);
		if (el.id == 'thumbs') {
			this.pages.selectNone();
		}
	}


	// ----------------------------
	// Function: select()
	//
	// Event Handler - Called when a thumbnail object is clicked on. Calls the
	// internal function _doSelectHandler() to actually do the work.
	//
	// Arguments
	//    e - The YUI event object
	//
	// Return Value / Effect
	//    The state of the thumbnail(s) selected status(es) is(are) set.
	//    The preview image and metadata fields are updated as appropriate.
	// ----------------------------
	this.pageClickHandler = function(e, obj) {
		if (focusObject) {
			Dom.get(focusObject).blur();
		}
	//	if (this.addingMissingPages) {
			oBook._doSelectHandler(oBook.pages.find('thumbnailID', e.currentTarget.id));
	//	} else {
	//		this._doSelectHandler(this._getIndexFromThumbnail(e.currentTarget.id));
	//	}
	}

	// ----------------------------
	// Function: _initDataTable()
	//
	// Internal Function - Performs the work of setting up the data table to hold
	// the list of pages. Does not add any rows to the table. That's done when
	// the pages themselves are rendered. See the Page object for that.
	//
	// Arguments
	//    none
	//
	// Return Value / Effect
	//    The YUI data table is created, even if it's hidden on the page.
	// ----------------------------
	this._initDataTable = function() {
		var formatThumb = function(elCell, oRecord, oColumn, oData) {
			elCell.innerHTML = '<img src="'+oData+'" height="24" border="0">';
		}
		var myData = {pages: []};
		var myColumnDefs = [
			{key:"thumbnail",   label:'',     className:'default-dt-field', formatter:formatThumb},
			{key:"filebase",    label:'File', className:'default-dt-field' },
			{key:"sequence",    label:'Seq.', className:'default-dt-field', formatter:YAHOO.widget.DataTable.formatNumber}
			// This must come from the individual metadata modules
		];
		var dynColumns = this.pages.getTableColumns();
		for (var def in dynColumns) {
			myColumnDefs.push(dynColumns[def]);
		}

		var myDataSource = new YAHOO.util.DataSource(myData.pages);
		myDataSource.responseType = YAHOO.util.DataSource.TYPE_JSARRAY;
		myDataSource.responseSchema = {
			fields: ["thumbnail","filebase","sequence","page_prefix", "page_number", "page_type", "year", "volume", "piece"]
		};
		this.objDataTable = new YAHOO.widget.DataTable(this.elemDataTable, myColumnDefs, myDataSource);

// IMPORTANT! Drag-Drop on the data table is disabled
//		this.objDataTable.subscribe("rowAddEvent",function(e){
//			var id = e.record.getId();
//			oBook.DTDrags[id] = new YAHOO.macaw.ddDataTable(id);
//		})

		this.objDataTable.subscribe("rowClickEvent", this._selectFromDataTable, null, oBook);
	}

	// ----------------------------
	// Function: _renderDataTable()
	//
	// Internal Function - Fills the data table with data, getting the table data
	// from each of the pages.
	//
	// Arguments
	//    none
	//
	// Return Value / Effect
	//    Data table has rows (or not, if there are no pages)
	// ----------------------------
	this._renderDataTable = function() {
		this.objDataTable.addRows(this.pages.getTableData());
		var recs = this.objDataTable.getRecordSet().getRecords();

		for (var i in this.pages.pages) {
			var id = recs[i].getId();
			this.pages.pages[i].elemListRow = new YAHOO.util.Element(id);
			this.pages.pages[i].dataTableID = id;
// IMPORTANT! Drag-Drop on the data table is disabled
//			this.DTDrags[id] = new YAHOO.macaw.ddDataTable(id);
		}
	}

	// ----------------------------
	// Function: afterDragDrop()
	//
	// Internal Function - Called when we're done drag-dropping a thumbnail to
	// sync up our pages array and the rows in the data table to reflect the
	// new order of the pages. The code refers to being able to reorder pages
	// within the data table, but this is currently disabled.
	//
	// Arguments
	//
	// Return Value / Effect
	//
	// ----------------------------
	this.afterDragDrop = function(id, dragged_id, from_id) {
// IMPORTANT! Drag-Drop on the data table is disabled
// 		if (src == 'datatable') {
// 			this.pages.reorder('datatable');
// 			this._reorderThumbnails(id);
//
// 		} else
		// Are we on the missing insert page?
		if (this.addingMissingPages) {
			// If so, we need to insert this new page into the list of
			// pages for the main book
			// 1. Get the ID of the dragged page in the array of books for oBookMissing
			if (id == from_id) {
				// Dragging from/to the same element, don't do anything
				// but the arrays will be synced below

			} else if (id == 'thumbs' && from_id == 'thumbs_missing') {
				var idx = oBookMissing.pages.find('thumbnailID', dragged_id);
				var pg = oBookMissing.pages.remove(idx);
				oBook.pages.append(pg[0]);

			} else if (id == 'thumbs_missing' && from_id == 'thumbs') {
				var idx = oBook.pages.find('thumbnailID', dragged_id);
				var pg = oBook.pages.remove(page_id);
				oBookMissing.pages.append(pg[0]);
			}

			// Purge all other click listeners on what we dragged
			var el = new Elem(dragged_id);
			YAHOO.util.Event.removeListener(el, "click"); // Purge all other click listeners first

			// 4. Enable or Disable the Finished Button
			Scanning.obtnFinished.set('disabled', (Dom.get('thumbs_missing').children.length != 0));
			oBookMissing.pages.reorder('thumbs_missing');
			oBook.pages.reorder('thumbs');

		} else {
			// Now we can safely reorder the items in the book
			oBook.pages.reorder(id);
			if (this.objDataTable) {
				this._reorderDataTable(id);
			}

		}
	}


	// ----------------------------
	// Function: _updateDataTableRecordset()
	//
	// Arguments
	//    None
	//
	// Return Value / Effect
	//    The data table's rows are moved to the right order.
	// ----------------------------
	this._updateDataTableRecordset = function () {
		var recs = this.objDataTable.getRecordSet().getRecords();

		for (var i=0; i < this.pages.length(); i++) {
			this.objDataTable.updateRow(i, this.pages.getTableData(i));
			this.pages.pages[i].dataTableID = this.objDataTable.getRecord(i).getId();
		}
		this.pages.reHide()
	}

	// ----------------------------
	// Function: _reorderDataTable()
	//
	// Internal Function - Updates the order of things in the Data Table
	// to reflect that of the thumbnails. Called after a thumbnail has been
	// drag-dropped to reorder. This function actually deletes all records
	// in the data table and re-adds them to the
	//
	// Arguments
	//    None
	//
	// Return Value / Effect
	//    The data table's rows are moved to the right order.
	// ----------------------------
	this._reorderDataTable = function() {
		this.objDataTable.deleteRows(0, 2000);
		this._renderDataTable();
	}


	// ----------------------------
	// Function: _selectFromDataTable()
	//
	// Internal Function - Event Handler called when the data table is clicked,
	// selects the associated page element and, therefore, the thumbnail. Calls
	// _doSelectHandler() to actually do the work.
	//
	// Arguments
	//    e - the Event object
	//
	// Return Value / Effect
	//    The associated page item is marked as selected.
	// ----------------------------
	this._selectFromDataTable = function(e) {
		this._doSelectHandler(this.pages.find('dataTableID', e.target.id));
	}

	// ----------------------------
	// Function: _doSelectHandler()
	//
	// Internal Function - Attempts to figure out what was clicked on based
	// on the ID of the object that was clicked on. If it's a thumbnail, then
	// it decides what to do based on whether the shift and/or alt keys are
	// pressed.
	//
	// Arguments
	//    e - the Event object
	//
	// Return Value / Effect
	//    The associated page item is marked as selected.
	// ----------------------------
	this._doSelectHandler = function(idx) {
		// Halt this procedure if we are adding missing pages
		if (keyShift) { // Was shift held down?
			this.pages.highlight(idx);
			this.pages.selectBetween(); // Highlight everything in between

		} else if (keyAlt || keyCmd || keyCtrl) { // Was Alt pressed,
			if (this.pages.selected(idx)) {
				this.pages.unselect(idx);

			} else {
				// TODO: Make the distinction between selected and highlighted. What's the differenece here?
				if (this.pages.highlighted(idx)) {
					this.pages.unhighlight(idx);
				} else {
					this.pages.highlight(idx); // Highlight myself
				}
			}

		} else {
			this.pages.select(idx);
		}
		this.pages.setPreviewImage();

	}


// IMPORTANT! Drag-Drop on the data table is disabled
// 	this._reorderThumbnails = function(id) {
// 		// Where is this most recently moved item inside our data table?
// 		var tableIdx = this._getTableIndexFromDataTable(id);
//
// 		// Get the index into our array of pages based on the
// 		// ID of the row in the data table
// 		var pageIdx = this._getIndexFromDataTable(id);
//
// 		// Get the Thumbnail corresponding to the list item we just moved
// 		var thumb = new YAHOO.util.Element(this.pages[pageIdx].thumbnailID);
// 		var thumbs = new YAHOO.util.Element('thumbs');
//
//
// 		// Remove the Thumbnail from the its parent UL
// 		thumbs.removeChild(thumb);
//
// 		if (tableIdx >= this.pages.length-1) {
// 			// If we are at the end, ad the thumbnail to the end of the list
// 			thumbs.appendChild(thumb);
// 		} else {
// 			// If we are not at the end, Add the thumbnail back to the
// 			// list by adding it before its sibling, as it appears in the DataTable
//
// 			var recs = this.objDataTable.getRecordSet().getRecords();
// 			var sibID = recs[tableIdx+1]._sId; // The ID of the sibling Data Table Row
// 			var sibIdx = this._getIndexFromDataTable(sibID)
// 			var sibThumbID = this.pages[sibIdx].thumbnailID;
//
// 			var sibling = new YAHOO.util.Element(sibThumbID);
// 			thumbs.insertBefore(thumb,sibling);
// 		}
// 	}

// UNUSED UTILITY FUNCTIONS

// 	this._getDataTableIDs = function () {
// 		var recs = this.objDataTable.getRecordSet().getRecords();
// 		var x = new Array();
// 		for (var i in recs) {
// 			x.push(recs[i].getId());
// 		}
// 		return x.join(', ');
// 	}
//
// 	this._getPagesDataTableIDs = function () {
// 		var x = new Array();
// 		for (var i in this.pages) {
// 			x.push(this.pages[i].dataTableID);
// 		}
// 		return x.join(', ');
// 	}


};


/* ***************************************************************************************************
/* Drag Drop Code
/* *************************************************************************************************** */

// ----------------------------
// Object: DD THUMBS
//
// We need to handle dragging and dropping and this is the best way we
// knwow how, which is to add globally accessible code that extends the
// YAHOO drag and drop methods for both the list and the thumbnails.
// ----------------------------
YAHOO.macaw.ddThumbs = function(id, sGroup, config) {
	YAHOO.macaw.ddThumbs.superclass.constructor.call(this, id, sGroup, config);

	var el = this.getDragEl();
	Dom.setStyle(el, "opacity", 0.50); // The proxy is slightly transparent

	this.goingUp = false;
	this.lastX = 0;
	this.lastY = 0;
	this.dragFrom = 0;
	this.dropTo = 0;
	this.draggingID = 0;
	this.dragFrom = null;
}

YAHOO.extend(YAHOO.macaw.ddThumbs, YAHOO.util.DDProxy, {

	// ----------------------------
	// Function: startDrag()
	//
	// Called when we first start to drag-drop a thumbnail image. It creates the
	// proxy, semitransparent image on the page and it hides the real object
	// that we initially clicked on to start dragging.
	//
	// Arguments
	//    x / y - Coordinates of where we clicked
	//
	// Return Value / Effect
	//    Proxy object created, original image hidden
	// ----------------------------
	startDrag: function(x, y) {

		// make the proxy look like the source element
		var dragEl = this.getDragEl();
		var clickEl = this.getEl();
// 		if (clickEl.parentElement) {
// 			this.dragFrom = clickEl.parentElement.id;
// 		} else if (clickEl.parentNode) {
			this.dragFrom = clickEl.parentNode.id;
// 		}

		Dom.setStyle(clickEl, "visibility", "hidden");

		dragEl.innerHTML = clickEl.innerHTML;

		Dom.setAttribute(dragEl, "class", "thumb");
		Dom.setAttribute(dragEl, "width", "95px");
		Dom.setStyle(dragEl, "color", Dom.getStyle(clickEl, "color"));
		Dom.setStyle(dragEl, "backgroundColor", Dom.getStyle(clickEl, "backgroundColor"));
		Dom.setStyle(dragEl, "border", "0");
		this.draggingID = clickEl.id;
	},

	// ----------------------------
	// Function: endDrag()
	//
	// Called after the drag-drop is almost done, but before it completes.
	// "slides" the proxy element into the final location on the page.
	//
	// Arguments
	//    e - the drag drop event (not used in this function)
	//
	// Return Value / Effect
	//    N/A (too hard to explain)
	// ----------------------------
	endDrag: function(e) {

		var srcEl = this.getEl();
		var proxy = this.getDragEl();

		// Show the proxy element and animate it to the src element's location
		Dom.setStyle(proxy, "visibility", "");
		var a = new YAHOO.util.Motion(
			proxy, {
				points: {
					to: Dom.getXY(srcEl)
				}
			},
			0.2,
			YAHOO.util.Easing.easeOut
		)
		var proxyid = proxy.id;
		var thisid = this.id;

		// Hide the proxy and show the source element when finished with the animation
		a.onComplete.subscribe(function() {
			Dom.setStyle(proxyid, "visibility", "hidden");
			Dom.setStyle(thisid, "visibility", "");
		});
		a.animate();

	},

	// ----------------------------
	// Method: DRAGDROP.ONDRAGDROP
	// ----------------------------
	// ----------------------------
	// Function: onDragDrop()
	//
	// Called when the item is actually dropped back onto the page. This function
	// determines where it was dropped to place it in the proper location (in the
	// list or at the end). Also calls our oBook.afterDragDrop() to update our data.
	//
	// Arguments
	//    e - The Event Object
	//    id - The ID of the thing that's being dropped (I think)
	//
	// Return Value / Effect
	//    N/A (too hard to explain)
	// ----------------------------
	onDragDrop: function(e, id) {

		// If there is one drop interaction, the li was dropped either on the list,
		// or it was dropped on the current location of the source element.
		if (DDM.interactionInfo.drop.length === 1) {

			// The position of the cursor at the time of the drop (YAHOO.util.Point)
			var pt = DDM.interactionInfo.point;

			// The region occupied by the source element at the time of the drop
			var region = DDM.interactionInfo.sourceRegion;

			// Check to see if we are over the source element's location.  We will
			// append to the bottom of the list once we are sure it was a drop in
			// the negative space (the area of the list without any list items)
			if (!region.intersect(pt)) {
				var destEl = Dom.get(id);
				var destDD = DDM.getDDById(id);
				destEl.appendChild(this.getEl());
				destDD.isEmpty = false;
				DDM.refreshCache();
			}

		}
		// Be sure to update the data and the datatable to reflect the new
		// order of the items.

		oBook.afterDragDrop(id, this.draggingID, this.dragFrom);

	},

	// ----------------------------
	// Function: onDrag()
	//
	// Called while we are dragging. Determine which direction the mouse is moving
	// in relation to the objects on the page: up (towards the front of the list)
	// or down (towards the end of the list). This is important to decide where
	// to make the space to show on the page where the dropped item will go.
	//
	// Arguments
	//    e - The Event Object
	//
	// Return Value / Effect
	//    N/A (too hard to explain)
	// ----------------------------
	onDrag: function(e) {

		// Keep track of the direction of the drag for use during onDragOver
		var y = Event.getPageY(e);
		var x = Event.getPageX(e);

		if (y < this.lastY || x < this.lastX) {
			this.goingUp = true;
		} else if (y > this.lastY || x > this.lastX){
			this.goingUp = false;
		}

		this.lastX = x;
		this.lastY = y;
	},

	// ----------------------------
	// Function: onDragOver()
	//
	// Places a spacer of some sort into the list to show where the element will
	// go when it's dropped.
	//
	// Arguments
	//    e - The Event Object
	//    id - The ID of the thing that's being dropped (I think)
	//
	// Return Value / Effect
	//    N/A (too hard to explain)
	// ----------------------------
	onDragOver: function(e, id) {

		var srcEl = this.getEl();
		var destEl = Dom.get(id);

		// We are only concerned with list items, we ignore the dragover
		// notifications for the list.
		if (destEl.nodeName.toLowerCase() == "li") {
			var orig_p = srcEl.parentNode;
			var p = destEl.parentNode;

			if (this.goingUp) {
				p.insertBefore(srcEl, destEl); // insert above
			} else {
				p.insertBefore(srcEl, destEl.nextSibling); // insert below
			}

			DDM.refreshCache();
		}
	}
});


//
// IMPORTANT! Drag-Drop on the data table is disabled
//
// ----------------------------
// Object: DD PAGES LIST
//
// We need to handle dragging and dropping and this is the best way we
// knwow how, which is to add globally accessible code that extends the
// YAHOO drag and drop methods for both the list and the thumbnails.
// ----------------------------
//
// YAHOO.macaw.ddDataTable = function(id, sGroup, config) {
// 	YAHOO.macaw.ddDataTable.superclass.constructor.call(this, id, sGroup, config);
// 	Dom.addClass(this.getDragEl(),"custom-class");
// 	this.goingUp = false;
// 	this.lastY = 0;
// 	this.dropEl = null;
// };
//
// YAHOO.extend(YAHOO.macaw.ddDataTable, YAHOO.util.DDProxy, {
// 	proxyEl: null,
// 	srcEl:null,
// 	srcData:null,
// 	srcIndex: null,
// 	tmpIndex:null,
//
// 	startDrag: function(x, y) {
// 		var proxyEl = this.proxyEl = this.getDragEl(), srcEl = this.srcEl = this.getEl();
//
// 		this.srcData = oBook.objDataTable.getRecord(this.srcEl).getData();
// 		this.srcIndex = srcEl.sectionRowIndex;
// 		// Make the proxy look like the source element
// 		Dom.setStyle(srcEl, "visibility", "hidden");
// 		proxyEl.innerHTML = "<table id=\"ddproxy\"><tbody><tr>"+srcEl.innerHTML+"</tr></tbody></table>";
// 	},
// 	endDrag: function(x,y) {
// 		var position, srcEl = this.srcEl;
//
// 		Dom.setStyle(this.proxyEl, "visibility", "hidden");
// 		Dom.setStyle(srcEl, "visibility", "");
// 	},
// 	onDrag: function(e) {
// 		// Keep track of the direction of the drag for use during onDragOver
// 		var y = Event.getPageY(e);
//
// 		if (y < this.lastY) {
// 			this.goingUp = true;
// 		} else if (y > this.lastY) {
// 			this.goingUp = false;
// 		}
//
// 		this.lastY = y;
// 	},
// 	onDragDrop: function(e, id) {
// 		oBook.afterDragDrop('datatable', id);
// 	},
// 	onDragOver: function(e, id) {
// 		// Reorder rows as user drags
// 		var srcIndex = this.srcIndex,
// 			destEl = Dom.get(id),
// 			destIndex = destEl.sectionRowIndex,
// 			tmpIndex = this.tmpIndex;
//
// 		if (destEl.nodeName.toLowerCase() === "tr") {
// 			// Get the index of the page corresponding to the ID of the
// 			// table row we're about to delete
// 			var pagesIdx = null;
// 			if(tmpIndex !== null) {
// 				pagesIdx = oBook._getIndexFromDataTable(oBook.objDataTable.getRecord(tmpIndex)._sId);
// 				oBook.objDataTable.deleteRow(tmpIndex);
// 			} else {
// 				pagesIdx = oBook._getIndexFromDataTable(oBook.objDataTable.getRecord(this.srcIndex)._sId);
// 				oBook.objDataTable.deleteRow(this.srcIndex);
// 			}
//
// 			var r = oBook.objDataTable.addRow(this.srcData, destIndex);
// 			this.tmpIndex = destIndex;
// 			oBook.pages[pagesIdx].dataTableID = oBook.objDataTable.getRecord(destIndex)._sId
//
// 			DDM.refreshCache();
// 		}
// 	}
// });


