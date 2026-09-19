<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Ad;
use App\Models\AdEdit;
use App\Models\Category;
use App\Models\Province;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AdEditController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | فرم ویرایش آگهی
    |--------------------------------------------------------------------------
    */
    public function edit(Ad $ad)
    {
        $this->authorizeOwner($ad);

        return view('front.ad-edit', [
            'ad' => $ad->load('images'),
            'pendingEdit' => $ad->edits()->pending()->latest()->first(),
            'categories' => Category::where('type', $ad->type)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'provinces' => Province::with('cities')->orderBy('name')->get(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | ثبت درخواست ویرایش
    |--------------------------------------------------------------------------
    */
    public function update(Request $request, Ad $ad, ImageService $images)
    {
        $this->authorizeOwner($ad);

        /*
        | اگر درخواست بررسی‌نشده‌ای در صف است، درخواست دوم ساخته نمی‌شود.
        | در غیر این صورت کاربر می‌توانست صف ادمین را با ده نسخه از یک
        | آگهی پر کند و مشخص نبود کدام باید اعمال شود.
        */
        if ($ad->edits()->pending()->exists()) {
            return back()->with(
                'error',
                'یک درخواست ویرایش برای این آگهی در انتظار بررسی است. تا تعیین تکلیف آن نمی‌توانید ویرایش تازه‌ای ثبت کنید.'
            );
        }

        $data = $this->validated($request, $ad);

        /*
        | فقط فیلدهایی که واقعاً عوض شده‌اند نگه داشته می‌شوند. اگر
        | کاربر فرم را باز کند و بدون تغییر ذخیره کند، نباید کار
        | بیهوده‌ای روی میز ادمین بگذارد.
        */
        $changes = [];
        $original = [];

        foreach ($data as $field => $value) {

            $current = $ad->{$field};

            // مقایسه به‌صورت رشته تا 1000 و "1000.00" تغییر شمرده نشوند
            if ($this->normalise($current) === $this->normalise($value)) {
                continue;
            }

            $changes[$field] = $value;
            $original[$field] = $current instanceof \BackedEnum ? $current->value : $current;
        }

        $removedImageIds = array_map('intval', $request->input('delete_images', []));

        $addedPaths = [];

        foreach ($request->file('images', []) as $file) {
            $addedPaths[] = $images->upload($file);
        }

        if (! $changes && ! $removedImageIds && ! $addedPaths) {
            return back()->with('error', 'تغییری برای ثبت وجود ندارد.');
        }

        AdEdit::create([
            'ad_id' => $ad->id,
            'user_id' => auth()->id(),
            'payload' => $changes,
            'original' => $original,
            'added_images' => $addedPaths ?: null,
            'removed_image_ids' => $removedImageIds ?: null,
            'status' => 'pending',
        ]);

        return redirect()
            ->route($ad->type === 'product' ? 'product.panel' : 'service.panel')
            ->with(
                'success',
                'ویرایش شما ثبت شد و پس از تأیید مدیر روی آگهی اعمال می‌شود. تا آن زمان نسخه‌ی فعلی آگهی نمایش داده می‌شود.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | لغو درخواست بررسی‌نشده
    |--------------------------------------------------------------------------
    */
    public function cancel(Ad $ad)
    {
        $this->authorizeOwner($ad);

        $edit = $ad->edits()->pending()->latest()->first();

        if (! $edit) {
            return back()->with('error', 'درخواست ویرایشی در انتظار بررسی نیست.');
        }

        // تصاویری که برای این ویرایش آپلود شده بودند دیگر به‌کار نمی‌آیند
        foreach ((array) $edit->added_images as $path) {
            Storage::disk('public')->delete($path);
        }

        $edit->delete();

        return back()->with('success', 'درخواست ویرایش لغو شد.');
    }

    /*
    |--------------------------------------------------------------------------
    | اعتبارسنجی
    |--------------------------------------------------------------------------
    |
    | همان قوانین ثبت آگهی، به‌جز فیلدهایی که ارائه‌دهنده حق تغییرشان
    | را ندارد: نوع آگهی، وضعیت انتشار، تاریخ انقضا و نشان ویژه. اگر
    | این‌ها هم در فرم می‌آمدند، کاربر می‌توانست با دست‌کاری درخواست،
    | آگهی خودش را «ویژه» یا «تأییدشده» کند.
    |
    */
    private function validated(Request $request, Ad $ad): array
    {
        $request->merge([
            'phone' => $request->filled('phone') ? normalize_mobile($request->input('phone')) : null,
            'card_number' => $request->filled('card_number')
                ? preg_replace('/\D+/', '', fa_to_en_digits($request->input('card_number')))
                : null,
            'price' => $request->filled('price') ? fa_to_en_digits($request->input('price')) : null,
        ]);

        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'address' => ['required', 'string', 'max:1000'],
            'phone' => ['required', 'regex:/^0[0-9]{10}$/'],

            'category_id' => [
                'required',
                Rule::exists('categories', 'id')
                    ->where(fn ($q) => $q->where('type', $ad->type)->where('is_active', true)),
            ],
            'province_id' => ['required', 'exists:provinces,id'],
            'city_id' => [
                'required',
                Rule::exists('cities', 'id')
                    ->where(fn ($q) => $q->where('province_id', $request->input('province_id'))),
            ],

            'images' => ['nullable', 'array', 'max:10'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],

            'delete_images' => ['nullable', 'array'],
            'delete_images.*' => [
                'integer',
                Rule::exists('ad_images', 'id')->where(fn ($q) => $q->where('ad_id', $ad->id)),
            ],
        ];

        if ($ad->type === 'product') {
            $rules += [
                'brand' => ['nullable', 'string', 'max:100'],
                'model' => ['nullable', 'string', 'max:100'],
                'condition' => ['nullable', Rule::in(['new', 'used'])],
                'card_number' => ['required', 'digits:24'],
            ];
        } else {
            $rules += [
                'full_name' => ['required', 'string', 'max:255'],
                'service_title' => ['required', 'string', 'max:255'],
                'website' => ['nullable', 'url', 'max:255'],
            ];
        }

        $validated = $request->validate($rules, [
            'phone.regex' => 'شماره تلفن باید ۱۱ رقم و با ۰ شروع شود (مثال: 09121234567).',
            'category_id.exists' => 'دسته‌بندی انتخاب‌شده با نوع آگهی سازگار نیست.',
            'city_id.exists' => 'شهر انتخاب‌شده با استان سازگار نیست.',
            'card_number.digits' => 'شماره شبا باید ۲۴ رقم باشد (بدون IR).',
        ]);

        // این‌ها فیلد آگهی نیستند و نباید وارد payload شوند
        unset($validated['images'], $validated['delete_images']);

        return $validated;
    }

    private function normalise($value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_numeric($value)) {
            return rtrim(rtrim(number_format((float) $value, 4, '.', ''), '0'), '.');
        }

        return trim((string) $value);
    }

    private function authorizeOwner(Ad $ad): void
    {
        abort_unless($ad->user_id === auth()->id(), 403);
    }
}
