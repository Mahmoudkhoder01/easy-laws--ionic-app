jQuery(document).ready(function($){

    function alert(title, text){
        if(typeof text == 'undefined'){ text = title; title = ''; }
        swal({ title: title, text: text, type: 'warning' });
    }

    function success(title, text){
        if(typeof text == 'undefined'){ text = title; title = ''; }
        swal({ title: title, text: text, type: 'success' });
    }

    function close_user_modals(){
        $('#modal-signup').modal('hide');
        $('#modal-login').modal('hide');
    }

    $("form.validate").each(function() {
        $(this).validate({
            ignore: ":not(:visible)",
            errorPlacement: function(e, a) {
                // a.after(e);
            }
        });
    });

    function initialize(){

        $('.carousel').each(function(){
            var el = $(this),
                sm = el.data('items-sm') || 2,
                md = el.data('items-md') || 3,
                lg = el.data('items-lg') || 5;
                
            el.owlCarousel({
                rtl:true,
                loop:false,
                margin:10,
                mouseDrag: true,
                autoplay:false,
                autoplayTimeout:4000,
                nav:true,
                navText: ['<i class="fa fa-arrow-right"></i>', '<i class="fa fa-arrow-left"></i>'],
                dots: false,
                responsive:{
                    0:{ items: sm },
                    600:{ items: md },
                    1000:{ items: lg }
                }
            });
        })

        $('.testimonials-carousel').owlCarousel({
            rtl:true,
            loop:true,
            margin:0,
            mouseDrag: true,
            autoplay:false,
            autoplayTimeout:4000,
            nav:false,
            // navText: ['<i class="fa fa-arrow-right"></i>', '<i class="fa fa-arrow-left"></i>'],
            dots: true,
            items: 1,
            autoplay: true,
            autoplayTimeout: 5000,
            autoplayHoverPause: true,
        });

        $('.venobox').venobox({
            spinner: 'wave',
            spinColor: '#e52329',
            titleattr: 'data-title',
            numeratio: true,
            infinigall: true
        });

        $('.btn-login').off('click').on('click', function(e){
            e.preventDefault();
            $('#modal-signup').modal('hide');
            $('#modal-login').modal('show');
        });

        $('.btn-signup').off('click').on('click', function(e){
            e.preventDefault();
            $('#modal-login').modal('hide');
            $('#modal-signup').modal('show');
        });

        $('.app_directions').on('click', function(e){
            e.preventDefault();
            var el = $(this),
                txt = el.html(),
                address = el.data('address'),
                _locating = el.data('locating');

            if (navigator.geolocation) {
                el.addClass('blink').html(_locating);

                navigator.geolocation.getCurrentPosition( function(position){
                    el.removeClass('blink').html(txt);

                    var lat = position.coords.latitude,
                        lng = position.coords.longitude,
                        link = 'http://maps.google.com/maps?saddr='+lat+','+lng+'&daddr='+address;

                    window.location = link;
                }, function(error){
                    el.removeClass('blink').html(txt);
                    var errors = {
                        1: 'Permission denied',
                        2: 'Position unavailable',
                        3: 'Request timeout'
                    };
                    alert("Error: " + errors[error.code]);
                },{
                    enableHighAccuracy: true, timeout: 99999, maximumAge: 0
                });
            } else {
                alert("Geolocation is not supported by this browser");
            }
        });

        function design_glitches(){
            $('.subject-item').each(function(){
                var el = $(this),
                    img = el.find('.img');
                img.height(img.width());
            });
        }
        $(window).bind('load resize',function () {
            design_glitches();
        });
        design_glitches();
    }

    initialize();
});
