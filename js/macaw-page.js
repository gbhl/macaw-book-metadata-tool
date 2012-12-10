// ------------------------------
// PAGE OBJECT
//
// This represents one page in our book. Tells us if we're selected and provides
// a property to the metadata object that handles all of the data for the page.
// We also keep track of the ID of the page (ultimately from the database) and
// the item_id that the page belongs to, for convenience. Other fields that we
// keep track of are our parent object (The book) and the URLs to the thumbnail
// and preview images.
//
// Parameters
//    parent - Just in case we need it, the page object that contains us.
//    data - The data that makes up the entire metadata for this page.
//
// Revision History
//     2010/08/06 JMR - Created, initial coding completed.
// ------------------------------

YAHOO.macaw.Page = function(parent, data, mdModules) {

	this.pageID = data.id;
	this.itemID = data.item_id;
	this.urlThumbnail = data.thumbnail;
	this.urlPreview = data.preview;
	this.metadata = new YAHOO.macaw.Metadata(this, data, mdModules);
	this.parent = parent;
	this.isMissing = data.is_missing;

	this.selected = false;
	this.highlighted = false;
	this.rendered = false;

	this.thumbnailID = null;
	this.dataTableID = null;
	this.elemDataTableRow = null;
	this.elemThumbnailLI = null;
	this.elemThumbnailIMG = null;
	this.imgPreview = null;
	
	this.deleted = false;

	// ----------------------------
	// Function: render()
	//
	// Makes the page appear on the page, creating a thumbnail page element
	// but not adding to the data table. This creates objects using JS DOM
	// methods, instead of using .innerHTML or something simlar.
	//
	// Arguments
	//    none
	//
	// Return Value / Effect
	//    Thumbnail created, click event is being watched for
	// ----------------------------
	this.render = function() {

		// Create the thumbnail LI element
		var new_thumb = Dom.get(document.createElement('li'));
		// NOTE: We don't use any IDs of our own, because we don't care
		// and we always translate between screen elements and array
		// indexes when necessary. But we need some unique ID, so we let
		// YUI create it for us.
		this.thumbnailID = Dom.generateId(new_thumb);
		Dom.addClass(new_thumb, 'thumb');
		if (this.isMissing) {
			Dom.addClass(new_thumb, 'missing');
		}

		// Create the overlay info div element
		var new_info = Dom.get(document.createElement('div'));
		Dom.addClass(new_info, 'info');

		// Create the thumbnail image element
		var new_img = Dom.get(document.createElement('img'));
		Dom.addClass(new_img, 'image');
		Dom.get(new_img).src = this.urlThumbnail;
		//Dom.get(new_img).width = 100;

		// Create the thumbnail caption element
		var new_caption = Dom.get(document.createElement('div'));
		Dom.addClass(new_caption, 'caption');
		new_caption.innerHTML = 'Seq ' + this.metadata.sequence;

		// Hook everything together
		new_thumb.appendChild(new_info);
		new_thumb.appendChild(new_img);
		new_thumb.appendChild(new_caption);
		this.parent.parent.elemThumbnails.appendChild(new_thumb);

		// Remeber some stuff
		this.elemThumbnailLI = new_thumb;
		this.elemThumbnailIMG = new_img;

		// Make the new thumbnail drag-droppable
		// TODO: I don't think this should bve this.parent.select. or even this.parent.parent.select. What does it do?
		// Maybe it should be this.select? No arguments are allowed.
		YAHOO.util.Event.addListener(this.elemThumbnailLI, "click", this.parent.parent.pageClickHandler, null, this.parent.parent);

		var x = new YAHOO.macaw.ddThumbs(new_thumb.id);
		this.rendered = true;

		// If we are highlighted, make sure we appear as such
		if (this.highlighted) {
			this.highlight(); // Seems redundant, but it works
		}

	}

	this.delete = function() {
		this.deleted = true;
		General.divDelete(this.elemThumbnailLI.id);
		Scanning.log(this.pageID, 'PageDeleted', 'DELETED');
	}
	// ----------------------------
	// Function: select()
	//
	// Marks the page as selected. Also highlights us if the page is rendered.
	//
	// Arguments
	//    none
	//
	// Return Value / Effect
	//    select property is set to true.
	// ----------------------------
	this.select = function() {
		if (this.hidden) {
			this.selected = false;
			return;
		}
		// Mark ourselves as selected
		this.selected = true;
		// Do we need to update the window?
		if (this.rendered) {
			// Highlight myself
			this.highlight();
		}
	}

	// ----------------------------
	// Function: unselect()
	//
	// Unselects the page. Also unhighlights the page if we are rendered.
	//
	// Arguments
	//    none
	//
	// Return Value / Effect
	//    select property is set to false.
	// ----------------------------
	this.unselect = function() {
		// Mark ourselves as unselected
		this.selected = false;
		// Do we need to update the window?
		if (this.rendered) {
			// Unhighlight myself, possibly leaving others highlighted
			this.unhighlight();
		}
	}

	// ----------------------------
	// Function: highlight()
	//
	// Highlights the page. This means setting a border around the thumbnail image
	// which relies upon the fact that the render() function sets the
	// .elemThumbnailIMG property. Also selects the row in the data table.
	//
	// Arguments
	//    none
	//
	// Return Value / Effect
	//    Border on thumbnail image, table row selected
	// ----------------------------
	this.highlight = function() {
		if (this.hidden) {
			return;
		}
		// Set highlight around the thumbnail image
		Dom.setStyle(this.elemThumbnailIMG, 'border', '2px solid #000099');
		// Mark ourselves as highlighted
		this.highlighted = true;
		if (this.parent.parent.objDataTable) {
			this.parent.parent.objDataTable.selectRow(this.dataTableID) // Select the table row
		}
	}

	// ----------------------------
	// Function: unhighlight()
	//
	// Removes the highlight around the image, unselects the row in the data table.
	//
	// Arguments
	//    none
	//
	// Return Value / Effect
	//    No border on thumbnail, table row selection status cleared
	// ----------------------------
	this.unhighlight = function() {
		// Mark ourselves as not highlighted
		if (this.isMissing) {
			Dom.setStyle(this.elemThumbnailIMG, 'border', '2px solid #CEE4FF');
		} else {
			Dom.setStyle(this.elemThumbnailIMG, 'border', '2px solid #EEE');
		}
		// Remove the highlight around the thumbnail image
		this.highlighted = false;
		if (this.parent.parent.objDataTable) {
			this.parent.parent.objDataTable.unselectRow(this.dataTableID) // Select the table row
		}
	}

	this.hide = function() {
		this.unselect();
		// get the DOM element for the thumbnail for this page
		// hide it from the page
		Dom.setStyle(this.thumbnailID, 'display', 'none');
		Dom.setStyle(this.dataTableID, 'display', 'none');
		// Mark ourselves as hidden because we don't want to highlight/select ourself when we're hidden
		this.hidden = true;
	}

	this.unhide = function() {
		// get the DOM element for the thumbnail for this page
		// hide it from the page
		Dom.setStyle(this.thumbnailID, 'display', 'block');
		Dom.setStyle(this.dataTableID, 'display', 'table-row');
		// Mark ourselves as not hidden so we can play with everyone else again.
		this.hidden = false;
	}
	
	
	// ----------------------------
	// Function: getMetadataValue()
	//
	// Searches all metadata modules and returns an array of values 
	// for those matching fields..
	//
	// Arguments
	//    the name of the metadata field
	//
	// Return Value / Effect
	//    array, empty or filled
	// ----------------------------
	this.getMetadataValue = function(key) {
		ret = new Array();
		for(i=0; i<this.metadata.modules.length; i++) {
			data = this.metadata.modules[i].getData();
			if (data[key]) {
				if (data[key].length) {
					for(j=0; j<data[key].length; j++) {
						ret.push(data[key][j]);
					}
				} else {
					ret.push(data[key]);				
				}
			}
		}
		return ret;
	}
}

