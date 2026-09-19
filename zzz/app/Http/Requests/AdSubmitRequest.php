<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class AdSubmitRequest extends FormRequest {
public function authorize():bool{return auth()->check();}
public function rules():array {
$type=$this->input('type');
$rules=[
'type'=>['required',Rule::in(['product','service'])],
'category_id'=>['required',Rule::exists('categories','id')->where(fn($q)=>$q->where('type',$type)->where('is_active',true))],
'province_id'=>'required|exists:provinces,id',
'city_id'=>['required',Rule::exists('cities','id')->where(fn($q)=>$q->where('province_id',$this->input('province_id')))],
// حداکثر حجم هر تصویر: قبلاً 102400 (کیلوبایت = ۱۰۰ مگابایت) بود که
// به احتمال زیاد اشتباه تایپی بوده و مسیر مصرف زیاد منابع/پر شدن
// دیسک با آپلود تصاویر خیلی بزرگ رو باز می‌گذاشت. به ۱۰ مگابایت
// (که برای عکس آگهی کاملاً کافیه) اصلاح شد.
'title'=>'required|string|max:255','description'=>'nullable|string|max:5000','price'=>'nullable|numeric|min:0','address'=>'required|string|max:1000',
// «phone» عمداً اینجا تعریف نشده - قبلاً اینجا 'nullable' بود و پایین‌تر
// برای خدمت با $rules+=[...,'phone'=>'required',...] «بازنویسی» می‌شد؛
// ولی عملگر += در PHP کلیدهای از قبل موجود در آرایه‌ی چپ را دست‌نخورده
// نگه می‌دارد، پس آن 'required' هرگز واقعاً اعمال نمی‌شد و شماره تلفن
// برای خدمت هم عملاً nullable باقی می‌ماند - یک باگ واقعی. با تعریف‌نکردن
// اینجا و required-کردنش برای هر دو نوع پایین‌تر (هم طبق درخواست شما که
// محصول هم مثل خدمت باشه)، هم آن باگ رفع شد هم خواسته‌ی جدید اعمال شد.
'images'=>'nullable|array|max:10','images.*'=>'image|mimes:jpg,jpeg,png,webp|max:10240'
];
if($type==='product')$rules+=['phone'=>'required|string|max:30','brand'=>'nullable|string|max:100','model'=>'nullable|string|max:100','condition'=>['nullable',Rule::in(['new','used'])],'card_number'=>['required','digits:24']];
else $rules+=['phone'=>'required|string|max:30','full_name'=>'required|string|max:255','service_title'=>'required|string|max:255','website'=>'nullable|url|max:255'];
return $rules;
}
public function messages():array{return ['title.required'=>'عنوان آگهی الزامی است.','category_id.required'=>'دسته‌بندی را انتخاب کنید.','category_id.exists'=>'دسته‌بندی انتخاب‌شده با نوع آگهی سازگار نیست.','province_id.required'=>'استان را انتخاب کنید.','city_id.required'=>'شهر را انتخاب کنید.','city_id.exists'=>'شهر انتخاب‌شده با استان سازگار نیست.','full_name.required'=>'نام و نام خانوادگی الزامی است.','phone.required'=>'شماره تلفن الزامی است.','address.required'=>'آدرس الزامی است.','images.max'=>'حداکثر ۱۰ تصویر می‌توانید انتخاب کنید.','images.*.max'=>'حجم هر تصویر حداکثر ۱۰ مگابایت است.','card_number.required'=>'شماره شبا الزامی است.','card_number.digits'=>'شماره شبا باید ۲۴ رقم باشد (بدون IR).'];}
}
