<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdEdit extends Model
{
    protected $fillable = [
        'ad_id',
        'user_id',
        'payload',
        'original',
        'added_images',
        'removed_image_ids',
        'status',
        'rejection_reason',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        // دلیل integer بودنِ کلیدهای خارجی در App\Models\Ad توضیح داده شده.
        'ad_id' => 'integer',
        'user_id' => 'integer',
        'reviewed_by' => 'integer',

        'payload' => 'array',
        'original' => 'array',
        'added_images' => 'array',
        'removed_image_ids' => 'array',
        'reviewed_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | برچسب فارسی هر فیلد
    |--------------------------------------------------------------------------
    |
    | پنل ادمین باید بگوید «قیمت» تغییر کرده، نه «price».
    |
    */
    public const LABELS = [
        'title' => 'عنوان',
        'description' => 'توضیحات',
        'price' => 'قیمت',
        'brand' => 'برند',
        'model' => 'مدل',
        'condition' => 'وضعیت کالا',
        'full_name' => 'نام و نام خانوادگی',
        'service_title' => 'عنوان تخصص',
        'address' => 'آدرس',
        'phone' => 'شماره تماس',
        'website' => 'وب‌سایت',
        'card_number' => 'شماره شبا',
        'category_id' => 'دسته‌بندی',
        'province_id' => 'استان',
        'city_id' => 'شهر',
    ];

    public function ad()
    {
        return $this->belongsTo(Ad::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopePending($q)
    {
        return $q->where('status', 'pending');
    }

    /*
    |--------------------------------------------------------------------------
    | تفاوت‌ها، آماده برای نمایش
    |--------------------------------------------------------------------------
    |
    | خروجی: آرایه‌ای از [label, old, new] برای هر فیلد تغییرکرده.
    |
    | شناسه‌ها (دسته‌بندی، استان، شهر) به نامشان ترجمه می‌شوند، چون
    | «۱۷ ← ۳۸» برای ادمین هیچ معنایی ندارد.
    |
    | شماره شبا عمداً ماسک می‌شود: ادمین باید ببیند که عوض شده، اما
    | لازم نیست شماره‌ی کامل حساب بانکی در صفحه‌ی مرور پخش شود.
    |
    */
    public function diff(): array
    {
        $rows = [];

        foreach ($this->payload as $field => $new) {

            $old = $this->original[$field] ?? null;

            $rows[] = [
                'field' => $field,
                'label' => self::LABELS[$field] ?? $field,
                'old' => $this->present($field, $old),
                'new' => $this->present($field, $new),
            ];
        }

        return $rows;
    }

    private function present(string $field, $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        return match ($field) {

            'category_id' => Category::find($value)?->name ?? ('#' . $value),
            'province_id' => Province::find($value)?->name ?? ('#' . $value),
            'city_id' => City::find($value)?->name ?? ('#' . $value),

            'price' => format_price($value),

            'condition' => $value === 'new' ? 'نو' : 'کارکرده',

            // فقط چهار رقم آخر، تا تغییر دیده شود بدون افشای کل شماره
            'card_number' => '••••' . mb_substr((string) $value, -4),

            default => (string) $value,
        };
    }
}
