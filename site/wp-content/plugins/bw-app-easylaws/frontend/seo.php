<?php 
class APP_SEO {
    public $prx, $site_title, $site_desc, $url;

    public function __construct(){
        $this->prx = DB()->prefix.'app_';
        $this->url = "https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $this->site_title = get_bloginfo('title');
        $this->site_desc = get_bloginfo('description');
        add_filter('pre_get_document_title', [$this, 'wp_title']);
        add_action('wp_head', [$this, 'head']);
    }

    function id(){ return intval(get_query_var('ID')); }
    function p(){ return trim(strtolower(get_query_var('action'))); }

    function _var($table, $id, $var = 'title'){
        $t = $this->prx.$table;
        $id = intval($id);
        if($id && $table) return DB()->get_var("SELECT `{$var}` FROM {$t} WHERE ID={$id}");
        return '';
    }

    function title_template($title = ''){
        $d = $this->site_title . ' - ' . $this->site_desc;
        return $title ? $title.' | '.$d : $d;
    }

    function wp_title(){
        $id = $this->id();
        $o = '';
        switch($this->p()){
            case 'subject':
                if($id) $o = $this->_var('subjects', $id, 'title');
            break;
            case 'question':
                if($id) $o = $this->_var('questions', $id, 'title');
            break;
            case 'subjects': $o = 'المواضيع'; break;
            case 'request': $o = 'طرح سؤال'; break;
            case 'contact': $o = 'اتصلوا بنا'; break;
            case 'profile': $o = 'حسابي'; break;
            case 'favorites': $o = 'المفضلة'; break;
            case 'history': $o = 'تاريخ التصفح'; break;
            case 'search': $o = 'ابحث'; break;
        }
        return $this->title_template($o);
    }

    function desc(){
        $id = $this->id();
        $o = '';
        $default = 'Easy Laws is an App explaining the law in a simple “Question & Answer” format. Lebanese Laws apply on legal and non-legal society. We are your Friend In-Law.';
        switch($this->p()){
            case 'subject':
                if($id) $o = $this->_var('subjects', $id, 'details');
            break;
            case 'question':
                if($id) $o = $this->_var('questions', $id, 'details');
            break;
        }
        return $o ? strip_tags($o) : $default;
    }

    function head(){
        $title = $this->wp_title();
        $description = $this->desc();
        echo $this->og($title, $description);
    }

    function og($title, $description = ''){
        return '
            <meta property="fb:app_id" content="1363264657105483" />
            <meta name="description" content="'.$description.'" />
            <meta property="og:locale" content="ar_AR" />
            <meta property="og:title" content="'.$title.'" />
            <meta property="og:description" content="'.$description.'" />
            <meta property="og:type" content="website" />
            <meta property="og:url" content="'.$this->url.'" />
            <meta property="og:image" content="'.app_f()->assets('/img/og.png').'" />
            <meta property="og:image:width" content="1200" />
            <meta property="og:image:height" content="630" />
            <meta property="og:image" itemprop="image" content="'.app_f()->assets('/img/og-sm.png').'" />
            <meta property="og:image:width" content="1024" />
            <meta property="og:image:height" content="1024" />
            <meta property="og:site_name" content="'.$this->site_title.'" />
            <meta name="twitter:card" content="summary" />
            <meta name="twitter:title" content="'.$title.'"/>
            <meta name="twitter:description" content="'.$description.'" />
            <meta name="twitter:domain" content="easylaws"/>
        ';
    }
}
new APP_SEO;
