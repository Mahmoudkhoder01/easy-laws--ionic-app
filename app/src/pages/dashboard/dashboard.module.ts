import { NgModule } from '@angular/core';
import { IonicPageModule } from 'ionic-angular';
import { SharedModule } from '../../app/app.shared';
import { DashboardPage } from './dashboard';

@NgModule({
    declarations: [DashboardPage],
    imports: [
        IonicPageModule.forChild(DashboardPage),
        SharedModule,
    ]
})
export class DashboardPageModule {}
