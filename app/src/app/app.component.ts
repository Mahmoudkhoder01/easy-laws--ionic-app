import { Component, ViewChild } from '@angular/core';
import { Platform, Nav, App, Events, ToastController, ModalController, AlertController, NavController } from 'ionic-angular';
import { StatusBar } from '@ionic-native/status-bar';
import { SplashScreen } from '@ionic-native/splash-screen';

import { OneSignal } from '@ionic-native/onesignal';
import { Badge } from '@ionic-native/badge';

import { Device } from '@ionic-native/device';

import { TranslateService } from '@ngx-translate/core';
import { AppRate } from '@ionic-native/app-rate';

import { Market } from '@ionic-native/market';
import { InAppBrowser } from '@ionic-native/in-app-browser';

import { Deeplinks } from '@ionic-native/deeplinks';

import { AppConfig } from '../app.config';
import { Settings } from '../providers/settings';
import { Storage } from '../providers/storage';
import { Api } from '../providers/api';
import { User } from '../providers/user';

import moment from 'moment';
import 'moment/locale/ar';
import 'moment/locale/en-gb';
import 'moment/locale/fr';

@Component({
    template: '<ion-nav #rootNav [root]="rootPage" [class]="font_class"></ion-nav>'
})
export class M44App {
    @ViewChild('rootNav') nav: NavController;

    rootPage:string = "TabsPage";
    font_class: any = 'font_size_1';
    bgStateInterval: any;
    bgStateCounter: number = 0;
    bgStateTime: any;

    constructor(
        private platform: Platform,
        private statusBar: StatusBar,
        private oneSignal: OneSignal,
        private splashScreen: SplashScreen,
        private events: Events,
        private toastCtrl: ToastController,
        private modalCtrl: ModalController,
        private alertCtrl: AlertController,
        private translate: TranslateService,
        private settings: Settings,
        private storage: Storage,
        private device: Device,
        private api: Api,
        private user: User,
        private app: App,
        private badge: Badge,
        private appRate: AppRate,
        private market: Market,
        private browser: InAppBrowser,
        private deeplinks: Deeplinks
    ) {
        this.setRootPage();
        this.init();
        this.platform.ready().then(() => {
            if(this.platform.is('cordova')){
                // this.statusBar.styleDefault();
                this.statusBar.styleLightContent();
                this.statusBar.hide();
                this.splashScreen.hide();
                //Subscribe on background state
                this.platform.pause.subscribe(() => {
                    console.log('paused');
                    this.bgStateTime = moment(new Date());
                });
  
                //Subscribe on foreground state
                this.platform.resume.subscribe(() => {
                    console.log('resumed');
                    let time = moment(new Date());
                    let diff = moment.duration(time.diff(this.bgStateTime)).asSeconds();
                    console.log(time);
                    console.log(diff);
                    if(diff > 30){
                        this.events.publish('force_reload', true);
                    }
                });
                setTimeout(() => this.init_oneSignal(), 800);

                setTimeout(() => this.rate_app(false), 1000);

                this.deepLinks();
            }

            this.events.subscribe('force_reload', () => {
                console.log('force_reload');
                if(this.isUSER){
                    this.nav.setRoot('TabsPage');
                }
                setTimeout(() => this.init_oneSignal(), 300);
            })

            // setTimeout(() => this.gotoPage('SubjectsPage', { ID: '1000100' }), 800);
        });
    }

    deepLinks(){
        this.deeplinks.route({
            '/': {section: 'app'},
            '/q/:question_id': {section: 'question'}
        }).subscribe((match) => {
            // match.$route - the route we matched, which is the matched entry from the arguments to route()
            // match.$args - the args passed in the link
            // match.$link - the full link data
            if(match.$route.section){
                if(match.$route.section == 'question'){
                    this.gotoPage('QuestionPage', {
                        question_id: match.$args.question_id
                    });
                }
            }
            // alert(JSON.stringify(match));
            // console.log('Successfully matched route', match);
        }, (nomatch) => {
            // nomatch.$link - the full link data
            // console.error('Got a deeplink that didn\'t match', nomatch.$link);
        });
    }

    rate_app(immediate){
        this.translate.get([
            'APPRATE_TITLE', 'APPRATE_MESSAGE', 'APPRATE_CANCEL_BUTTON', 'APPRATE_LATER_BUTTON',
            'APPRATE_RATE_BUTTON', 'APPRATE_YES_BUTTON', 'APPRATE_NO_BUTTON', 'APPRATE_PROMPT_TITLE',
            'APPRATE_FEEDBACK_PROMPT_TITLE'
        ]).subscribe(v => {
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

    get isUSER(){
        return this.storage.get('user') != null
    }

    gotoPage(page: any, params: any = {}, options: any = {}) {
        // this.app.getRootNav().push('QuestionPage', { question_id: additionalData.ID });
        if(this.storage.get('user') != null){
            // this.nav.setRoot('TabsPage');
            setTimeout(() => {
                this.nav.push(page, params, options);
            }, 1);
        }
    }

    open_app_store(){
        let url = this.platform.is('ios') ? AppConfig.STORE_URLS.ios : AppConfig.STORE_URLS.android;
        this.market.open(url);
    }

    open_system_url(url){
        // <any>window.open(url, '_system', 'location=yes');
        this.platform.ready().then(() => {
            this.browser.create(url, '_system');
        });
    }

    setRootPage(){
        if(this.storage.get('introShown') == 'true1' && this.storage.get('eula') == 'yes' && this.isUSER){
            this.rootPage = 'TabsPage';
            // this.rootPage = 'IntroPage';
        } else {
            this.rootPage = 'IntroPage';
            this.storage.set('introShown', 'true1');
        }
    }

    init(){
        if(this.user.is_loggedin){
            this.init_notifications();
        }
        this.translate.setDefaultLang(AppConfig.DEFAULT_LANG);
        this.events.subscribe('settings:change', (settings) => {
            let lang = settings.lang || AppConfig.DEFAULT_LANG;
            this.translate.use(lang);

            let moment_lang = lang;
            if(lang == 'en') moment_lang = 'en-gb';
            moment.locale(moment_lang);

            if(lang == 'ar'){
                this.platform.setDir('rtl', true);
            } else {
                this.platform.setDir('ltr', true);
            }

            let font_size = settings.font_size || AppConfig.DEFAULT_FONT_SIZE;
            this.font_class = 'font_size_'+font_size;
        });

        let defaults = {
            lang: this.settings.get('lang') || AppConfig.DEFAULT_LANG,
            country: this.settings.get('country') || AppConfig.DEFAULT_COUNTRY,
            font_size: this.settings.get('font_size') || AppConfig.DEFAULT_FONT_SIZE,
        }
        this.events.publish('settings:change', defaults);

        this.events.subscribe('user:login', (data) => {
            this.translate.get('WELCOME').subscribe(v => {
                this.toast(`${v} ${data.name}`);
            });
            this.init_notifications();
            this.oneSignal.sendTags({user_id: data.ID});
            this.register_device();
        });

        this.events.subscribe('notifications:count', (data) => {
            setTimeout(() => {
                if(this.platform.is('cordova')){
                    this.badge.set(data);
                    // this.badge.increase(1);
                    // this.badge.clear();
                }
            }, 1000);
        })

        this.events.subscribe('notifications:action', (data) => {
            this.init_notifications();
        })

        let nnmodal = this.modalCtrl.create('NoNetworkPage');
        this.events.subscribe('is:online', online => {
            if(online === false){
                // nnmodal.present();
            } else {
                // nnmodal.dismiss();
            }
        });
    }

    init_notifications(){
        this.api.post('get_unread_notifications')
            .subscribe(data => this.events.publish('notifications:count', data.count) );
    }

    init_oneSignal(){

        this.oneSignal.startInit(AppConfig.ONESIGNAL_APP_ID);
        this.oneSignal.inFocusDisplaying(this.oneSignal.OSInFocusDisplayOption.Notification);
        this.oneSignal.handleNotificationOpened().subscribe((data) => {
            if (data.notification.payload.additionalData != null) {
                setTimeout(() => {
                    let additionalData = data.notification.payload.additionalData;
                    console.log('Section: '+additionalData.section);
                    if(additionalData._key){
                        this.api.post('mark_notifications', {as: 'read', _key: additionalData._key}).subscribe(data => {
                            this.events.publish('notifications:action');
                        });
                    }
                    if(additionalData.section == 'question'){
                        this.gotoPage('QuestionPage', {
                            question_id: additionalData.ID,
                            message: additionalData.message
                        });
                    }
                    if(additionalData.section == 'subject'){
                        this.gotoPage('QuestionsPage', {
                            catID: additionalData.ID,
                            message: additionalData.message
                        });
                    }
                    if(additionalData.section == 'subject_list'){
                        this.gotoPage('SubjectsPage', {
                            ID: additionalData.ID,
                            message: additionalData.message
                        });
                    }
                    if(additionalData.section == 'search'){
                        this.gotoPage('QuestionsPage', {
                            s: additionalData.term,
                            message: additionalData.message
                        });
                    }
                    if(additionalData.section == 'inbox' || additionalData.section == 'notification'){
                        this.gotoPage('NotificationsPage', {
                            message_id: additionalData.ID,
                            message: additionalData.message
                        });
                    }
                    if(additionalData.section == 'app_store'){
                        this.open_app_store()
                    }
                    if(additionalData.section == 'rate_app'){
                        this.rate_app(true)
                    }
                    if(additionalData.section == 'link'){
                        this.open_system_url(additionalData.ID);
                    }
                }, 300);
            }
        });
        this.oneSignal.endInit();

        setTimeout(() => {
            this.register_device();
        }, 5000);
    }

    register_device(){
        this.oneSignal.getPermissionSubscriptionState().then((status) => {
            console.log(`PLAYERID: ${status.subscriptionStatus.userId}`)
            /*
            status.permissionStatus.hasPrompted;
            status.permissionStatus.status;
            status.subscriptionStatus.subscribed;
            status.subscriptionStatus.userSubscriptionSetting;
            status.subscriptionStatus.pushToken;
            status.subscriptionStatus.userId; //playerID
            */
            this.api.post('record_device', {
                push_id: status.subscriptionStatus.pushToken,
                player_id: status.subscriptionStatus.userId,
                uuid: this.device.uuid,
                model: this.device.model,
                platform: this.device.platform,
                version: this.device.version,
                manufacturer: this.device.manufacturer,
                serial: this.device.serial
            }).subscribe(data => console.log(JSON.stringify(data)));
        });
    }

    toast(message){
        this.toastCtrl.create({ message: message, duration: 3000, position: 'top' }).present();
    }
}
