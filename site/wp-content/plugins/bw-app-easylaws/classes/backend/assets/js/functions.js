( function ( $ ) {

	if( typeof( APP ) == 'undefined' ) window.APP = {};

	window.APP = $.extend( {
		ref_tree_count: function(){
			$('.ref-count-check').off('click').on('click', function(e){
				e.preventDefault();
				var el = $(this),
					id = el.attr('id').replace('ref-', '');

				el.html('<i class="fa fa-refresh fa-spin"></i>');
				$.post(ajaxurl, {action: 'ref_tree_count', id: id}).then(function(data){
					el.html(data);
				});
			})
		},
		init: function(){
			$('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
				setTimeout(function(){
					APP.autogrow();
				}, 10);
				console.log('tab shown');
			});

			APP.floating();
			APP.dropUp();
			APP.autogrow();
			APP.editor();
			$('.__tree_wrap').each(function(){
				var el = $(this);
				APP.tree(el);
			});

			$('.input_color').colorpicker();

			$('.modal_link').off('click').on('click', function(e){
				e.preventDefault();
				var
					el = $(this),
					tit = el.attr('data-title'),
					action = el.attr('data-action'),
					id = el.attr('data-id') || 0,
					table = el.attr('data-table') || '',
					reload = el.attr('data-reload') || '',
					modal = $('#app-modal'),
					title = modal.find('.modal-title'),
					content = modal.find('#app-modal-body'),
					loader = modal.find('#app-modal-loader')
				;

				loader.show();
				title.html('');
				content.html('');
				modal.modal('show');

				$.post(ajaxurl, {action: action, id: id, table: table}).then(function(data){
					loader.hide();
					title.html(tit);
					content.html(data);
				});

				modal.on('hidden.bs.modal', function(){
					if(reload == 'yes') window.location.reload();
				});
			});

			$('.subject-document-creator').on('click', function(){
				var el = $(this),
					id = el.attr('data-id'),
					span = el.find('span'),
					init_text = span.html();

				span.attr('disabled', 'disabled').html('Generating...');
				$.post(ajaxurl, {action: 'app_create_doc', id: id}).then(function(data){
					span.removeAttr('disabled').html(init_text);
					if(data == 'Error'){
						alert('Error Generating File');
					} else {
						el.after('<a href="'+data+'" class="btn btn-success" target="_blank"><i class="fa fa-arrow-down"></i> Download File</a>');
					}
				});
			});

			$('.__tree_wrap .dropdown-menu').on('click', function(e) {
				if(!e.isPropagationStopped()){
    				e.stopPropagation();
    			}
			});
			$('input.tagsinput').tagsinput();


			$('textarea.app_editor_sm').each(function(){
				APP.editor_sm($(this));
			});

			$('#app-table-sortable').find("#the-list").sortable({
				'items': 'tr',
				'axis': 'y',
				'helper': function(e, ui) {
					ui.children().children().each(function() {
						$(this).width($(this).width());
					});
					return ui;
				},
				'update' : function(e, ui) {
					$.post( ajaxurl, {
						action: 'app-update-menu-order',
						order: $("#the-list").sortable("serialize"),
					});
				}
			});
			$('form.app_dash_form').areYouSure();
			$('.app_select').selectize();
			$('.app_select_sortable').each(function(){
				var el = $(this),
					table = el.data('table'),
					can_add = el.data('can_add'),
					options = {
						plugins: ['remove_button', 'drag_drop'],
				    	delimiter: ',',
				    	persist: false,
				    	maxItems: null,
				    	maxOptions: 9999,
					};

				if(can_add != undefined && can_add == 'yes'){
					options.create = function(input, callback) {
				        // return { value: input, text: input }
				        el.parent().find('div.selectize-control').addClass('loading');
				        $.post(ajaxurl, {
				        	action:'create_from_select', 'title': input, 'table': table
				        }, function(data){
				        	el.parent().find('div.selectize-control').removeClass('loading');
				        	callback({ value: data.value, text: data.text });
				        }, 'json');
				    }
				}

				el.selectize(options);
			});
			$('.repeater').each(function(){
				$(this).repeater({
		            show: function () {
		            	var el = $(this);
		                el.slideDown(function(){
		                	APP.editor_sm(el.find('textarea.app_editor_sm'));
		                });
		            },
		            hide: function (deleteElement) {
		                if(confirm('Are you sure you want to delete this element?')) {
		                    $(this).slideUp(deleteElement);
		                }
		            },
		            ready: function (setIndexes) {}
		        });
			});

			if($('.app_media').length>0){
				// console.log('media found');
				$('.app_media').off('click').on( 'click', function( atts ){
					var wrp = jQuery(APP.media.els).closest('.attach-field-wrp'), url = atts.url;
					wrp.find('input.app-param').val(atts.id);
					if(atts.sizes && typeof atts.sizes.thumbnail == 'object' ){
						var url = atts.sizes.thumbnail.url,
							url_full = atts.sizes.full.url || '';
					} else {
						var url = url_full = VARS.assets_url+'/img/video.jpg';
					}
					wrp.prepend('<div data-id="'+atts.id+'" class="img-wrp"><img title="Drag image to sort" src="'+url+'" data-full-url="'+url_full+'" alt="" /><i title="Delete" class="fa fa-close"></i><div class="img-title">'+atts.filename+'</div></div>');
					APP.upload_helper( wrp );
				}, APP.media.opens );

				$('.attach-field-wrp').each(function(){
					var el = $(this);
					APP.upload_helper( el );
				});
			}

			$('body').on('click','td.clickable', function() {
				var el = $(this), url = el.attr('data-url');
				if(url.length > 2) window.location.href = url;
			});
		},

		tree: function(el){
			var tree = el.find(".__tree"),
				tree_search = el.find(".__tree_search"),
				input = el.find(".__input"),
				expand = el.find(".__expand"),
				collapse = el.find(".__collapse");

			el.on('click', function(){
				setTimeout(function(){
					tree_search.focus();
				}, 100);
			})

			input.tagsinput({
				itemValue: function(item) {
    				return item.ID;
  				},
				itemText: function(item) {
    				return item.text;
  				}
			});
			input.tagsinput("input").attr("readonly", "readonly");
			input.on("itemRemoved", function(e) {
				if (!e.options || !e.options.preventPost) {
					var nid = e.item.node;
					tree.treeview("uncheckNode", nid);
				}
			});

			tree.treeview({
				data: el.attr('data-items'),
				showCheckbox: true,
				checkboxFirst: true,
				levels: 1,
			});

			tree.on("nodeChecked", function(e, node){
				if(typeof node !== "object") return;
				input.tagsinput("add", {
				    "ID": node.ID,
		      		"text": node.text,
			  		"node": node
	       		});
			});
			tree.on("nodeUnchecked", function(e, node){
				if(typeof node !== "object") return;
				input.tagsinput("remove", {
				    "ID": node.ID,
		      		"text": node.text,
	          		"node": node
	       		}, {preventPost: true});
			});

			tree_search.on("keyup", function(){
				var pattern = tree_search.val();
		        var options = {
					ignoreCase: true,
		            exactMatch: false,
		            revealResults: true
		        };
		        if(pattern.length){
		        	tree.treeview("collapseAll");
		        	var results = tree.treeview("search", [ pattern, options ]);

		        	if(results && results.length > 0){
			        	var roots = tree.treeview('getSiblings', 0);
	            		// roots.push(tree.treeview('getNode', 0));

	            		var unrelated = APP.collectUnrelated(roots);
	            		$.each(unrelated, function (i, un) {
	            			tree.treeview('disableNode', un, {silent: true});
	            		});
	            	} else {
	            		APP.clear_search(tree);
	            	}
		        } else {
		        	APP.clear_search(tree);
		        }
			});

			expand.on("click", function(e){
				e.preventDefault();
				tree.treeview("expandAll");
			});
			collapse.on("click", function(e){
				e.preventDefault();
				tree.treeview("collapseAll");
			});

			setTimeout(function(){
				var values = tree.treeview('getChecked');
				input.val(''); // reset input (for edit)
				tree.treeview("collapseAll");

				$.each(values, function(index, node){
					tree.treeview('revealNode', node);
					input.tagsinput("add", {
					    "ID": node.ID,
			      		"text": node.text,
				  		"node": node
		       		});
				});
				// console.log('values_initial_checked', values);
			},10);
		},

		clear_search: function(tree){
			tree.treeview("clearSearch");
			tree.treeview("collapseAll");
	    	tree.treeview("enableAll");
		},

		collectUnrelated: function(nodes) {
	        var unrelated = [];
	        $.each(nodes, function (i, n) {
	            if (!n.searchResult && !n.state.expanded) { // no hit, no parent
	                // unrelated.push(n.nodeId);
	                unrelated.push(n);
	            }
	            if (!n.searchResult && n.nodes) { // recurse for non-result children
	                $.merge(unrelated, APP.collectUnrelated(n.nodes));
	            }
	        });
	        // console.log('unrelated', unrelated);
	        return unrelated;
	    },

		dropUp: function() {
			$(window).on('scroll', function(){
				var dropUpMarginBottom = 50;
	    		var windowHeight = $(window).height();
	    		$(".__tree_wrap").each(function() {
	      			var dropDownMenuHeight,
	          		rect = this.getBoundingClientRect();

	      			if (rect.top > windowHeight) {
	        			return;
	      			}

	      			dropDownMenuHeight = $(this).children('.dropdown-menu').height();

	      			if( (windowHeight - rect.bottom) < (dropDownMenuHeight + dropUpMarginBottom) && (rect.top > dropDownMenuHeight) ){
	    				$(this).addClass("dropup");
	    				$(this).removeClass("dropdown");
	    			} else {
	    				$(this).removeClass("dropup");
	    				$(this).addClass("dropdown");
	    			}

	    		});
	    	});
		},
		autogrow: function(){
			$('textarea.autogrow').each(function(){
				var el = $(this);
				el.autogrow({vertical: true, horizontal: false, flickering: false});
			});
		},
		editor: function(){
			$('textarea.app_editor').each(function(){
				var el = $(this),
				lang = el.data('lang') || 'en';
				el.trumbowyg({
					lang: lang,
					svgPath: VARS.assets_url+'/img/icons.svg',
					resetCss: true,
					removeformatPasted: true,
					autogrow: true,
					fullscreenable: true,
					btns: ['formatting', '|', 'bold', 'italic', 'underline', 'strikethrough', '|', 'foreColor', 'backColor', '|', 'align', '|', 'lists', '|', 'image', '|', 'link', '|', 'table', '|', 'removeformat', '|', 'viewHTML', 'fullscreen']
				});
			});
		},
		editor_sm: function(el){
			el.trumbowyg('destroy');
			var lang = el.data('lang') || 'en';
			setTimeout(function(){
				el.trumbowyg({
					lang: lang,
					svgPath: VARS.assets_url+'/img/icons.svg',
					resetCss: true,
					removeformatPasted: true,
					autogrow: false,
					fullscreenable: false,
					btns: ['formatting', '|', 'bold', 'italic', 'underline', 'strikethrough', '|', 'foreColor', 'backColor', '|', 'align', '|', 'lists', '|', 'image', '|', 'link', '|', 'table', '|', 'removeformat', '|', 'viewHTML', 'fullscreen']
					// btns: ['formatting', '|', 'bold', 'italic', 'underline', '|', 'foreColor', 'backColor', '|', 'align', '|', 'lists', '|', 'viewHTML']
				});
			}, 10);
		},
		floating: function(){
			if(!$('.floating').length) return;
			var y = $('.floating').offset().top;
			$(window).on('scroll resize', function() {
				var w = $('.floating').parent().width();
				if($(window).width() < 991) return;
				$('.floating').width(w);
			    if ($(window).scrollTop() > y){
			        $('.floating').addClass('is_floating');
			    } else {
			        $('.floating').removeClass('is_floating');
			    }
			});
		},

		upload_helper: function(el){
			$('div.attach-field-wrp').sortable({
				items : 'div.img-wrp',
				placeholder: "ui-state-highlight",
				update : function( e, el ){
					console.log('el', $(el));
					APP.upload_refresh( $(el.item).parent() );
				}
			});

			el.find('div.img-wrp i').off('click').on( 'click', el, function( e ){
				jQuery(this).closest('div.img-wrp').remove();
				APP.upload_refresh( e.data );
			});

			APP.upload_refresh( el );
		},

		upload_refresh: function(el){
			var val = [];
			el.find('div.img-wrp').each(function(){
				val[ val.length ] = jQuery(this).data('id');
			});
			if( val.length <= 4 ){
				el.removeClass('img-wrp-medium').removeClass('img-wrp-large');
			}else if( val.length > 4 && val.length < 9 ){
				el.addClass('img-wrp-medium').removeClass('img-wrp-large');
			}else if( val.length >= 9 ){
				el.removeClass('img-wrp-medium').addClass('img-wrp-large');
			}

			el.find('input.app-param').val( val.join(',') );

			el.find('div.img-wrp img').off('click').on( 'click', el, function( e ){
				e.preventDefault();
				window.open($(this).attr('data-full-url'));
				// el.find('.app_media').trigger('click');
			});
		},

		media : {
			el : null,
			callback : null,
			uploader : null,
			open : function( e ){
				if( typeof e.preventDefault == 'function' ) e.preventDefault();
				atts = $().extend({ frame: 'select', multiple: false, title: 'Choose Image', button: 'Choose Image', type: 'image' }, e.data.atts );

				APP.media.el = this;

				if( typeof e.data.callback == 'function' )
					APP.media.callback = e.data.callback;
				else APP.media.callback = null;

		        if ( APP.media.uploader ) {
		           return APP.media.uploader.open();
		        }

				var insertImage = wp.media.controller.Library.extend({
				    defaults :  _.defaults({
			            id: 'insert-image',
			            title: atts.title,
			            button: {
			                text: atts.button
			            },
			            multiple: false,
						editing:   true,
						allowLocalEdits: true,
			            displaySettings: true,
			            displayUserSettings: true,
			            type : atts.type
				      }, wp.media.controller.Library.prototype.defaults )
				});

		        //Extend the wp.media object
		        APP.media.uploader = wp.media.frames.file_frame = wp.media({
		            frame: atts.frame,
		            state : 'insert-image',
				    states : [ new insertImage() ]
		        });

		        APP.media.uploader.on('select', function( e ) {

			        var currentSize = $('.attachment-display-settings .size').val()
		        	var state = APP.media.uploader.state('insert-image');
		            var attachments = state.get('selection');

		            if( attachments.length === 0 ){

			            if( $('#embed-url-field').get(0) && $('#embed-url-field').val() != null ){
				            if( typeof APP.media.callback == 'function' )
					     	 	APP.media.callback( {
						     	 		url: $('#embed-url-field').val(), sizes: {} },
						     	 		$(APP.media.el)
						     	 	);
			            }

		            }else{

			            attachments.map( function( attachment ) {

					     	 var attachment = attachment.toJSON();
					     	 attachment.size = currentSize;
					     	 if( typeof APP.media.callback == 'function' )
					     	 	APP.media.callback( attachment, $(APP.media.el) );
					    });

				    }

		        });

				APP.media.uploader.on('open', function( e ) {

				 	var ids = $(APP.media.el).parent().find('.app-param').val();
				 	if( ids === undefined || ids == null || ids == '' )
				 		return;

				 	ids = ids.split(',');

				 	var selection = APP.media.uploader.state().get('selection');
				 	var attachments = [];

				 	ids.forEach(function( id ){
						attachments[ attachments.length ] = wp.media.attachment( id );
					});

					selection.add( attachments );


				});

		        //Open the uploader dialog
		       return APP.media.uploader.open();

		    },

		    els : null,

			callbacks : null,

			uploaders : null,

			opens : function( e ){
				if( typeof e.preventDefault == 'function' ) e.preventDefault();
				APP.media.els = this;
				var type = $(e.target).attr('data-type') || 'image';
				// console.log('type', type);
				if( typeof e.data == 'function' ) APP.media.callbacks = e.data;
				else APP.media.callbacks = null;

		        // if ( APP.media.uploaders ) {
		        //    APP.media.uploaders.open();
		        //    return false;
		        // }



		        //Extend the wp.media object
		        APP.media.uploaders = wp.media.frames.file_frame = wp.media({
		            title: 'Choose Files',
		            button: {
		                text: 'Choose Files'
		            },
		            multiple: true,
					editing:   true,
					allowLocalEdits: true,
		            displaySettings: true,
		            displayUserSettings: true,
		            library: { type : type}, // audio, video
		        });

		        APP.media.uploaders.on('select', function( e ) {

		            var attachments = APP.media.uploaders.state().get('selection');
		            attachments.map( function( attachment ) {
				     	 var attachment = attachment.toJSON();
				     	 if( typeof APP.media.callbacks == 'function' )
				     	 	APP.media.callbacks( attachment, $(APP.media.els) );
				    });

		        });

		        APP.media.uploaders.on('open', function( e ) {

					// Maybe we dont need to active selected images
					return false;

				 	var ids = $(APP.media.els).parent().find('.app-param').val();
				 	if( ids === undefined || ids == null || ids == '' )
				 		return;

				 	ids = ids.split(',');

				 	var selection = APP.media.uploaders.state().get('selection');
				 	var attachments = [];

				 	ids.forEach(function( id ){
						attachments[ attachments.length ] = wp.media.attachment( id );
					});

					selection.add( attachments );

				});

		        //Open the uploader dialog
				APP.media.uploaders.open();

			   return false;

		    }

		}
	});

	$( document ).ready(function(){
		APP.init();
	});
})( jQuery );
