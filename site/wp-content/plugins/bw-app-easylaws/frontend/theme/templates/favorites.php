<?php 
	$segment = trim(strtolower(get_query_var('ID')));
	$type = $segment == 'questions' ? 0 : 1;
	$page = app_rq('page') ? intval(app_rq('page')) : 1;
	$res = wapi()->get_likes($type, $page);
	$items = $res['results'];

	$pg = new App_Front_Paginate(
		$res['total'], 
		$res['per_page'], 
		$page, 
		'',
		'justify-content-center bg-light p-3'
	);

	function __btn_class($s, $type){
		if($s == 'subjects'){
			if($type) return 'btn-dark';
			return 'btn-outline-dark';
		}
		if($s == 'questions'){
			if($type) return 'btn-outline-dark';
			return 'btn-dark';
		}
	}
?>
<div class="bg-white py-4 text-center">
	<h2>المفضلة</h2>
	<div class="btn-group">
		<a class="btn btn-lg <?php echo __btn_class('subjects', $type);?>" href="<?php echo site_url('favorites/subjects/');?>">المواضيع</a>
		<a class="btn btn-lg <?php echo __btn_class('questions', $type);?>" href="<?php echo site_url('favorites/questions');?>">الأسئلة</a>
	</div>
</div>

<div class="bg-light py-4">
	<div class="container">
		<?php if($type): ?>
		<div class="list-group mx-auto" style="max-width: 600px;">
			<?php foreach($items as $item): ?>
        	<a href="<?php echo site_url('subject/'.$item->subject_id);?>" class="list-group-item d-flex align-items-center" style="color: #222;">
           		<img src="<?php echo $item->image; ?>" style="width:50px; height: 50px; border-radius: 50px;" />
           		<span class="ml-2"><?php echo $item->title; ?></span>
           		<i class="fa fa-chevron-left ml-auto"></i>
        	</a>
        	<?php endforeach; ?>
    	</div>

    	<?php else: ?>
		<div class="list-group mx-auto">
			<?php foreach($items as $item): ?>
        	<a href="<?php echo site_url('question/'.$item->question_id);?>" class="list-group-item d-flex align-items-center" style="color: #222;">
           		<i class="fa fa-question-circle-o" style="color: #8DC4A1; font-size: 32px;"></i>
           		<span class="ml-2"><?php echo $item->title; ?></span>
           		<i class="fa fa-chevron-left ml-auto"></i>
        	</a>
        	<?php endforeach; ?>
    	</div>

    	<?php endif; ?>
    	<?php echo $pg->toHtml(); ?>
	</div>
</div>