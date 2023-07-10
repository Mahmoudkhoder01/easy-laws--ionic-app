import { NgModule } from '@angular/core';
import { IonicPageModule } from 'ionic-angular';
import { SharedModule } from '../../app/app.shared';
import { DevicePage } from './device';

@NgModule({
  declarations: [
    DevicePage,
  ],
  imports: [
    IonicPageModule.forChild(DevicePage),
    SharedModule,
  ]
})
export class DevicePageModule {}
