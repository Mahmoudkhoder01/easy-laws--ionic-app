<?php

class BWD_DB_Repair{
	public function __construct(){
		add_action('wp_ajax_bwd_repair_db', array($this, 'run'));
		add_action('wp_ajax_bwd_backup_db', array($this, 'backup'));
	}

	public function run(){
		if(!current_user_can('can_bitwize')) die();
		global $wpdb;
		$optimize = 2 == $_REQUEST['repair'];
		$okay = true;
		$problems = array();

		$tables = $this->get_table_list();
		$tables = array_merge( $tables, (array) apply_filters( 'tables_to_repair', array() ) );

		foreach ( $tables as $table ) {
			$check = $wpdb->get_row( "CHECK TABLE $table" );

			echo '<p>';
			if ( 'OK' == $check->Msg_text ) {
				printf( __( '%s table is okay.' ), "<code>$table</code>" );
			} else {
				printf( __( '%1$s table is not okay.<br>It is reporting the following error: %2$s. WordPress will attempt to repair this table&hellip;' ) , "<code>$table</code>", "<code>$check->Msg_text</code>" );

				$repair = $wpdb->get_row( "REPAIR TABLE $table" );

				echo '<br />--- ';
				if ( 'OK' == $check->Msg_text ) {
					printf( __( 'Successfully repaired the %s table.' ), "<code>$table</code>" );
				} else {
					echo sprintf( __( 'Failed to repair the %1$s table.<br>Error: %2$s' ), "<code>$table</code>", "<code>$check->Msg_text</code>" ) . '<br />';
					$problems[$table] = $check->Msg_text;
					$okay = false;
				}
			}

			if ( $okay && $optimize ) {
				$check = $wpdb->get_row( "ANALYZE TABLE $table" );

				echo '<br />';
				if ( 'Table is already up to date' == $check->Msg_text )  {
					printf( __( '%s table is already optimized.' ), "<code>$table</code>" );
				} else {
					$check = $wpdb->get_row( "OPTIMIZE TABLE $table" );
					echo '<br />--- ';
					if ( 'OK' == $check->Msg_text || 'Table is already up to date' == $check->Msg_text ) {
						printf( __( 'Successfully optimized the %s table.' ), "<code>$table</code>" );
					} else {
						printf( __( 'Failed to optimize the %1$s table.<br>Error: %2$s' ), "<code>$table</code>", "<code>$check->Msg_text</code>" );
					}
				}
			}
			echo '</p>';
		}

		if ( $problems ) {
			echo '<p>' . __('Some database problems could not be repaired. ') . '</p>';
			$problem_output = '';
			foreach ( $problems as $table => $problem )
				$problem_output .= "$table: $problem\n";
			echo '<p><textarea name="errors" id="errors" rows="20" cols="60">' . esc_textarea( $problem_output ) . '</textarea></p>';
		} else {
			echo '<p>' . __( 'Repairs complete.' ) . "</p>";
		}
		die();
	}

	function get_table_list($return_type='all'){
		/*
		 * @param : return_type - string : all, wp, non_wp
		 * @return all tables from db
		 */
		global $wpdb;
		$arr = array();
		$q = "SELECT table_name FROM information_schema.tables WHERE table_schema = '". DB_NAME ."'";
		$data = $wpdb->get_results($q);
		foreach ($data as $table){
			if (strpos($table->table_name, $wpdb->prefix)===0){
				$key = str_replace($wpdb->prefix, '', $table->table_name);
			} else {
				$key = $table->table_name;
			}
			$arr[$key] = $table->table_name;
		}

		if ($return_type!='all'){
			$tables_wp = $wpdb->tables('all');
			$native_wp = array();
			foreach ($tables_wp as $k=>$v){
				if (strpos($v, $wpdb->prefix)===0){
					$key = str_replace($wpdb->prefix, '', $v);
				} else {
					$key = $v;
				}
				$native_wp[$key] = $v;
			}

			if ($return_type=='wp'){
				return $native_wp;
			} elseif ($return_type=='non_wp'){
				return array_diff($arr, $native_wp);
			}
		}
		return $arr;
	}

	function backup(){
		if(!current_user_can('can_bitwize')) die();
		global $wpdb;
		$tables = $this->get_table_list();
		$return = '';
		foreach($tables as $table){
			$num_fields = 0;
			$result = $wpdb->get_results( "SELECT * FROM $table", ARRAY_N);
			$fields = $wpdb->get_results( "SHOW COLUMNS FROM $table");
			$num_fields = (int) count($fields);

			$return.= 'DROP TABLE IF EXISTS `'.$table.'`;';
			$row2 = $wpdb->get_row("SHOW CREATE TABLE $table", ARRAY_N);
			$return.= "\n\n".$row2[1].";\n\n";


			foreach($result as $row){
				$return.= 'INSERT INTO `'.$table.'` VALUES(';

				for($j=0; $j < $num_fields; $j++) {
					$row[$j] = addslashes($row[$j]);
					$row[$j] = str_replace("\n","\\n",$row[$j]);
					if (isset($row[$j])) { $return.= '"'.$row[$j].'"' ; } else { $return.= '""'; }
					if ($j < ($num_fields-1)) { $return.= ','; }
				}

				$return.= ");\n";
			}

			$return.="\n\n\n";
		}

		$sitename = strtolower( $_SERVER['SERVER_NAME'] );
		if ( substr( $sitename, 0, 4 ) == 'www.' ) $sitename = substr( $sitename, 4 );
		$sitename = str_ireplace('.', '-', $sitename);

		$path_info = wp_upload_dir();
		$dir = $path_info['basedir'] . '/backup-db';
		$url = $path_info['baseurl'] . '/backup-db';

		$name = $sitename.'-db-backup-'.date('M-d-Y').'-'.time();
		$fname = $dir.'/'.$name;
		$furl  = $url.'/'.$name;

		wp_mkdir_p($dir);
		$handle = fopen( $fname.'.sql', 'w+' );
		fwrite( $handle, $return );
		fclose( $handle );

		if (class_exists('ZipArchive')) {
	        $zip = new ZipArchive;
	        $zip->open($fname . ".zip", ZipArchive::CREATE);
	        $zip->addFile($fname.'.sql', $name.'.sql');
	        $zip->close();
	        @unlink($fname.".sql");
	        echo '<a href="'.$furl.'.zip" target="_blank">'.$name.'.zip</a>';
	    } else {
	    	echo '<a href="'.$furl.'.sql" target="_blank">'.$name.'.sql</a>';
	    }

		die();
	}
}

$GLOBALS['BWD_DB_Repair'] = new BWD_DB_Repair;
