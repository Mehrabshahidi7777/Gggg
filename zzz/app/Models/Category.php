<?php
namespace App\Models;
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Model;
class Category extends Model { use Sluggable; protected $fillable=['name','slug','parent_id','type','icon','is_active']; protected $casts=['is_active'=>'boolean']; public function sluggable():array{return ['slug'=>['source'=>'name','onUpdate'=>true,'unique'=>true]];} public function parent(){return $this->belongsTo(Category::class,'parent_id');} public function children(){return $this->hasMany(Category::class,'parent_id');} public function ads(){return $this->hasMany(Ad::class);} }
