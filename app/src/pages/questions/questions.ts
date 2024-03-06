import { Component, Injector, ViewChild } from '@angular/core';
import { Base } from '../../app.base';

import { IonicPage, Content } from 'ionic-angular';
@IonicPage()
@Component({
    selector: 'page-questions',
    templateUrl: 'questions.html'
})
export class QuestionsPage extends Base {

    @ViewChild(Content) content: Content;

    trans: any;
    trans_keys = ['ARE_YOU_SURE'];

    item: any = null;
    cat: any = null;
    catID: any = null;
    tag: any = null;
    items: any = [];
    subjects: any = [];
    page: number = 1;
    total: number;
    total_pages: number;
    isIOS: boolean = false;

    searchInput: string = '';
    show_search: boolean = false;
    show_total: boolean = false;

    like_icon: string = 'app-icon-favorite';

    __loading: boolean = false;

    constructor(injector: Injector) {
        super(injector);
        this.translate.get(this.trans_keys).subscribe(trans => {
            this.trans = trans;
        });

        this.isIOS = this.platform.is('ios');

        this.item = this.navParams.get('item');
        this.cat = this.navParams.get('cat');
        this.catID = this.navParams.get('catID');
        this.tag = this.navParams.get('tag');
        this.searchInput = this.navParams.get('s') || '';
        let message = this.navParams.get('message');
        if(message) this.toast(message);

        // console.log(JSON.stringify(this.cat));
    }

    ionViewDidLoad(){
        if(this.catID){
            this.api.post('get_subject_by_id',{id: this.catID}).subscribe(data => {
                this.cat = data.subject;
                this.load();
            });
        } else {
            this.load();
        }
    }

    scrollToTop() {
        this.content.scrollToTop();
    }

    reset(){
        this.cat = null;
        this.catID = null;
        this.tag = null;
        this.searchInput = '';
        this.page = 1;
        this.load();
    }

    load(show_loader = true, callback?){
        if(show_loader) this.__loading = true;
        this.api.post('get_questions',{
            cat: this.cat ? this.cat.ID : null,
            tag: this.tag ? this.tag.ID : null,
            s: this.searchInput,
        }).subscribe(data => {
            this.__loading = false;
            this.items = data.results;
            this.subjects = data.subjects;
            this.page = data.page;
            this.total = data.total;
            this.total_pages = data.total_pages;
            this.setLikeIcon(data.is_subject_liked);
            setTimeout(() => {
                if(this.searchInput.length){
                    this.show_total = true;
                }
            }, 200);
            if(callback) callback();
            // console.log(this.items);
        });
    }
    open(item){
        this.goto("QuestionPage", {item: item, all_items: this.items});
    }

    setLikeIcon(like){
        like = parseInt(like) || 0;
        if(like == 1){
            this.like_icon = 'app-icon-favorite-filled';
        } else {
            this.like_icon = 'app-icon-favorite';
        }
    }

    onLike(){
       if(this.user.is_loggedin){
           this.api.post('like', { subject_id: this.cat.ID, type: 1 }).subscribe(data => {
                if(data.valid == 'YES'){
                    this.setLikeIcon(data.results);
                }
            });
        } else {
            this.loginAlert();
        }
    }

    onSubject(item){
        if(item.children && item.children.length){
            console.log('Going to subjects')
            console.log(item)
            this.goto("SubjectsPage", {
                item: item,
                // ID: item.data.ID 
            });
        } else {
            this.goto("QuestionsPage", {
                cat: item.data,
            });
        }
    }

    onInfinite(event){
        // console.log(`page: ${this.page}, total_pages: ${this.total_pages}`);
        if(this.page < this.total_pages){
            this.api.post('get_questions', {
                cat: this.cat ? this.cat.ID : null,
                tag: this.tag ? this.tag.ID : null,
                s: this.searchInput,
                page: this.page+1
            }).subscribe(data => {
                for(let i=0; i < data.results.length; i++){
                    this.items.push(data.results[i]);
                }
                this.page = data.page;
                this.total = data.total;
                this.total_pages = data.total_pages;
                event.complete();
            });
        } else {
            event.complete();
        }
    }

    onRefresh(event){
        this.page = 1;
        this.load(false, () => event.complete());
        // event.complete();
    }

    onSearch(event?){
        if(!this.searchInput.length) return;
        this.user.search_history = this.searchInput;
        this.page = 1;
        this.scrollToTop();
        this.load();
        setTimeout(() => this.show_search = false, 200);
    }

    search(key){
        this.searchInput = key;
        this.onSearch();
    }

    onSearchInput(){
        setTimeout(() => this.show_search = true, 300);
    }

    onSearchCancel(event?){
        this.searchInput = '';
        setTimeout(() => this.show_search = false, 200);
    }

}
