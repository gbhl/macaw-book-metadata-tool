// ------------------------------
// GENERAL LIBRARY
//
// This holds a few functions that may be needed everywhere and don't necessarily
// pertain to one type of object on the page. In the case of the manifying glass
// stuff, arguably we should place that into the Book object.
//
// Revision History
//     2010/08/06 JMR - Created, initial coding completed.
// ------------------------------

(function() {

	Login = {

		// ----------------------------
		// Function: init()
		//
		// Initializes the login page by building and showing the login window.
		//
		// Arguments
		//    None
		//
		// Return Value / Effect
		//    The login box is displayed on the page.
		//
		// TODO: Make sure <enter> submits the login form on all browsers.
		// ----------------------------
		init: function() {
			if (!YAHOO.macaw.login) {
				// Initialize the  Panel
				/*YAHOO.macaw.login =
						new YAHOO.widget.Dialog("login",
												{ width: "300px",
												  xy: [500,250],
												  close: false,
												  draggable: false,
												  zindex:4,
												  modal: false,
												  visible: false,
												  preventcontextoverlap: true,
												  postmethod: "form",
												  buttons: [ { text: "Login", handler: Login.handleLoginSubmit, isDefault: true } ]
												}
											);
*/
			MessageBox.init();
			YAHOO.macaw.login =
						new YAHOO.widget.Dialog("login",
												{ 
												  close: false,
												  draggable: false,
												  zindex:4,
												  modal: false,
												  visible: false,
												  preventcontextoverlap: false,
												  postmethod: "form",
												  buttons: [ { text: "Login", handler: Login.handleLoginSubmit, isDefault: true } ]
												}
											);
				var keyLogin = new YAHOO.util.KeyListener(
					document,
					{ keys: 13 },
					{ fn: Login.handleLoginSubmit,
					  scope: YAHOO.macaw.login,
					  correctScope: true }
				);
				YAHOO.macaw.login.cfg.queueProperty("keylisteners", keyLogin);

				YAHOO.macaw.login.setHeader("Login to Macaw");
				YAHOO.macaw.login.setBody(Dom.get('logincontenttemplate').innerHTML);
				YAHOO.macaw.login.render(Dom.get('logincontent'));
			}

			// Show the Panel
			YAHOO.macaw.login.show();
			Login.centerLogin();
			YAHOO.util.Event.addListener(window, 'resize', Login.centerLogin);
		},

		// ----------------------------
		// Function: centerLogin()
		//
		// Keeps the login box centered on the page, more or less
		//
		// Arguments
		//    None
		//
		// Return Value / Effect
		//    The login window is always centered in the whitespace on the right
		//    side of the window.
		// ----------------------------
		centerLogin: function(ev) {
			var rgBD = Dom.getRegion('bd');
			var total = rgBD.right - rgBD.left;

			var rgTH = Dom.getRegion('thumbs');
			var left = rgTH.right - rgTH.left;

			var width = new Number(total - left);
			var new_left = new Number(((width - 300) / 2) + left + 20).toFixed() ;
			Dom.setX("login_c", new_left);
			if (ev) {
				YAHOO.util.Event.stopEvent(ev);
			}
		},

		// ----------------------------
		// Function: handleLoginSubmit()
		//
		// Submits the login form when the OK button is clicked.
		//
		// Arguments
		//    None
		//
		// Return Value / Effect
		//    The login page is submitted and the page reloads.
		// ----------------------------
		handleLoginSubmit: function() {
			Dom.get('loginform').submit();
		}

	}

	General = {
		message: null,
		pleaseWait: null,

		// ----------------------------
		// Function: showErrorMessage()
		//
		// When we want to display a warning message to the user, we call this
		// function to make it display. This uses a standard YUI simple dialog box.
		// This box has an orange title bar to indicate error status. :)
		//
		// Arguments
		//    sWords - What do we want to say to the user.
		//
		// Return Value / Effect
		//    Dialogbox is show.
		// ----------------------------
		showErrorMessage: function(sWords) {
			var handleOK = function () {
				General.message.cancel();
				General.message.destroy();
				General.divDelete('message_mask');
				General.divDelete('message_c');
			}

			General.message = new YAHOO.widget.SimpleDialog("errorDialog", {
				width: "400px",
				visible: false,
				draggable: false,
				fixedcenter: true,
				modal: true,
				underlay: 'none',
				buttons: [ { text:'Ok', handler:handleOK, isDefault: true }]
				}
			);

			General.message.setHeader("Warning");
			General.message.setBody(sWords);
			General.message.render(document.body);
			General.message.show();
		},

		// ----------------------------
		// Function: showMessage()
		//
		// When we want to display a warning message to the user, we call this
		// function to make it display. This uses a standard YUI simple dialog box.
		//
		// Arguments
		//    sWords - What do we want to say to the user.
		//
		// Return Value / Effect
		//    Dialogbox is show.
		// ----------------------------
		showMessage: function(sWords) {
			var handleOK = function () {
				General.message.cancel();
				General.message.destroy();
				General.divDelete('message_mask');
				General.divDelete('message_c');
			}

			General.message = new YAHOO.widget.SimpleDialog("messageDialog", {
				width: "320px",
				visible: false,
				draggable: false,
				fixedcenter: true,
				modal: true,
				underlay: 'none',
				zIndex: 10,
				buttons: [ { text:'Ok', handler:handleOK, isDefault: true }]
				}
			);

			General.message.setHeader("Message from Macaw");
			General.message.setBody(sWords);
			General.message.render(document.body);
			General.message.show();
		},

		// ----------------------------
		// Function: showYesNo()
		//
		// When we want to display a Yes/No question to the user, we call this
		// function to make it display. This uses a standard YUI simple dialog box.
		//
		// Arguments
		//    sWords - What do we want to say to the user.
		//
		// Return Value / Effect
		//    Dialogbox is show.
		// ----------------------------
		showYesNo: function(sWords, yesCallback) {
			var handleNO = function () {
				General.message.cancel();
				General.message.destroy();
				General.divDelete('message_mask');
				General.divDelete('message_c');
			}

			General.message = new YAHOO.widget.SimpleDialog("messageDialog", {
				width: "320px",
				visible: false,
				draggable: false,
				fixedcenter: true,
				modal: true,
				underlay: 'none',
				zIndex: 10,
				buttons: [ 
					{ text:'Yes', handler:yesCallback }, 
					{ text:'No', handler:handleNO } 
				]}
			);

			General.message.setHeader("Question from Macaw");
			General.message.setBody(sWords);
			General.message.render(document.body);
			General.message.show();
		},

		// ----------------------------
		// Function: divDelete()
		//
		// Delets a DIV from the page, including all its children.
		// This is used by the OK button callback handler on the showErrorMessage()
		// dialog window to properly clean up after itself (in chrome, there
		// may be a bug?)
		//
		// Arguments
		//    id - The id of something to delete.
		//
		// Return Value / Effect
		//    Poof! No more DIV.
		// ----------------------------
		divDelete: function(id) {
			var el = document.getElementById(id);
			if (el) {
				if (el.parentNode) {
					el.parentNode.removeChild(el)
				}
			}
		},
		// ----------------------------
		// Function: openHelp
		//
		// Show the help window. Easy enough!
		//
		// Arguments
		//    None
		//
		// Return Value / Effect
		//    Popup window appears
		// ----------------------------
		openHelp: function() {
			window.open(sBaseUrl+'/help','help_window','height=700,width=300,status=no,location=no,menubar=no,resizable=yes,toolbar=no');
		},

		// ----------------------------
		// Function: showMagnifier()
		//
		// Event handler - When the preview image is clicked, show the
		// "magnifying glass". Hiding the magnifiying glass happens elsewhere.
		//
		// Arguments
		//    None
		//
		// Return Value / Effect
		//    The magnifying glass widget is shown over the preview image
		// ----------------------------
		showMagnifier: function() {
			if (Dom.get('preview_img').src != imgSpacer.src) {
				Scanning.magnifier.init(
					Dom.getAttribute('preview_img','src'),
					'preview_img'
				);
			}
			return false;
		},
		
		runCronAction: function(action) {	
			// This is the callback to handle the saving of the data.
			var runCallback = {
				success: function (o){
					eval('var r = '+o.responseText);
					if (r.redirect) {
						window.location = r.redirect;
					} else {
						if (r.error) {
							General.showErrorMessage(r.error);
						} else if (r.message) {
							General.showMessage(r.message);
						}
					}
				},
				failure: function (o){
					General.showErrorMessage('There was a problem starting the cron job. If it helps, the error was:<blockquote style="font-weight:bold;color:#990000;">'+o.statusText+"</blockquote>");
				},
				scope: this
			};
			var transaction = YAHOO.util.Connect.asyncRequest('GET', sBaseUrl+'/admin/cron/'+action, runCallback);
		}
	}

})();
