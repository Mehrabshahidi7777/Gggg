<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable=['order_number','buyer_id','total_amount','phone','address','status','payment_transaction_id','payment_reference_id','paid_at'];
    protected $casts=['buyer_id'=>'integer','total_amount'=>'decimal:2','paid_at'=>'datetime'];
    public function buyer(){return $this->belongsTo(User::class,'buyer_id');}
    public function items(){return $this->hasMany(OrderItem::class);}

    public function getStatusTextAttribute(): string {
        return match($this->status){
            'pending'=>'در انتظار پرداخت','paid'=>'پرداخت‌شده','processing'=>'در حال آماده‌سازی','shipped'=>'ارسال‌شده','completed'=>'تکمیل‌شده','cancelled'=>'لغوشده',default=>'نامشخص'
        };
    }

    public function syncStatusFromItems(): void
    {
        $statuses = $this->items()->pluck('status');
        if ($statuses->isEmpty()) return;
        if ($statuses->every(fn($s) => $s === 'cancelled')) $status='cancelled';
        elseif ($statuses->every(fn($s) => $s === 'completed')) $status='completed';
        elseif ($statuses->contains('shipped')) $status='shipped';
        elseif ($statuses->contains('processing')) $status='processing';
        elseif ($statuses->contains('paid')) $status='paid';
        else $status='pending';
        $this->updateQuietly(['status'=>$status]);
    }
}
