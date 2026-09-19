<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ServiceSubscription extends Model {
    protected $fillable=['type','user_id','service_plan_id','amount','starts_at','ends_at','grace_until','paid_at','transaction_id','reference_id','status'];
    protected $casts=['amount'=>'decimal:2','starts_at'=>'datetime','ends_at'=>'datetime','grace_until'=>'datetime','paid_at'=>'datetime'];
    public function user(){return $this->belongsTo(User::class);}
    public function plan(){return $this->belongsTo(ServicePlan::class,'service_plan_id');}
    public function scopeCurrent($q){
        /*
        | «فعلاً معتبر» یعنی الان بین تاریخ شروع و پایانِ یک اشتراکِ
        | پرداخت‌شده باشیم — فارغ از اینکه وضعیت آن ردیف «active» یا
        | «expired» باشد. چون وقتی کاربر زودتر از پایان، تمدید می‌کند،
        | اشتراک قبلی بلافاصله «expired» می‌شود (چون جایش را به اشتراک
        | جدید که ادامه‌ی آن است می‌دهد)، در حالی که تاریخ همان اشتراکِ
        | expired-شده هنوز می‌تواند «الان» را پوشش بدهد. اگر فقط
        | status=active را چک کنیم، دقیقاً همین حالت (که کاربر واقعاً
        | و پیوسته پرداخت کرده) به‌اشتباه «بدون اشتراک» شمرده می‌شود.
        */
        return $q->whereIn('status', ['active', 'expired'])
            ->where(function($q){ $q->whereNull('starts_at')->orWhere('starts_at','<=',now()); })
            ->where('ends_at','>',now());
    }
    public function scopeOfType($q,$type){return $q->where('type',$type);}
    public function isActive(): bool { return in_array($this->status, ['active','expired']) && (!$this->starts_at || $this->starts_at->isPast()) && $this->ends_at && $this->ends_at->isFuture(); }
    public function isSuspended(): bool { return $this->ends_at && $this->grace_until && $this->grace_until->isPast(); }
}
