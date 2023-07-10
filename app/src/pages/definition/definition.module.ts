import { NgModule } from '@angular/core';
import { IonicPageModule } from 'ionic-angular';
import { SharedModule } from '../../app/app.shared';
import { DefinitionPage } from './definition';

@NgModule({
    declarations: [DefinitionPage],
    imports: [
        IonicPageModule.forChild(DefinitionPage),
        SharedModule,
    ]
})
export class DefinitionPageModule {}
