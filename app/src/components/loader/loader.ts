import { Component, Input } from '@angular/core';

@Component({
  selector: 'app-loader',
  template: `
    <div [class]="class">
        <ion-spinner [name]="name"></ion-spinner>
    </div>
  `
})
export class AppLoader {

  	@Input() name: string = 'crescent';
  	@Input() class: string = 'p text-center';

  	constructor() {}
}
