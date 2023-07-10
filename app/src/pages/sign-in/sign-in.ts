import { Component, Injector } from '@angular/core';
import { Base } from '../../app.base';

import { IonicPage } from 'ionic-angular';
@IonicPage()
@Component({
    selector: 'page-sign-in',
    templateUrl: 'sign-in.html'
})
export class SignInPage extends Base {
    trans: any;
    trans_keys = ['ARE_YOU_SURE'];

    item: any = {
        email: '',
        password: '',
        forgot_email: ''
    };
    disabled: boolean = true;
    forgot_disabled: boolean = true;

    sect: string = 'login';

    constructor(injector: Injector) {
        super(injector);
        this.translate.get(this.trans_keys).subscribe(trans => {
            this.trans = trans;
        });
    }

    forgot(e = true){
        this.item.forgot_email = this.item.email;
        this.check_forgot();
        this.sect = e ? 'forgot' : 'login';
    }

    check_forgot(){
        if(this.is_email(this.item.forgot_email)){
            this.forgot_disabled = false;
        } else {
            this.forgot_disabled = true;
        }
        return !this.forgot_disabled;
    }

    do_forgot(){
        if(this.check_forgot()){
            this.user.forgot(this.item.forgot_email).then(data => {
                this.toast('We have sent you an email');
                this.sect = 'login';
            }, err => this.toast(err));
        } else {
            this.toast('Invalid Email or Password');
        }
    }

    check(){
        if(this.is_email(this.item.email) && this.item.password.length > 5){
            this.disabled = false;
        } else {
            this.disabled = true;
        }
        return !this.disabled;
    }

    login_by_facebook(){
        this.user.doFbLogin().then(data => {
            this.viewCtrl.dismiss();
            // console.log(JSON.stringify(data))
        }, err => console.log(JSON.stringify(err)));
    }

    login_by_google(){
        this.user.doGoogleLogin().then(data => {
            this.viewCtrl.dismiss();
            // console.log(JSON.stringify(data))
        }, err => console.log(JSON.stringify(err)));
    }

    login(){
        if(this.check()){
            this.user.login(this.item).then(data => {
                this.viewCtrl.dismiss();
                // console.log(JSON.stringify(data))
            }, err => this.toast(err));
        } else {
            this.toast('Invalid Email or Password');
        }
    }

    signup(){
        this.modal('SignUpPage', {
            closeLogin: this.close.bind(this)
        })
    }

    close(){
        this.viewCtrl.dismiss();
    }

}
