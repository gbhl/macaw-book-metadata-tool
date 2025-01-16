// ----------------------------
// MACAW - Metadata Collection and Workflow
//
// Revision History
//     2010/08/06 JMR - Created, initial coding completed.
// ----------------------------
YAHOO.namespace("macaw"); // This is required in all JS files for some reason
YAHOO.widget.Chart.SWFURL = sBaseUrl+"/inc/swf/charts.swf";

YAHOO.widget.DataTable.formatLink = function(elLiner, oRecord, oColumn, oData) { 
	var barcode = YAHOO.lang.escapeHTML(oData.replace(/\\'/g, "'")); 
	elLiner.innerHTML = "<a href=\"" + sBaseUrl + "/main/managebarcode/" + barcode + "/\">" + barcode + "</a>"; 
};

// Some variables that come in handy for not beating up the server

// Image Cache of commonly referenced images or icons that change a lot
var imgSpacer = new Image;
imgSpacer.src = sBaseUrl+'/images/spacer.gif';
var imgMultiSelect = new Image;
imgMultiSelect.src = sBaseUrl+'/images/multiselect.png';
var imgToggleRight = new Image;
imgToggleRight.src = sBaseUrl+'/images/icons/resultset_next.png';
var imgToggleLeft = new Image;
imgToggleLeft.src = sBaseUrl+'/images/icons/resultset_previous.png';
var imgClear = new Image;
imgClear.src = sBaseUrl+'/images/icons/page_white_delete_grey.png';
var imgClearOver = new Image;
imgClearOver.src = sBaseUrl+'/images/icons/page_white_delete.png';

// Make some abbreviations that just make our life easier
var Dom = YAHOO.util.Dom;
var Elem = YAHOO.util.Element;
var Event = YAHOO.util.Event;
var DDM = YAHOO.util.DragDropMgr;
var JSON = YAHOO.lang.JSON;
var Lang = YAHOO.lang;

Event.throwErrors = true;

// Our main object that describes the book we are currently editing
var oBook;
var onBookLoaded = new YAHOO.util.CustomEvent("onBookLoaded");

// Library objects used in various places
var Barcode;
var Scanning;
var timeoutAutoSave;

// ----------------------------
// Focus Tracking
//
// When handling keypress for setting page types, we need to know if the focus
// is on something on the page. So we have a function to set and clear a variable
// that tells us which object currently has the focus.
// ----------------------------

var focusObject = null;

function focusOn(o) {
	if (o) {
		focusObject = o.id;
		document.getElementById('foobar').value = focusObject;
	}
}

function focusOff() {
	focusObject = null;
	document.getElementById('foobar').value = focusObject;
}

// ----------------------------
// KeyPress handling
//
// We need to know when shift or ctrl are pressed. So we monitor the keypresses
// and set or unset our boolean variable when that happens. Then we just need to
// check those booleans when handling click, ctrl-click or shift-click events.
// ----------------------------
var keyCtrl = false;
var keyShift = false;
var keyAlt = false;
var keyCmd = false;

// ----------------------------
// Function: checkKey()
//
// Event handler - When a key is pressed, we see if it's one we are interested in
// (shift, alt, ctrl, command) and set flags based on the value. Presumably we can do this
// for arrow keys, too.
//
// Arguments
//    e - the Event that triggered the action
//
// Return Value / Effect
//    Variables keyCtrl, keyShift, keyAlt and/or keyMeta are set appropriately
// ----------------------------
function checkKey(e) {
	// returns true if the Ctrl key was pressed with the last key
	function isCtrl(e) {
		if (window.event) {
			return (window.event.ctrlKey);
		} else {
			return (e.ctrlKey || (e.modifiers==2) || (e.modifiers==3) || (e.modifiers>5));
		}
		return false;
	}

	// returns true if the Alt key was pressed with the last key
	function isAlt(e) {
		if (window.event) {
			return (window.event.altKey);
		} else {
				return (e.altKey || (e.modifiers % 2));
		}
		return false;
	}

	// returns true if the Shift key was pressed with the last key
	function isShift(e) {
		if (window.event) {
			return (window.event.shiftKey);
		} else {
			return (e.shiftKey || (e.modifiers>3));
		}
		return false;
	}

	function isCmd(e) {
		if (window.event) {
			return (window.event.metaKey);
		} else {
			return (e.metaKey);
		}
		return false;
	}

	keyAlt = isAlt(e);
	keyCtrl = isCtrl(e);
	keyShift = isShift(e);
	keyCmd = isCmd(e);
}

if (document.layers) {
	document.captureEvents(Event.KEYDOWN);
	document.captureEvents(Event.KEYPRESS);
	document.captureEvents(Event.KEYUP);
}

document.onkeydown = checkKey;
document.onkeypress = checkKey;
document.onkeyup = checkKey;
// ----------------------------
// (end KeyPress handling)
// ----------------------------



// ----------------------------
// Function: isblank()
//
// Tell us whether a field is "blank" or not. That is, is it zero length,
// equal to empty string or is null. All of these are "blank". Did I forget
// antyhing?
//
// Arguments
//    val - The string to check
//
// Return Value / Effect
//    True or false
// ----------------------------
function isBlank(val) {
	if (val == null) { return true; }
	val = String(val);
	for (var i=0;i<val.length;i++) {
		if ((val.charAt(i) != ' ') && (val.charAt(i) != "\t") && (val.charAt(i) != "\n") && (val.charAt(i) != "\r")) {
			return false;
		}
	}
	return true;
}

// ----------------------------
// Function: getKeyCode()
//
// Definitively get the keycode for the key being pressed. Handles browser wierdness.
//
// Arguments
//    e - The event object, we pass in whatever we think we have elsewhere
//
// Return Value / Effect
//    The ASCII Key code
// ----------------------------
function getKeyCode(e) {
	var evt = e || window.event || event;
	var code = evt.which || evt.keyCode || event.charCode;
	return code;
}

var int = function(x) {
	x = (x < 0) ? Math.ceil(x) : Math.floor(x);
	return x;
}

	MessageBox = {
		closeError: null,
		closeWarning: null,
		closeMessage: null,
		init: function() {			
			err = Dom.get('errormessage');
			if (err) {
				MessageBox.closeError = Dom.get("btnCloseError");
				YAHOO.util.Event.addListener(MessageBox.closeError, "click", MessageBox.close, 'error');				
			}
			warn = Dom.get('warning');
			if (warn) {
				MessageBox.closeWarning = new Dom.get("btnCloseWarning");
				YAHOO.util.Event.addListener(MessageBox.closeWarning, "click", MessageBox.close, 'warning');				
			}
			msg = Dom.get('message');
			if (msg) {
				MessageBox.closeMessage = new Dom.get("btnCloseMessage");
				YAHOO.util.Event.addListener(MessageBox.closeMessage, "click", MessageBox.close, 'message');				
			}
		},
		close: function(event,payload) {
			if (payload == 'error') {
				el = MessageBox.closeError;
			}
			if (payload == 'warning') {
				el = MessageBox.closeWarning;
			}
			if (payload == 'message') {
				el = MessageBox.closeMessage;
			}
			YAHOO.util.Event.removeListener(el, "click");
			el.parentElement.parentElement.removeChild(el.parentElement);
		}
	};

var formatBytes = function(elLiner, oRecord, oColumn, oData) {
	elLiner.innerHTML = Math.round(int(oData)/1024/1024)+' MB';
}

var formatIAIdentifier = function (elLiner, oRecord, oColumn, oData) {
	elLiner.innerHTML = '<a href="https://archive.org/details/' + oData + '">Details</a>&nbsp;<a href="https://archive.org/history/' + oData +'">History</a>';
}

var formatStatus2 = function(elCell, oRecord, oColumn, oData) {
	if (oData == 'new') {
		elCell.innerHTML = '<span style="color: #903">New</span>';

	} else if (oData == 'scanning') {
		elCell.innerHTML = '<span style="color: #F60">Images Uploading</span>';

	} else if (oData == 'scanned') {
		elCell.innerHTML = '<span style="color: #F60">Images Imported</span>';

	} else if (oData == 'reviewing') {
		elCell.innerHTML = '<span style="color: #F60">Metadata Entry</span>';

	} else if (oData == 'qa-ready') {
		elCell.innerHTML = '<span style="color: #DA0">QA Ready</span>';

	} else if (oData == 'qa-active') {
		elCell.innerHTML = '<span style="color: #DA0">QA In Progtress</span>';

	} else if (oData == 'reviewed') {
		elCell.innerHTML = '<span style="color: #090">Metadata Complete</span>';

	} else if (oData == 'exporting') {
		elCell.innerHTML = '<span style="color: #09F">Exporting</span>';

	} else if (oData == 'completed') {
		elCell.innerHTML = '<span style="color: #093">Export&nbsp;Complete</span>';

	} else if (oData == 'error') {
		elCell.innerHTML = '<span style="color: #F00">Error</span>';

	} else {
		elCell.innerHTML = oData;
	}
};

var formatStatus = function(elCell, oRecord, oColumn, oData) {
	if (oData == 'new') {
		elCell.innerHTML = '<span style="color: #C00">New</span>';

	} else if (oData == 'scanning') {
		elCell.innerHTML = '<span style="color: #39F">In&nbsp;Progress</span>';

	} else if (oData == 'scanned') {
		elCell.innerHTML = '<span style="color: #39F">In&nbsp;Progress</span>';

	} else if (oData == 'reviewing') {
		elCell.innerHTML = '<span style="color: #39F">In&nbsp;Progress</span>';

	} else if (oData == 'qa-ready') {
		elCell.innerHTML = '<span style="color: #DA0">QA&nbsp;Ready</span>';

	} else if (oData == 'qa-active') {
		elCell.innerHTML = '<span style="color: #DA0">QA&nbsp;In&nbsp;Progress</span>';

	} else if (oData == 'reviewed') {
		elCell.innerHTML = '<span style="color: #090">Awaiting&nbsp;Export</span>';

	} else if (oData == 'uploading') {
		elCell.innerHTML = '<span style="color: #360">Uploading</span>';

	} else if (oData == 'exporting') {
		elCell.innerHTML = '<span style="color: #39F">Exporting</span>';

	} else if (oData == 'completed') {
		elCell.innerHTML = '<span style="color: #360">Export&nbsp;Complete</span>';

	// These are for IA Statuses
	} else if (oData == 'pending') {
		elCell.innerHTML = '<span style="color: #39F">Exporting (Ready to Send)</span>';

	} else if (oData == 'uploaded') {
		elCell.innerHTML = '<span style="color: #39F">Exporting (Uploaded)</span>';

	} else if (oData == 'verified_upload') {
		elCell.innerHTML = '<span style="color: #39F">Exporting (Deriving)</span>';

	} else if (oData == 'verified_derive') {
		elCell.innerHTML = '<span style="color: #39F">Exporting (Derived)</span>';

	// Default
	} else {
		elCell.innerHTML = oData;
	}
}


var formatDateAge = function(elCell, oRecord, oColumn, oData) {
	function timeSince(date) {

	  var seconds = Math.floor((new Date() - date) / 1000);

	  var interval = seconds / 31536000;

	  if (interval > 1) {
	    return Math.floor(interval) + "&nbsp;years&nbsp;ago";
	  }
	  interval = seconds / 2592000;
	  if (interval > 1) {
	    return Math.floor(interval) + "&nbsp;months&nbsp;ago";
	  }
	  interval = seconds / 86400;
	  if (interval > 1) {
	    return Math.floor(interval) + "&nbsp;days&nbsp;ago";
	  }
	  interval = seconds / 3600;
	  if (interval > 1) {
	    return Math.floor(interval) + "&nbsp;hours&nbsp;ago";
	  }
	  interval = seconds / 60;
	  if (interval > 1) {
	    return Math.floor(interval) + "&nbsp;minutes&nbsp;ago";
	  }
	  return Math.floor(seconds) + "&nbsp;seconds&nbsp;ago";
	}

	dt = new Date(oData);
  elCell.innerHTML =  timeSince(dt);
}

var sortBytes = function(a, b, desc, field) {
	// Deal with empty values
	if(!YAHOO.lang.isValue(a)) {
		return (!YAHOO.lang.isValue(b)) ? 0 : 1;
	} else if(!YAHOO.lang.isValue(b)) {
		return -1;
	}
	
	// First compare by Column2
	var comp = YAHOO.util.Sort.compare;
	var compState = comp(int(a.getData(field)), int(b.getData(field)), desc);
	
	// If values are equal, then compare by Column1
	return (compState !== 0) ? compState : comp(a.getData("Column1"), b.getData("Column1"), desc);
};

var sortNoCase = function(a, b, desc, field) {
	// Deal with empty values
	if(!YAHOO.lang.isValue(a)) {
		return (!YAHOO.lang.isValue(b)) ? 0 : 1;
	} else if(!YAHOO.lang.isValue(b)) {
		return -1;
	}

	// First compare by Column2
	var comp = YAHOO.util.Sort.compare;
	var compState = comp(a.getData(field).toLowerCase(), b.getData(field).toLowerCase(), desc);

	// If values are equal, then compare by Column1
	return (compState !== 0) ? compState : comp(a.getData("Column1"), b.getData("Column1"), desc);
};


var sortStatus = function(a, b, desc, field) {
	// Deal with empty values
	if(!YAHOO.lang.isValue(a)) {
		return (!YAHOO.lang.isValue(b)) ? 0 : 1;
	} else if(!YAHOO.lang.isValue(b)) {
		return -1;
	}

  // Convert the words to numbers
  valueList = {
    'new': 1, 'scanning': 2, 'scanned': 3, 'reviewing': 4, 
    'qa-ready': 5, 'qa-active': 6, 'reviewed': 7, 'exporting': 8, 
    'completed': 9, 'archived': 10, 'error': 11
  };

  a = valueList[a.getData(field)];
  b = valueList[b.getData(field)];


	// First compare by Column2
	var comp = YAHOO.util.Sort.compare;
	return comp(a, b, desc);
};


(function() {
	
});

// This give us a proper sleep function. Just: await timer(100) in an async function;
const timer = ms => new Promise(res => setTimeout(res, ms))
