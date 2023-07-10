import { NgModule } from '@angular/core';
import { IonicPageModule } from 'ionic-angular';
import { SharedModule } from '../../app/app.shared';
import { NoNetworkPage } from './no-network';

@NgModule({
  declarations: [
    NoNetworkPage,
  ],
  imports: [
    IonicPageModule.forChild(NoNetworkPage),
    SharedModule,
  ]
})
export class NoNetworkPageModule {}
