// ------------------------------
// METADATA OBJECT
//
// This represents the metadata for a single page in our book. It also handles
// displaying (or not) the data in the fields on the page as required. This
// does not save the data to the database, that is done on the book object itself.
// This also handles the interaction with the individual customizable metadata
// modules, specifically when getting or setting data in the modules.
//
// Parameters
//    parent - Just in case we need it, the page object that contains us.
//    data - The data that makes up the entire metadata for this page.
//
// Revision History
//     2010/08/06 JMR - Created, initial coding completed.
// ------------------------------

YAHOO.macaw.Metadata = function(parent, data, mdModules) {
	this.sequence = null;
	this.parent = parent;
	this.filebase = null;
	this.rendered = false;
	this.changed = new YAHOO.util.CustomEvent('changed', this);
	
	// Set the stuff from the data parameter into properties on us
	if (data.sequence_number)      this.sequence = data.sequence_number;
	if (data.filebase)             this.filebase = data.filebase;

	// Save the info about our customizable metadata modules, instantiate
	// the object and store it here.
	this.metadataModules = mdModules;
	this.modules = new Array();

	for (var m in this.metadataModules) {
		eval('var mod = new YAHOO.macaw.'+mdModules[m]+'(this, data);');
		mod.init();
		this.modules.push(mod);
	}

	// ----------------------------
	// Function: render()
	//
	// When one item is selected, then we need to enable and display the
	// metadata. This function does that, callign various internal
	// this._render(something) functions. Also sets our rendered flag to true
	// in case we need to reference it.
	//
	// Arguments
	//    None
	//
	// Return Value / Effect
	//    Fields are filled, but enabled
	// ----------------------------
	this.render = function() {
		if (!this.parent.parent.parent.addingMissingPages) {
			Dom.get('sequence_number').innerHTML = 'for Sequence Number '+this.sequence;
			for (var m in this.modules) {
				this.modules[m].render();
			}

			this.rendered = true;
		}
	}

	// ----------------------------
	// Function: unrender()
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
	this.unrender = function() {
		if (!this.parent.parent.parent.addingMissingPages) {
			Dom.get('sequence_number').innerHTML = '';
			for (var m in this.modules) {
				this.modules[m].unrender();
			}

			this.rendered = false;
		}
	}


	// ----------------------------
	// Function: getSaveData()
	//
	// Get the data for this one page from all of the metadata modules that is going to be sent
	// back to the server. This may look the same as the getTableData() function, but it's not.
	//
	// Arguments
	//    None
	//
	// Return Value / Effect
	//    Object (associative array) of data.
	// ----------------------------
	this.getSaveData = function() {
		var results = {};

		// Cycle through all of the metadata modules
		for (var m in this.modules) {
			var data = this.modules[m].getData();
			for (var i in data) {
				results[i] = data[i];
			}
		}
		// Return an object of name-value pairs. Values may be an array of similar items (i.e. Page Type)
		return results;
	}

	// ----------------------------
	// Function: getTableData()
	//
	// Get the data for this one page from all of the metadata modules to be placed into the
	// data table (list view). This may look the same as the getSaveData() function, but it's not.
	// Also makes sure that the return object contains the filebase, sequence and thumbnail URL.
	//
	// Arguments
	//    None
	//
	// Return Value / Effect
	//    Object (associative array) of data.
	// ----------------------------
	this.getTableData = function() {
		var results = new Object();

		// Cycle through all of the metadata modules
		for (var m in this.modules) {
			var data = this.modules[m].getTableData();
			for (var i in data) {
				results[i] = data[i];
			}
		}
        results.filebase = this.filebase;
        results.sequence = this.sequence;
        results.thumbnail = this.parent.urlThumbnail;
		// Return an object of name-value pairs. Values may be an array of similar items (i.e. Page Type)
		return results;
	}

	// ----------------------------
	// Function: getTableColumns()
	//
	// Get the descriptors from the individual metadata modules. If the module does not
	// implement a getTableColumns() function, then it's not called. Coallates all of the
	// results into an array.
	//
	// Arguments
	//    None
	//
	// Return Value / Effect
	//    Array of objects suitable for the ColumnDefs of a YUI data table.
	// ----------------------------
	this.getTableColumns = function() {
		var results = new Array();

		// Cycle through all of the metadata modules and get the info for each
		for (var m in this.modules) {
			if (this.modules[m]['getTableColumns']) {
				results = results.concat(this.modules[m].getTableColumns());
			}
		}
		return results;
	}

	// ----------------------------
	// Function: callFunction()
	//
	// This is a generic entry-point to call an arbitrary method on an arbitrary
	// metadata module. We don't really care if or who implements the function
	// so we cycle through all of the metadata modules to see if the method
	// is implemented and if so, it's called. We also handle an arbitrary
	// number of arguments to be passed to the metadata method.
	//
	// Arguments
	//    Variable Arguments. The first argument is the method to be called.
	//    Remaining arguments are passed into the method when it's called.
	//
	// Return Value / Effect
	//    None, neither success nor failure
	// ----------------------------
	this.callFunction = function() {
		// Make our arguments into a real array so we can use array functions, like shift.
		var args = Array.prototype.slice.call(arguments);
		// Get the first item in the arguments
		var method = args.shift();

		for (var m in this.modules) {
			if (this.modules[m][method]) {
				// Isn't this pretty? It works, let's hope it works in all browsers.
				// TODO: Test this piece of code in all modern browsers.
				this.modules[m][method].apply(this.modules[m], args);
			}
		}
	}

}
