<?php

	global $drag_drop_featured_image;
	$drag_drop_featured_image = new WP_Drag_Drop_Featured_Image;

	class WP_Drag_Drop_Featured_Image {

		/**
		 * Plugin constructor.
		 */
		public function __construct(){

			// Globalize:
			global $wp_version;

			// Set default options
			add_option('drag-drop-post-types', array('post', 'product'));
			add_option('drag-drop-file-types', array('jpg', 'jpeg', 'png', 'gif'));
			add_option('drag-drop-page-reload', 0);

			// Bind plugin actions:
			add_action('admin_menu', array(&$this, 'add_admin_menu_page'));
			add_action('admin_head', array(&$this, 'print_header_post_id'));
			add_action('add_meta_boxes', array(&$this, 'toggle_meta_box_functionality'));
			add_action('admin_enqueue_scripts', array(&$this, 'handle_plugin_script_loading'));
			add_action('wp_ajax_dgd_upload_featured_image', array(&$this, 'ajax_upload_image_file'));
			add_action('wp_ajax_dgd_set_featured_image', array(&$this, 'ajax_set_featured_image'));
			add_action('wp_ajax_dgd_get_featured_image', array(&$this, 'ajax_get_featured_image'));

			// Bind plugin filters:
			add_filter('admin_body_class', array(&$this, 'modify_admin_body_class'));

			// Set local variables:
			$this->plugin_locale = BW_TD;
			$this->plugin_options_slug = 'drag-drop-featured-image';
			$this->plugin_dirname = dirname(plugin_basename(__FILE__));
			$this->plugin_directory = plugins_url('',__FILE__).'/';
			$this->selected_post_types = get_option('drag-drop-post-types');
			$this->selected_file_types = get_option('drag-drop-file-types');
			$this->selected_page_reload = get_option('drag-drop-page-reload');

			// Set WordPress version:
			$this->wordpress_version = substr(str_replace('.', '', $wp_version), 0, 2);

		}


		/**
		 * Get selected post types.
		 * @return array
		 */
		public function get_option_post_types(){
			return get_option('drag-drop-post-types');
		}

		/**
		 * Get selected file types.
		 * @return array
		 */
		public function get_option_file_types(){
			return get_option('drag-drop-file-types');
		}

		/**
		 * Get page reload status.
		 * @return boolean
		 */
		public function get_option_page_reload(){
			return get_option('drag-drop-page-reload');
		}


		/**
		 * Print current post ID in header.
		 */
		public function print_header_post_id(){
			global $post, $current_screen;
			if ($current_screen->base === 'post'){
				echo '
					<script type="text/javascript">
						var dgd_post_id = '.$post->ID.';
						var dgd_page_reload = '.$this->get_option_page_reload().';
					</script>
				';
			}
		}


		/**
		 * Toggle the metaboxes.
		 */
		public function toggle_meta_box_functionality(){

			// Fetch selected post types:
			$selected = get_option('drag-drop-post-types');

			// Add theme support:
			add_theme_support('post-thumbnails', $selected);

			// Remove default meta box:
			foreach ($selected as $post_type){
				add_post_type_support($post_type, 'thumbnail');
				remove_meta_box('postimagediv', $post_type, 'side');
				remove_meta_box('postimagediv', $post_type, 'normal');
				remove_meta_box('postimagediv', $post_type, 'advanced');
			}

			// Add the enhanced meta box:
			foreach ($selected as $post_type){
				add_meta_box('drag_to_upload', __('Featured Image'), array(&$this, 'updated_upload_meta_box'), $post_type, 'side', 'default');
			}

		}


		/**
		 * Handle script loading.
		 * @param $hook
		 */
		public function handle_plugin_script_loading($hook){

			// Globalize:
			global $post;

			// Get current post type:
			if (!is_null($post)){
				$current_post_type = get_post_type($post->ID);
			}

			// Post edit screen:
			if ($hook == 'post.php' || $hook == 'post-new.php'){
				if (in_array($current_post_type, $this->selected_post_types)){

					// Stylesheets:
					wp_register_style('dgd_uploaderStyle', $this->plugin_directory.'assets/style/drag-drop-uploader.css', false);
					wp_enqueue_style('dgd_uploaderStyle');

					// Scripts:
					wp_enqueue_script('plupload-all');
					wp_register_script('dgd_uploaderScript', $this->plugin_directory.'assets/scripts/drag-drop-uploader.js', 'jquery');
					wp_enqueue_script('dgd_uploaderScript');

					// Localize JavaScript:
					wp_localize_script('dgd_uploaderScript', 'dgd_strings', array(
						'panel' => array(
							'title' => __('Set featured image'),
							'button' => __('Set featured image')
						)
					));

				}
			}

			// Plugin options screen:
			if ($hook === 'settings_page_'.$this->plugin_options_slug){

				// Stylesheets:
				wp_register_style('itoggle_style', $this->plugin_directory.'assets/style/engage.itoggle.css', false);
				wp_register_style('dgd_panelStyle', $this->plugin_directory.'assets/style/drag-to-feature.css', false);
				wp_enqueue_style('itoggle_style');
				wp_enqueue_style('dgd_panelStyle');

				// Scripts:
				wp_register_script('itoggle_script', $this->plugin_directory.'assets/scripts/engage.itoggle.1.7.min.js', 'jquery');
				wp_register_script('dgd_panelScript', $this->plugin_directory.'assets/scripts/drag-to-feature.js', 'jquery');
				wp_enqueue_script('itoggle_script');
				wp_enqueue_script('dgd_panelScript');
			}

		}


		/**
		 * Add admin menu page.
		 */
		public function add_admin_menu_page(){
			$required_capability = 'can_bitwize';
			add_options_page(__('Drag & Drop Featured Image', $this->plugin_locale), __('Featured Image', $this->plugin_locale), $required_capability, $this->plugin_options_slug, array(&$this, 'render_options_page'));
		}


		/**
		 * Render options page.
		 */
		public function render_options_page(){
			require_once('assets/views/options.php');
		}


		/**
		 * Byte formatting function:
		 * @param $bytes
		 * @param int $precision
		 * @return float
		 */
		public function format_bytes($bytes, $precision = 2) {
			$units = array('B', 'KB', 'MB', 'GB', 'TB');
			$bytes = max($bytes, 0);
			$pow = floor(($bytes ? log($bytes) : 0) / log(1024));
			$pow = min($pow, count($units) - 1);
			$bytes /= pow(1024, $pow);
			return round($bytes, $precision);
		}


		/**
		 * Uploading functionality trigger.
		 * (Most of the code comes from media.php and handlers.js)
		 */
		function updated_upload_meta_box(){ ?>
			<?php global $post; ?>
			<div id="uploadContainer" style="margin-top: 10px;">

				<!-- Current image -->
				<div id="current-uploaded-image" class="<?php echo has_post_thumbnail() ? 'open' : 'closed'; ?>">
					<?php if (has_post_thumbnail()): ?>
						<?php the_post_thumbnail('full'); ?>
					<?php else: ?>
						<img class="attachment-full" src="" />
					<?php endif; ?>

					<?php $thumbnail_id = get_post_thumbnail_id($post->ID); ?>
					<?php $ajax_nonce = wp_create_nonce("set_post_thumbnail-$post->ID"); ?>
					<p class="hide-if-no-js">
						<a class="button-secondary" href="#" id="remove-post-thumbnail" onclick="WPRemoveThumbnail('<?php echo $ajax_nonce; ?>');return false;"><?php _e('Remove featured image'); ?></a>
					</p>
				</div>

				<!-- Uploader section -->
				<div id="uploaderSection">
					<div class="loading">
						<img src="<?php echo $this->plugin_directory; ?>/assets/images/loading.gif" alt="Loading..." />
					</div>
					<div id="plupload-upload-ui" class="hide-if-no-js">
						<div id="drag-drop-area" style="height:auto;">
							<div class="drag-drop-inside">
								<p class="drag-drop-info"><?php _e('Drop files here'); ?></p>
								<p><?php _ex('or', 'Uploader: Drop files here - or - Select Files'); ?></p>
								<p style="margin-bottom:25px;">
									<input id="plupload-browse-button" type="button" value="<?php esc_attr_e('Select Files'); ?>" class="button" />
									<?php _e('from', $this->plugin_locale); ?>
									<a href="#" id="dgd_library_button" title="Add Media">
										<span class="wp-media-buttons-icon"></span><?php _e('Library', $this->plugin_locale); ?>
									</a>
								</p>
							</div>
						</div>
					</div>
				</div>

			</div>

			<?php
				global $post;
				$plupload_init = array(
					'runtimes'            => 'html5,silverlight,flash,html4',
					'browse_button'       => 'plupload-browse-button',
					'container'           => 'plupload-upload-ui',
					'drop_element'        => 'drag-drop-area',
					'file_data_name'      => 'async-upload',
					'multiple_queues'     => false,
					'multi_selection'	  => false,
					'max_file_size'       => wp_max_upload_size().'b',
					'url'                 => admin_url('admin-ajax.php'),
					'flash_swf_url'       => includes_url('js/plupload/plupload.flash.swf'),
					'silverlight_xap_url' => includes_url('js/plupload/plupload.silverlight.xap'),
					'filters'             => array(
						array(
							'title' => __('Allowed Files', $this->plugin_locale),
							'extensions' => implode(',', $this->get_option_file_types())
						)
					),
					'multipart'           => true,
					'urlstream_upload'    => true,

					// Additional parameters:
					'multipart_params'    => array(
						'_ajax_nonce' => wp_create_nonce('photo-upload'),
						'action'      => 'dgd_upload_featured_image',
						'postID'	  => $post->ID
					),
				);

				// Apply filters to initiate plupload:
				$plupload_init = apply_filters('plupload_init', $plupload_init); ?>

				<script type="text/javascript">
					jQuery(document).ready(function($){

						// Create uploader and pass configuration:
						var uploader = new plupload.Uploader(<?php echo json_encode($plupload_init); ?>);

						// Check for drag'n'drop functionality:
						uploader.bind('Init', function(up){
							var uploaddiv = $('#plupload-upload-ui');

							// Add classes and bind actions:
							if(up.features.dragdrop){
								uploaddiv.addClass('drag-drop');
								$('#drag-drop-area')
									.bind('dragover.wp-uploader', function(){ uploaddiv.addClass('drag-over'); })
									.bind('dragleave.wp-uploader, drop.wp-uploader', function(){ uploaddiv.removeClass('drag-over'); });

							} else{
								uploaddiv.removeClass('drag-drop');
								$('#drag-drop-area').unbind('.wp-uploader');
							}
						});

						// Initiate uploading script:
						uploader.init();

						// File queue handler:
						uploader.bind('FilesAdded', function(up, files){
							var hundredmb = 100 * 1024 * 1024, max = parseInt(up.settings.max_file_size, 10);

							// Limit to one limit:
							if (files.length > 1){
								alert("<?php _e('You may only upload one image at a time!', $this->plugin_locale); ?>");
								return false;
							}

							// Remove extra files:
							if (up.files.length > 1){
								up.removeFile(uploader.files[0]);
								up.refresh();
							}

							// Loop through files:
							plupload.each(files, function(file){

								// Handle maximum size limit:
								if (max > hundredmb && file.size > hundredmb && up.runtime != 'html5'){
									alert("<?php _e('The file you selected exceeds the maximum filesize limit.', $this->plugin_locale); ?>");
									return false;
								}

							});

							// Refresh and start:
							up.refresh();
							up.start();

							// Set sizes and hide container:
							var currentHeight = $('#uploaderSection').outerHeight();
							$('#uploaderSection').css({ height: currentHeight });
							$('div#plupload-upload-ui').fadeOut('medium');
							$('#uploaderSection .loading').fadeIn('medium');

						});

						// A new file was uploaded:
						uploader.bind('FileUploaded', function(up, file, response){

							// Publish post:
							if (dgd_page_reload){
								$('div#publishing-action input#publish').trigger('click');
							}

							// Toggle image:
							$('#current-uploaded-image').slideUp('medium', function(){

								// Parse response AS JSON:
								response = $.parseJSON(response.response);

								// Find current image and continue:
								if ($('#drag_to_upload div.inside').find('.attachment-full').length > 0){

									// Update image with new info:
									var imageObject = $('#drag_to_upload div.inside img.attachment-full');
									imageObject.attr('src', response.image);
									imageObject.removeAttr('width');
									imageObject.removeAttr('height');
									imageObject.removeAttr('title');
									imageObject.removeAttr('alt');

									// Hide container:
									imageObject.load(function(){

										// Display container:
										$('#current-uploaded-image').slideDown('medium');

										// Fade in upload container:
										$('div#plupload-upload-ui').fadeIn('medium');
										$('#uploaderSection .loading').fadeOut('medium');

										// Remove previous uploads:
										if (uploader.files.length >= 1){
											uploader.splice(0, (uploader.files.length - 1));
										}

									});

								}

							});

						});

					});
				</script>
			<?php
		}


		/**
		 * File upload handler.
		 */
		public function ajax_upload_image_file(){

			// Check referer, die if no ajax:
			check_ajax_referer('photo-upload');

			/// Upload file using Wordpress functions:
			$file = $_FILES['async-upload'];
			$status = wp_handle_upload($file, array(
				'test_form' => true,
				'action' => 'dgd_upload_featured_image'
			));

			// Fetch post ID:
			$post_id = $_POST['postID'];

			// Insert uploaded file as attachment:
			$attach_id = wp_insert_attachment(array(
				'post_mime_type' => $status['type'],
				'post_title' => preg_replace('/\.[^.]+$/', '', basename($file['name'])),
				'post_content' => '',
				'post_status' => 'inherit'
			), $status['file'], $post_id);

			// Include the image handler library:
			require_once(ABSPATH . 'wp-admin/includes/image.php');

			// Generate meta data and update attachment:
			$attach_data = wp_generate_attachment_metadata($attach_id, $status['file']);
			wp_update_attachment_metadata($attach_id, $attach_data);

			// Check for current meta (update / add):
			if ($prevValue = get_post_meta($post_id, '_thumbnail_id', true)){
				update_post_meta($post_id, '_thumbnail_id', $attach_id, $prevValue);
			} else {
				add_post_meta($post_id, '_thumbnail_id', $attach_id);
			}

			// Get image sizes and correct thumb:
			$croppedImage = wp_get_attachment_image_src($attach_id, 'full');
			$imageDetails = getimagesize($croppedImage[0]);

			// Create response array:
			$uploadResponse = array(
				'image' => $croppedImage[0],
				'width' => $imageDetails[0],
				'height' => $imageDetails[1],
				'postID' => $post_id
			);

			// Return response and exit:
			die(json_encode($uploadResponse));

		}


		/**
		 * AJAX function: Set featured image.
		 */
		public function ajax_set_featured_image(){
			$postID = isset($_POST['postID']) ? (int) $_POST['postID'] : false;
			$attachmentID = isset($_POST['attachmentID']) ? (int) $_POST['attachmentID'] : false;
			if ($postID && $attachmentID){

				// Update / add post meta:
				if ($status = get_post_meta($postID, '_thumbnail_id', true)){
					update_post_meta($postID, '_thumbnail_id', $attachmentID);
				} else {
					add_post_meta($postID, '_thumbnail_id', $attachmentID);
				}

				$response = array(
					'response_code' => 200,
					'response_content' => 'success'
				);

			} else {
				$response = array(
					'response_code' => 500,
					'response_content' => __('Drag & Drop Feaured image: Unknown post och attachment ID, please try again!', $this->plugin_locale)
				);
			}

			// Return response:
			die(json_encode($response));

		}


		/**
		 * AJAX function: Get featured image.
		 */
		public function ajax_get_featured_image(){

			$postID = isset($_POST['post_id']) ? (int) $_POST['post_id'] : false;
			if ($postID){
				$featured = wp_get_attachment_image_src(get_post_thumbnail_id($postID), 'full');
				$response = array(
					'response_code' => 200,
					'response_content' => $featured[0]
				);
			} else {
				$response = array(
					'response_code' => 500,
					'response_content' => __('Drag & Drop Feaured image: An error occured, please reload page!', $this->plugin_locale)
				);
			}

			// Return response:
			die(json_encode($response));

		}


		/**
		 * Add body classes to the admin.
		 * @param $classes
		 * @return string
		 */
		public function modify_admin_body_class($classes){
			if ($this->wordpress_version >= 38){ $classes .= 'wp38'; }
			return $classes;
		}


	}
