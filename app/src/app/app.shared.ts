import { Components, Directives, Pipes } from './app.imports';
import { NgModule } from '@angular/core';
import { IonicModule } from 'ionic-angular';
import { TranslateModule } from '@ngx-translate/core';

@NgModule({
    declarations: [
        Pipes,
        Directives,
        Components
    ],
    imports: [
        IonicModule,
        TranslateModule.forChild()
    ],
    exports: [
        Pipes,
        Components,
        Directives,
        TranslateModule
    ]
})

export class SharedModule { }
