import {ElementRef, Directive, OnInit} from '@angular/core';

@Directive({
    selector: '[autofocus]'
})

export class Autofocus implements OnInit {

    constructor(public element:ElementRef) {}

    ngOnInit():void {
        setTimeout(() => {
            this.element.nativeElement.focus();
        }, 500);
    }
}
