jQuery(document).ready(function($) {
	"use strict";

	function fixFullWidthRows() {
		$('.bwvca_fullheight_row').each(function(i) {

			// Find the parent row
			var row = $( document.bwvcaFindElementParentRow( $(this)[0] ) );

			var contentWidth = $(this).attr('data-content-location') || 'center';

			// We need to add minheight or else the content can go past the row
			row.css('minHeight', row.height() + 60);

			// Let CSS do the work for us
			row.addClass('bwvca-row-fullheight bwvca-row-height-location-' + contentWidth);

			// If center, remove top margin of topmost text & bottom margin of bottommost text
			if ( contentWidth === 'center' ) {
				row.find('> .vc_column_container > .wpb_wrapper > .wpb_text_column > .wpb_wrapper > *:first-child')
				.css('marginTop', 0);
				row.find('> .vc_column_container > .wpb_wrapper > .wpb_text_column > .wpb_wrapper > *:last-child')
				.css('marginBottom', 0);
			}
		});
	}

	fixFullWidthRows();
});
