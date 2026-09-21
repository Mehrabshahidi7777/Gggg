<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class AdSubmitRequest extends FormRequest {
public function authorize():bool{return auth()->check();}

/*
|--------------------------------------------------------------------------
| یکدست‌سازی ورودی قبل از اعتبارسنجی
|--------------------------------------------------------------------------
|
| کاربرِ فارسی‌زبان ارقام را با کیبورد فارسی می‌زند («۰۹۱۲...»). اگر
| همان‌طور ذخیره شوند، نه قابل شماره‌گیری‌اند، نه قابل جست‌وجو، و
| قانون digits:24 روی شماره شبا هم بی‌دلیل رد می‌شود. پس همه‌ی
| فیلدهای عددی قبل از رسیدن به قوانین، به ارقام انگلیسی تبدیل
| می‌شوند و شماره تلفن به قالب 09xxxxxxxxx درمی‌آید.
|
*/
protected function prepareForValidation():void {
$this->merge(array_filter([
'phone'=>$this->filled('phone') ? normalize_mobile($this->input('phone')) : null,
'card_number'=>(config('marketplace.collect_card_number') && $this->filled('card_number')) ? preg_replace('/\D+/','',fa_to_en_digits($this->input('card_number'))) : null,
'price'=>$this->filled('price') ? fa_to_en_digits($this->input('price')) : null,
], fn($v)=>$v!==null));
}

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
'title'=>'required|string|max:255','description'=>'nullable|string|max:5000','price'=>'nullable|numeric|min:0','address'=>'required|string|max:255',
// «phone» عمداً اینجا تعریف نشده - قبلاً اینجا 'nullable' بود و پایین‌تر
// برای خدمت با $rules+=[...,'phone'=>'required',...] «بازنویسی» می‌شد؛
// ولی عملگر += در PHP کلیدهای از قبل موجود در آرایه‌ی چپ را دست‌نخورده
// نگه می‌دارد، پس آن 'required' هرگز واقعاً اعمال نمی‌شد و شماره تلفن
// برای خدمت هم عملاً nullable باقی می‌ماند - یک باگ واقعی. با تعریف‌نکردن
// اینجا و required-کردنش برای هر دو نوع پایین‌تر (هم طبق درخواست شما که
// محصول هم مثل خدمت باشه)، هم آن باگ رفع شد هم خواسته‌ی جدید اعمال شد.
'images'=>['nullable','array','max:'.\App\Models\Ad::MAX_IMAGES],'images.*'=>'image|mimes:jpg,jpeg,png,webp|max:10240'
];
if($type==='product'){
    $rules+=['phone'=>['required','regex:/^0[0-9]{10}$/'],'brand'=>'nullable|string|max:100','model'=>'nullable|string|max:100','condition'=>['nullable',Rule::in(['new','used'])]];

    /*
    | شماره شبا فقط وقتی خرید آنلاین برگردد. توضیح در
    | config/marketplace.php.
    |
    | ⚠️ آکولاد اینجا لازم است، نه تزئینی: اولین بار این شرط را
    | بدون آن نوشتم و else پایین به جای if($type==='product') به
    | همین if چسبید - یعنی وقتی کلید خاموش بود، قوانینِ «خدمت»
    | روی آگهی محصول اعمال می‌شد و ثبت محصول می‌خواست نام
    | ارائه‌دهنده و عنوان خدمت بدهی.
    */
    if(config('marketplace.collect_card_number')){
        $rules+=['card_number'=>['required','digits:24']];
    }
}
else $rules+=['phone'=>['required','regex:/^0[0-9]{10}$/'],'full_name'=>'required|string|max:255','service_title'=>'required|string|max:255','website'=>'nullable|url|max:255'];
return $rules;
}
public function messages():array{return ['title.required'=>'عنوان آگهی الزامی است.','category_id.required'=>'دسته‌بندی را انتخاب کنید.','category_id.exists'=>'دسته‌بندی انتخاب‌شده با نوع آگهی سازگار نیست.','province_id.required'=>'استان را انتخاب کنید.','city_id.required'=>'شهر را انتخاب کنید.','city_id.exists'=>'شهر انتخاب‌شده با استان سازگار نیست.','full_name.required'=>'نام و نام خانوادگی الزامی است.','phone.required'=>'شماره تلفن الزامی است.','phone.regex'=>'شماره تلفن باید ۱۱ رقم و با ۰ شروع شود (مثال: 09121234567).','address.required'=>'آدرس الزامی است.','address.max'=>'آدرس حداکثر ۲۵۵ نویسه می‌تواند باشد.','images.max'=>'حداکثر ۱۰ تصویر می‌توانید انتخاب کنید.','images.*.max'=>'حجم هر تصویر حداکثر ۱۰ مگابایت است.','card_number.required'=>'شماره شبا الزامی است.','card_number.digits'=>'شماره شبا باید ۲۴ رقم باشد (بدون IR).'];}
}
