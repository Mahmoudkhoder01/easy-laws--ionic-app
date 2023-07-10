import { Component, Injector, ViewChild, ElementRef } from '@angular/core';
import { Base } from '../../app.base';
import { Slides } from 'ionic-angular';

import { IonicPage } from 'ionic-angular';
@IonicPage()
@Component({
    selector: 'page-intro',
    templateUrl: 'intro.html'
})
export class IntroPage extends Base {
    @ViewChild(Slides) slides: Slides;
    @ViewChild('header') header: ElementRef;

    trans: any;
    trans_keys = ['ARE_YOU_SURE', 'CHOOSE_LANG'];
    options: any = {};
    sect: string = '';
    item: any = [];
    subjects: any = [];
    liked_count: number = 0;
    liked_subjects: any = []; // fallback to not loggedin users

    header_height: any = 0;
    check_all: boolean = false;
    eula_url: string = 'https://easylaws.me/legal/eula.html';
    lang: string = 'ar';

    constructor(injector: Injector) {
        super(injector);
        this.options = this.settings.all;
        this.translate.get(this.trans_keys).subscribe(trans => {
            this.trans = trans;
        });

        this.events.subscribe('user:login', (userData) => {
            this.show_subjects();
        });

        if(this.platform.is('ios')){
            this.eula_url = 'https://easylaws.me/legal/eula.ios.html';
        } else if(this.platform.is('android')){
            this.eula_url = 'https://easylaws.me/legal/eula.android.html';
        }
    }

    change_lang(){
        this.events.publish('settings:change', this.options);
        this.settings.merge(this.options);
    }

    agree(){
        this.user.accept_eula();
        this.sect = 'login';
    }

    ionViewWillEnter(){
        this.user.eula ? this.sect = 'login' : this.sect = 'eula';
        if(this.user.is_loggedin){
            this.show_subjects();
        }
    }

    get_header_height(){
        this.header_height = this.header.nativeElement.offsetHeight+'px';
    }

    show_subjects(){
        this.sect = 'subjects';
        this.item = this.user.user;
        setTimeout(() => {
            this.get_header_height();
        }, 1);

        this.show_loader();
        this.api.post('get_firstrun_subjects_new').subscribe(data => {
            this.hide_loader();

            if(data.subjects){
                for(let i =0; i< data.subjects.length; i++){
                    data.subjects[i].bg = this.dark_color();
                }
                this.subjects = data.subjects;
                this.count_liked();
            }
        });
    }

    set_check_all(){
        let is_liked = this.check_all ? 1 : 0;
        for(let i = 0; i < this.subjects.length; i++){
            this.subjects[i].is_liked = is_liked;
        }
        this.count_liked();
    }

    continue(){
        let liked = [];
        for(let i = 0; i < this.subjects.length; i++){
            if(this.subjects[i].is_liked && parseInt(this.subjects[i].is_liked) == 1){
                liked.push(this.subjects[i].ID);
                this.liked_subjects.push(this.subjects[i]);
            }
        }
        if(!this.user.is_loggedin){
            this.storage.set('liked_subjects', this.liked_subjects);
            this.setRoot('TabsPage');
        } else {
            this.show_loader();
            this.api.post('set_firstrun_subjects_new', {
                subjects: liked.join(',')
            }).subscribe(data => {
                this.hide_loader();
                this.setRoot('TabsPage');
            });
        }
    }

    onSubject(subject){
        let liked = parseInt(subject.is_liked);
        subject.is_liked = liked === 0 ? 1 : 0;
        this.count_liked();
    }

    disabled(){
        return false;
        // return this.liked_count < 5;
    }

    count_liked(){
        let cnt = 0;
        for(let i = 0; i < this.subjects.length; i++){
            if(this.subjects[i].is_liked && parseInt(this.subjects[i].is_liked) == 1){
                cnt++;
            }
        }
        this.liked_count = cnt;
    }

    icon_check(liked){
        liked = parseInt(liked);
        return liked ? 'checkmark-circle' : 'add-circle-outline';
    }

    nextSlide(){ this.slides.slideNext(); }

}
