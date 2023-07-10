<!DOCTYPE html>
<html>
    <head>
        <?php global $BWMM; ?>
        <title><?php echo $BWMM['title'];?></title>
        <meta charset="utf-8" />
		<meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1, maximum-scale=1">
        <link rel="stylesheet" href="<?php echo THEME_URL;?>/css.css">
		<?php if (isset($BWMM['favicon'])) echo '<link rel="shortcut icon" href="'.$BWMM['favicon']['url'].'" />';?>
        <?php wp_head(); ?>
        <?php if (isset($BWMM['css_code']) && $BWMM['css_code']!='') echo '<style type="text/css">'.$BWMM['css_code'].'</style>'; ?>
    </head>
    <?php
        if($BWMM['bg_type'] == 'image'){
            $style = 'background-image:url('.$BWMM['bg_image']['url'].'); background-color:'.$BWMM['bg_color'].';';
        } else {
            $style = 'background-color:'.$BWMM['bg_color'].';';
        }
    ?>
    <body style="<?php echo $style; ?>">
