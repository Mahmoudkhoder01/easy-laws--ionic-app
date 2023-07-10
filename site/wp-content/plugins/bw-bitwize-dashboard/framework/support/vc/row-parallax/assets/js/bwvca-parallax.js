/**
 * requestAnimationFrame polyfill
 *
 * http://paulirish.com/2011/requestanimationframe-for-smart-animating/
 * http://my.opera.com/emoller/blog/2011/12/20/requestanimationframe-for-smart-er-animating
 * requestAnimationFrame polyfill by Erik Möller. fixes from Paul Irish and Tino Zijdel
 * requestAnimationFrame polyfill under MIT license
 */
(function() {
	var lastTime = 0;
	var vendors = ['ms', 'moz', 'webkit', 'o'];
	for( var x = 0; x < vendors.length && ! window.requestAnimationFrame; ++x ) {
		window.requestAnimationFrame = window[vendors[x]+'RequestAnimationFrame'];
	}

	if (!window.requestAnimationFrame) {
		window.requestAnimationFrame = function( callback, element ) {
			return window.setTimeout( function() { callback(); }, 16 );
		};
	}
}());


// Don't re-initialize our variables since that can delete existing values
if ( typeof _bwvcaImageParallaxImages === 'undefined' ) {
	var _bwvcaImageParallaxImages = [];
	var _bwvcaScrollTop;
	var _bwvcaWindowHeight;
	var _bwvcaScrollLeft;
	var _bwvcaWindowWidth;
}


;(function ( $, window, document, undefined ) {
	// Create the defaults once
	var pluginName = "bwvcaImageParallax",
		defaults = {
			direction: 'up', // fixed
			mobileenabled: false,
			mobiledevice: false,
			width: '',
			height: '',
			align: 'center',
			opacity: '1',
			velocity: '.3',
			image: '', // The background image to use, if empty, the current background image is used
			target: '', // The element to apply the parallax to
			repeat: false,
			loopScroll: '',
			loopScrollTime: '2',
			removeOrig: false,
			id: '',
			complete: function() {}
	};

	// The actual plugin constructor
	function Plugin ( element, options ) {
		this.element = element;
		// jQuery has an extend method which merges the contents of two or
		// more objects, storing the result in the first object. The first object
		// is generally empty as we don't want to alter the default options for
		// future instances of the plugin
		this.settings = $.extend( {}, defaults, options );

		if ( this.settings.align == '' ) {
			this.settings.align = 'center';
		}

		if ( this.settings.id === '' ) {
			this.settings.id = +new Date();
		}

		this._defaults = defaults;
		this._name = pluginName;
		this.init();
	}

	// Avoid Plugin.prototype conflicts
	$.extend(Plugin.prototype, {
		init: function () {
			// Place initialization logic here
			// You already have access to the DOM element and
			// the options via the instance, e.g. this.element
			// and this.settings
			// you can add more functions like the one below and
			// call them like so: this.yourOtherFunction(this.element, this.settings).
			// console.log("xD");

			// $(window).bind( 'parallax', function() {
			// self.bwvcaImageParallax();
			// });

			// If there is no target, use the element as the target
			if ( this.settings.target === '' ) {
				this.settings.target = $(this.element);
			}

			this.settings.target.addClass( this.settings.direction );

			// If there is no image given, use the background image if there is one
			if ( this.settings.image === '' ) {
				//if ( typeof $(this.element).css('backgroundImage') !== 'undefined' && $(this.element).css('backgroundImage').toLowerCase() !== 'none' && $(this.element).css('backgroundImage') !== '' )
				if ( typeof $(this.element).css('backgroundImage') !== 'undefined' && $(this.element).css('backgroundImage') !== '' ) {
					this.settings.image = $(this.element).css('backgroundImage').replace( /url\(|\)|"|'/g, '' );
				}
			}

			_bwvcaImageParallaxImages.push( this );

			this.setup();

			this.settings.complete();

			this.containerWidth = 0;
			this.containerHeight = 0;
		},


		setup: function () {
			if ( this.settings.removeOrig !== false ) {
				$(this.element).remove();
			}

			this.resizeParallaxBackground();
		},


		doParallax: function () {

			// if it's a mobile device and not told to activate on mobile, stop.
			if ( this.settings.mobiledevice && ! this.settings.mobileenabled ) {
				return;
			}

			// fixed backgrounds need no movement
			if ( this.settings.direction === 'fixed' ) {

				// Chrome retina bug where the background doesn't repaint
				// Bug report: https://code.google.com/p/chromium/issues/detail?id=366012
				if ( window.devicePixelRatio > 1 ) {
					$(this.settings.target).hide().show(0);
					//this.settings.target[0].style.display = 'none';
					//this.settings.target[0].style.display = '';
				}

			}

			// check if the container is in the view
			if ( ! this.isInView() ) {
				return;
			}

			// Continue moving the background
			if ( typeof this.settings.inner === 'undefined' ) {
				// this.settings.inner = this.settings.target.find('.parallax-inner-' + this.settings.id);
				this.settings.inner = this.settings.target[0].querySelectorAll('.parallax-inner-' + this.settings.id)[0];
			}
			var $target = this.settings.inner;


			// Retrigger a resize if the container's size suddenly changed
			// var w = this.settings.target.width() + parseInt( this.settings.target.css( 'paddingRight' ) ) + parseInt( this.settings.target.css( 'paddingLeft' ) );
			// var h = this.settings.target.height() + parseInt( this.settings.target.css( 'paddingTop' ) ) + parseInt( this.settings.target.css( 'paddingBottom' ) );

			if ( typeof this.settings.doParallaxClientLastUpdate === 'undefined'
				|| +new Date() - this.settings.doParallaxClientLastUpdate > 2000 + Math.random() * 1000 ) {
				this.settings.doParallaxClientLastUpdate = +new Date();

				this.settings.clientWidthCache = this.settings.target[0].clientWidth;
				this.settings.clientHeightCache = this.settings.target[0].clientHeight;
			}

			if ( this.containerWidth !== 0
				&& this.containerHeight !== 0
				&& ( this.settings.clientWidthCache !== this.containerWidth
					|| this.settings.clientHeightCache !== this.containerHeight ) ) {
				this.resizeParallaxBackground();
			}
			this.containerWidth = this.settings.clientWidthCache;
			this.containerHeight = this.settings.clientHeightCache;

			// If we don't have anything to scroll, stop
			// if ( typeof $target === 'undefined' || $target.length === 0 ) {
			// 	return;
			// }

			// compute for the parallax amount
			var percentageScroll = (_bwvcaScrollTop - this.scrollTopMin) / (this.scrollTopMax - this.scrollTopMin);
			var dist = this.moveMax * percentageScroll;

			// change direction
			if ( this.settings.direction === 'left' || this.settings.direction === 'up' ) {
				dist *= -1;
			}

			// IE9 check, IE9 doesn't support 3d transforms, so fallback to 2d translate
			var translateHori = 'translate3d(';
			var translateHoriSuffix = 'px, 0px, 0px)';
			var translateVert = 'translate3d(0px, ';
			var translateVertSuffix = 'px, 0px)';
			if ( typeof _bwvcaParallaxIE9 !== 'undefined' ) {
				translateHori = 'translate(';
				translateHoriSuffix = 'px, 0px)';
				translateVert = 'translate(0px, ';
				translateVertSuffix = 'px)';
			}


			if ( $target.style.backgroundRepeat === "no-repeat" ) {
				if ( this.settings.direction === 'down' && dist < 0 ) {
					dist = 0;
				}
				if ( this.settings.direction === 'up' && dist > 0 ) {
					dist = 0;
				}
			}

			// Apply the parallax transforms
			if ( this.settings.direction === 'left' || this.settings.direction === 'right' ) {
				$target.style.transition = 'transform 1ms linear';
				$target.style.webkitTransform = translateHori + dist + translateHoriSuffix;
				$target.style.transform = translateHori + dist + translateHoriSuffix;
			}
			else {
				$target.style.transition = 'transform 1ms linear';
				$target.style.webkitTransform = translateVert + dist + translateVertSuffix;
				$target.style.transform = translateVert + dist + translateVertSuffix;
			}

			// In some browsers, parallax might get jumpy/shakey, this hack makes it better
			// by force-cancelling the transition duration
			$target.style.transition = 'transform -1ms linear';
		},


		// Checks whether the container with the parallax is inside our viewport
		isInView: function() {

			// if ( typeof $target === 'undefined' || $target.length === 0 ) {
			// 	return;
			// }

			// Cache some values for faster calculations
			if ( typeof this.settings.offsetLastUpdate === 'undefined'
				|| +new Date() - this.settings.offsetLastUpdate > 4000 + Math.random() * 1000 ) {

				this.settings.offsetLastUpdate = +new Date();

				var $target = this.settings.target[0];

				// this.settings.offsetTopCache = $target.offset().top;
				// this.settings.elemHeightCache = $target.height()
				// 	+ parseInt( $target.css('paddingTop') )
				// 	+ parseInt( $target.css('paddingBottom') );
				this.settings.offsetTopCache = $target.getBoundingClientRect().top + window.pageYOffset;
				this.settings.elemHeightCache = $target.clientHeight;
			}

			var elemTop = this.settings.offsetTopCache;
			var elemHeight = this.settings.elemHeightCache;

			if ( elemTop + elemHeight < _bwvcaScrollTop || _bwvcaScrollTop + _bwvcaWindowHeight < elemTop ) {
				return false;
			}

			return true;
		},


		computeCoverDimensions: function( imageWidth, imageHeight, container ) {
			/* Step 1 - Get the ratio of the div + the image */
	        var imageRatio = imageWidth / imageHeight;
	        var coverRatio = container.offsetWidth / container.offsetHeight;

	        /* Step 2 - Work out which ratio is greater */
	        if ( imageRatio >= coverRatio ) {
	            /* The Height is our constant */
	            var finalHeight = container.offsetHeight;
	            var scale = ( finalHeight / imageHeight );
				var finalWidth = imageWidth * scale;
	        } else {
	            /* The Width is our constant */
	            var finalWidth = container.offsetWidth;
	            var scale = ( finalWidth / imageWidth );
				var finalHeight = imageHeight * scale;
	        }

			return finalWidth + 'px ' + finalHeight + 'px';
		},


		// Resizes the parallax to match the container size
		resizeParallaxBackground: function() {

			var $target = this.settings.target;
			if ( typeof $target === 'undefined' || $target.length === 0 ) {
				return;
			}


			// Repeat the background
			var isRepeat = this.settings.repeat === 'true' || this.settings.repeat === true || this.settings.repeat === 1;

			// Assert a minimum of 150 pixels of height globally. Prevents the illusion of parallaxes not rendering at all in empty fields.
			$target[0].style.minHeight = '150px';


			/*
			 * None, do not apply any parallax at all.
			 */

			if ( this.settings.direction === 'none' ) {

				// Stretch the image to fit the entire window
				var w = $target.width() + parseInt( $target.css( 'paddingRight' ) ) + parseInt( $target.css( 'paddingLeft' ) );

				// Compute position
				var position = $target.offset().left;
				if ( this.settings.align === 'center' ) {
					position = '50% 50%';
				}
				else if ( this.settings.align === 'left' ) {
					position = '0% 50%';
				}
				else if ( this.settings.align === 'right' ) {
					position = '100% 50%';
				}
				else if ( this.settings.align === 'top' ) {
					position = '50% 0%';
				}
				else if ( this.settings.align === 'bottom' ) {
					position = '50% 100%';
				}

				$target.css({
					opacity: Math.abs( parseFloat ( this.settings.opacity ) / 100 ),
					backgroundSize: 'cover',
					backgroundAttachment: 'scroll',
					backgroundPosition: position,
					backgroundRepeat: 'no-repeat'
				});
				if ( this.settings.image !== '' && this.settings.image !== 'none' ) {
					$target.css({
						opacity: Math.abs( parseFloat ( this.settings.opacity ) / 100 ),
						backgroundImage: 'url(' + this.settings.image + ')'
					});
				}

			/*
			 * Fixed, just stretch to fill up the entire container
			 */


			} else if ( this.settings.direction === 'fixed' ) {


				// Stretch the image to fit the entire window
				var w = $target.width() + parseInt( $target.css( 'paddingRight' ) ) + parseInt( $target.css( 'paddingLeft' ) );
				var h = $target.height() + parseInt( $target.css( 'paddingTop' ) ) + parseInt( $target.css( 'paddingBottom' ) );
				var origW = w;
				w += 400 * Math.abs( parseFloat( this.settings.velocity ) );

				// Compute left position
				var top = '0%';
				if ( this.settings.align === 'center' ) {
					top = '50%';
				} else if ( this.settings.align === 'bottom' ) {
					top = '100%';
				}

				// Compute top position
				var left = 0;
				if ( this.settings.direction === 'right' ) {
					left -= w - origW;
				}

				if ( $target.find('.parallax-inner-' + this.settings.id).length < 1 ) {
					$('<div></div>')
						.addClass('bwvca_parallax_inner')
						.addClass('parallax-inner-' + this.settings.id)
						.addClass( this.settings.direction )
						.prependTo( $target );
				}

				// Apply the required styles
				$target.css({
					position: 'relative',
					overflow: 'hidden',
					zIndex: 1
				})
				// .attr('style', 'background-image: none !important; ' + $target.attr('style'))
				.find('.parallax-inner-' + this.settings.id ).css({
					pointerEvents: 'none',
					width: w,
					height: h,
					position: 'absolute',
					zIndex: -1,
					top: 0,
					left: left,
					opacity: Math.abs( parseFloat ( this.settings.opacity ) / 100 ),
					// backgroundSize: isRepeat ? '100%' : 'cover',
					backgroundSize: isRepeat ? '100%' : this.computeCoverDimensions( this.settings.width, this.settings.height, $target[0].querySelectorAll('.parallax-inner-' + this.settings.id)[0] ),
					backgroundPosition: isRepeat ? '0 0 ' : '50% ' + top,
					backgroundRepeat: isRepeat ? 'repeat' : 'no-repeat',
					backgroundAttachment: 'fixed'
				});

				if ( this.settings.image !== '' && this.settings.image !== 'none' ) {
					$target.find('.parallax-inner-' + this.settings.id ).css({
						opacity: Math.abs( parseFloat ( this.settings.opacity ) / 100 ),
						backgroundImage: 'url(' + this.settings.image + ')'
					});
				}



			/*
			 * Left & right parallax - Stretch the image to fit the height & extend the sides
			 */


			} else if ( this.settings.direction === 'left' || this.settings.direction === 'right' ) {

				// Stretch the image to fit the entire window
				var w = $target.width() + parseInt( $target.css( 'paddingRight' ) ) + parseInt( $target.css( 'paddingLeft' ) );
				var h = $target.height() + parseInt( $target.css( 'paddingTop' ) ) + parseInt( $target.css( 'paddingBottom' ) );
				var origW = w;
				w += 400 * Math.abs( parseFloat( this.settings.velocity ) );

				// Compute left position
				var top = '0%';
				if ( this.settings.align === 'center' ) {
					top = '50%';
				} else if ( this.settings.align === 'bottom' ) {
					top = '100%';
				}

				// Compute top position
				var left = 0;
				if ( this.settings.direction === 'right' ) {
					left -= w - origW;
				}

				if ( $target.find('.parallax-inner-' + this.settings.id).length < 1 ) {
					$('<div></div>')
						.addClass('bwvca_parallax_inner')
						.addClass('parallax-inner-' + this.settings.id)
						.addClass( this.settings.direction )
						.prependTo( $target );
				}

				// Apply the required styles
				$target.css({
					position: 'relative',
					overflow: 'hidden',
					zIndex: 1
				})
				// .attr('style', 'background-image: none !important; ' + $target.attr('style'))
				.find('.parallax-inner-' + this.settings.id ).css({
					pointerEvents: 'none',
					width: w,
					height: h,
					position: 'absolute',
					zIndex: -1,
					top: 0,
					left: left,
					opacity: Math.abs( parseFloat ( this.settings.opacity ) / 100 ),
					// backgroundSize: isRepeat ? '100%' : 'cover',
					backgroundSize: isRepeat ? '100%' : this.computeCoverDimensions( this.settings.width, this.settings.height, $target[0].querySelectorAll('.parallax-inner-' + this.settings.id)[0] ),
					backgroundPosition: isRepeat ? '0 0 ' : '50% ' + top,
					backgroundRepeat: isRepeat ? 'repeat' : 'no-repeat'
				});


				if ( this.settings.image !== '' && this.settings.image !== 'none' ) {
					$target.find('.parallax-inner-' + this.settings.id ).css({
						opacity: Math.abs( parseFloat ( this.settings.opacity ) / 100 ),
						backgroundImage: 'url(' + this.settings.image + ')'
					});
				}

				// Compute for the positions to save cycles
				var scrollTopMin = 0;
				if ( $target.offset().top > _bwvcaWindowHeight ) {
					scrollTopMin = $target.offset().top - _bwvcaWindowHeight;
				}
				var scrollTopMax = $target.offset().top + $target.height() + parseInt( $target.css( 'paddingTop' ) ) + parseInt( $target.css( 'paddingBottom' ) );

				this.moveMax = w - origW;
				this.scrollTopMin = scrollTopMin;
				this.scrollTopMax = scrollTopMax;


			/*
			 * Up & down parallax - Stretch the image to fit the width & extend vertically
			 */


			} else { // Up or down
				// We have to add a bit more to DOWN since the page is scrolling as well,
				// or else it will not be visible
				var heightCompensate = 800;
				if ( this.settings.direction === 'down' ) {
					heightCompensate *= 1.2;
				}

				// Stretch the image to fit the entire window
				var w = $target.width() + parseInt( $target.css( 'paddingRight' ) ) + parseInt( $target.css( 'paddingLeft' ) );
				var h = $target.height() + parseInt( $target.css( 'paddingTop' ) ) + parseInt( $target.css( 'paddingBottom' ) );
				var origH = h;
				h += heightCompensate * Math.abs( parseFloat(this.settings.velocity) );

				// Compute left position
				var left = '0%';
				if ( this.settings.align === 'center' ) {
					left = '50%';
				} else if ( this.settings.align === 'right' ) {
					left = '100%';
				}

				// Compute top position
				var top = 0;
				if ( this.settings.direction === 'down' ) {
					top -= h - origH;
				}

				if ( $target.find('.parallax-inner-' + this.settings.id).length < 1 ) {
					$('<div></div>')
						.addClass('bwvca_parallax_inner')
						.addClass('parallax-inner-' + this.settings.id)
						.addClass( this.settings.direction )
						.prependTo( $target );
				}

				// Apply the required styles
				$target.css({
					position: 'relative',
					overflow: 'hidden',
					zIndex: 1
				})
				// .attr('style', 'background-image: none !important; ' + $target.attr('style'))
				.find('.parallax-inner-' + this.settings.id).css({
					pointerEvents: 'none',
					width: w,
					height: h,
					position: 'absolute',
					zIndex: -1,
					top: top,
					left: 0,
					opacity: Math.abs( parseFloat ( this.settings.opacity ) / 100 ),
					// backgroundSize: isRepeat ? '100%' : 'cover',
					backgroundSize: isRepeat ? '100%' : this.computeCoverDimensions( this.settings.width, this.settings.height, $target[0].querySelectorAll('.parallax-inner-' + this.settings.id)[0] ),
					backgroundPosition: isRepeat ? '0' : left + ' 50%',
					backgroundRepeat: isRepeat ? 'repeat' : 'no-repeat'
				});

				if ( this.settings.image !== '' && this.settings.image !== 'none' ) {
					$target.find('.parallax-inner-' + this.settings.id).css({
						opacity: Math.abs( parseFloat ( this.settings.opacity ) / 100 ),
						backgroundImage: 'url(' + this.settings.image + ')'
					});
				}

				// Compute for the positions to save cycles
				var scrollTopMin = 0;
				if ( $target.offset().top > _bwvcaWindowHeight ) {
					scrollTopMin = $target.offset().top - _bwvcaWindowHeight;
				}
				var scrollTopMax = $target.offset().top + $target.height() + parseInt( $target.css( 'paddingTop' ) ) + parseInt( $target.css( 'paddingBottom' ) );

				this.moveMax = h - origH;
				this.scrollTopMin = scrollTopMin;
				this.scrollTopMax = scrollTopMax;
			}
		},

	});


	// A really lightweight plugin wrapper around the constructor,
	// preventing against multiple instantiations
	$.fn[ pluginName ] = function ( options ) {
		this.each(function() {
			if ( !$.data( this, "plugin_" + pluginName ) ) {
				$.data( this, "plugin_" + pluginName, new Plugin( this, options ) );
			}
		});

		// chain jQuery functions
		return this;
	};


})( jQuery, window, document );



function _bwvcaRefreshScroll() {
	var $ = jQuery;
	_bwvcaScrollTop = window.pageYOffset;//$(window).scrollTop();
	_bwvcaScrollLeft = window.pageXOffset;//$(window).scrollLeft();
}

function _bwvcaParallaxAll() {
	_bwvcaRefreshScroll();
	for ( var i = 0; i < _bwvcaImageParallaxImages.length; i++) {
		_bwvcaImageParallaxImages[ i ].doParallax();
	}
}

jQuery(document).ready(function($) {
	"use strict";

	$( window ).on(
		'scroll touchmove touchstart touchend gesturechange mousemove', function( e ) {
			requestAnimationFrame( _bwvcaParallaxAll );
		}
	);

	function mobileParallaxAll() {
		_bwvcaRefreshScroll();
		for ( var i = 0; i < _bwvcaImageParallaxImages.length; i++) {
			_bwvcaImageParallaxImages[ i ].doParallax();
		}
		requestAnimationFrame(mobileParallaxAll);
	}


	if ( navigator.userAgent.match(/Mobi/)) {
		requestAnimationFrame(mobileParallaxAll);
	}


	// When the browser resizes, fix parallax size
	// Some browsers do not work if this is not performed after 1ms
	$(window).on( 'resize', function() {
		setTimeout( function() {
			var $ = jQuery;
			_bwvcaRefreshWindow();
			$.each( _bwvcaImageParallaxImages, function( i, parallax ) {
				parallax.resizeParallaxBackground();
			} );
		}, 1 );
	} );

	// setTimeout( parallaxAll, 1 );
	setTimeout( function() {
		var $ = jQuery;
		_bwvcaRefreshWindow();
		$.each( _bwvcaImageParallaxImages, function( i, parallax ) {
			parallax.resizeParallaxBackground();
		} );
	}, 1 );

	// setTimeout( parallaxAll, 100 );
	setTimeout( function() {
		var $ = jQuery;
		_bwvcaRefreshWindow();
		$.each( _bwvcaImageParallaxImages, function( i, parallax ) {
			parallax.resizeParallaxBackground();
		} );
	}, 100 );

	function _bwvcaRefreshWindow() {
		_bwvcaScrollTop = window.pageYOffset;//$(window).scrollTop();
		_bwvcaWindowHeight = window.innerHeight;//$(window).height()
		_bwvcaScrollLeft = window.pageXOffset;//$(window).scrollLeft();
		_bwvcaWindowWidth = window.innerWidth;//$(window).width()
	}
});




