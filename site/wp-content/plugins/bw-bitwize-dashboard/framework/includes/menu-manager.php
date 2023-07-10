<?php
if(!class_exists('BW_Menu_Manager')):
class BW_Menu_Manager{

    public function __construct(){
        $this->option = 'bw_menu_man';
        $this->slug = 'bw-menu-man';
        $this->url = 'admin.php?page='.$this->slug;
        $this->tab = isset($_REQUEST['tab']) ? $_REQUEST['tab'] : 'menu';
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_menu', array($this, 'run'), 99999);
    }

    function admin_menu(){
        add_submenu_page(NULL, 'Menu+', 'Menu+', 'can_bitwize', $this->slug, array($this, 'page'));
    }

    function tabs(){
        echo '<h2 class="nav-tab-wrapper">';
        echo '<a href="'.$this->url.'&tab=menu" class="nav-tab '.(($this->tab == 'menu') ? 'nav-tab-active' : '').'">Menu</a>';
        echo '<a href="'.$this->url.'&tab=dashboard" class="nav-tab '.(($this->tab == 'dashboard') ? 'nav-tab-active' : '').'">Dashboard</a>';
        echo '</h2>';
    }

    function run(){
        $m = $s = array();
        $op = get_option($this->option);
        if($op){
            $m = $op['menu'];
            $s = $op['submenu'];
            if(!current_user_can('can_bitwize')){
                foreach($m as $k => $v){
                    remove_menu_page($v);
                }

                foreach($s as $k => $v){
                    $v = explode('*|*', $v);
                    remove_submenu_page($v[0], $v[1]);
                }
            }
        }
    }

    function page(){
        global $menu, $submenu;

        echo '
            <script src="https://npmcdn.com/masonry-layout@4.1/dist/masonry.pkgd.min.js"></script>
            <script>
            jQuery(document).ready(function($){
                $(".grid").masonry({
                    itemSelector: ".grid-item",
                    columnWidth: 300,
                    gutter: 10
                });
            });
            </script>
            <style>
                .grid {position: relative; margin-bottom: 30px;}
                .grid-item { width: 300px; margin-bottom: 10px;}
                .grid-item > div {background:#e3e8eb; padding:10px;}
            </style>
        ';

        echo '<div class="wrap"><div class="grid">';
        echo '<h2>Menu / Dashboard Manager</h2>';
        // $this->tabs();
        echo '<h4>Select Menu items to disable for administrators</h4>';

        if(isset($_POST['action']) && $_POST['action'] == 'bw_menu_man_update'){
            echo '<div class="updated"><h3>Menu updated successfully</h3></div>';
            $bwmenu = $_POST['bw_menu_man'];
            $bwsubmenu = $_POST['bw_submenu_man'];

            $val = array('menu' => $bwmenu, 'submenu' => $bwsubmenu);
            update_option($this->option, $val);
        }

        $m = $s = array();
        $op = get_option($this->option);
        if($op){
            $m = $op['menu'];
            $s = $op['submenu'];
        }

        echo '<form action="" method="post"><div class="grid">';
            echo '<input type="hidden" name="action" value="bw_menu_man_update">';

            $i =0;
            foreach($menu as $k => $v){

                // if($v[0] && $v[1] !== 'can_bitwize') {
                if($v[0]) {
                    $i++;
                    echo '<div class="grid-item"><div>';
                    $mchecked = '';
                    if(in_array($v[2], $m)) $mchecked = 'checked="checked"';
                    echo '
                        <p style="margin-top:0;"><label>
                            <input type="checkbox" value="'.$v[2].'" name="bw_menu_man[]" '.$mchecked.'>
                            <strong>'.$v[0].'</strong>
                        </label></p>
                    ';

                    if(isset($submenu[$v[2]])){
                        foreach($submenu[$v[2]] as $key => $val){
                            $schecked = '';
                            if(in_array($v[2].'*|*'.$val[2], $s)) $schecked = 'checked="checked"';
                            if($val[0] && $val[1] !== 'can_bitwize') {
                                echo '
                                <p style="padding:0 50px; margin-bottom:0;"><label>
                                <input type="checkbox" value="'.$v[2].'*|*'.$val[2].'" name="bw_submenu_man[]" '.$schecked.'>
                                '.$val[0].'
                                </label></p>
                            ';
                            }
                        }
                    }
                    echo '</div></div>';
                }
            }

            echo '</div>'; // end .grid

            echo '<div style="clear:both;"></div>';
            echo '<input type="submit" name="submit" value="Submit" class="button">';

        echo '</form>';
        echo '<div style="clear:both;"></div>';
        echo '</div>';
    }

}

endif;

new BW_Menu_Manager;
