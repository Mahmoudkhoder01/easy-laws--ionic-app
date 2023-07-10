document.bwvcaFindElementParentRow = function(el) {
    var row = el.parentNode;
    while (!row.classList.contains('vc_row') && !row.classList.contains('wpb_row')) {
        if (row.tagName === 'HTML') {
            row = false;
            break;
        }
        row = row.parentNode;
    }
    if (row !== false) {
        return row;
    }
    row = el.parentNode;
    var found = false;
    while (!found) {
        Array.prototype.forEach.call(row.classList, function(className, i) {
            if (found) {
                return;
            }
            if (className.match(/row/g)) {
                found = true;
                return;
            }
        })
        if (found) {
            return row;
        }
        if (row.tagName === 'HTML') {
            break;
        }
        row = row.parentNode;
    }
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
            target: $(document.bwvcaFindElementParentRow($(this)[0])),
            complete: function() {}
        });
    });
});
jQuery(document).ready(function($) {
    "use strict";

    function fixFullWidthRows() {
        $('.bwvca_fullwidth_row').each(function(i) {
            var row = $(document.bwvcaFindElementParentRow($(this)[0]));
            row.css({
                'width': '',
                'position': '',
                'maxWidth': '',
                'left': '',
                'paddingLeft': '',
                'paddingRight': ''
            });
            var contentWidth = $(this).attr('data-content-width') || row.children(':not([class^=bwvca])').width() + 'px';
            row.parent().css('overflowX', 'visible');
            row.css('left', '');
            row.css({
                'width': '100vw',
                'position': 'relative',
                'maxWidth': $(window).width(),
                'left': -row.offset().left
            });
            if (contentWidth === '') {
                return;
            }
            var padding = 0,
                actualWidth, paddingLeft, paddingRight;
            if (contentWidth.search('%') !== -1) {
                actualWidth = parseFloat(contentWidth) / 100 * $(window).width();
            } else {
                actualWidth = parseFloat(contentWidth);
            }
            padding = ($(window).width() - actualWidth) / 2;
            paddingLeft = padding + parseFloat(row.css('marginLeft'));
            paddingRight = padding + parseFloat(row.css('marginRight'));
            if (actualWidth > $(window).width()) {
                paddingLeft = 0;
                paddingRight = 0;
            }
            row.css({
                'paddingLeft': paddingLeft,
                'paddingRight': paddingRight
            });
        });
    }
    fixFullWidthRows();
    $(window).resize(function() {
        fixFullWidthRows();
    });
});
jQuery(document).ready(function($) {
    "use strict";

    function fixFullWidthRows() {
        $('.bwvca_fullheight_row').each(function(i) {
            var row = $(document.bwvcaFindElementParentRow($(this)[0]));
            var contentWidth = $(this).attr('data-content-location') || 'center';
            row.css('minHeight', row.height() + 60);
            row.addClass('bwvca-row-fullheight bwvca-row-height-location-' + contentWidth);
            if (contentWidth === 'center') {
                row.find('> .vc_column_container > .wpb_wrapper > .wpb_text_column > .wpb_wrapper > *:first-child').css('marginTop', 0);
                row.find('> .vc_column_container > .wpb_wrapper > .wpb_text_column > .wpb_wrapper > *:last-child').css('marginBottom', 0);
            }
        });
    }
    fixFullWidthRows();
});
(function() {
    var lastTime = 0;
    var vendors = ['ms', 'moz', 'webkit', 'o'];
    for (var x = 0; x < vendors.length && !window.requestAnimationFrame; ++x) {
        window.requestAnimationFrame = window[vendors[x] + 'RequestAnimationFrame'];
    }
    if (!window.requestAnimationFrame) {
        window.requestAnimationFrame = function(callback, element) {
            return window.setTimeout(function() {
                callback();
            }, 16);
        };
    }
}());
if (typeof _bwvcaImageParallaxImages === 'undefined') {
    var _bwvcaImageParallaxImages = [];
    var _bwvcaScrollTop;
    var _bwvcaWindowHeight;
    var _bwvcaScrollLeft;
    var _bwvcaWindowWidth;
};
(function($, window, document, undefined) {
    var pluginName = "bwvcaImageParallax",
        defaults = {
            direction: 'up',
            mobileenabled: false,
            mobiledevice: false,
            width: '',
            height: '',
            align: 'center',
            opacity: '1',
            velocity: '.3',
            image: '',
            target: '',
            repeat: false,
            loopScroll: '',
            loopScrollTime: '2',
            removeOrig: false,
            id: '',
            complete: function() {}
        };

    function Plugin(element, options) {
        this.element = element;
        this.settings = $.extend({}, defaults, options);
        if (this.settings.align == '') {
            this.settings.align = 'center';
        }
        if (this.settings.id === '') {
            this.settings.id = +new Date();
        }
        this._defaults = defaults;
        this._name = pluginName;
        this.init();
    }
    $.extend(Plugin.prototype, {
        init: function() {
            if (this.settings.target === '') {
                this.settings.target = $(this.element);
            }
            this.settings.target.addClass(this.settings.direction);
            if (this.settings.image === '') {
                if (typeof $(this.element).css('backgroundImage') !== 'undefined' && $(this.element).css('backgroundImage') !== '') {
                    this.settings.image = $(this.element).css('backgroundImage').replace(/url\(|\)|"|'/g, '');
                }
            }
            _bwvcaImageParallaxImages.push(this);
            this.setup();
            this.settings.complete();
            this.containerWidth = 0;
            this.containerHeight = 0;
        },
        setup: function() {
            if (this.settings.removeOrig !== false) {
                $(this.element).remove();
            }
            this.resizeParallaxBackground();
        },
        doParallax: function() {
            if (this.settings.mobiledevice && !this.settings.mobileenabled) {
                return;
            }
            if (this.settings.direction === 'fixed') {
                if (window.devicePixelRatio > 1) {
                    $(this.settings.target).hide().show(0);
                }
            }
            if (!this.isInView()) {
                return;
            }
            if (typeof this.settings.inner === 'undefined') {
                this.settings.inner = this.settings.target[0].querySelectorAll('.parallax-inner-' + this.settings.id)[0];
            }
            var $target = this.settings.inner;
            if (typeof this.settings.doParallaxClientLastUpdate === 'undefined' || +new Date() - this.settings.doParallaxClientLastUpdate > 2000 + Math.random() * 1000) {
                this.settings.doParallaxClientLastUpdate = +new Date();
                this.settings.clientWidthCache = this.settings.target[0].clientWidth;
                this.settings.clientHeightCache = this.settings.target[0].clientHeight;
            }
            if (this.containerWidth !== 0 && this.containerHeight !== 0 && (this.settings.clientWidthCache !== this.containerWidth || this.settings.clientHeightCache !== this.containerHeight)) {
                this.resizeParallaxBackground();
            }
            this.containerWidth = this.settings.clientWidthCache;
            this.containerHeight = this.settings.clientHeightCache;
            var percentageScroll = (_bwvcaScrollTop - this.scrollTopMin) / (this.scrollTopMax - this.scrollTopMin);
            var dist = this.moveMax * percentageScroll;
            if (this.settings.direction === 'left' || this.settings.direction === 'up') {
                dist *= -1;
            }
            var translateHori = 'translate3d(';
            var translateHoriSuffix = 'px, 0px, 0px)';
            var translateVert = 'translate3d(0px, ';
            var translateVertSuffix = 'px, 0px)';
            if (typeof _bwvcaParallaxIE9 !== 'undefined') {
                translateHori = 'translate(';
                translateHoriSuffix = 'px, 0px)';
                translateVert = 'translate(0px, ';
                translateVertSuffix = 'px)';
            }
            if ($target.style.backgroundRepeat === "no-repeat") {
                if (this.settings.direction === 'down' && dist < 0) {
                    dist = 0;
                }
                if (this.settings.direction === 'up' && dist > 0) {
                    dist = 0;
                }
            }
            if (this.settings.direction === 'left' || this.settings.direction === 'right') {
                $target.style.transition = 'transform 1ms linear';
                $target.style.webkitTransform = translateHori + dist + translateHoriSuffix;
                $target.style.transform = translateHori + dist + translateHoriSuffix;
            } else {
                $target.style.transition = 'transform 1ms linear';
                $target.style.webkitTransform = translateVert + dist + translateVertSuffix;
                $target.style.transform = translateVert + dist + translateVertSuffix;
            }
            $target.style.transition = 'transform -1ms linear';
        },
        isInView: function() {
            if (typeof this.settings.offsetLastUpdate === 'undefined' || +new Date() - this.settings.offsetLastUpdate > 4000 + Math.random() * 1000) {
                this.settings.offsetLastUpdate = +new Date();
                var $target = this.settings.target[0];
                this.settings.offsetTopCache = $target.getBoundingClientRect().top + window.pageYOffset;
                this.settings.elemHeightCache = $target.clientHeight;
            }
            var elemTop = this.settings.offsetTopCache;
            var elemHeight = this.settings.elemHeightCache;
            if (elemTop + elemHeight < _bwvcaScrollTop || _bwvcaScrollTop + _bwvcaWindowHeight < elemTop) {
                return false;
            }
            return true;
        },
        computeCoverDimensions: function(imageWidth, imageHeight, container) {
            var imageRatio = imageWidth / imageHeight;
            var coverRatio = container.offsetWidth / container.offsetHeight;
            if (imageRatio >= coverRatio) {
                var finalHeight = container.offsetHeight;
                var scale = (finalHeight / imageHeight);
                var finalWidth = imageWidth * scale;
            } else {
                var finalWidth = container.offsetWidth;
                var scale = (finalWidth / imageWidth);
                var finalHeight = imageHeight * scale;
            }
            return finalWidth + 'px ' + finalHeight + 'px';
        },
        resizeParallaxBackground: function() {
            var $target = this.settings.target;
            if (typeof $target === 'undefined' || $target.length === 0) {
                return;
            }
            var isRepeat = this.settings.repeat === 'true' || this.settings.repeat === true || this.settings.repeat === 1;
            $target[0].style.minHeight = '150px';
            if (this.settings.direction === 'none') {
                var w = $target.width() + parseInt($target.css('paddingRight')) + parseInt($target.css('paddingLeft'));
                var position = $target.offset().left;
                if (this.settings.align === 'center') {
                    position = '50% 50%';
                } else if (this.settings.align === 'left') {
                    position = '0% 50%';
                } else if (this.settings.align === 'right') {
                    position = '100% 50%';
                } else if (this.settings.align === 'top') {
                    position = '50% 0%';
                } else if (this.settings.align === 'bottom') {
                    position = '50% 100%';
                }
                $target.css({
                    opacity: Math.abs(parseFloat(this.settings.opacity) / 100),
                    backgroundSize: 'cover',
                    backgroundAttachment: 'scroll',
                    backgroundPosition: position,
                    backgroundRepeat: 'no-repeat'
                });
                if (this.settings.image !== '' && this.settings.image !== 'none') {
                    $target.css({
                        opacity: Math.abs(parseFloat(this.settings.opacity) / 100),
                        backgroundImage: 'url(' + this.settings.image + ')'
                    });
                }
            } else if (this.settings.direction === 'fixed') {
                var w = $target.width() + parseInt($target.css('paddingRight')) + parseInt($target.css('paddingLeft'));
                var h = $target.height() + parseInt($target.css('paddingTop')) + parseInt($target.css('paddingBottom'));
                var origW = w;
                w += 400 * Math.abs(parseFloat(this.settings.velocity));
                var top = '0%';
                if (this.settings.align === 'center') {
                    top = '50%';
                } else if (this.settings.align === 'bottom') {
                    top = '100%';
                }
                var left = 0;
                if (this.settings.direction === 'right') {
                    left -= w - origW;
                }
                if ($target.find('.parallax-inner-' + this.settings.id).length < 1) {
                    $('<div></div>').addClass('bwvca_parallax_inner').addClass('parallax-inner-' + this.settings.id).addClass(this.settings.direction).prependTo($target);
                }
                $target.css({
                    position: 'relative',
                    overflow: 'hidden',
                    zIndex: 1
                }).find('.parallax-inner-' + this.settings.id).css({
                    pointerEvents: 'none',
                    width: w,
                    height: h,
                    position: 'absolute',
                    zIndex: -1,
                    top: 0,
                    left: left,
                    opacity: Math.abs(parseFloat(this.settings.opacity) / 100),
                    backgroundSize: isRepeat ? '100%' : this.computeCoverDimensions(this.settings.width, this.settings.height, $target[0].querySelectorAll('.parallax-inner-' + this.settings.id)[0]),
                    backgroundPosition: isRepeat ? '0 0 ' : '50% ' + top,
                    backgroundRepeat: isRepeat ? 'repeat' : 'no-repeat',
                    backgroundAttachment: 'fixed'
                });
                if (this.settings.image !== '' && this.settings.image !== 'none') {
                    $target.find('.parallax-inner-' + this.settings.id).css({
                        opacity: Math.abs(parseFloat(this.settings.opacity) / 100),
                        backgroundImage: 'url(' + this.settings.image + ')'
                    });
                }
            } else if (this.settings.direction === 'left' || this.settings.direction === 'right') {
                var w = $target.width() + parseInt($target.css('paddingRight')) + parseInt($target.css('paddingLeft'));
                var h = $target.height() + parseInt($target.css('paddingTop')) + parseInt($target.css('paddingBottom'));
                var origW = w;
                w += 400 * Math.abs(parseFloat(this.settings.velocity));
                var top = '0%';
                if (this.settings.align === 'center') {
                    top = '50%';
                } else if (this.settings.align === 'bottom') {
                    top = '100%';
                }
                var left = 0;
                if (this.settings.direction === 'right') {
                    left -= w - origW;
                }
                if ($target.find('.parallax-inner-' + this.settings.id).length < 1) {
                    $('<div></div>').addClass('bwvca_parallax_inner').addClass('parallax-inner-' + this.settings.id).addClass(this.settings.direction).prependTo($target);
                }
                $target.css({
                    position: 'relative',
                    overflow: 'hidden',
                    zIndex: 1
                }).find('.parallax-inner-' + this.settings.id).css({
                    pointerEvents: 'none',
                    width: w,
                    height: h,
                    position: 'absolute',
                    zIndex: -1,
                    top: 0,
                    left: left,
                    opacity: Math.abs(parseFloat(this.settings.opacity) / 100),
                    backgroundSize: isRepeat ? '100%' : this.computeCoverDimensions(this.settings.width, this.settings.height, $target[0].querySelectorAll('.parallax-inner-' + this.settings.id)[0]),
                    backgroundPosition: isRepeat ? '0 0 ' : '50% ' + top,
                    backgroundRepeat: isRepeat ? 'repeat' : 'no-repeat'
                });
                if (this.settings.image !== '' && this.settings.image !== 'none') {
                    $target.find('.parallax-inner-' + this.settings.id).css({
                        opacity: Math.abs(parseFloat(this.settings.opacity) / 100),
                        backgroundImage: 'url(' + this.settings.image + ')'
                    });
                }
                var scrollTopMin = 0;
                if ($target.offset().top > _bwvcaWindowHeight) {
                    scrollTopMin = $target.offset().top - _bwvcaWindowHeight;
                }
                var scrollTopMax = $target.offset().top + $target.height() + parseInt($target.css('paddingTop')) + parseInt($target.css('paddingBottom'));
                this.moveMax = w - origW;
                this.scrollTopMin = scrollTopMin;
                this.scrollTopMax = scrollTopMax;
            } else {
                var heightCompensate = 800;
                if (this.settings.direction === 'down') {
                    heightCompensate *= 1.2;
                }
                var w = $target.width() + parseInt($target.css('paddingRight')) + parseInt($target.css('paddingLeft'));
                var h = $target.height() + parseInt($target.css('paddingTop')) + parseInt($target.css('paddingBottom'));
                var origH = h;
                h += heightCompensate * Math.abs(parseFloat(this.settings.velocity));
                var left = '0%';
                if (this.settings.align === 'center') {
                    left = '50%';
                } else if (this.settings.align === 'right') {
                    left = '100%';
                }
                var top = 0;
                if (this.settings.direction === 'down') {
                    top -= h - origH;
                }
                if ($target.find('.parallax-inner-' + this.settings.id).length < 1) {
                    $('<div></div>').addClass('bwvca_parallax_inner').addClass('parallax-inner-' + this.settings.id).addClass(this.settings.direction).prependTo($target);
                }
                $target.css({
                    position: 'relative',
                    overflow: 'hidden',
                    zIndex: 1
                }).find('.parallax-inner-' + this.settings.id).css({
                    pointerEvents: 'none',
                    width: w,
                    height: h,
                    position: 'absolute',
                    zIndex: -1,
                    top: top,
                    left: 0,
                    opacity: Math.abs(parseFloat(this.settings.opacity) / 100),
                    backgroundSize: isRepeat ? '100%' : this.computeCoverDimensions(this.settings.width, this.settings.height, $target[0].querySelectorAll('.parallax-inner-' + this.settings.id)[0]),
                    backgroundPosition: isRepeat ? '0' : left + ' 50%',
                    backgroundRepeat: isRepeat ? 'repeat' : 'no-repeat'
                });
                if (this.settings.image !== '' && this.settings.image !== 'none') {
                    $target.find('.parallax-inner-' + this.settings.id).css({
                        opacity: Math.abs(parseFloat(this.settings.opacity) / 100),
                        backgroundImage: 'url(' + this.settings.image + ')'
                    });
                }
                var scrollTopMin = 0;
                if ($target.offset().top > _bwvcaWindowHeight) {
                    scrollTopMin = $target.offset().top - _bwvcaWindowHeight;
                }
                var scrollTopMax = $target.offset().top + $target.height() + parseInt($target.css('paddingTop')) + parseInt($target.css('paddingBottom'));
                this.moveMax = h - origH;
                this.scrollTopMin = scrollTopMin;
                this.scrollTopMax = scrollTopMax;
            }
        },
    });
    $.fn[pluginName] = function(options) {
        this.each(function() {
            if (!$.data(this, "plugin_" + pluginName)) {
                $.data(this, "plugin_" + pluginName, new Plugin(this, options));
            }
        });
        return this;
    };
})(jQuery, window, document);

function _bwvcaRefreshScroll() {
    var $ = jQuery;
    _bwvcaScrollTop = window.pageYOffset;
    _bwvcaScrollLeft = window.pageXOffset;
}

function _bwvcaParallaxAll() {
    _bwvcaRefreshScroll();
    for (var i = 0; i < _bwvcaImageParallaxImages.length; i++) {
        _bwvcaImageParallaxImages[i].doParallax();
    }
}
jQuery(document).ready(function($) {
    "use strict";
    $(window).on('scroll touchmove touchstart touchend gesturechange mousemove', function(e) {
        requestAnimationFrame(_bwvcaParallaxAll);
    });

    function mobileParallaxAll() {
        _bwvcaRefreshScroll();
        for (var i = 0; i < _bwvcaImageParallaxImages.length; i++) {
            _bwvcaImageParallaxImages[i].doParallax();
        }
        requestAnimationFrame(mobileParallaxAll);
    }
    if (navigator.userAgent.match(/Mobi/)) {
        requestAnimationFrame(mobileParallaxAll);
    }
    $(window).on('resize', function() {
        setTimeout(function() {
            var $ = jQuery;
            _bwvcaRefreshWindow();
            $.each(_bwvcaImageParallaxImages, function(i, parallax) {
                parallax.resizeParallaxBackground();
            });
        }, 1);
    });
    setTimeout(function() {
        var $ = jQuery;
        _bwvcaRefreshWindow();
        $.each(_bwvcaImageParallaxImages, function(i, parallax) {
            parallax.resizeParallaxBackground();
        });
    }, 1);
    setTimeout(function() {
        var $ = jQuery;
        _bwvcaRefreshWindow();
        $.each(_bwvcaImageParallaxImages, function(i, parallax) {
            parallax.resizeParallaxBackground();
        });
    }, 100);

    function _bwvcaRefreshWindow() {
        _bwvcaScrollTop = window.pageYOffset;
        _bwvcaWindowHeight = window.innerHeight;
        _bwvcaScrollLeft = window.pageXOffset;
        _bwvcaWindowWidth = window.innerWidth;
    }
});

function _vcRowGetAllElementsWithAttribute(attribute) {
    var matchingElements = [];
    var allElements = document.getElementsByTagName('*');
    for (var i = 0, n = allElements.length; i < n; i++) {
        if (allElements[i].getAttribute(attribute)) {
            matchingElements.push(allElements[i]);
        }
    }
    return matchingElements;
}

function _vcRowOnPlayerReady(event) {
    var player = event.target;
    player.playVideo();
    if (player.isMute) {
        player.mute();
    }
    if (player.forceHD) {
        player.setPlaybackQuality('hd720');
    }
    var prevCurrTime = player.getCurrentTime();
    var timeLastCall = +new Date() / 1000;
    var currTime = 0;
    var firstRun = true;
    player.loopInterval = setInterval(function() {
        if (typeof player.loopTimeout !== 'undefined') {
            clearTimeout(player.loopTimeout);
        }
        if (prevCurrTime == player.getCurrentTime()) {
            currTime = prevCurrTime + (+new Date() / 1000 - timeLastCall);
        } else {
            currTime = player.getCurrentTime();
            timeLastCall = +new Date() / 1000;
        }
        prevCurrTime = player.getCurrentTime();
        if (currTime + (firstRun ? 0.45 : 0.21) >= player.getDuration()) {
            player.pauseVideo();
            player.seekTo(0);
            player.playVideo();
            firstRun = false;
        }
    }, 150);
}

function _vcRowOnPlayerStateChange(event) {
    if (event.data === YT.PlayerState.ENDED) {
        if (typeof event.target.loopTimeout !== 'undefined') {
            clearTimeout(event.target.loopTimeout);
        }
        event.target.seekTo(0);
    } else if (event.data === YT.PlayerState.PLAYING) {
        jQuery(event.target.getIframe()).parent().css('visibility', 'visible');
    }
}

function resizeVideo($wrapper) {
    var $videoContainer = $wrapper.parent();
    if ($videoContainer.find('iframe').width() === null) {
        setTimeout(function() {
            resizeVideo($wrapper);
        }, 500);
        return;
    }
    var $videoWrapper = $wrapper;
    $videoWrapper.css({
        width: 'auto',
        height: 'auto',
        left: 'auto',
        top: 'auto'
    });
    $videoWrapper.css('position', 'absolute');
    var vidWidth = $videoContainer.find('iframe').width();
    var vidHeight = $videoContainer.find('iframe').height();
    var containerWidth = $videoContainer.width();
    var containerHeight = $videoContainer.height();
    var finalWidth;
    var finalHeight;
    var deltaWidth;
    var deltaHeight;
    var aspectRatio = '16:9';
    if (typeof $wrapper.attr('data-video-aspect-ratio') !== 'undefined') {
        if ($wrapper.attr('data-video-aspect-ratio').indexOf(':') !== -1) {
            aspectRatio = $wrapper.attr('data-video-aspect-ratio').split(':');
            aspectRatio[0] = parseFloat(aspectRatio[0]);
            aspectRatio[1] = parseFloat(aspectRatio[1]);
        }
    }
    finalHeight = containerHeight;
    finalWidth = aspectRatio[0] / aspectRatio[1] * containerHeight;
    deltaWidth = (aspectRatio[0] / aspectRatio[1] * containerHeight) - containerWidth;
    deltaHeight = (containerWidth * aspectRatio[1]) / aspectRatio[0] - containerHeight;
    if (finalWidth >= containerWidth && finalHeight >= containerHeight) {
        height = containerHeight;
        width = aspectRatio[0] / aspectRatio[1] * containerHeight
    } else {
        width = containerWidth;
        height = (containerWidth * aspectRatio[1]) / aspectRatio[0];
    }
    marginTop = -(height - containerHeight) / 2;
    marginLeft = -(width - containerWidth) / 2;
    $videoContainer.find('iframe').css({
        'width': width,
        'height': height,
        'marginLeft': marginLeft,
        'marginTop': marginTop
    }).attr('width', width).attr('height', height);
}
var tag = document.createElement('script');
tag.src = 'https://www.youtube.com/iframe_api';
var firstScriptTag = document.getElementsByTagName('script')[0];
firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);

function onYouTubeIframeAPIReady() {
    var videos = _vcRowGetAllElementsWithAttribute('data-youtube-video-id');
    for (var i = 0; i < videos.length; i++) {
        var videoID = videos[i].getAttribute('data-youtube-video-id');
        var elemID = '';
        for (var k = 0; k < videos[i].childNodes.length; k++) {
            if (/div/i.test(videos[i].childNodes[k].tagName)) {
                elemID = videos[i].childNodes[k].getAttribute('id');
                break;
            }
        }
        if (elemID === '') {
            continue;
        }
        var mute = videos[i].getAttribute('data-mute');
        var player = new YT.Player(elemID, {
            height: 'auto',
            width: 'auto',
            videoId: videoID,
            playerVars: {
                autohide: 1,
                autoplay: 1,
                fs: 0,
                showinfo: 0,
                loop: 1,
                modestBranding: 1,
                start: 0,
                controls: 0,
                rel: 0,
                disablekb: 1,
                iv_load_policy: 3,
                wmode: 'transparent'
            },
            events: {
                'onReady': _vcRowOnPlayerReady,
                'onStateChange': _vcRowOnPlayerStateChange,
            }
        });
        player.isMute = mute === 'true';
        player.forceHD = videos[i].getAttribute('data-force-hd') === 'true';
        setTimeout(function() {
            jQuery('#' + elemID).css('visibility', 'visible');
        }, 500);
    }
}
jQuery(document).ready(function($) {
    if ($('body').hasClass('vc_editor')) {
        return;
    }
    if (navigator.userAgent.match(/Mobi/)) {
        $('.bwvca_video_row').attr('style', 'display: none !important');
        return;
    }
    $('.bwvca_video_row').each(function() {
        var row = $(document.bwvcaFindElementParentRow($(this)[0]));
        var videoContainer = $('<div></div>').addClass('bwvca_video_inner').css('opacity', Math.abs(parseFloat($(this).attr('data-opacity')) / 100));
        row.css('position', 'relative');
        $(this).children().prependTo(videoContainer);
        videoContainer.prependTo(row);
    });
    $('[data-youtube-video-id], [data-vimeo-video-id]').each(function() {
        var $this = $(this);
        setTimeout(function() {
            resizeVideo($this);
        }, 100);
    });
    $(window).resize(function() {
        $('[data-youtube-video-id], [data-vimeo-video-id]').each(function() {
            var $this = $(this);
            setTimeout(function() {
                resizeVideo($this);
            }, 2);
        });
    });
    $('[data-vimeo-video-id]').each(function() {
        var player = $f($(this).find('iframe')[0]);
        var $this = $(this);
        player.addEvent('ready', function() {
            if ($this.attr('data-mute') === 'true') {
                player.api('setVolume', 0);
            }
            player.addEvent('playProgress', function(data, id) {
                jQuery('#' + id).parent().css('visibility', 'visible');
            });
        });
    });
});
document.addEventListener('DOMContentLoaded', function() {
    var elements = document.querySelectorAll('.bwvca_hover_row');
    Array.prototype.forEach.call(elements, function(el, i) {
        var row = document.bwvcaFindElementParentRow(el);
        row.style.overflow = 'hidden';
        row.classList.add('has_bwvca_hover_row');
        var div = document.createElement('div');
        div.classList.add('bwvca_hover_inner');
        div.setAttribute('data-type', el.getAttribute('data-type'));
        div.setAttribute('data-amount', el.getAttribute('data-amount'));
        div.setAttribute('data-inverted', el.getAttribute('data-inverted'));
        div.style.opacity = Math.abs(parseFloat(el.getAttribute('data-opacity')) / 100);
        div.style.backgroundImage = 'url(' + el.getAttribute('data-bg-image') + ')';
        var offset = 0;
        if (el.getAttribute('data-type') === 'tilt') {
            offset = -parseInt(el.getAttribute('data-amount')) * .6 + '%';
        } else {
            offset = -parseInt(el.getAttribute('data-amount')) + 'px';
        }
        div.style.top = offset;
        div.style.left = offset;
        div.style.right = offset;
        div.style.bottom = offset;
        row.insertBefore(div, row.firstChild);
    });
    if (navigator.userAgent.match(/Mobi/)) {
        return;
    }
    var elements = document.querySelectorAll('.has_bwvca_hover_row');
    Array.prototype.forEach.call(elements, function(row, i) {
        row.addEventListener('mousemove', function(e) {
            var parentRow = e.target.parentNode;
            while (!parentRow.classList.contains('has_bwvca_hover_row')) {
                if (parentRow.tagName === 'HTML') {
                    return;
                }
                parentRow = parentRow.parentNode;
            }
            var rect = parentRow.getBoundingClientRect();
            var top = e.pageY - (rect.top + window.pageYOffset);
            var left = e.pageX - (rect.left + window.pageXOffset);
            top /= parentRow.clientHeight;
            left /= parentRow.clientWidth;
            var hoverRows = parentRow.querySelectorAll('.bwvca_hover_inner');
            Array.prototype.forEach.call(hoverRows, function(hoverBg, i) {
                var amount = parseFloat(hoverBg.getAttribute('data-amount'));
                var inverted = hoverBg.getAttribute('data-inverted') === 'true';
                var transform;
                if (hoverBg.getAttribute('data-type') === 'tilt') {
                    var rotateY = left * amount - amount / 2;
                    var rotateX = (1 - top) * amount - amount / 2;
                    if (inverted) {
                        rotateY = (1 - left) * amount - amount / 2;
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
                    if (inverted) {
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
        row.addEventListener('mouseout', function(e) {
            var parentRow = e.target.parentNode;
            while (!parentRow.classList.contains('has_bwvca_hover_row')) {
                if (parentRow.tagName === 'HTML') {
                    return;
                }
                parentRow = parentRow.parentNode;
            }
            var hoverRows = parentRow.querySelectorAll('.bwvca_hover_inner');
            Array.prototype.forEach.call(hoverRows, function(hoverBg, i) {
                var amount = parseFloat(hoverBg.getAttribute('data-amount'));
                hoverBg.style.transition = 'all 3s ease-in-out';
                if (hoverBg.getAttribute('data-type') === 'tilt') {
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
document.addEventListener('DOMContentLoaded', function() {
    var elements = document.querySelectorAll('.bwvca_background_row');
    Array.prototype.forEach.call(elements, function(el, i) {
        var row = document.bwvcaFindElementParentRow(el);
        var styles = getComputedStyle(el);
        row.style.backgroundImage = styles.backgroundImage;
        row.style.backgroundColor = styles.backgroundColor;
        row.style.backgroundRepeat = styles.backgroundRepeat;
        row.style.backgroundSize = styles.backgroundSize;
        row.style.backgroundPosition = styles.backgroundPosition;
    });
});
