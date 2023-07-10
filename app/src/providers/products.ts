import { Injectable } from '@angular/core';
import { Api } from './api';
import { AppNetwork } from './network';


@Injectable()
export class Products {
    cache_name = 'products';
    items: any = [];

    constructor(public api: Api, public network: AppNetwork) {}

    load(force: boolean = false){
        if(!force){
            if(!this.is_empty(this.items) || this.cache_check()){
                return Promise.resolve( this.cache_load() );
            }
        }
        if(this.network.is_online()){
            return new Promise( resolve => {
                this.api.post('get_products').subscribe(data => {
                    this.items = data.results;
                    this.items = this.reset_hiden(this.items);
                    this.cache_set();
                    resolve(this.items);
                });
            });
        } else {
            return Promise.resolve( this.cache_load() );
        }
    }

    reset_hiden(items){
        return items.filter((item) => {
            item.is_hidden = 0;
            return item;
        });
    }

    cache_load(){
        if (!this.is_empty(this.items)) {
            return this.reset_hiden(this.items);
        }
        if( this.cache_check() ){
            this.items = this.reset_hiden( this.cache_get() );
            console.log(`${this.cache_name} FROM CACHE`);
            return this.items;
        }
    }

    cache_check(){
        return localStorage.getItem(this.cache_name) !== null;
    }

    cache_set(){
        localStorage.setItem(this.cache_name, JSON.stringify(this.items) );
    }

    cache_get(){
        return JSON.parse(localStorage.getItem(this.cache_name));
    }

    cache_clear(){
        this.items = [];
        localStorage.removeItem(this.cache_name);
    }

    is_empty(obj) {
        return (Object.keys(obj).length === 0);
    }

    query(params?: any) {
        if (!params) return this.items;

        return this.items.filter((item) => {
            for (let key in params) {
                let field = item[key];
                if (typeof field == 'string' && field.toLowerCase().indexOf(params[key].toLowerCase()) >= 0) {
                    item.is_hidden = 0;
                    return item;
                } else if (field == params[key]) {
                    item.is_hidden = 0;
                    return item;
                }
            }
            item.is_hidden = 1;
            return item;
        });

        /*return this.items.filter((item) => {
            for (let key in params) {
                let field = item[key];
                if (typeof field == 'string' && field.toLowerCase().indexOf(params[key].toLowerCase()) >= 0) {
                    return item;
                } else if (field == params[key]) {
                    return item;
                }
            }
            return null;
        });*/
    }

    add(item) {
        return new Promise( resolve => {
            this.api.post('add_product', item).subscribe(data => {
                this.items.push( data.results );
                resolve( data.results );
            });
        });
    }

    delete(item) {
        this.items.splice(this.items.indexOf(item), 1);
    }
}
