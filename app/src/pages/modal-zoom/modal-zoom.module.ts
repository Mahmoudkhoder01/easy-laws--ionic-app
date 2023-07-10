import { NgModule } from '@angular/core';
import { IonicPageModule } from 'ionic-angular';
import { SharedModule } from '../../app/app.shared';
import { ModalZoomPage } from './modal-zoom';

@NgModule({
  declarations: [
    ModalZoomPage,
  ],
  imports: [
    IonicPageModule.forChild(ModalZoomPage),
    SharedModule,
  ],
})
export class ModalZoomPageModule {}
