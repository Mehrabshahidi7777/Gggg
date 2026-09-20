<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ServiceAdDraft extends Model {
    protected $fillable=['user_id','payload','image_paths','expires_at'];
    protected $casts=['user_id'=>'integer','payload'=>'array','image_paths'=>'array','expires_at'=>'datetime'];
    public function user(){return $this->belongsTo(User::class);}
}
