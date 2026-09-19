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
    | متن وضعیت، برای نمایش به خودِ نویسنده‌ی نظر.
    */
    public function commentStatusText(): string
    {
        return match ($this->comment_status) {
            'pending' => 'نظر شما ثبت شد و پس از تأیید مدیر نمایش داده می‌شود.',
            'approved' => 'نظر شما تأیید شده و روی این صفحه نمایش داده می‌شود.',
            'rejected' => $this->comment_rejection_reason
                ? 'نظر شما تأیید نشد: ' . $this->comment_rejection_reason
                : 'نظر شما تأیید نشد.',
            default => '',
        };
    }
}
