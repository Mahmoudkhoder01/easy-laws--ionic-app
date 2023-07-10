<?php

class App_Dashboard_Widgets
{

	public function __construct(){
		add_action('wp_dashboard_setup', array($this, 'dashboard_setup'), 9);
		// add_action('welcome_panel',  array($this, 'monthly_chart'));

		add_action('wp_ajax_subjects_dash', array($this, 'subjects'));
		add_action('wp_ajax_stats_dash', array($this, 'stats'));
	}

	public function dashboard_setup(){
		global $wp_meta_boxes;
		remove_meta_box( 'dashboard_activity', 'dashboard', 'normal');
		if(current_user_can('manage_options')){
			wp_add_dashboard_widget('fun_stats', '<i class="fa fa-bar-chart-o"></i> Stats', array($this, 'fun_stats_dash'));

			wp_add_dashboard_widget('subjects', '<i class="fa fa-bars"></i> Top Subjects', array($this, 'subjects_dash'));
		}
	}

	function subjects(){
		// echo '<pre>'; print_r(app_notify_admins_emails()); echo '</pre>';
		$prx = DB()->prefix.'app_';
		$results = DB()->get_results("SELECT * FROM {$prx}subjects WHERE parent>0 ORDER BY posts_count DESC LIMIT 10");
		echo '<style>
			.arabic .list-group-item{
				direction: rtl; text-align: right;
			}
			.arabic .list-group-item>.badge{
				float: left;
			}
		</style>';
		echo '<ul class="list-group arabic">';

		foreach($results as $o){
			if($o->posts_count < 1) continue;
			echo '<li class="list-group-item">';
				// echo $o->title.': ';
				echo implode(' <i class="fa fa-caret-left"></i> ', get_subject_ancestors($o->ID));
				echo '<span class="badge">'.$o->posts_count.'</span>';
			echo '</li>';
		}
		echo '</ul>';
		die();
	}

	function subjects_dash(){
		?>
		<div id="subjects_dash"><i class="fa fa-circle-o-notch fa-spin"></i></div>
		<script type="text/javascript">jQuery(document).ready(function($){
			$.post(ajaxurl,{action:'subjects_dash'}, function(data){
				$("#subjects_dash").html(data);
			});
		});</script>
		<?php
	}

	function fun_stats_dash(){
		?>
		<div id="fun_stats_dash_dash"><i class="fa fa-circle-o-notch fa-spin"></i></div>
		<script type="text/javascript">jQuery(document).ready(function($){
			$.post(ajaxurl,{action:'stats_dash'}, function(data){
				$("#fun_stats_dash_dash").html(data);
			});
		});</script>
		<?php
	}

	function examples_number(){
		$t = DB()->prefix.'app_questions';
		$examples = DB()->get_col("SELECT examples from $t WHERE trashed=0");
		$num = 0;
		foreach($examples as $ex){
			$num = $num + app_repeat_count($ex);
		}
		return $num;
	}

	function notes_number(){
		$t = DB()->prefix.'app_questions';
		$notes = DB()->get_col("SELECT notes from $t WHERE trashed=0");
		$num = 0;
		foreach($notes as $note){
			$num = $num + app_repeat_count($note);
		}
		return $num;
	}

	function stats(){
		$prx = DB()->prefix.'app_';
		$questions = DB()->get_var("SELECT COUNT(*) FROM {$prx}questions");
		$questions_active = DB()->get_var("SELECT COUNT(*) FROM {$prx}questions WHERE status=1");
		$questions_corrected = DB()->get_var("SELECT COUNT(*) FROM {$prx}questions WHERE status=2");
		$questions_pending = DB()->get_var("SELECT COUNT(*) FROM {$prx}questions WHERE status=0");

		$subjects = DB()->get_var("SELECT COUNT(*) FROM {$prx}subjects");
		$subjects_root = DB()->get_var("SELECT COUNT(*) FROM {$prx}subjects WHERE parent=0");

		$tags = DB()->get_var("SELECT COUNT(*) FROM {$prx}tags");
		$keywords = DB()->get_var("SELECT COUNT(*) FROM {$prx}keywords");
		$definitions = DB()->get_var("SELECT COUNT(*) FROM {$prx}definitions");
		$references = DB()->get_var("SELECT COUNT(*) FROM {$prx}references");


		$upload_dir  = wp_upload_dir();
		$dir_info    = $this->get_dir_size( $upload_dir['basedir'] );
		$attachments = wp_count_posts( 'attachment' );
		$media_items = $attachments->inherit;
		$media_files = $dir_info['count'];
		$media_size  = $this->format_dir_size( $dir_info['size'] );

		$url = [
			'questions' => admin_url('admin.php?page=app-questions'),
			'questions_active' => admin_url('admin.php?page=app-questions&app_filter_status=active'),
			'questions_pending' => admin_url('admin.php?page=app-questions&app_filter_status=pending'),
			'questions_corrected' => admin_url('admin.php?page=app-questions&app_filter_status=corrected'),
			'subjects' => admin_url('admin.php?page=app-subjects'),
			'tags' => admin_url('admin.php?page=app-tags'),
			'keywords' => admin_url('admin.php?page=app-keywords'),
			'definitions' => admin_url('admin.php?page=app-definitions'),
			'references' => admin_url('admin.php?page=app-references'),
			'media' => admin_url('upload.php'),
		];
		echo '
			<div class="app-dashboard-widget">
				<div class="app-dashboard-today app-clearfix">
					<a href="'.$url['questions'].'">
					<h3 class="app-dashboard-date-today"><b>There are: <b class="dash-color">'.$questions.'</b> Questions</b></h3>
					</a>
				</div>

				<table class="app-table-stats"><tbody>
					<tr id="app-table-stats-tr-1" class="bordered">
						<td colspan="2" class="clickable" data-url="'.$url['questions_active'].'">
							<p class="app-dashboard-stat-total">'.$questions_active.'</p>
							<p class="app-dashboard-stat-total-label">Active</p>
						</td>
						<td colspan="1" class="clickable" data-url="'.$url['questions_corrected'].'">
							<p class="app-dashboard-stat-total">'.$questions_corrected.'</p>
							<p class="app-dashboard-stat-total-label">Corrected</p>
						</td>
						<td colspan="1" class="clickable" data-url="'.$url['questions_pending'].'">
							<p class="app-dashboard-stat-total">'.$questions_pending.'</p>
							<p class="app-dashboard-stat-total-label">Pending</p>
						</td>
					</tr>

					<tr id="app-table-stats-tr-1">
						<td colspan="4" class="clickable" data-url="'.$url['subjects'].'">
							<b class="dash-color">'.$subjects.'</b> Subjects found, <b class="dash-color">'.$subjects_root.'</b> at base (root) level
						</td>
					</tr>

					<tr id="app-table-stats-tr-1" class="bordered">
						<td class="clickable" data-url="'.$url['tags'].'">
							<p class="app-dashboard-stat-total">'.$tags.'</p>
							<p class="app-dashboard-stat-total-label">Tags</p>
						</td>
						<td class="clickable" data-url="'.$url['keywords'].'">
							<p class="app-dashboard-stat-total">'.$keywords.'</p>
							<p class="app-dashboard-stat-total-label">Keywords</p>
						</td>
						<td class="clickable" data-url="'.$url['definitions'].'">
							<p class="app-dashboard-stat-total">'.$definitions.'</p>
							<p class="app-dashboard-stat-total-label">Definitions</p>
						</td>
						<td class="clickable" data-url="'.$url['references'].'">
							<p class="app-dashboard-stat-total">'.$references.'</p>
							<p class="app-dashboard-stat-total-label">References</p>
						</td>
					</tr>

					<tr id="app-table-stats-tr-1" class="bordered">
						<td colspan=2>
							<p class="app-dashboard-stat-total">'.$this->examples_number().'</p>
							<p class="app-dashboard-stat-total-label">Examples</p>
						</td>
						<td colspan=2>
							<p class="app-dashboard-stat-total">'.$this->notes_number().'</p>
							<p class="app-dashboard-stat-total-label">Notes</p>
						</td>
					</tr>

					<tr id="app-table-stats-tr-1 last">
						<td colspan="4" class="clickable" data-url="'.$url['media'].'">
							<b class="dash-color">'.$media_items.'</b> total media items<br>
							<b class="dash-color">'.$media_size.'</b> total media library size ('.$media_files.' files)
						</td>
					</tr>

				</tbody></table>
			</div>
		';

		die();
	}

	public function get_dir_size( $path ) {
		$totalsize  = 0;
		$totalcount = 0;
		$dircount   = 0;
		if ( $handle = opendir( $path ) ) {
			while ( false !== ( $file = readdir( $handle ) ) ) {
				$nextpath = $path . '/' . $file;
				if ( $file != '.' && $file != '..' && ! is_link( $nextpath ) ) {
					if ( is_dir( $nextpath ) ) {
						$dircount ++;
						$result = $this->get_dir_size( $nextpath );
						$totalsize += $result['size'];
						$totalcount += $result['count'];
						$dircount += $result['dircount'];
					} elseif ( is_file( $nextpath ) ) {
						$totalsize += filesize( $nextpath );
						$totalcount ++;
					}
				}
			}
		}
		closedir( $handle );
		$total['size']     = $totalsize;
		$total['count']    = $totalcount;
		$total['dircount'] = $dircount;

		return $total;
	}

	public function format_dir_size( $size ) {
		if ( $size < 1024 ) {
			return $size . " bytes";
		} else if ( $size < ( 1024 * 1024 ) ) {
			$size = round( $size / 1024, 1 );
			return $size . " KB";
		} else if ( $size < ( 1024 * 1024 * 1024 ) ) {
			$size = round( $size / ( 1024 * 1024 ), 1 );
			return $size . " MB";
		} else {
			$size = round( $size / ( 1024 * 1024 * 1024 ), 1 );
			return $size . " GB";
		}
	}

}
new App_Dashboard_Widgets;
