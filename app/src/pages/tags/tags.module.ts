import { NgModule } from '@angular/core';
import { IonicPageModule } from 'ionic-angular';
import { SharedModule } from '../../app/app.shared';
import { TagsPage } from './tags';

@NgModule({
    declarations: [TagsPage],
    imports: [
        IonicPageModule.forChild(TagsPage),
        SharedModule,
    ]
})
export class TagsPageModule {}
