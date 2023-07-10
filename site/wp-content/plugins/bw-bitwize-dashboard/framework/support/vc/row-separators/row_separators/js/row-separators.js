/**
 * Gets the IE version if IE is used
 */
document.gambitIsIE = function () {
	var myNav = navigator.userAgent.toLowerCase();
	return (myNav.indexOf('msie') !== -1) ? parseInt(myNav.split('msie')[1]) : false;
};


/**
 * Finds the VC parent row (or just the parent if there's no VC)
 */
document.gambitFindElementParentRow = function( el ) {
	// find VC row
	var row = el.parentNode;
	if ( row.classList ) {
		while ( ! row.classList.contains('vc_row') && ! row.classList.contains('wpb_row') ) {
			if ( row.tagName === 'HTML' ) {
				row = false;
				break;
			}
			row = row.parentNode;
		}
	}
	if ( row !== false ) {
		return row;
	}

	// If vc_row & wpb_row have been removed/renamed, find a suitable row
	row = el.parentNode;
	var found = false;
	while ( ! found ) {
		Array.prototype.forEach.call( row.classList, function( className ) {
			if ( found ) {
				return;
			}
			if ( className.match(/row/g) ){
				found = true;
				return;
			}
		});

		if ( found ) {
			return row;
		}

		if ( row.tagName === 'HTML' ) {
			break;
		}

		row = row.parentNode;
	}

	// Last resort, return the immediate parent
	return el.parentNode;
};


/**
 * Main script
 */
document.addEventListener('DOMContentLoaded', function() {

	// Don't do anything while in frontend editor because we do not play well with that
	if ( document.querySelector('body').classList.contains( 'vc_editor' ) ) {
		return;
	}

	var elements = document.querySelectorAll('.bwrow_separator');
	Array.prototype.forEach.call(elements, function( el ){

		// Remove if IE9-
		if ( document.gambitIsIE() && document.gambitIsIE() < 10 ) {
			el.parentNode.removeChild( el );
			return;
		}

		var row = document.gambitFindElementParentRow( el ),
			adjustTop = el.getAttribute('class').indexOf('bwrow_sep_top') !== -1;

		// Move the SVG to the immediate row
		row.appendChild( el );
		row.classList.add( 'bwrow_sep_parent' );
		row.classList.add( adjustTop ? 'bwrow_sep_parent_top' : 'bwrow_sep_parent_bottom' );

		// Remove the Row's margin since it will not look well with the separator
		row.style[ ! adjustTop ? 'marginBottom' : 'marginTop' ] = 0;

		// Put in the background color
		var mainPolygons = el.querySelectorAll('.bwrow_sep_main');
		Array.prototype.forEach.call(mainPolygons, function( poly ){
			var fill = getComputedStyle(row).backgroundColor;

			// Check if the background color is transparent
			if ( fill === 'rgba(0, 0, 0, 0)' || fill === 'transparent' ) {
				fill = '#fff';
			} else {
				var match = fill.match( /rgba\(\s?\d+\s?,\s?\d+\s?,\s?\d+\s?,\s?(\d+)\s?\)/ );
				if ( match ) {
					match = parseFloat( match[1] );
					if ( match === 0 ) {
						fill = '#fff';
					}
				}
			}

			poly.style.fill = fill;
		});

		// classList property does not work for SVGs in IE, use getAttribute instead
		var rowToAdjust = adjustTop ? row.previousElementSibling : row.nextElementSibling,
			paddingStyle = adjustTop ? 'padding-bottom' : 'padding-top',
			svgHeight = parseInt( el.getAttribute( 'data-height' ) ) || 200,
			curPadding;

		// VC full width has a dummy div with the class vc_row-full-width, ignore it
		if ( rowToAdjust.classList && rowToAdjust.classList.contains( 'vc_row-full-width' ) ) {
			rowToAdjust = adjustTop ? rowToAdjust.previousElementSibling : rowToAdjust.nextElementSibling;
		}

		if ( rowToAdjust ) {
			curPadding = parseInt( getComputedStyle( rowToAdjust )[ paddingStyle ] ) || 0;
			// We need !important here since adding a padding has !important, we need to override that
			rowToAdjust.style.cssText = paddingStyle + ': calc(' + svgHeight + ' / 1600 * 100vw + ' + curPadding + 'px) !important;' + rowToAdjust.style.cssText;
			// No need to override this, if they provide a margin, then let them
			rowToAdjust.style[ adjustTop ? 'marginBottom' : 'marginTop' ] = 0;
		}

		el.classList.add( 'bwrow_sep_loaded' );
		el.style.display = 'block';
	});


	var elements = document.querySelectorAll('.bwrow_sep_parent_bottom');
	var totalBottom = elements.length + 3;
	Array.prototype.forEach.call(elements, function( el, i ) {
		el.style.cssText = 'z-index: ' + ( totalBottom - i ) + ' !important;' + el.style.cssText;
	});
});
