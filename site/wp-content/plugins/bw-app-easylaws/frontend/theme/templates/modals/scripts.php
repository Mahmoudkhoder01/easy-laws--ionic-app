<script src="https://apis.google.com/js/api:client.js"></script>
<script id="facebook-jssdk" src="https://connect.facebook.net/en_US/sdk.js"></script>
<script>
    jQuery(document).ready(function($){

        var GOOGLE_ID = '541446533258-4912n0mt6p6i08oamldunnid5b8cipmo.apps.googleusercontent.com',
            FACEBOOK_ID = '1363264657105483';

        function alert(title, text){
            if(typeof text == 'undefined'){ text = title; title = ''; }
            swal({ title: title, text: text, type: 'warning' });
        }

        function success(title, text){
            if(typeof text == 'undefined'){ text = title; title = ''; }
            swal({ title: title, text: text, type: 'success' });
        }

        gapi.load('auth2', function(){
            auth2 = gapi.auth2.init({
                client_id: GOOGLE_ID,
                cookiepolicy: 'single_host_origin',
            });
            attachSignin($('#google-login').get(0));
            attachSignin($('#google-signup').get(0));
        });

        function attachSignin(element) {
            auth2.attachClickHandler(element, {}, function(googleUser) {
                onGoogleSignIn(googleUser);
                // element.innerText = "Signed in: " + googleUser.getBasicProfile().getName();
            }, function(error) {
                // alert(JSON.stringify(error, undefined, 2));
                alert(error.error);
            });
        }

        function onGoogleSignIn(googleUser) {
            var res = googleUser.getBasicProfile();
            console.log('PROFILE: ', res);
            console.log('ID', res.getId());
            console.log('Name', res.getName());
            console.log('FName', res.getGivenName());
            console.log('LName', res.getFamilyName());
            console.log('Image URL', res.getImageUrl());
            console.log('Email', res.getEmail());  

            $.post(ajaxurl, {
                action: 'login',
                provider: 'google',
                google_id: res.getId(),
                name: res.getName(),
                first_name: res.getGivenName(),
                last_name: res.getFamilyName(),
                email: res.getEmail(),
                image: res.getImageUrl(),
                // link: res.link
            }, function(data){
                if(data == 'OK'){
                    window.location.reload();
                } else {
                    alert('حدث خطأ: ' + data);
                }
            });
        }

        function googleSignOut() {
            var auth2 = gapi.auth2.getAuthInstance();
            auth2.signOut().then(function () {
              console.log('User signed out.');
            });
        }

        window.fbAsyncInit = function() {
            FB.init({
                appId      : FACEBOOK_ID,
                xfbml      : true,
                version    : 'v3.2'
            });
            FB.AppEvents.logPageView();
        };

        var FB_Login = window.FB_Login = function(){
            var fbimg = '';
            FB.login(function(response) {
                if (response.authResponse){
                    FB.api('/me?fields=id,name,first_name,last_name,picture,email,link,gender,age_range,timezone,birthday,location,friends', function(res) {
                        // console.log(response);
                        $.post(ajaxurl, {
                            action: 'login',
                            provider: 'facebook',
                            fb_id: res.id,
                            name: res.name,
                            first_name: res.first_name,
                            last_name: res.last_name,
                            email: res.email,
                            image: 'https://graph.facebook.com/'+res.id+'/picture?type=large',
                            link: res.link
                        }, function(data){
                            if(data == 'OK'){
                                window.location.reload();
                            } else {
                                alert('حدث خطأ: ' + data);
                            }
                        });
                    });
                } else {
                    alert('غير مصرح به أو ملغى', 'مصادقة الفيسبوك الخاص بك لم تسير على ما يرام :(');
                }
            },{
                // scope: 'public_profile,email,user_birthday', // requires review
                scope: 'public_profile,email',
                return_scopes: true
            });
            return false;
        }

        var FB_Logout = window.FB_Logout = function(){
            FB.getLoginStatus(function (response) {
                console.log(response);
                if (response && response.status === 'connected') {
                    FB.logout(function(){
                        success('تم تسجيل الخروج');
                    });
                }
            });
            return false;
        }

        $('.btn-facebook-login').off('click').on('click', function(e){
            e.preventDefault();
            FB_Login();
        });

        $('.btn-facebook-logout').off('click').on('click', function(e){
            e.preventDefault();
            FB_Logout();
        });
    });
</script>