import { Component, Injector } from '@angular/core';
import { Base } from '../../app.base';

import { IonicPage } from 'ionic-angular';
@IonicPage()
@Component({
    selector: 'page-settings',
    templateUrl: 'settings.html'
})
export class SettingsPage extends Base {
    options: any = {};

    constructor(injector: Injector) {
        super(injector);
        this.options = this.settings.all;
    }

    save(){
        this.show_loader();
        this.events.publish('settings:change', this.options);
        this.settings.merge(this.options);
        this.hide_loader();
        this.translate.get('SETTINGS_SAVED').subscribe(v => {
            this.toast(v);
        });
    }

    go_to(page){
        this.goto(page);
    }

    on_logout(){
        this.translate.get(['ARE_YOU_SURE', 'LOGOUT_CANCELLED']).subscribe(v=>{
            this.confirm(v.ARE_YOU_SURE).then(() => {
                this.modal("LandingPage");
            }, () => {
                this.alert(v.LOGOUT_CANCELLED);
            });
        })

    }

}
