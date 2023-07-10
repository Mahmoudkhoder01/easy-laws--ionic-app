import { NgModule } from '@angular/core';
import { IonicPageModule } from 'ionic-angular';
import { SharedModule } from '../../app/app.shared';
import { MorePage } from './more';

@NgModule({
    declarations: [MorePage],
    imports: [
        IonicPageModule.forChild(MorePage),
        SharedModule,
    ]
})
export class MorePageModule {}
