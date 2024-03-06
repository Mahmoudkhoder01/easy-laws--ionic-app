import { Component, Injector } from "@angular/core";
import { Base } from "../../app.base";

import { IonicPage } from "ionic-angular";
@IonicPage()
@Component({
    selector: "page-favorites",
    templateUrl: "favorites.html",
})
export class FavoritesPage extends Base {
    items: any = [];
    page: number = 1;
    total: number;
    total_pages: number;
    segment: string = "subjects";
    type: number = 1;
    __loading: boolean = false;
    qImg = "assets/icons/question-new.svg";

    constructor(injector: Injector) {
        super(injector);
        if (!this.platform.is("cordova")) {
            this.qImg = "../" + this.qImg;
        }
        this.segment = this.navParams.get("segment") || "subjects";

        this.events.subscribe("user:login", (userData) => {
            this.segment_load();
        });
    }

    ionViewDidLoad() {
        this.segment_load();
    }

    segment_load() {
        this.items = [];
        this.type = this.segment === "questions" ? 0 : 1;
        this.load();
    }

    load(callback?) {
        if (this.user.is_loggedin) {
            // this.show_loader();
            this.__loading = true;
            this.api
                .post("get_likes", { type: this.type })
                .subscribe((data) => {
                    // this.hide_loader();
                    this.__loading = false;
                    if (data.valid == "YES") {
                        this.items = data.results;
                        this.page = data.page;
                        this.total = data.total;
                        this.total_pages = data.total_pages;
                    } else {
                        this.translate.get("ERROR_OCCURED").subscribe((v) => {
                            this.alert(v);
                        });
                    }
                    if (callback) callback();
                });
        }
    }

    onDelete(item) {
        this.items.splice(this.items.indexOf(item), 1);
        let ID = this.type === 1 ? item.subject_id : item.question_id;
        this.api
            .post("like_delete", { id: ID, type: this.type })
            .subscribe((data) => {
                console.log("deleted");
            });
    }

    onGo(item) {
        this.goto("QuestionPage", { question_id: item.question_id });
    }
    onSubject(item) {
        this.goto("QuestionsPage", {
            cat: {
                ID: item.subject_id,
                title: item.title,
                color: item.color,
                image: item.image,
            },
        });
    }

    onRefresh(event) {
        this.page = 1;
        this.load(() => event.complete());
    }

    onInfinite(event) {
        if (this.user.is_loggedin) {
            if (this.page < this.total_pages) {
                this.api
                    .post("get_likes", { type: this.type, page: this.page + 1 })
                    .subscribe((data) => {
                        for (let i = 0; i < data.results.length; i++) {
                            this.items.push(data.results[i]);
                        }
                        this.page = data.page;
                        this.total = data.total;
                        this.total_pages = data.total_pages;
                        event.complete();
                    });
            } else {
                event.complete();
            }
        }
    }
}
