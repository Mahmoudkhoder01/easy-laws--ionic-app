import { Directive, ElementRef, OnInit, OnDestroy, Input, Output, EventEmitter } from '@angular/core';
// import { Gesture } from 'ionic-angular';
import {Gesture} from 'ionic-angular/gestures/gesture';


@Directive({
  selector: '[touch-events]'
})
export class TouchEventsDirective implements OnInit, OnDestroy {
  el: HTMLElement;
  gestureListener: Gesture;

  // http://ryanmullins.github.io/angular-hammer/

  @Output() pinch = new EventEmitter();
  @Output() pinchstart = new EventEmitter();
  @Output() pinchend = new EventEmitter();

  @Output() onpan = new EventEmitter();
  @Output() panstart = new EventEmitter();
  @Output() panup = new EventEmitter();
  @Output() pandown = new EventEmitter();
  @Output() panend = new EventEmitter();
  @Output() pancancel = new EventEmitter();

  @Output() doubletap = new EventEmitter();

  @Output() press = new EventEmitter();
  @Output() pressup = new EventEmitter();

  constructor(el: ElementRef) {
    this.el = el.nativeElement;
  }

  ngOnInit() {
    this.gestureListener = new Gesture(this.el);
    this.gestureListener.listen();

    this.gestureListener.on('pinch', (event) => {
      this.pinch.emit(event);
    });
    this.gestureListener.on('pinchstart', (event) => {
      this.pinchstart.emit(event);
    });
    this.gestureListener.on('pinchend', (event) => {
      this.pinchend.emit(event);
    });
    this.gestureListener.on('pan', (event) => {
      this.onpan.emit(event);
    });
    this.gestureListener.on('panup', (event) => {
      this.panup.emit(event);
    });
    this.gestureListener.on('pandown', (event) => {
      this.pandown.emit(event);
    });
    this.gestureListener.on('panstart', (event) => {
      this.panstart.emit(event);
    });
    this.gestureListener.on('panend', (event) => {
      this.panend.emit(event);
    });
    this.gestureListener.on('pancancel', (event) => {
      this.pancancel.emit(event);
    });
    this.gestureListener.on('doubletap', (event) => {
      this.doubletap.emit(event);
    });

    this.gestureListener.on('press', (event) => {
      this.press.emit(event);
    });

    this.gestureListener.on('pressup', (event) => {
      this.pressup.emit(event);
    });

  }

  ngOnDestroy() {
    this.gestureListener.destroy();
  }
}
