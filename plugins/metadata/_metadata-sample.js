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

YAHOO.macaw.SAMPLE = function(parent, data) {

	// Intialize the fields that will hold our metadata
	this.data = data;
	this.sequence = data.sequence_number;
	this.parent = parent;
	this.filebase = data.filebase;
	this.pageID = this.parent.parent.pageID;
	// These correspond exactly to the id attributes in the php file
	// The "Type" specifier gives clues to render() and unrender() about
	// how to handle different types of fields.
	YAHOO.macaw.SAMPLE.metadataFields = [
		{ id: 'SAMPLE_name', display_name: 'Name', type: 'text'},
		{ id: 'SAMPLE_type', display_name: 'Type', type: 'select-one'},
		{ id: 'SAMPLE_text', display_name: 'Text', type: 'long-text'}
	];

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
		var fields = YAHOO.macaw.SAMPLE.metadataFields;
		for (f in fields) {
			if (typeof this.data[fields[f].id] != 'undefined') {
				this[fields[f].id] = this.data[fields[f].id];
			} else {
				this[fields[f].id] = null;
			}
		}

		// This is really special. We DO NOT want to call this more than once.
		// So we set our own variable onto the oBook because we can
		// (and which is more or less global, as far as we are concerned)
		if (!oBook.initialized_SAMPLE) {
			// Enter stuff here that should happen exactly once when the page is loaded.
			oBook.initialized_SAMPLE = true;
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

		// Create a new object with only the data that we want to save
		// We COULD send in the entire "this" object, but that's messy.
		var fields = YAHOO.macaw.SAMPLE.metadataFields;
		for (f in fields) {
			data[fields[f].id] = this[fields[f].id];
		}

		return data;
	}

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
		var fields = YAHOO.macaw.SAMPLE.metadataFields;
		for (f in fields) {
			cols.push({
				'key':   fields[f].id,
				'label': fields[f].display_name
			});
		}
		return cols;
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
		// This is almost the same as getData. If any field is something other than
		// a text field, you may want to handle it differently. The data returned here
		// will be converted to a string.
		var data = {};

		// Create a new object with only the data that we want to save
		// We COULD send in the entire "this" object, but that's messy.
		var fields = YAHOO.macaw.SAMPLE.metadataFields;
		for (f in fields) {
			if (fields[f].type == 'long-text') {
				if (this[fields[f].id] != '' && this[fields[f].id] != null) {
					data[fields[f].id] = '<em>(text)</em>';
				}
			} else {
				data[fields[f].id] = this[fields[f].id];
			}
		}

		return data;
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
	this.set = function(field, value, mult) {
		// When we are saving multiple records at once, we DO NOT set anything if the field is empty.
		if (!mult || (mult && !isBlank(value))) {
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
		// Fill in the fields on the page.
		// This uses the YUI Dom object.
		var fields = YAHOO.macaw.SAMPLE.metadataFields;
		for (f in fields) {
			// Text boxes and Textareas are easy
			if (fields[f].type == 'text' || fields[f].type == 'long-text') {
				Dom.get(fields[f].id).value = this[fields[f].id]

			// Select boxes are more difficult, but select-one is pretty simple.
			} else if(fields[f].type == 'select-one') {
				var el = document.getElementById(fields[f].id);
				el.selectedIndex = -1;
				if (this[fields[f].id] && this[fields[f].id] != null && typeof(this[fields[f].id]) != undefined) {
					for (i=0; i < el.options.length; i++) {
						if (el.options[i].text == this[fields[f].id]) {
							el.selectedIndex = i;
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
		// Fill in the fields on the page.
		// This uses the YUI Dom object.
		var fields = YAHOO.macaw.SAMPLE.metadataFields;
		for (f in fields) {
			if (fields[f].type == 'text' || fields[f].type == 'long-text') {
				Dom.get(fields[f].id).value = '';
			} else if(fields[f].type == 'select-one') {
				Dom.get(fields[f].id).selectedIndex = -1;
			}
		}
	}

}


/* ****************************************** */
/* CLASS MEHTODS                              */
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
YAHOO.macaw.SAMPLE.metadataChange = function(obj) {
	// Get the things that are selected
	var pg = oBook.pages.arrayHighlighted();
	// All of these REPLACE the data that's on the object.
	// Only the Page Type and Pieces will APPEND.
	var i;
	// Set an array to accumulate any pageids we modify
	var page_ids = new Array();
	var multiple = (pg.length > 1);
	var fields = YAHOO.macaw.SAMPLE.metadataFields;

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

