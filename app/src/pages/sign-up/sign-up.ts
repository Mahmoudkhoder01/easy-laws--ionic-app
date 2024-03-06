import { Component, Injector } from '@angular/core';
import { Base } from '../../app.base';
import moment from 'moment';

import { IonicPage } from 'ionic-angular';
@IonicPage()
@Component({
    selector: 'page-sign-up',
    templateUrl: 'sign-up.html'
})

export class SignUpPage extends Base {
    trans: any;
    trans_keys = ['ARE_YOU_SURE'];

    item: any = {
        email: '',
        password: '',
        name: '',
        dob: '',
        phone: '',
        gender: ''
    };
    disabled: boolean = true;
    closeLogin: any;
    eula_url: string = 'https://easylaws.me/legal/eula.html';

    constructor(injector: Injector) {
        super(injector);
        this.translate.get(this.trans_keys).subscribe(trans => {
            this.trans = trans;
        });

        this.closeLogin = this.navParams.get('closeLogin');

        if(this.platform.is('ios')){
            this.eula_url = 'https://easylaws.me/legal/eula.ios.html';
        } else if(this.platform.is('android')){
            this.eula_url = 'https://easylaws.me/legal/eula.android.html';
        }
    }

    check(){
        if(this.is_email(this.item.email) && this.item.password.length > 5 && this.item.name.length > 2){
            this.disabled = false;
        } else {
            this.disabled = true;
        }
        return !this.disabled;
    }

    signup(){
        if(this.check()){
            if(this.item.dob){
                this.item.dob = moment(this.item.dob).format('YYYY-MM-DD');
            }
            this.user.signup(this.item).then(data => {
                this.alert('Success', `We have sent you an email including the activation link for your account. Activate your account then login. Kindly check your "Junk Folder", in case you didn't receive the email.`);
                this.close();
            }, err => this.toast(err));
        }
    }

    login_by_facebook(){
        this.user.doFbLogin().then(data => {
            this.close_all();
            // console.log(JSON.stringify(data))
        }, err => console.log(JSON.stringify(err)));
    }

    login_by_google(){
        this.user.doGoogleLogin().then(data => {
            this.close_all();
            // console.log(JSON.stringify(data))
        }, err => console.log(JSON.stringify(err)));
    }

    close_all(){
        if(typeof this.closeLogin == 'function') this.closeLogin();
        this.viewCtrl.dismiss();
    }

    close(){
        this.viewCtrl.dismiss();
    }

}
