import { Injector, NgZone } from '@angular/core';
import 'rxjs/Rx';

// PROVIDERS
import { OneSignal } from '@ionic-native/onesignal';
import { Api } from './providers/api';
import { Storage } from './providers/storage';
import { Settings } from './providers/settings';
import { Products } from './providers/products';
import { AppNetwork } from './providers/network';
import { AppMedia } from './providers/media';
import { User } from './providers/user';

import { AppConfig } from './app.config';

import {Platform, Events, NavController, NavParams, LoadingController, ToastController, AlertController, MenuController, ActionSheetController, ModalController, PopoverController, ViewController } from 'ionic-angular';

// import { Storage } from '@ionic/storage';
import { Device } from '@ionic-native/device';
import { Camera } from '@ionic-native/camera';
import { Dialogs } from '@ionic-native/dialogs';
import { Clipboard } from '@ionic-native/clipboard';
import { Geolocation } from '@ionic-native/geolocation';
import { SocialSharing } from '@ionic-native/social-sharing';

import { TranslateService } from '@ngx-translate/core';
import { AppRate } from '@ionic-native/app-rate';
import { Market } from '@ionic-native/market';
import { InAppBrowser } from '@ionic-native/in-app-browser';

export abstract class Base {

    protected disconnectSubscription: any;
    protected connectSubscription: any;
    protected is_connected: boolean = true;

    protected local_device: any = {
        uuid: 'local',
        model: 'Sierra',
        platform: 'OSX',
        version: '10.3',
        manufacturer: 'Apple',
        serial: 'unknown'
    }

    protected zone: NgZone;
    // protected storage: Storage;
    protected navParams: NavParams;
    protected platform: Platform;

    protected oneSignal: OneSignal;
    protected browser: InAppBrowser;

    protected device: Device;
    protected camera: Camera;
    protected events: Events;
    protected actionSheet: ActionSheetController;
    protected menuController: MenuController;
    protected modalCtrl: ModalController;
    protected popoverCtrl: PopoverController;
    protected viewCtrl: ViewController;

    protected dialogs: Dialogs;
    protected clipboard: Clipboard;
    protected geolocation: Geolocation;
    protected socialSharing: SocialSharing;

    protected translate: TranslateService;

    protected appRate: AppRate;
    protected market: Market;

    protected appConfig = AppConfig;

    protected api: Api;
    protected storage: Storage;
    protected settings: Settings;
    protected products: Products;
    protected network: AppNetwork;
    protected media: AppMedia;
    protected user: User;

    // protected translate: TranslateService;

    protected loader: any;
    protected navCtrl: NavController;
    protected toastCtrl: ToastController;
    protected loadingCtrl: LoadingController;
    protected alertCtrl: AlertController;

    constructor(injector: Injector) {

        this.zone = injector.get(NgZone);
        // this.storage = injector.get(Storage);

        this.loadingCtrl = injector.get(LoadingController);
        this.toastCtrl = injector.get(ToastController);
        this.navCtrl = injector.get(NavController);
        this.alertCtrl = injector.get(AlertController);
        this.navParams = injector.get(NavParams);
        this.platform = injector.get(Platform);
        this.events = injector.get(Events);

        this.oneSignal = injector.get(OneSignal);
        this.browser = injector.get(InAppBrowser);

        this.device = injector.get(Device);
        this.camera = injector.get(Camera);
        this.actionSheet = injector.get(ActionSheetController);
        this.menuController = injector.get(MenuController);
        this.modalCtrl = injector.get(ModalController);
        this.popoverCtrl = injector.get(PopoverController);
        this.viewCtrl = injector.get(ViewController);
        this.dialogs = injector.get(Dialogs);
        this.clipboard = injector.get(Clipboard);
        this.geolocation = injector.get(Geolocation);
        this.socialSharing = injector.get(SocialSharing);

        this.translate = injector.get(TranslateService);
        this.appRate = injector.get(AppRate);
        this.market = injector.get(Market);

        this.api = injector.get(Api);
        this.storage = injector.get(Storage);
        this.settings = injector.get(Settings);
        this.products = injector.get(Products);
        this.network = injector.get(AppNetwork);
        this.media = injector.get(AppMedia);
        this.user = injector.get(User);

        this.__init();
    }

    __init(){}

    open_app_store(){
        let url = this.platform.is('ios') ? AppConfig.STORE_URLS.ios : AppConfig.STORE_URLS.android;
        this.market.open(url);
    }

    isiPhoneX(){
        if(this.platform.is('ios')){
            var ratio = window.devicePixelRatio || 1;
            var screen = {
                width : window.screen.width * ratio,
                height : window.screen.height * ratio
            };

            console.log(JSON.stringify(screen))

            return (screen.width == 1125 && screen.height === 2436);
        }
        return false;
    }

    get_bottom_ads(sect = 'screen', screen = 'dashboard', id = null){
        return new Promise( (resolve, reject) => {
            this.api.post('get_ads', {sect: sect, screen: screen, id: id}).subscribe(data => {
                if(data.valid == 'YES'){
                    resolve( data.results );
                } else {
                    reject( data.reason );
                }
            })
        });
    }

    rate_app(immediate = false){
        this.translate.get([
            'APPRATE_TITLE', 'APPRATE_MESSAGE', 'APPRATE_CANCEL_BUTTON', 'APPRATE_LATER_BUTTON',
            'APPRATE_RATE_BUTTON', 'APPRATE_YES_BUTTON', 'APPRATE_NO_BUTTON', 'APPRATE_PROMPT_TITLE',
            'APPRATE_FEEDBACK_PROMPT_TITLE'
        ]).subscribe(v =>{
            this.appRate.preferences = {
                displayAppName: 'EasyLaws',
                usesUntilPrompt: 3,
                promptAgainForEachNewVersion: true,
                inAppReview: false,
                storeAppURL: AppConfig.STORE_URLS,
                customLocale: {
                    title: v.APPRATE_TITLE,
                    message: v.APPRATE_MESSAGE,
                    cancelButtonLabel: v.APPRATE_CANCEL_BUTTON,
                    laterButtonLabel: v.APPRATE_LATER_BUTTON,
                    rateButtonLabel: v.APPRATE_RATE_BUTTON,
                    yesButtonLabel: v.APPRATE_YES_BUTTON,
                    noButtonLabel: v.APPRATE_NO_BUTTON,
                    appRatePromptTitle: v.APPRATE_PROMPT_TITLE,
                    feedbackPromptTitle: v.APPRATE_FEEDBACK_PROMPT_TITLE,
                },
                callbacks: {
                    handleNegativeFeedback: function(){
                        // window.open('mailto:feedback@example.com','_system');
                        console.log('rating cancelled')
                    },
                    onRateDialogShow: function(callback){
                        callback(1) // cause immediate click on 'Rate Now' button
                    },
                    onButtonClicked: function(buttonIndex){
                        console.log("onButtonClicked -> " + buttonIndex);
                    }
                }
            };
            this.appRate.promptForRating(immediate);
        });
    }

    send_push(content, to = 'all'){
        let obj:any = {
            contents: {en: content}
        }
        if(to == 'all'){
            this.oneSignal.getIds().then((ids) => {
                obj.include_player_ids = [ids.userId];
                this.__send_push(obj);
            })
            /*window["plugins"].OneSignal.getIds(function(ids) {
                obj.include_player_ids = [ids.userId];
                this.__send_push(obj);
            });*/
        } else {
            let _to = Array.isArray(to) ? to : [to];
            obj.include_player_ids = _to;
            this.__send_push(obj);
        }
    }

    __send_push(obj){
        this.oneSignal.postNotification(obj).then((data) => {
            console.log("Notification Post Success:"+ JSON.stringify(data));
        }, (err) => {
            console.log("Notification Post Failed: "+ JSON.stringify(err));
        })
        /*window["plugins"].OneSignal.postNotification(obj,
            function(successResponse) {
                console.log("Notification Post Success:"+ JSON.stringify(successResponse));
            },
            function (failedResponse) {
                console.log("Notification Post Failed: "+ JSON.stringify(failedResponse));
            }
        );*/
    }

    get font_class(){
        let font_size = this.settings.get('font_size') || AppConfig.DEFAULT_FONT_SIZE;
        return 'font_size_'+font_size;
    }

    is_email(email){
        let re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/.test(email);
        return re;
    }

    open_system_url(url){
        // <any>window.open(url, '_system', 'location=yes');
        this.platform.ready().then(() => {
            this.browser.create(url, '_system');
        });
    }

    open_url(url){
        this.platform.ready().then(() => {
            this.browser.create(url, '_blank', 'location=yes,zoom=no,toolbarposition=top,transitionstyle=fliphorizontal');
        });
    }

    share(message, subject, file, url = null){
        this.socialSharing.share(message, subject, file, url);
    }

    show_loader(text: string = '') {
        this.loader = this.loadingCtrl.create({
            content: text
        });
        this.loader.present();
    }

    hide_loader() {
        this.loader.dismiss();
    }

    toast(message: string) {
        this.toastCtrl.create({
            message: message,
            duration: 3000,
            position: 'top' // 'top, bottom, middle'
        }).present();
    }

    alert(title: string, subTitle: string = '') {
        this.translate.get(['DISMISS']).subscribe(v =>{
            this.alertCtrl.create({
                title: title,
                subTitle: subTitle,
                buttons: [{
                    text: v.DISMISS
                }]
            }).present();
        });
    }

    alert_(obj: any) {
        this.alertCtrl.create(obj).present();
    }

    loginAlert(){
        this.translate.get(['LOGIN_REQUIRED', 'LOGIN_REQUIRED_TEXT', 'LOGIN', 'DISMISS']).subscribe(v =>{
            this.alertCtrl.create({
                title: v.LOGIN_REQUIRED,
                subTitle: v.LOGIN_REQUIRED_TEXT,
                buttons: [
                    { text: v.LOGIN, handler: () => this.modal("SignInPage")},
                    {text: v.DISMISS }
                ]
            }).present();
        });
    }

    confirm(message: string, title: string = ''): Promise<boolean> {
        return new Promise((resolve, reject) => {
            this.translate.get(['OK', 'CANCEL']).subscribe(v =>{
                this.alertCtrl.create({
                    title: title,
                    message: message,
                    buttons: [
                        { text: v.CANCEL, handler: () => { reject(); } },
                        { text: v.OK, handler: () => { resolve(true); } }
                    ]
                }).present();
            });
        });
    }

    copy(text){
        this.translate.get(['COPY', 'COPIED', 'CANCEL']).subscribe(v => {
            this.actionSheet.create({
                buttons: [{
                    text: v.COPY,
                    handler: () => {
                        this.clipboard.copy(text)
                        this.toast(v.COPIED);
                    }
                },{ text: v.CANCEL, role: 'cancel' }
                ]
            }).present();
        });
    }

    light_color() {
        let letters = 'BCDEF'.split('');
        let color = '#';
        for (let i = 0; i < 6; i++ ) {
            color += letters[Math.floor(Math.random() * letters.length)];
        }
        return color;
    }
    dark_color(){
        let letters = '012345'.split('');
        let color = '#';
        color += letters[Math.round(Math.random() * 5)];
        letters = '0123456789ABCDEF'.split('');
        for (let i = 0; i < 5; i++) {
            color += letters[Math.round(Math.random() * 15)];
        }
        return color;
    }

    goto(page: any, params: any = {}, options: any = {}) {
        this.navCtrl.push(page, params, options);

        /*
        OPTIONS
        animate      boolean   Whether or not the transition should animate.
        animation    string    What kind of animation should be used.
        direction    string    forward, or back?
        duration     number    The length in milliseconds the animation should take.
        easing       string    The easing for the animation.
        The property 'animation' understands the following values: md-transition, ios-transition and wp-transition.*/
    }

    setRoot(page: any) {
        this.navCtrl.setRoot(page);
    }

    modal(page: any, params: any = null, css: any = null) {
        this.modalCtrl.create(page, params, {cssClass: css}).present();
    }

    __modal(page: any, params: any = null, css: any = null) {
        return this.modalCtrl.create(page, params, {cssClass: css});
    }
    
    get_picture(callback?:any){
        this.choose_image('camera', callback);
    }

    get_picture_lib(callback?:any) {
        this.translate.get(['CAMERA', 'PHOTO_LIBRARY', 'CANCEL']).subscribe(v =>{
            this.actionSheet.create({
                // title: 'Choose Option',
                buttons: [
                    { text: v.CAMERA, handler: () => { this.choose_image('camera', callback); } },
                    { text: v.PHOTO_LIBRARY, handler: () => { this.choose_image('library', callback); } },
                    { text: v.CANCEL, role: 'cancel'}
                ]
            }).present();
        });
    }

    choose_image(sourceType: string = 'camera', callback?:any) {
        var type;
        switch(sourceType){
            case 'library': type = this.camera.PictureSourceType.PHOTOLIBRARY; break;
            case 'camera': type = this.camera.PictureSourceType.CAMERA; break;
            default:  type = this.camera.PictureSourceType.CAMERA; break;
        }

        this.camera.getPicture({
            sourceType: type,
            destinationType: this.camera.DestinationType.DATA_URL,
            encodingType: this.camera.EncodingType.JPEG,
            targetWidth: 900,
            targetHeight: 900,
            quality: 80,
            saveToPhotoAlbum: true,
            correctOrientation: true
        }).then((imageData) => {
            if(callback) callback([imageData]);
        });
    }

    view_video(src, title=''){
        this.modal('ModalGalleryPage', {
            videoSrc: src,
            title: title
        });
    }

    view_pdf(src, title=''){
        this.modal('ModalGalleryPage', {
            pdfSrc: src,
            title: title
        });
    }

    view_gallery(imgs, initialSlide = 0, title=''){
        if(typeof imgs == 'string') imgs = imgs.split(',');

        this.modal('ModalGalleryPage', {
            slides: imgs,
            initialSlide: initialSlide,
            title: title
        });
    }

}
