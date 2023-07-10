import { Component, Input } from '@angular/core';
import { NavController, ModalController } from 'ionic-angular';

@Component({
  selector: 'login-view',
  template: `<div class="container">
			  <ion-icon name="sad"></ion-icon>
			  <p>{{ 'LOGIN_NOTE' | translate }}</p>
			  <p>
			  	<button ion-button outline color="white" (click)="modal('SignInPage')">
			  		{{'LOGIN' | translate}}
			  	</button>
			  	<button ion-button outline color="light" (click)="modal('SignUpPage')">
			  		{{'SIGNUP' | translate}}
			  	</button>
			  </p>
			</div>`
})
export class LoginView {

  	// @Input() text: string = '';

  	constructor(private navCtrl: NavController, private modalCtrl: ModalController) {}

  	goto(page: any, params: any = {}) {
        this.navCtrl.push(page, params);
    }

    modal(page: any, params: any = null, css: any = null) {
        this.modalCtrl.create(page, params, {cssClass: css}).present();
    }

}
