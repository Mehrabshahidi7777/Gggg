<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class LoginOtp extends Model {
    protected $fillable=['mobile','code_hash','purpose','attempts','expires_at','verified_at'];
    protected $casts=['expires_at'=>'datetime','verified_at'=>'datetime'];
}
