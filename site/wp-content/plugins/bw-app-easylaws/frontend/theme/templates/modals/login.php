<div class="modal fade" id="modal-login" role="dialog"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header">
        <h5 class="modal-title">تسجيل الدخول</h5>
        <button class="close" data-dismiss="modal">×</button>
    </div>
    <div class="modal-body">
        <a href="#" class="btn btn-block mb-2 btn-facebook btn-facebook-login text-center">تسجيل الدخول باستخدام الفيسبوك</a>
        <a href="#" id="google-login" class="btn btn-block mb-4 btn-google btn-google-login text-center">تسجيل الدخول باستخدام غوغل</a>

        <p class="text-center">أو تسجيل الدخول باستخدام البريد الإلكتروني</p>
        <hr>
        <form action="" method="POST" class="validate">
            <input type="hidden" name="_action" value="login" />
            <div class="form-group">
                <label>أدخل البريد الإلكتروني</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <div class="form-group">
                <label>كلمه السر</label>
                <input type="password" name="password" class="form-control" required minlength="6">
            </div>
            <div class="text-center">
                <button type="submit" class="btn btn-dark btn-lg btn-block  mb-3">تسجيل الدخول</button>
                <p>ليس لديك حساب؟ <a class="btn-signup black" href="#">سجل الأن</a>.</p>
            </div>
        </form>
    </div>

</div></div></div>
