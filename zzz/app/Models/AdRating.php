<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdRating extends Model
{
    protected $fillable = [
        'ad_id',
        'user_id',
        'rating',
        'weight',
        'comment',
        'comment_status',
        'comment_rejection_reason',
        'comment_reviewed_by',
        'comment_reviewed_at',
    ];

    protected $casts = [
        // دلیل integer بودنِ کلیدهای خارجی در App\Models\Ad توضیح داده شده.
        'ad_id' => 'integer',
        'user_id' => 'integer',
        'comment_reviewed_by' => 'integer',

        'rating' => 'integer',
        'weight' => 'float',
        'comment_reviewed_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | برچسب‌های امتیاز
    |--------------------------------------------------------------------------
    |
    | ترتیب کلیدها همان ترتیبی است که در صفحه از چپ به راست دیده
    | می‌شود: ستاره‌ی سمت چپ «افتضاح» و ستاره‌ی سمت راست «بسیار عالی».
    | صفحه RTL است، بنابراین ویجت ستاره‌ها عمداً با direction:ltr
    | رندر می‌شود تا این ترتیب حفظ شود.
    |
    */
    public const LABELS = [
        1 => 'افتضاح',
        2 => 'ضعیف',
        3 => 'متوسط',
        4 => 'خوب',
        5 => 'بسیار عالی',
    ];

    /*
    |--------------------------------------------------------------------------
    | وزن امتیاز
    |--------------------------------------------------------------------------
    |
    | بر اساس سن حسابِ امتیازدهنده در همین لحظه. جدول مقادیر در
    | config/marketplace.php است تا بدون تغییر کد قابل تنظیم باشد.
    |
    | وزن در لحظه‌ی ثبت حساب می‌شود و در ستون ذخیره می‌ماند - نه هنگام
    | خواندن. اگر موقع خواندن حساب می‌شد، هر کارت آگهی مجبور بود سن
    | حساب همه‌ی امتیازدهنده‌ها را بخواند و میانگین دیگر با یک SUM
    | ساده درنمی‌آمد.
    |
    | نتیجه‌ی این انتخاب: کسی که در روز اول حسابش امتیاز داده، وزنش
    | با گذشت زمان بالا نمی‌رود مگر اینکه امتیازش را ویرایش کند. این
    | عمدی است - وزن، اعتبارِ حساب در لحظه‌ی رأی را ثبت می‌کند.
    |
    */
    public static function weightFor(?User $user): float
    {
        $tiers = config('marketplace.rating_weights', [0 => 1.0]);

        // از بزرگ‌ترین آستانه به کوچک‌ترین
        krsort($tiers);

        $ageInDays = $user?->created_at
            ? $user->created_at->diffInDays(now())
            : 0;

        foreach ($tiers as $minimumDays => $weight) {
            if ($ageInDays >= $minimumDays) {
                return (float) $weight;
            }
        }

        // اگر جدول خالی یا خراب بود، وزن کامل - نه صفر.
        return 1.0;
    }

    public static function labelFor(?int $rating): string
    {
        return self::LABELS[$rating] ?? 'بدون امتیاز';
    }

    public function ad()
    {
        return $this->belongsTo(Ad::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function commentReviewer()
    {
        return $this->belongsTo(User::class, 'comment_reviewed_by');
    }

    /*
    | فقط نظرهایی که ادمین تأیید کرده روی صفحه‌ی آگهی دیده می‌شوند.
    */
    public function scopeApprovedComments($q)
    {
        return $q->where('comment_status', 'approved')->whereNotNull('comment');
    }

    /*
    |--------------------------------------------------------------------------
    | متن وضعیت، برای نمایش به خودِ نویسنده‌ی نظر
    |--------------------------------------------------------------------------
    |
    | عمداً هیچ اشاره‌ای به «مدیر» و «تأیید» نمی‌شود.
    |
    | فرایند بررسی، کارِ داخلی ماست و کاربر لازم نیست بداند نظرش روی
    | میز چه کسی می‌رود؛ همان‌طور که اکثر سایت‌ها فقط می‌گویند «ثبت
    | شد». متن‌ها در عین حال دروغ هم نمی‌گویند: تا وقتی نظر منتشر
    | نشده، نمی‌گوییم منتشر شده.
    |
    | برای ادمین (در پنل مدیریت) همچنان واژه‌های صریح «تأیید» و «رد»
    | به‌کار می‌رود؛ آن متن‌ها جای دیگری تعریف شده‌اند.
    |
    */
    public function commentStatusText(): string
    {
        return match ($this->comment_status) {

            'pending' => 'نظر شما ثبت شد و به‌زودی روی این صفحه نمایش داده می‌شود.',

            'approved' => 'نظر شما روی این صفحه منتشر شده است.',

            'rejected' => $this->comment_rejection_reason
                ? 'نظر شما منتشر نشد: ' . $this->comment_rejection_reason
                : 'نظر شما منتشر نشد.',

            default => '',
        };
    }
}
