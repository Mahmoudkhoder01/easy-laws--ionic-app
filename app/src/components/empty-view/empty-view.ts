import { Component, Input } from '@angular/core';

@Component({
  selector: 'empty-view',
  template: `<div class="container {{class}}">
			  <ion-icon [name]="icon"></ion-icon>
			  <p>{{ text }}</p>
			</div>`
})
export class EmptyView {

  @Input() text: string = '';
  @Input() icon: string = 'alert';
  @Input() class: string = '';

  constructor() {}

}
