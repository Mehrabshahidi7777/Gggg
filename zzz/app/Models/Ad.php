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
        'views_count','expires_at','is_suspended','suspended_at','paused_at'
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
        'paused_at' => 'datetime',

        // شماره شبا در دیتابیس رمزنگاری‌شده ذخیره/خوانده می‌شود. این کار
        // کاملاً شفاف است: هر جای کد که $ad->card_number را بخواند یا
        // بنویسد (کنترلرها، Blade، SalesController) نیازی به تغییر ندارد -
        // Eloquent خودش رمزنگاری/رمزگشایی را انجام می‌دهد. فقط باید حتماً
        // migration مربوطه (2026_09_18_000002_encrypt_ads_card_number)
        // قبل از این تغییر روی دیتابیس اجرا شده باشد، وگرنه مقادیر قدیمیِ
        // متن‌سادهْ موقع خواندن خطای رمزگشایی می‌دهند.
        'card_number' => 'encrypted',
    ];

    /*
    |--------------------------------------------------------------------------
    | جست‌وجو
    |--------------------------------------------------------------------------
    |
    | جست‌وجوی قبلی فقط LIKE '%term%' بود. آن درصدِ ابتدای عبارت یعنی
    | هیچ ایندکسی قابل استفاده نیست و MySQL مجبور است کل جدول را ردیف
    | به ردیف بخواند - برای هر جست‌وجو. با چند هزار آگهی این محسوس
    | می‌شود.
    |
    | حالا اگر ایندکس FULLTEXT موجود باشد از آن استفاده می‌شود، وگرنه
    | همان LIKE. هر دو مسیر زنده می‌مانند، به سه دلیل:
    |
    |   ۱. تا وقتی فایل SQL ایمپورت نشده، ایندکس وجود ندارد و سایت
    |      نباید بشکند.
    |   ۲. تست‌ها روی SQLite اجرا می‌شوند که MATCH ... AGAINST ندارد.
    |   ۳. پیش‌فرض MySQL کلمات کمتر از سه نویسه را ایندکس نمی‌کند
    |      (innodb_ft_min_token_size) و روی هاست اشتراکی این تنظیم
    |      قابل تغییر نیست. پس عبارت‌های کوتاه باید به LIKE بیفتند،
    |      وگرنه جست‌وجوی «در» هیچ نتیجه‌ای نمی‌دهد.
    |
    */
    public const FULLTEXT_COLUMNS = [
        'title',
        'description',
        'brand',
        'model',
        'full_name',
        'service_title',
    ];

    public const FULLTEXT_INDEX = 'ads_fulltext';

    /*
    | کمترین طول کلمه‌ای که MySQL ایندکس می‌کند. پیش‌فرضِ InnoDB است.
    */
    public const FULLTEXT_MIN_TOKEN = 3;

    public static function searchTokens(string $term): array
    {
        return preg_split('/\s+/u', trim($term), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    }

    /*
    | آیا برای این عبارت می‌شود از FULLTEXT استفاده کرد؟
    |
    | نتیجه‌ی وجود ایندکس یک روز کش می‌شود؛ بدون کش، هر جست‌وجو یک
    | کوئری اضافه به information_schema می‌زد و کل هدفِ این کار نقض
    | می‌شد. اگر فایل SQL را بعداً ایمپورت کردید، یا یک روز صبر کنید
    | یا کش را پاک کنید.
    */
    public static function fullTextIsUsable(string $term): bool
    {
        if (\Illuminate\Support\Facades\DB::connection()->getDriverName() !== 'mysql') {
            return false;
        }

        foreach (self::searchTokens($term) as $token) {
            if (mb_strlen($token) < self::FULLTEXT_MIN_TOKEN) {
                return false;
            }
        }

        return self::fullTextIndexExists();
    }

    public static function fullTextIndexExists(): bool
    {
        return \Illuminate\Support\Facades\Cache::remember(
            'ads.fulltext_index',
            now()->addDay(),
            function () {
                try {
                    return \Illuminate\Support\Facades\DB::table('information_schema.statistics')
                        ->where('table_schema', \Illuminate\Support\Facades\DB::getDatabaseName())
                        ->where('table_name', 'ads')
                        ->where('index_name', self::FULLTEXT_INDEX)
                        ->exists();
                } catch (\Throwable) {
                    // اگر به information_schema دسترسی نبود، محتاطانه نه.
                    return false;
                }
            }
        );
    }

    /*
    | عبارتِ boolean mode. هر کلمه با + اجباری می‌شود (یعنی «و»، نه
    | «یا») و با * پیشوندی، تا «سیمان» آگهیِ «سیمانکاری» را هم بیاورد.
    |
    | نویسه‌های عملگرِ خود MySQL حذف می‌شوند تا کاربر نتواند - یا @
    | بفرستد و کوئری را عوض کند یا بشکند.
    */
    public static function booleanQueryFor(string $term): string
    {
        $tokens = array_map(
            fn ($t) => preg_replace('/[+\-><()~*"@]+/u', '', $t),
            self::searchTokens($term)
        );

        $tokens = array_filter($tokens, fn ($t) => $t !== '');

        return implode(' ', array_map(fn ($t) => '+' . $t . '*', $tokens));
    }

    /*
    | $likeColumns ستون‌هایی است که در مسیر LIKE جست‌وجو می‌شوند و
    | برای محصول و خدمت فرق دارد. مسیر FULLTEXT همیشه روی همان یک
    | ایندکس کار می‌کند.
    */
    public function scopeSearchFor($query, string $term, array $likeColumns)
    {
        if (self::fullTextIsUsable($term)) {

            $boolean = self::booleanQueryFor($term);

            $query->where(function ($q) use ($boolean, $term) {
                $q->whereFullText(self::FULLTEXT_COLUMNS, $boolean, ['mode' => 'boolean'])
                  ->orWhereHas('category', fn ($c) => $c->where('name', 'like', "%{$term}%"));
            });

            /*
            | مرتب‌سازی بر اساس ارتباط، نه فقط تازگی. این مهم‌ترین
            | سودِ کاربریِ این تغییر است: آگهی‌ای که عبارت در عنوانش
            | آمده بالاتر از آگهی‌ای می‌نشیند که فقط در توضیحاتش
            | آمده.
            */
            $columns = implode(',', self::FULLTEXT_COLUMNS);

            return $query->orderByRaw(
                "MATCH({$columns}) AGAINST (? IN BOOLEAN MODE) DESC",
                [$boolean]
            );
        }

        return $query->where(function ($q) use ($likeColumns, $term) {

            foreach (array_values($likeColumns) as $i => $column) {
                $i === 0
                    ? $q->where($column, 'like', "%{$term}%")
                    : $q->orWhere($column, 'like', "%{$term}%");
            }

            $q->orWhereHas('category', fn ($c) => $c->where('name', 'like', "%{$term}%"));
        });
    }

    /*
    | همان جست‌وجو، ولی به‌زور با LIKE. وقتی مسیر FULLTEXT چیزی پیدا
    | نکند دوباره با این اجرا می‌شود تا کاربر هیچ‌وقت نتیجه‌ی کمتری
    | از قبل نگیرد: FULLTEXT کلمه‌محور است و LIKE زیررشته‌محور، و این
    | دو دقیقاً یکی نیستند.
    */
    public function scopeSearchForWithLike($query, string $term, array $likeColumns)
    {
        return $query->where(function ($q) use ($likeColumns, $term) {

            foreach (array_values($likeColumns) as $i => $column) {
                $i === 0
                    ? $q->where($column, 'like', "%{$term}%")
                    : $q->orWhere($column, 'like', "%{$term}%");
            }

            $q->orWhereHas('category', fn ($c) => $c->where('name', 'like', "%{$term}%"));
        });
    }

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
        /*
        | میانگین وزنی است، نه ساده: SUM(rating * weight) / SUM(weight).
        | وزن هر امتیاز بر اساس سن حساب امتیازدهنده در لحظه‌ی رأی ثبت
        | شده (توضیح در AdRating::weightFor).
        |
        | withAvg اینجا کار نمی‌کند چون میانگین ساده می‌گیرد، پس دو
        | زیرکوئریِ جمع اضافه می‌شود. هر دو روی همان ایندکس
        | (ad_id, user_id) کار می‌کنند و مثل قبل N+1 نمی‌سازند.
        */
        $q->withCount('ratings')->addSelect([

            'ratings_weighted_sum' => AdRating::query()
                ->selectRaw('COALESCE(SUM(rating * weight), 0)')
                ->whereColumn('ad_id', 'ads.id'),

            'ratings_weight_total' => AdRating::query()
                ->selectRaw('COALESCE(SUM(weight), 0)')
                ->whereColumn('ad_id', 'ads.id'),
        ]);

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

            /*
            | مسیر سریع: مقادیری که scopeWithRatingSummary از قبل
            | آورده. مسیر تنبل فقط برای جاهایی است که آن scope صدا
            | زده نشده، تا هیچ ویویی به خطا نخورد.
            */
            if (array_key_exists('ratings_weighted_sum', $this->attributes)) {
                $sum = (float) $this->attributes['ratings_weighted_sum'];
                $weight = (float) ($this->attributes['ratings_weight_total'] ?? 0);
            } else {
                $row = $this->ratings()
                    ->selectRaw('COALESCE(SUM(rating * weight), 0) as s, COALESCE(SUM(weight), 0) as w')
                    ->first();

                $sum = (float) ($row->s ?? 0);
                $weight = (float) ($row->w ?? 0);
            }

            /*
            | وزن صفر یعنی اصلاً امتیازی نیست. تقسیم بر صفر نمی‌کنیم و
            | null برمی‌گردانیم تا کارت «هنوز امتیازی ثبت نشده» نشان
            | دهد.
            */
            return $weight > 0 ? round($sum / $weight, 1) : null;
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
            /*
            | ترتیب مهم است: تعلیقِ سیستمی (اشتراک تمام شده) بر
            | خاموشیِ خودخواسته مقدم است، چون کاربر باید اول آن را
            | حل کند - روشن‌کردن آگهی تا وقتی اشتراک نباشد کاری از
            | پیش نمی‌برد.
            */
            get: fn() => $this->is_suspended
                ? 'در حالت تعلیق'
                : ($this->paused_at
                ? 'موقتاً غیرفعال'
                : match ($this->status) {
                    'pending' => 'در انتظار تأیید',
                    'approved' => 'تأیید شده',
                    'rejected' => 'رد شده',
                    default => 'نامشخص',
                })
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
        /*
        | paused_at یعنی خودِ ارائه‌دهنده آگهی را موقتاً خاموش کرده
        | (جنس تمام شده، سرش شلوغ است). چون این شرط همین‌جا نشسته،
        | همه‌ی مسیرها - فهرست، صفحه‌ی اصلی، جست‌وجو، صفحه‌ی آگهی و
        | نقشه‌ی سایت - با یک جا اضافه‌شدن پوشش داده می‌شوند.
        */
        return $q
            ->where('status', 'approved')
            ->where('is_suspended', false)
            ->whereNull('paused_at')
            ->where(fn($x) => $x->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    public function scopeFeatured($q){return $q->where('is_featured',true);}
    public function scopeByType($q,$type){return $q->where('type',$type);}
}
