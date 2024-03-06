import { Component, Injector } from "@angular/core";
import { Base } from "../../app.base";

import { IonicPage } from "ionic-angular";
@IonicPage()
@Component({
    selector: "page-record",
    templateUrl: "record.html",
})
export class RecordPage extends Base {
    text: string = "Record";
    dump: any = "";

    item: any = {
        details: "",
        wav: "",
    };
    disabled: boolean = true;

    trans: any;
    trans_keys = ["RECORD", "RECORDING", "SUCCESS", "THANK_YOU_RECORDING"];

    progress: number = 0;
    progress_interval: any;
    play_time: number = 0;
    track_icon: string = "play";
    is_playing: boolean = false;
    finished: boolean = false;
    is_recording: boolean = false;
    duration: string = "00:00";
    duration_sec: number = 0;
    can_record: boolean = true;

    constructor(injector: Injector) {
        super(injector);

        this.translate.get(this.trans_keys).subscribe((trans) => {
            this.trans = trans;
            this.text = this.trans.RECORD;
        });
    }

    ionViewWillLoad() {
        this.show_loader();
        this.api.post("allow_requests").subscribe((data) => {
            this.hide_loader();
            if (data.result) {
                this.can_record = true;
                console.log("you may");
            } else {
                this.can_record = false;
                console.log("NOOO");
            }
        });
    }

    ionViewDidLoad() {
        if (this.platform.is("cordova")) {
            this.media.create_file();
        }
        // this.authorize();
    }

    /*authorize(){
        this.diagnostic.isMicrophoneAuthorized().then((state) => {
            if(!state){
                this.diagnostic.requestMicrophoneAuthorization().then(() => {}).catch(err => {});
            }
        });
        if(this.platform.is('android')){
            this.diagnostic.getExternalStorageAuthorizationStatus().then((state) => {
                if(!state){
                    this.diagnostic.requestExternalStorageAuthorization().then(() => {
                        this.media.create_file();
                    }).catch(err => {});
                }
            });
        }
    }*/

    check() {
        if (this.item.details.length > 3 || this.item.wav.length) {
            this.disabled = false;
        } else {
            this.disabled = true;
        }
        return !this.disabled;
    }

    save() {
        if (this.check()) {
            this.show_loader();
            this.api
                .post("set_request_m4a", this.item, 240000)
                .subscribe((data) => {
                    this.hide_loader();
                    if (data.valid == "YES") {
                        this.alert(
                            this.trans.SUCCESS,
                            this.trans.THANK_YOU_RECORDING
                        );
                        this.item = { details: "", wav: "" };
                        this.finished = false;
                        this.reset();
                        this.check();
                    } else {
                        this.toast(data.reason);
                    }
                });
        }
    }

    play() {
        if (this.is_playing == true) {
            this.media.pause();
            this.is_playing = false;
            this.track_icon = "play";
            // clearInterval(this.progress_interval);
        } else {
            this.media.play();
            this.is_playing = true;
            this.track_icon = "pause";
            this.set_play_dur();
        }
    }

    set_play_dur() {
        if (!this.is_playing) return;
        setTimeout(() => {
            this.play_time += 100;
            this.progress = (this.play_time / this.duration_sec) * 100;
            if (this.play_time >= this.duration_sec) this.stop();
            this.set_play_dur(); // loop
        }, 100);
    }

    reset() {
        // clearInterval(this.progress_interval);
        this.progress = 0;
        this.track_icon = "play";
        this.is_playing = false;
        this.play_time = 0;
    }

    stop() {
        this.reset();
        this.media.stop();
    }

    onRecord(event) {
        if (this.is_recording) {
            this.stop_recording(event);
        } else {
            this.start_recording(event);
        }
    }

    start_recording(event) {
        this.finished = false;
        this.is_recording = true;
        this.text = this.trans.RECORDING + "...";
        this.media.record();
    }

    stop_recording(event) {
        this.text = this.trans.RECORD;
        this.is_recording = false;
        this.duration_sec = this.media.__duration;
        // console.log(`Duration: ${this.duration_sec}`);
        this.duration = this.media.fancyTime(this.duration_sec / 1000);

        this.media
            .finish()
            .then((data: any) => {
                // console.log(data); // base64 file
                this.item.wav = data;
                this.finished = true;
                this.check();
            })
            .catch((err) => (this.dump = JSON.stringify(err)));
    }

    rem() {
        this.item.wav = "";
        this.finished = false;
        this.is_playing = false;
        this.reset();
        this.check();
    }
}
