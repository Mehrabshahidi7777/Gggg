<?php
namespace Database\Seeders;
use App\Models\User;
use Illuminate\Database\Seeder;
class AdminUserSeeder extends Seeder {
    public function run():void {
        $email=env('ADMIN_EMAIL','admin@sazmat.com');
        $password=env('ADMIN_PASSWORD','ChangeMe123!');
        User::updateOrCreate(['email'=>$email],['name'=>'مدیر سازمت','username'=>'admin','password'=>$password,'is_admin'=>true]);
    }
}
