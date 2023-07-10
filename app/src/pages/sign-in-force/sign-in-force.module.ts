import { NgModule } from '@angular/core';
import { IonicPageModule } from 'ionic-angular';
import { SharedModule } from '../../app/app.shared';
import { SignInForcePage } from './sign-in-force';

@NgModule({
    declarations: [SignInForcePage],
    imports: [
        IonicPageModule.forChild(SignInForcePage),
        SharedModule,
    ]
})
export class SignInForcePageModule {}
