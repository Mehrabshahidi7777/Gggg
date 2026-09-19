<?php
namespace Database\Seeders;
use App\Models\ServicePlan; use Illuminate\Database\Seeder;
class ServicePlanSeeder extends Seeder { public function run():void {foreach([[1,'یک ماهه',100000],[3,'سه ماهه',250000],[6,'شش ماهه',450000],[9,'نه ماهه',600000],[12,'دوازده ماهه',750000]] as [$months,$title,$price])ServicePlan::updateOrCreate(['months'=>$months],['title'=>$title,'price'=>$price,'is_active'=>true,'sort_order'=>$months]);} }
