<?php
class AppFront_Ajax {

	public function __construct(){
		$this->prx = DB()->prefix.'app_';
		$this->assign('definition_modal');
        $this->assign('reference_modal');
        $this->assign('login');
	}

	function assign($slug){
		add_action( 'wp_ajax_'.$slug, [$this, $slug] );
		add_action( 'wp_ajax_nopriv_'.$slug, [$this, $slug] );
    }

    function login(){
        $post = $_POST;
        unset($post['action']);
        $res = wapi()->login($post);
        if($res['valid'] == 'YES'){
            echo 'OK';
        } else {
            echo $res['reason'];
        }
        die();
    }
    
	function definition_modal(){
        $id = intval(app_rq('id'));
        $text = app_rq('text');
        if($id){
            $r = wapi()->get_definition($id)['results'];
            echo '
                <h2>'.$text.'</h2>
                <div>'.$r->details.'</div>
            ';
        }
		die();
	}

	function reference_modal(){
        $id = intval(app_rq('id'));
        if($id){
            $r = wapi()->get_reference($id)['results'];
            if($r->parent){
                echo '<h4>'.$r->parent.'</h4>';
            }
            echo '
                <h2>'.$r->title.'</h2>
                <div>'.$r->details.'</div>
            ';
        }
		die();
	}
}

new AppFront_Ajax;
