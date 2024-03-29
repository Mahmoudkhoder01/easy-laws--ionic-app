/* Capability Manager for users.php */

function bw_capsman_move_users_from_no_role_dialog() {
    jQuery('#move_from_no_role_dialog').dialog({
        dialogClass: 'wp-dialog',
        modal: true,
        autoOpen: true,
        closeOnEscape: true,
        width: 400,
        height: 200,
        resizable: false,
        title: bw_capsman_users_data.move_from_no_role_title,
        'buttons'       : {
            'OK': function () {
                bw_capsman_move_users_from_no_role();

            },
            Cancel: function() {
                jQuery(this).dialog('close');
                return false;
            }
          }
      });

      var options = jQuery("#new_role > option").clone();
      jQuery('#bw_capsman_new_role').empty().append(options);
      if (jQuery('#bw_capsman_new_role option[value="no_rights"]').length===0) {
          jQuery('#bw_capsman_new_role').append('<option value="no_rights">'+ bw_capsman_users_data.no_rights_caption +'</option>');
      }

      // Exclude change role to
      jQuery('#selectBox option[value=""]').remove();
      var new_role = jQuery('#new_role').find(":selected").val();
      if (new_role.length>0) {
          jQuery("#bw_capsman_new_role").val(new_role);
      }
  }


function bw_capsman_move_users_from_no_role() {
    new_role = jQuery('#bw_capsman_new_role').find(":selected").val();
    if (new_role.length==0) {
        alert(bw_capsman_users_data.provide_new_role_caption);
        return;
    }
    jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        dataType: 'html',
        data: {
            action: 'bw_capsman_ajax',
            sub_action: 'get_users_without_role',
            wp_nonce: bw_capsman_users_data.wp_nonce,
            new_role: new_role
        },
        success: function(response) {
            var data = jQuery.parseJSON(response);
            if (typeof data.result !== 'undefined') {
                if (data.result === 'success') {
                    bw_capsman_post_move_users_command(data);
                } else if (data.result === 'failure') {
                    alert(data.message);
                } else {
                    alert('Wrong response: ' + response)
                }
            } else {
                alert('Wrong response: ' + response)
            }
        },
        error: function(XMLHttpRequest, textStatus, exception) {
            alert("Ajax failure\n" + errortext);
        },
        async: true
    });

}


function bw_capsman_post_move_users_command(data) {
    var options = jQuery("#bw_capsman_new_role > option").clone();
    jQuery('#new_role').empty().append(options);
    jQuery("#new_role").val(data.new_role);
    var el = jQuery('.bulkactions').append();
    for(var i=0; i<data.users.length; i++) {
        if (jQuery('#user_'+ data.users[i]).length>0) {
            jQuery('#user_'+ data.users[i]).prop('checked', true);
        } else {
            var html = '<input type="checkbox" name="users[]" id="user_'+ data.users[i] +'" value="'+ data.users[i] +'" checked="checked" style="display: none;">';
            el.append(html);
        }
    }

    // submit form
    jQuery('#changeit').click();
}
