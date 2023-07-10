import { Component, Injector } from '@angular/core';
import { Base } from '../../app.base';

import { IonicPage } from 'ionic-angular';
@IonicPage()
@Component({
    selector: 'page-sign-in-force',
    templateUrl: 'sign-in-force.html'
})
export class SignInForcePage extends Base {

    eula_url: string = 'https://easylaws.me/legal/eula.html';
    tabBarElement: any;

    constructor(injector: Injector) {
        super(injector);

        this.events.subscribe('user:login', (userData) => {
            this.setRoot('TabsPage');
        });

        if(this.platform.is('ios')){
            this.eula_url = 'https://easylaws.me/legal/eula.ios.html';
        } else if(this.platform.is('android')){
            this.eula_url = 'https://easylaws.me/legal/eula.android.html';
        }

        this.tabBarElement = document.querySelector('.tabs .tabbar');
        this.back_prevent();
    }

    back_prevent(){
        this.platform.registerBackButtonAction(() => {
            this.translate.get('ARE_YOU_SURE').subscribe(v =>{
                this.confirm(v).then(() => {
                    this.platform.exitApp();
                }, () => {});
            });
        });
    }

    ionViewWillEnter(){
        if(this.tabBarElement) this.tabBarElement.style.display = 'none';
    }

    ionViewWillLeave(){
        if(this.tabBarElement) this.tabBarElement.style.display = 'flex';
    }

}
