<!DOCTYPE html>
<html dir="rtl">
<head>
<?php
    global $post, $APP;
    $logo =  app_f()->assets('/img/logo.png');
    $logo_w =  app_f()->assets('/img/logo-white-red.png');
    $favicon =  app_f()->assets('/img/favicon.png');
    $u = wapi()->user();
?>
    <meta charset='utf-8'>
    <meta http-equiv='X-UA-Compatible' content='IE=edge,chrome=1'>
    <meta name='viewport' content='width=device-width, initial-scale=1, maximum-scale=1' />
    <meta name='format-detection' content='telephone=no'>
    <link rel="shortcut icon" href="<?php echo $favicon; ?>?_=2" />
    <script>window.ajaxurl = "<?php echo admin_url('admin-ajax.php'); ?>";</script>

    <?php wp_head(); ?>
</head>
<body <?php body_class(''); ?>>

<div class="d-lg-none bg-dark row p-1 no-gutters align-items-center">
    
    <div class="col-9">
        <form class="header_search" method="GET" action="<?php echo site_url('search');?>">
            <input type="text" class="form-control" name="q" required placeholder="ابحث" autocomplete="off" value="<?php echo app_rq('q'); ?>">
            <i class="fa fa-search color-red"></i>
        </form>
    </div>

    <div class="col-3 text-center">
        <div class="dropdown">
            <a href="#" class="dropdown-toggle" data-toggle="dropdown" 
                style="font-size: 20px;text-decoration: none; display: flex; justify-content: center;align-items: center; color: #fff;">
                <i class="fa fa-user-circle"></i>
            </a>
            <div class="dropdown-menu">
                <?php if($u) :?>
                    <a class="dropdown-item" href="<?php echo site_url('profile'); ?>">حسابي</a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="<?php echo site_url('logout'); ?>">تسجيل الخروج</a>
                <?php else: ?>
                    <a href="#" class="dropdown-item btn-login">تسجيل الدخول</a> 
                <?php endif;?>
            </div>
        </div>
    </div>
</div>

<div class="<?php app_f()->body_class(); ?>">
    
    <div class="container">
        <nav class="navbar navbar-expand-lg navbar-dark">
            <a href="<?php echo site_url();?>" class="navbar-brand">
                <img src="<?php echo $logo_w;?>" alt="<?php bloginfo('name');?>">
            </a>

            <button class="navbar-toggler border-0 p-0" type="button" data-toggle="collapse" data-target="#navbar">
                <!-- <span class="navbar-toggler-icon"></span> -->
                <svg viewBox="0 0 32 32" width="48px" height="48px"><g>
                    <path d="M1,10h30c0.552,0,1-0.448,1-1c0-0.552-0.448-1-1-1H1C0.448,8,0,8.448,0,9C0,9.552,0.448,10,1,10z" fill="#fff"/>
                    <path d="M31,15H1c-0.552,0-1,0.448-1,1c0,0.552,0.448,1,1,1h30c0.552,0,1-0.448,1-1C32,15.448,31.552,15,31,15z" fill="#fff"/>
                    <path d="M31,22H11c-0.552,0-1,0.448-1,1s0.448,1,1,1h20c0.552,0,1-0.448,1-1S31.552,22,31,22z" fill="#fff"/></g>
                </svg>
            </button>

            <div class="collapse navbar-collapse" id="navbar">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item"><a class="nav-link" href="<?php echo site_url();?>">الرئيسية</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo site_url('subjects');?>">المواضيع</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo site_url('request');?>">طرح سؤال</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo site_url('contact');?>">اتصلوا بنا</a></li>
                </ul>

                <div class="my-2 my-md-0 d-none d-lg-block">
                    <div class="user-header"><div class="mb-1">
                    <?php if($u) :?>
                        <a href="<?php echo site_url('profile'); ?>" class="link-with-sep">حسابي</a>
                        <a href="<?php echo site_url('logout'); ?>">تسجيل الخروج</a>
                    <?php else: ?>
                        <a href="#" class="btn-login">تسجيل الدخول</a> 
                    <?php endif;?>
                    </div></div>

                    <form class="header_search" method="GET" action="<?php echo site_url('search');?>">
                        <input type="text" class="form-control" name="q" required placeholder="ابحث" autocomplete="off" value="<?php echo app_rq('q'); ?>">
                        <i class="fa fa-search color-red"></i>
                    </form>
                </div>
            </div>
        </nav>
    </div>
</div>

    