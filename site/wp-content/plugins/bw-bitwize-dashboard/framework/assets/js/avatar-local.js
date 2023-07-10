        var bw_local_avatar_frame, avatar_spinner, avatar_ratings, avatar_container, avatar_form_button;
        var avatar_working = false;
        jQuery(document).ready(function($) {
            $(document.getElementById('bw-local-avatar-media')).on('click', function(event) {
                event.preventDefault();
                if (avatar_working) return;
                if (bw_local_avatar_frame) {
                    bw_local_avatar_frame.open();
                    return;
                }
                bw_local_avatar_frame = wp.media.frames.bw_local_avatar_frame = wp.media({
                    title: i10n_BWLocalAvatars.insertMediaTitle,
                    button: {
                        text: i10n_BWLocalAvatars.insertIntoPost
                    },
                    library: {
                        type: 'image'
                    },
                    multiple: false
                });
                bw_local_avatar_frame.on('select', function() {
                    avatar_lock('lock');
                    var avatar_url = bw_local_avatar_frame.state().get('selection').first().toJSON().id;
                    jQuery.post(ajaxurl, {
                        action: 'assign_bw_local_avatar_media',
                        media_id: avatar_url,
                        user_id: i10n_BWLocalAvatars.user_id,
                        _wpnonce: i10n_BWLocalAvatars.mediaNonce
                    }, function(data) {
                        if (data != '') {
                            avatar_container.innerHTML = data;
                            $(document.getElementById('bw-local-avatar-remove')).show();
                            avatar_ratings.disabled = false;
                            avatar_lock('unlock');
                        }
                    });
                });
                bw_local_avatar_frame.open();
            });
            $(document.getElementById('bw-local-avatar-remove')).on('click', function(event) {
                event.preventDefault();
                if (avatar_working) return;
                avatar_lock('lock');
                $.get(ajaxurl, {
                    action: 'remove_bw_local_avatar',
                    user_id: i10n_BWLocalAvatars.user_id,
                    _wpnonce: i10n_BWLocalAvatars.deleteNonce
                }).done(function(data) {
                    if (data != '') {
                        avatar_container.innerHTML = data;
                        $(document.getElementById('bw-local-avatar-remove')).hide();
                        avatar_ratings.disabled = true;
                        avatar_lock('unlock');
                    }
                });
            });
        });

        function avatar_lock(lock_or_unlock) {
            if (undefined == avatar_spinner) {
                avatar_ratings = document.getElementById('bw-local-avatar-ratings');
                avatar_spinner = jQuery(document.getElementById('bw-local-avatar-spinner'));
                avatar_container = document.getElementById('bw-local-avatar-photo');
                avatar_form_button = jQuery(avatar_ratings).closest('form').find('input[type=submit]');
            }
            if (lock_or_unlock == 'unlock') {
                avatar_working = false;
                avatar_form_button.removeAttr('disabled');
                avatar_spinner.hide();
            } else {
                avatar_working = true;
                avatar_form_button.attr('disabled', 'disabled');
                avatar_spinner.show();
            }
        }
