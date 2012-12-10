/**
 * Image magnifier widget.
 * Author: Julien Lecomte <jlecomte@yahoo-inc.com>
 * Copyright (c) 2007, Yahoo! Inc. All rights reserved.
 * Code licensed under the BSD License:
 * http://developer.yahoo.net/yui/license.txt
 * Requires YUI >= 2.3.
 *
 * @module image-magnifier
 * @title Image magnifier
 * @namespace YAHOO.widget
 * @requires yahoo,dom,event,dragdrop
 */

/**
 * Image magnifier widget.
 * @namespace YAHOO.widget
 * @class ImageMagnifier
 * @constructor
 * @param {String | HTMLImageElement} img Accepts a string to use as an ID or an actual DOM reference.
 * @param {String} src Url or relative path to the magnified image.
 */
YAHOO.widget.ImageMagnifier = function ( img, src ) {

    // A few shortcuts
    var YD = YAHOO.util.Dom;
    var YE = YAHOO.util.Event;
    var YL = YAHOO.lang;

    /************************************************************************
     * PRIVATE MEMBERS
     ************************************************************************/

    /**
     * Reference to the outer HTML element.
     * @property _elem
     * @type HTMLDivElement
     * @private
     */
    var _elem;

    /**
     * Reference to the canvas element.
     * @property _canvas
     * @type HTMLCanvasElement | HTMLDivElement
     * @private
     */
    var _canvas;

    /**
     * Reference to the magnified image.
     * @property _magnifiedImage
     * @type HTMLImageElement
     * @private
     */
    var _magnifiedImage;

    /**
     * Initialize the image magnifier. Called when the specified image is loaded.
     * @method _init
     * @private
     */
    function _init () {

        var xratio, yratio, dim, oval, fill, ctx, drawMagnifiedImageFn, dd;

        xratio = _magnifiedImage.width / img.width;
        yratio = _magnifiedImage.height / img.height;
        dim = Math.floor( _elem.clientWidth / 2 ) - 1;

        if ( YAHOO.env.ua.ie ) {

            oval = document.createElement( "v:oval" );
            oval.style.position = "absolute";
            oval.style.left = oval.style.top = "0px";
            oval.style.width = _canvas.offsetWidth + "px";
            oval.style.height = _canvas.offsetHeight + "px";

            fill = document.createElement( "v:fill" );
            fill.type = "frame";
            fill.src = _magnifiedImage.src;
            fill.size = ( _magnifiedImage.width / _canvas.offsetWidth ) + ", " + ( _magnifiedImage.height / _canvas.offsetHeight );
            oval.appendChild( fill );

            _canvas.appendChild( oval );

            drawMagnifiedImageFn = function () {

                var dx, dy;

                dx = ( YD.getX( _elem ) + _elem.clientWidth / 2 ) - ( YD.getX( img ) + img.width / 2 );
                dy = ( YD.getY( _elem ) + _elem.clientHeight / 2 ) - ( YD.getY( img ) + img.height / 2 );

                fill.position = ( -xratio * ( dx / _canvas.offsetWidth ) ) + ", " + ( -yratio * ( dy / _canvas.offsetHeight ) );
            };

        } else {

            ctx = _canvas.getContext( "2d" );

            // Create a circular clipping path
            ctx.beginPath();
            ctx.arc( dim, dim, dim, 0, 2 * Math.PI, true );
            ctx.clip();

            drawMagnifiedImageFn = function () {

                var sx, sy, sw, sh, dx, dy, dw, dh;

                dx = dy = 0;
                sw = sh = dw = dh = 2 * dim;

                // Fill with the same color as the background surrounding the small image
                ctx.fillStyle = "#FFFFFF"; // FIXME -> This should be an optional parameter
                ctx.fillRect( 0, 0, dw, dh );

                sx = Math.floor( ( YD.getX( _canvas ) - YD.getX( img ) + _elem.offsetWidth / 2 ) * xratio - dim );
                sy = Math.floor( ( YD.getY( _canvas ) - YD.getY( img ) + _elem.offsetHeight / 2 ) * yratio - dim );

                // Handle corner/border cases...
                if ( sx < 0 ) {
                    dx = -sx;
                    sx = 0;
                }

                if ( sy < 0 ) {
                    dy = -sy;
                    sy = 0;
                }

                if ( sx > _magnifiedImage.width || sy > _magnifiedImage.height ||
                     sx + sw < 0 || sy + sh < 0 ) {
                    return;
                }

                if ( sx + sw > _magnifiedImage.width ) {
                    sw = _magnifiedImage.width - sx;
                    dw = sw;
                }

                if ( sy + sh > _magnifiedImage.height ) {
                    sh = _magnifiedImage.height - sy;
                    dh = sh;
                }

                // Draw the image
                ctx.drawImage( _magnifiedImage, sx, sy, sw, sh, dx, dy, dw, dh );
            };

        }

        // Originally center the magnifier
        YD.setX( _elem, YD.getX( img ) + Math.floor( ( img.width - _elem.clientWidth ) / 2 ) );
        YD.setY( _elem, YD.getY( img ) + Math.floor( ( img.height - _elem.clientHeight ) / 2 ) );

        // Show the lens
        _elem.style.visibility = "visible";

        // Originally draw the magnified image
        if ( YAHOO.env.ua.ie )	// in IE occurs no magnified image at start (mostly)
        	setTimeout(drawMagnifiedImageFn, 0);
        else
	        drawMagnifiedImageFn();

        // Set up the drag'n'drop...
        dd = new YAHOO.util.DD( _elem, '', {scroll:false} );

        dd.onDrag = function ( evt ) {
            drawMagnifiedImageFn();
        };
    }

    /************************************************************************
     * CONSTRUCTOR
     * (wrapped inside a function in order to differentiate variables
     * local to the constructor and private members of the object)
     ************************************************************************/

    ( function () {

        var lens, ss, loadTimer;

        // In case the caller passed in the id string of the image.
        img = YD.get( img );

        // Check the validity of the parameters passed to the constructor.
        if ( !img || img.tagName !== "IMG" || typeof src !== "string" ) {
            throw new Error( "Invalid argument" );
        }

        _elem = document.createElement( "DIV" );
        _elem.className = "magnifier";
        document.body.appendChild( _elem );

        lens = document.createElement( "DIV" );
        lens.className = "lens";
        lens.unselectable = "on"; // avoid selection on IE...
        _elem.appendChild( lens );

        // Note: The following code assumes a round magnifier image. We use a
        // 1 pixel margin so that the canvas does not show around the lens...

        if ( YAHOO.env.ua.ie ) {

            document.namespaces.add( "v", "urn:schemas-microsoft-com:vml" );
            ss = document.createStyleSheet();
            ss.addRule( "v\\:*", "behavior:url(#default#VML);" );

            _canvas = document.createElement( "DIV" );
            _canvas.style.width = ( lens.clientWidth - 2 ) + "px";
            _canvas.style.height = ( lens.clientHeight - 2 ) + "px";

        } else {

            _canvas = document.createElement( "CANVAS" );
            _canvas.width = lens.clientWidth - 2;
            _canvas.height = lens.clientHeight - 2;

        }

        _canvas.style.position = "absolute";
        _canvas.style.left = _canvas.style.top = "1px";
        _elem.appendChild( _canvas );

        _magnifiedImage = new Image();
        _magnifiedImage.src = src;

		loaddiv = document.createElement( "DIV" );
		var loadtext = document.createTextNode('Lupe wird geladen...');
		loaddiv.appendChild(loadtext);
		YAHOO.util.Dom.addClass(loaddiv, 'loaddiv');
//		document.getElementById('big_bild_div').appendChild(loaddiv);

		loadTimer = setInterval( function () {
            if (img.complete && _magnifiedImage.complete) {
                clearInterval( loadTimer );
                if (YAHOO.util.Dom.inDocument(loaddiv)) {
                	YAHOO.util.Dom.getElementsByClassName('loaddiv', 'DIV', 'big_bild_div',
                		function(o) { document.getElementById('big_bild_div').removeChild(o); });
                }
                _init();
            } else {
//            	alert('unready');
				document.getElementById('big_bild_div').appendChild(loaddiv);
            }
        }, 100 );

        // Figure out when the rendered size of both images is known.
//        loadTimer = setInterval( function () {
//            if ( YAHOO.env.ua.webkit && img.width !== 0 && _magnifiedImage.width !== 0 ||
//                 !YL.isUndefined( img.naturalWidth ) && img.naturalWidth !== 0 && _magnifiedImage.naturalWidth !== 0 ||
//                 img.complete && _magnifiedImage.complete ) {
//                clearInterval( loadTimer );
//                _init();
//            }
//        }, 100 );

    } )();

};