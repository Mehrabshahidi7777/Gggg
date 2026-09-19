<?php
namespace App\Models;
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Model;
class Province extends Model { use Sluggable; protected $fillable=['name','slug']; public function sluggable():array{return ['slug'=>['source'=>'name','onUpdate'=>true,'unique'=>true]];} public function cities(){return $this->hasMany(City::class);} public function ads(){return $this->hasMany(Ad::class);} }
