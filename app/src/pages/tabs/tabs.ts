import { Component } from '@angular/core';
import { IonicPage, Platform, Events } from 'ionic-angular';
import { TranslateService } from '@ngx-translate/core';

@IonicPage()
@Component({
    template: `
  	    <ion-tabs color="tabs">
            <ion-tab root="DashboardPage" [tabTitle]="trans.DASHBOARD" tabIcon="app-icon-home"></ion-tab>
            <ion-tab root="SubjectsPage" [tabTitle]="trans.SUBJECTS" tabIcon="app-icon-subjects"></ion-tab>
	        <ion-tab root="QuestionsPage" [tabTitle]="trans.SEARCH" tabIcon="app-icon-search"></ion-tab>
	        <ion-tab root="NotificationsPage" [tabTitle]="trans.NOTIFICATIONS" tabIcon="app-icon-notification" [tabBadge]="nCount"></ion-tab>
	        <ion-tab root="MorePage" [tabTitle]="trans.MORE" tabIcon="app-icon-more"></ion-tab>
	    </ion-tabs>
    `
})
export class TabsPage {
	trans: any = {
		'DASHBOARD': 'Dashboard',
		'SUBJECTS': 'Subjects',
		'SEARCH': 'Search',
		'MORE': 'More',
		'INBOX': 'Inbox',
		'NOTIFICATIONS': 'Notifications'
	};

	nCount: any = '';

	constructor(
		private translate: TranslateService, 
		private events: Events, 
		private platform: Platform
	){
		// setTimeout(()=>{ this.get_trans(); }, 1000);
		this.events.subscribe('settings:change', (lang) => {
			this.get_trans();
		});
		this.platform.ready().then(() => {
			this.get_trans();
		});
		this.events.subscribe('notifications:count', count => {
			this.nCount = count > 0 ? count : '';
		})
	}

	get_trans(){
		let trans = ['DASHBOARD', 'SUBJECTS', 'SEARCH', 'MORE', 'INBOX', 'NOTIFICATIONS'];
		this.translate.get(trans).subscribe(values => {
			this.trans = values;
		});
	}
}
