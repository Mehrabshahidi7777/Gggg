<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $fillable=['order_item_id','buyer_id','seller_id','ad_id','rating','comment'];
    protected $casts=['rating'=>'integer'];
    public function orderItem(){return $this->belongsTo(OrderItem::class);}
    public function buyer(){return $this->belongsTo(User::class,'buyer_id');}
    public function seller(){return $this->belongsTo(User::class,'seller_id');}
    public function ad(){return $this->belongsTo(Ad::class);}
}
