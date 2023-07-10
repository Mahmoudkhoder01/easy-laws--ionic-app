<?php 
	$data = wapi()->get_subjects(['parent' => 0]);
?>

<div class="subjects-banner"></div>
<div class="bg-white py-4 text-center">
	<h2>المـــــواضيـــــــع</h2>
</div>

<div class="bg-light py-4 text-center">
	<div class="container">
		<div class="row align-items-center">
			<?php foreach($data as $i): $item = $i['data']; ?>
				<div class="col-6 col-lg-2">
					<div class="subject-item mb-5"><a href="<?php echo site_url('subject/'.$item->ID); ?>">
						<div class="img shadow" style="background: <?php echo $item->color;?>">
							<img src="<?php echo $item->image; ?>" alt="" />
						</div>
						<div class="title"><?php echo $item->title; ?></div>
					</a></div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</div>
