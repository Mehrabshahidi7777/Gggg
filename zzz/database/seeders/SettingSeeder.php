<?php
namespace Database\Seeders;
use App\Models\Setting; use Illuminate\Database\Seeder;
class SettingSeeder extends Seeder { public function run():void {Setting::set('site_name','سازمت');Setting::set('site_description','بازار آنلاین صنعت ساختمان');Setting::set('contact_email','info@sazmat.com');} }
