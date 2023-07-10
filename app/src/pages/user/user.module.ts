import { NgModule } from '@angular/core';
import { IonicPageModule } from 'ionic-angular';
import { SharedModule } from '../../app/app.shared';
import { UserPage } from './user';

@NgModule({
    declarations: [UserPage],
    imports: [
        IonicPageModule.forChild(UserPage),
        SharedModule,
    ]
})
export class UserPageModule {}
