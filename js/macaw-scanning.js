
// ----------------------------
// SCANNING LIBRARY
//
// This module contains functions that are central to the scanning and review
// process. These don't belong in any one object, so they are encapsulated here.
//
// Revision History
//     2010/08/06 JMR - Created, initial coding completed.
// ----------------------------

(function() {

	// The scanning object
	Scanning = {
		missingPages: false,
		importStarted: false,
		obtnFinished: null,
		obtnBack: null,
		metadataModules: new Array,

		// ----------------------------
		// Object: Magnifier "glass"
		//
		// This is used to zoom in on a portion of the
		// large version of the page.
		//
		// Arguments
		//
		// Return Value / Effect
		//
		// ----------------------------
		magnifier: {
			vars: {
				img_id : '',
				exists : false
			},
			init: function(big_img,div_id) {
				if (!Scanning.magnifier.vars.exists) {
					Scanning.magnifier.vars.img_id = div_id;
					new YAHOO.widget.ImageMagnifier(Scanning.magnifier.vars.img_id, big_img);
					Dom.setStyle(Scanning.magnifier.vars.img_id, 'cursor', 'default');
					Scanning.magnifier.vars.exists = true;
				} else {
					Scanning.magnifier.kill();
				}
			},
			kill: function() {
				Dom.getElementsByClassName('magnifier', 'div', document.body, function(o) {document.body.removeChild(o);});
				Dom.setStyle(Scanning.magnifier.vars.img_id, 'cursor', "url('"+sBaseUrl+"/inc/magnifier/assets/mag-cursor.gif'),auto");
				Scanning.magnifier.vars.exists = false;
			}
		},
		
		// ----------------------------
		// Function:
		//
		// Arguments
		//
		// ----------------------------
		initInsertMissing: function() {
			YAHOO.util.Event.addListener(window, "resize", Scanning.resizeMissingWindow);

			// Create our book object.
			oBook = new YAHOO.macaw.Book();

			// This callback is used when loading the data. It basicallly sends
			// the data to the book object and then renders the book on the page.
			var loadDataCallback = {
				success: function (o){
					eval('var r = '+o.responseText);
					if (r.redirect) {
						window.location = r.redirect;
					} else {
						if (r.error) {
							General.showErrorMessage(r.error);
						} else {
							oBook.load(r, true);
							oBook.render('thumbs', null);
							oBook.zoom(27);
							Scanning.resizeMissingWindow();
							onBookLoaded.fire();
						}
					}
				},
				failure: function (o){
					General.showErrorMessage('There was a problem retrieving the metadata for the non-missing pages. Please try reloaing the page. If it helps, the error was:<blockquote style="font-weight:bold;color:#990000;">'+o.statusText+"</blockquote>");
				},
				scope: this
			};

			// Call the URL to get the data
			var transaction = YAHOO.util.Connect.asyncRequest('GET', sBaseUrl+'/scan/get_thumbnails/non_missing', loadDataCallback, null);

			oBookMissing = new YAHOO.macaw.Book();

			// This callback is used when loading the data. It basically sends
			// the data to the book object and then renders the book on the page.
			var loadDataCallback2 = {
				success: function (o){
					eval('var r = '+o.responseText);
					if (r.redirect) {
						window.location = r.redirect;
					} else {
						if (r.error) {
							General.showErrorMessage(r.error);
						} else {
							oBookMissing.load(r, true);
							oBookMissing.render('thumbs_missing', null);
							oBookMissing.zoom(27);
							Scanning.resizeMissingWindow();
							onBookLoaded.fire();
						}
					}
				},
				failure: function (o){
					General.showErrorMessage('There was a problem retrieving the metadata for the missing pages. Please try reloaing the page. If it helps, the error was:<blockquote style="font-weight:bold;color:#990000;">'+o.statusText+"</blockquote>");
				},
				scope: this
			};

			// Call the URL to get the data
			var transaction = YAHOO.util.Connect.asyncRequest('GET', sBaseUrl+'/scan/get_thumbnails/missing', loadDataCallback2, null);

			// Set up the rest of the screen: set up the buttons
			Scanning.obtnFinished = new YAHOO.widget.Button("btnFinished");
			Scanning.obtnFinished.on("click", oBook.finishLater, null, oBook);
			//Removed initial disabling - re-enabling appears broken and can't workout why.
			//Scanning.obtnFinished.set('disabled', true);

			var obtnCancel = new YAHOO.widget.Button("btnCancel");
			//changed cancel button to go to scan/review instead of main/manage
			obtnCancel.on("click", function(o) { window.location = sBaseUrl+'/scan/review'; });

			// Set up the zoom slider and intialize it
			var onSliderChange = function (wd) {
				oBook.zoom(wd);
				Scanning.resizeMissingWindow();
			};
			YAHOO.macaw.slider = YAHOO.widget.Slider.getHorizSlider("sliderbg", "sliderthumb", 0, 100);
			YAHOO.macaw.slider.subscribe('change', onSliderChange);
  			YAHOO.macaw.slider.setValue(27);

			// Just in case we need it, set up a YUI logging window
			// var myLogReader = new YAHOO.widget.LogReader();

			// For starters, make sure the layout of the window is proper
			Scanning.resizeMissingWindow();
		},

		// ----------------------------
		// Function: Initialize the Review Page
		//
		// Sets up the review page, gets the data, creates the buttons and
		// other object and, creates our book object that handles everything.
		//
		// Arguments
		//
		// Return Value / Effect
		//
		// ----------------------------
		initReview: function() {
			YAHOO.util.Event.addListener(window, "resize", Scanning.resizeWindow);
			YAHOO.util.Event.on(window, 'resize', Scanning.magnifier.kill);
			MessageBox.init();

			// Create our book object.
			oBook = new YAHOO.macaw.Book();

			// This callback is used when loading the data. It basicallly sends
			// the data to the book object and then renders the book on the page.
			var loadDataCallback = {
				success: function (o){
					eval('var r = '+o.responseText.replace(/&amp;/g, '&'));
					if (r.redirect) {
						window.location = r.redirect;
					} else {
						if (r.error) {
							General.showErrorMessage(r.error);
						} else {
							oBook.load(r, false);
							oBook.render('thumbs','list');
							onBookLoaded.fire();
						}
					}
				},
				failure: function (o){
					General.showErrorMessage('There was a problem retrieving the metadata for the pages. Please try reloaing the page. If it helps, the error was:<blockquote style="font-weight:bold;color:#990000;">'+o.statusText+"</blockquote>");
				},
				scope: this
			};

			// Call the URL to get the data
			var transaction = YAHOO.util.Connect.asyncRequest('GET', sBaseUrl+'/scan/get_thumbnails', loadDataCallback, null);

			// Set up the rest of the screen: set up the buttons
			events = Event.getListeners(document.getElementById('btnSave'));
			var obtnSave = new YAHOO.widget.Button("btnSave");
			obtnSave.on("click", oBook.save, null, oBook);
			for (e in events) {
				obtnSave.on(events[e].type, events[e].fn);
			}

			var obtnFinished = new YAHOO.widget.Button("btnFinished");
			obtnFinished.on("click", oBook.finish, null, oBook);

			//var obtnFinishLater = new YAHOO.widget.Button("btnFinishLater");
			//obtnFinishLater.on("click", oBook.finishLater, null, oBook);

			var obtnToggle = new Elem('btnToggle');
			obtnToggle.on("click", Scanning.togglePreview, null, oBook);

			// Set up the rest of the screen: set up the thumbnails area and its onClick event.
			var oThumbs = new Elem('thumbs');
			oThumbs.on("click", oBook.thumbsClick, null, oBook);

			// Set up the Thumbnails / List buttons to toggle between
			YAHOO.macaw.toggleThumbsList = new YAHOO.widget.ButtonGroup("btnToggleListThumbs");
			YAHOO.macaw.toggleThumbsList.on("click", Scanning.toggleListThumbs);

			// Set up the zoom slider and intialize it
			var onSliderChange = function (wd) {
				oBook.zoom(wd);
			};
			YAHOO.macaw.slider = YAHOO.widget.Slider.getHorizSlider("sliderbg", "sliderthumb", 0, 100);
			YAHOO.macaw.slider.subscribe('change', onSliderChange);
			YAHOO.macaw.slider.setValue(27);

			// Set the style on the preview image to show a magnifying glass icon
			Dom.setStyle('preview_img', 'cursor', "url('"+sBaseUrl+"/inc/magnifier/assets/mag-cursor.gif'), auto");

			// Just in case we need it, set up a YUI logging window
			// var myLogReader = new YAHOO.widget.LogReader();
			if (Scanning.metadataModules.length > 1) {
				Scanning.metadataTabs = new YAHOO.widget.TabView("metadata_tabs");
				// When we switch tabs, adjust the heights of the window.
				for (i = 0; i < Scanning.metadataModules.length; i++) {
					var tab = Scanning.metadataTabs.getTab(i);
					tab.addListener('click', Scanning.resizeWindow);
				}
			}

			// For starters, make sure the layout of the window is proper
			Scanning.resizeWindow();

			// Auto-save the metadata every 2 minutes
			timeoutAutoSave = setTimeout("Scanning.autoSaveTimeout();", 120000);
		},

		// ----------------------------
		// Function: Automatically save the metadata
		//
		// Call the oBook.save routine every 2 minutes to save the metadata.
		// This also serves to keep the session active, but that's an odd side
		// effect.
		//
		// Arguments
		//
		// ----------------------------
		autoSaveTimeout: function() {
			oBook.save(null, false, false, true);
			timeoutAutoSave = setTimeout("Scanning.autoSaveTimeout();", 120000);
		},


		// ----------------------------
		// Function: Toggle list or thumbnails
		//
		// This callback function switches between the thumbnail view and
		// list view of the review page.
		//
		// Arguments
		//
		// Return Value / Effect
		//
		// ----------------------------
		toggleListThumbs: function() {
			var val = YAHOO.macaw.toggleThumbsList.get('value');

			if (val == 'Ls') {
				Dom.setStyle('list','display','block');
				Dom.setStyle('extra','display','none');
				Dom.setStyle('thumbs','display','none');
				Dom.setStyle('slider','display','none');
			} else if (val == 'Ex') {
				Dom.setStyle('list','display','none');
				Dom.setStyle('thumbs','display','none');
				Dom.setStyle('extra','display','block');
				Dom.setStyle('slider','display','none');
			} else {
				Dom.setStyle('list','display','none');
				Dom.setStyle('thumbs','display','block');
				Dom.setStyle('extra','display','none');
				Dom.setStyle('slider','display','block');
			}
		},

		// ----------------------------
		// Function: Toggle preview (large image)
		//
		// This shows or hides the large preview of the selected thumbnail image
		// as well as the metadata, allowing us to expand the window to show
		// the thumbnails or the list in the full window. Triggered by the small
		// blue arrow button in the upper-left of the preview image.
		//
		// Arguments
		//
		// Return Value / Effect
		//
		// ----------------------------
		togglePreview: function() {
			var pvw = Dom.getStyle('preview', 'display');
			if (pvw == 'none') {
				Dom.setStyle('preview', 'display', 'block');
				Dom.setStyle('metathumbsection', 'width', '66%');
				Dom.setStyle('metadata', 'display', 'block');
 				Dom.get('btnToggle').src = imgToggleRight.src;
 				Dom.setAttribute('btnToggle', 'alt', 'Show preview and metadata');
			} else {
				Dom.setStyle('preview', 'display', 'none');
				Dom.setStyle('metathumbsection', 'width', '98.5%');
				Dom.setStyle('metadata', 'display', 'none');
 				Dom.get('btnToggle').src = imgToggleLeft.src;
 				Dom.setAttribute('btnToggle', 'alt', 'Hide preview and metadata');
			}
			Scanning.resizeWindow();
		},

		// ----------------------------
		// Function: Resize window event handler
		//
		// This is called whenever the window is resized. It adjusts the height
		// and width of the preview image as well as the height of the
		// thumbnails/list area to ensure that the metadata never flows off
		// of the page. Also called in a few cases when setting metadata since
		// some fields can cause its area to grow.
		//
		// Arguments
		//
		// Return Value / Effect
		// 
		//SCS - changed the constant to be subtraced for new window height. Also, setting 
		// the height of the preview window to match the height of the thumbnail height.
		// ----------------------------
		resizeWindow: function() {
			var pvw = Dom.getStyle('preview', 'display');
			if (pvw == 'none') {
				/* Set the height of the thumbs and metadata */
				var intHeight = Dom.getViewportHeight() -
								Dom.get('hd').clientHeight -
								200;

				Dom.setStyle('thumbs', 'height', intHeight+'px');
				Dom.setStyle('list', 'height', intHeight+'px');
				Dom.setStyle('extra', 'height', intHeight+'px');

				Dom.setX('save_buttons', Dom.getX('sliderbg') - Dom.get('save_buttons').clientWidth - 20 - 16);
				Dom.setY('save_buttons', Dom.getY('sliderbg') + 2);

			} else {
				/* Set the height of the thumbs and metadata */

				var intHeight = Dom.getViewportHeight() -
								Dom.get('banner').clientHeight -
								Dom.get('main-menu').clientHeight -
								Dom.get('metadata').clientHeight -
								110;

				Dom.setStyle('thumbs', 'height', intHeight+'px');
				Dom.setStyle('list', 'height', intHeight+'px');
				Dom.setStyle('extra', 'height', intHeight+'px');
// 				Dom.setStyle('preview', 'height', intHeight +'px');

				var buttonWidths = Dom.get('btnSave').clientWidth + Dom.get('btnFinished').clientWidth + 26;
				Dom.setStyle('save_buttons', 'width', buttonWidths+'px');
				Dom.setStyle('save_buttons', 'padding-left', ((Dom.get('preview').clientWidth - buttonWidths) / 2)+'px');
				Dom.setX('save_buttons', Dom.getX('preview'));
				Dom.setY('save_buttons', Dom.getViewportHeight() - Dom.get('save_buttons').clientHeight  - 20);
			}

			/* Set the height of the splitter line */
			intHeight = Dom.getViewportHeight() -
						Dom.get('btnToggle').clientHeight -
						Dom.get('hd').clientHeight - 40;
			Dom.setStyle('line', 'height', intHeight+'px');
		},

		// ----------------------------
		// Function:
		//
		// Arguments
		//
		// Return Value / Effect
		//
		// ----------------------------
		resizeMissingWindow: function() {
			/* Set the height of the thumbs and metadata */
			var intHeight = Dom.getViewportHeight() -
							Dom.get('hd').clientHeight -
							Dom.get('new_thumbs_header').clientHeight -
							Dom.get('new_thumbs').clientHeight -
							Dom.get('controls').clientHeight -
							187;

			var thumbs = Dom.getElementsByClassName('thumb', 'li', 'thumbs_missing');
			if (thumbs.length > 0) {
				missingwidth = thumbs.length * (thumbs[0].clientWidth + 5);
				if (missingwidth < Dom.getViewportWidth()) {
					missingwidth = Dom.getViewportWidth();
				}
			} else {
				missingwidth = Dom.getViewportWidth();
			}
			Dom.setStyle('thumb_scroller','width', missingwidth+'px');
			Dom.setStyle('thumbs', 'height', intHeight+'px');


		}
	};

})();
