<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdContactReveal extends Model
{
    /*
    | فقط created_at لازم است؛ این رکوردها هرگز ویرایش نمی‌شوند.
    */
    public const UPDATED_AT = null;

    protected $fillable = [
        'ad_id',
        'user_id',
        'ip_hash',
        'revealed_on',
    ];

    protected $casts = [
        // دلیل integer بودنِ کلیدهای خارجی در App\Models\Ad توضیح داده شده.
        'ad_id' => 'integer',
        'user_id' => 'integer',

        'revealed_on' => 'date',
    ];

    public function ad()
    {
        return $this->belongsTo(Ad::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
