<?php
namespace App\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Ad extends Model
{
    use Sluggable;

    protected $fillable = [
        'user_id','category_id','province_id','city_id','type','title','slug',
        'description','price','brand','model','condition','full_name',
        'service_title','address','phone','card_number','website','status','is_featured',
        'views_count','expires_at','is_suspended','suspended_at'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_featured' => 'boolean',
        'is_suspended' => 'boolean',
        'expires_at' => 'datetime',
        'suspended_at' => 'datetime',

        // شماره شبا در دیتابیس رمزنگاری‌شده ذخیره/خوانده می‌شود. این کار
        // کاملاً شفاف است: هر جای کد که $ad->card_number را بخواند یا
        // بنویسد (کنترلرها، Blade، SalesController) نیازی به تغییر ندارد -
        // Eloquent خودش رمزنگاری/رمزگشایی را انجام می‌دهد. فقط باید حتماً
        // migration مربوطه (2026_09_18_000002_encrypt_ads_card_number)
        // قبل از این تغییر روی دیتابیس اجرا شده باشد، وگرنه مقادیر قدیمیِ
        // متن‌سادهْ موقع خواندن خطای رمزگشایی می‌دهند.
        'card_number' => 'encrypted',
    ];

    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'title',
                'onUpdate' => true,
                'unique' => true,
                'method' => function ($string, $separator) {
                    $slug = \Illuminate\Support\Str::slug($string, $separator);

                    /*
                    | Titles that are entirely Persian/Arabic (or otherwise
                    | non-Latin) can transliterate down to an empty string,
                    | which breaks the ad.show route entirely wherever this
                    | ad is linked to (home page, category pages, listings).
                    | Always fall back to a random-but-valid slug instead.
                    */

                    return $slug !== ''
                        ? $slug
                        : 'ad-' . \Illuminate\Support\Str::random(8);
                },
            ],
        ];
    }

    public function user(){return $this->belongsTo(User::class);}
    public function category(){return $this->belongsTo(Category::class);}
    public function province(){return $this->belongsTo(Province::class);}
    public function city(){return $this->belongsTo(City::class);}
    public function images(){return $this->hasMany(AdImage::class)->orderByDesc('is_primary');}
    public function primaryImage(){return $this->hasOne(AdImage::class)->where('is_primary',true);}
    public function reviews(){return $this->hasMany(Review::class);}

    protected function priceFormatted(): Attribute
    {
        return Attribute::make(get: fn() => format_price($this->price));
    }

    protected function statusText(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->is_suspended
                ? 'در حالت تعلیق'
                : match ($this->status) {
                    'pending' => 'در انتظار تأیید',
                    'approved' => 'تأیید شده',
                    'rejected' => 'رد شده',
                    default => 'نامشخص',
                }
        );
    }

    public function scopeApproved($q)
    {
        /*
        | is_suspended قبلاً فقط برای خدمات چک می‌شد (محصول همیشه از این
        | چک معاف بود، چون قبلاً محصول هرگز suspend نمی‌شد). حالا که
        | اشتراک محصول هم اضافه شده و محصول هم می‌تواند suspend شود،
        | این چک باید بدون توجه به نوع، برای هر دو یکسان اعمال شود.
        */
        return $q
            ->where('status', 'approved')
            ->where('is_suspended', false)
            ->where(fn($x) => $x->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    public function scopeFeatured($q){return $q->where('is_featured',true);}
    public function scopeByType($q,$type){return $q->where('type',$type);}
}
