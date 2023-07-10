(function ($) {
    
    importWpmfTaxo = function(doit, button) {
        jQuery(button).closest('div').find('.spinner').show().css('visibility','visible');
        $.ajax({
            type: "POST",
            url: ajaxurl,
            data: {
                action: "import_categories",
                doit: doit
            },
            success: function(response) {
                jQuery(button).closest('div').find('.spinner').hide();
                jQuery(button).closest('div').find('.wpmf_info_update').fadeIn(1000).delay(500).fadeOut(1000);
            }
        });
    }
    
    bindSelect = function(){
        $('.wpmf-section-title').on('click',function(){
            var title = $(this).data('title');
            if($(this).closest('li').hasClass('open')){
                $('.content_list_'+ title +'').slideUp('fast');
                $(this).closest('li').removeClass('open');
            }else{
                $('.content_list_'+ title +'').slideDown('fast');
                $(this).closest('li').addClass('open')
            }
        });
        
        $('#wmpfImpoBtn').on('click',function(){
            $(this).addClass('button-primary');
            importWpmfTaxo(true,this);
        });
        
        $('.btn_import_gallery').on('click',function(){
            $('.btn_import_gallery').closest('div').find('.spinner').show().css('visibility','visible');
            $(this).addClass('button-primary');
            $.ajax({
                type: 'POST',
                url : ajaxurl,
                data :  {
                    action : "import_gallery",
                    doit : true
                },
                success : function(res){
                    $('.btn_import_gallery').closest('div').find('.spinner').hide();
                    $('.btn_import_gallery').closest('div').find('.wpmf_info_update').fadeIn(1000).delay(500).fadeOut(1000);
                }
            });
        });
        
        $('.cb_option').unbind('click').bind('click', function() {
            var check = $(this).attr('checked');
            var type = $(this).attr('type');
            var value;
            var $this = $(this);
            if (type == 'checkbox') {
                if (check == 'checked') {
                    value = 1;
                    if($(this).data('label') == 'wpmf_active_media'){
                        $('.wpmf_show_media').slideDown('fast');
                    }
                } else {
                    if($(this).data('label') == 'wpmf_active_media'){
                        $('.wpmf_show_media').slideUp('fast');
                    }
                    value = 0;
                }
                $('input[name="'+ $(this).data('label') +'"]').val(value);
            }else if(type == 'radio'){
                value = $(this).val();
                $('input[name="'+ $(this).data('label') +'"]').val(value);
            }else{
                $this.closest('div').find('.spinner').show().css('visibility','visible');
                $('.cb_option').removeClass('button-primary');
                $(this).addClass('button-primary');
                value = $(this).data('value');
                $.ajax({
                    type: "POST",
                    url: ajaxurl,
                    data: {
                        action: "update_opt",
                        label: $(this).data('label'),
                        value: value
                    },
                    success: function(res) {
                        $this.closest('div').find('.spinner').hide();
                        $this.closest('div').find('.wpmf_info_update').fadeIn(1000).delay(500).fadeOut(1000);
                    }
                });
            } 
        });
    }
    
    $(document).ready(bindSelect);
})(jQuery);