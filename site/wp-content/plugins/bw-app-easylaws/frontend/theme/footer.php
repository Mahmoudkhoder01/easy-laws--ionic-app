<?php 
    $testimonials = [
        [
            'en' => 1,
            'user' => 'Carla Zoghby',
            'text' => 'I downloaded the app curious to check it out, then I was amazed with all the info and answers. Very detailed and specific also easy to use and surf.'
        ],
        [
            'en' => 1,
            'user' => 'Rami Alame',
            'text' => 'The application is purely excellent and has a great content. As a Lawyer and expert in the field I definitely recommend it to everyone.'
        ],
        [
            'en' => 1,
            'user' => 'Maroun1234',
            'text' => 'By small phrases, you are teaching generations..'
        ],
        [
            'en' => 0,
            'user' => 'Anthony Ghostine',
            'text' => 'عنجد يعطيكن الف عافية برنامج كتير مهم وكتير مرتب مشغول ومتعوب عليه 👌'
        ],
        [
            'en' => 0,
            'user' => 'نجيب القمر',
            'text' => 'تطبيق جدا مهم وعملي ومحترف'
        ],
        [
            'en' => 1,
            'user' => 'RPKiwan',
            'text' => 'Chapeau bas aux créateurs de cette application.merci…'
        ],
        [
            'en' => 1,
            'user' => 'Grace M.Y',
            'text' => 'It is an excellent application in which we can find answers to our legal questions without having to bother our lawyers or spend hours reading law books.<br>Keep up your excellent initiative and work!'
        ],
        [
            'en' => 1,
            'user' => 'Jaodbdhd',
            'text' => 'This app that I just downloaded answered already most of the questions I had for the past few years; questions related to work, end of services and elections. It brought to my attention things I’ve never thought of. I recommend people to downloaded if they need prompt and user friendly answers related to their rights, elections and Lebanese laws. Well done!'
        ],
        [
            'en' => 1,
            'user' => 'Wael Khan',
            'text' => 'Great and helpful app for every citizen! For me as a political science student, it helped me a lot, big thanks for the developers 👏🏼'
        ],
    ];
?>    
<div class="pre-footer">
    <div class="container">
        <div class="row">
            
            <div class="col-12 col-lg-7 mb-3 mb-lg-0">
                <div class="row align-items-center">
                    <div class="col-4 col-lg-3 text-center">
                        <img src="<?php echo app_f()->assets('/img/testimonials.png');?>" alt="" />
                    </div>
                    <div class="col-8 col-lg-9">
                        <h3>شهادات</h3>
                        <div class="testimonials-carousel owl-carousel owl-theme">
                            <?php foreach($testimonials as $t): $tc = $t['en'] ? 'ltr' : ''; ?>
                            <div class="item">
                                <p class="testimonials-user"><?php echo $t['user'];?></p>
                                <div class="testimonials-stars"></div>
                                <p class="testimonials-text"><span class="<?php echo $tc;?>"><?php echo $t['text'];?></span></p>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-1 d-none d-lg-block text-center align-self-center">
                <div class="sep"></div>
            </div>

            <div class="col-12 col-lg-4">
                
                <div class="row align-items-center">
                    <div class="col-4 text-center">
                        <img src="<?php echo app_f()->assets('/img/dyk.jpg');?>" alt="" />
                    </div>
                    <div class="col-8">
                        <?php 
                            $dyk = wapi()->get_dyk(1);
                        ?>
                        <h3>هل كنت تعلم </h3>
                        <p><?php echo $dyk->did_you_know; ?></p>
                        <a href="<?php echo site_url('question/'.$dyk->ID) ?>" class="btn btn-dark">إقرأ المزيد</a>
                    </div>
                </div>

            </div>

        </div>
    </div>
</div>

<footer>
    <div class="container">
        <div class="row b-b">
            <div class="col-12 col-lg-3 text-center text-lg-left mb-3 mb-lg-0">
                <a href="<?php echo site_url();?>">
                    <img class="logo-footer" src="<?php echo app_f()->assets('/img/logo.png');?>" alt="" />
                </a>
            </div>
            <div class="col-12 col-lg-4 text-center mb-3 mb-lg-0">
                <a href="https://itunes.apple.com/lb/app/easy-laws-me/id1365602918?mt=8" target="_blank" class="footer-stores">
                    <img src="<?php echo app_f()->assets('/img/appstore.svg');?>" alt="" />
                </a>

                <a href="https://play.google.com/store/apps/details?id=me.easylaws.www" target="_blank" class="footer-stores">
                    <img src="<?php echo app_f()->assets('/img/playstore.svg');?>" alt="" />
                </a>

            </div>
            <div class="col-12 col-lg-5">
                <div class="row">
                    <div class="col-12 col-lg-6">
                        <div class="social text-center text-lg-right">
                            <a href="https://www.facebook.com/easylaws/" target="_blank"><i class="fa fa-facebook-square"></i></a>
                            <a href="https://www.instagram.com/easylaws2017/" target="_blank"><i class="fa fa-instagram"></i></a>
                            <a href="https://www.youtube.com/channel/UCMT1gIMNLqGYU1EK3azAB9w" target="_blank"><i class="fa fa-youtube-square"></i></a>
                        </div>
                    </div>

                    <div class="col-12 col-lg-6">
                        <div class="text-center text-lg-right">
                            <a href="https://hiil.org/what-we-do/the-justice-accelerator/" target="_blank">
                                <img src="<?php echo app_f()->assets('/img/hiil.png');?>" alt="" style="max-height: 80px" />
                            </a>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 col-sm-12 text-center text-lg-left mb-3 mb-lg-0">
                
                <ul class="footernav">
                    <li><a href="<?php echo site_url();?>">الرئيسية</></li>
                    <li><a href="<?php echo site_url('subjects');?>">المواضيع</a></li>
                    <li><a href="<?php echo site_url('request');?>">طرح سؤال</a></li>
                    <li><a href="<?php echo site_url('contact');?>">اتصلوا بنا</a></li>
                </ul>

            </div>
            <div class="col-md-6 col-sm-12 text-center text-lg-right">©<?php echo date('Y');?> Easy Laws | جميع الحقوق محفوظه.</div>
        </div>
    </div>
</footer>
<?php 
wp_footer();

if(!wapi()->user()) include(__DIR__.'/templates/modals/__init.php');

if(app_rq('error') || app_rq('msg')){
    $atype = app_rq('error') ? 'danger' : 'success';
    $amsg = app_rq('error') ? app_rq('error') : app_rq('msg');
    echo '<script>jQuery.notify({message: "<div class=\"text-center\">'.$amsg.'</div>"}, {type: "'.$atype.'"});</script>';
}
?>
    </body>
</html>
