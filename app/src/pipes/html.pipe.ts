import { Pipe } from '@angular/core';
import { DomSanitizer } from '@angular/platform-browser';

@Pipe({
    name: 'html'
})
export class HtmlPipe {
    constructor(private sanitizer:DomSanitizer){}

    transform(html) {
        return this.sanitizer.bypassSecurityTrustHtml(html);
    }
}
