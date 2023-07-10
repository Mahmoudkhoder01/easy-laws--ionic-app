<?php 
	$u = wapi()->user();
	if(!$u){
		wp_redirect( site_url() );
		exit;
	}

	function __redr(){
		wp_reditect( site_url('profile') );
		exit();
	}

	$_MSG = $_ERR = '';

	$action = trim(strtolower(app_rq('_action')));
	switch($action){
		case 'acc':
			$img = !empty($_FILES['image']) ? $_FILES['image'] : '';
			$post = stripslashes_deep($_POST);
			$post['name'] = $post['_name'];
			$res = wapi()->edit_profile($post, $img);
			if($res['valid'] == 'YES'){
				$_MSG = 'تم تغيير المعلومات الخاصة بك بنجاح';
				$u = wapi()->user();
			} else {
				$_ERR = $res['reason'];
			}
		break;

		case 'pwd':
			$res = wapi()->change_password( app_rq('password') );
			if($res['valid'] == 'YES'){
				$_MSG = 'تم تغيير كلمه المرور بنجاح';
			} else {
				$_ERR = $res['reason'];
			}
		break;

		case 'email':
			$res = wapi()->change_email( app_rq('email'), app_rq('password') );
			if($res['valid'] == 'YES'){
				$_MSG = 'تم تغيير البريد الكتروني بنجاح';
			} else {
				$_ERR = $res['reason'];
			}
		break;
	}
?>
<div class="bg-white py-4 text-center">
	<h2>حـــســـابـــي</h2>
</div>
<?php if($_ERR):?>
<div class="container">
	<div class="alert alert-danger"><?php echo $_ERR; ?></div>
</div>
<?php endif; ?>

<?php if($_MSG):?>
<div class="container">
	<div class="alert alert-success"><?php echo $_MSG; ?></div>
</div>
<?php endif; ?>
<div class="bg-light py-4">
	<div class="container">
		<div class="row">
			<div class="col-12 col-lg-8">
				<form class="bg-white p-4 mb-4 rounded" action="" method="POST" class="validate" enctype="multipart/form-data">
					<input type="hidden" name="_action" value="acc" />

					<div class="file-field mb-3">
            			<div class="mb-1 text-center">
              				<img src="<?php echo $u->image;?>" id="img_preview" class="avatar" alt="">
            			</div>
            			<div class="d-flex justify-content-center">
              				<div class="btn btn-sm btn-grey">
                				<span>تغيير الصورة</span>
                				<input type="file" id="img_file" name="image" accept="image/*">
              				</div>
            			</div>
          			</div>

		            <div class="form-row mb-2">
		                <label class="col-3">الاسم</label>
		                <input type="text" name="_name" class="form-control col-9" required value="<?php echo $u->name;?>">
		            </div>
		            <div class="form-row mb-2">
		                <label class="col-3">رقم الهاتف</label>
		                <input type="text" name="phone" class="form-control col-9" required value="<?php echo $u->phone;?>">
		            </div><div class="form-row mb-2">
		                <label class="col-3">تاريخ الميلاد</label>
		                <input type="date" name="dob" class="form-control col-9" required value="<?php echo $u->dob;?>">
		            </div>
		            <div class="form-row mb-2">
		                <label class="col-3">الجنس</label>
		                <select name="gender" class="form-control col-9">
		                	<option value="male" <?php selected($u->gender, 'male');?>>ذكر</option>
		                	<option value="female" <?php selected($u->gender, 'female');?>>أنثى</option>
		                </select>
		            </div>
		            <div class="row"><div class="col-9 offset-3">
		            	<button type="submit" class="btn btn-dark px-5 mr-n-10"><i class="fa fa-floppy-o mr-2"></i> حفظ</button>
		            </div></div>
				</form>

				<form class="bg-white p-4 mb-4 rounded" action="" method="POST" class="validate">
					<input type="hidden" name="_action" value="pwd" />
					<h4 class="mb-4">تغيير كلمة السر</h4>
		            <div class="form-row mb-2">
		                <label class="col-3">كلمة السر الجديدة</label>
		                <input type="password" name="password" class="form-control col-9" required minlength="6">
		            </div>
		            <div class="form-row mb-2">
		                <label class="col-3">اعد كلمة السر</label>
		                <input type="password" name="old-password" class="form-control col-9" required minlength="6">
		            </div>
		            <div class="row"><div class="col-9 offset-3">
		            	<button type="submit" class="btn btn-dark px-5 mr-n-10"><i class="fa fa-floppy-o mr-2"></i> حفظ</button>
		            </div></div>
				</form>

				<form class="bg-white p-4 mb-4 rounded" action="" method="POST" class="validate">
					<input type="hidden" name="_action" value="email" />
					<h4 class="mb-4">تغيير البريد الإلكتروني</h4>
		            <div class="form-row mb-2">
		                <label class="col-3">البريد الإلكتروني</label>
		                <input type="email" name="email" class="form-control col-9" required value="<?php echo $u->email;?>">
		            </div>
		            <div class="form-row mb-2">
		                <label class="col-3">كلمة السر</label>
		                <input type="password" name="password" class="form-control col-9" required minlength="6">
		            </div>
		            <div class="row"><div class="col-9 offset-3">
		            	<button type="submit" class="btn btn-dark px-5 mr-n-10"><i class="fa fa-floppy-o mr-2"></i> حفظ</button>
		            </div></div>
				</form>
			</div>
			<div class="col-12 col-lg-4">
				<a class="btn btn-lg btn-block btn-grey mb-4" href="<?php echo site_url('favorites');?>">
					<i class="fa fa-star mx-3"></i> المفضلة
				</a>

				<a class="btn btn-lg btn-block btn-grey mb-4" href="<?php echo site_url('history');?>">
					<i class="fa fa-chrome mx-3"></i> لائحة التصفح
				</a>
			</div>
		</div>
	</div>
</div>

<script>
	jQuery(document).ready(function($){
		$('#img_file').change(function(){
			var input = this;
		    var url = $(this).val();
		    var ext = url.substring(url.lastIndexOf('.') + 1).toLowerCase();
		    if (input.files && input.files[0] && (ext == "gif" || ext == "png" || ext == "jpeg" || ext == "jpg")) {
		        var reader = new FileReader();
		        reader.onload = function (e) {
		           $('#img_preview').attr('src', e.target.result);
		        }
		       reader.readAsDataURL(input.files[0]);
		    }
		});
	})
</script>