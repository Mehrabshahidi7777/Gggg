<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdEdit;
use App\Models\AdImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AdEditController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | صف درخواست‌های ویرایش
    |--------------------------------------------------------------------------
    */
    public function index(Request $request)
    {
        $status = $request->input('status', 'pending');

        $edits = AdEdit::query()
            ->with(['ad', 'user', 'reviewer'])
            ->when(
                in_array($status, ['pending', 'approved', 'rejected'], true),
                fn ($q) => $q->where('status', $status)
            )
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.ad-edits.index', [
            'edits' => $edits,
            'status' => $status,
            'pendingCount' => AdEdit::pending()->count(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | نمایش تفاوت‌ها
    |--------------------------------------------------------------------------
    */
    public function show(AdEdit $adEdit)
    {
        $adEdit->load(['ad.images', 'user', 'reviewer']);

        return view('admin.ad-edits.show', [
            'edit' => $adEdit,
            'diff' => $adEdit->diff(),
            'removedImages' => AdImage::whereIn('id', (array) $adEdit->removed_image_ids)->get(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | تأیید و اعمال ویرایش
    |--------------------------------------------------------------------------
    */
    public function approve(AdEdit $adEdit)
    {
        if ($adEdit->status !== 'pending') {
            return back()->with('error', 'این درخواست قبلاً بررسی شده است.');
        }

        DB::transaction(function () use ($adEdit) {

            $ad = $adEdit->ad;

            /*
            | فیلدهای متنی. توجه: اسلاگ عمداً دوباره ساخته نمی‌شود -
            | مدل Ad روی onUpdate=false تنظیم شده تا آدرس صفحه پایدار
            | بماند و لینک‌های موجود نشکنند.
            */
            if ($adEdit->payload) {
                $ad->fill($adEdit->payload)->save();
            }

            /*
            | حذف تصاویر. فایل فیزیکی هم پاک می‌شود، وگرنه روی دیسک
            | بی‌صاحب می‌ماند.
            */
            if ($adEdit->removed_image_ids) {

                $images = AdImage::whereIn('id', $adEdit->removed_image_ids)
                    ->where('ad_id', $ad->id)
                    ->get();

                foreach ($images as $image) {
                    Storage::disk('public')->delete($image->path);
                    $image->delete();
                }
            }

            /*
            | افزودن تصاویر تازه. فایل‌ها هنگام ثبت درخواست آپلود شده
            | بودند؛ اینجا فقط رکوردشان ساخته می‌شود تا از این لحظه
            | روی سایت دیده شوند.
            */
            foreach ((array) $adEdit->added_images as $path) {
                AdImage::create([
                    'ad_id' => $ad->id,
                    'path' => $path,
                    'is_primary' => false,
                ]);
            }

            /*
            | اگر تصویر اصلی جزو حذف‌شده‌ها بود، آگهی بدون تصویر شاخص
            | می‌ماند و کارتش در فهرست خالی نشان داده می‌شود.
            */
            if (! $ad->images()->where('is_primary', true)->exists()) {
                $ad->images()->orderBy('id')->limit(1)->update(['is_primary' => true]);
            }

            $adEdit->update([
                'status' => 'approved',
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
            ]);
        });

        // شمارنده‌ی کنار منوی پنل نباید عدد قدیمی نشان بدهد
        Cache::forget('admin.pending_ad_edits');

        return redirect()
            ->route('admin.ad-edits.index')
            ->with('success', 'ویرایش تأیید و روی آگهی اعمال شد.');
    }

    /*
    |--------------------------------------------------------------------------
    | رد درخواست
    |--------------------------------------------------------------------------
    */
    public function reject(Request $request, AdEdit $adEdit)
    {
        if ($adEdit->status !== 'pending') {
            return back()->with('error', 'این درخواست قبلاً بررسی شده است.');
        }

        $data = $request->validate(
            ['rejection_reason' => ['required', 'string', 'max:1000']],
            ['rejection_reason.required' => 'دلیل رد درخواست را بنویسید تا کاربر بداند چه چیزی را اصلاح کند.']
        );

        /*
        | تصاویری که فقط برای این ویرایشِ ردشده آپلود شده بودند حذف
        | می‌شوند؛ هیچ‌وقت به آگهی وصل نشدند و ماندنشان روی دیسک فقط
        | فضا اشغال می‌کند.
        */
        foreach ((array) $adEdit->added_images as $path) {
            Storage::disk('public')->delete($path);
        }

        $adEdit->update([
            'status' => 'rejected',
            'rejection_reason' => $data['rejection_reason'],
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'added_images' => null,
        ]);

        Cache::forget('admin.pending_ad_edits');

        return redirect()
            ->route('admin.ad-edits.index')
            ->with('success', 'درخواست ویرایش رد شد.');
    }
}
