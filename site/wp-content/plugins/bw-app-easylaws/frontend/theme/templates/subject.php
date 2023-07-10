<?php 
	$u = wapi()->user();
	$ID = intval(get_query_var('ID'));
	if(!$ID) {
		wp_redirect( site_url('subjects') );
		exit;
	}
	$data = wapi()->get_subjects(['ID' => $ID])[0];
	$s = $data['data'];

	if($s->parent){
		$crumb = wapi()->get_subject_ancestors($ID, '<i class="fa fa-arrow-left mx-3"></i>');
	} else {
		$crumb = 'المـــــواضيـــــــع';
	}

	$_page = site_url('subject/'.$ID);
	$like = wapi()->is_subject_liked($ID);

	$action = strtolower(app_rq('_action'));
	if($action == 'like'){
		wapi()->like($ID, 1);
		wp_redirect( $_page );
        exit();
	}
?>

<div style="background: <?php echo $s->color; ?>; height: 75px;"></div>
<div class="bg-white py-4 text-center">
	<div class="container position-relative">
		<div class="subject-page-image" style="background: <?php echo $s->color; ?>">
			<img src="<?php echo $s->image; ?>" alt="" />
		</div>
		<div class="subject-page-title">
			<h2>
				<?php echo $s->title; ?>
				<?php if ($u) echo '<a class="sfav" href="'.add_query_arg('_action', 'like', $_page).'"><i class="fa '.( $like ? 'fa-star' : 'fa-star-o' ) .'" ></i></a>'; ?>
			</h2>
			<h5><?php echo $crumb; ?></h5>
		</div>
	</div>
	
</div>

<?php if($data['children']): ?>

<div class="bg-light py-4 text-center">
	<div class="container">
		<?php app_f()->subjects_list($data['children']); ?>
	</div>
</div>

<?php else: ?>

<div class="bg-white py-4 text-center">
	<div class="container">
		<?php app_f()->questions_list(['cat' => $ID]); ?>
	</div>
</div>

<?php endif; ?>
