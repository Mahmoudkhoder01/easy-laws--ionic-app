import { Component, Injector, ViewChild } from '@angular/core';
import { IonicPage, Gesture, ViewController } from 'ionic-angular';
import { Base } from '../../app.base';

import { ScreenOrientation } from '@ionic-native/screen-orientation';


@IonicPage()
@Component({
  	selector: 'page-modal-zoom',
  	templateUrl: 'modal-zoom.html',
})
export class ModalZoomPage extends Base {

    @ViewChild('image') element;
    @ViewChild('imageParent') elementParent;

    image = null;
    container = null;
    transforms = [];
    adjustScale = 1;
    adjustDeltaX = 0;
    adjustDeltaY = 0;

    currentScale = null;
    currentDeltaX = null;
    currentDeltaY = null;

    public media: any;
    public src: any;
    public mediaType: string;
    private gesture: Gesture;
    public mediaLoaded:boolean = false;

    constructor(
        injector: Injector,
        private vc: ViewController,
        private screenOrientation: ScreenOrientation,
    ) {
        super(injector);
        this.src = this.navParams.get('url') || '';
    }

    setMediaLoaded =() =>{
        setTimeout(()=>this.mediaLoaded = true, 200);
    }

    ionViewDidLoad() {
        this.image = this.element.nativeElement;
        this.container = this.elementParent.nativeElement;
        // Prevent long press saving on mobiles.
        // this.container.addEventListener('touchstart', function(e) {
        //     e.preventDefault();
        // });
        this.init();
        this.screenOrientation.unlock();
    }


    init = () => {
        //create gesture obj w/ ref to DOM element
        this.gesture = new Gesture(this.element.nativeElement);

        //listen for the gesture
        this.gesture.listen();

        this.gesture.on('doubletap', (ev) => {
            this.transforms = [];
            this.adjustScale += 1;
            if (this.adjustScale >= 4) this.adjustScale = 1;
            this.transforms.push('scale(' + this.adjustScale + ')');
            this.container.style.transform = this.transforms.join(' ');
        });

        this.gesture.on("pinch", (ev) => {
            this.transforms = [];
            // Adjusting the current pinch/pan event properties using the previous ones set when they finished touching
            this.currentScale = this.adjustScale * ev.scale;
            this.currentDeltaX = this.adjustDeltaX + (ev.deltaX / this.currentScale);
            this.currentDeltaY = this.adjustDeltaY + (ev.deltaY / this.currentScale);
            // Concatinating and applying parameters.
            if (this.currentScale < 1) {
                this.currentScale = 1;
                this.currentDeltaX = 0;
                this.currentDeltaY = 0;
            }
            this.transforms.push('scale(' + this.currentScale + ')');
            this.transforms.push('translate(' + this.currentDeltaX + 'px,' + this.currentDeltaY + 'px)');
            this.container.style.transform = this.transforms.join(' ');

        });

        this.gesture.on("pinchend", (ev) => {
            // Saving the final transforms for adjustment next time the user interacts.
            this.adjustScale = this.currentScale;
            this.adjustDeltaX = this.currentDeltaX;
            this.adjustDeltaY = this.currentDeltaY;
        });

        this.gesture.on("panend", (ev) => {
            // Saving the final transforms for adjustment next time the user interacts.
            this.adjustScale = this.currentScale;
            this.adjustDeltaX = this.currentDeltaX;
            this.adjustDeltaY = this.currentDeltaY;
        });

        // this.gesture.on("pandown", (ev) => {
        //     this.close();
        // });
    }

    close(){
        this.screenOrientation.lock(this.screenOrientation.ORIENTATIONS.PORTRAIT);
        this.vc.dismiss();
    }

}
