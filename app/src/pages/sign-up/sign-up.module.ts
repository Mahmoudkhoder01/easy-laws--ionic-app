import { NgModule } from '@angular/core';
import { IonicPageModule } from 'ionic-angular';
import { SharedModule } from '../../app/app.shared';
import { SignUpPage } from './sign-up';

@NgModule({
    declarations: [SignUpPage],
    imports: [
        IonicPageModule.forChild(SignUpPage),
        SharedModule,
    ]
})
export class SignUpPageModule {}
