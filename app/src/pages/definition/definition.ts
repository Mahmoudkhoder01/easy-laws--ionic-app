import { Component, Injector, ViewChild } from '@angular/core';
import { App, Content } from 'ionic-angular';
import { Base } from '../../app.base';

import { IonicPage } from 'ionic-angular';
@IonicPage()
@Component({
    selector: 'page-definition',
    templateUrl: 'definition.html'
})
export class DefinitionPage extends Base {
    @ViewChild('thegrid') thegrid;
    @ViewChild(Content) content: Content;

    sect: any = '';
    trans: any;
    trans_keys = ['ARE_YOU_SURE'];

    id: any = null;
    title: any = '';
    item: any;
    loaded: boolean = false;

    constructor(injector: Injector, private app: App) {
        super(injector);
        this.translate.get(this.trans_keys).subscribe(trans => {
            this.trans = trans;
        });

        this.id = this.navParams.get('id');
        this.title = this.navParams.get('title');
        this.load();
    }

    load(){
        this.api.post('get_definition', {id: this.id}).subscribe(data => {
            this.loaded = true;
            this.item = data.results;
            if(this.item.tags.length){
                this.item.tags.sort(function() { return 0.5 - Math.random() });
            }
        });
    }

    nav_by_tag(tag){
        this.viewCtrl.dismiss(tag);
    }

    do_sect(arg = ''){
        if(this.sect == arg){
            this.sect = '';
        } else {
            this.sect = arg;
        }
        let yOffset = this.thegrid.nativeElement.offsetTop;
        this.content.scrollTo(0, yOffset, 700);
    }

    close(){
        this.viewCtrl.dismiss();
    }

}
