<?php
namespace App\Models;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class User extends Authenticatable {
    use HasFactory, Notifiable;

    /*
    | is_admin عمداً از اینجا حذف شد: هیچ کنترلری الان با mass assignment
    | خام (مثل User::create($request->all())) این را ست نمی‌کند، اما نگه
    | داشتنش در fillable یعنی هر تغییر کد در آینده (مثلاً پروفایل‌ای که
    | یک‌روزی با $request->all() نوشته بشه) می‌تونه به کاربر عادی اجازه‌ی
    | خوداعطاییِ دسترسی ادمین رو بده. در Admin\UserController::update که
    | تنها جای مجاز برای تغییر این فیلده، به‌جای mass assignment مستقیم
    | ست می‌شود.
    */
    protected $fillable=['name','username','email','mobile','password'];
    protected $hidden=['password','remember_token'];
    protected function casts():array{return ['email_verified_at'=>'datetime','password'=>'hashed','is_admin'=>'boolean'];}
    public function ads(){return $this->hasMany(Ad::class);}

    /*
    | مهم: بعد از اضافه‌شدن ستون type به service_subscriptions (برای
    | پشتیبانی از اشتراک محصول در همون جدول)، این رابطه‌ها صراحتاً باید
    | نوع را فیلتر کنند - وگرنه یک اشتراکِ محصولِ فعال به‌اشتباه باعث
    | می‌شود سیستم فکر کند کاربر اشتراک خدمت هم دارد (چون هر دو در یک
    | جدول‌اند و current() فقط تاریخ/وضعیت را چک می‌کند، نه نوع را).
    */
    public function serviceSubscriptions(){return $this->hasMany(ServiceSubscription::class)->where('type','service');}
    public function activeServiceSubscription(){return $this->hasOne(ServiceSubscription::class)->where('type','service')->current()->latestOfMany();}
    public function productSubscriptions(){return $this->hasMany(ServiceSubscription::class)->where('type','product');}
    public function activeProductSubscription(){return $this->hasOne(ServiceSubscription::class)->where('type','product')->current()->latestOfMany();}

    public function orders(){return $this->hasMany(Order::class,'buyer_id');}
    public function orderItems(){return $this->hasMany(OrderItem::class,'seller_id');}
    public function receivedReviews(){return $this->hasMany(Review::class,'seller_id');}
    public function writtenReviews(){return $this->hasMany(Review::class,'buyer_id');}
    public function adRatings(){return $this->hasMany(AdRating::class);}
}
