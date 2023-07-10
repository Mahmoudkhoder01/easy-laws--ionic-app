jQuery(document).ready(function($) {
	"use strict";

	function fixFullWidthRows() {
		$('.bwvca_fullwidth_row').each(function(i) {

			// Find the parent row
			var row = $( document.bwvcaFindElementParentRow( $(this)[0] ) );

            // Reset changed parameters for contentWidth so that width recalculation on resize will work
            row.css({
				'width': '',
				'position': '',
				'maxWidth': '',
				'left': '',
				'paddingLeft': '',
				'paddingRight': ''
            });

			var contentWidth = $(this).attr('data-content-width') || row.children(':not([class^=bwvca])').width() + 'px';

			// Make sure our parent won't hide our content
			row.parent().css('overflowX', 'visible');

			// Reset the left parameter
			row.css('left', '');

			// Assign the new full-width styles
			row.css({
				'width': '100vw',
				'position': 'relative',
				'maxWidth': $(window).width(),
				'left': -row.offset().left
			});


			if ( contentWidth === '' ) {
				return;
			}


			// Calculate the required left/right padding to ensure that the content width is being followed
			var padding = 0, actualWidth, paddingLeft, paddingRight;
			if ( contentWidth.search('%') !== -1 ) {
				actualWidth = parseFloat( contentWidth ) / 100 * $(window).width();
			} else {
				actualWidth = parseFloat( contentWidth );
			}

			padding = ( $(window).width() - actualWidth ) / 2;
			paddingLeft = padding + parseFloat( row.css('marginLeft' ) );
			paddingRight = padding + parseFloat( row.css('marginRight' ) );

			// If the width is too large, don't pad
			if ( actualWidth > $(window).width() ) {
				paddingLeft = 0;
				paddingRight = 0;
			}

			row.css({
				'paddingLeft': paddingLeft,
				'paddingRight': paddingRight
			});

		});
	}

	// setTimeout( function() {
		fixFullWidthRows();
	// }, 2);
	$(window).resize(function() {
		fixFullWidthRows();
		// setTimeout( function() {
			// fixFullWidthRows();
		// }, 2);
	});
});
