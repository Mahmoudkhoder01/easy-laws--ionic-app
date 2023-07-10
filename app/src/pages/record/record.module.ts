import { NgModule } from '@angular/core';
import { IonicPageModule } from 'ionic-angular';
import { SharedModule } from '../../app/app.shared';
import { RecordPage } from './record';

@NgModule({
  declarations: [
    RecordPage,
  ],
  imports: [
    IonicPageModule.forChild(RecordPage),
    SharedModule,
  ]
})
export class RecordPageModule {}
