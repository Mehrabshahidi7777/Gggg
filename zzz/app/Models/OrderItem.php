<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id','ad_id','seller_id','seller_phone','title','unit_price','quantity','subtotal','status',
    ];

    protected $casts = [
        // دلیل integer بودنِ کلیدهای خارجی در App\Models\Ad توضیح داده شده.
        'order_id' => 'integer',
        'ad_id' => 'integer',
        'seller_id' => 'integer',

        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function order(){ return $this->belongsTo(Order::class); }
    public function ad(){ return $this->belongsTo(Ad::class); }
    public function seller(){ return $this->belongsTo(User::class, 'seller_id'); }
    public function review(){ return $this->hasOne(Review::class); }

    public function getStatusTextAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'در انتظار پرداخت',
            'paid' => 'پرداخت‌شده',
            'processing' => 'در حال آماده‌سازی',
            'shipped' => 'ارسال‌شده',
            'completed' => 'تکمیل‌شده',
            'cancelled' => 'لغوشده',
            default => 'نامشخص',
        };
    }
}
