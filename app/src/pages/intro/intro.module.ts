import { NgModule } from '@angular/core';
import { IonicPageModule } from 'ionic-angular';
import { SharedModule } from '../../app/app.shared';
import { IntroPage } from './intro';

@NgModule({
    declarations: [IntroPage],
    imports: [
        IonicPageModule.forChild(IntroPage),
        SharedModule,
    ]
})
export class IntroPageModule {}
