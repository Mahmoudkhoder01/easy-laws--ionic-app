<?php
class App_Hooks
{
	public function __construct(){
		$prx = DB()->prefix.'app_';
		$this->prx = $prx;
		$this->t_questions = $prx.'questions';
		$this->t_subjects = $prx.'subjects';
		$this->t_user_likes = $prx.'user_likes';
		$this->t_devices = $prx.'devices';

		add_action('app_add_question',  [$this, 'add_question'],  10, 2);
        add_action('app_edit_question', [$this, 'edit_question'], 10, 2);
        
        add_action('admin_init', [$this, 'strip_all_questions']);

        add_action('admin_head', function(){
            echo "<style>
            @media(max-width: 769px){
                #wp-admin-bar-site-name{
                    display: none !important;
                }
            }</style>";
        });
	}

	function add_question($id = null){
        update_option('cats_need_recount', 'yes');
        question_strip_arabic($id);

	}

	function edit_question($id = null){
        update_option('cats_need_recount', 'yes');
        question_strip_arabic($id);
    }
    

    function strip_all_questions(){
        if(!__rq('strip_all_questions')) return false;
        $T = $this->t_questions;
        $rows = DB()->get_col("SELECT ID from $T");
        foreach($rows as $row){
            question_strip_arabic($row);
        }
    }

	

}
new App_Hooks;
