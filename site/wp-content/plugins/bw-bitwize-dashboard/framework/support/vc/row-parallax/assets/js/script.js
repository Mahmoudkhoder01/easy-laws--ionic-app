// @codekit-append "bwvca-fullwidth.js"
// @codekit-append "bwvca-fullheight.js"
// @codekit-append "bwvca-parallax.js"
// @codekit-append "bwvca-video-bg.js"
// @codekit-append "bwvca-hover.js"
// @codekit-append "bwvca-background.js"


/**
 * Finds the parent VC row. If it fails, it returns a parent that has a class name of *row*.
 * If it still fails, it returns the immediate parent.
 */
document.bwvcaFindElementParentRow = function( el ) {
	// find VC row
	var row = el.parentNode;
	while ( ! row.classList.contains('vc_row') && ! row.classList.contains('wpb_row') ) {
		if ( row.tagName === 'HTML' ) {
			row = false;
			break;
		}
		row = row.parentNode;
	}
	if ( row !== false ) {
		return row;
	}

	// If vc_row & wpb_row have been removed/renamed, find a suitable row
	row = el.parentNode;
	var found = false;
	while ( ! found ) {
		Array.prototype.forEach.call( row.classList, function(className, i) {
			if ( found ) {
				return;
			}
			if ( className.match(/row/g) ){
				found = true;
				return;
			}
		})
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
}



jQuery(document).ready(function($) {

	function _isMobile() {
		return navigator.userAgent.match(/Mobi/);
	}

	$('.bwvca_parallax_row').each(function() {

		$(this).bwvcaImageParallax({
			image: $(this).attr('data-bg-image'),
			direction: $(this).attr('data-direction'),
			mobileenabled: $(this).attr('data-mobile-enabled'),
			mobiledevice: _isMobile(),
			opacity: $(this).attr('data-opacity'),
			width: $(this).attr('data-bg-width'),
			height: $(this).attr('data-bg-height'),
			velocity: $(this).attr('data-velocity'),
			align: $(this).attr('data-bg-align'),
			repeat: $(this).attr('data-bg-repeat'),
			target: $( document.bwvcaFindElementParentRow( $(this)[0] ) ),
			complete: function() {
			}
		});
	});
});
