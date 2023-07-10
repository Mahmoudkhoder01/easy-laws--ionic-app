<?php 
	$page = app_rq('page') ? intval(app_rq('page')) : 1;
	$res = wapi()->get_browsing_history($page);
	$items = $res['results'];

	$pg = new App_Front_Paginate(
		$res['total'], 
		$res['per_page'], 
		$page, 
		'',
		'justify-content-center bg-light p-3'
	);
?>
<div class="bg-white py-4 text-center">
	<h2>لائحة التصفح</h2>
</div>

<div class="bg-light py-4">
	<div class="container">
		<div class="list-group mx-auto">
			<?php foreach($items as $item): ?>
        	<a href="<?php echo site_url('question/'.$item->question_id);?>" class="list-group-item d-flex align-items-center" style="color: #222;">
           		<i class="fa fa-question-circle-o" style="color: #8DC4A1; font-size: 32px;"></i>
           		<span class="ml-2"><?php echo $item->title; ?></span>
           		<div class="ml-auto">
           			<span class="text-muted d-none d-lg-inline" style="font-size: 11px;"><?php echo wapi()->arabicDate($item->date_created); ?></span>
           			<i class="fa fa-chevron-left ml-3"></i>
           		</div>
        	</a>
        	<?php endforeach; ?>
    	</div>
    	<?php echo $pg->toHtml(); ?>
    </div>
</div>