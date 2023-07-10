import { Component, Injector } from '@angular/core';
import { Base } from '../../app.base';

import { IonicPage } from 'ionic-angular';
@IonicPage()
@Component({
    selector: 'page-about',
    templateUrl: 'about.html'
})
export class AboutPage extends Base {
    constructor(injector: Injector) {
        super(injector);
    }
}
