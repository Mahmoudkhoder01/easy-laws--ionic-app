import { Component, Injector } from '@angular/core';
import { Base } from '../../app.base';

import { IonicPage } from 'ionic-angular';
@IonicPage()
@Component({
    selector: 'page-device',
    templateUrl: 'device.html'
})
export class DevicePage extends Base {

    _device: any;

    constructor(injector: Injector) {
        super(injector);

        this.platform.ready().then(() => {
            if(this.platform.is('cordova')){
                this._device = this.device;
            } else {
                this._device = this.local_device;
            }
        });
    }

    copy(text){
        this.translate.get(['COPY', 'CANCEL']).subscribe(trans => {
            this.actionSheet.create({
                buttons: [{
                    text: trans.COPY,
                    handler: () => {
                        this.clipboard.copy(text)
                        this.toast('Copied to clipboard');
                    }
                },{ text: trans.CANCEL, role: 'cancel' }
                ]
            }).present();
        });
    }

}
