import { NgModule } from '@angular/core';
import { IonicPageModule } from 'ionic-angular';
import { SharedModule } from '../../app/app.shared';
import { ReferencePage } from './reference';

@NgModule({
    declarations: [ReferencePage],
    imports: [
        IonicPageModule.forChild(ReferencePage),
        SharedModule,
    ]
})
export class ReferencePageModule {}
