import { Component, Injector, Renderer2, ViewChild } from '@angular/core';
import { Content } from 'ionic-angular';
import { Base } from '../../app.base';

import { IonicPage } from 'ionic-angular';
@IonicPage()
@Component({
    selector: 'page-question',
    templateUrl: 'question.html'
})
export class QuestionPage extends Base {
    @ViewChild('details_target') details_target;
    @ViewChild('notes_target') notes_target;
    @ViewChild('thegrid') thegrid;
    @ViewChild(Content) content: Content;

    sect: string = '';

    trans: any;
    trans_keys = ['ARE_YOU_SURE', 'COULD_NOT_LOAD', 'ALREADY_VOTED', 'ALREADY_VOTED_COMMENT', 'LOGIN_REQUIRED', 'LOGIN_REQUIRED_TEXT', 'LOGIN', 'DISMISS'];

    like_icon: string = 'app-icon-favorite-green';
    comments: any = [];

    item: any = null;
    question_id: any = null;
    fetch_by_id: boolean = false;

    items: any;
    all_items: any;
    page: number = 1;
    total: number;
    total_pages: number;

    show_prev_next: boolean = false;
    has_next: boolean = false;
    has_prev: boolean = false;

    tags: any = [];
    all_images: any = [];

    __loading: boolean = false;
    plt: string = 'android';
    share_icon: string = 'md-share'

    constructor(injector: Injector, private renderer: Renderer2) {
        super(injector);
        this.translate.get(this.trans_keys).subscribe(trans => {
            this.trans = trans;
        });
        this.item = this.navParams.get('item');
        this.all_items = this.navParams.get('all_items');
        this.randomize_tags();
        this.collect_images();
        this.prev_next();
        this.question_id = this.navParams.get('question_id');

        let message = this.navParams.get('message');
        if(message) this.toast(message);

        if(this.platform.is('ios')){
            this.plt = 'ios';
            console.log('isIOS')
            this.share_icon = 'ios-share-outline'
        }

        if(this.item == null && this.question_id){
            this.fetch(this.question_id)
        } else {
            setTimeout(() => this.fetch_def(this.item.ID), 200)
        }
    }

    fetch_def(ID){
        this.api.post('get_question_by_id', {question_id: ID}).subscribe(data => {
            if(data.valid == 'YES'){
                this.item.details = data.results.details;
                this.item.notes = data.results.notes;
                setTimeout(() => this.load_definitions(), 1000);
            }
        });
    }

    fetch(ID){
        this.fetch_by_id = true;
        this.init_empty_item();
        this.__loading = true;
        
        this.api.post('get_question_by_id', {question_id: ID}).subscribe(data => {
            this.__loading = false;
            if(data.valid == 'YES'){
                this.item = data.results;
                this.randomize_tags();
                this.collect_images();
                setTimeout(() => this.load_assets(), 100);
                setTimeout(() => this.load_definitions(), 1000);
            } else {
                this.toast(this.trans.COULD_NOT_LOAD);
                this.viewCtrl.dismiss();
            }
        });
    }

    root_url(url){
        url = url.replace('http://', '').replace('https://', '');
        let parts = url.split('/');
        return parts[0];
    }

    prev_next(){
        if(this.all_items){
            let index = this.all_items.indexOf(this.item);
            if(index > -1){
                if(this.all_items.length > 1){
                    this.show_prev_next = true;
                }
                if(index === 0){
                    this.has_prev = false;
                } else {
                    this.has_prev = true;
                }
                if(index === (this.all_items.length -1)){
                    this.has_next = false;
                } else {
                    this.has_next = true;
                }
            }
        } else {
            this.show_prev_next = false;
        }
    }
    next(){
        if(!this.has_next) return;
        // console.log('NEXT');
        let index = this.all_items.indexOf(this.item);
        this.goto("QuestionPage", {item: this.all_items[index+1], all_items: this.all_items}, {animate: true, direction: 'forward', easing: 'easeOutExpo', duration: 500});
    }
    prev(){
        if(!this.has_prev) return;
        // console.log('PREV');
        let index = this.all_items.indexOf(this.item);
        this.goto("QuestionPage", {item: this.all_items[index-1], all_items: this.all_items}, {animate: true, direction: 'back', easing: 'easeOutExpo', duration: 500});
    }

    swipeNext(){
        this.platform.isRTL ? this.prev() : this.next();
    }

    swipePrev(){
        this.platform.isRTL ? this.next() : this.prev();
    }

    collect_images(){
        if(this.item && this.item.images && this.item.images.length){
            for(let i=0; i<this.item.images.length; i++){
                this.all_images.push(this.item.images[i].url);
            }
        }
    }
    randomize_tags(){
        if(this.item && this.item.tags && this.item.tags.length){
            this.tags = this.item.tags;
            this.tags.sort(function() { return 0.5 - Math.random() });
        }
    }

    init_empty_item(){
        this.item = {
            title: '', details: '', votes: 0, tags: [], notes: [], examples: [], links: [], references: []
        }
    }

    ionViewDidLoad(){
        if(!this.fetch_by_id) this.load_assets();
    }

    ngAfterViewInit() {
        if(!this.fetch_by_id) this.load_definitions();
    }

    load_assets(){
        // this.show_loader();
        this.api.post('get_question_assets', {question_id: this.item.ID}).subscribe(data => {
            // this.hide_loader();
            this.user.set_browsing_history({
                question_id: this.item.ID,
                title: this.item.title
            });
            if(data.valid == 'YES'){
                this.setLikeIcon(data.like);
                this.comments = data.comments;
                this.item.voted = data.voted;
                this.item.vote_direction = data.vote_direction;
            }
        });
    }

    load_definitions(){
        let els = this.details_target.nativeElement.querySelectorAll('.inner-link');
        for(let el of els){
            this.renderer.listen(el, 'click', (evt) => {
                // console.log(evt);
                // console.log(el);
                let text = evt.srcElement.innerHTML;
                let classes = evt.srcElement.className;
                let def_id = classes.replace('inner-link definition-', '');
                if(def_id){
                    this.open_def(def_id, text);
                }
            });
        }

        let n_els = this.notes_target.nativeElement.querySelectorAll('.inner-link');
        for(let el of n_els){
            this.renderer.listen(el, 'click', (evt) => {
                // console.log(evt);
                // console.log(el);
                let text = evt.srcElement.innerHTML;
                let classes = evt.srcElement.className;
                let def_id = classes.replace('inner-link definition-', '');
                if(def_id){
                    this.open_def(def_id, text);
                }
            });
        }
    }

    setLikeIcon(like){
        like = parseInt(like) || 0;
        if(like == 1){
            this.like_icon = 'app-icon-favorite-filled-green';
        } else {
            this.like_icon = 'app-icon-favorite-green';
        }
    }

    onShare(){
        this.share(this.item.title, this.appConfig.APP_NAME, null, this.appConfig.SHARE_URL+this.item.ID);
    }

    close(){
        this.viewCtrl.dismiss();
    }

    open_link(link){
        this.open_url(link);
    }

    nav_by_cat(cat){
        this.goto("SubjectsPage", {ID: cat.ID});
    }

    nav_by_tag(tag){
        this.goto("QuestionsPage", {tag: tag});
    }

    nav_by_reference(item){
        this.modal("ReferencePage", {item: item}, 'inset');
    }

    do_sect(arg = ''){
        if(this.user.is_loggedin){
            if(this.sect == arg){
                this.sect = '';
            } else {
                this.sect = arg;
            }
            this.scrollToGrid();
        } else {
            this.loginAlert();
        }
    }

    scrollToGrid() {
        let yOffset = this.thegrid.nativeElement.offsetTop;
        this.content.scrollTo(0, yOffset, 700)
    }

    open_def(id, title){
        if(this.user.is_loggedin){
            // this.modal("DefinitionPage", {id: id, title: title}, 'inset');
            let modal = this.modalCtrl.create("DefinitionPage", {id: id, title: title}, {cssClass: 'inset'});
            modal.onDidDismiss(data => {
                if(data){
                    this.goto("QuestionsPage", {tag: data});
                }
            });
            modal.present();
        } else {
            this.loginAlert();
        }
    }

    hasBack(){
        return this.navCtrl.canGoBack();
    }
    onBack(){
        this.navCtrl.pop();
    }

    onComment(){
        if(this.user.is_loggedin){
            let modal = this.__modal('CommentPage', {item: this.item}, 'inset');
            modal.onDidDismiss(data => {
                if(data == 'DID_COMMENT') this.load_assets();
            });
            modal.present();
        } else {
            this.loginAlert();
        }
    }

    onLike(){
       if(this.user.is_loggedin){
           this.api.post('like', { question_id: this.item.ID }).subscribe(data => {
                if(data.valid == 'YES'){
                    this.setLikeIcon(data.results);
                }
            });
        } else {
            this.loginAlert();
        }
    }

    vote_icon(dir, el){
        let pre = 'app-icon-';
        let name = dir === 'up' ? 'like' : 'dislike';
        if(el.vote_direction && el.vote_direction == dir){
            // return 'thumbs-'+dir;
            return pre + name + '-filled';
        } else {
            // return 'thumbs-'+dir+'-outline';
            return pre + name;
        }
    }

    onVote(dir){
        if(this.user.is_loggedin){
            this.api.post('vote', {
                question_id: this.item.ID,
                direction: dir,
            }).subscribe(data => {
                if(data.valid == 'YES'){
                    this.item.vote_direction = dir;
                    this.item.voted = true;
                    if(dir == 'up'){
                        this.item.votes += 1;
                    } else {
                        this.item.votes -= 1;
                    }
                } else {
                    this.alert(this.trans.ALREADY_VOTED);
                }
            });
        } else {
            this.loginAlert();
        }
    }

    onCommentVote(dir, comment){
        if(this.user.is_loggedin){
            this.api.post('comment_vote', {
                comment_id: comment.ID,
                direction: dir,
            }).subscribe(data => {
                if(data.valid == 'YES'){
                    comment.vote_direction = dir;
                    comment.voted = true;
                    if(dir == 'up'){
                        comment.votes += 1;
                    } else {
                        comment.votes -= 1;
                    }
                } else {
                    this.alert(this.trans.ALREADY_VOTED_COMMENT);
                }
            });
        } else {
            this.loginAlert();
        }
    }

    zoom(url){
        this.modal('ModalZoomPage', {url: url});
    }

}
