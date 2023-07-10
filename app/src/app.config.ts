export class AppConfig {
    // public static get API_URL() {return 'http://local.bitwize.com/__APPS/easylaws/site/api/';}
    // public static get SHARE_URL() {return 'http://local.bitwize.com/__APPS/easylaws/site/q/';}

    public static get API_URL() {return 'https://easylaws.me/api/';}
	public static get SHARE_URL() {return 'https://easylaws.me/q/';}

    public static get DEFAULT_LANG() {return 'ar';}
    public static get DEFAULT_COUNTRY() {return 'LB';}
    public static get DEFAULT_FONT_SIZE() {return '1';}
    public static get APP_NAME() {return 'Easy Laws';}
    public static get PACKAGE_NAME() {return 'me.easylaws.www';}
    public static get VERSION() {return '1.0.9';}

    public static get FB_APP_ID() {return '1363264657105483';}
    public static get FB_APP_NAME() {return 'Easy Laws';}

    public static get GOOGLE_CLIENT_ID() {
        return '282686342243-4ktjrnrg083hvestah1sebpkjbosck76';
        // https://console.firebase.google.com
        // https://console.developers.google.com/apis/credentials?project=easylaws-gplus
    }

    // public static get FCM_SENDER_ID(){return '1077085787624';}
    // public static get ONESIGNAL_APP_ID() {return '21df94be-bd39-4438-ad36-175cb0390739';}

    public static get FCM_SENDER_ID(){return '409595032957';}
    public static get ONESIGNAL_APP_ID() {return '077c162d-a824-4c8a-b8c0-c584d685c147';}

    public static get GA() {return 'UA-115444022-1';}

    public static get STORE_URLS(){
        return {
            ios: '1365602918',
            android: 'market://details?id=me.easylaws.www',
        }
    }
}
