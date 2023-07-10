import { Injectable } from '@angular/core';

@Injectable()
export class Storage {
	storage: any = (<any>window).localStorage;

    constructor() {}

    set(item, value){
        let new_value = JSON.stringify(value);
        if(value && new_value.length<3){ // detect array (force to json object)
            new_value = JSON.stringify({value});
        }
        this.storage.setItem(item, new_value);
    }

    get(item){
    	if(this.storage.getItem(item) != null){
    		return JSON.parse(this.storage.getItem(item));
    	}
    	return null;
    }

    remove(item){
        this.storage.removeItem(item);
    }
}
