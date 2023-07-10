import { Injectable } from '@angular/core';
import { Network } from '@ionic-native/network';
import { Subscription} from 'rxjs/Subscription';
import { Platform, Events } from 'ionic-angular';

declare var window: any;
declare var navigator: any;

@Injectable()
export class AppNetwork {

    online: boolean = true;
    type: string = '---';
    connected_sub: Subscription;
    disconnected_sub: Subscription;

    constructor(private platform: Platform, private network: Network, private events: Events) {
        this.detect();
    }

    get connected(){ return this.online; }
    get connection_type(){ return this.type; }

    get() {
        return {
            online: this.online,
            type: this.type
        }
    }

    get_core() {
        return this.network;
    }

    get_online(){
        return this.online;
    }

    is_online(){
        return this.online === true;
    }

    get_type() {
        return this.type;
    }

    status(callback?: any) {
        this.platform.ready().then(() => {
            if(this.platform.is('cordova')){
                this.type = this.network.type;
                if(this.type == "unknown" || this.type == "none" || this.type == undefined){
                    this.online = false;
                    this.events.publish('is:online', this.online);
                } else {
                    this.online = true;
                    this.events.publish('is:online', this.online);
                }
                if (callback) callback(this.get());
            } else {
                this.type = navigator.onLine ? 'wifi' : 'unknown';
                this.online = navigator.onLine;
                this.events.publish('is:online', this.online);
                if (callback) callback(this.get());
            }
        });
    }

    detect(callback?: any) {
        this.platform.ready().then(() => {
            if(this.platform.is('cordova')){
                this.type = this.network.type;
                if(this.type == "unknown" || this.type == "none" || this.type == undefined){
                    this.online = false;
                    this.events.publish('is:online', this.online);
                } else {
                    this.online = true;
                    this.events.publish('is:online', this.online);
                }
                if (callback) callback(this.get());

                // this.unsubscribe();

                this.network.onDisconnect().subscribe(() => {
                    this.online = false;
                    this.events.publish('is:online', this.online);
                    if (callback) callback(this.get());
                });

                this.network.onConnect().subscribe(() => {
                    this.online = true;
                    this.events.publish('is:online', this.online);
                    if (callback) callback(this.get());
                });
            } else {
                this.type = navigator.onLine ? 'wifi' : 'unknown';
                this.online = navigator.onLine;
                if (callback) callback(this.get());

                window.addEventListener("online", () => {
                    this.online = true;
                    this.events.publish('is:online', this.online);
                    this.type = 'wifi';
                    if (callback) callback(this.get());
                });

                window.addEventListener("offline", () => {
                    this.online = false;
                    this.events.publish('is:online', this.online);
                    this.type = 'unknown';
                    if (callback) callback(this.get());
                });
            }
        });
    }

    unsubscribe() {
        this.connected_sub.unsubscribe();
        this.disconnected_sub.unsubscribe();
    }
}
