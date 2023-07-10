import { Component, Injector } from '@angular/core';
import { Base } from '../../app.base';

import { IonicPage } from 'ionic-angular';
@IonicPage()
@Component({
    selector: 'page-contact',
    templateUrl: 'contact.html'
})
export class ContactPage extends Base {

    constructor(injector: Injector) {
        super(injector);
    }

}
