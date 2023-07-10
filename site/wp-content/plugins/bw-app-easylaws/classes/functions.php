<?php

	function __CORS($url){
		if(AH()->is_dev()) return $url;
		if (!preg_match("~^(?:f|ht)tps?:~i", $url)) {
	        $url = "https:" . $url;
	    }
		return "https://cors.bitwize.com.lb/$url";
	}

	function __rq($e){
		return !empty($_REQUEST[$e]) ? trim($_REQUEST[$e]) : '';
	}

	function __normalize_arabic($text) {
		// $patterns     = array( "/إ|أ|آ/" ,"/ة/", "/َ|ً|ُ|ِ|ٍ|ٌ|ّ/" );
		// $replacements = array( "ا" ,  "ه"      , ""         );
		$patterns     = array( "/إ|أ|آ/", "/َ|ً|ُ|ِ|ٍ|ٌ|ّ/" );
		$replacements = array( "ا"      , ""         );
		return preg_replace($patterns, $replacements, $text);
	}

	function question_strip_arabic($id){
		$id = intval($id);
		if(!$id) return;
		$T = DB()->prefix.'app_questions';
		$row = DB()->get_row("SELECT ID, title, details FROM $T WHERE ID=$id");
        DB()->update($T, [
            'title_striped' => __normalize_arabic($row->title),
            'details_striped' => __normalize_arabic($row->details),
        ], ['ID' => $id]);
	}

	function __fix_spaces($input){
		$input = str_replace(', ,', ', ', $input);
		$input = preg_replace("/,+/", ",", $input);
		return preg_replace('!\s+!', ' ', $input);
	}

	function __fix_keywords(){
		$t = DB()->prefix.'app_keywords';
		$keys = DB()->get_results("SELECT * FROM $t");
		foreach($keys as $key){
			DB()->update($t, [
				'details' => __fix_spaces($key->details)
			], ['ID' => $key->ID]);
		}
	}

	function __fix_url($url){
		if(!$url) return '';
		$arr = [
			'https://local.bitwize.com/__APPS/easylaws/site',
			'http://local.bitwize.com/__APPS/easylaws/site',
			'https://127.0.0.1/__APPS/easylaws/site',
			'http://127.0.0.1/__APPS/easylaws/site',
		];
		foreach($arr as $a){
			$url = str_replace($a, 'https://easylaws.me', $url);
		}
		return $url;
	}

	// __fix_keywords();

	function insert_notification($users = '', $args = []){
		$t = DB()->prefix.'app_notifications';
		$t_users = DB()->prefix.'app_users';
		$args = wp_parse_args($args, [
			'title' => '',
			'details' => '',
			'action' => '',
			'action_id' => '',
			'is_read' => 0,
			'_key' => uniqid().time(),
			'date_created' => time(),
		]);

		if(!$args['details'] || !$users) return;
		if($users == '__ALL__'){
			$users = DB()->get_col("SELECT ID FROM $t_users");
		}

		if(!is_array($users)) $users = [$users];
		if(empty($users)) return;
		foreach($users as $ID){
			DB()->insert($t, array_merge($args, ['user_id' => $ID]));
		}
		return;
	}

	function flag($i){
		if(!$i) return;
		return '<i class="flag flag-'.strtolower($i).'"></i>';
	}

	function tabs_langs(){
		return [
			'type' => 'tablist',
			'active' => 'AR',
			'tabs' => [
				'AR' => '<i class="flag flag-sa"></i> Arabic',
				'EN' => '<i class="flag flag-us"></i> English',
				'FR' => '<i class="flag flag-fr"></i> French',
			]
		];
	}

	function __excerpt($excerpt= '', $limit = 180, $after = '...'){
    	if(empty($excerpt)) return '';
	    $excerpt = preg_replace(" (\[.*?\])",'',$excerpt);
	    $excerpt = strip_shortcodes($excerpt);
	    $excerpt = strip_tags($excerpt);
	    $excerpt = str_replace('&nbsp;', ' ', $excerpt);

	    if(strlen($excerpt) <= $limit) $after = '';
	    $excerpt = substr($excerpt, 0, $limit);
	    if(strripos($excerpt, " ")){
	    	$excerpt = substr($excerpt, 0, strripos($excerpt, " "));
	    }
	    $excerpt = trim(preg_replace( '/\s+/', ' ', $excerpt));
	    $excerpt = $excerpt.$after;
	    return $excerpt;
	}

	function app_avatar__( $string = '', $size=128, $rounded = true ) {
		if ( '' == $string || strlen($string)<3 ){
			$red   = (int) mt_rand( 60, 230 );
			$blue  = (int) mt_rand( 60, 230 );
			$green = (int) mt_rand( 60, 230 );
		}else{
			$txt   = str_replace(' ','',$string);
			$arr   = str_split($txt);
			$count = count($arr);
			$mid   = (int) $count/2;
			$red   = (int) (60 + app_avatar_rnd($arr[0]) );
			$blue  = (int) (60 + app_avatar_rnd($arr[$mid]) );
			$green = (int) (60 + app_avatar_rnd($arr[$count-1]) );
		}
		$bgcolor = dechex( $red ) . dechex( $blue ) . dechex( $green );
		$color   = 'ffffff';

		$name = str_replace(' ', '+', trim($string));
		$url = "https://ui-avatars.com/api/?name=$name&color=$color&background=$bgcolor&size=$size";
		if($rounded){
			$url .= "&rounded=true";
		}
		return $url;
	}

    function app_avatar_rnd($n = ''){
    	if('' == $n) return 0;
    	$m = is_numeric($m) ? $m : ord(strtolower($m)) - 96;
    	$x = 230 - 60;
    	$v = (int) ($m*$m)/3;
    	if($v > $x) $v = $x;
    	return $v;
	}

	function get_status_icons($status = 0){
		if($status == 1){
			$color = 'success';
		} elseif($status == 2){
			$color = 'warning';
		} else {
			$color = 'danger';
		}
		return '<i class="fa fa-circle color-'.$color.'"></i>';
	}

	function app_status_select(){
		return array(
			0 => 'Pending',
			2 => 'Corrected',
			1 => 'Active',
		);
	}

	function app_get_authors(){
		$a = get_users([
			'role__not_in' => ['Subscriber'],
			'fields' => ['ID', 'display_name', 'user_email'],
			'exclude' => [1],
		]);
		$o = [];
		foreach($a as $b){
			$o[$b->ID] = $b->display_name;
		}
		return $o;
	}

	function app_get_question_color($ID){
		$default = get_subject_color();
		$id = intval($ID);
		if(!$id) return $default;
		$ts = DB()->prefix.'app_subjects';
		$tq = DB()->prefix.'app_questions';
		$cats = DB()->get_var("SELECT categories FROM $tq WHERE ID=$id");
		$cat = '';
		if($cats){
			if(strpos($cats,',')===false){
				$cat = intval($cats);
			} else {
				$cats = explode(',', $cats);
				$cat = intval($cats[0]);
			}
			if($cat){
				$color = DB()->get_var("SELECT color FROM $ts WHERE ID=$cat");
				$color = $color ? $color : $default;
				return $color;
			}
		}
		return $default;
	}

	function app_map_keywords($table, $id = null, $field = 'keywords', $field_translated = 'keywords_translated'){
		$tk = DB()->prefix.'app_keywords';
		if($id){
			$keys = DB()->get_var("SELECT `{$field}` FROM {$table} WHERE ID={$id}");
			$keys = explode(',',$keys);
			$vals = [];
			foreach($keys as $key){
				$key = trim($key);
				if($key && is_numeric($key)){
					$val = DB()->get_var("SELECT `title` FROM {$tk} WHERE ID={$key}");
					if($val) $vals[] = $val;
				}
			}
			$vals = implode(',', $vals);
			DB()->update($table, [$field_translated => $vals], ['ID' => $id]);
		} else {
			$items = DB()->get_results("SELECT `ID`,`{$field}` FROM {$table}");
			foreach($items as $item){
				$keys = explode(',',$item->$field);
				$vals = [];
				foreach($keys as $key){
					$key = trim($key);
					if($key && is_numeric($key)){
						$val = DB()->get_var("SELECT `title` FROM {$tk} WHERE ID={$key}");
						if($val) $vals[] = $val;
					}
				}
				$vals = implode(',', $vals);
				DB()->update($table, [$field_translated => $vals], ['ID' => $item->ID]);
			}
		}
	}

	function app_notify_admins_emails(){
		$admins = get_users(['role_in' => ['Administrator','Editor','Author']]);
		$emails = [];
		foreach($admins as $admin){
			$enabled = trim(get_user_option('can_receive_notifications', $admin->ID));
			if($enabled == 'enabled'){
				$emails[] = $admin->user_email;
			}
		}
		return $emails;
	}

	function app_notify_admins($subject, $message, $attachments = []){
		$headers = '';
		// $to = app_notify_admins_emails();
		$to = ['support@easylaws.me'];
		$sitename = get_bloginfo('name');
		$subject = "[{$sitename}] ".$subject;
		$ip = AH()->get_ip();
		$message = $message."<BR><BR>(IP: {$ip})";
		if(!empty($to)){
			return wp_mail($to, $subject, $message, $headers, $attachments);
		}
		return false;
	}

	function sql_stop_words(){
		$en = ["a","able","about","across","after","all","almost","also","am","among","an","and","any","are","as","at","be","because","been","but","by","can","cannot","could","dear","did","do","does","either","else","ever","every","for","from","get","got","had","has","have","he","her","hers","him","his","how","however","i","if","in","into","is","it","its","just","least","let","like","likely","may","me","might","most","must","my","neither","no","nor","not","of","off","often","on","only","or","other","our","own","rather","said","say","says","she","should","since","so","some","than","that","the","their","them","then","there","these","they","this","tis","to","too","twas","us","wants","was","we","were","what","when","where","which","while","who","whom","why","will","with","would","yet","you","your","ain't","aren't","can't","could've","couldn't","didn't","doesn't","don't","hasn't","he'd","he'll","he's","how'd","how'll","how's","i'd","i'll","i'm","i've","isn't","it's","might've","mightn't","must've","mustn't","shan't","she'd","she'll","she's","should've","shouldn't","that'll","that's","there's","they'd","they'll","they're","they've","wasn't","we'd","we'll","we're","weren't","what'd","what's","when'd","when'll","when's","where'd","where'll","where's","who'd","who'll","who's","why'd","why'll","why's","won't","would've","wouldn't","you'd","you'll","you're","you've"];

	    $ar = ["فى", "في", "كل", "لم", "لن", "له", "من", "هو", "هي", "قوة", "كما", "لها", "منذ", "وقد", "ولا", "نفسه", "لقاء", "مقابل", "هناك", "وقال", "وكان", "نهاية", "وقالت", "وكانت", "للامم", "فيه", "كلم", "لكن", "وفي", "وقف", "ولم", "ومن", "وهو", "وهي", "فيها", "منها", "مليار", "لوكالة", "يكون", "يمكن", "مليون", "حيث", "اكد", "الا", "اما", "امس", "السابق", "التى", "التي", "اكثر", "ايار", "ايضا", "ثلاثة", "الذاتي", "الاخيرة", "الثاني", "الثانية", "الذى", "الذي", "الان", "امام", "ايام", "خلال", "حوالى", "الذين", "الاول", "الاولى", "بين", "ذلك", "دون", "حول", "حين", "الف", "الى", "انه", "اول", "ضمن", "انها", "جميع", "الماضي", "الوقت", "المقبل", "ـ", "ف", "و", "و6", "قد", "لا", "ما", "مع", "مساء", "هذا", "واحد", "واضاف", "واضافت", "فان", "قبل", "قال", "كان", "لدى", "نحو", "هذه", "وان", "واكد", "كانت", "واوضح", "مايو", "ب", "ا", "أ", "،", "عشر", "عدد", "عدة", "عشرة", "عدم", "عام", "عاما", "عن", "عند", "عندما", "على", "عليه", "عليها", "زيارة", "سنة", "سنوات", "تم", "ضد", "بعد", "بعض", "اعادة", "اعلنت", "بسبب", "حتى", "اذا", "احد", "اثر", "برس", "باسم", "غدا", "شخصا", "صباح", "اطار", "اربعة", "اخرى", "بان", "اجل", "غير", "بشكل", "حاليا", "بن", "به", "ثم", "اف", "ان", "او", "اي", "بها", "صفر", "غاي"];
	    return array_merge($en, $ar);
	}

	function sql_replace_AR_A($t){
		$t = str_ireplace(['أ', 'إ', 'آ'], ['ا', 'ا', 'ا'], $t);
		// $t = str_ireplace('ال', '', $t);
		$t = str_ireplace(['ّ', 'ْ', 'ٌ', 'ُ', 'ٍ', 'ِ', 'ً', 'َ'], '', $t);
		$t = str_ireplace(['أل', 'إل', 'آل'], ['ال', 'ال', 'ال'], $t);
		return $t;
	}

	function sql_search_text($text){
		$t = explode(' ', $text);
		foreach($t as $k => $v){
			if(in_array($v, sql_stop_words())) unset($t[$k]);
			// $t[$k] = sql_replace_AR_A($v);
			$t[$k] = esc_sql($t[$k]);
		}
		return implode(' ', $t);
	}
	function sql_search_array($field='', $array='', $pre="OR"){
	    if(empty($field) || empty($array)) return '';
		if(!is_array($array)) $array = explode(' ', $array);
	    foreach($array as $k => $v){
	    	if(in_array($v, sql_stop_words())) unset($array[$k]);
			if(strlen($v) < 2) unset($array[$k]); // omit small keywords
			// $array[$k] = sql_replace_AR_A($v);
			$array[$k] = esc_sql($array[$k]);
			if(strlen($array[$k]) < 2) unset($array[$k]);
	    }
	    if(!empty($array)){
	   		$o = "`{$field}` LIKE '%".implode("%' OR `{$field}` LIKE '%", $array) . "%'";
	   		return "$pre ".str_replace("`{$field}` LIKE '%%' OR ", "", $o);
	   	}
	   	return '';
	}

	function get_question_comments($question_id, $user_id = ''){
		$question_id = intval($question_id);
		if(!$question_id) return '';

		$tc = DB()->prefix.'app_question_comments';
		$tv = DB()->prefix.'app_user_comment_votes';
		$tu = DB()->prefix.'app_users';
		$comments = DB()->get_results("SELECT * FROM {$tc} WHERE question_id={$question_id} AND status=1 ORDER BY date_created ASC");
		if($comments){
			$comments = array_map(function($item) use ($tu, $tv, $user_id){
				$voted = false;
				$vote_direction = '';
				if($user_id){
					$vote_check = DB()->get_row("SELECT ID,direction FROM {$tv} WHERE user_id={$user_id} AND comment_id={$item->ID}");

					if($vote_check){
						$voted = true;
						$vote_direction = $vote_check->direction ? 'up' : 'down';
					}
				}

				$u = DB()->get_row("SELECT * FROM $tu WHERE ID={$item->user_id}");
				$item->user = $u;
				$item->voted = $voted;
				$item->vote_direction = $vote_direction;
				$item->votes = intval($item->votes_up) - intval($item->votes_down);
				$item->date = date('Y-m-d H:i:s', $item->date_created);
				return $item;
			}, $comments);
		}
		return $comments ? $comments : [];
	}

	function get_question_images($images='', $size = 'mobile-resized'){
		$o = [];
		if(empty($images)) return $o;
		$images = explode(',', $images);
		foreach($images as $image){
			$src   = wp_get_attachment_image_src($image, $size);
			$thumb = wp_get_attachment_image_src($image, 'mobile-thumb');
			$o[] = [
				'title' => get_the_title($image),
				'url'   => !empty($src) ? __fix_url($src[0]) : '',
				'thumb' => !empty($thumb) ? __fix_url($thumb[0]) : '',
			];
		}
		return $o;
	}

	function get_question_videos($videos=''){
		$o = [];
		if(empty($videos)) return $o;
		$videos = explode(',', $videos);
		foreach($videos as $video){
			$o[] = [
				'title' => get_the_title($video),
				'url'   => __fix_url( wp_get_attachment_url($video) )
			];
		}
		return $o;
	}

	function get_subject_color($color=''){
		global $APP;
		$default = ($APP['default_subject_color']) ? $APP['default_subject_color'] : '#000';
		return $color ? $color : $default;
	}

	function get_subject_image($image, $size = 'mobile-thumb'){
		if(!empty($image)){
			$image = explode(',', $image);
			$image = !empty($image) ? $image[0] : '';
			if($image){
				$src = wp_get_attachment_image_src($image, $size);
				if(!empty($src)){
					return __fix_url($src[0]);
				}
			}
		}
		global $APP;
		$default = ($APP['default_subject_image']) ? $APP['default_subject_image']['url'] : '';
		return $default;
	}

	function number_shorten($number, $precision = 3, $divisors = null) {
	    if (!isset($divisors)) {
	        $divisors = array(
	            pow(1000, 0) => '',   // 1000^0 == 1
	            pow(1000, 1) => 'K',  // Thousand
	            pow(1000, 2) => 'M',  // Million
	            pow(1000, 3) => 'B',  // Billion
	            pow(1000, 4) => 'T',  // Trillion
	            pow(1000, 5) => 'Qa', // Quadrillion
	            pow(1000, 6) => 'Qi', // Quintillion
	        );
	    }
	    foreach ($divisors as $divisor => $shorthand) {
	        if (abs($number) < ($divisor * 1000)) {
	            break;
	        }
	    }
	    return number_format($number / $divisor, $precision) . $shorthand;
	}

	function app_rq($request = '', $int = false){
		if($request == '') return '';
		$o = !empty( $_REQUEST[$request] ) ? $_REQUEST[$request] : '';
		if(is_array($o)){
			$o = array_map(function($item){
				return wp_unslash(trim($item));
			}, $o);
		} else {
			$o = wp_unslash(trim($o));
		}
		if($int) $o = intval($o);
		return $o;
	}

	function app_referer(){
		return esc_attr( remove_query_arg( ['_wp_http_referer', '_wpnonce'], wp_get_referer() ) );
	}

	function app_user_card($ID){
		$ID = intval($ID);
		if(!$ID) return '';
		$t = DB()->prefix.'app_users';
		$row = DB()->get_row("SELECT * FROM $t WHERE ID={$ID}");
		if(!$row) return '---';

		$img = $row->image ? __CORS($row->image) : bwd_avatar()->get_img($row->name, 32);
        $img = '<img src="'.$img.'" width="32" height="32" />';

        $icon_gender = (strtolower($row->gender) == 'female') ? 'venus' : 'mars';
        $icon = '<i class="fa fa-'.$icon_gender.'" style="margin-right: 5px;"></i> ';
        $age = ($row->dob && $row->dob != '0000-00-00') ? AH()->get_age($row->dob).' years old<br>' : '';

        $email = $row->email ? '<a href="mailto:'.$row->email.'">'.$row->email.'</a><br>' : '';
        $phone = $row->phone ? $row->phone.'<br>' : '';
		return $icon.$img.' <b>'.$row->name.'</b><br>'.$email.$phone.$age.$row->ID;
	}

	function app_user_object($ID){
		$ID = intval($ID);
		if(!$ID) return '';
		$t = DB()->prefix.'app_users';
		$row = DB()->get_row("SELECT * FROM $t WHERE ID={$ID}");
		if(!$row) return [];

		$row->image = $row->image ? $row->image : bwd_avatar()->get_img($row->name, 32);
        $row->age = $row->dob ? AH()->get_age($row->dob).' years old<br>' : '';

        return $row;
	}

	function questions_by_subject($id){
		if($id){
			$t = DB()->prefix . 'app_questions';
			$id = intval($id);
			return DB()->get_results("SELECT * FROM {$t} WHERE FIND_IN_SET({$id}, `categories`)>0 AND trashed=0 ORDER BY menu_order ASC");
		}
		return '';
	}

	function get_subjects_by_line($line, $sep = ', ', $arrow = ' <- '){
		if($line){
			$ids = explode(',', $line);
			$o = [];
			foreach($ids as $id){
				$id = intval($id);
				$o[] = implode($arrow, get_subject_ancestors($id));
			}
			return implode($sep, $o);
		}
		return '';
	}

	function get_references_by_line($line, $sep = ', ', $arrow = ' <- '){
		if($line){
			$ids = explode(',', $line);
			$o = [];
			foreach($ids as $id){
				$id = intval($id);
				$o[] = implode($arrow, get_reference_ancestors($id));
			}
			return implode($sep, $o);
		}
		return '';
	}

	function get_keywords_by_line($line, $sep = ', '){
		if($line){
			$ids = explode(',', $line);
			$o = [];
			foreach($ids as $id){
				$id = intval($id);
				$o[] = keyword_by_id($id);
			}
			return implode($sep, $o);
		}
		return '';
	}

	function get_tags_by_line($line, $sep = ', '){
		if($line){
			$ids = explode(',', $line);
			$o = [];
			foreach($ids as $id){
				$id = intval($id);
				$o[] = tag_by_id($id);
			}
			return implode($sep, $o);
		}
		return '';
	}

	function keyword_by_id($id){
		$t = DB()->prefix.'app_keywords';
		if($id && is_numeric($id)) return DB()->get_var("SELECT title FROM {$t} WHERE ID={$id}");
		return '';
	}

	function tag_by_id($id){
		$t = DB()->prefix.'app_tags';
		if($id && is_numeric($id)) return DB()->get_var("SELECT title FROM {$t} WHERE ID={$id}");
		return '';
	}

	function subject_by_id($id){
		$t = DB()->prefix.'app_subjects';
		if($id && is_numeric($id)) return DB()->get_var("SELECT title FROM {$t} WHERE ID={$id}");
		return '';
	}

	function subject_row_by_id($id){
		$t = DB()->prefix.'app_subjects';
		if($id && is_numeric($id)) return DB()->get_row("SELECT * FROM {$t} WHERE ID={$id}");
		return '';
	}

	function subjects_by_field($f = '', $implode = '<br>'){
		$t = DB()->prefix.'app_subjects';
		$o = array();
		if($f){
			$ns = explode(',',$f);
			foreach($ns as $n){
				if($n){
					// $o[] = subject_by_id( intval(trim($n)) );
					$o[] = get_subject_ancestors( intval(trim($n)), ' &rsaquo; ' );
				}
			}
		}
		if(!empty($o)) return implode($implode, $o);
		return '';
	}

	function get_subject_ancestors( $post, $implode = false ) {
		$t = DB()->prefix.'app_subjects';
		$post = intval($post);
		if(!$post) return;
		$post = DB()->get_row("SELECT ID,parent,title FROM $t WHERE ID=$post");
		if(!$post) return '';
		if (empty($post->parent) || $post->parent == $post->ID || $post->parent == 0) {
			if($implode){
				return $post->title;
			} else {
				return array($post->title);
			}
		}
		$ancestors = array();
		$ancestors_names = array();
		$id = $ancestors[] = $post->parent;
		$ancestors_names[] = $post->title;

		while ($ancestor = DB()->get_row("SELECT ID,parent,title FROM $t WHERE ID=$id") ) {
			if ( !$ancestor->parent || ($ancestor->parent == $post->ID) || in_array($ancestor->parent, $ancestors) ) {
				$id = $ancestors[] = $ancestor->parent;
				$ancestors_names[] = $ancestor->title;
				break;
			}

			$id = $ancestors[] = $ancestor->parent;
			$ancestors_names[] = $ancestor->title;
		}
		$o = array_reverse($ancestors_names);
		if($implode){
			return implode($implode, $o);
		} else {
			return $o;
		}
	}

	function get_subject_ancestors_array( $post, $implode = false ) {
		$t = DB()->prefix.'app_subjects';
		$post = intval($post);
		if(!$post) return;
		$post = DB()->get_row("SELECT ID,parent,title FROM $t WHERE ID=$post");
		if(!$post) return '';
		if (!$post->parent || $post->parent == $post->ID) {
			return [['ID' => $post->ID, 'title' => $post->title]];
		}
		$ancestors = [];
		$ancestors_names = [];
		$id = $ancestors[] = $post->parent;
		$ancestors_names[] = ['ID' => $post->ID, 'title' => $post->title];

		while ($ancestor = DB()->get_row("SELECT ID,parent,title FROM $t WHERE ID=$id") ) {
			if ( !$ancestor->parent || ($ancestor->parent == $post->ID) || in_array($ancestor->parent, $ancestors) ) {
				$id = $ancestors[] = $ancestor->parent;
				$ancestors_names[] = ['ID' => $ancestor->ID, 'title' => $ancestor->title];
				break;
			}

			$id = $ancestors[] = $ancestor->parent;
			$ancestors_names[] = ['ID' => $ancestor->ID, 'title' => $ancestor->title];
		}
		$o = array_reverse($ancestors_names);
		return $o;
	}

	function get_subjects($args = array()) {
		$args = wp_parse_args($args, array(
			'compact' => false,
			'cache' => true,
			'show_count' => false,
			's' => '',
			'trans' => '',
			'parent' => 0,
			'hide_empty' => false,
			'limited_search' => false,
		));
		$t = DB()->prefix.'app_subjects';
		$t_keywords = DB()->prefix.'app_keywords';
	    $flat = [];
	    $tree = [];
	    $fields = 'T.*';

	    $where = "WHERE 1=1";
	    $order="T.menu_order ASC, T.title ASC";
	    $join = '';
	    $group = '';

	    $parent = intval($args['parent']);
		$s = trim($args['s']);
		$s_orig = $s;
	    if(strlen($s)>1){
	    	$trans = $args['trans'] ? $args['trans'] : app_trans()->go($s);

			// $s = $s.' '.$trans['term'];
			// if($args['limited_search']){
			// 	$s = trim($trans['str'].' '.$trans['term'].' '.$trans['str_keywords']);
			// } else {
				$s = $trans['query'];
			// }


			$s = sql_search_text($s);
			$fields .= ", MATCH(T.title) AGAINST ('$s' IN BOOLEAN MODE) AS relevance";
	    	// $join = "LEFT JOIN {$t_keywords} KEYW on FIND_IN_SET(KEYW.ID, T.keywords)>0";
	    	// $where .= " AND (T.title LIKE '%$s%' OR T.details LIKE '%$s%' OR KEYW.title LIKE '%$s%')";
	    	$where .= " AND (MATCH(T.title) AGAINST ('$s' IN BOOLEAN MODE))";
	    	$where .= " GROUP BY T.ID";
			$order = "relevance DESC";

			/*
			$s = $trans['search'];
			$s_title = sql_search_array('T.title', $s, '');
			$where .= " AND ($s_title)";
			*/
	    }

	    if($parent){
	    	$where .= " AND (T.ID={$parent} OR T.parent={$parent})";
	    	$order = "T.ID ASC";
	    }

	    $query = "SELECT {$fields} FROM {$t} T {$join} {$where} {$group} ORDER BY {$order}";
		$rows = DB()->get_results($query);

		// if($s_orig && !$rows){
		// 	$where = "WHERE 1=1";
		// 	$fields = 'T.*';
		// 	$order="T.menu_order ASC, T.title ASC";
		// 	if($parent){
		// 		$where .= " AND (T.ID={$parent} OR T.parent={$parent})";
		// 		$order = "T.ID ASC";
		// 	}
		// 	$s_title = sql_search_array('T.title', $s_orig, '');
		// 	$where .= " AND ($s_title)";
		// 	$query = "SELECT {$fields} FROM {$t} T {$join} {$where} {$group} ORDER BY {$order}";
		// 	$rows = DB()->get_results($query);
		// }

	    foreach ($rows as $row) {
	    	$row->image = get_subject_image($row->image, 'mobile-thumb');
	    	$row->color = get_subject_color($row->color);
	        $flat[$row->ID] = [
	            'data'     => $row,
	            'children' => array(),
	        ];
	    }

	    foreach($rows as $row) {
	    	if($args['hide_empty'] && $row->posts_count == 0) continue;
	    	$row->parent = intval($row->parent);
	        if ($row->parent > 0 && $row->ID != $parent) {
	        	if(strlen($s)>2 && empty($flat[$row->parent])){
	        		$tree[] =& $flat[$row->ID];
	        	} else {
	        		$flat[$row->parent]['children'][] =& $flat[$row->ID];
	            }
	        } else {
	            $tree[] =& $flat[$row->ID];
	        }
	    }
	    return $tree;
	}

	function map_subject($row){
		$row->image = get_subject_image($row->image, 'mobile-thumb');
		$row->color = get_subject_color($row->color);
		$children = $row->children;
		unset($row->children);
		$data = $row;
		return ['data' => $data, 'children' => $children];
	}

	function get_subjects_new($args = []){
		$args = wp_parse_args($args, [
			'search' => '',
			'ID' => '',
			'parent' => 0,
			'hide_empty' => 1,
		]);

		$t = DB()->prefix.'app_subjects';
		$order="menu_order ASC, title ASC";
		$where = '1=1';

		if($args['search']){
			$args['parent'] = ''; // disable parents
			$search = trim(__normalize_arabic($args['search']));
			$title_search = sql_search_array('title', $search, '');
			$keyword_search = sql_search_array('keywords', $search, '');
			$where .= " AND ($title_search OR $keyword_search) ";
			$order="parent ASC, menu_order ASC, title ASC";
		}

		if($args['parent'] || $args['parent'] === 0) {
			$parent = intval($args['parent']);
			if($parent || $parent === 0) $where .= " AND parent={$parent}";
		}

		$ID = intval($args['ID']);
		if($ID) $where .= " AND ID={$ID}";

		if($args['hide_empty']){
			$where .= " AND posts_count > 0";
		}
		$query = "SELECT * FROM $t WHERE $where ORDER BY $order";
		// AH()->print_r($query);
		$rows = DB()->get_results($query);
		foreach($rows as $row){
			$row->children = get_subjects_new( array_merge($args, [
				'search' => '',
				'ID' => '',
				'parent' => $row->ID
			]));
		}
		return array_map('map_subject', $rows);
	}

	function app_subjects_select_row($row, $level = 0, $str_repeat="---"){
		$data = $row;
        $row = AH()->array_to_object($row['data']);
        $o = array();
        $o[$row->ID] = str_repeat($str_repeat, $level)." ".$row->title;
        if(!empty($data['children'])){
            $level = $level + 1;
            foreach($data['children'] as $crow){
                $o = $o + app_subjects_select_row($crow, $level, $str_repeat);
            }
        }
        return $o;
	}
	function app_subjects_select($enable_none = true, $str_repeat="---"){
		$arr = get_subjects(['compact' => true]);
		$o = array();
		foreach($arr as $k => $v){
			$o = $o + app_subjects_select_row($v, 0, $str_repeat);
		}
		if($enable_none) return array(0 => '--NONE--') + $o;
	    return $o;
	}

	function app_count_cat_posts_raw($ID){
		$t = DB()->prefix.'app_questions';
		$o = DB()->get_var("SELECT COUNT(ID) FROM {$t} WHERE FIND_IN_SET({$ID}, categories)>0 AND status=1");
		return intval($o);
	}

	function app_count_cat_posts($ID, $count_subs = false){
		$t = DB()->prefix.'app_questions';
		$ts = DB()->prefix.'app_subjects';
		if($count_subs){
			$count = 0;
			$subs = DB()->get_col("SELECT ID FROM $ts WHERE parent=$ID");
			if($subs){
				foreach($subs as $sub){
					$sub_subs = DB()->get_col("SELECT ID FROM $ts WHERE parent=$sub");
					if($sub_subs){
						foreach($sub_subs as $sub_sub){
							$count += app_count_cat_posts_raw($sub_sub);
						}
					}
					$count += app_count_cat_posts_raw($sub);
				}
				return $count;
			} else {
				return app_count_cat_posts_raw($ID);
			}
		}
		return app_count_cat_posts_raw($ID);
	}

	function app_set_count_cat_posts(){
		$t = DB()->prefix.'app_subjects';
		$ids = DB()->get_col("SELECT ID FROM $t");
		if($ids){
			foreach($ids as $id){
				DB()->update($t, ['posts_count' => app_count_cat_posts($id, true)], ['ID' => $id]);
			}
		}
	}

	function app_count_ref_posts_raw($ID){
		$t = DB()->prefix.'app_questions';
		// $o = DB()->get_var("SELECT COUNT(ID) FROM {$t} WHERE FIND_IN_SET({$ID}, `references`)>0 AND status=1");
		$o = DB()->get_var("SELECT COUNT(ID) FROM {$t} WHERE FIND_IN_SET({$ID}, `references`)>0");
		return intval($o);
	}

	function app_count_ref_posts($ID, $count_subs = false){
		$t = DB()->prefix.'app_questions';
		$ts = DB()->prefix.'app_references';
		if($count_subs){
			$count = 0;
			$subs = DB()->get_col("SELECT ID FROM $ts WHERE parent=$ID");
			if($subs){
				foreach($subs as $sub){
					$sub_subs = DB()->get_col("SELECT ID FROM $ts WHERE parent=$sub");
					if($sub_subs){
						foreach($sub_subs as $sub_sub){
							$count += app_count_ref_posts_raw($sub_sub);
						}
					}
					$count += app_count_ref_posts_raw($sub);
				}
				return $count;
			} else {
				return app_count_ref_posts_raw($ID);
			}
		}
		return app_count_ref_posts_raw($ID);
	}

	function app_count_cat_views($ID){
		$t = DB()->prefix.'app_subjects';
		$views = DB()->get_var("SELECT views FROM {$t} WHERE ID={$ID}");
		return number_shorten($views, 0);
	}

	function app_tag_count($ID){
		$t = DB()->prefix.'app_questions';
		return DB()->get_var("SELECT COUNT(ID) FROM {$t} WHERE FIND_IN_SET({$ID}, tags)>0");
	}

	function app_json_tree_parse($item, $values = '', $count = false, $ref = false){
		$o = array();
		$data = (array) $item['data'];
		$children = (array) $item['children'];

		$o['id'] = $data['ID'];
		$o['ID'] = $data['ID'];
		$o['text'] = $data['title'];

		$color = (isset($data['color']) && $data['color']) ? $data['color'] : get_subject_color();
		$image = (isset($data['image']) && $data['image']) ? '<i class=\"fa fa-check\"></i>' : '<i class=\"fa fa-exclamation\"></i>';
		if($count){
			$o['tags'] = [
				$image,
				'<div style=\"width: 12px; height: 12px; background-color: '.$color.'\"></div>',
				'<i class=\"fa fa-arrow-up\"></i> '.$data['menu_order'],
				'QN: '.$data['posts_count'],
				'Views: '.app_count_cat_views($data['ID']),
				'ID: '.$data['ID'],
			];
		}

		if($ref){
			$o['tags'] = [
				'<a href=\"admin.php?page=app-questions&app_filter_ref='.$data['ID'].'\"><i class=\"fa fa-link\"></i></a>',
				'QN: <a href=\"javascript:void(0);\" class=\"ref-count-check\" id=\"ref-'.$data['ID'].'\"><i class=\"fa fa-dot-circle-o\"></i></a>',
				// 'ID: '.$data['ID'],
			];
		}

		$c_values = explode(',', $values);
		if( in_array(trim($data['ID']), array_map('trim', $c_values))){
			$o['state']['checked'] = true;
		}
		if(!empty($children)){
			$o['nodes'] = array_map(function($item) use ($values, $count, $ref){
				return app_json_tree_parse($item, $values, $count, $ref);
			}, $children);
		}
		return $o;
	}

	function app_json_tree($type = 'subjects', $values = '', $show_count = false){
		if($type == 'subjects'){
			$ss = get_subjects(['compact' => false]);
			$ref = false;
		} else {
			$ss = get_references(['compact' => true]);
			$ref = true;
		}
		$arr = array_map(function($item) use ($values, $show_count, $ref){
			return app_json_tree_parse($item, $values, $show_count, $ref);
		}, $ss);
		return json_encode($arr);
	}

	function reference_by_id($id){
		$t = DB()->prefix.'app_references';
		if($id && is_numeric($id)) return DB()->get_var("SELECT title FROM {$t} WHERE ID={$id}");
		return '';
	}

	function reference_row_by_id($id){
		$t = DB()->prefix.'app_references';
		if($id && is_numeric($id)) return DB()->get_row("SELECT * FROM {$t} WHERE ID={$id}");
		return '';
	}

	function get_references($args = array()) {
		$args = wp_parse_args($args, array(
			'compact' => false,
		));
		$t = DB()->prefix.'app_references';
		$tq = DB()->prefix.'app_questions';
	    $flat = array();
	    $tree = array();
	    $fields = $args['compact'] ? 'ID,title,parent' : '*';
		// $rows = DB()->get_results("SELECT *, (SELECT COUNT({$tq}.ID) FROM {$tq} WHERE FIND_IN_SET({$t}.ID, {$tq}.references)>0 AND {$tq}.status=1) as q_count FROM {$t} ORDER BY {$t}.title ASC");
		$rows = DB()->get_results("SELECT * FROM {$t} ORDER BY {$t}.title ASC");
	    foreach ($rows as $row) {
	        $flat[$row->ID] = array(
	            'data'     => $row,
	            'children' => array(),
	        );
	    }
	    foreach($rows as $row) {
	    	$row->parent = intval($row->parent);
	        if ($row->parent > 0) {
	            $flat[$row->parent]['children'][] =& $flat[$row->ID];
	        } else {
	            $tree[] =& $flat[$row->ID];
	        }
	    }
	    return $tree;
	}

	function get_reference_ancestors( $post ) {
		$t = DB()->prefix.'app_references';
		$post = DB()->get_row("SELECT ID,parent,title FROM $t WHERE ID=$post");
		if(!$post) return '';
		if (empty($post->parent) || $post->parent == $post->ID) {
			return array($post->title);
		}
		$ancestors = array();
		$ancestors_names = array();
		$id = $ancestors[] = $post->parent;
		$ancestors_names[] = $post->title;

		while ($ancestor = DB()->get_row("SELECT ID,parent,title FROM $t WHERE ID=$id") ) {
			if ( !$ancestor->parent || ($ancestor->parent == $post->ID) || in_array($ancestor->parent, $ancestors) ) {
				$id = $ancestors[] = $ancestor->parent;
				$ancestors_names[] = $ancestor->title;
				break;
			}

			$id = $ancestors[] = $ancestor->parent;
			$ancestors_names[] = $ancestor->title;
		}
		return array_reverse($ancestors_names);
	}

	function app_references_select_row($row, $level = 0, $str_repeat="---"){
		$data = $row;
        $row = AH()->array_to_object($row['data']);
        $o = array();
        $o[$row->ID] = str_repeat($str_repeat, $level)." ".$row->title;
        if(!empty($data['children'])){
            $level = $level + 1;
            foreach($data['children'] as $crow){
                $o = $o + app_references_select_row($crow, $level, $str_repeat);
            }
        }
        return $o;
	}
	function app_references_select($enable_none = true, $str_repeat="---"){
		$arr = get_references(['compact' => true]);
		$o = array();
		foreach($arr as $k => $v){
			$o = $o + app_references_select_row($v, 0, $str_repeat);
		}
		if($enable_none) return array(0 => '--NONE--') + $o;
	    return $o;
	}

	function app_repeat_count($txt = ''){
		if($txt){
			return count(explode('*||*', $txt));
		}
		return 0;
	}

	function app_repeat_translate($txt = ''){
		if($txt){
			return explode('*||*', $txt);
		}
		return [];
	}

	function app_tags_translate($tags){
		$t = DB()->prefix.'app_questions';
		$tags_arr = [];
        if($tags){
        	$tags = explode(',', $tags);
        	foreach($tags as $ID){
				// $count = DB()->get_var("SELECT COUNT(ID) FROM {$t} WHERE FIND_IN_SET({$ID}, tags)>0 AND status=1 AND trashed=0");
				// if($count){
					$tags_arr[] = [
						'ID' => $ID,
						'title' => tag_by_id($ID),
					];
				// }
        	}
        }
        return $tags_arr;
	}

	function app_references_translate($references){
		$t = DB()->prefix.'app_references';
		$references_arr = [];
        if($references){
        	$references = explode(',', $references);
        	foreach($references as $reference){
        		$parent = DB()->get_var("SELECT parent FROM {$t} WHERE ID={$reference}");
        		if($parent) $parent = reference_by_id($parent);
        		$references_arr[] = [
        			'ID' => $reference,
        			'title' => reference_by_id($reference),
        			'parent' => $parent,
        		];
        	}
        }
        return $references_arr;
	}

	function app_convert_from_repeater($vals){
		if(!empty($vals)){
			// AH()->print_r($vals);
			// echo '<pre>'; echo json_encode($vals, JSON_PRETTY_PRINT); echo '</pre>';

			echo '';
			$arr = array();
			foreach($vals as $val){
				foreach($val as $k => $v){
					if(!empty($v)) $arr[] = $v;
				}
			}
			return implode('*||*', $arr);
		}
		return '';
	}

	function app_tags_select() {
		$t = DB()->prefix.'app_tags';
		$list_items = array();
	    $cats = DB()->get_results("SELECT ID, title FROM {$t} ORDER BY title ASC");
	    foreach ($cats as $cat) {
	        $list_items[$cat->ID] = $cat->title;
	    }
	    return $list_items;
	}

	function app_keywords_select() {
		$t = DB()->prefix.'app_keywords';
		$list_items = array();
	    $cats = DB()->get_results("SELECT ID, title FROM {$t} ORDER BY title ASC");
	    foreach ($cats as $cat) {
	        $list_items[$cat->ID] = $cat->title;
	    }
	    return $list_items;
	}

	function AF_EX(){
		return [
			'AR', '/AR', 'EN', '/EN', 'FR', '/FR', 'tablist', '/tablist'
		];
	}

	function AFL($input, $label = '', $desc = '', $subtitle = ''){
		$desc = $desc ? '<p class="desc">'.$desc.'</p>' : '';
		$subtitle = $subtitle ? '<p class="subtitle">'.$subtitle.'</p>' : '';
		$tt = sanitize_title($label);
		return '
			<table id="form-item-'.$tt.'" class="wp-list-table widefat fixed app-table"><tbody>
				<tr><th>'.$label.$subtitle.'</th><td>'.$input.$desc.'</td></tr>
			</tbody></table>
		';
	}

	function AF($type, $args = array()){
		$args = wp_parse_args($args, array(
			'method' => 'post',
			'name' => '',
			'value' => '',
			'label' => '',
			'desc' => '',
			'subtitle' => '',
			'options' => [],
			'multiple' => false,
			'can_add' => '',
			'can_sort' => '',
			'media_type' => 'image',
			'section' => '',
			'tabs' => [],
			'active' => '',
			'lang' => '',
			'raw_select' => false,
		));
		switch ($type){
			case 'form_open':
			case 'form':
				return '<form action="" method="'.$args['method'].'" class="app_dash_form __FORM">';
			break;
			case 'form_close':
			case '/form':
				return '</form>';
			break;
			case 'table_open':
				return '<table class="wp-list-table widefat fixed app-table">';
			break;
			case 'table_close':
				return '</table>';
			break;
			case 'tablist':
				$ss = '';
				if(!empty($args['tabs'])){
					foreach($args['tabs'] as $k => $v){
						$cls = ($k == $args['active']) ? 'active' : '';
						$ss .= '<li class="'.$cls.'"><a href="#tab-'.$k.'" role="tab" data-toggle="tab">'.$v.'</a></li>';
					}
				}
				return '
					<ul class="nav nav-tabs">'.$ss.'</ul>
					<div class="tab-content">
				';
			break;
			case 'tab':
				$cls = $args['active'] ? 'active' : '';
				return '<div class="tab-pane '.$cls.'" id="tab-'.$args['name'].'">';
			break;
			case '/tablist':
			case '/tab':
				return '</div>';
			break;
			case 'html':
				return $args['value'];
			break;
			case 'submit':
				return '
					<div class="app-tablenav bottom">
						<input type="submit" name="'.$args['name'].'" value="'.$args['value'].'" class="button-primary __SUBMIT">
					</div>
				';
			break;
			case 'hidden':
				return '<input type="hidden" name="'.$args['name'].'" value="'.$args['value'].'">';
			break;
			case 'text_repeater':
				$items = '';
				if($args['value'] && strlen($args['value']) > 1){
					$vals = explode('*||*', $args['value'] );
					foreach($vals as $val){
						$items .= '
						<div data-repeater-item class="repeater-item">
			        		<input class="app_input repeated" type="text" name="'.$args['name'].'[]" value="'.$val.'">
			       			<a data-repeater-delete class="button button-error"><i class="fa fa-trash"></i></a>
			   			</div>
						';
					}
				} else {
					$items .= '
					<div data-repeater-item class="repeater-item">
			       		<input class="app_input repeated" type="text" name="'.$args['name'].'[]" value="'.$args['value'].'">
			 			<a data-repeater-delete class="button button-error"><i class="fa fa-trash"></i></a>
	      			</div>
					';
				}
				$input = '
				<div class="repeater">
					<div class="repeater-list" data-repeater-list="'.$args['name'].'">
			      		'.$items.'
			    	</div>
			    	<a data-repeater-create class="button button-secondary">Add</a>
			    </div>
			    ';
				return AFL($input, $args['label'], $args['desc'], $args['subtitle']);
			break;
			case 'textarea_repeater':
				$items = '';
				if($args['value'] && strlen($args['value']) > 1){
					$vals = explode('*||*', $args['value'] );
					foreach($vals as $val){
						$items .= '
						<div data-repeater-item class="repeater-item app_editor_sm_wrap">
			        		<textarea class="app_input app_editor_sm repeated" name="'.$args['name'].'[]" data-lang="'.$args['lang'].'">'.$val.'</textarea>
			       			<a data-repeater-delete class="button button-error"><i class="fa fa-trash"></i></a>
			   			</div>
						';
					}
				} else {
					$items .= '
					<div data-repeater-item class="repeater-item app_editor_sm_wrap">
			       		<textarea class="app_input app_editor_sm repeated" name="'.$args['name'].'[]" data-lang="'.$args['lang'].'">'.$args['value'].'</textarea>
			 			<a data-repeater-delete class="button button-error"><i class="fa fa-trash"></i></a>
	      			</div>
					';
				}
				$input = '
				<div class="repeater">
					<div class="repeater-list" data-repeater-list="'.$args['name'].'">
			      		'.$items.'
			    	</div>
			    	<a data-repeater-create class="button button-secondary">Add</a>
			    </div>
			    ';
				return AFL($input, $args['label'], $args['desc'], $args['subtitle']);
			break;
			case 'text':
				$args['value'] = str_replace('"', "'", $args['value']);
				$input = '<input class="app_input" type="text" name="'.$args['name'].'" value="'.$args['value'].'">';
				return AFL($input, $args['label'], $args['desc'], $args['subtitle']);
			break;
			case 'color':
				$args['value'] = str_replace('"', "'", $args['value']);

				$input = '
				<div class="input-group colorpicker-component input_color">
					<input type="text" class="app_input" name="'.$args['name'].'" value="'.$args['value'].'" data-color="'.get_subject_color().'"/>
					<span class="input-group-addon"><i></i></span>
			  	</div>
				';
				return AFL($input, $args['label'], $args['desc'], $args['subtitle']);
			break;
			case 'number':
				$input = '<input class="app_input" type="number" name="'.$args['name'].'" value="'.$args['value'].'">';
				return AFL($input, $args['label'], $args['desc'], $args['subtitle']);
			break;
			case 'textarea':
				$input = '<textarea class="app_input autogrow" name="'.$args['name'].'">'.$args['value'].'</textarea>';
				return AFL($input, $args['label'], $args['desc'], $args['subtitle']);
			break;
			case 'editor':
				$input = '<textarea name="'.$args['name'].'" class="app_editor" data-lang="'.$args['lang'].'">'.$args['value'].'</textarea>';
				return AFL($input, $args['label'], $args['desc'], $args['subtitle']);
			break;
			case 'select':
				$default = isset($args['default']) ? $args['default'] : '';
				$multi = $args['multiple'] ? 'multiple' : '';
				$name = $args['multiple'] ? $args['name'].'[]' : $args['name'];
				$class = $args['multiple'] ? 'app_select_sortable' : 'app_select';
				if($args['raw_select']) $class = '';
			
				$options = '';
				if($args['options']):
				// Sort options for select ORDER
				if($args['can_sort'] == 'yes'){
					$vvs = explode(',', $args['value']);
					$args['options'] = sortArrayByArray($args['options'], $vvs);
				}
				// end
				foreach($args['options'] as $k => $v){
					$val = $args['value'] ? $args['value'] : $default;
					$sel = '';
					if(strpos($val,',')===false){
						if(trim($val) == trim($k)){
							$sel = 'selected="selected"';
						}
					} else {
						$vals = explode(',', $val);
						foreach($vals as $val){
							if(trim($val) == trim($k)){
								$sel = 'selected="selected"';
							}
						}
					}
					$options .= '<option value="'.$k.'" '.$sel.'>'.$v.'</option>';
				}
				endif;
				$input = '<select class="app_input '.$class.'" name="'.$name.'" '.$multi.' data-table="'.$args['name'].'" data-can_add="'.$args['can_add'].'" data-can_sort="'.$args['can_sort'].'">'.$options.'</select>';
				return AFL($input, $args['label'], $args['desc'], $args['subtitle']);
			break;
			case 'checkbox':
				$cid = 'list-'.uniqid();
				$name = $args['name'].'[]';
				$options = '';
				if($args['options']):
				foreach($args['options'] as $k => $v){
					$val = $args['value'];
					$sel = '';
					if(strpos($val,',')===false){
						if(trim($val) == trim($k)){
							$sel = 'checked="checked"';
						}
					} else {
						$vals = explode(',', $val);
						foreach($vals as $val){
							if(trim($val) == trim($k)){
								$sel = 'checked="checked"';
							}
						}
					}
					$options .= '<li><label><input type="checkbox" name="'.$name.'" value="'.$k.'" '.$sel.'><span class="text">'.$v.'</span></label></li>';
				}
				endif;
				$input = '
					<div id="'.$cid.'" class="checkbox-wrap">
						<input class="search app_input" type="text" placeholder="Search" />
						<ul class="list">
							'.$options.'
						</ul>
					</div>
					<script>
					jQuery(document).ready(function($){
						new List("'.$cid.'", {valueNames: ["text"]});
					});
					</script>
				';
				return AFL($input, $args['label'], $args['desc'], $args['subtitle']);
			break;
			case 'checkbox_one':
				$default = !empty($args['default']) ? $args['default'] : '';
				$value = !empty($args['value']) ? $args['value'] : $default;
				$input = '<input type="checkbox" name="'.$args['name'].'" value="'.$value.'">';
				return AFL($input, $args['label'], $args['desc'], $args['subtitle']);
			break;
			case 'switch':
				$sel = '';
				$default = !empty($args['default']) ? $args['default'] : '';
				$value = !empty($args['value']) ? $args['value'] : $default;
				if($value && intval($value) == 1) $sel = "checked";
				$input = '<input type="checkbox" '.$sel.' data-toggle="toggle" name="'.$args['name'].'" value="1" data-on="Yes" data-off="No">';
				return AFL($input, $args['label'], $args['desc'], $args['subtitle']);
			break;
			case 'number':
				$default = !empty($args['default']) ? $args['default'] : '';
				$value = !empty($args['value']) ? $args['value'] : $default;
				$input = '<input class="app_input" type="number" name="'.$args['name'].'" value="'.$value.'">';
				return AFL($input, $args['label'], $args['desc'], $args['subtitle']);
			break;
			case 'date':
				wp_enqueue_script('jquery-ui-datepicker');
				wp_enqueue_style('jquery.ui.theme', 'https://code.jquery.com/ui/1.11.3/themes/smoothness/jquery-ui.css' );
				$default = !empty($args['default']) ? $args['default'] : '';
				$value = !empty($args['value']) ? $args['value'] : $default;
				$input = '<input class="app_input" type="text" name="'.$args['name'].'" value="'.$value.'" id="'.$args['name'].'">';
				$input.= '
				<script>jQuery(document).ready(function($){
					$("#'.$args['name'].'").datepicker({dateFormat: "yy-mm-dd"});
				});</script>
				';
				return AFL($input, $args['label'], $args['desc'], $args['subtitle']);
			break;
			case 'media':
				$input = '
					<div class="attach-field-wrp">
						<input name="'.$args['name'].'" class="app-param" value="'.$args['value'].'" type="hidden" />
				';
				if( $args['value'] != '' ){
					$v = explode(',', $args['value']);
					foreach( $v as $n ){
						$url = admin_url('admin-ajax.php?action=app_get_thumbn&id='.$n.'&size=thumbnail');
						$url_full = admin_url('admin-ajax.php?action=app_get_thumbn&id='.$n.'&size=full');
						$input .= '<div data-id="'.$n.'" class="img-wrp"><img title="Drag to sort" src="'.$url.'" data-full-url="'.$url_full.'" alt="" /><i class="fa fa-close"></i><div class="img-title">'.basename ( get_attached_file( $n ) ).'</div></div>';
					}
				}
				$input .= '
						<div class="clear"></div>
						<a class="button app_media button-primary" data-type="'.$args['media_type'].'">Browse</a>
					</div>
				';
				return AFL($input, $args['label'], $args['desc'], $args['subtitle']);
			break;

			case 'tree':
				$data = app_json_tree($args['section'], $args['value']);
				$input = app_input_tree($args);
				return AFL($input, $args['label'], $args['desc'], $args['subtitle']);
			break;
		}
	}

	function sortArrayByArray(array $array, array $orderArray) {
	    $ordered = array();
	    foreach ($orderArray as $key) {
	        if (array_key_exists($key, $array)) {
	            $ordered[$key] = $array[$key];
	            unset($array[$key]);
	        }
	    }
	    return $ordered + $array;
	}

	function app_input_tree($args){
		$args = wp_parse_args($args, array(
			'name' => '',
			'value' => '',
			'section' => 'subjects',
		));
		$data = app_json_tree($args['section'], $args['value']);
		$input = '
			<div class="__tree_wrap tree_row dropdown" style="position:relative;" data-items=\''.$data.'\'>
				<div class="__input_wrap dropdown-toggle" data-toggle="dropdown">
					<input type="text" name="'.$args['name'].'" class="__input tagsinput">
				</div>
				<div class="dropdown-menu full-width">
					<div class="p bg-white">
						<div class="row no-gutter" style="margin-bottom:5px;">
							<div class="col-md-6">
								<button type="button" class="btn btn-default btn-sm __expand">
									<i class="fa fa-expand"></i>
								</button>
								<button type="button" class="btn btn-default btn-sm __collapse">
									<i class="fa fa-compress"></i>
								</button>
							</div>
							<div class="col-md-6">
								<input type="text" class="__tree_search form-control input-sm" placeholder="Search..." value="">
							</div>
						</div>
						<div style="max-height:260px; overflow:scroll;">
							<div class="__tree"></div>
						</div>
					</div>
				</div>
			</div>
		';
		return $input;
	}

	function app_has_duplicate($t, $title, $id = 0){
		$title = trim($title);
		$sql = "SELECT ID FROM {$t} WHERE `title`='{$title}'";
		if($id){
			$id = intval($id);
			$sql .= " AND ID <> {$id}";
		}
		return DB()->get_var($sql);
	}

	function create_doc(){
		$loc = APP_PLUGIN_DIR.'/tmp/';
		wp_mkdir_p($loc);
		$name = 'fad.docx';
		$targetFile = $loc.$name;

		$style_header = array('rtl' => true, 'bold' => true, 'size' => 18);
		$style_subheader = array('rtl' => true, 'bold' => true, 'size' => 16);
		$style_p = array('rtl' => true, 'bold' => false, 'size' => 14);
		$style_red = array('rtl' => true, 'size' => 14, 'bgColor' => 'FF0000');
		$style_grey = array('rtl' => true, 'size' => 14, 'bgColor' => 'EEEEEE');

		$phpWord = new \PhpOffice\PhpWord\PhpWord();

		$properties = $phpWord->getDocInfo();
		$properties->setCreator('Bitwize');
		$properties->setCompany('Bitwize');
		$properties->setTitle('My title');
		// $properties->setDescription('My description');
		// $properties->setCategory('My category');
		// $properties->setLastModifiedBy('My name');
		// $properties->setCreated(mktime(0, 0, 0, 3, 12, 2014));
		// $properties->setModified(mktime(0, 0, 0, 3, 14, 2014));
		// $properties->setSubject('My subject');
		// $properties->setKeywords('my, key, word');

		$phpWord->setDefaultFontName('Arial (Body CS)');
		// $phpWord->setDefaultFontSize(13);

		$section = $phpWord->addSection();

		$textrun = $section->addTextRun(array('alignment' => 'end'));
		$textrun->addText('الاستراحة اليومية', $style_header);
		$textrun->addTextBreak(3);
		$textrun->addText('راحة- break- pause- coffee break- pause cafe- lunch break- استراحة- وقت فراغ- أوقات فراغ', $style_p);
		$textrun->addTextBreak(2);
		$textrun->addText('ما هي فترة الإستراحة اليومية؟', $style_subheader);
		$textrun->addTextBreak(2);
		$textrun->addText('فترة الاستراحة اليومية أو BREAK هي الفترة التي يأخذها الأجير عند التوقف عن العمل للترفيه عن نفسه.
يمكن أن تكون فترة الإستراحة خلال وقت العمل أو بعد وقت العمل لليوم التالي للعمل.', $style_p);
		$textrun->addTextBreak(2);
		$textrun->addText('ملاحظة:', $style_subheader);
		$textrun->addTextBreak(2);
		$textrun->addText('- قد ينص النظام الداخلي للمؤسسة على فترات استراحة لعشرة دقائق كاستراحة القهوة أو استراحة الشاي (coffee break- tea time- pause café- pause the)، وهذه الأستراحات تُنظم من قبل صاحب العمل وحده وتختلف عن تلك التي نص عليها القانون.', $style_p);
		$textrun->addTextBreak();
		$textrun->addText('- لا تدخل فترة الإستراحة في حساب ساعات عمل الأجير، فالأجير الذي يعمل من الساعة 8 صباحاً إلى الساعة 5 مساءاً ويأخذ ساعة استراحة يكون قد عمل فعلياً 8 ساعات وليس 9 ساعات نظراً لأن الإستراحة لا تحسب في دوام العمل.', $style_p);
		$textrun->addTextBreak(2);
		$textrun->addText('مثلاً:', $style_subheader);
		$textrun->addTextBreak(2);
		$textrun->addText('- استراحة الغداء هي الإستراحة التي يأخذها الأجير خلال الدوام.', $style_p);
		$textrun->addTextBreak();
		$textrun->addText('- الإستراحة التي يأخذها الأجير بعد انتهاء دوام عمله يومياً للذهاب إلى المنزل إلى حين العودة في اليوم التالي إلى العمل.', $style_p);
		$textrun->addTextBreak(2);
		$textrun->addText('المادة 34 عمل', $style_red);
		$textrun->addTextBreak(2);
		$textrun->addText('دوام العمل- النظام الداخلي- عقد العمل- صاحب العمل- الأجير- حقوق الاجير وواجباته', $style_grey);

		$section->addTextBreak(4);

		$phpWord->save($targetFile, 'Word2007');

	}

	function app_modal($args=[]){
		$modal = '
			<div class="modal fade" id="app-modal" tabindex="-1" role="dialog" aria-hidden="true">
				<div class="modal-dialog modal-lg">
				    <div class="modal-content">
				      	<div class="modal-header">
				        	<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				        	<h4 class="modal-title"></h4>
				      	</div>
				      	<div class="modal-body">
				      		<div id="app-modal-loader" style="padding: 50px; text-align: center;">
				      			<i class="fa fa-refresh fa-spin"></i>
				      		</div>
				      		<div id="app-modal-body"></div>
				      	</div>
				    </div>
			  	</div>
			</div>
		';
		echo $modal;
	}
	add_action('admin_footer', 'app_modal');
