import { PipeTransform, Pipe } from '@angular/core';

/*
<span *ngFor="#entry of content | keys">
  Key: {{entry.key}}, value: {{entry.value}}
</span>
*/
@Pipe({name: 'keys'})
export class KeysPipe implements PipeTransform {
  transform(value, args:string[]) : any {
    let keys = [];
    for (let key in value) {
      keys.push({key: key, value: value[key]});
    }
    return keys;
  }
}
