import { Component, Injector } from '@angular/core';
import { Base } from '../../app.base';

import { IonicPage } from 'ionic-angular';
@IonicPage()
@Component({
    selector: 'page-more',
    templateUrl: 'more.html'
})
export class MorePage extends Base {

    constructor(injector: Injector) {
        super(injector);
    }

    on_logout(){
        this.translate.get('ARE_YOU_SURE').subscribe(v =>{
            this.confirm(v).then(() => {
                this.user.logout();
            }, () => {});
        });
    }

}
