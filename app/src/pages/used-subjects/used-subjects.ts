import { Component, Injector } from '@angular/core';
import { Base } from '../../app.base';

import { IonicPage } from 'ionic-angular';
@IonicPage()
@Component({
    selector: 'page-used-subjects',
    templateUrl: 'used-subjects.html'
})
export class UsedSubjectsPage extends Base {

    items: any = [];
    page: number = 1;
    total: number;
    total_pages: number;
    segment: string = 'subjects';
    type: number = 1;

    constructor(injector: Injector) {
        super(injector);

        this.segment = this.navParams.get('segment') || 'subjects';

        this.events.subscribe('user:login', (userData) => {
            this.load();
        });
    }

    ionViewDidLoad(){
        this.load();
    }

    segment_load(){

    }

    load(callback?){
        if(this.user.is_loggedin){
            this.show_loader();
            this.api.post('get_used_subjects').subscribe(data => {
                this.hide_loader();
                if(data.valid == 'YES'){
                    this.items = data.results;
                } else {
                    this.translate.get('ERROR_OCCURED').subscribe(v => {
                        this.alert(v);
                    });
                }
                if(callback) callback();
            });
        }
    }

    onSearch(term){
        this.goto('QuestionsPage', {s: term});
    }
    onSubject(subject){
        this.goto("QuestionsPage", {
            cat: {ID: subject.subject_id, title: subject.title},
        });
    }

    onRefresh(event){
        this.page = 1;
        this.load(() => event.complete());
    }

    onInfinite(event){
        if(this.user.is_loggedin){
            if(this.page < this.total_pages){
                this.api.post('get_likes').subscribe(data => {
                    for(let i=0; i < data.results.length; i++){
                        this.items.push(data.results[i]);
                    }
                    this.page = data.page;
                    this.total = data.page;
                    this.total_pages = data.total_pages;
                    event.complete();
                });
            } else {
                event.complete();
            }
        }
    }

}
