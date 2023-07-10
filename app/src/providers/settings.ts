import { Injectable } from '@angular/core';
import { Storage } from './storage';
import { AppConfig } from '../app.config';


@Injectable()
export class Settings {
	_KEY: string = '_settings';
  	settings: any;
	_defaults: any;

    constructor(public storage: Storage) {
    	this._defaults = {
	        lang: AppConfig.DEFAULT_LANG,
	        country: 'LB',
          font_size: '1',
	    };
	    this.load();
    }

    load(){
    	let value = this.storage.get(this._KEY);
    	if (value){
    		this.settings = value;
        	this._mergeDefaults(this._defaults);
    	} else {
    		this.setAll(this._defaults);
    		this.settings = this._defaults;
    	}
    	return this.settings;
    }

    _mergeDefaults(defaults: any) {
    	for (let k in defaults) {
      		if (!(k in this.settings)) {
        		this.settings[k] = defaults[k];
      		}
    	}
    	return this.setAll(this.settings);
  	}

  	merge(settings: any) {
    	for (let k in settings) {
      		this.settings[k] = settings[k];
    	}
    	return this.setAll(this.settings);
  	}

  	set(key: string, value: any) {
    	this.settings[key] = value;
    	return this.storage.set(this._KEY, this.settings);
  	}

  	get(key: string){
  		return this.settings[key];
  	}

  	setAll(value: any) {
    	return this.storage.set(this._KEY, value);
  	}

  	get all() { return this.settings;}
}
