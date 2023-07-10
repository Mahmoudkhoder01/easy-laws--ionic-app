import { Component, Input } from '@angular/core';

@Component({
  selector: 'app-avatar',
  template: `
  	<letter-avatar avatar-data="{{text}}" *ngIf="!image"></letter-avatar>
    <img [src]="image" *ngIf="image" />
  `
})
export class AppAvatar {

  	@Input() text: string = '---';
  	@Input() image: string = '';

  	constructor() {}
}
