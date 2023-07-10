import { NgModule, ErrorHandler } from '@angular/core';
import { IonicApp, IonicModule, IonicErrorHandler, Config } from 'ionic-angular';

import { M44App } from './app.component';

import { SharedModule } from './app.shared';
import { Modules, Providers } from './app.imports';
import { IOSCustomTransition } from './app.transitions';
import { NotificationsPopover } from '../components/notification-popover/notifications-popover';

@NgModule({
  declarations: [
    M44App,
  ],
  imports: [
    Modules,
    IonicModule.forRoot(M44App, {
        preloadModules: true,
        mode: 'ios',
        pageTransition: 'ios-transition',
        swipeBackEnabled: false,
        backButtonText: '',
        spinner: 'crescent', // ios, ios-small, bubbles, circles, crescent, dots
        tabsHighlight: false,
        activator: 'ripple', // highlight
    }),
    SharedModule,
  ],
  bootstrap: [IonicApp],
  entryComponents: [
    M44App,
    NotificationsPopover
  ],
  providers: [Providers, { provide: ErrorHandler, useClass: IonicErrorHandler }]
})
export class AppModule {
  constructor(config: Config){
    config.setTransition('ios-transition', IOSCustomTransition);
  }
}
