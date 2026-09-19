<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AdImage extends Model { protected $fillable=['ad_id','path','is_primary']; protected $casts=['is_primary'=>'boolean']; public function ad(){return $this->belongsTo(Ad::class);} }
