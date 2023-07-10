import { NgModule } from '@angular/core';
import { IonicPageModule } from 'ionic-angular';
import { SharedModule } from '../../app/app.shared';
import { SubjectsPage } from './subjects';

@NgModule({
    declarations: [SubjectsPage],
    imports: [
        IonicPageModule.forChild(SubjectsPage),
        SharedModule,
    ]
})
export class SubjectsPageModule {}
