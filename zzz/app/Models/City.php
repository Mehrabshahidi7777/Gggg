<?php
namespace App\Models;
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Model;
class City extends Model { use Sluggable; protected $fillable=['name','slug','province_id']; protected $casts=['province_id'=>'integer']; public function sluggable():array{return ['slug'=>['source'=>'name','onUpdate'=>true,'unique'=>true]];} public function province(){return $this->belongsTo(Province::class);} public function ads(){return $this->hasMany(Ad::class);} }
