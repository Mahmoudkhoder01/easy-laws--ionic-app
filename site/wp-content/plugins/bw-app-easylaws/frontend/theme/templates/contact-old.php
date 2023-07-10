<div id="map" class="shadow" style="width:100%; height: 400px;"></div>

<div class="py-5 bg-light">
    <div class="container">
        <div class="row">
            <div class="col-12 col-lg-6 mb-5">
                <h2 class="mb-4">للاتصال بنا</h2>
                <p><i class="fa fa-map-marker mx-2"></i> سنتر مكلس ٢٠٠١، بلوك A، الطابق الثالث، مكلس، لبنان</p>
                <p><i class="fa fa-phone mx-2"></i> <a href="tel:+96170150051" dir="ltr">+96170150051</a></p>
                
                <div class="my-4">
                    <a href="https://m.me/easylaws" target="_blank" class="mr-3"><img style="width:43px; height: 43px;" src="<?php echo app_f()->assets('/img/messenger.svg');?>" alt="" /></a>
                    <a href="https://api.whatsapp.com/send?phone=96170150051" target="_blank" class="mr-3"><img style="width:50px; height: 50px;" src="<?php echo app_f()->assets('/img/whatsapp.svg');?>" alt="" /></a>
                </div>
                <p><button class="btn btn-dark app_directions" data-address="33.8602,35.5522" data-locating="جارٍ تحديد موقعك…"><i class="fa fa-location-arrow mx-3"></i> الاتجاهات</button></p>
            </div>
            <div class="col-12 col-lg-6">
<?php if(app_rq('_action') == 'sendmail'):
    $to = 'support@easylaws.me';
    $subject = '** Contact from easylaws.me: ('.app_rq('_name').')';
    $msg = '
        <p><b>Name: </b>'.app_rq('_name').'</p>
        <p><b>Email: </b>'.app_rq('_email').'</p>
        <p><b>Phone: </b>'.app_rq('_phone').'</p>
        <p><b>Message: </b></p>
        <p>'.nl2br(app_rq('_message')).'</p>
    ';
    if(wp_mail($to, $subject, $msg)){
        echo '<div class="alert alert-success">نشكر تواصلكم معنا. سيتم مراسلتكم في أقرب وقت ممكن.</div>';
    } else {
        echo '<div class="alert alert-danger">حدث خطا، يرجى المحاولة مره أخرى</div>';
    }
else: ?>
                <h2 class="mb-4">للتواصل معنا</h2>
                <form method="POST" action="" class="validate">
                    <input type="hidden" name="_action" value="sendmail" />
                    <div class="form-group">
                        <label>الاسم الكامل</label>
                        <input name="_name" type="text" class="form-control" required minlength="3" />
                    </div>
                    <div class="form-group">
                        <label>بريدك الالكتروني</label>
                        <input name="_email" type="email" class="form-control" required />
                    </div>
                    <div class="form-group">
                        <label>رقم الهاتف</label>
                        <input name="_phone" type="text" class="form-control" required minlength="8" />
                    </div>
                    <div class="form-group">
                        <label>رسالتك</label>
                        <textarea name="_message" class="form-control" rows="3" required minlength="2"></textarea>
                    </div>
                    <button type="submit" class="btn btn-dark btn-block">ارسال</button>
                </form>
<?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script async defer src="https://maps.googleapis.com/maps/api/js?callback=initMap&key=AIzaSyAca0f-WVNBWeGbbsw9kjmGI13z_J0SwPw"></script>
<script>
var geocoder, map;
var markers = [
    ['Easy Laws', 33.8602, 35.5522]
];

function initMap() {
    var map_center = {lat: markers[0][1], lng: markers[0][2]};
    var map = new google.maps.Map(document.getElementById('map'), {
        zoom: 17,
        center: map_center,
        gestureHandling: 'cooperative'
        // mapTypeId: google.maps.MapTypeId.ROADMAP
    });
    for (i = 0; i < markers.length; i++) {
        marker = new google.maps.Marker({
            position: {lat: markers[i][1], lng: markers[i][2]},
            map: map,
            title: markers[i][0]
        });
        // var infowindow = new google.maps.InfoWindow();
        // google.maps.event.addListener(marker, "click", (function(marker) {
        //     return function(evt) {
        //         var content = marker.getTitle();
        //         infowindow.setContent(content);
        //         infowindow.open(map, marker);
        //     }
        // })(marker));
    }
}

</script>
