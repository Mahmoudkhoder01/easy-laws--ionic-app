import { Component, Injector } from '@angular/core';
import { Base } from '../../app.base';

import { IonicPage } from 'ionic-angular';
@IonicPage()
@Component({
    selector: 'page-comment',
    templateUrl: 'comment.html'
})
export class CommentPage extends Base {
    trans: any;
    trans_keys = ['ARE_YOU_SURE'];

    item: any = null;
    items: any;
    details: string = '';
    disabled: boolean = true;

    constructor(injector: Injector) {
        super(injector);
        this.translate.get(this.trans_keys).subscribe(trans => {
            this.trans = trans;
        });
        this.item = this.navParams.get('item');
    }

    check(){
        if(this.details.length > 3){
            this.disabled = false;
        } else {
            this.disabled = true;
        }
        return !this.disabled;
    }

    save(){
        if(this.check()){
            this.show_loader();
            this.api.post('comment', {
                details: this.details,
                question_id: this.item.ID
            }).subscribe(data => {
                this.hide_loader();
                if(data.valid == 'YES'){
                    this.translate.get('COMMENT_RECORDED').subscribe(v=>{
                        this.toast(v);
                        this.viewCtrl.dismiss('DID_COMMENT');
                    });
                } else {
                    this.alert(data.reason);
                }
            });
        }
    }

}
