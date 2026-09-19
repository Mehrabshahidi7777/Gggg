<?php
namespace Database\Seeders;
use App\Models\Province; use Illuminate\Database\Seeder;
class ProvinceSeeder extends Seeder { public function run():void {foreach(['تهران','اصفهان','فارس','خراسان رضوی','خوزستان','مازندران','گیلان','آذربایجان شرقی','آذربایجان غربی','کرمان','کرمانشاه','همدان','یزد','سمنان','البرز','قم','قزوین','گلستان','مرکزی','هرمزگان'] as $name)Province::updateOrCreate(['name'=>$name],['slug'=>\Illuminate\Support\Str::slug($name)]);} }
