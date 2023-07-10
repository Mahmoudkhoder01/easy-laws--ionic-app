import { Component, Injector, Renderer2, ViewChild } from '@angular/core';
import { Base } from '../../app.base';

import { IonicPage } from 'ionic-angular';
@IonicPage()
@Component({
    selector: 'page-reference',
    templateUrl: 'reference.html'
})
export class ReferencePage extends Base {
    trans: any;
    trans_keys = ['ARE_YOU_SURE'];

    id: any = null;
    item: any;
    loaded: boolean = false;

    constructor(injector: Injector) {
        super(injector);
        this.translate.get(this.trans_keys).subscribe(trans => {
            this.trans = trans;
        });

        this.id = this.navParams.get('item');
        this.load();
    }

    load(){
        this.api.post('get_reference', {id: this.id.ID}).subscribe(data => {
            this.loaded = true;
            this.item = data.results;
        });
    }

    close(){
        this.viewCtrl.dismiss();
    }
}
