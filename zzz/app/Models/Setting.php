<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Setting extends Model { protected $fillable=['key','value','type','group']; protected $casts=['value'=>'json']; public static function get($key,$default=null){$s=self::where('key',$key)->first();return $s?$s->value:$default;} public static function set($key,$value,$type='text',$group='general'){return self::updateOrCreate(['key'=>$key],['value'=>$value,'type'=>$type,'group'=>$group]);} }
