import { NgModule } from '@angular/core';
import { IonicPageModule } from 'ionic-angular';
import { SharedModule } from '../../app/app.shared';
import { SignInPage } from './sign-in';

@NgModule({
    declarations: [SignInPage],
    imports: [
        IonicPageModule.forChild(SignInPage),
        SharedModule,
    ]
})
export class SignInPageModule {}
