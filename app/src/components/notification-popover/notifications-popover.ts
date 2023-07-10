import { Component, Input } from '@angular/core';
import { Events } from 'ionic-angular';
import { Api } from '../../providers/api';

@Component({
  selector: 'notifications-popover',
  template: `
    <div>
    	<p class="m-sm p-sm b-b" style="background: #dcdde2; border-radius: 10px 10px 0 0;">
    		{{'MARK_ALL' | translate}}
    	</p>
    	<p tappable class="m-sm p-sm b-b" (click)="read()">{{'MARK_ALL_READ' | translate}}</p>
    	<p tappable class="m-sm p-sm" (click)="unread()">{{'MARK_ALL_UNREAD' | translate}}</p>
    </div>
  `
})
export class NotificationsPopover {

  	constructor(private events: Events, private api: Api) {}

  	read(){
  		this.api.post('mark_notifications', {as: 'read'}).subscribe(data => {
  			this.events.publish('notifications:action');
  			this.events.publish('notifications:reload');
  		})
  	}

  	unread(){
  		this.api.post('mark_notifications', {as: 'unread'}).subscribe(data => {
  			this.events.publish('notifications:action');
  			this.events.publish('notifications:reload');
  		})
  	}

}
