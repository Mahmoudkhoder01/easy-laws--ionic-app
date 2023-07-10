import { Component, Injector, ViewChild, ElementRef } from '@angular/core';
import { Base } from '../../app.base';
import { NotificationsPopover } from '../../components/notification-popover/notifications-popover'

import { IonicPage, Content } from 'ionic-angular';

@IonicPage()
@Component({
    selector: 'page-notifications',
    templateUrl: 'notifications.html'
})
export class NotificationsPage extends Base {

	__loading: boolean = false;
	items: any = [];

    constructor(injector: Injector ){
        super(injector)
        this.load()
        this.events.subscribe('notifications:reload', data => this.load())
    }
  
    ionViewWillLeave() {}
  
    ionViewDidEnter() {}

    load(){
    	this.__loading = true;
    	this.api.post('get_notifications').subscribe(data => {
    		this.__loading = false;
    		this.items = data.results
    	})
    }

    delete(item){
        this.translate.get('ARE_YOU_SURE').subscribe(v =>{
            this.confirm(v).then(() => {
            	this.items.splice( this.items.indexOf(item), 1);
                this.api.post('delete_notifications', {ids: item.ID}).subscribe(data => {
                	this.events.publish('notifications:action');
                })
            }, () => {});
        });
    }

    presentPopover(myEvent) {
    	let popover = this.popoverCtrl.create(NotificationsPopover);
    	popover.present({
      		ev: myEvent
    	});
  	}

    click_action(item){
    	item.is_read = 1;
    	this.api.post('mark_notifications', {as: 'read', ids: item.ID}).subscribe(data => {
    		this.events.publish('notifications:action');
    	});
    	
    	switch(item.action){
    		case 'question':
    			this.goto('QuestionPage', {question_id: item.action_id});
    		break;
    		case 'subject':
    			this.goto('QuestionsPage', {catID: item.action_id});
    		break;
    		case 'subject_list':
    			this.goto('SubjectsPage', {ID: item.action_id});
    		break;
    		case 'search':
    			this.goto('QuestionsPage', {s: item.action_id});
    		break;
    		case 'inbox':
    			// this.goto('InboxPage', {message_id: item.action_id});
    		break;
    		case 'notification':
    			// this.goto('NotificationsPage', {message_id: item.action_id});
			break;
			case 'app_store':
				this.open_app_store();
			break;
			case 'rate_app':
				this.rate_app(true);
			break;
			case 'link':
				this.open_system_url(item.action_id);
			break;
    	}
    }
  
    
}
