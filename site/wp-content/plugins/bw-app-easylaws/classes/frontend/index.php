<?php
require_once __DIR__."/mobile-detect.php";
$detect = new Mobile_Detect;
$link_ios = 'https://itunes.apple.com/lb/app/easy-laws-me/id1365602918?mt=8';
$link_android = 'https://play.google.com/store/apps/details?id=me.easylaws.www';

$isMobile = $detect->isMobile();
if( $detect->isiOS() ) $link = $link_ios;
if( $detect->isAndroidOS() ) $link = $link_android;

$q = get_query_var('qID');
if(!$q) $q = app_rq('q');;

$card = false;
$title = get_bloginfo('name');
$desc = 'Your friend in-law';

if($q){
    $t = DB()->prefix.'app_questions';
    $row = DB()->get_row("SELECT title, details FROM {$t} WHERE ID={$q}");
    if($row) {
        $card = true;
        $title = $row->title;
        $desc = __excerpt($row->details);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0">
    <link rel="icon" href="<?php app()->assets('img/favicon.png?v=3');?>" />

    <meta property="og:type" content="website" />
    <meta property="og:title" content="<?php echo $title; ?>" />
    <meta property="og:description" content="<?php echo $desc;?>" />
    <meta property="og:image" content="<?php app()->assets('img/icon.png?v=3');?>" />
    <meta property="og:url" content="<?php echo site_url(); ?>" />
    <meta name="twitter:card" content="summary_large_image" />
    <meta property="og:site_name" content="<?php echo get_bloginfo('name'); ?>" />
    <meta property="fb:app_id" content="1363264657105483" />
    <meta name="twitter:site" content="@easylaws" />

    <link rel="stylesheet" href="<?php app()->assets('css/app.css');?>?v=7">

    <?php do_action('the_app_header'); ?>
    <?php wp_head();?>
    <?php echo app_option('css_header'); echo app_option('js_header');?>
</head>
<body class="linear-bg">
    <div class="container"><div style="text-align: center;">
        <div class="logo"><img src="<?php app()->assets('img/logo-white.png');?>"></div>
        <?php if($card): ?>
            <div style="background: #fff; padding: 20px; border-radius: 10px; margin-bottom: 20px; direction: rtl;">
                <h4><?php echo $title;?></h4>
                <p><?php echo $desc;?></p>
                <?php if($isMobile):?>
                    <p><a href="<?php echo $link;?>" style="padding: 10px 20px; background: #000; color: #fff;    display: inline-block;margin-top: 20px; text-decoration: none; border-radius: 5px;font-size: 18px;">
                        حمل التطبيق
                    </a></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <!-- <h3>Download Now</h3> -->
        <div class="store-links">
            <a href="<?php echo $link_ios; ?>">
                <img src="<?php app()->assets('img/appstore.svg');?>">
            </a>
            <a href="<?php echo $link_android; ?>">
                <img src="<?php app()->assets('img/playstore.svg');?>">
            </a>
        </div>
        <div class="text">
            <p>Easy Laws is a unique FREE application explaining the Lebanese laws, legislations, rules and regulations. Easy Laws displayed in a simple language and comprehensive Question and Answer format.  It provides Examples of real life cases and additional Notes and clarifications.  For further credibility and transparency, each question is linked to its related legal article as it was originally stated in the Official Gazette.</p>
            <p>Our objective is to enhance the rule of law and to educate the individual on his rights and duties.</p> 
        </div>
        <div class="copyright">Copyright © 2018 EasyLaws®. All rights reserved.</div>
    </div></div>

    <div class="seo">
    Lebanese Laws, Lois Libanaises, Laws of Lebanon, Loi du Liban, Législations, Legislations, Legal, Légale, Q&A, Explanation of Law, Explications de la Loi, Loi simplifier, simple Laws, best explanation, of law, Easy Law, EasyLaw, Easy Laws ME, constitutional, legitimate, juridical, lawful, rightful, legality, justice, judgement, paralegal, legal assistant, act, regulation, enactment, decree, rule of law, legal publishing, legal publishers, قوانين لبنان, القوانين اللبنانية, قانوني, قانون لبنان, تشريعات, شرائع, تفسير القانون, القانون السهل, القوانين السهلة, النشر القانوني, اسئلة واجوبة, تفسير القوانين, تفسير القانون, دستوري, حقوق, واجبات, قضائي, عدالة, مراسيم, مرسوم, المنشورات القانونية, إجهاض, Abortion, Avortement, إنتحار, suicide, elections, éléctions, الارث, inheritance, Héritage, الاغتصاب, rape, viole, violation, التحرش الجنسي, sexual harassment, harcèlement sexuel, الدفاع عن النفس, self-defense, auto-défense, الرصاص الطائش, lead reckless, plomb irréflechi, الزنا, adultery, adultère, قانون السير , Traffic law, loi de la route, العنف الأسري, domestic violence, violence familial, المثلية, homosexuality, homosexual, المخدرات, drugs, drogues, المدارس, schools, écoles, collèges, الوصية, commandment, commandement, بناء, buildings, batiments, تحقيقات الجرائم, crime investigations, enquêtes criminelles, جرائم المعلوماتية, computer crimes, crimes informatiques, حضانة, incubation, custody, droit de garde, حوادث, accidents, زواج, marriage, mariage, سِفاح القُربى, incest, inceste, شركات, companies, entreprises, قانون العمل, Labor law, loi du travail
    </div>
<?php do_action('the_app_footer'); ?>
<?php wp_footer();?>
<?php echo app_option('js_footer');?>
</body>
</html>
