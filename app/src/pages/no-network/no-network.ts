import { Component, Injector } from '@angular/core';
import { Base } from '../../app.base';

import { IonicPage } from 'ionic-angular';
@IonicPage()
@Component({
    selector: 'page-no-network',
    templateUrl: 'no-network.html'
})
export class NoNetworkPage extends Base {
    options: any = {};

    constructor(injector: Injector) {
        super(injector);
        this.options = this.settings.all;
    }

    recheck(){
        if(this.network.connected){
            this.viewCtrl.dismiss();
        } else {
            this.translate.get('NO_NETWORK_DETECTED_STILL').subscribe(v => {
                this.alert(v);
            });
        }
    }

}
