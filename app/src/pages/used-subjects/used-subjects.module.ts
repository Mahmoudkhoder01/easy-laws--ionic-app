import { NgModule } from '@angular/core';
import { IonicPageModule } from 'ionic-angular';
import { SharedModule } from '../../app/app.shared';
import { UsedSubjectsPage } from './used-subjects';

@NgModule({
    declarations: [UsedSubjectsPage],
    imports: [
        IonicPageModule.forChild(UsedSubjectsPage),
        SharedModule,
    ]
})
export class UsedSubjectsPageModule {}
