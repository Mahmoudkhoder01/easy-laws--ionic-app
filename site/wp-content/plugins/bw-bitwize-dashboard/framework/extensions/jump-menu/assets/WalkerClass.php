<?php
// Custom Walker Class to walk through the page/custom post type hierarchy tree
class Jump_Menu_Walker_PageDropDown extends Walker_PageDropDown {

	var $tree_type = "page";

	function start_el(&$output, $page, $depth = 0, $args = array(), $id = 0) {

		global $current_user, $post;

		$status_color = array(
            'publish' => '#000000',
            'pending' => '#FF3636',
            'draft' => '#33FF85',
            'auto-draft' => '#AFFF38',
            'future' => '#FFAC4D',
            'private' => '#3D3D3D',
            'inherit' => '#595959',
            'trash' => '#FF0000',
		);

		$pad = str_repeat(' &#8212;', $depth * 1);

		$editLink = (is_admin()) ? get_edit_post_link($page->ID) : get_permalink($page->ID);
		$output .= "\t<option data-permalink=\"".get_permalink($page->ID)."\" class=\"level-$depth\" value=\"".$editLink."\"";
		if ( (isset($_GET['post']) && ($page->ID == $_GET['post'])) || (isset($post) && ($page->ID == $post->ID)) )
			$output .= ' selected="selected"';

		$post_type_object = get_post_type_object( $args['post_type'] );

		if (!current_user_can($post_type_object->cap->edit_post,$page->ID))
			$output .= ' disabled="disabled"';

			$output .= ' style="color: '.$status_color['publish'].';"';
			// If the setting to show ID's is true, show the ID in ()
			$output .= ' data-post-id="'.$page->ID.'"';

		$output .= '>';
		$title = apply_filters( 'list_pages', $page->post_title );
		$output .= esc_html( $title ) . $pad;

		$output .= "</option>\n";
	}
}
// end Jump_Menu_Walker_PageDropDown class
?>
