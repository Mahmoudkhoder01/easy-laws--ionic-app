// Modules
import { BrowserModule } from '@angular/platform-browser';
import { HttpModule, JsonpModule, Http } from '@angular/http';
import { FormsModule }   from '@angular/forms';

import { PdfViewerComponent } from 'ng2-pdf-viewer';

// NATIVE
import { StatusBar } from '@ionic-native/status-bar';
import { SplashScreen } from '@ionic-native/splash-screen';
import { OneSignal } from '@ionic-native/onesignal';
import { InAppBrowser } from '@ionic-native/in-app-browser';

// import { IonicStorageModule } from '@ionic/storage';
import { Device } from '@ionic-native/device';
import { Camera } from '@ionic-native/camera';
// import { Keyboard } from '@ionic-native/keyboard';
import { Dialogs } from '@ionic-native/dialogs';
import { Clipboard } from '@ionic-native/clipboard';
import { Geolocation } from '@ionic-native/geolocation';
import { Network } from '@ionic-native/network';
import { SocialSharing } from '@ionic-native/social-sharing';

import { Media, MediaObject } from '@ionic-native/media';
import { File } from '@ionic-native/file';

import { Facebook } from '@ionic-native/facebook';
import { GooglePlus } from '@ionic-native/google-plus';

import { Badge } from '@ionic-native/badge';
import { ScreenOrientation } from '@ionic-native/screen-orientation';
import { AppRate } from '@ionic-native/app-rate';
import { Market } from '@ionic-native/market';
import { Deeplinks } from '@ionic-native/deeplinks';

// PROVIDERS
import { Api } from '../providers/api';
import { Storage } from '../providers/storage';
import { Settings } from '../providers/settings';
import { Products } from '../providers/products';
import { AppNetwork } from '../providers/network';
import { AppMedia } from '../providers/media';
import { User } from '../providers/user';

// PIPES
import { LetterAvatar } from "../pipes/letter-avatar.pipe";
import { MomentPipe } from '../pipes/moment.pipe';
import { OrderByPipe } from '../pipes/orderby.pipe';
import { HtmlPipe } from '../pipes/html.pipe';
import { KeysPipe } from '../pipes/keys.pipe';

// Directives
import { TouchEventsDirective } from '../directives/touch-events/touch-events';
import { Autosize } from '../directives/autosize/autosize';
import { Autofocus } from '../directives/autofocus/autofocus';
import { IonAffix } from '../directives/ion-affix/ion-affix';
// import { KeyboardAttachDirective } from '../directives/keyboard-attach/keyboard-attach';

// Components
import { AppAvatar } from '../components/app-avatar/app-avatar';
import { BottomAds } from '../components/bottom-ads/bottom-ads';
import { EmptyView } from '../components/empty-view/empty-view';
import { LoginView } from '../components/login-view/login-view';
import { AppLoader } from '../components/loader/loader';
import { PhotoTiltComponent } from '../components/photo-tilt/photo-tilt';
import { NotificationsPopover } from '../components/notification-popover/notifications-popover';


// TRANSLATE
// import { HttpClientModule, HttpClient } from '@angular/common/http';
import { TranslateModule, TranslateLoader } from '@ngx-translate/core';
import { TranslateHttpLoader } from '@ngx-translate/http-loader';
export function TranslateLoaderFactory(http: Http) {
    return new TranslateHttpLoader(http, './assets/i18n/', '.json');
}


export const Modules = [
    // IonicStorageModule.forRoot(),
    BrowserModule,
    HttpModule,
    JsonpModule,
    FormsModule,

    // HttpClientModule,
    TranslateModule.forRoot({
        loader: {
            provide: TranslateLoader,
            useFactory: (TranslateLoaderFactory),
            deps: [Http]
        }
    }),
]

export const Providers = [
    StatusBar,
    SplashScreen,
    OneSignal,
    InAppBrowser,
    Camera,
    Device,
    Dialogs,
    Clipboard,
    Geolocation,
    Network,
    SocialSharing,

    Media,
    File,

    Facebook,
    GooglePlus,

    Badge,
    ScreenOrientation,
    AppRate,
    Market,
    Deeplinks,

    Api,
    Storage,
    Settings,
    Products,
    AppNetwork,
    AppMedia,
    User,
]

export const Pipes = [
    LetterAvatar,
    MomentPipe,
    OrderByPipe,
    HtmlPipe,
    KeysPipe,
]

export const Components = [
    PdfViewerComponent,
    EmptyView,
    LoginView,
    AppAvatar,
    BottomAds,
    AppLoader,
    PhotoTiltComponent,
    NotificationsPopover,
]

export const Directives = [
    Autosize,
    Autofocus,
    IonAffix,
    TouchEventsDirective,
]
