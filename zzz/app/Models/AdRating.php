<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdRating extends Model
{
    protected $fillable = [
        'ad_id',
        'user_id',
        'rating',
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
