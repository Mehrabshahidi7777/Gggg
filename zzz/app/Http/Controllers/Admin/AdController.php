<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ad;
use App\Models\AdImage;
use App\Models\Category;
use App\Models\Province;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AdController extends Controller
{
    public function index(Request $r)
    {
        $q = Ad::with(['user', 'category', 'province', 'city'])->latest();

        if ($r->filled('status')) $q->where('status', $r->status);
        if ($r->filled('type')) $q->where('type', $r->type);
        if ($r->filled('search')) $q->where('title', 'like', '%' . $r->search . '%');

        return view('admin.ads.index', ['ads' => $q->paginate(20)->withQueryString()]);
    }

    public function show(Ad $ad)
    {
        $ad->load(['user', 'category', 'province', 'city', 'images']);

        return view('admin.ads.show', compact('ad'));
    }

    public function edit(Ad $ad)
    {
        $ad->load(['images', 'category', 'province', 'city']);

        return view('admin.ads.edit', [
            'ad' => $ad,
            'categories' => Category::orderBy('type')->orderBy('name')->get(),
            'provinces' => Province::with('cities')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $r, Ad $ad, ImageService $images)
    {
        $type = $r->input('type', $ad->type);

        $rules = [
            'type' => ['required', Rule::in(['product', 'service'])],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'price' => ['nullable', 'numeric', 'min:0'],

            'category_id' => [
                'required',
                Rule::exists('categories', 'id')->where(fn ($q) => $q->where('type', $type)),
            ],
            'province_id' => ['required', 'exists:provinces,id'],
            'city_id' => [
                'required',
                Rule::exists('cities', 'id')->where(fn ($q) => $q->where('province_id', $r->input('province_id'))),
            ],

            'address' => ['required', 'string', 'max:1000'],

            /*
            | همان قالبی که فرم ثبت و ویرایش آگهی اجبار می‌کند.
            | قبلاً اینجا فقط 'string|max:30' بود، یعنی ادمین می‌توانست
            | شماره‌ای ذخیره کند که فرم کاربر هرگز نمی‌پذیرفت - و
            | همان شماره بعداً در لینک tel: کار نمی‌کرد.
            */
            'phone' => ['required', 'regex:/^0[0-9]{10}$/'],

            'status' => ['required', Rule::in(['pending', 'approved', 'rejected'])],
            'rejection_reason' => ['nullable', 'string', 'max:1000'],
            'expires_at' => ['nullable', 'date'],
            'is_featured' => ['nullable', 'boolean'],
            'is_suspended' => ['nullable', 'boolean'],

            'images' => ['nullable', 'array', 'max:10'],
            // 100MB/image in the original request rule was almost certainly a typo
            // (102400 KB). 10MB is more than enough for a listing photo and closes
            // an easy resource-exhaustion / disk-fill path.
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],

            'delete_images' => ['nullable', 'array'],
            'delete_images.*' => [
                'integer',
                Rule::exists('ad_images', 'id')->where(fn ($q) => $q->where('ad_id', $ad->id)),
            ],

            'primary_image_id' => [
                'nullable',
                'integer',
                Rule::exists('ad_images', 'id')->where(fn ($q) => $q->where('ad_id', $ad->id)),
            ],
        ];

        if ($type === 'product') {
            $rules += [
                'brand' => ['nullable', 'string', 'max:100'],
                'model' => ['nullable', 'string', 'max:100'],
                'condition' => ['nullable', Rule::in(['new', 'used'])],
                'card_number' => ['nullable', 'digits:24'],
            ];
        } else {
            $rules += [
                'full_name' => ['required', 'string', 'max:255'],
                'service_title' => ['required', 'string', 'max:255'],
                'website' => ['nullable', 'url', 'max:255'],
            ];
        }

        /*
        | ارقام فارسی را قبل از اعتبارسنجی یکدست می‌کنیم، دقیقاً مثل
        | فرم سمت کاربر.
        */
        $r->merge([
            'phone' => $r->filled('phone') ? normalize_mobile($r->input('phone')) : null,
            'card_number' => $r->filled('card_number')
                ? preg_replace('/\D+/', '', fa_to_en_digits($r->input('card_number')))
                : null,
            'price' => $r->filled('price') ? fa_to_en_digits($r->input('price')) : null,
        ]);

        $data = $r->validate($rules, [
            'phone.regex' => 'شماره تلفن باید ۱۱ رقم و با ۰ شروع شود (مثال: 09121234567).',
            'category_id.exists' => 'دسته‌بندی انتخاب‌شده با نوع آگهی سازگار نیست.',
            'city_id.exists' => 'شهر انتخاب‌شده با استان سازگار نیست.',
            'card_number.digits' => 'شماره شبا باید ۲۴ رقم باشد (بدون IR).',
        ]);

        $data['is_featured'] = $r->boolean('is_featured');
        $data['is_suspended'] = $r->boolean('is_suspended');
        $data['suspended_at'] = $data['is_suspended']
            ? ($ad->is_suspended ? $ad->suspended_at : now())
            : null;

        // Clear out fields that don't apply to the other type instead of
        // leaving stale product data on a service ad (or vice versa).
        if ($type === 'product') {
            $data['full_name'] = null;
            $data['service_title'] = null;
            $data['website'] = null;
        } else {
            $data['brand'] = null;
            $data['model'] = null;
            $data['condition'] = null;
            $data['card_number'] = null;
        }

        DB::transaction(function () use ($r, $ad, $data, $images) {
            $ad->update($data);

            foreach ($r->input('delete_images', []) as $imageId) {
                $image = $ad->images()->whereKey($imageId)->first();
                if (!$image) continue;

                $images->delete($image->path);
                $image->delete();
            }

            foreach ($r->file('images', []) as $file) {
                AdImage::create([
                    'ad_id' => $ad->id,
                    'path' => $images->upload($file),
                    'is_primary' => false,
                ]);
            }

            $primaryId = $r->input('primary_image_id');
            if ($primaryId && $ad->images()->whereKey($primaryId)->exists()) {
                $ad->images()->update(['is_primary' => false]);
                $ad->images()->whereKey($primaryId)->update(['is_primary' => true]);
            } elseif (!$ad->images()->where('is_primary', true)->exists()) {
                $ad->images()->orderBy('id')->limit(1)->update(['is_primary' => true]);
            }
        });

        return redirect()->route('admin.ads.show', $ad)->with('success', 'آگهی به‌روزرسانی شد.');
    }

    public function approve(Ad $ad)
    {
        $ad->update(['status' => 'approved', 'rejection_reason' => null]);

        return back()->with('success', 'آگهی تأیید شد.');
    }

    public function reject(Request $r, Ad $ad)
    {
        $ad->update(['status' => 'rejected', 'rejection_reason' => $r->input('rejection_reason')]);

        return back()->with('success', 'آگهی رد شد.');
    }

    public function feature(Ad $ad)
    {
        $ad->update(['is_featured' => !$ad->is_featured]);

        return back()->with('success', $ad->is_featured ? 'آگهی ویژه شد.' : 'ویژه بودن آگهی برداشته شد.');
    }

    public function destroy(Ad $ad)
    {
        $ad->load('images');

        foreach ($ad->images as $image) {
            Storage::disk('public')->delete($image->path);
        }

        $ad->delete();

        return redirect()->route('admin.ads.index')->with('success', 'آگهی و اطلاعات وابسته آن با موفقیت حذف شد.');
    }

    public function create()
    {
        return redirect()->route('admin.ads.index');
    }

    public function store(Request $r)
    {
        return redirect()->route('admin.ads.index');
    }
}
