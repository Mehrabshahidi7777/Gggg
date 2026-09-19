<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
class DatabaseSeeder extends Seeder { public function run():void {$this->call([AdminUserSeeder::class,CategorySeeder::class,ProvinceSeeder::class,CitySeeder::class,SettingSeeder::class,ServicePlanSeeder::class]);} }
