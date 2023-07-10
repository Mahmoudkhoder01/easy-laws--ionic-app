jQuery(function($) {
    // Init
    $(document).ready(function() {

        // if( typeof(BWD_startup_panel) === 'undefined' ){
        //     var BWD_startup_panel = '.us_latest';
        // }

        $('#products_grid').mixItUp({
    		load: {
      			filter: BWD_startup_panel,
      			sort: 'myorder:asc'
    		},
    		controls: {
      			// toggleFilterButtons: true
    		},
    		animation: {
				duration: 400,
				effects: 'fade translateZ(100px)',
				easing: 'cubic-bezier(0.25, 0.46, 0.45, 0.94)'
			}
  		});
        var inputText;
        var $matching = $();
        // Delay function
        var delay = (function() {
            var timer = 0;
            return function(callback, ms) {
                clearTimeout(timer);
                timer = setTimeout(callback, ms);
            };
        })();

        setTimeout(function(){
            $("input#bwa-search").focus();
        },500);

        $("input#bwa-search").keyup(function() {
            delay(function() {
                inputText = $("#bwa-search").val().toLowerCase();
                if ((inputText.length) > 0) {
                    $('.mix').each(function() {
                        if ($(this).find('h2').text().toLowerCase().match(inputText)) {
                            $matching = $matching.add(this);
                        } else {
                            $matching = $matching.not(this);
                        }
                    });
                    $("#products_grid").mixItUp('filter', $matching);
                } else {
                    $("#products_grid").mixItUp('filter', 'all');
                }
            }, 200);
        });

        $('.ext-popup').magnificPopup({
            disableOn: 700,
            type: 'iframe',
            mainClass: 'mfp-fade',
            removalDelay: 160,
            preloader: false,

            fixedContentPos: false
        });
    });
    $('#cb-select-all').change(function() {
        var checkboxes = $("#products_grid").find(':visible').find(':checkbox');
        if ($(this).prop('checked')) {
            checkboxes.prop('checked', true);
        } else {
            checkboxes.prop('checked', false);
        }
    });
    $('#doaction').on('click', function() {
        var action = $("select[name='bulk_actions']").val();
        var batch = [];
        var ftp = {};
        if (action && action != "") {
            $("#products_grid").find(':visible').find('input:checkbox:checked').each(function(i, el) {
                var plugin = $(this).val(),
                    from = $(this).data("file"),
                    version = $(this).data("version"),
                    conflicts = $(this).data("conflicts"),
                    installed = ($(this).data("activation-status") != 'new'),
                    update_status = $(this).data("update-status"),
                    updated = (update_status == 'updated' || update_status == 'new');
                switch (action) {
                    case 'install_update_activate':
                        // conflicts...
                        if (conflicts != undefined && conflicts != '' && conflicts != 'none') {
                            batch.push({
                                "action": "deactivate",
                                "args": {
                                    "plugin": conflicts
                                }
                            });
                        }
                        if (installed && updated) {
                            batch.push({
                                "action": "upgrade-plugin",
                                "args": {
                                    "plugin": plugin
                                }
                            });
                        } else if (updated) {
                            batch.push({
                                "action": "install-plugin",
                                "args": {
                                    "from": bwd_dashboard.api_endpoint + from + '?v=' + version
                                }
                            });
                        }
                        if (plugin.indexOf('bwd-dashboard') == -1) {
                            batch.push({
                                "action": "activate",
                                "args": {
                                    "plugin": plugin
                                }
                            });
                        }
                        if ($('#ftp-wrap').is(':visible')) {
                            ftp = {
                                hostname: $('#hostname').val(),
                                username: $('#username').val(),
                                password: $('#password').val(),
                                connection_type: $('input[name=connection_type]:checked').val()
                            };
                            $('#ftp-wrap').slideUp();
                        }
                        break;
                    case 'delete':
                        batch.push({
                            "action": "delete",
                            "args": {
                                "plugin": plugin
                            }
                        });
                        break;
                    case 'deactivate':
                        if (plugin.indexOf('bwd-dashboard') == -1) {
                            batch.push({
                                "action": "deactivate",
                                "args": {
                                    "plugin": plugin
                                }
                            });
                        }
                        break;
                    case 'activate':
                        if (conflicts != undefined && conflicts != '' && conflicts != 'none') {
                            batch.push({
                                "action": "deactivate",
                                "args": {
                                    "plugin": conflicts
                                }
                            });
                        }
                        batch.push({
                            "action": "activate",
                            "args": {
                                "plugin": plugin
                            }
                        });
                        break;
                }
            });
            if (batch.length > 0) {
                var data = {
                    action: 'bwd-batch',
                    bwd_nonce: bwd_dashboard.nonce,
                    batch: JSON.stringify(batch)
                };
                if (!$.isEmptyObject(ftp)) {
                    $.extend(data, ftp);
                }
                $("#message").html("").removeClass("error");
                $("#actions_progressbar").show();
                $.post(ajaxurl, data, function(response) {
                    $("#actions_progressbar").hide();
                    var errors = [];
                    try {
                        result = $.parseJSON(response);
                        $.each(result, function(idx, el) {
                            if (el.result == false && el.msg) {
                                errors.push('<p>' + el.msg + '</p>');
                            }
                        });
                    } catch (e) {
                        // Hack for FTP credentials error
                        if (response.indexOf("form") > 0) {
                            //console.log(response);
                            response = response.replace('<input type="submit"', '<input type="button" onclick="jQuery(\'#doaction\').trigger(\'click\');"');
                            $('#ftp-wrap').html(response);
                            $('#ftp-wrap').slideDown();
                            return;
                        } else {
                            errors.push('<p><pre>' + response + '</pre></p>');
                        }
                    }
                    if (errors.length) {
                        $("#message").html(errors.join("\n")).addClass("error");
                    } else {
                        location.reload();
                    }
                });
            }
        } else {
            alert("Please select an action first.");
        }
        return false;
    });
});
