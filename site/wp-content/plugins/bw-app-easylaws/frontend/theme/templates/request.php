<div class="bg-white py-4 text-center">
	<h2>طرح سؤال</h2>
</div>

<?php 
$u = wapi()->user();
if(!$u):
    include(__DIR__.'/403.php');
else:
?>

<div class="bg-light py-5 text-center">
	<div class="container">
        <div class="mx-auto" style="max-width: 600px">
            <?php if(app_rq('_action') == 'sendrequest') :

                $r = wapi()->set_request(app_rq('_details'), app_rq('_wav'));
                if($r['valid'] == 'YES'){
                    echo '<div class="alert alert-success">نشكر تواصلكم معنا. سيتم مراسلتكم في أقرب وقت ممكن.</div>';
                } else {
                    echo '<div class="alert alert-danger">حدث خطا، يرجى المحاولة مره أخرى</div>';
                }
            else: ?>
            <form action="" method="POST">
                <input type="hidden" name="_action" value="sendrequest" />
                <h3 class="mb-3">اكتب طلبك هنا أو سجل صوتي باستخدام الزر أدناه</h3>
                <textarea class="form-control mb-3 shadow" name="_details" id="text_data" rows="3" placeholder="اكتب طلبك هنا"></textarea>
                <input type="hidden" id="wav_data" name="_wav" class="wav_data" />

                <div class="my-5">
                    <button class="btn btn-success py-3 px-5 mx-3" id="recordButton"><i class="fa fa-microphone" style="font-size: 48px;"></i></button>
                    <button class="btn btn-danger py-3 px-5 mx-3" id="stopButton" disabled><i class="fa fa-stop" style="font-size: 48px;"></i></button>
                </div>
                <div class="audio-wrap text-center my-5">
                    <audio id="audio" src="" controls></audio>
                </div>

                <button class="btn btn-outline-dark mb-3 px-5" id="btn" disabled>أرسل</button>
            </form>
            <?php endif; ?>

            <p class="text-muted mx-5">ملاحظة: سوف يصل طلبك إلى فريقنا مباشرةً. لن يتمّ الكشف عن محتواه علناً. سيتمٌ الإتصال بك خلال ال٤٨ ساعة عمل المقبلة.</p>
        </div>
    </div>
</div>

<script src="https://cdn.rawgit.com/mattdiamond/Recorderjs/08e7abd9/dist/recorder.js"></script>
<script>
jQuery(document).ready(function($){
    URL = window.URL || window.webkitURL;
    var gumStream, rec, input, AudioContext = window.AudioContext || window.webkitAudioContext, audioContext;

    $('.audio-wrap').hide();

    function __dis(el, dis){
        if(dis){ el.attr('disabled', 'disabled'); } else { el.removeAttr('disabled'); }
    }

    function _validate(){
        var valid = $('#text_data').val().length || $('#wav_data').val().length;
        var el = $('#btn');
        if(valid){
            el.removeAttr('disabled');
        } else {
            el.attr('disabled', 'disabled');
        }
    }

    $('#text_data').on('keyup', function(e){
        e.preventDefault();
        _validate();
    })

    $('#recordButton').on('click', function(e) {
        e.preventDefault();
        console.log("recordButton clicked");
        $('.audio-wrap').hide();
        var constraints = { audio: true, video:false }
        __dis($('#recordButton'), true);
        __dis($('#stopButton'), false);

        navigator.mediaDevices.getUserMedia(constraints).then(function(stream) {
            audioContext = new AudioContext();
            gumStream = stream;
            input = audioContext.createMediaStreamSource(stream);
            rec = new Recorder(input,{numChannels:1});
            rec.record()
        }).catch(function(err) {
            __dis($('#recordButton'), false);
            __dis($('#stopButton'), true);
        });
    });

    $('#stopButton').on('click', function(e) {
        e.preventDefault();
        console.log("stopButton clicked");
        __dis($('#recordButton'), false);
        __dis($('#stopButton'), true);
        // $('#pauseButton').html("Pause");
        rec.stop();
        gumStream.getAudioTracks()[0].stop();
        rec.exportWAV(createDownloadLink);
    });

    function blobToBase64(blob, callback) {
        var reader = new FileReader();
        reader.onload = function() {
            var dataUrl = reader.result;
            // var base64 = dataUrl.split(',')[1];
            callback(dataUrl);
        };
        reader.readAsDataURL(blob);
    }

    function createDownloadLink(blob) {
        var url = URL.createObjectURL(blob);
        var au = $('#audio')[0];
        au.src = url;
        au.controls = true;
        $('.audio-wrap').show();

        blobToBase64(blob, function(data){
            $('#wav_data').val(data);
            setTimeout(function(){
                _validate();
            }, 1);
        })
    }
});
</script>
<?php endif; ?>
