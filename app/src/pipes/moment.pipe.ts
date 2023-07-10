import { Pipe } from '@angular/core';
import moment from 'moment';

@Pipe({name: 'moment'})
export class MomentPipe {
    transform(value, args, args2) {
    	let out = '';
        args = args || '';
        args2 = args2 || '';
        switch(args){
        	case 'ago': out = moment(value).fromNow(); break;
        	case 'toNow': out = moment(value).toNow(true); break;
            case 'relativeDay': out = this.relativeDay(value, args2); break;
        	default: out = moment(value).format(args);
        }
        return out;
    }

    relativeDay(value, args){
        var trans;
        args = args || '';
        switch(args){
            case 'ar': trans = {today: 'اليوم', yesterday: 'أمس', tomorrow: 'غدًا'}; break;
            case 'fr': trans = {today: 'Aujourd’hui', yesterday: 'Hier', tomorrow: 'Demain'}; break;
            default: trans = {today: 'Today', yesterday: 'Yesterday', tomorrow: 'Tomorrow'}; break;
        }

        let strDate = "";
        value = moment(value).toDate();

        let today = new Date(); today.setHours(0, 0, 0, 0);
        let yesterday = new Date(); yesterday.setHours(0, 0, 0, 0); yesterday.setDate(yesterday.getDate() - 1);
        let tomorrow = new Date(); tomorrow.setHours(0, 0, 0, 0); tomorrow.setDate(tomorrow.getDate() + 1);

        if (today.getTime() == value.getTime()) {
            strDate = trans.today;
        } else if (yesterday.getTime() == value.getTime()) {
            strDate = trans.yesterday;
        } else if (tomorrow.getTime() == value.getTime()) {
            strDate = trans.tomorrow;
        } else {
            strDate = moment(value).format('DD MMMM YYYY');
        }

        return strDate;
    }
}
