import { Component, Injector } from '@angular/core';
import { Base } from '../../app.base';

import { IonicPage } from 'ionic-angular';
@IonicPage()
@Component({
    selector: 'page-user',
    templateUrl: 'user.html'
})
export class UserPage extends Base {
    trans: any;
    trans_keys = ['SUCCESS'];

    item: any = null;
    sect: any = 'edit';

    disabled: boolean = true;
    disabled_pwd: boolean = true;
    disabled_email: boolean = true;

    password_new: string = '';
    password_new_again: string = '';

    email_new: string = '';
    email_new_password: string = '';

    constructor(injector: Injector) {
        super(injector);
        this.translate.get(this.trans_keys).subscribe(trans => {
            this.trans = trans;
        });

        this.item = this.user.user;
        this.email_new = this.item.email;

        this.events.subscribe('user:reload', (data) => {
            this.item = data;
            this.email_new = this.item.email;
        });
    }

    check(){
        if(this.item.name.length > 2 && this.item.phone.length > 5){
            this.disabled = false;
        } else {
            this.disabled = true;
        }
        return !this.disabled;
    }

    change(){
        if(this.check()){
            this.api.post('edit_profile', this.item).subscribe(data => {
                if(data.valid == 'YES'){
                    this.user.reload();
                    this.alert(this.trans.SUCCESS);
                } else {
                    this.toast(data.reason);
                }
            })
        }
    }

    check_pwd(){
        if(this.password_new.length > 5 && this.password_new_again == this.password_new){
            this.disabled_pwd = false;
        } else {
            this.disabled_pwd = true;
        }
        return !this.disabled_pwd;
    }

    change_pwd(){
        if(this.check_pwd()){
            this.api.post('change_password', {
                password: this.password_new
            }).subscribe(data => {
                if(data.valid == 'YES'){
                    this.user.reload();
                    this.alert(this.trans.SUCCESS);
                } else {
                    this.toast(data.reason);
                }
            })
        }
    }

    check_email(){
        if(this.is_email(this.email_new) && this.email_new_password.length > 5){
            this.disabled_email = false;
        } else {
            this.disabled_email = true;
        }
        return !this.disabled_email;
    }

    change_email(){
        if(this.check_email()){
            this.api.post('change_email', {
                email: this.email_new,
                old_password: this.email_new_password
            }).subscribe(data => {
                if(data.valid == 'YES'){
                    this.user.reload();
                    this.alert(this.trans.SUCCESS);
                } else {
                    this.toast(data.reason);
                }
            })
        }
    }

    on_logout(){
        this.translate.get('ARE_YOU_SURE').subscribe(v =>{
            this.confirm(v).then(() => {
                this.user.logout();
                // this.setRoot('TabsPage');
                this.setRoot('SignInForcePage');
            }, () => {});
        });
    }

    on_capture(){
        this.get_picture_lib( (data) => {
            if(data){
                let img = 'data:image/jpeg;base64,' + data;
                this.item.image = img;
                this.api.post('change_img', {
                    data: img,
                }).subscribe(data => {
                    console.log(JSON.stringify(data));
                    this.user.reload();
                });
            }
        });
    }

}
