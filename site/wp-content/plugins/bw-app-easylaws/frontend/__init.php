<?php
final class AppFrontend
{
    private static $_instance;
    public static function instance() {
        if (is_null(self::$_instance)) self::$_instance = new self();
        return self::$_instance;
    }

    public function init() {
		$this->v = AH()->is_dev() ? uniqid() : 7;
		$this->URL = plugins_url('', __FILE__);
		$this->includes();
		
		add_action('wp_enqueue_scripts', array($this, 'register_assets'), 20);
		add_action('__app_header', function(){include __DIR__ . '/theme/header.php';});
		add_action('__app_footer', function(){include __DIR__ . '/theme/footer.php';});
		add_action('__app', function(){
			include __DIR__ . '/router.php';
		});
		add_action('init', function(){
			if(app_rq('_action') == 'login') $this->login();
			if(app_rq('_action') == 'signup') $this->signup();
		});
    }

    function includes(){
    	$files = [
    		'/ajax.php',
    		'/api.php',
			'/paginate.php',
			'/seo.php'
    	];
    	foreach($files as $file){
    		require_once(__DIR__.$file);
    	}
	}

	function login(){
		$data = wapi()->login([
            'email' => app_rq('email'),
            'password' => app_rq('password')
        ]);
        if($data['valid'] == 'YES'){
        	$name = $data['results']->name;
            $url = add_query_arg(['msg' => urlencode('أهلاً بك، '.$name)]);
        } else {
        	$url = add_query_arg(['error' => urlencode($data['reason'])]);
        }
        wp_redirect( $url );
        exit;
	}

	function signup(){
		$data = wapi()->create_user([
			'name' => app_rq('_name'),
			'gender' => app_rq('_gender'),
			'phone' => app_rq('_phone'),
			'email' => app_rq('_email'),
			'password' => app_rq('_password'),
		]);
        if($data['valid'] == 'YES'){
        	// $name = $data['results']->name;
            $url = add_query_arg(['msg' => urlencode('لقد أرسلنا لك رسالة بالبريد الكتروني بما في ذلك رابط التفعيل لحسابك. فعل حسابك ثم سجل الدخول.')]);
        } else {
        	$url = add_query_arg(['error' => urlencode($data['reason'])]);
        }
        wp_redirect( $url );
        exit;
	}

    public function register_assets(){
    	if(is_admin()) return;
    	wp_deregister_script('jquery');
    	wp_enqueue_script('jquery', 'https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js');

		if(AH()->is_dev()){
			$styles = [
				'lib' => $this->assets('/css/app.lib.css'),
				'app' => $this->assets('/css/__app.css'),
			];

			$scripts = [
				'lib' => $this->assets('/js/app.lib.js'),
				'app' => $this->assets('/js/__app.js'),
			];
		} else {
			$styles = [
				'app' => $this->assets('/css/app.min.css'),
			];

			$scripts = [
				'app' => $this->assets('/js/app.min.js'),
			];
		}

		foreach($styles as $k => $v){
			wp_enqueue_style($k, $v);
		}

		foreach($scripts as $k => $v){
			wp_enqueue_script($k, $v, ['jquery'], '', true);
		}
		wp_localize_script('app', 'VARS', [
            'ajaxurl'  => admin_url('admin-ajax.php'),
            'assetsurl' => $this->URL . '/assets',
        ]);
    }

    public function assets($file){
        global $APP;
        $u = $this->URL;
        if($APP['forcessl'] && !AH()->is_dev()) $u = str_replace('http:', 'https:', $u);
        return $u.'/assets'.$file.'?_='.$this->v;
    }

    function body_class(){
		$action = get_query_var('action');
		$action = $action ? trim(strtolower($action)) : '';
		switch($action){
			case 'home': 
			case 'subjects':
				$o = 'bg-static'; break;
			default: 
				$o = 'bg-app'; break;
		}
		echo $o;
	}

    function paginate($total, $page, $_class = ''){
    	$total = intval($total);
    	$page = intval($page);
    	$page = $page ? $page : 1;
		if($total == 1) return false;
		$prev_link = add_query_arg(['page' => ($page - 1)]);
		$next_link = add_query_arg(['page' => ($page + 1)]);
		$prev = $page > 1 ? '<li class="page-item"><a class="page-link" href="'.$prev_link.'">&laquo;</a></li>' : '';
		$next = $page < $total ? '<li class="page-item"><a class="page-link" href="'.$next_link.'">&raquo;</a></li>' : '';
		$pages = '';
		for($i = 1; $i <= $total; $i++){
			$link = add_query_arg(['page' => $i]);
			$class = $i == $page ? 'active' : '';
			$pages .= '<li class="page-item '.$class.'"><a class="page-link" href="'.$link.'">'.$i.'</a></li>';
		}
		return '<ul class = "pagination '.$_class.'">'.$prev.$pages.$next.'</ul>';
	}

	function subjects_carousel($args = []){
		$args = extract(wp_parse_args($args, [
			'subjects' => [],
			'title' => '',
			'subtitle' => '',
			'before' => '',
			'after' => '',
			'class' => '',
		]));

		$title = $title ? "<h3>$title</h3>" : ''; 
		$subtitle = $subtitle ? "<h5>$subtitle</h5>" : ''; 

		$items = $o = '';
		if($subjects){
			foreach($subjects as $s){
				$items .= '
					<div class="item subject-item"><a href="'.site_url('subject/'.$s->ID).'">
						<div class="img shadow" style="background: '.$s->color.'">
							<img src="'.$s->image.'" alt="" />
						</div>
						<div class="title">'.$s->title.'</div>
					</a></div>
				';
			}

			$o = $before.'
				<div class="container">
					<div class="text-center mb-3">'.$title.$subtitle.'</div>
					<div class="carousel owl-carousel owl-theme '.$class.'">'.$items.'</div>
				</div>
			'.$after;
		}

		echo $o;
	}

	function questions_carousel($args = []){
		$args = extract(wp_parse_args($args, [
			'questions' => [],
			'title' => '',
			'subtitle' => '',
			'before' => '',
			'after' => '',
			'class' => '',
		]));

		$title = $title ? "<h3>$title</h3>" : ''; 
		$subtitle = $subtitle ? "<h5>$subtitle</h5>" : ''; 

		$items = $o = '';
		if($questions){
			foreach($questions as $s){
				$items .= '
					<div class="item carousel-question-item shadow mb-3" style="background: '.$s->color.'"><a href="'.site_url('question/'.$s->ID).'">
						<div class="title">'.$s->title.'</div>
					</a></div>
				';
			}

			$o = $before.'
				<div class="container">
					<div class="text-center mb-3">'.$title.$subtitle.'</div>
					<div class="carousel owl-carousel owl-theme '.$class.'" data-items-sm="1" data-items-md="2" data-items-lg="3">'.$items.'</div>
				</div>
			'.$after;
		}

		echo $o;
	}

	function subjects_list($subjects = []){
		if(!$subjects) return '';
		$o = '';
		foreach($subjects as $i){
			$item = $i['data'];
			$o .= '
				<div class="col-6 col-lg-2">
					<div class="subject-item mb-5"><a href="'.site_url('subject/'.$item->ID).'">
						<div class="img shadow" style="background: '.$item->color.'">
							<img src="'.$item->image.'" alt="" />
						</div>
						<div class="title">'. $item->title.'</div>
					</a></div>
				</div>
			';
		}
		echo '<div class="row align-items-center">'.$o.'</div>';
	}

	function questions_list($args = []){
		$args = wp_parse_args($args, [
			'cat' => '',
			'tag' => '',
			'order_by' => '',
			's' => '',
			'is_search' => false
		]);
		$q = wapi()->get_questions($args);

		$items = '';
		foreach($q['results'] as $i){
			$items .= '
				<div class="col-12 col-lg-6"><div class="question-item bg-light">
					<div class="question-item-head"><a href="'.site_url('question/'.$i->ID).'">
						<div class="text-center">
							<div class="icon"><i class="fa fa-question-circle-o"></i></div>
							<div class="title">'. $i->title.'</div>
							<div class="excerpt">'. $i->excerpt.'</div>
						</div>
					</a></div>
					<div class="qactions">
						<div>
							<i class="fa fa-thumbs-o-up"></i>
							<span>أصوات</span>
							<span class="num">'.$i->votes.'</span>
						</div>

						<div>
							<i class="fa fa-comments-o"></i>
							<span>ردود</span>
							<span class="num">'.$i->comments.'</span>
						</div>
					</div>
				</div></div>
			';


		}

		$pg = new App_Front_Paginate(
			$q['total'], 
			$q['per_page'], 
			app_rq('page') ? intval(app_rq('page')) : 1, 
			'',
			'justify-content-center bg-light p-3'
		);

		if($args['is_search']){
			echo '
				<div class="bg-light py-4 text-center">
					<div class="container position-relative">
						<h2><span class="text-muted">ابحـــث:</span> '.app_rq('q').'</h2>
						<h5>عدد النتائج: '.$q['total'].'</h5>
					</div>
				</div>

				<div class="bg-white py-4 text-center">
					<div class="container">
			';

			if($q['subjects']){
				echo $this->subjects_list($q['subjects']);
			}
		}

		echo '
			<div class="row align-items-center row-eq-height">'.$items.'</div>
		'.$pg->toHtml();

		if($args['is_search']){
			echo '</div></div>';
		}
	}

}

function app_f() {return AppFrontend::instance();}
$GLOBALS['app_f'] = app_f();
app_f()->init();
