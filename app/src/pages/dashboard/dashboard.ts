import { Component, Injector, ViewChild } from '@angular/core';
import { Base } from '../../app.base';

import { IonicPage, Slides } from 'ionic-angular';
@IonicPage()
@Component({
    selector: 'page-dashboard',
    templateUrl: 'dashboard.html'
})
export class DashboardPage extends Base {
    @ViewChild('slides') slides: Slides;

    trans: any;
    trans_keys = ['ARE_YOU_SURE', 'SEARCH', 'LOADING'];

    searchInput: any = '';
    show_search: boolean = false;

    data: any = [];
    did_you_know: any = [];

    __loading: boolean = false;

    ads: any = [];

    constructor(injector: Injector) {
        super(injector);
        this.translate.get(this.trans_keys).subscribe(trans => {
            this.trans = trans;
        });
    }

    ionViewWillEnter(){ this.load(); }
    // ionViewCanEnter(){ console.log('ionViewCanEnter'); }
    // ionViewWillLeave(){ console.log('ionViewWillLeave'); }
    // ionViewDidLoad(){ console.log('ionViewDidLoad'); }
    // ionViewWillLoad(){ console.log('ionViewWillLoad'); }

    load(callback?){
        this.__loading = true;
        // this.show_loader();
        this.api.post('get_dashboard').subscribe(data => {
            this.__loading = false;
            // this.hide_loader();
            // if(!this.user.is_loggedin){
                // data.liked_subjects = this.storage.get('liked_subjects');
            // }
            this.data = data;
            this.did_you_know = data.did_you_know;
            this.apply_bgs();
            if(callback) callback();
        });

        this.get_bottom_ads().then(data => this.ads = data).catch(err => console.log(err));

    }

    slide(bool = true){
        if(bool){
            this.slides.slideNext();
        } else {
            this.slides.slidePrev();
        }
    }

    onSubject(subject){
        this.goto('QuestionsPage', {cat: subject});
    }
    onQuestion(q){
        this.goto('QuestionPage', {question_id: q.ID});
    }

    apply_bgs(){
        if(this.data.subjects){
            for(let i =0; i< this.data.subjects.length; i++){
                this.data.subjects[i].bg = this.dark_color();
            }
        }
        if(this.data.used_subjects){
            for(let i =0; i< this.data.used_subjects.length; i++){
                this.data.used_subjects[i].bg = this.dark_color();
            }
        }
        if(this.data.liked_subjects){
            for(let i =0; i< this.data.liked_subjects.length; i++){
                this.data.liked_subjects[i].bg = this.dark_color();
            }
        }
        if(this.data.liked_questions){
            for(let i =0; i< this.data.liked_questions.length; i++){
                // this.data.liked_questions[i].bg = this.dark_color();
                var c = this.data.liked_questions[i].color;
                if(c == '#000' || c == '#000000'){
                    c = '#eeeeee';
                }
                this.data.liked_questions[i].bg =  c;
            }
        }
    }

    onRefresh(event){
        this.load(()=>{
            event.complete();
        })
    }

    onSearch(event){
        if(!this.searchInput.length) return;
        this.user.search_history = this.searchInput;
        this.show_search = false;
        setTimeout(() => {
            this.show_search = false;
        }, 200);
        this.goto('QuestionsPage', {s: this.searchInput});
    }

    search(txt){
        if(!txt.length) return;
        this.show_search = false;
        this.user.search_history = txt;
        this.goto('QuestionsPage', {s: txt});
    }

    onSearchInput(ev){
        setTimeout(() => this.show_search = true, 500);
    }

    onSearchCancel(event){
        this.searchInput = '';
        setTimeout(() => this.show_search = false, 200);
    }
}
