import { NgModule } from '@angular/core';
import { IonicPageModule } from 'ionic-angular';
import { SharedModule } from '../../app/app.shared';
import { FavoritesPage } from './favorites';

@NgModule({
    declarations: [FavoritesPage],
    imports: [
        IonicPageModule.forChild(FavoritesPage),
        SharedModule,
    ]
})
export class FavoritesPageModule {}
