<?php
namespace Database\Seeders;
use App\Models\Category; use Illuminate\Database\Seeder;
class CategorySeeder extends Seeder { public function run():void {foreach([['مصالح بنایی','masonry','product'],['فولاد و آهن‌آلات','steel','product'],['لوله و اتصالات','pipes','product'],['کاشی و سرامیک','ceramics','product'],['سیمان و گچ','cement','product'],['تأسیسات ساختمان','facilities','product'],['اجرای اسکلت','skeleton','service'],['برق‌کشی','electrical','service'],['لوله‌کشی','plumbing','service'],['نماکاری','facade','service'],['طراحی داخلی','interior-design','service'],['نقاشی ساختمان','painting','service']] as [$name,$slug,$type])Category::updateOrCreate(['slug'=>$slug],['name'=>$name,'type'=>$type,'is_active'=>true]);} }
