<div class="wrap">
	<div id="icon-themes" class="icon32"></div>
	<h2><?php echo get_admin_page_title(); ?></h2>

	<?php

		// Check for POST request:
		if (isset($_POST['updatePluginSettings'])){

			// Check that everything is set:
			if ($_POST['file_types'] && !empty($_POST['post_types'])){

				// Update post types:
				$selected = $_POST['post_types'];
				update_option('drag-drop-post-types', $selected);

				// Update formats:
				$selected = $_POST['file_types'];
				update_option('drag-drop-file-types', $selected);

				// Update page reload:
				if (isset($_POST['page_reload']) && $_POST['page_reload'] == 'checked'){
					update_option('drag-drop-page-reload', 1);
				} else {
					update_option('drag-drop-page-reload', 0);
				}

				// Show message:
				echo '<div id="message" style="margin-top: 10px;" class="updated"><p><strong>'.__('Success:', $this->plugin_locale).'</strong> '.__('The plugin options have been successfully updated!', $this->plugin_locale).'</p></div>';

			} else {

				// Show message:
				echo '<div id="message" style="margin-top: 10px;" class="error"><p>'.__('Please make sure you filled in all the required fields before submitting. At least <em><strong>one post type</strong></em> and <em><strong>one extension</strong></em> must be selected!', $this->plugin_locale).'</p></div>';

			}
		}

	?>

	<div id="drag-to-feature-image" class="metabox-holder">

		<div id="post-body">
			<div id="post-body-content">

				<!-- Meta box -->
				<div id="manage-plugin-options" class="postbox">
					<h3 class="hndle"><span><?php _e('Available options:', $this->plugin_locale); ?></span></h3>
					<div id="itoggle" class="inside" style="padding: 20px 30px;">
						<form action="" method="post">

							<div class="metaBoxRow">
								<strong><?php _e('Which post types do you want the meta box to display at?', $this->plugin_locale); ?></strong><br />
								<div class="containerDiv" style="margin: 2px 0px 0px 0px; overflow: hidden;">
									<?php $post_types = get_post_types(array( 'show_ui' => true )); ?>
									<?php $selected = $this->get_option_post_types(); ?>
									<div class="objectRow">
										<?php foreach ($post_types as $type): ?>
											<?php if ($type !== 'attachment'): ?>
												<?php $checked = (in_array($type, $selected)) ? 'checked="checked"' : ''; ?>
												<div class="toggleObject itoggle">
													<input class="iOSToggle" name="post_types[]" <?php echo $checked; ?> type="checkbox" id="type_<?php echo $type; ?>" value="<?php echo $type; ?>" />
													<p><?php echo ucfirst($type); ?></p>
												</div>
											<?php endif; ?>
										<?php endforeach; ?>
									</div>
								</div>
							</div>

							<div class="metaBoxRow">
								<strong><?php _e('Which of the following file types should be supported?', $this->plugin_locale); ?></strong><br />
								<div class="containerDiv" style="margin: 2px 0px 0px 0px; overflow: hidden;">
									<?php $selected = $this->get_option_file_types(); ?>
									<?php $file_types = array('jpg', 'jpeg', 'png', 'gif'); ?>
									<div class="objectRow">
										<?php foreach ($file_types as $ft): ?>
											<?php $checked = (in_array($ft, $selected)) ? 'checked="checked"' : ''; ?>
											<div class="toggleObject">
												<input class="iOSToggle" name="file_types[]" <?php echo $checked; ?> type="checkbox" id="filetype_<?php echo $ft; ?>" value="<?php echo $ft; ?>" />
												<p><?php echo strtoupper($ft); ?></p>
											</div>
										<?php endforeach; ?>
									</div>
								</div>
							</div>

							<div class="metaBoxRow">
								<strong><?php _e('Publish / update post after successful image upload?', $this->plugin_locale); ?></strong><br />
								<small><em><?php _e('If you like some users upload the featured image as the last step when publishing a post, this option is for you.', $this->plugin_locale); ?></em></small><br />
								<div class="containerDiv" style="margin: 2px 0px 0px 0px; overflow: hidden;">
									<div class="objectRow">
										<?php $checked = $this->get_option_page_reload() ? 'checked="checked"' : ''; ?>
										<div class="toggleObject itoggle">
											<input class="iOSToggle" name="page_reload" <?php echo $checked; ?> type="checkbox" id="reload_page" value="checked" />
										</div>
									</div>
								</div>
							</div>

							<div class="metaBoxRow last">
								<input type="submit" name="updatePluginSettings" class="button-secondary" value="<?php _e('Update settings', $this->plugin_locale); ?>" />
							</div>

						</form>
					</div>
				</div>

			</div>
		</div>

	</div>

	<div id="contact-info" class="metabox-holder">

	</div>

</div>
