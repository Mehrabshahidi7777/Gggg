<?php
namespace App\Http\Controllers;
use App\Models\LoginOtp;
use App\Models\User;
use App\Services\AmootSmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Str;
use RuntimeException;
class AuthController extends Controller {
    public function showLogin(){return view('auth.login');}
    public function login(Request $request){
        $data=$request->validate(['email'=>'required|email','password'=>'required|string']);
        if(Auth::attempt(['email'=>$data['email'],'password'=>$data['password']],$request->boolean('remember'))){$request->session()->regenerate();return redirect()->intended(route('home'))->with('success','خوش آمدید.');}
        return back()->withErrors(['email'=>'ایمیل یا رمز عبور اشتباه است.'])->onlyInput('email');
    }
    public function showRegister(){return view('auth.register');}
    public function register(Request $request){
        $data=$request->validate([
            'username'=>['required','string','min:3','max:50','alpha_dash','unique:users,username'],
            'email'=>'required|email|max:255|unique:users,email',
            'password'=>['required','confirmed',Password::min(8)],
        ],['username.unique'=>'این نام کاربری قبلاً استفاده شده است.','username.alpha_dash'=>'نام کاربری فقط می‌تواند شامل حروف انگلیسی، عدد، خط تیره و زیرخط باشد.']);
        $user=User::create(['name'=>$data['username'],'username'=>$data['username'],'email'=>$data['email'],'password'=>$data['password']]);
        Auth::login($user);$request->session()->regenerate();
        /*
        | intended، نه route('home').
        |
        | کسی که روی «نمایش شماره» یک آگهی زده و به اینجا فرستاده شده،
        | باید به همان آگهی برگردد. ورود و تأیید پیامک از قبل intended
        | را رعایت می‌کردند و فقط همین یک مسیر جا مانده بود، پس
        | ثبت‌نام با ایمیل کاربر را به خانه می‌انداخت و آگهی گم می‌شد.
        */
        return redirect()->intended(route('home'))->with('success','حساب شما ساخته شد.');
    }
    public function requestMobileOtp(Request $request,AmootSmsService $sms){return $this->sendMobileOtp($request,$sms,'login');}
    public function requestMobileRegistrationOtp(Request $request,AmootSmsService $sms){
        $data=$request->validate(['username'=>['required','string','min:3','max:50','alpha_dash','unique:users,username'],'mobile'=>'required|string']);
        $request->session()->put('mobile_register_username',$data['username']);
        return $this->sendMobileOtp($request,$sms,'register',$data['mobile']);
    }
    private function sendMobileOtp(Request $request,AmootSmsService $sms,string $purpose,?string $rawMobile=null){
        $mobile=$this->normalizeMobile($rawMobile ?: $request->input('mobile',''));
        validator(['mobile'=>$mobile],['mobile'=>['required','regex:/^09\d{9}$/']],[
            'mobile.required'=>'لطفا فیلد شماره را کامل کنید.',
            'mobile.regex'=>'شماره موبایل را به‌درستی وارد کنید.',
        ])->validate();
        if($purpose==='login' && !User::where('mobile',$mobile)->exists()) return back()->withErrors(['mobile'=>'حسابی با این شماره پیدا نشد. ابتدا ثبت‌نام کنید.'])->withInput();
        if($purpose==='register' && User::where('mobile',$mobile)->exists()) return back()->withErrors(['mobile'=>'این شماره قبلاً ثبت‌نام شده است؛ از گزینه ورود با موبایل استفاده کنید.'])->withInput();
        $key='otp:'.$purpose.':'.$request->ip().':'.$mobile;
        if(RateLimiter::tooManyAttempts($key,5)) return back()->withErrors(['mobile'=>'تعداد درخواست‌ها زیاد است. چند دقیقه بعد دوباره تلاش کنید.'])->withInput();
        RateLimiter::hit($key,90);
        LoginOtp::where('mobile',$mobile)->where('purpose',$purpose)->whereNull('verified_at')->delete();
        $code=(string) random_int(100000,999999);
        $otp=LoginOtp::create(['mobile'=>$mobile,'code_hash'=>Hash::make($code),'purpose'=>$purpose,'expires_at'=>now()->addMinutes(3)]);
        try{$sms->sendOtp($mobile,$code);}catch(RuntimeException $e){$otp->delete();return back()->withErrors(['mobile'=>$e->getMessage()])->withInput();}
        $request->session()->put('mobile_auth_pending',$mobile);
        $request->session()->put('mobile_auth_purpose',$purpose);
        return redirect()->route('mobile.verify')->with('success','کد تأیید برای شماره شما ارسال شد.');
    }
    public function showMobileVerify(Request $request){if(!$request->session()->has('mobile_auth_pending'))return redirect()->route('login');return view('auth.mobile-verify',['mobile'=>$request->session()->get('mobile_auth_pending'),'purpose'=>$request->session()->get('mobile_auth_purpose','login')]);}
    public function verifyMobileOtp(Request $request){
        $mobile=$request->session()->get('mobile_auth_pending');$purpose=$request->session()->get('mobile_auth_purpose','login');if(!$mobile)return redirect()->route('login');
        $data=$request->validate(['code'=>'required|digits:6']);
        $otp=LoginOtp::where('mobile',$mobile)->where('purpose',$purpose)->whereNull('verified_at')->latest()->first();
        if(!$otp || $otp->expires_at->isPast())return back()->withErrors(['code'=>'کد تأیید منقضی شده است. دوباره درخواست کد کنید.']);
        if($otp->attempts>=5)return back()->withErrors(['code'=>'تعداد تلاش‌های مجاز تمام شده است. دوباره کد بگیرید.']);
        $otp->increment('attempts');
        if(!Hash::check($data['code'],$otp->code_hash))return back()->withErrors(['code'=>'کد تأیید اشتباه است.']);
        $otp->update(['verified_at'=>now()]);
        if($purpose==='register'){
            $username=$request->session()->get('mobile_register_username');
            $user=User::create(['mobile'=>$mobile,'username'=>$username,'name'=>$username,'password'=>Str::random(40)]);
        }else{$user=User::where('mobile',$mobile)->firstOrFail();}
        Auth::login($user,true);$request->session()->forget(['mobile_auth_pending','mobile_auth_purpose','mobile_register_username']);$request->session()->regenerate();return redirect()->intended(route('home'))->with('success','با موفقیت وارد شدید.');
    }
    public function logout(Request $request){Auth::logout();$request->session()->invalidate();$request->session()->regenerateToken();return redirect()->route('home')->with('success','با موفقیت خارج شدید.');}
    /*
    | همان منطق قبلی، حالا از helper مشترک normalize_mobile() می‌آید.
    |
    | این تابع دو نسخه‌ی جداگانه داشت - یکی اینجا و یکی در helperها -
    | که یعنی هر اصلاحی باید در دو جا انجام می‌شد. نسخه‌ی helper یک
    | حالت مرزی را هم بهتر مدیریت می‌کند: شماره‌ی ده‌رقمی که با «98»
    | شروع شود (مثل 9876543210) در نسخه‌ی قدیم به اشتباه پیش‌شماره‌ی
    | کشور تلقی و بریده می‌شد.
    */
    private function normalizeMobile(string $mobile): string {
        return (string) normalize_mobile($mobile);
    }
}
