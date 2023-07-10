import { Component, Input } from '@angular/core';
import { Platform } from 'ionic-angular';
import { InAppBrowser } from '@ionic-native/in-app-browser';

@Component({
  selector: 'bottom-ads',
  template: `
    <div class="sponsor"></div>
    <div *ngIf="ads && ads.length == 1" class="bottom-ads">
        <img [src]="ads[0].image" tappable (click)="open_url(ads[0].link)">
    </div>
    <ion-slides *ngIf="ads && ads.length > 1" autoplay="10000" loop="true" class="bottom-ads" dir="ltr">  
        <ion-slide *ngFor="let ad of ads">
            <img [src]="ad.image" tappable (click)="open_url(ad.link)">
        </ion-slide>
    </ion-slides>`
})
export class BottomAds {

  	@Input() ads: any = [];

  	constructor(private platform: Platform, private browser: InAppBrowser) {}

  	open_url(url){
        // <any>window.open(url, '_system', 'location=yes');
        this.platform.ready().then(() => {
            this.browser.create(url, '_system');
        });
    }

}
