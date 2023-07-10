import { NgModule } from '@angular/core';
import { IonicPageModule } from 'ionic-angular';
import { SharedModule } from '../../app/app.shared';
import { ModalGalleryPage } from './modal-gallery';

@NgModule({
  declarations: [
    ModalGalleryPage,
  ],
  imports: [
    IonicPageModule.forChild(ModalGalleryPage),
    SharedModule,
  ],
})
export class ModalGalleryPageModule {}
