<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ServicePlan extends Model {
    protected $fillable=['type','months','title','price','is_active','sort_order'];
    protected $casts=['months'=>'integer','price'=>'decimal:2','is_active'=>'boolean'];
    public function subscriptions(){return $this->hasMany(ServiceSubscription::class);}
    public function scopeOfType($q,$type){return $q->where('type',$type);}
}
