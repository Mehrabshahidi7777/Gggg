<?php
namespace App\Services;
use Illuminate\Support\Facades\Http;
use RuntimeException;
class AmootSmsService {
    public function sendOtp(string $mobile, string $code): void {
        $token=config('services.amoot.token');
        $line=config('services.amoot.line_number');
        $pattern=(int) config('services.amoot.pattern_code_id');
        if(!$token || !$line || !$pattern){throw new RuntimeException('تنظیمات پیامک آموت کامل نشده است.');}
        $response=Http::asForm()->withHeaders(['Authorization'=>$token])->timeout(20)->post('https://portal.amootsms.com/rest/SendWithPatternOWN',[
            'Mobile'=>$mobile,
            'LineNumber'=>$line,
            'PatternCodeID'=>$pattern,
            'PatternValues'=>$code,
        ]);
        if(!$response->successful()) throw new RuntimeException('ارسال کد تأیید پیامکی ناموفق بود.');
        $json=$response->json();
        if(is_array($json) && array_key_exists('Status',$json) && in_array($json['Status'],[false,0,'0','false','False'],true)){
            throw new RuntimeException('سامانه پیامکی ارسال کد را تأیید نکرد.');
        }
    }
}
