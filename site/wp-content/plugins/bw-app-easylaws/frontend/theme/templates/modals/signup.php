<div class="modal fade" id="modal-signup" role="dialog"><div class="modal-dialog"><div class="modal-content">
    
    <div class="modal-header">
        <h5 class="modal-title">سجل</h5>
        <button class="close" data-dismiss="modal">×</button>
    </div>
    <div class="modal-body">
        <a href="#" class="btn btn-block mb-2 btn-facebook btn-facebook-login text-center">اشترك عبر حساب فايسبوك</a>
        <a id="google-signup" href="#" class="btn btn-block mb-4 btn-google btn-google-login text-center">اشترك عبر حساب غوغل</a>
        
        <p class="text-center">أو الاشتراك مع البريد الإلكتروني</p>
        <hr>
        <form id="form-signup" class="validate" action="" method="POST">
            <input type="hidden" name="_action" value="signup" />
            <div class="form-group">
                <label>الاسم الكامل</label>
                <input type="text" name="_name" class="form-control" minlength="3" required>
            </div>
            <div class="form-group">
                <label>الجنس</label>
                <select name="_gender" class="form-control" required>
                    <option value="male">ذكر</option>
                    <option value="female">أنثى</option>
                </select>
            </div>
            <div class="form-group">
                <label>رقم الهاتف</label>
                    <input type="text" name="_phone" class="form-control" required minlength="8">
            </div>
            <div class="form-group">
                <label>أدخل البريد الإلكتروني</label>
                <input type="email" name="_email" class="form-control" required>
            </div>
            <div class="form-group">
                <label>كلمه السر</label>
                <input type="password" name="_password" class="form-control" minlength="6" required>
            </div>
            <div class="text-center">
                <button type="submit" name="_submit" class="btn btn-dark btn-lg btn-block mb-3">سجل</button>
                <p>هل أنت مستخدم بالفعل؟ <a class="btn-login black" href="#">تسجيل الدخول</a>.</p>
            </div>
        </form>
    </div>

</div></div></div>
