<?php
class BW_JumpMenu
{
    var $dir, $path, $version, $options, $current_user, $options_page;

    function __construct() {

        $this->path = plugin_dir_path( __FILE__ );
        $this->dir = plugins_url( '', __FILE__ );
        $this->version = BWD_VERSION;

        add_action( 'init', array($this, 'init') );

        return true;
    }

    function init() {

        global $wp_version;

        if( is_network_admin() ) return false;
        if( !is_admin() ) return false;
        if( !current_user_can( 'edit_posts' ) ) return false;

        $this->current_user = wp_get_current_user();

        add_action( 'admin_print_scripts', array($this, 'js') );
        add_action( 'save_post', array($this, 'save_post'), 11, 2 );
        add_action( 'trashed_post', array($this, 'save_post'), 11, 2 );
        // add_action( 'admin_bar_menu', array($this, 'admin_bar_menu'), 25 );
        add_action( 'bw_admin_bar_after_user', array($this, 'admin_bar_menu'), 25 );
    }

    function js() {
        wp_enqueue_script( 'bwjmchosenjs', $this->dir . '/assets/bwjmchosen/custom.bwjmchosen.jquery.js', array('jquery'), BWD_VERSION, true );
        wp_enqueue_style( 'bwjmchosencss-wpadminbar', $this->dir . '/assets/bwjmchosen/bwjmchosen-wpadmin.css', array(), BWD_VERSION, 'all' );
    }

    public function save_post($post_id, $post){
        // $slugs = array('product', 'page', 'post');
        $slugs = get_post_types(array('public' => true));
        if ( wp_is_post_revision( $post->ID ) ) return;
        if(!in_array($post->post_type, $slugs)) return;
        delete_transient('bw_jumpmenu_transient');
    }

    function admin_bar_menu() {
        global $wp_admin_bar;

        if( is_admin_bar_showing() ) {
            $postid = isset( $_GET['post'] ) ? intval($_GET['post']) : 0;
            $html = $this->dropdown();
            $html.= "
            <script>
			jQuery(document).ready(function($){
                var id = ".$postid.";
                jQuery('#bw-jump-menu option').filter('[data-post-id=\"'+id+'\"]').attr('selected', 'selected');
                jQuery('#bw-jump-menu').off('change').on('change',function() {
					window.location = this.value;
				}).customBWJMChosen({position:'wpAdminBar', search_contains: true});
            });
            </script>
            ";

            $wp_admin_bar->add_menu( array('id' => 'bw-jump-menu', 'parent' => 'top-secondary', 'title' => '', 'meta' => array('html' => $html)) );
        }
    }

    private function dropdown($force=false){
        if($force) return $this->get_dropdown();

        if (false === ($transient = get_transient('bw_jumpmenu_transient'))) {
            $transient = $this->get_dropdown();
            set_transient('bw_jumpmenu_transient', $transient, 24 * HOUR_IN_SECONDS);
        }
        return $transient;
    }

    function get_dropdown() {

        require_once( 'assets/WalkerClass.php' );

        global $current_user, $post;

        $custom_post_types = array();

        $types = get_post_types(array('public' => true));
        if( bwd_get_option('disable_blog') ) unset($types['post']);
        foreach($types as $type){

            $custom_post_types[$type] = array(
                'show' => 1,
                'numberposts' => -1,
                'sortby' => 'post_title',
                'sort' => 'ASC',
                'poststatus' => array('publish', 'draft', 'private', 'future'),
            );
        }

        $status_color = array(
            'publish'       => '#000000',
            'pending'       => '#FF3636',
            'draft'         => '#33FF85',
            'auto-draft'    => '#AFFF38',
            'future'        => '#FFAC4D',
            'private'       => '#3D3D3D',
            'inherit'       => '#595959',
            'trash'         => '#FF0000',
        );

        $custom_post_types = apply_filters('jump_menu_cpts', $custom_post_types);

        $string = '';

        $string.= '<select id="bw-jump-menu" data-placeholder=" <span class=\'fa fa-search\'></span> " class="bwjmchosen-select" style="display:none;">';
        $string.= '<option></option>';

        $string = apply_filters( 'jump_menu_begin', $string );

        $disabled = apply_filters('jump_menu_disabled', array());

        if( $custom_post_types ) {

                foreach( $custom_post_types as $key => $value ) {

                    $cpt = $key;
                    $post_type_object = get_post_type_object( $cpt );
                    $sortby = $value['sortby'];
                    $sort = $value['sort'];
                    $numberposts = $value['numberposts'];
                    $showdrafts =( isset( $value['showdrafts'] ) ? $value['showdrafts'] : '' );
                    $post_status = $value['poststatus'];

                    $args = array(
                        'orderby' => $sortby,
                        'order' => $sort,
                        'posts_per_page' => $numberposts,
                        'post_type' => $cpt,
                        // 'post_mime_type' => $postmimetype,
                        'post_status' =>( is_array( $post_status ) ?( in_array( 'any', $post_status ) ? 'any' : $post_status ) : $post_status )
                    );
                    $pd_posts = get_posts( $args );

                    $pd_total_posts = count( $pd_posts );

                    $cpt_obj = get_post_type_object( $cpt );
                    $cpt_labels = $cpt_obj->labels;

                    $pd_i = 0;

                    if( !is_post_type_hierarchical( $cpt ) ) {

                        $string.= '<optgroup label="' . $cpt_labels->name . '">';

                        if( $cpt_labels->name != 'Media' ) {

                            if( current_user_can( $post_type_object->cap->edit_posts ) ) {
                                $string.= '<option value="post-new.php?post_type=';
                                $string.= $cpt_obj->name;
                                $string.= '">+ Add New ' . $cpt_labels->singular_name . ' +</option>';
                            }
                        }

                        foreach( $pd_posts as $pd_post ) {

                            if(in_array($pd_post->ID, $disabled)) continue;

                            $pd_i++;

                            $string.= '<option data-permalink="' . get_permalink( $pd_post->ID ) . '" value="';
                            $editLink =( is_admin() ) ? get_edit_post_link( $pd_post->ID ) : get_permalink( $pd_post->ID );
                            $string.= $editLink;
                            $string.= '"';

                            // if(( isset( $_GET['post'] ) &&( $pd_post->ID == $_GET['post'] ) ) ||( isset( $post ) &&( $pd_post->ID == $post->ID ) ) )$string.= ' selected="selected"';

                            if( !current_user_can( $post_type_object->cap->edit_post, $pd_post->ID ) )$string.= ' disabled="disabled"';

                            if( isset( $status_color[$pd_post->post_status] ) ) {
                                $string.= ' style="color: ' . $status_color[$pd_post->post_status] . ';"';
                            }

                            $string.= ' data-post-id="' . $pd_post->ID . '"';


                            $string.= '>';

                            $string.= $this->get_page_title( $pd_post->post_title );

                            if( $pd_post->post_status != 'publish' && $pd_post->post_status != 'inherit' )$string.= ' - ' . $pd_post->post_status;

                            if( $pd_post->post_type == 'attachment' )$string.= ' (' . $pd_post->post_mime_type . ')';

                            if( $pd_post->post_status == 'future' )$string.= ' - ' . $pd_post->post_date;

                            $string.= '</option>';
                        }
                        $string.= '</optgroup>';
                    }
                    else {

                        $orderedListWalker = new Jump_Menu_Walker_PageDropDown();

                        $string.= '<optgroup label="' . $cpt_labels->name . '">';

                        if( ( current_user_can( $post_type_object->cap->edit_posts ) || current_user_can( $post_type_object->cap->edit_pages ) ) ) {
                            $string.= '<option value="post-new.php?post_type=';
                            $string.= $cpt_obj->name;
                            $string.= '">+ Add New ' . $cpt_labels->singular_name . ' +</option>';
                        }

                        foreach( $post_status as $status ) {

                            if( $status == 'publish' )continue;

                            $pd_posts_drafts = get_posts( 'orderby=' . $sortby . '&order=' . $sort . '&posts_per_page=' . $numberposts . '&post_type=' . $cpt . '&post_status=' . $status );

                            foreach( $pd_posts_drafts as $pd_post ) {

                                if(in_array($pd_post->ID, $disabled)) continue;

                                $pd_i++;

                                $string.= '<option data-permalink="' . get_permalink( $pd_post->ID ) . '" value="';
                                $editLink =( is_admin() ) ? get_edit_post_link( $pd_post->ID ) : get_permalink( $pd_post->ID );
                                $string.= $editLink;
                                $string.= '"';

                                // if(( isset( $_GET['post'] ) &&( $pd_post->ID == $_GET['post'] ) ) ||( isset( $post ) &&( $pd_post->ID == $post->ID ) ) )$string.= ' selected="selected"';

                                if( !current_user_can( $post_type_object->cap->edit_post, $pd_post->ID ) )$string.= ' disabled="disabled"';

                                if( isset( $status_color[$pd_post->post_status] ) ) {
                                    $string.= ' style="color: ' . $status_color[$pd_post->post_status] . ';"';
                                }

                                $string.= ' data-post-id="' . $pd_post->ID . '"';


                                $string.= '>';

                                $string.= $this->get_page_title( $pd_post->post_title );

                                if( $pd_post->post_status != 'publish' )$string.= ' - ' . $status;

                                if( $pd_post->post_status == 'future' )$string.= ' - ' . $pd_post->post_date;

                                $string.= '</option>';
                            }
                        }
                        if( is_array( $post_status ) ) {

                            if( in_array( 'publish', $post_status ) ) {

                                $string.= wp_list_pages( array('walker' => $orderedListWalker, 'post_type' => $cpt, 'echo' => 0, 'depth' => $numberposts, 'sort_column' => $sortby, 'sort_order' => $sort, 'exclude' => implode(',', $disabled) ) );
                            }
                        }
                        else if( $post_status == 'publish' ) {
                            $string.= wp_list_pages( array('walker' => $orderedListWalker, 'post_type' => $cpt, 'echo' => 0, 'depth' => $numberposts, 'sort_column' => $sortby, 'sort_order' => $sort, 'exclude' => implode(',', $disabled)) );
                        }

                        $string.= '</optgroup>';
                    }
                }

        }
        $string = apply_filters( 'jump_menu_end', $string );

        if( is_woocommerce_active() ) {

            $string.= '<optgroup label="// Store Settings //">';
            $string.= '<option value="' . admin_url('admin.php?page=wc-settings') . '">Store Settings</option>';
            $string.= '</optgroup>';
        }

        $string.= '</select>';

        return $string;
    }

    function get_page_title( $pd_title ) {
        if( strlen( $pd_title ) > 50 ) {
            return substr( $pd_title, 0, 50 ) . "...";
        }
        else {
            return $pd_title;
        }
    }

}

if( !is_network_admin() ) {
    if( function_exists( 'current_user_can' ) ) {
        new BW_JumpMenu();
    }
}
?>
