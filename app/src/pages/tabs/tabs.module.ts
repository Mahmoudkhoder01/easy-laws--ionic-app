import { NgModule } from '@angular/core';
import { IonicPageModule } from 'ionic-angular';
// import { SharedModule } from '../../app/app.shared';
import { TabsPage } from './tabs';

@NgModule({
  declarations: [
    TabsPage,
  ],
  imports: [
    IonicPageModule.forChild(TabsPage),
    // SharedModule,
  ]
})
export class TabsPageModule {}
