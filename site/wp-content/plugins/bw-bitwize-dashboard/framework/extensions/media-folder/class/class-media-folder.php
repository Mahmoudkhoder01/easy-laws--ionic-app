<?php

class Wp_Media_Folder{

    function __construct() {
        add_action('init', array($this, 'wpmf_session_start'), 1);
        add_action( 'admin_enqueue_scripts', array($this, 'wpmf_load_custom_wp_admin_script') );
        add_action( 'admin_init', array($this, 'addAdminStylesheets') );

        // if(!get_option('_wpmf_import_notice_flag', false)){
        //         add_action( 'admin_notices', array($this, 'wpmf_whow_notice'), 3 );
        // }

        if(!get_option('wpmf_use_taxonomy', false)){
                add_option('wpmf_use_taxonomy', 1, '', 'yes' );
        }
        add_action( 'wp_ajax_wpmf_import', array($this,'wpmf_import_categories') );
        add_action( 'init', array($this, 'wpmf_register_taxonomy_for_images') );
        add_action( 'restrict_manage_posts', array($this, 'wpmf_add_image_category_filter') );
        add_action('pre_get_posts', array($this, 'wpmf_pre_get_posts1'));
        add_action( 'admin_head', array($this, 'wpmf_admin_head') );
        add_action( 'pre_get_posts', array($this, 'wpmf_pre_get_posts') , 0, 1 );
        add_action('wp_ajax_change_folder', array($this, 'wpmf_change_folder'));
        add_filter( 'wp_generate_attachment_metadata', array($this, 'wpmf_after_upload'), 10, 2 );
        add_action('wp_ajax_add_folder', array($this, 'wpmf_add_folder') );
        add_action('wp_ajax_edit_folder', array($this, 'wpmf_edit_folder') );
        add_action('wp_ajax_delete_folder', array($this, 'wpmf_delete_folder') );
        add_action('wp_ajax_move_file', array($this, 'wpmf_move_file') );
        add_action('wp_ajax_move_folder', array($this, 'wpmf_move_folder') );
        add_action('wp_ajax_get_terms', array($this, 'wpmf_get_terms') );
        add_action('admin_footer', array($this,'add_editor_footer'));
    }

    public function wpmf_session_start() {
        if ( ! session_id() ) {
           @session_start();
        }
     }

    public function wpmf_load_custom_wp_admin_script() {
            global $current_user;
            $roles = array('administrator','editor','author');
            $user_roles = $current_user->roles;
            if(in_array($user_roles[0], $roles)){
                wp_register_script('wpmf-script', plugins_url( '/assets/js/script.js', dirname(__FILE__) ),array('plupload'));
                wp_enqueue_script('wpmf-script');
            }
            global $pagenow;
            if($pagenow == 'customize.php'){
                $this->wpmf_admin_head();
            }
    }

    public function addAdminStylesheets() {
        wp_enqueue_style('wpmf-style',plugins_url( '/assets/css/style.css', dirname(__FILE__) ));
        wp_enqueue_style('wpmf-jaofiletree',plugins_url( '/assets/css/jaofiletree.css', dirname(__FILE__) ));
        wp_enqueue_style('wpmf-material-design-iconic-font.min',plugins_url( '/assets/css/material-design-iconic-font.min.css', dirname(__FILE__) ));
    }


    public function wpmf_whow_notice(){
	echo '<script type="text/javascript">'.PHP_EOL
		. 'function importWpmfTaxonomy(doit,button){'.PHP_EOL
		    .'jQuery(button).find(".spinner").show().css({"visibility":"visible"});'.PHP_EOL
		    .'jQuery.post(ajaxurl, {action: "wpmf_import",doit:doit}, function(response) {'.PHP_EOL
			.'jQuery(button).closest("div#wpmf_error").hide();'.PHP_EOL
			.'if(doit===true){'.PHP_EOL
			    .'jQuery("#wpmf_error").after("<div class=\'updated\'> <p><strong>'. __('Categories imported into WP Media Folder. Enjoy!!!',BW_TD) .'</strong></p></div>");'.PHP_EOL
			.'}'.PHP_EOL
		    .'});'.PHP_EOL
		. '}'.PHP_EOL
	    . '</script>';
	echo '<div class="error" id="wpmf_error">'
		. '<p>'
		. __('You\'ve just installed WP Media Folder, to save your time we can import your media categories into WP Media Folder',BW_TD)
		    . '<a href="#" class="button button-primary" style="margin: 0 5px;" onclick="importWpmfTaxonomy(true,this);" id="wmpfImportBtn">'.__('Import categories now',BW_TD).' <span class="spinner" style="display:none"></span></a> or <a href="#" onclick="importWpmfTaxonomy(false,this);" style="margin: 0 5px;" class="button">'.__('No thanks ',BW_TD).' <span class="spinner" style="display:none"></span></a>'
		. '</p>'
	    . '</div>';
    }

    function wpmf_import_categories(){
        $option_import_taxo = get_option('_wpmf_import_notice_flag');
        if(isset($option_import_taxo) && $option_import_taxo == 'yes'){
            die();
        }
        if($_POST['doit']==='true'){
            $terms = get_terms( 'category', array(
                            'orderby'       => 'name',
                            'order'         => 'ASC',
                            'hide_empty'    => false,
                            'child_of'	=> 0
                    ) );

            $termsRel = array('0'=>0);
            foreach ($terms as $term) {
                $inserted = wp_insert_term($term->name, 'wpmf-category',array('slug'=>wp_unique_term_slug($term->slug,$term)));
                if ( is_wp_error($inserted) ) {
                    wp_send_json($inserted->get_error_message());
                }
                $termsRel[$term->term_id] = $inserted['term_id'];
            }
            foreach ($terms as $term) {
                wp_update_term($termsRel[$term->term_id], 'wpmf-category',array('parent'=>$termsRel[$term->parent]));
            }

            //update attachments
            $attachments = get_posts(array('posts_per_page'=>-1,'post_type'=>'attachment'));
            foreach ($attachments as $attachment) {
                $terms = wp_get_post_terms($attachment->ID,'category');
                $termsArray = array();
                foreach ($terms as $term) {
                    $termsArray[] = $termsRel[$term->term_id];
                }
                if($termsArray != null){
                    wp_set_post_terms( $attachment->ID, $termsArray, 'wpmf-category');
                }
            }
        }
        if($_POST['doit']==='true'){
            update_option('_wpmf_import_notice_flag', 'yes');
        }else{
            update_option('_wpmf_import_notice_flag', 'no');
        }
        die();
    }

    public  function wpmf_add_image_category_filter() {
        global $pagenow;
        $taxo = $this->get_taxonomy();
        if ( $pagenow == 'upload.php' ) {
            $wpmf_active_media = get_option('wpmf_active_media');
            $user_data = get_userdata( get_current_user_id() );
            $user_roles = $user_data->roles;

            $wpmfterm = $this->wpmf_term_root();
            $term_rootId = $wpmfterm['term_rootId'];
            $term_label = $wpmfterm['term_label'];
            if($user_roles[0] != 'administrator' && $wpmf_active_media == 1 && $term_rootId){
                $dropdown_options = array( 'show_option_none'=> $term_label , 'option_none_value' => $term_rootId, 'hide_empty' => false, 'hierarchical' => true, 'orderby' => 'name', 'taxonomy'=>$taxo, 'class'=>'wpmf-categories', 'name' => 'wcat', 'selected' => (int)(isset($_GET['wcat'])?$_GET['wcat']:0) );
            }else{
                $dropdown_options = array( 'show_option_none'=> __( 'No Categories', BW_TD ) , 'option_none_value' => 0, 'hide_empty' => false, 'hierarchical' => true, 'orderby' => 'name', 'taxonomy'=>$taxo, 'class'=>'wpmf-categories', 'name' => 'wcat', 'selected' => (int)(isset($_GET['wcat'])?$_GET['wcat']:0) );
            }

            wp_dropdown_categories( $dropdown_options );
        }
    }

    public function wpmf_pre_get_posts1($query){
        global $pagenow;
        $taxo = $this->get_taxonomy();
        if ( $pagenow == 'upload.php' ) {
            if(isset($_GET['wcat']) && (int)$_GET['wcat']!==0){
                $query->tax_query->queries[] = array(
                            'taxonomy' => $taxo,
                            'field'    => 'term_id',
                            'terms'    => (int)$_GET['wcat'],
                            'include_children' => false
                    );
                $query->query_vars['tax_query'] = $query->tax_query->queries;
            }else{
                $wpmf_active_media = get_option('wpmf_active_media');
                $user_data = get_userdata( get_current_user_id() );
                $user_roles = $user_data->roles;

                if($wpmf_active_media == 1 && $user_roles[0] !='administrator'){
                    $wpmfterm = $this->wpmf_term_root();
                    $term_rootId = $wpmfterm['term_rootId'];
                    $query->tax_query->queries[] = array(
                            'taxonomy' => $taxo,
                            'field'    => 'term_id',
                            'terms'    => (int)$term_rootId,
                            'include_children' => false
                    );
                    $query->query_vars['tax_query'] = $query->tax_query->queries;
                }else{
                    $terms = get_categories(array('hide_empty'=>false,'taxonomy'=>$taxo));
                    $cats = array();
                    foreach ($terms as $term) {
                        if(!empty($term->term_id)){
                            $cats[] = $term->term_id;
                        }
                    }
                    $query->tax_query->queries[] = array(
                            'taxonomy' => $taxo,
                            'field'    => 'term_id',
                            'terms'    => $cats,
                            'operator' => 'NOT IN',
                            'include_children' => false
                        );
                    $query->query_vars['tax_query'] = $query->tax_query->queries;
                }
            }
        }
    }

    function wpmf_admin_head(){
        global $pagenow,$current_user;
        $taxo = $this->get_taxonomy();
        $attachment_terms = array();
	$terms = get_categories(array('hide_empty'=>false,'taxonomy'=>$taxo));
	$terms = $this->generatePageTree($terms);
	$terms = $this->parent_sort($terms);


        $attachment_terms_order= array();
        $wpmf_active_media = get_option('wpmf_active_media');
        $user_roles = $current_user->roles;
        if($user_roles[0] == 'administrator' || $wpmf_active_media == 0){
            $attachment_terms[] = array( 'id' => 0, 'label' => __('No') . ' Categories' , 'slug' => '' , 'parent_id' => 0);
            $attachment_terms_order[] = '0';
        }else{
            $wpmfterm = $this->wpmf_term_root();
            $term_rootId = $wpmfterm['term_rootId'];
            if(!$term_rootId){
                $attachment_terms[] = array( 'id' => 0, 'label' => __('No') . ' Categories' , 'slug' => '' , 'parent_id' => 0);
                $attachment_terms_order[] = '0';
            }
        }

	foreach ( $terms as $term ){
            if(isset($wpmf_active_media) && $wpmf_active_media == 1 && $user_roles[0] !='administrator'){
                if($term->term_group == get_current_user_id()){
                    $wpmfterm = $this->wpmf_term_root();
                    $term_rootId = $wpmfterm['term_rootId'];
                    if($term_rootId){
                        if($term->name == $current_user->user_login || $term->category_parent !=0){
                            $attachment_terms[$term->term_id] = array( 'id' => $term->term_id, 'label' => $term->name, 'slug' => $term->slug, 'parent_id' => $term->category_parent, 'depth'=>$term->depth ,'term_group' => $term->term_group);
                            $attachment_terms_order[] = $term->term_id;
                        }
                    }else{
                        $attachment_terms[$term->term_id] = array( 'id' => $term->term_id, 'label' => $term->name, 'slug' => $term->slug, 'parent_id' => $term->category_parent, 'depth'=>$term->depth ,'term_group' => $term->term_group);
                        $attachment_terms_order[] = $term->term_id;
                    }
                }
            }else{
                $attachment_terms[$term->term_id] = array( 'id' => $term->term_id, 'label' => $term->name, 'slug' => $term->slug, 'parent_id' => $term->category_parent, 'depth'=>$term->depth , 'term_group' => $term->term_group);
                $attachment_terms_order[] = $term->term_id;
            }
	}

        $wcat = isset($_GET['wcat'])?$_GET['wcat']:'0';
        $parents = array();
        $pCat = (int)$wcat;
        while($pCat != 0 ) {
            $parents[]  = $pCat;
            $pCat = (int)$attachment_terms[$pCat]['parent_id'];
        }
        $parents_array = json_encode(array_reverse($parents));
        $usegellery = get_option('wpmf_usegellery');
        $get_plugin_enhanced_media = strpos(json_encode(get_option( 'active_plugins' )),'enhanced-media-library.php' );
        $usegellery = get_option('wpmf_usegellery');
	?>
	<script type="text/javascript">
	    wpmf_categories = <?php echo json_encode( $attachment_terms ) ?>;
            wpmf_categories_order = <?php echo json_encode( $attachment_terms_order ) ?>;
	    wpmf_images_path = '<?php echo plugins_url( 'assets/images', dirname(__FILE__) ) ?>';
            taxo = '<?php echo $taxo; ?>';
            var parents_array = <?php echo $parents_array;?> ;
            var wpmf_pagenow = '<?php echo $pagenow ?>';
            var usegellery = '<?php echo $usegellery ?>';
            var enhanced_media_plugin = '<?php echo $get_plugin_enhanced_media ?>';
            var wpmf_role = '<?php echo $user_roles[0]; ?>';
            var wpmf_curent_userid = '<?php echo get_current_user_id(); ?>';
            var wpmf_active_media = '<?php echo $wpmf_active_media; ?>';
            var term_root_username = '<?php echo $current_user->user_login; ?>';
            var term_root_id = '<?php echo @$term_rootId ?>';
            var wpmflang = {
                'create_folder': "<?php _e('Create Folder', BW_TD) ?>",
                'media_folder': "<?php _e('Media Library', BW_TD) ?>",
                'promt': "<?php _e('Please give a name to this new folder', BW_TD) ?>",
                'new_folder': "<?php _e('New folder',BW_TD) ?>",
                'alert_add': "<?php _e('A term with the name and slug already exists with this parent.',BW_TD) ?>",
                'alert_delete': "<?php _e('Are you sure to want to delete this folder',BW_TD) ?>",
                'alert_delete1': "<?php _e('this folder contains sub-folder, delete sub-folders before',BW_TD) ?>",
                'display_media': "<?php _e('Display only my own media',BW_TD) ?>",
                'create_gallery_folder': "<?php _e('Create a gallery from folder',BW_TD) ?>",
                'home' : "<?php _e('Home',BW_TD); ?>",
                'youarehere' : "<?php _e('You are here',BW_TD); ?>",
                'back' : "<?php _e('Back',BW_TD); ?>",
                'dragdrop' : "<?php _e('Drag and Drop me hover a folder',BW_TD); ?>",
            };
	</script>
	<?php

	//include jquery ui
	wp_enqueue_script( array('jquery-ui-draggable','jquery-ui-droppable') );
    }

    public function add_editor_footer() {
        wp_editor( '', 'wpmf-editor', array('media_buttons' => false,'editor_class' => 'wpmf-editor','tinymce' => false) );
    }

    public function wpmf_register_taxonomy_for_images() {
        $taxo = $this->get_taxonomy();
        register_taxonomy($taxo, 'attachment',array('hierarchical'=>true,'show_in_nav_menus'=>false,'show_ui'=>false));
    }

    public function getRecursiveTerms($taxonomy,$term=0){
        $terms = get_terms( $taxonomy, array(
                            'orderby'       => 'name',
                            'order'         => 'ASC',
                            'hide_empty'    => true,
                            'child_of'	=> $term
                    ) );
        return $terms;
    }

    public function wpmf_pre_get_posts( $query ){
       $taxo = $this->get_taxonomy();
       if ( !isset( $query->query_vars['post_type'] ) || $query->query_vars['post_type'] != 'attachment')
	       return;

       $taxonomies = apply_filters( 'attachment-category', get_object_taxonomies('attachment', 'objects' ) );
       if ( !$taxonomies ) return;
       foreach ( $taxonomies as $taxonomyname => $taxonomy ) :
           if($taxonomyname == $taxo){
	       if ( isset( $_REQUEST['query']['wpmf_taxonomy']) && $_REQUEST['query']['term_slug'] ){
		   $query->set('tax_query', array(
		       array(
			   'taxonomy' => $taxonomyname,
			   'field' => 'slug',
			   'terms' => $_REQUEST['query']['term_slug'],
			   'include_children' => false
			   )
		       )
		   );
	       }elseif ( isset( $_REQUEST[$taxonomyname] ) && is_numeric( $_REQUEST[$taxonomyname] ) && intval( $_REQUEST[$taxonomyname] ) != 0 ){
		       $term = get_term_by( 'id', $_REQUEST[$taxonomyname], $taxonomyname );
		       if ( is_object( $term ) )
			       set_query_var( $taxonomyname, $term->slug );
	       }elseif(isset( $_REQUEST['query']['wpmf_taxonomy'] ) && $_REQUEST['query']['term_slug'] == ''){
		    $terms = get_terms($taxonomyname,array('hide_empty'=>false,'hierarchical'=>false));
		    $unsetTags = array();
		    foreach ($terms as $term){
			$unsetTags[] = $term->slug;
		    }
		    $query->set('tax_query', array(
			    array(
				'taxonomy' => $taxonomyname,
				'field' => 'slug',
				'terms' => $unsetTags,
				'operator' => 'NOT IN',
				'include_children' => false,
				)
			    )
			);
	       }
           }

       endforeach;

        global $current_user, $wpdb;
        $role = $wpdb->prefix . 'capabilities';
        $current_user->role = array_keys($current_user->$role);
        $role = $current_user->role[0];
        $roles = array('administrator','editor','author');

        $wpmf_active_media = get_option('wpmf_active_media');
        $option2 = get_option('wpmf_folder_option2');
        $id_author = get_current_user_id();

        if($role == 'administrator'){
            if(isset($_SESSION['wpmf_display_media']) && $_SESSION['wpmf_display_media'] == 'yes'){
                $query->query_vars['author'] = $id_author;
            }
        }elseif(isset($wpmf_active_media) && $wpmf_active_media == 1){
            if(in_array($role, $roles) && $role != 'administrator'){
                $query->query_vars['author'] = $id_author;
            }
        }
   return $query;
}

//add_filter( 'the_posts','wpmf_post_results'  );
public function wpmf_post_results($posts){
    $taxo = $this->get_taxonomy();
    if (defined('DOING_AJAX') && DOING_AJAX && $_REQUEST['action']==='query-attachments') {
	if ( isset( $_REQUEST['query']['category'] ) ){
	    $parent = $_REQUEST['query']['category']['term_id'];
	}else{
	    $parent = 0;
	}
	$terms = get_terms( $taxo, array(
			    'orderby'       => 'name',
			    'order'         => 'ASC',
			    'parent'	    => $parent,
			    'hide_empty'    => false
			    )
			);
	$ij = 1;
	if(!empty($terms)){
	    foreach ($terms as $term) {
		$post = new stdClass();
		$post->ID = -$ij;
		$post->comment_count = 0;
		$post->comment_status = 'open';
		$post->filter = 'raw';
		$post->guid = $term->name;
		$post->menu_order = 0;
		$post->ping_status = 'open';
		$post->pinged = '';
		$post->post_author = '1';
		$post->post_content = $term->name;
		$post->post_content_filtered = '';
		$post->post_date = '2014-10-02 03:49:36';
		$post->post_date_gmt = '2014-10-02 03:49:36';
		$post->post_excerpt = '';
		$post->post_mime_type = 'application/xxx-folder';
		$post->post_modified = '2014-10-02 03:49:36';
		$post->post_modified_gmt = '2014-10-02 03:49:36';
		$post->post_name = $term->slug;
		$post->post_parent = 0;
		$post->post_password = '';
		$post->post_status = 'inherit';
		$post->post_title = $term->name;
		$post->post_type = 'attachment';
		$post->to_ping = '';
		$post = new WP_Post($post);
		$ij++;
	    }
	}
	array_splice($posts, 40);
    }
    return $posts;
}

    public function wpmf_change_folder(){
        global $current_user;
        $id = (int)$_POST['id'] | 0;
        $_SESSION['wpmf-current-folder'] = $id;
        $taxo = Wp_Media_Folder::get_taxonomy();

        $wpmf_active_media = get_option('wpmf_active_media');
        $user_roles = $current_user->roles;
        if(isset($_SESSION['wpmf_display_media']) && $_SESSION['wpmf_display_media'] == 'yes'){
            $terms = get_terms( $taxo, array('orderby'=> 'id','order'=> 'ASC','hide_empty'=> false));
            $id1 = array();
            foreach ($terms as $term){
                if($term->term_group == get_current_user_id()){
                    if($term->parent != 0){
                        $term_parent = get_term($term->parent,$taxo);
                        if($term_parent->term_group != null && $term_parent->term_group == get_current_user_id() ){
                            $id1[] = $term->term_id;
                        }
                    }else{
                        $id1[] = $term->term_id;
                    }
                }
            }

            wp_send_json($id1);
        }
    }

    /* */
    public function wpmf_after_upload($metadata, $attachment_id) {
        $taxo = $this->get_taxonomy();
        $parent = isset($_SESSION['wpmf-current-folder']) ?(int)$_SESSION['wpmf-current-folder']: 0;

        if($parent){
            wp_set_object_terms($attachment_id,$parent,$taxo,true);
        }
        return $metadata;
    }

    /** Add a new folder via ajax **/
    public function wpmf_add_folder(){
        $taxo = $this->get_taxonomy();
        if(isset($_POST['name']) && $_POST['name']){
            $term = esc_attr($_POST['name']);
        }else{
            $term = __('New folder',BW_TD);
        }
        $termParent = (int)$_POST['parent'] | 0;
        $id_author = get_current_user_id();
        $inserted = wp_insert_term($term, $taxo,array('parent'=>$termParent));
        if ( is_wp_error($inserted) ) {
            // oops WP_Error obj returned, so the term existed prior
            wp_send_json($inserted->get_error_message());
        }else{
            $updateted = wp_update_term( $inserted['term_id'], $taxo, array('term_group' => $id_author) );
            $termInfos = get_term($updateted['term_id'],$taxo);
            wp_send_json($termInfos);
        }
    }


    /** Edit folder via ajax **/
    public function wpmf_edit_folder(){
        $taxo = $this->get_taxonomy();
        $term = esc_attr($_POST['name']);
        if(!$term){
            return;
        }
        //check duplicate name
        $siblings = get_terms($taxo, array('fields' => 'names', 'get' => 'all', 'parent' => (int)$_POST['parent_id']));
        if (in_array($term, $siblings)) {
            return wp_send_json(false);
        }
        $termInfos = wp_update_term((int)$_POST['id'],$taxo,array('name'=>$term));
         if($termInfos instanceof WP_Error){
            wp_send_json($termInfos->get_error_messages());
        }else{
             $termInfos = get_term($termInfos['term_id'],$taxo);
            wp_send_json($termInfos);
        }
    //
    }

    /** Edit folder via ajax **/
    public function wpmf_delete_folder(){
        $taxo = $this->get_taxonomy();
        $childs = get_term_children((int)$_POST['id'],$taxo);
        if(is_array($childs) && count($childs)>0){
            wp_send_json('not empty');
        }else{
            $child = get_term_children((int)$_POST['parent'],$taxo);
            wp_send_json(array('status' => wp_delete_term((int)$_POST['id'],$taxo),'count_child' => count($child)));
        }
    }

    /** Move a file via ajax **/
    public function wpmf_move_file(){
        $taxo = $this->get_taxonomy();
        $return = true;
        $ids = explode(',', $_POST['ids']);
        foreach ($ids as $id){
            wp_delete_object_term_relationships((int)$id, $taxo);
            if((int)$_POST['id_category'] === 0 || wp_set_object_terms((int)$id,(int)$_POST['id_category'],$taxo,true)){

            }else{
                $return = false;
            }
        }
        wp_send_json($return);
    }

    /** Move a folder via ajax **/
    public function wpmf_move_folder(){
        $taxo = $this->get_taxonomy();
        //check duplicate name
        $term = esc_attr($_POST['name']);
        $siblings = get_terms($taxo, array('fields' => 'names', 'get' => 'all', 'parent' => (int)$_POST['id_category']));
        if (in_array($term, $siblings)) {
            return wp_send_json(false);
        }

        $r = wp_update_term((int)$_POST['id'],$taxo,array('parent'=>(int)$_POST['id_category']));
        if($r instanceof WP_Error){
            wp_send_json(false);
        }else{
            $child_id = get_term_children((int)$_POST['id'],$taxo);
            $child_id_category = get_term_children((int)$_POST['id_category'],$taxo);
            $child_parent_id = get_term_children((int)$_POST['parent_id'],$taxo);
            wp_send_json(array('status' => true, 'count_id'=>count($child_id), 'id_category'=>count($child_id_category), 'parent_id'=>count($child_parent_id),));
        }
    }

    public function generatePageTree($datas, $parent = 0, $depth=0, $limit=0){
            if($limit > 1000) return ''; // Make sure not to have an endless recursion
            $tree = array();
            for($i=0, $ni=count($datas); $i < $ni; $i++){
                if($datas[$i]->parent == $parent){
                    //$datas[$i]->name = str_repeat('&nbsp;&nbsp;',$depth).$datas[$i]->name;
                    $datas[$i]->name = $datas[$i]->name;
                    $datas[$i]->depth = $depth;
                    $tree[] = $datas[$i];
                    $t = $this->generatePageTree($datas, $datas[$i]->term_id, $depth+1, $limit++);
                        $tree = array_merge($tree,$t);
                }
            }
            return $tree;
    }

    /**
     * sort parents before children
     * http://stackoverflow.com/questions/6377147/sort-an-array-placing-children-beneath-parents
     *
     * @param array   $objects input objects with attributes 'id' and 'parent'
     * @param array   $result  (optional, reference) internal
     * @param integer $parent  (optional) internal
     * @param integer $depth   (optional) internal
     * @return array           output
     */
    public function parent_sort(array $objects, array &$result=array(), $parent=0, $depth=0) {
        foreach ($objects as $key => $object) {
            if ($object->parent == $parent) {
                $object->depth = $depth;
                array_push($result, $object);
                unset($objects[$key]);
                $this->parent_sort($objects, $result, $object->term_id, $depth + 1);
            }
        }
        return $result;
    }

    //Folder tree
    public function wpmf_get_terms(){
        global $current_user;
        $taxo = $this->get_taxonomy();
        $dir = '/';
        if (!empty($_GET['dir'])) {
            $dir = $_GET['dir'];
            if ($dir[0] == '/') {
                $dir = '.' . $dir . '/';
            }
        }
        $dir = str_replace('..', '', $dir);
        $root = dirname(__FILE__) . '/../';
        $dirs = $fi = array();
        $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
        $files = get_terms( $taxo, array('orderby'=> 'id','order'=> 'ASC','parent'=> $id,'hide_empty'=> false));
        $wpmf_active_media = get_option('wpmf_active_media');
        $option2 = get_option('wpmf_folder_option2');
        $user_roles = $current_user->roles;
        foreach ($files as $file) {
            if(($user_roles[0] !='administrator' && isset($wpmf_active_media) && $wpmf_active_media == 1) || ($user_roles[0] =='administrator' && isset($_SESSION['wpmf_display_media']) && $_SESSION['wpmf_display_media'] =='yes')){
                if($file->term_group == get_current_user_id()){
                    $child = get_term_children((int)$file->term_id,$taxo);
                    $countchild = count($child);
                    $dirs[] = array('type' => 'dir', 'dir' => $dir, 'file' => $file->name ,'id' => $file->term_id,'parent_id' => $file->parent,'count_child' => $countchild , 'term_group' => $file->term_group);
                }
            }else{
                $child = get_term_children((int)$file->term_id,$taxo);
                $countchild = count($child);
                $dirs[] = array('type' => 'dir', 'dir' => $dir, 'file' => $file->name ,'id' => $file->term_id,'parent_id' => $file->parent,'count_child' => $countchild , 'term_group' => $file->term_group);
            }
        }

        if(count($dirs)<0){
            wp_send_json('not empty');
        }else{
            wp_send_json($dirs);
        }
    }

    public function get_taxonomy() {
        $option = get_option('wpmf_use_taxonomy');
        if(isset($option)){
            if($option==0){
                $taxo = 'category';
            }else{
                $taxo = 'wpmf-category';
            }
        }else{
            $taxo = 'category';
        }
        return $taxo;
    }

    public function wpmf_term_root(){
        global $current_user;
        $taxo = $this->get_taxonomy();
        $term_roots = get_terms( $taxo, array('parent'=> 0 , 'hide_empty'=> false));
        $wpmfterm = array();
        if(count($term_roots) > 0 ){
            foreach ($term_roots as $term){
                if($term->name == $current_user->user_login && $term->term_group == get_current_user_id()){
                    $wpmfterm['term_rootId'] = $term->term_id;
                    $wpmfterm['term_label'] = $term->name;
                }
            }
        }
        return $wpmfterm;
    }
}
?>
