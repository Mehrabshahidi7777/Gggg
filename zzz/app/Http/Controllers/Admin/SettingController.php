<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
class SettingController extends Controller {
    public function index(){return view('admin.settings.index',['settings'=>Setting::orderBy('group')->orderBy('key')->get()]);}
    public function update(Request $r){
        $data=$r->validate(['site_name'=>'nullable|string|max:255','site_description'=>'nullable|string|max:1000','contact_email'=>'nullable|email','admin_email'=>'required|email|unique:users,email,'.auth()->id(),'admin_password'=>['nullable','confirmed',Password::min(8)]]);
        foreach(['site_name','site_description','contact_email'] as $key){if(array_key_exists($key,$data))Setting::set($key,$data[$key]);}
        auth()->user()->email=$data['admin_email'];if(!empty($data['admin_password']))auth()->user()->password=$data['admin_password'];auth()->user()->save();
        return back()->with('success','تنظیمات و اطلاعات ورود مدیر ذخیره شد.');
    }
}
