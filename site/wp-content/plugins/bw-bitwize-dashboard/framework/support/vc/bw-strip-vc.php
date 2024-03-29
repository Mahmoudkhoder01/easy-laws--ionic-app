<?php
if ( ! function_exists( 'bw_vc_strip' ) ) :

    function bw_vc_strip() {
        // vc_remove_element( 'vc_column_text' );
        // vc_remove_element( 'vc_raw_html' );
        // vc_remove_element( 'vc_raw_js' );
        // vc_remove_element( 'vc_pie' );
        // vc_remove_element( 'vc_video' );

        vc_remove_element("vc_button");
        vc_remove_element('vc_button2');
        vc_remove_element('vc_cta_button');
        vc_remove_element('vc_cta_button2');
        // vc_remove_element("vc_posts_slider");
        // vc_remove_element("vc_gmaps");
        // vc_remove_element("vc_teaser_grid");
        // vc_remove_element("vc_progress_bar");

        vc_remove_element("vc_facebook");
        vc_remove_element("vc_tweetmeme");
        vc_remove_element("vc_googleplus");
        vc_remove_element("vc_facebook");
        vc_remove_element("vc_pinterest");
        vc_remove_element("vc_flickr");

        // vc_remove_element("vc_message");

        // vc_remove_element("vc_carousel");
        // vc_remove_element("vc_tour");
        // vc_remove_element("vc_separator");
        // vc_remove_element("vc_single_image");
        vc_remove_element("vc_accordion");
        vc_remove_element("vc_accordion_tab");
        // vc_remove_element("vc_toggle");
        vc_remove_element("vc_tabs");
        vc_remove_element("vc_tab");
        // vc_remove_element("vc_images_carousel");
        // vc_remove_element("vc_gallery");
        // vc_remove_element('vc_text_separator');
        vc_remove_element('vc_widget_sidebar');

        vc_remove_element("vc_wp_archives");
        vc_remove_element("vc_wp_calendar");
        vc_remove_element("vc_wp_categories");
        // vc_remove_element("vc_wp_custommenu");
        vc_remove_element("vc_wp_links");
        vc_remove_element("vc_wp_meta");
        vc_remove_element("vc_wp_pages");
        vc_remove_element("vc_wp_posts");
        vc_remove_element("vc_wp_recentcomments");
        vc_remove_element("vc_wp_rss");
        vc_remove_element("vc_wp_search");
        vc_remove_element("vc_wp_tagcloud");
        vc_remove_element("vc_wp_text");
    }
    bw_vc_strip();
    add_action( 'init', 'bw_vc_strip' );
endif;
?>
