<?php
get_header();

if (have_posts()) : the_post();
	the_content();
endif;

get_footer();
?>
