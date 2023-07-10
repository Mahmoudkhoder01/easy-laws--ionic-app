import { Injectable } from '@angular/core';
import { Platform, Events } from 'ionic-angular';
import { Facebook } from '@ionic-native/facebook';
import { GooglePlus } from '@ionic-native/google-plus';

import { AppConfig } from '../app.config';
import { Api } from './api';
import { Storage } from './storage';
import moment from 'moment';

@Injectable()
export class User {
    constructor(
        private platform: Platform,
        private events: Events,
    	private api: Api,
    	private storage: Storage,
    	private fb: Facebook,
    	private googlePlus: GooglePlus
    ) {}

    get user() {return this.storage.get('user') ? this.storage.get('user') : false;}
    get user_id() {
    	let user = this.storage.get('user');
    	if(user != null){
    		if(user.ID){
    			return user.ID;
    		}
    		return false;
    	}
    }

    get eula(){
        return this.storage.get('eula') ? true : false;
    }

    accept_eula(){
        this.storage.set('eula', 'yes');
    }

    get is_loggedin(){
    	return this.storage.get('user') != null;
    }

    reload(){
        this.api.post('get_user').subscribe(data => {
            if(data.valid == 'YES'){
                this.storage.set('user', data.results);
                this.events.publish('user:reload', data.results);
            }
        });
    }

    logout(){
        this.events.publish('user:logout', true);
    	this.storage.remove('user');
    }

    groupBy(xs, key) {
        return xs.reduce(function(rv, x) {
            (rv[x[key]] = rv[x[key]] || []).push(x);
            return rv;
        }, {});
    }

    set_browsing_history(args){
    	let key = 'browsing_history';
    	let value = this.storage.get(key) || [];
        let date = moment().locale('en-gb').format('YYYY-MM-DD');
        if(args.question_id && args.title){
            args.date = date;
            for(let i=0; i<value.length; i++){
                if(value[i].question_id == args.question_id){
                    value.splice(i, 1);
                }
            }
            value.unshift(args);
            setTimeout(() => {
                if(value.length > 50){
                    value.splice(50, value.length); // only allow 50 questions
                }
                this.storage.set(key, value);
            }, 100);

            if(this.user_id) this.api.post('set_browsing_history', args).subscribe(data => {});
        }
    }
    get browsing_history(){
    	let val = this.storage.get('browsing_history') || [];
        return this.groupBy(val, 'date');
    }

    get has_browsing_history(){
        return Object.keys(this.browsing_history).length > 0;
    }

    clear_browsing_history(){
        this.storage.remove('browsing_history');
    }

    set search_history(keyword){
        if(!keyword) return;
        keyword = keyword.toLowerCase()
    	let key = 'search_history';
    	let value = this.storage.get(key);
    	if(value == null) value = [];
    	let index = value.indexOf(keyword);
    	if(index !== -1){
    		value.splice(index, 1);
    		value.unshift(keyword);
    	} else {
    		value.unshift(keyword);
    	}
    	this.storage.set(key, value);
        if(this.user_id) this.api.post('set_search_history', {keyword: keyword}).subscribe(data => {});
    }
    get search_history(){
    	return this.storage.get('search_history') || [];
    }

    forgot(email = ''){
        return new Promise((resolve, reject) => {
            this.api.post('forgot', {email: email}).subscribe(data => {
                if(data.valid == 'YES'){
                    // this.storage.set('user', data.results);
                    resolve(data.results);
                } else {
                    reject(data.reason);
                }
            }, err => reject(err));
        });
    }

    signup(userObj){
        return new Promise((resolve, reject) => {
            this.api.post('signup', userObj).subscribe(data => {
                if(data.valid == 'YES'){
                    // this.storage.set('user', data.results);
                    resolve(data.results);
                } else {
                    reject(data.reason);
                }
            }, err => reject(err));
        });
    }

    login(userObj){
        return new Promise((resolve, reject) => {
            this.api.post('login', userObj).subscribe(data => {
                if(data.valid == 'YES'){
                    this.storage.set('user', data.results);
                    this.events.publish('user:login', data.results);
                    resolve(data.results);
                } else {
                    reject(data.reason);
                }
            }, err => reject(err));
        });
    }

    setup_FB(response){
    	return new Promise((resolve, reject) => {
	        let userId = response.authResponse.userID;
	        let pic = `https://graph.facebook.com/${userId}/picture?type=large`;
	        this.fb.api("/me?fields=name,email,gender,birthday", []).then((user) => {
	        	let userObj = {
	                name: user.name,
	                email: user.email,
	                gender: user.gender,
                    dob: user.birthday,
	                image: pic,
	                fb_id: userId,
	                provider: 'facebook'
	            };
                this.login(userObj).then(data => resolve(data), err => reject(err));
	        }).catch(err => reject(err));
	    });
    }

    doFbLogin(){
        return new Promise((resolve, reject) => {
            this.platform.ready().then(() => {
            	let permissions = ['public_profile', 'email'];
        	    this.fb.getLoginStatus().then((response) => {
            	    // console.log(JSON.stringify(response));
            	    if(response.status == 'connected'){
            	        this.setup_FB(response).then(data => resolve(data)).catch(err => reject(err));
            	    } else {
            	        this.fb.login(permissions).then((response) => {
            	            this.setup_FB(response).then(data => resolve(data)).catch(err => reject(err));
            	        }).catch(err => reject(err));
            	    }
        	    }).catch(err => reject(err));
            });
        });
    }

    setup_Google(response){
    	return new Promise((resolve, reject) => {
	        let userObj = {
	            name: response.displayName,
	            email: response.email,
	            gender: response.gender,
	            image: response.imageUrl,
	            google_id: response.userId,
	            provider: 'google'
	        };
	        this.login(userObj).then(data => resolve(data), err => reject(err));
	    });
    }

    doGoogleLogin(){
        return new Promise((resolve, reject) => {
            this.platform.ready().then(() => {
            	this.googlePlus.login({
        	        'webClientId': AppConfig.GOOGLE_CLIENT_ID,
        	        'offline': true,
                    'scopes': 'profile email',
        	    }).then((response) => {
                    console.log('GOOGLE'+JSON.stringify(response));
        	        this.setup_Google(response).then(data => resolve(data)).catch(err => reject(err));
        	    }).catch(err => {
                    console.log('GOOGLE ERR: '+JSON.stringify(err));
                    reject(err);
                });
            });
        });
    }
}
