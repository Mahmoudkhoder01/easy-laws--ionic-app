<?php get_header(); ?>

    <?php
        global $BWMM;
        $logo = '';
        if (isset($BWMM['logo']) && isset($BWMM['logo']['url'])){
            $logo = '<div class="logo"><img src="'.$BWMM['logo']['url'].'"></div>';
        }
    ?>
    <div class="wrap">
        <?php echo $logo; ?>
        <h1 style="color:<?php echo $BWMM['heading_color'];?>"><?php echo $BWMM['heading'];?></h1>
        <h2 style="color:<?php echo $BWMM['text_color'];?>"><?php echo do_shortcode($BWMM['text']);?></h2>
    </div>

<?php get_footer(); ?>
