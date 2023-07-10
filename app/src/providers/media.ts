import { Injectable } from '@angular/core';
import { Platform } from 'ionic-angular';
import { Media, MediaObject } from '@ionic-native/media';
import { File } from '@ionic-native/file';

@Injectable()
export class AppMedia {
	mediaObject: MediaObject;
	file_path: string;
    file_media: string;
	file_name: string = 'record.m4a';
	file_found: boolean = false;
    duration: number = 0;
    duration_interval: any;
    is_recording: boolean = false;

    constructor(
        private platform: Platform,
        private media: Media,
        private file: File
    ) {
        if(this.platform.is('cordova')){
            this.platform.ready().then(() => {
                if(this.platform.is('ios')){
                    this.file_path = this.file.dataDirectory;
        	        this.file_media = this.file_path.replace(/^file:\/\//, '')+this.file_name;
                } else {
                    this.file_path = this.file.externalRootDirectory;
                    this.file_media = this.file_path+this.file_name;
                }
                // this.create_file();
            });
        }
    }

    play(){
    	this.mediaObject.play();
    }

    stop(){
    	this.mediaObject.stop();
    }

    pause(){
    	this.mediaObject.pause();
    }

    seekTo(milliseconds){
        this.mediaObject.seekTo(milliseconds);
    }

    setVolume(value){
        // The value must be within the range of 0.0 to 1.0.
        this.mediaObject.setVolume(value);
    }

    getCurrentAmplitude(){
        return new Promise(resolve => {
            this.mediaObject.getCurrentAmplitude().then(data => resolve(data));
        });
    }

    getCurrentPosition(){
        return new Promise(resolve => {
            this.mediaObject.getCurrentPosition().then(data => resolve(data));
        });
    }

    fancyTime(time) {
        if(time <= 0) time = 0;
        let hrs = Math.floor(time / 3600);
        let mins = Math.floor((time % 3600) / 60);
        let secs = Math.floor(time % 60);

        let d_mins = (mins < 10) ? "0"+mins : mins;
        let d_secs = (secs < 10) ? "0"+secs : secs;

        // Output like "1:01" or "4:03:59" or "123:03:59"
        let ret = "";
        if (hrs > 0)  ret += "" + hrs + ":";
        ret += "" + d_mins + ":" + d_secs;
        return ret;
    }

    get __duration(){
        return this.duration;
    }

    create_file(){
        return new Promise((resolve, reject) => {
            this.platform.ready().then(() => {
                this.file.createFile(this.file_path, this.file_name, true).then(() => {
                    this.mediaObject = this.media.create(this.file_media);
                    // console.log('File created: '+this.file_path+this.file_name);
                    resolve(this.mediaObject);
                }).catch(err => reject(err));
            });
        });
    }

    delete_file(){
        this.file.removeFile(this.file_path, this.file_name);
    }

    record(){
        this.duration = 0; // reinit
        this.mediaObject.startRecord();
        this.is_recording = true;
        this.set_dur();
    }

    set_dur(){
        if(!this.is_recording) return;
        this.duration_interval = setTimeout(() => {
            this.duration = this.duration + 100;
            this.set_dur(); // loop
        }, 100);
    }

    finish(): Promise<any>{
        this.is_recording = false;
        this.mediaObject.stopRecord();
        this.mediaObject.release();
    	return new Promise( (resolve, reject) => {
	      	this.file.readAsDataURL(this.file_path, this.file_name).then(data => {
                resolve(data);
	      	}).catch(err => reject(err));
      	});
    }
}
