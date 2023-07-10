// get/post via jQuery
(function($) {
    $.extend({
        bw_capsman_getGo: function(url, params) {
            document.location = url + '?' + $.param(params);
        },
        bw_capsman_postGo: function(url, params) {
            var $form = $("<form>")
                .attr("method", "post")
                .attr("action", url);
            $.each(params, function(name, value) {
                $("<input type='hidden'>")
                    .attr("name", name)
                    .attr("value", value)
                    .appendTo($form);
            });
            $form.appendTo("body");
            $form.submit();
        }
    });
})(jQuery);


jQuery(function() {
  jQuery("#bw_capsman_select_all").button({
    label: bw_capsman_data.select_all
  }).click(function(event){
		event.preventDefault();
    bw_capsman_select_all(1);
  });

	if (typeof bw_capsman_current_role === 'undefined' || 'administrator' !== bw_capsman_current_role ) {
    jQuery("#bw_capsman_unselect_all").button({
      label: bw_capsman_data.unselect_all
    }).click(function(event){
			event.preventDefault();
      bw_capsman_select_all(0);
    });

    jQuery("#bw_capsman_reverse_selection").button({
      label: bw_capsman_data.reverse
    }).click(function(event){
			event.preventDefault();
      bw_capsman_select_all(-1);
    });
  }

  jQuery("#bw_capsman_update_role").button({
    label: bw_capsman_data.update
  }).click(function(){
    if (!confirm(bw_capsman_data.confirm_submit)) {
			return false;
		}
		jQuery('#bw_capsman_form').submit();
  });


function ui_button_text(caption) {
  var wrapper = '<span class="ui-button-text">'+ caption +'</span>';

  return wrapper;
}


function bw_capsman_show_add_role_dialog() {
    jQuery(function($) {
      $info = $('#bw_capsman_add_role_dialog');
      $info.dialog({
        dialogClass: 'wp-dialog',
        modal: true,
        autoOpen: true,
        closeOnEscape: true,
        width: 400,
        height: 230,
        resizable: false,
        title: bw_capsman_data.add_new_role_title,
        'buttons'       : {
            'Add Role': function () {
              var role_id = $('#user_role_id').val();
              if (role_id == '') {
                alert( bw_capsman_data.role_name_required );
                return false;
              }
              if  (!(/^[\w-]*$/.test(role_id))) {
                alert( bw_capsman_data.role_name_valid_chars );
                return false;
              }
              if  ((/^[0-9]*$/.test(role_id))) {
                alert( bw_capsman_data.numeric_role_name_prohibited );
                return false;
              }
              var role_name = $('#user_role_name').val();
              var role_copy_from = $('#user_role_copy_from').val();

              $(this).dialog('close');
              $.bw_capsman_postGo( bw_capsman_data.page_url,
                           { action: 'add-new-role', user_role_id: role_id, user_role_name: role_name, user_role_copy_from: role_copy_from,
                             bw_capsman_nonce: bw_capsman_data.wp_nonce} );
            },
            Cancel: function() {
                $(this).dialog('close');
                return false;
            }
          }
      });
      $('.ui-dialog-buttonpane button:contains("Add Role")').attr("id", "dialog-add-role-button");
      $('#dialog-add-role-button').html(ui_button_text(bw_capsman_data.add_role));
      $('.ui-dialog-buttonpane button:contains("Cancel")').attr("id", "add-role-dialog-cancel-button");
      $('#add-role-dialog-cancel-button').html(ui_button_text(bw_capsman_data.cancel));
    });
}


    jQuery("#bw_capsman_add_role").button({
        label: bw_capsman_data.add_role
    }).click(function(event) {
        event.preventDefault();
        bw_capsman_show_add_role_dialog();
    });


function bw_capsman_show_rename_role_dialog() {
    jQuery(function($) {
      $('#bw_capsman_rename_role_dialog').dialog({
        dialogClass: 'wp-dialog',
        modal: true,
        autoOpen: true,
        closeOnEscape: true,
        width: 400,
        height: 230,
        resizable: false,
        title: bw_capsman_data.rename_role_title,
        'buttons'       : {
            'Rename Role': function () {
              var role_id = $('#ren_user_role_id').val();
              var role_name = $('#ren_user_role_name').val();
              $(this).dialog('close');
              $.bw_capsman_postGo( bw_capsman_data.page_url,
                           { action: 'rename-role', user_role_id: role_id, user_role_name: role_name, bw_capsman_nonce: bw_capsman_data.wp_nonce}
                          );
            },
            Cancel: function() {
                $(this).dialog('close');
                return false;
            }
          }
      });
      $('.ui-dialog-buttonpane button:contains("Rename Role")').attr("id", "dialog-rename-role-button");
      $('#dialog-rename-role-button').html(ui_button_text(bw_capsman_data.rename_role));
      $('.ui-dialog-buttonpane button:contains("Cancel")').attr("id", "rename-role-dialog-cancel-button");
      $('#rename-role-dialog-cancel-button').html(ui_button_text(bw_capsman_data.cancel));
      $('#ren_user_role_id').val(bw_capsman_current_role);
      $('#ren_user_role_name').val(bw_capsman_current_role_name);
    });
}


    jQuery("#bw_capsman_rename_role").button({
        label: bw_capsman_data.rename_role
    }).click(function(event) {
        event.preventDefault();
        bw_capsman_show_rename_role_dialog();
    });


  jQuery("#bw_capsman_delete_role").button({
    label: bw_capsman_data.delete_role
  }).click(function(event){
		event.preventDefault();
    jQuery(function($) {
      $('#bw_capsman_delete_role_dialog').dialog({
        dialogClass: 'wp-dialog',
        modal: true,
        autoOpen: true,
        closeOnEscape: true,
        width: 320,
        height: 190,
        resizable: false,
        title: bw_capsman_data.delete_role,
        buttons: {
          'Delete Role': function() {
            var user_role_id = $('#del_user_role').val();
            if (!confirm(bw_capsman_data.delete_role)) {
              return false;
            }
            $(this).dialog('close');
            $.bw_capsman_postGo(bw_capsman_data.page_url,
                    {action: 'delete-role', user_role_id: user_role_id, bw_capsman_nonce: bw_capsman_data.wp_nonce});
          },
          Cancel: function() {
            $(this).dialog('close');
          }
        }
      });
      // translate buttons caption
      $('.ui-dialog-buttonpane button:contains("Delete Role")').attr("id", "dialog-delete-button");
      $('#dialog-delete-button').html(ui_button_text(bw_capsman_data.delete_role));
      $('.ui-dialog-buttonpane button:contains("Cancel")').attr("id", "delete-role-dialog-cancel-button");
      $('#delete-role-dialog-cancel-button').html(ui_button_text(bw_capsman_data.cancel));
    });
  });


  jQuery("#bw_capsman_add_capability").button({
    label: bw_capsman_data.add_capability
  }).click(function(event){
		event.preventDefault();
    jQuery(function($) {
      $info = $('#bw_capsman_add_capability_dialog');
      $info.dialog({
        dialogClass: 'wp-dialog',
        modal: true,
        autoOpen: true,
        closeOnEscape: true,
        width: 350,
        height: 190,
        resizable: false,
        title: bw_capsman_data.add_capability,
        'buttons'       : {
            'Add Capability': function () {
              var capability_id = $('#capability_id').val();
              if (capability_id == '') {
                alert( bw_capsman_data.capability_name_required );
                return false;
              }
              if  (!(/^[\w-]*$/.test(capability_id))) {
                alert( bw_capsman_data.capability_name_valid_chars );
                return false;
              }

              $(this).dialog('close');
              $.bw_capsman_postGo( bw_capsman_data.page_url,
                           { action: 'add-new-capability', capability_id: capability_id, bw_capsman_nonce: bw_capsman_data.wp_nonce} );
            },
            Cancel: function() {
                $(this).dialog('close');
            }
          }
      });
      $('.ui-dialog-buttonpane button:contains("Add Capability")').attr("id", "dialog-add-capability-button");
      $('#dialog-add-capability-button').html(ui_button_text(bw_capsman_data.add_capability));
      $('.ui-dialog-buttonpane button:contains("Cancel")').attr("id", "add-capability-dialog-cancel-button");
      $('#add-capability-dialog-cancel-button').html(ui_button_text(bw_capsman_data.cancel));
    });
  });


  jQuery("#bw_capsman_delete_capability").button({
    label: bw_capsman_data.delete_capability
  }).click(function(event){
		event.preventDefault();
    jQuery(function($) {
      $('#bw_capsman_delete_capability_dialog').dialog({
        dialogClass: 'wp-dialog',
        modal: true,
        autoOpen: true,
        closeOnEscape: true,
        width: 320,
        height: 190,
        resizable: false,
        title: bw_capsman_data.delete_capability,
        buttons: {
          'Delete Capability': function() {
            if (!confirm(bw_capsman_data.delete_capability +' - '+ bw_capsman_data.delete_capability_warning)) {
              return;
            }
            $(this).dialog('close');
            var user_capability_id = $('#remove_user_capability').val();
            $.bw_capsman_postGo(bw_capsman_data.page_url,
                    {action: 'delete-user-capability', user_capability_id: user_capability_id, bw_capsman_nonce: bw_capsman_data.wp_nonce});
          },
          Cancel: function() {
            $(this).dialog('close');
          }
        }
      });
      // translate buttons caption
      $('.ui-dialog-buttonpane button:contains("Delete Capability")').attr("id", "dialog-delete-capability-button");
      $('#dialog-delete-capability-button').html(ui_button_text(bw_capsman_data.delete_capability));
      $('.ui-dialog-buttonpane button:contains("Cancel")').attr("id", "delete-capability-dialog-cancel-button");
      $('#delete-capability-dialog-cancel-button').html(ui_button_text(bw_capsman_data.cancel));
    });
  });

  jQuery("#bw_capsman_default_role").button({
    label: bw_capsman_data.default_role
  }).click(function(event){
		event.preventDefault();
    jQuery(function($) {
      $('#bw_capsman_default_role_dialog').dialog({
        dialogClass: 'wp-dialog',
        modal: true,
        autoOpen: true,
        closeOnEscape: true,
        width: 320,
        height: 190,
        resizable: false,
        title: bw_capsman_data.default_role,
        buttons: {
          'Set New Default Role': function() {
            $(this).dialog('close');
            var user_role_id = $('#default_user_role').val();
            $.bw_capsman_postGo(bw_capsman_data.page_url,
                    {action: 'change-default-role', user_role_id: user_role_id, bw_capsman_nonce: bw_capsman_data.wp_nonce});
          },
          Cancel: function() {
            $(this).dialog('close');
          }
        }
      });
      // translate buttons caption
      $('.ui-dialog-buttonpane button:contains("Set New Default Role")').attr("id", "dialog-default-role-button");
      $('#dialog-default-role-button').html(ui_button_text(bw_capsman_data.set_new_default_role));
      $('.ui-dialog-buttonpane button:contains("Cancel")').attr("id", "default-role-dialog-cancel-button");
      $('#default-role-dialog-cancel-button').html(ui_button_text(bw_capsman_data.cancel));
    });
  });

  jQuery('#bw_capsman_reset_roles_button').button({
    label: bw_capsman_data.reset
  }).click(function(event){
    event.preventDefault();
    if (!confirm( bw_capsman_data.reset_warning )) {
      return false;
    }
    jQuery.bw_capsman_postGo(bw_capsman_data.page_url, {action: 'reset', bw_capsman_nonce: bw_capsman_data.wp_nonce});
  });

});


// change color of apply to all check box - for multi-site setup only
function bw_capsman_applyToAllOnClick(cb) {
  el = document.getElementById('bw_capsman_apply_to_all_div');
  if (cb.checked) {
    el.style.color = '#FF0000';
  } else {
    el.style.color = '#000000';
  }
}
// end of bw_capsman_applyToAllOnClick()


// turn on checkbox back if clicked to turn off
function turn_it_back(control) {

  control.checked = true;

}
// end of turn_it_back()


/**
 * Manipulate mass capability checkboxes selection
 * @param {bool} selected
 * @returns {none}
 */
function bw_capsman_select_all(selected) {

	var qfilter = jQuery('#quick_filter').val();
  var form = document.getElementById('bw_capsman_form');
  for (i = 0; i < form.elements.length; i++) {
    el = form.elements[i];
    if (el.type !== 'checkbox') {
      continue;
    }
    if (el.name === 'bw_capsman_caps_readable' || el.name === 'bw_capsman_show_deprecated_caps' ||
		el.name === 'bw_capsman_apply_to_all' || el.disabled ||
		el.name.substr(0, 8) === 'wp_role_')  {
      continue;
    }
		if (qfilter!=='' && !form.elements[i].parentNode.bw_capsman_tag) {
			continue;
		}
		if (selected >= 0) {
			form.elements[i].checked = selected;
		} else {
			form.elements[i].checked = !form.elements[i].checked;
		}

  }

}
// end of bw_capsman_select_all()


function bw_capsman_turn_caps_readable(user_id) {

	if (user_id === 0) {
		var bw_capsman_object = 'role';
	} else {
		var bw_capsman_object = 'user';
	}

	jQuery.bw_capsman_postGo(bw_capsman_data.page_url, {action: 'caps-readable', object: bw_capsman_object, user_id: user_id, bw_capsman_nonce: bw_capsman_data.wp_nonce});

}
// end of bw_capsman_turn_caps_readable()


function bw_capsman_turn_deprecated_caps(user_id) {

	var bw_capsman_object = '';
	if (user_id === 0) {
		bw_capsman_object = 'role';
	} else {
		bw_capsman_object = 'user';
	}
	jQuery.bw_capsman_postGo(bw_capsman_data.page_url, {action: 'show-deprecated-caps', object: bw_capsman_object, user_id: user_id, bw_capsman_nonce: bw_capsman_data.wp_nonce});

}
// bw_capsman_turn_deprecated_caps()


function bw_capsman_role_change(role_name) {

	jQuery.bw_capsman_postGo(bw_capsman_data.page_url, {action: 'role-change', object: 'role', user_role: role_name, bw_capsman_nonce: bw_capsman_data.wp_nonce});

}
// end of bw_capsman_role_change()


function bw_capsman_filter_capabilities(cap_id) {
	var div_list = jQuery("div[id^='bw_capsman_div_cap_']");
	for (i=0; i<div_list.length; i++) {
		if (cap_id!=='' && div_list[i].id.substr(11).indexOf(cap_id)!==-1) {
			div_list[i].bw_capsman_tag = true;
			div_list[i].style.color = '#27CF27';
		} else {
			div_list[i].style.color = '#000000';
			div_list[i].bw_capsman_tag = false;
		}
	};

}
// end of bw_capsman_filter_capabilities()
