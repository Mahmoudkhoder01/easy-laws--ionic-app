import { Component, Injector } from '@angular/core';
import { Base } from '../../app.base';

import { IonicPage } from 'ionic-angular';
@IonicPage()
@Component({
    selector: 'page-browsing-history',
    templateUrl: 'browsing-history.html'
})
export class BrowsingHistoryPage extends Base {
    trans: any;
    trans_keys = ['ARE_YOU_SURE'];
    lang: any = 'en';

    constructor(injector: Injector) {
        super(injector);
        this.lang = this.settings.get('lang');
        this.translate.get(this.trans_keys).subscribe(trans => {
            this.trans = trans;
        });
    }

    onGo(question_id){
        this.goto('QuestionPage', {question_id: question_id});
    }

    onClear(){
        this.confirm(this.trans.ARE_YOU_SURE).then(() => {
            this.user.clear_browsing_history();
        }, () => {});
    }

}
