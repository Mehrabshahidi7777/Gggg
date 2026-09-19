<?php

namespace App\Console\Commands;

use App\Models\Ad;
use App\Models\City;
use App\Models\Order;
use App\Models\Province;
use Database\Seeders\CitySeeder;
use Database\Seeders\ProvinceSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| نصب یکجای به‌روزرسانی
|--------------------------------------------------------------------------
|
| این دستور برای کسی نوشته شده که نه ترمینال دارد و نه phpMyAdmin،
| ولی می‌تواند کرون‌جاب بسازد. کرون همان پوسته‌ی فرمان است، پس هر
| کاری که در ترمینال می‌شد کرد، از اینجا هم می‌شود.
|
| کاری که انجام می‌دهد، دقیقاً معادل هشت فایل SQL پوشه‌ی deploy است:
|
|   ۱. اجرای migrationها   (ساخت جدول‌ها و ستون‌های تازه)
|   ۲. اجرای seederها      (۳۱ استان و ۲۱۵ شهر)
|   ۳. حذف داده‌ی آزمایشی  (دو آگهی و ۱۳ سفارش) - فقط با --purge-demo
|   ۴. پاک‌سازی کش         (تنظیمات، ویو، مسیرها)
|
| همه‌ی مراحل idempotent هستند: اجرای دوباره چیزی را خراب نمی‌کند و
| رکورد تکراری نمی‌سازد.
|
*/
class InstallCommand extends Command
{
    protected $signature = 'sazmat:install
                            {--purge-demo : داده‌ی آزمایشی (دو آگهی و ۱۳ سفارش) هم پاک شود}
                            {--dry-run : فقط گزارش بده، چیزی را تغییر نده}';

    protected $description = 'اجرای یکجای migration، seeder، پاک‌سازی کش و (اختیاری) حذف داده‌ی آزمایشی';

    /*
    | آگهی‌ها و سفارش‌های آزمایشیِ شناخته‌شده. هدف‌گیری بر اساس اسلاگ و
    | شماره سفارش است، نه شناسه یا شرط کلی، تا اگر داده‌ی واقعی‌ای
    | اضافه شده باشد دست نخورد.
    */
    private const DEMO_AD_SLUGS = ['dab', 'ktkt'];

    private const DEMO_ORDER_NUMBERS = [
        'SZ-260824005356-F8YNU', 'SZ-260824014950-3ZIAC', 'SZ-260905024557-HNFY8',
        'SZ-260905033356-SBWXC', 'SZ-260906000422-EONKN', 'SZ-260906002137-MSFB1',
        'SZ-260906002218-RBD2X', 'SZ-260906214853-MI5WD', 'SZ-260907161813-Z7VBM',
        'SZ-260911102801-HAHPF', 'SZ-260914221236-R9G6M', 'SZ-260914221716-HXTUQ',
        'SZ-260917143933-Q2VGM',
    ];

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');

        $this->line('');
        $this->line('=========================================');
        $this->line('  سازمت | نصب به‌روزرسانی');
        $this->line('  ' . now()->format('Y-m-d H:i:s'));
        $this->line('=========================================');

        if ($dry) {
            $this->warn('حالت آزمایشی: هیچ تغییری اعمال نمی‌شود.');
        }

        $this->step1Migrate($dry);
        $this->step2Seed($dry);
        $this->step3PurgeDemo($dry);
        $this->step4ClearCaches($dry);

        $this->line('');
        $this->line('-----------------------------------------');
        $this->info('  همه‌ی مراحل با موفقیت انجام شد.');
        $this->line('  حالا می‌توانید این کرون‌جاب را حذف کنید.');
        $this->line('-----------------------------------------');
        $this->line('');

        return self::SUCCESS;
    }

    /*
    |--------------------------------------------------------------------------
    | ۱) migrationها
    |--------------------------------------------------------------------------
    */
    private function step1Migrate(bool $dry): void
    {
        $this->line('');
        $this->line('[۱/۴] ساخت جدول‌ها و ستون‌های تازه...');

        if ($dry) {
            Artisan::call('migrate:status');
            $this->line(Artisan::output());
            return;
        }

        /*
        | --force لازم است چون لاراول در محیط production قبل از اجرای
        | migration تأیید تعاملی می‌خواهد و کرون نمی‌تواند جواب بدهد.
        */
        Artisan::call('migrate', ['--force' => true]);

        $this->line(trim(Artisan::output()));
    }

    /*
    |--------------------------------------------------------------------------
    | ۲) استان‌ها و شهرها
    |--------------------------------------------------------------------------
    */
    private function step2Seed(bool $dry): void
    {
        $this->line('');
        $this->line('[۲/۴] افزودن استان‌ها و شهرهای جاافتاده...');

        $before = ['p' => Province::count(), 'c' => City::count()];

        if (! $dry) {
            $this->callSilent('db:seed', ['--class' => ProvinceSeeder::class, '--force' => true]);
            $this->callSilent('db:seed', ['--class' => CitySeeder::class, '--force' => true]);
        }

        $after = ['p' => Province::count(), 'c' => City::count()];

        $this->line(sprintf(
            '      استان: %d ← %d    شهر: %d ← %d',
            $before['p'], $after['p'], $before['c'], $after['c']
        ));

        if (! $dry && $after['p'] !== 31) {
            $this->warn('      هشدار: تعداد استان‌ها ۳۱ نشد. لطفاً گزارش بدهید.');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | ۳) داده‌ی آزمایشی
    |--------------------------------------------------------------------------
    */
    private function step3PurgeDemo(bool $dry): void
    {
        $this->line('');

        if (! $this->option('purge-demo')) {
            $this->line('[۳/۴] حذف داده‌ی آزمایشی: رد شد (بدون --purge-demo)');
            return;
        }

        $this->line('[۳/۴] حذف داده‌ی آزمایشی...');

        $ads = Ad::whereIn('slug', self::DEMO_AD_SLUGS)->with('images')->get();

        foreach ($ads as $ad) {
            $this->line("      آگهی: {$ad->title}  (slug: {$ad->slug})");

            if (! $dry) {
                /*
                | برخلاف حذف مستقیم در دیتابیس، اینجا فایل تصویر هم پاک
                | می‌شود و روی دیسک بی‌صاحب نمی‌ماند.
                */
                foreach ($ad->images as $image) {
                    Storage::disk('public')->delete($image->path);
                }

                $ad->delete();
            }
        }

        $orders = Order::whereIn('order_number', self::DEMO_ORDER_NUMBERS)->get();

        foreach ($orders as $order) {
            $this->line("      سفارش: {$order->order_number}  ({$order->total_amount} تومان)");

            // ردیف‌های order_items با ON DELETE CASCADE خودکار پاک می‌شوند
            if (! $dry) {
                $order->delete();
            }
        }

        $this->line(sprintf(
            '      مجموع: %d آگهی و %d سفارش%s',
            $ads->count(),
            $orders->count(),
            $dry ? ' (حذف نشد)' : ' حذف شد'
        ));
    }

    /*
    |--------------------------------------------------------------------------
    | ۴) کش
    |--------------------------------------------------------------------------
    */
    private function step4ClearCaches(bool $dry): void
    {
        $this->line('');
        $this->line('[۴/۴] پاک‌سازی کش...');

        if ($dry) {
            $this->line('      رد شد (حالت آزمایشی)');
            return;
        }

        /*
        | این کار جایگزین پاک‌کردن دستیِ پوشه‌های
        | storage/framework/views و bootstrap/cache است. بدون این،
        | تنظیمات و ویوهای قدیمی همچنان سرو می‌شوند و هیچ تغییری
        | دیده نمی‌شود.
        */
        foreach (['config:clear', 'view:clear', 'route:clear', 'cache:clear'] as $command) {
            $this->callSilent($command);
            $this->line("      {$command} ✓");
        }
    }
}
