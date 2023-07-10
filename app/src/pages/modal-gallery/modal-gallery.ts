import { Component, Injector } from '@angular/core';
import { IonicPage } from 'ionic-angular';
import { Base } from '../../app.base';

import { ScreenOrientation } from '@ionic-native/screen-orientation';


@IonicPage()
@Component({
  	selector: 'page-modal-gallery',
  	templateUrl: 'modal-gallery.html',
})
export class ModalGalleryPage extends Base {

    title: any = '';
	videoSrc: any = '';
    pdfSrc: any = '';
    slides: any = '';
    initialSlide: any = 0;
    type: any = 'image';
    modalOpen: boolean = false;

  	constructor(
        injector: Injector,
        private screenOrientation: ScreenOrientation,
    ) {
        super(injector);
        this.title = this.navParams.get('title') || '...';
        this.videoSrc = this.navParams.get('videoSrc') || '';
        this.pdfSrc = this.navParams.get('pdfSrc') || '';
        this.slides = this.navParams.get('slides') || [];
        this.initialSlide = this.navParams.get('initialSlide') || 0;

        this.type = (this.videoSrc != '') ? 'video' : (this.pdfSrc != '') ? 'pdf' : 'image';
    }

    ionViewDidLoad(){
        this.screenOrientation.unlock();
    }

    zoom(url){
        let m = this.__modal('ModalZoomPage', {
            url: url
        });
        if(!this.modalOpen){
            m.present();
            this.modalOpen = true;
        } else {
            this.close();
        }
    }

    close(){
        this.screenOrientation.lock(this.screenOrientation.ORIENTATIONS.PORTRAIT);
        this.viewCtrl.dismiss();
        this.modalOpen = false;
    }

}
