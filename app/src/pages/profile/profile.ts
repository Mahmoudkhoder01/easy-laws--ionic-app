import { Component, Injector } from '@angular/core';
import { Base } from '../../app.base';

import { IonicPage } from 'ionic-angular';
@IonicPage()
@Component({
  selector: 'page-profile',
  templateUrl: 'profile.html'
})
export class ProfilePage extends Base {
  // Our local settings object
  item: any = JSON.parse( localStorage.getItem('user_object') );

  constructor(injector: Injector) {
      super(injector);
  }

  on_save(){
    this.show_loader();
    this.api.post('edit_profile', this.item).subscribe(data => {
      this.hide_loader();
      if(data.valid == 'YES'){
        localStorage.setItem('user_object', JSON.stringify(this.item) );
        this.toast('Settings Saved');
      } else {
        this.toast('Error Occured');
      }
    });
  }

  ionViewDidLoad() {}

}
