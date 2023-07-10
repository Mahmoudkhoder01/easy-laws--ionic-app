import { NgModule } from '@angular/core';
import { IonicPageModule } from 'ionic-angular';
import { SharedModule } from '../../app/app.shared';
import { QuestionPage } from './question';

@NgModule({
    declarations: [QuestionPage],
    imports: [
        IonicPageModule.forChild(QuestionPage),
        SharedModule,
    ]
})
export class QuestionPageModule {}
