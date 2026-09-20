<?php
namespace App\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Ad extends Model
{
    use Sluggable;

    /*
    |--------------------------------------------------------------------------
    | سقف تصاویر هر آگهی
    |--------------------------------------------------------------------------
    |
    | هم موقع ثبت آگهی و هم موقع ویرایش به این عدد نگاه می‌شود. جدا
    | نگه‌داشتنِ این مقدار در یک جا باعث می‌شود سقفِ فرم ثبت و سقفِ
    | ویرایش هیچ‌وقت از هم جدا نیفتند.
    |
    */
    public const MAX_IMAGES = 10;

    protected $fillable = [
        'user_id','category_id','province_id','city_id','type','title','slug',
        'description','price','brand','model','condition','full_name',
        'service_title','address','phone','card_number','website','status','is_featured',
        'views_count','expires_at','is_suspended','suspended_at'
    ];

    protected $casts = [
        /*
        |----------------------------------------------------------------------
        | چرا کلیدهای خارجی صراحتاً integer شده‌اند
        |----------------------------------------------------------------------
        |
        | کل کد از مقایسه‌ی سخت‌گیرانه استفاده می‌کند، مثلاً:
        |
        |     $ad->user_id === auth()->id()
        |
        | سمت راست همیشه عدد صحیح است (Eloquent کلید اصلی را خودش
        | cast می‌کند)، ولی سمت چپ یک ستون معمولی است و هر چیزی که
        | درایور دیتابیس برگرداند همان است.
        |
        | روی نصب‌های سالمِ MySQL این مقدار عدد برمی‌گردد و همه‌چیز
        | کار می‌کند. ولی اگر PDO به‌جای mysqlnd با libmysqlclient
        | بسته شده باشد - که روی هاست‌های اشتراکی کم پیش نمی‌آید -
        | هر ستون به‌صورت رشته برمی‌گردد. آن‌وقت:
        |
        |     '3' === 3   →   false
        |
        | و هر محافظی که به این مقایسه تکیه کرده بی‌صدا از کار
        | می‌افتد: صاحب آگهی می‌تواند به آگهی خودش امتیاز بدهد،
        | بازدید خودش به‌عنوان سرنخ شمرده می‌شود، و برعکس، جایی که
        | abort_unless گذاشته‌ایم صاحبِ واقعی ۴۰۳ می‌گیرد.
        |
        | این cast مسئله را از ریشه حل می‌کند: هر چه درایور بدهد،
        | خواندنِ این ستون‌ها همیشه عدد صحیح است.
        |
        | توجه: فقط کلیدهای عددی. شناسه‌های درگاه پرداخت رشته‌اند و
        | نباید cast شوند.
        */
        'user_id' => 'integer',
        'category_id' => 'integer',
        'province_id' => 'integer',
        'city_id' => 'integer',

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

                /*
                | onUpdate عمداً false است.
                |
                | قبلاً true بود، یعنی هر بار که ادمین عنوان آگهی را
                | ویرایش می‌کرد، اسلاگ و در نتیجه آدرس صفحه هم عوض
                | می‌شد. هر لینکی که جایی به آن آگهی داده شده بود
                | (نتایج گوگل، پیام واتساپ، لینک اشتراک‌گذاشته‌شده)
                | از آن لحظه ۴۰۴ می‌شد.
                |
                | آدرس یک صفحه باید پایدار بماند. ضمناً همین تنظیم
                | تضمین می‌کند آگهی‌های قدیمی که اسلاگ ترانویسی‌شده
                | دارند، با تغییر الگوی اسلاگ‌سازیِ زیر آدرسشان عوض
                | نشود و لینک‌های موجود سالم بماند.
                */
                'onUpdate' => false,

                'unique' => true,

                'method' => function ($string, $separator) {

                    /*
                    | Str::slug لاراول فارسی را ترانویسی می‌کند: «مصالح
                    | پایه» می‌شد msalh-payh و «داب» می‌شد dab. گوگل از
                    | این آدرس هیچ کلیدواژه‌ای برداشت نمی‌کرد و کاربر
                    | هم از دیدن لینک نمی‌فهمید کجا می‌رود.
                    |
                    | fa_slug حروف فارسی را نگه می‌دارد، پس آدرس آگهی
                    | می‌شود /ad/سیمان-تیپ-۲-اصفهان که هم برای کاربر
                    | خواناست و هم برای موتور جست‌وجو معنا دارد.
                    */
                    $slug = fa_slug($string, $separator);

                    /*
                    | عنوانی که فقط از نویسه‌های خاص یا ایموجی ساخته
                    | شده می‌تواند به رشته‌ی خالی برسد و مسیر ad.show
                    | را بشکند. در این حالت اسلاگ تصادفی اما معتبر
                    | جایگزین می‌شود.
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
    public function ratings(){return $this->hasMany(AdRating::class);}
    public function contactReveals(){return $this->hasMany(AdContactReveal::class);}
    public function edits(){return $this->hasMany(AdEdit::class);}

    /*
    | نظرهای تأییدشده‌ی کاربران روی این آگهی. نظر تأییدنشده هرگز
    | نباید در صفحه‌ی عمومی دیده شود.
    */
    public function approvedComments()
    {
        return $this->hasMany(AdRating::class)
            ->approvedComments()
            ->with('user')
            ->latest();
    }

    /*
    | امتیازی که کاربرِ واردشده‌ی فعلی به این آگهی داده (اگر داده باشد).
    | برای مهمان، user_id برابر null است و هیچ ردیفی نمی‌گیرد.
    */
    public function myRating()
    {
        return $this->hasOne(AdRating::class)->where('user_id', auth()->id());
    }

    /*
    |--------------------------------------------------------------------------
    | Rating aggregates
    |--------------------------------------------------------------------------
    |
    | هر کارت آگهی میانگین، تعداد امتیاز و امتیازِ خودِ کاربر را نشان
    | می‌دهد. بدون این scope، هر کارت در یک صفحه‌ی ۱۲تایی سه کوئری
    | جداگانه می‌زد (N+1). این scope همه را در چند کوئری تجمیعی
    | می‌آورد: ratings_avg_rating، ratings_count و رابطه‌ی myRating.
    |
    */
    public function scopeWithRatingSummary($q)
    {
        $q->withAvg('ratings', 'rating')->withCount('ratings');

        if (auth()->check()) {
            $q->with('myRating');
        }

        return $q;
    }

    /*
    | میانگین امتیاز به‌صورت عدد گردشده با یک رقم اعشار.
    | اگر scope بالا صدا زده نشده باشد، به‌صورت تنبل از رابطه می‌خواند
    | تا ویوها هرگز به خطا نخورند.
    */
    protected function ratingAverage(): Attribute
    {
        return Attribute::make(get: function () {

            $avg = array_key_exists('ratings_avg_rating', $this->attributes)
                ? $this->attributes['ratings_avg_rating']
                : $this->ratings()->avg('rating');

            return $avg === null ? null : round((float) $avg, 1);
        });
    }

    protected function ratingCount(): Attribute
    {
        return Attribute::make(get: fn() => array_key_exists('ratings_count', $this->attributes)
            ? (int) $this->attributes['ratings_count']
            : $this->ratings()->count());
    }

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
