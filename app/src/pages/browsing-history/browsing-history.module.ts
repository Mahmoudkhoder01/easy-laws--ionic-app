import { NgModule } from '@angular/core';
import { IonicPageModule } from 'ionic-angular';
import { SharedModule } from '../../app/app.shared';
import { BrowsingHistoryPage } from './browsing-history';

@NgModule({
    declarations: [BrowsingHistoryPage],
    imports: [
        IonicPageModule.forChild(BrowsingHistoryPage),
        SharedModule,
    ]
})
export class BrowsingHistoryPageModule {}
