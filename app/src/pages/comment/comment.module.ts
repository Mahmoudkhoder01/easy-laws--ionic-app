import { NgModule } from '@angular/core';
import { IonicPageModule } from 'ionic-angular';
import { SharedModule } from '../../app/app.shared';
import { CommentPage } from './comment';

@NgModule({
    declarations: [CommentPage],
    imports: [
        IonicPageModule.forChild(CommentPage),
        SharedModule,
    ]
})
export class CommentPageModule {}
