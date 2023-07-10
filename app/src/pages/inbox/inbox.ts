import { Component, Injector, ViewChild, ElementRef } from '@angular/core';
import { Base } from '../../app.base';

import { IonicPage, Content } from 'ionic-angular';

@IonicPage()
@Component({
    selector: 'page-inbox',
    templateUrl: 'inbox.html'
})
export class InboxPage extends Base {

    @ViewChild(Content) content: Content;
    @ViewChild('chat_input') messageInput: ElementRef;
    msgList: any = [];
    __user: any = null;
    toUser: any = null;
    editorMsg = '';

    constructor(injector: Injector ){
        super(injector)

        this.toUser = {
            id: this.navParams.get('toUserId'),
            name: this.navParams.get('toUserName')
        };
        this.__getUserInfo().then((res) => this.__user = res );
    }
  
    ionViewWillLeave() {
        this.events.unsubscribe('chat:received');
    }
  
    ionViewDidEnter() {
        this.getMsg();
        this.events.subscribe('chat:received', msg => {
            this.pushNewMsg(msg);
        })
    }
  
    onFocus() {
        this.content.resize();
        this.scrollToBottom();
    }
  
    getMsg() {
        // Get mock message list
        return this.__getMsgList()
        .subscribe(res => {
            this.msgList = res;
            this.scrollToBottom();
        });
    }
  

    sendMsg() {
        if (!this.editorMsg.trim()) return;
    
        // Mock message
        const id = Date.now().toString();
        let newMsg = {
            messageId: Date.now().toString(),
            userId: this.__user.id,
            userName: this.__user.name,
            userAvatar: this.__user.avatar,
            toUserId: this.toUser.id,
            time: Date.now(),
            message: this.editorMsg,
            status: 'pending'
        };
    
        this.pushNewMsg(newMsg);
        this.editorMsg = '';
    
        this.focus();
    
        this.__sendMsg(newMsg).then(() => {
            let index = this.getMsgIndexById(id);
            if (index !== -1) {
                this.msgList[index].status = 'success';
            }
        })
    }

    pushNewMsg(msg) {
        const userId = this.__user.id,
            toUserId = this.toUser.id;
        // Verify user relationships
        if (msg.userId === userId && msg.toUserId === toUserId) {
            this.msgList.push(msg);
        } else if (msg.toUserId === userId && msg.userId === toUserId) {
            this.msgList.push(msg);
        }
        this.scrollToBottom();
    }
  
    getMsgIndexById(id) {
      return this.msgList.findIndex(e => e.messageId === id)
    }
  
    scrollToBottom() {
        setTimeout(() => {
            if (this.content.scrollToBottom) {
                this.content.scrollToBottom();
            }
        }, 400)
    }
  
    private focus() {
        if (this.messageInput && this.messageInput.nativeElement) {
            this.messageInput.nativeElement.focus();
        }
    }
  
    private setTextareaScroll() {
        const textarea =this.messageInput.nativeElement;
        textarea.scrollTop = textarea.scrollHeight;
    }

    __mockNewMsg(msg) {
        const mockMsg = {
            messageId: Date.now().toString(),
            userId: '210000198410281948',
            userName: 'Hancock',
            userAvatar: './assets/mock/to-user.jpg',
            toUserId: '140000198202211138',
            time: Date.now(),
            message: msg.message,
            status: 'success'
        };
    
        setTimeout(() => {
            this.events.publish('chat:received', mockMsg, Date.now())
        }, Math.random() * 1800)
    }
    
    __getMsgList() {
        const msgListUrl = './assets/mock/msg-list.json';
        const data = this.api.get_json(msgListUrl);
        console.log(data);
        return data;
    }
    
    __sendMsg(msg) {
        return new Promise(resolve => setTimeout(() => resolve(msg), Math.random() * 1000))
        .then(() => this.__mockNewMsg(msg));
    }
    
    __getUserInfo() {
        const userInfo = {
            id: '140000198202211138',
            name: 'Luff',
            avatar: './assets/mock/user.jpg'
        };
        return new Promise(resolve => resolve(userInfo));
    }
}
