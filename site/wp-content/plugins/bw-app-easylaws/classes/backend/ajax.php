<?php
class App_Admin_Ajax
{
	public function __construct() {
        $this->assign('app-update-menu-order', 'update_menu_order' );
        $this->assign('create_from_select', 'create_from_select' );
        $this->assign('app_get_thumbn', 'app_get_thumbn' );

        $this->assign('app_question_logs', 'app_question_logs');

        $this->assign('app_create_doc', 'create_doc');
        $this->assign('ref_tree_count', 'ref_tree_count');
        $this->assign('dyk_filter', 'dyk_filter');
        $this->assign('dyk_count_by_cat', 'dyk_count_by_cat');
        
        $this->assign('app_subject_notes', 'app_subject_notes');
    }

    public function assign($handle, $func){
        add_action('wp_ajax_'.$handle, array($this, $func));
    }

    function app_subject_notes(){
        $ID = app_rq('ID', true);
        if(!$ID) die();
        $note = app_rq('note');
        $t = PRX.'subject_notes';
        $dbid = DB()->get_var("SELECT ID FROM $t WHERE subject_id=$ID");
        if($dbid){
            DB()->update($t, ['note' => $note, 'subject_id' => $ID, 'date_edited' => time()], ['ID' => $dbid]);
        } else {
            DB()->insert($t, ['note' => $note, 'subject_id' => $ID, 'date_created' => time()]);
        }
        die();
    }

    function dyk_count_by_cat(){
        $ID = app_rq('ID', true);
        $t = DB()->prefix.'app_questions';
		$sent = get_option('app_did_you_know_sent');
		$sent = $sent ? $sent : [];
		$sent_ids = implode(',', $sent);

        $query = "SELECT COUNT(ID) from {$t} WHERE status=1 AND CHAR_LENGTH(did_you_know) > 10 AND FIND_IN_SET({$ID}, categories)>0";

        $total = DB()->get_var($query);
        
        $sent = 0;
        if($sent_ids) {
            $query .= " AND ID IN ({$sent_ids})";
            $sent = DB()->get_var($query);
        }
        echo "DYKs: $total | SENT: $sent";
        die();
    }

    function dyk_filter(){
        $ids = app_rq('ids');
        update_option('app_did_you_know_filter', $ids);
        die();
    }

    function ref_tree_count(){
        $id = app_rq('id', true);
        if(!$id) die('NO ID');
        $n = app_count_ref_posts($id, true);
        echo $n;
        die();
    }

    function app_question_logs(){
        $t = DB()->prefix.'app_question_logs';
        $id = app_rq('id', true);
        if(!$id) die('NO ID');
        $results = DB()->get_results("SELECT * FROM $t WHERE question_id={$id} ORDER BY date_created DESC");
        if(!$results) die('No records found');
        echo '<table class="table striped"><thead><tr><th>Date</th><th>Operation</th></thead><tbody>';
        foreach($results as $r){
            echo '<tr><td>'.date('F j, Y @ H:i:s', $r->date_created).'</td><td>'.$r->details.'</td></tr>';
        }
        echo '</tbody></table>';
        die();
    }

    function update_menu_order(){
        $t = DB()->prefix.'app_questions';
        parse_str($_POST['order'], $data);
        if ( is_array($data) ) {
            $id_arr = array();
            foreach( $data as $key => $values ) {
                foreach( $values as $position => $id ) {
                    $id_arr[] = $id;
                }
            }
            // $menu_order_arr = array();
            // foreach( $id_arr as $key => $id ) {
            //     $results = DB()->get_results( "SELECT menu_order FROM $t WHERE ID = ".$id );
            //     foreach( $results as $result ) {
            //         $menu_order_arr[] = $result->menu_order;
            //     }
            // }
            // sort($menu_order_arr);
            // AH()->print_r($menu_order_arr);
            foreach( $data as $key => $values ) {
                foreach( $values as $position => $id ) {
                    $position = intval($position) + 1;
                    DB()->update( $t, array( 'menu_order' => $position ), array( 'ID' => $id ) );
                }
            }
        }
        die();
    }

    function create_from_select(){
        $title = (!empty($_REQUEST['title'])) ? $_REQUEST['title'] : '';
        $table = (!empty($_REQUEST['table'])) ? $_REQUEST['table'] : '';

        if($title && $table){
            $t = DB()->prefix.'app_'.$table;

            $dup = app_has_duplicate($t, $title);
            if($dup){
                echo json_encode(array('value' => $dup, 'text' => $title));
                die(); // bail after duplicate
            }

            $insert_vals = array();
            $insert_vals['title'] = $title;
            $insert_vals['date_created'] = time();
            $insert_vals['author'] = get_current_user_id();
            if (DB()->insert($t, AH()->stripslashes($insert_vals)) ){
                $rid = DB()->insert_id;
                echo json_encode(array('value' => $rid, 'text' => $title));
            } else {
                echo json_encode(array('value' => '', 'text' => 'ERROR INSERTING'));
            }
        } else {
            echo json_encode(array('value' => '', 'text' => 'ERROR'));
        }
        die();
    }

    function app_get_thumbn( $abc ){
        $assets_url = plugins_url('assets', __FILE__);

        $imid = !empty( $_GET['id'] ) ? $_GET['id'] : '';

        if( $imid == '' || $imid == 'undefined' ){
            header( 'location: '.$assets_url.'/img/get_start.jpg' );
            exit;
        }

        if( $imid == 'featured_image' ){}

        $img = wp_get_attachment_image_src( esc_attr( $_GET['id'] ), (!empty( $_GET['size'] )?esc_attr( $_GET['size'] ):'medium') );

        if( !empty( $img[0] ) ){
            header( 'location: '.$img[0] );
        } else {
            header( 'location: '.$assets_url.'/img/video.jpg' );
        }
    }

    function xml($str){
        $str = esc_sql($str);
        $str = str_replace(['’', '‘', "'", '"'], ['', '', '', ''], $str);
        $str = strip_tags($str);
        $str = AH()->stripslashes($str);
        $str = htmlentities($str);
        $xml = array('&#34;','&#38;','&#38;','&#60;','&#62;','&#160;','&#161;','&#162;','&#163;','&#164;','&#165;','&#166;','&#167;','&#168;','&#169;','&#170;','&#171;','&#172;','&#173;','&#174;','&#175;','&#176;','&#177;','&#178;','&#179;','&#180;','&#181;','&#182;','&#183;','&#184;','&#185;','&#186;','&#187;','&#188;','&#189;','&#190;','&#191;','&#192;','&#193;','&#194;','&#195;','&#196;','&#197;','&#198;','&#199;','&#200;','&#201;','&#202;','&#203;','&#204;','&#205;','&#206;','&#207;','&#208;','&#209;','&#210;','&#211;','&#212;','&#213;','&#214;','&#215;','&#216;','&#217;','&#218;','&#219;','&#220;','&#221;','&#222;','&#223;','&#224;','&#225;','&#226;','&#227;','&#228;','&#229;','&#230;','&#231;','&#232;','&#233;','&#234;','&#235;','&#236;','&#237;','&#238;','&#239;','&#240;','&#241;','&#242;','&#243;','&#244;','&#245;','&#246;','&#247;','&#248;','&#249;','&#250;','&#251;','&#252;','&#253;','&#254;','&#255;');
        $html = array('&quot;','&amp;','&amp;','&lt;','&gt;','&nbsp;','&iexcl;','&cent;','&pound;','&curren;','&yen;','&brvbar;','&sect;','&uml;','&copy;','&ordf;','&laquo;','&not;','&shy;','&reg;','&macr;','&deg;','&plusmn;','&sup2;','&sup3;','&acute;','&micro;','&para;','&middot;','&cedil;','&sup1;','&ordm;','&raquo;','&frac14;','&frac12;','&frac34;','&iquest;','&Agrave;','&Aacute;','&Acirc;','&Atilde;','&Auml;','&Aring;','&AElig;','&Ccedil;','&Egrave;','&Eacute;','&Ecirc;','&Euml;','&Igrave;','&Iacute;','&Icirc;','&Iuml;','&ETH;','&Ntilde;','&Ograve;','&Oacute;','&Ocirc;','&Otilde;','&Ouml;','&times;','&Oslash;','&Ugrave;','&Uacute;','&Ucirc;','&Uuml;','&Yacute;','&THORN;','&szlig;','&agrave;','&aacute;','&acirc;','&atilde;','&auml;','&aring;','&aelig;','&ccedil;','&egrave;','&eacute;','&ecirc;','&euml;','&igrave;','&iacute;','&icirc;','&iuml;','&eth;','&ntilde;','&ograve;','&oacute;','&ocirc;','&otilde;','&ouml;','&divide;','&oslash;','&ugrave;','&uacute;','&ucirc;','&uuml;','&yacute;','&thorn;','&yuml;');
        // $str = str_replace($html,$xml,$str);
        $str = str_ireplace($html,$xml,$str);
        return $str;
    }

    public function xml_deep($value) {
        if ( is_array($value) ) {
            $value = array_map( array($this, 'xml'), $value);
        } elseif ( is_object($value) ) {
            $vars = get_object_vars( $value );
            foreach ($vars as $key=>$data) {
                $value->{$key} = $this->xml( $data );
            }
        } elseif ( is_string( $value ) ) {
            $value = $this->xml($value);
        }
        return $value;
    }


    function create_doc(){
        $id = !empty( $_REQUEST['id'] ) ? intval($_REQUEST['id']) : '';
        if(!$id) die('Error');

        $folder_name = '/generated_documents/';
        $upload_dir = wp_upload_dir();
        $base_dir = $upload_dir['basedir'];
        $base_url = $upload_dir['baseurl'];
        $folder = $base_dir . $folder_name;
        $folder_url = $base_url . $folder_name;
        wp_mkdir_p($folder);

        $subject = subject_row_by_id($id);
        $subject_keywords = get_keywords_by_line($subject->keywords);
        $questions = questions_by_subject($id);

        // AH()->print_r($subjects);
        // AH()->print_r($question);

        $name = $subject->title.'-'.date('d-m-Y-H-i-s').'.docx';
        $targetFile = $folder.$name;
        $targetURL = $folder_url.$name;

        $style_header = array('rtl' => true, 'bold' => true, 'size' => 18);
        $style_subheader = array('rtl' => true, 'bold' => true, 'size' => 16);
        $style_p = array('rtl' => true, 'bold' => false, 'size' => 14);
        $style_red = array('rtl' => true, 'size' => 14, 'bgColor' => 'FF0000');
        $style_grey = array('rtl' => true, 'size' => 14, 'bgColor' => 'CCCCCC');

        // PhpOffice\PhpWord\Settings::setOutputEscapingEnabled(true);
        $phpWord = new \PhpOffice\PhpWord\PhpWord();

        $properties = $phpWord->getDocInfo();
        $properties->setCreator('Bitwize');
        $properties->setCompany('Bitwize');
        // $properties->setTitle($subject->title);
        // $properties->setDescription($subject->details);
        // $properties->setCategory('My category');
        // $properties->setLastModifiedBy('My name');
        $properties->setCreated(time());
        $properties->setModified(time());
        // $properties->setSubject($subject->title);
        // $properties->setKeywords($subject_keywords);

        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(14);

        $section = $phpWord->addSection();

        $textrun = $section->addTextRun(array('alignment' => 'end'));
        $textrun->addText($this->xml( $subject->title ), $style_header);
        $textrun->addTextBreak(3);
        if($subject->details){
            $textrun->addText($this->xml( $subject->details ), $style_p);
            $textrun->addTextBreak(2);
        }
        if($subject_keywords){
            $textrun->addText($this->xml( $subject_keywords ), $style_p);
            $textrun->addTextBreak(2);
        }
        $textrun->addTextBreak(2);

        if($questions){
            foreach($questions as $q){
                $textrun->addText($this->xml( $q->title ), $style_subheader);
                $textrun->addTextBreak(2);

                if($q->details){
                    $textrun->addText($this->xml( $q->details), $style_p);
                    $textrun->addTextBreak(2);
                }

                if($q->notes){
                    $textrun->addText('ملاحظة:', $style_subheader);
                    $textrun->addTextBreak(2);

                    $notes = explode('*||*', $q->notes );
                    foreach($notes as $note){
                        $textrun->addText($this->xml( $note ), $style_p);
                        $textrun->addTextBreak(2);
                    }
                }

                if($q->examples){
                    $textrun->addText('مثلاً:', $style_subheader);
                    $textrun->addTextBreak(2);

                    $examples = explode('*||*', $q->examples );
                    foreach($examples as $example){
                        $textrun->addText($this->xml( $example ), $style_p);
                        $textrun->addTextBreak(2);
                    }
                }

                if($q->references){
                    $refs = get_references_by_line($q->references);
                    $textrun->addText($this->xml( $refs ), $style_red);
                    $textrun->addTextBreak(2);
                }

                if($q->tags){
                    $tags = get_tags_by_line($q->tags);
                    $textrun->addText($this->xml( $tags ), $style_grey);
                    $textrun->addTextBreak(2);
                }

                $textrun->addTextBreak(4);
            }
        }
        ob_clean();
        flush();
        $phpWord->save($targetFile, 'Word2007');
        echo $targetURL;

        // header('Content-Description: File Transfer');
        // header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        // header('Content-Disposition: attachment;filename="' . $name . '"');
        // header('Content-Transfer-Encoding: binary');
        // header('Expires: 0');
        // header('Cache-Control: must-revalidate');
        // header('Pragma: public');
        // header('Content-Length: ' . filesize($targetFile));
        // ob_clean();
        // flush();
        // $phpWord->save('php://output', 'Word2007');
        // readfile($targetFile);

        die();
    }
}
new App_Admin_Ajax;
