<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdRating extends Model
{
    protected $fillable = [
        'ad_id',
        'user_id',
        'rating',
    ];

    protected $casts = [
        'rating' => 'integer',
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
}
