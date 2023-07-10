import { Component, Injector } from '@angular/core';
import { Base } from '../../app.base';

import { IonicPage } from 'ionic-angular';
@IonicPage()
@Component({
    selector: 'page-subjects',
    templateUrl: 'subjects.html'
})
export class SubjectsPage extends Base {

    trans: any;
    trans_keys = ['ARE_YOU_SURE'];

    item: any = null;
    ID: any = null;
    subjects: any;
    breadcrumb: any;
    __loading: boolean = false;

    constructor(injector: Injector) {
        super(injector);
        this.translate.get(this.trans_keys).subscribe(trans => {
            this.trans = trans;
        });

        this.item = this.navParams.get('item');
        this.ID = this.navParams.get('ID');

        this.load(() => {
            this.check_ID();
        });

    }

    ionViewDidLoad(){}

    load(callback?){
        if(this.item == null){
            this.breadcrumb = '';
            this.__loading = true;
            this.api.post('get_subjects').subscribe(data => {
                this.__loading = false;
                this.subjects = data.results;
                if(callback) callback();
            });
        } else {
            this.breadcrumb = this.item.data.title;
            this.subjects = this.item.children;
            if(callback) callback();
        }
    }

    check_ID(){
        if(this.ID){
            this.__loading = true;
            this.api.post('get_subjects', {ID: this.ID}).subscribe(data => {
                this.__loading = false;
                if(data.results && data.results[0]){
                    this.go_to(data.results[0]);
                }
            });
        }
    }

    go_to(item){
        if(item.children && item.children.length){
            this.goto("SubjectsPage", {item: item});
        } else {
            this.goto("QuestionsPage", {cat: item.data});
        }
    }

}
