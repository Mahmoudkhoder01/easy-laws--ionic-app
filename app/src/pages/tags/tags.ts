import { Component, Injector } from '@angular/core';
import { Base } from '../../app.base';

import { IonicPage } from 'ionic-angular';
@IonicPage()
@Component({
    selector: 'page-tags',
    templateUrl: 'tags.html'
})
export class TagsPage extends Base {
    trans: any;
    trans_keys = ['ARE_YOU_SURE'];

    items: any;

    constructor(injector: Injector) {
        super(injector);
        this.translate.get(this.trans_keys).subscribe(trans => {
            this.trans = trans;
        });
    }

    ionViewDidLoad(){
        this.load();
    }

    load(){
        this.show_loader();
        this.api.post('get_tags').subscribe(data => {
            this.items = data.results;
            this.hide_loader();
        });
    }

    go_to(item){
        this.goto("QuestionsPage", {tag: item});
    }

}
