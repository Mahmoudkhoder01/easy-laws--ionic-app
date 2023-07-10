document.addEventListener('DOMContentLoaded', function() {
	var elements = document.querySelectorAll('.bwvca_hover_row');

	// Set up the hover div
	Array.prototype.forEach.call(elements, function(el, i) {
		// find Row
		var row = document.bwvcaFindElementParentRow( el );

		row.style.overflow = 'hidden';
		row.classList.add('has_bwvca_hover_row');

		// Add a new div
		var div = document.createElement('div');
		div.classList.add('bwvca_hover_inner');
		div.setAttribute('data-type', el.getAttribute('data-type'));
		div.setAttribute('data-amount', el.getAttribute('data-amount'));
		div.setAttribute('data-inverted', el.getAttribute('data-inverted'));
		div.style.opacity = Math.abs( parseFloat ( el.getAttribute('data-opacity') ) / 100 );
		div.style.backgroundImage = 'url(' + el.getAttribute('data-bg-image') + ')';

		var offset = 0;
		if ( el.getAttribute('data-type') === 'tilt' ) {
			offset = - parseInt( el.getAttribute('data-amount') ) * .6 + '%';
		} else {
			offset = - parseInt( el.getAttribute('data-amount') ) + 'px';
		}
		div.style.top = offset;
		div.style.left = offset;
		div.style.right = offset;
		div.style.bottom = offset;

		row.insertBefore(div, row.firstChild);

	});


	// Disable hover rows in mobile
	if ( navigator.userAgent.match(/Mobi/) ) {
		return;
	}


	// Bind to mousemove so animate the hover row
	var elements = document.querySelectorAll('.has_bwvca_hover_row');
	Array.prototype.forEach.call(elements, function(row, i) {

		row.addEventListener('mousemove', function(e) {

			// Get the parent row
			var parentRow = e.target.parentNode;
			while ( ! parentRow.classList.contains('has_bwvca_hover_row') ) {

				if ( parentRow.tagName === 'HTML' ) {
					return;
				}

				parentRow = parentRow.parentNode;
			}

			// Get the % location of the mouse position inside the row
			var rect = parentRow.getBoundingClientRect();
			var top = e.pageY - ( rect.top + window.pageYOffset );
			var left = e.pageX  - ( rect.left + window.pageXOffset );
			top /= parentRow.clientHeight;
			left /= parentRow.clientWidth;

			// Move all the hover inner divs
			var hoverRows = parentRow.querySelectorAll('.bwvca_hover_inner');
			Array.prototype.forEach.call(hoverRows, function(hoverBg, i) {

				// Parameters
				var amount = parseFloat( hoverBg.getAttribute( 'data-amount' ) );
				var inverted = hoverBg.getAttribute( 'data-inverted' ) === 'true';
				var transform;

				if ( hoverBg.getAttribute( 'data-type' ) === 'tilt' ) {

					var rotateY = left * amount - amount / 2;
					var rotateX = ( 1 - top ) * amount - amount / 2;
					if ( inverted ) {
						rotateY = ( 1 - left ) * amount - amount / 2;
						rotateX = top * amount - amount / 2;
					}

					transform = 'perspective(2000px) ';
					transform += 'rotateY(' + rotateY + 'deg) ';
					transform += 'rotateX(' + rotateX + 'deg) ';

					hoverBg.style.transition = 'all 0s';
					hoverBg.style.webkitTransform = transform;
					hoverBg.style.transform = transform;

				} else {

					var moveX = left * amount - amount / 2;
					var moveY = top * amount - amount / 2;
					if ( inverted ) {
						moveX *= -1;
						moveY *= -1;
					}
					transform = 'translate3D(' + moveX + 'px, ' + moveY + 'px, 0) ';

					hoverBg.style.transition = 'all 0s';
					hoverBg.style.webkitTransform = transform;
					hoverBg.style.transform = transform;
				}

			});
		});


		// Bind to mousemove so animate the hover row to it's default state
		row.addEventListener('mouseout', function(e) {

			// Get the parent row
			var parentRow = e.target.parentNode;
			while ( ! parentRow.classList.contains('has_bwvca_hover_row') ) {

				if ( parentRow.tagName === 'HTML' ) {
					return;
				}

				parentRow = parentRow.parentNode;
			}

			// Reset all the animations
			var hoverRows = parentRow.querySelectorAll('.bwvca_hover_inner');
			Array.prototype.forEach.call(hoverRows, function(hoverBg, i) {

				var amount = parseFloat( hoverBg.getAttribute( 'data-amount' ) );

				hoverBg.style.transition = 'all 3s ease-in-out';
				if ( hoverBg.getAttribute( 'data-type' ) === 'tilt' ) {
					hoverBg.style.webkitTransform = 'perspective(2000px) rotateY(0) rotateX(0)';
					hoverBg.style.transform = 'perspective(2000px) rotateY(0) rotateX(0)';
				} else {
					hoverBg.style.webkitTransform = 'translate3D(0, 0, 0)';
					hoverBg.style.transform = 'translate3D(0, 0, 0)';
				}

			});
		});
	});

});
