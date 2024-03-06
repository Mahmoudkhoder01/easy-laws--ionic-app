import { Injectable } from '@angular/core';
import { Http, Headers, RequestOptions, URLSearchParams } from '@angular/http';
import { Observable } from 'rxjs/Observable';
// import 'rxjs/Rx';
import 'rxjs/add/operator/map';
import 'rxjs/add/operator/timeout';

import { AppConfig } from '../app.config';
import { Storage } from '../providers/storage';

@Injectable()
export class Api {

    API_URL: string = AppConfig.API_URL;
    timeout: number = 10000;

	constructor(private http: Http, private storage: Storage) {}

    get user_id() {
        let user = this.storage.get('user');
        if(user != null){
            if(user.ID){
                return user.ID;
            }
            return false;
        }
    }

    get_json(url: string): Observable<any>{
        let headers = new Headers({'Content-Type': 'application/json; charset=UTF-8'});
        let options = new RequestOptions({ headers: headers });

        return this.http.get(url, options).timeout(this.timeout).map(res => res.json()).publishReplay(1).refCount();
    }

  	get(action : string = '', req: any = {}): Observable<any> {
        req = req || {};
        req.action = action;
        req.__ref = '__fromapp';
        req.user_id = this.user_id;
        let params = Object.keys(req).map(function(k) {
            return encodeURIComponent(k) + '=' + encodeURIComponent(req[k])
        }).join('&');
        let url = this.API_URL + '?' + params;

        let headers = new Headers({'Content-Type': 'application/json; charset=UTF-8'});
        let options = new RequestOptions({ headers: headers });

        return this.http.get(url, options).timeout(this.timeout).map(res => res.json()).publishReplay(1).refCount();
    }

        post(action: string = '', req: any = {}, timeOut = this.timeout): Observable<any> {
        const body = new URLSearchParams();
        body.set('action', action);
        body.set('__ref', '__fromapp');
        Object.keys(req).forEach(key => {
            body.set(key, req[key]);
        });
        body.set('user_id', this.user_id );

        let headers = new Headers({'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'});
        let options = new RequestOptions({ headers: headers });
        return this.http.post(this.API_URL, body.toString(), options).timeout(timeOut).map(res => res.json());
    }

}
