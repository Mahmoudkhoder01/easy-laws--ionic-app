<?php
$ID = intval(get_query_var('ID'));
$req = wapi()->get_question_by_id($ID);
$q = $req['results'];
extract($req['assets']); // like, voted, vote_direction, comments
$s = $q->cat;
$color = $s->color ? $s->color : '#8cc3a0';
extract( wapi()->adjacent_questions($ID, $s->ID) ); // $next, $prev
$_page = site_url('question/'.$ID);

function __p($action, $dir = '', $cid=''){
    $arr = ['_action' => $action];
    if($dir) $arr['direction'] = $dir;
    if($cid) $arr['comment_id'] = $cid;
    echo add_query_arg($arr, $_page);
}

// echo '<pre dir="ltr" style="direction:ltr; text-align: left;">'. print_r($req, true).'</pre>';
?>

<div style="background: <?php echo $s->color; ?>; height: 75px;"></div>
<div class="bg-white py-4 text-center">
	<div class="container position-relative">
		<div class="subject-page-image" style="background: <?php echo $color; ?>">
			<img src="<?php echo $s->image; ?>" alt="" />
		</div>
		<div class="subject-page-title">
            <?php 
            echo '<h2><a href="'.site_url('subject/'.$s->ID).'">'.$s->title.'</a></h2>';
            echo $s->parent ? "<h5>{$s->ancestors}</h5>" : '';  
            ?>
		</div>
	</div>
</div>

<?php 
$u = wapi()->user();
if(!$u):
    include(__DIR__.'/403.php');
else:

    $action = strtolower(app_rq('_action'));
    if($action){
        switch($action){
            case 'like': 
                wapi()->like($ID, 0); 
            break;
            case 'vote':
                $dir = app_rq('direction');
                $dir = $dir ? strtolower($dir) : 'up'; 
                wapi()->vote($ID, $dir); 
            break;
            case 'comment':
                $details = app_rq('details');
                wapi()->comment($ID, $details); 
            break;
            case 'comment_vote':
                $cid = intval(app_rq('comment_id'));
                $dir = app_rq('direction');
                $dir = $dir ? strtolower($dir) : 'up'; 
                wapi()->comment_vote($cid, $dir);
            break;
        }
        wp_redirect($_page);
        exit();
    } else {
        wapi()->set_browsing_history($ID, $q->title);
    }
?>
<div class="bg-light qactions-wrap">
<div class="container">
    <div class="qactions">
        <?php if($prev):?>
        <a class="item" href="<?php echo site_url('question/'.$prev);?>">
            <i class="fa fa-chevron-right" style="color: <?php echo $color;?>"></i></a>
        <?php endif; ?>

        <a class="item start qfav" href="<?php __p('like'); ?>">
            <i class="fa <?php echo $like? 'fa-star' : 'fa-star-o';?>" style="color: <?php echo $color;?>"></i></a>

        <div class="item border-0">
            <a href="<?php __p('vote', 'up'); ?>">
                <i class="fa <?php echo $voted && $vote_direction == 'up'? 'fa-thumbs-up' : 'fa-thumbs-o-up';?> text-muted"></i>
            </a>
            <div class="sep"></div>
            <a href="<?php __p('vote', 'down'); ?>">
                <i class="fa <?php echo $voted && $vote_direction == 'down'? 'fa-thumbs-down' : 'fa-thumbs-o-down';?> text-muted"></i>
            </a>
            <span class="ml-3 text-muted">أصوات</span>
            <div class="sep"></div>
            <span class="color-red font-weight-bold"><?php echo $q->votes; ?></span>
        </div>

        <?php if($next):?>
        <a class="item next" href="<?php echo site_url('question/'.$next);?>">
            <i class="fa fa-chevron-left" style="color: <?php echo $color;?>"></i></a>
        <?php endif; ?>
    </div>
</div>
</div>

<div class="bg-white py-4">
	<div class="container pt-4">
        <div class="row">
            <div class="col">
                <h3><?php echo $q->title; ?></h3>
                <div class="text-muted"><?php echo $q->details; ?></div>
            </div>
        </div>
    </div>
</div>
<?php if( !empty($q->notes) || !empty($q->examples) || !empty($q->links) || !empty($q->references) || !empty($q->images) || !empty($q->videos) ): ?>
<div class="bg-light py-4">
	<div class="container">
        <nav>
            <div class="nav nav-tabs nav-fill" id="nav-tab" role="tablist">
                <?php if(!empty($q->notes)): ?>
				<a class="nav-item nav-link" data-toggle="tab" href="#notes" role="tab">
                    <img src="<?php echo app_f()->assets('/icons/note.svg'); ?>"> ملاحظات</a>
                <?php endif;?>
                <?php if(!empty($q->examples)): ?>
                <a class="nav-item nav-link" data-toggle="tab" href="#examples" role="tab">
                    <img src="<?php echo app_f()->assets('/icons/examples.svg'); ?>"> أمثلة</a>
                <?php endif;?>
                <?php if(!empty($q->links)): ?>
                <a class="nav-item nav-link" data-toggle="tab" href="#links" role="tab">
                    <img src="<?php echo app_f()->assets('/icons/links.svg'); ?>"> روابط</a>
                <?php endif;?>
                <?php if(!empty($q->references)): ?>
                <a class="nav-item nav-link" data-toggle="tab" href="#references" role="tab">
                    <img src="<?php echo app_f()->assets('/icons/references.svg'); ?>"> المراجع</a>
                <?php endif;?>
                <?php if(!empty($q->images)): ?>
                <a class="nav-item nav-link" data-toggle="tab" href="#images" role="tab">
                    <img src="<?php echo app_f()->assets('/icons/pictures.svg'); ?>"> صور</a>
                <?php endif;?>
                <?php if(!empty($q->videos)): ?>
                <a class="nav-item nav-link" data-toggle="tab" href="#videos" role="tab">
                    <img src="<?php echo app_f()->assets('/icons/video.svg'); ?>"> فيديو</a>
                <?php endif;?>
			</div>
		</nav>
        <div class="tab-content">
            <?php if(!empty($q->notes)): ?>
            <div class="tab-pane fade" id="notes">
                <ol class="qstyle"><?php foreach($q->notes as $note) echo "<li>$note</li>" ?></ol>
            </div>
            <?php endif;?>

            <?php if(!empty($q->examples)): ?>
            <div class="tab-pane fade" id="examples">
                <ol class="qstyle"><?php foreach($q->examples as $e) echo "<li>$e</li>" ?></ol>
            </div>
            <?php endif;?>

            <?php if(!empty($q->links)): ?>
            <div class="tab-pane fade" id="links">
                <ol class="qstyle"><?php foreach($q->links as $e) echo "<li><a href='$e' target='_blank'>$e</a></li>" ?></ol>
            </div>
            <?php endif;?>

            <?php if(!empty($q->references)): ?>
            <div class="tab-pane fade" id="references">
                <ol class="qstyle"><?php foreach($q->references as $e) echo '<li><a href="#" class="ref-item" data-id="'.$e['ID'].'">'.$e['title'].': '.$e['parent'].'</a></li>' ?></ol>
            </div>
            <?php endif;?>

            <?php if(!empty($q->images)): ?>
            <div class="tab-pane fade" id="images">
                <?php foreach($q->images as $e) {
                    echo '<a href="'.$e['url'].'" class="venobox" data-gall="img-gallery" data-title="'.$e['title'].'"><div class="img-gal"><img src="'.$e['thumb'].'"><div class="title">'.$e['title'].'</div></div></a>'; 
                }?>
                <div style="clear:both;"></div>
            </div>
            <?php endif;?>
        </div>
    </div>
</div>
<?php endif; ?>
<div class="container py-2">
    <h3 class="m-0">ردود 
        <!-- <span class="text-muted">(<?php echo $q->comments;?>)</span> -->
    </h3>
</div>
<div class="bg-light py-4">
    <div class="container">

        <div class="comments">

            <?php 
                $__i = 0;
                $_c_class = '';
                foreach($comments as $c): 
                    $__i++;
                    if($__i == count($comments)) $_c_class = 'last';
            ?>
                <div class="comment <?php echo $_c_class;?>">
                    <a class="avatar"><img src="<?php echo $c->user->image;?>"></a>
                    <div class="content">
                        <a class="author"><?php echo $c->user->name;?></a>
                        <div class="metadata">
                            <span class="date"><?php echo wapi()->arabicDate($c->date_created);?></span>
                        </div>
                        <div class="text"><?php echo $c->details;?></div>
                        <div class="actions">
                            
                            <a href="<?php __p('comment_vote', 'up', $c->ID); ?>">
                                <i class="fa <?php echo $c->voted && $c->vote_direction == 'up'? 'fa-thumbs-up' : 'fa-thumbs-o-up';?>"></i></a>

                            <a href="<?php __p('comment_vote', 'down', $c->ID); ?>">
                                <i class="fa <?php echo $c->voted && $c->vote_direction == 'down'? 'fa-thumbs-down' : 'fa-thumbs-o-down';?>"></i></a>

                            <a><span class="color-red"><?php echo $c->votes; ?></span></a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <form class="reply form" action="" method="POST">
                <input type="hidden" name="_action" value="comment">
                <a class="avatar"><img src="<?php echo $u->image;?>"></a>
                <div class="field">
                    <textarea class="form-control mb-2" name="details" rows="2"></textarea>
                    <button class="btn btn-dark">
                        <i class="fa fa-edit mx-3"></i> اكتب تعليقك
                    </button>
                </div>
                
            </form>

        </div>
    </div>
</div>

<div class="modal fade" id="modal-ref" role="dialog"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header bg-app text-white">
        <h5 class="modal-title"><img src="<?php echo app_f()->assets('/icons/references-white.svg'); ?>" style="height: 16px;" /> المراجع</h5>
        <button class="close text-white" data-dismiss="modal">×</button>
    </div>
    <div class="modal-body"></div>
</div></div></div>

<div class="modal fade" id="modal-def" role="dialog"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header bg-app text-white">
        <h5 class="modal-title">تعريف</h5>
        <button class="close text-white" data-dismiss="modal">×</button>
    </div>
    <div class="modal-body"></div>
</div></div></div>

<script>
jQuery(document).ready(function($){
    var __loader = '<div style="font-size: 32px; text-align: center;"><i class="fa fa-refresh fa-spin"></i></div>';

    $('.nav-tabs > a:first').addClass('active');
    $('.tab-content > div:first').addClass('show active');

    $('.inner-link').on('click', function(e){
        e.preventDefault();
        var el = $(this),
            id = el.data('id'),
            modal = $('#modal-def'),
            body = modal.find('.modal-body'),
            text = el.text();
        
        modal.modal('show');
        body.html(__loader);
        $.post(ajaxurl, {action: 'definition_modal', id: id, text: text}, function(data){
            body.html(data);
        });
    });

    $('.ref-item').on('click', function(e){
        e.preventDefault();
        var el = $(this),
            id = el.data('id'),
            modal = $('#modal-ref'),
            body = modal.find('.modal-body');
        
        modal.modal('show');
        body.html(__loader);
        $.post(ajaxurl, {action: 'reference_modal', id: id}, function(data){
            body.html(data);
        });
    });
})
</script>
<?php endif; ?>