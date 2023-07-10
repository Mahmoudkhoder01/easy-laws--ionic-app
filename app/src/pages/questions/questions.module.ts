import { NgModule } from '@angular/core';
import { IonicPageModule } from 'ionic-angular';
import { SharedModule } from '../../app/app.shared';
import { QuestionsPage } from './questions';

@NgModule({
    declarations: [QuestionsPage],
    imports: [
        IonicPageModule.forChild(QuestionsPage),
        SharedModule,
    ]
})
export class QuestionsPageModule {}
