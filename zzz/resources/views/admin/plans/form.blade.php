@extends('admin.layouts.app')

@section('content')

<h1 class="text-2xl font-black">
    {{ $plan->exists ? 'ویرایش پلن' : 'پلن جدید' }}
</h1>

<form
    method="POST"
    action="{{ $plan->exists ? route('admin.plans.update', $plan) : route('admin.plans.store') }}"
    class="bg-white p-6 rounded-xl mt-5 max-w-lg space-y-5"
>
    @csrf
    @if ($plan->exists)
        @method('PUT')
    @endif

    @if ($errors->any())
        <div class="bg-red-50 text-red-700 rounded-lg p-3 text-sm space-y-1">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <div>
        <label class="block mb-1 font-bold text-sm">نوع پلن</label>
        <select name="type" class="w-full rounded-lg border" {{ $plan->exists ? 'disabled' : '' }}>
            <option value="service" @selected(old('type', $plan->type) === 'service')>خدمات</option>
            <option value="product" @selected(old('type', $plan->type) === 'product')>محصول</option>
        </select>

        {{-- در حالت ویرایش نوع را عوض نمی‌کنیم تا اشتراک‌های قبلیِ همین
             پلن دچار ابهام نشوند؛ چون disabled است، مقدارش هم ارسال
             نمی‌شود - برای همین یک فیلد مخفی جداگانه لازم است. --}}
        @if ($plan->exists)
            <input type="hidden" name="type" value="{{ $plan->type }}">
        @endif
    </div>

    <div>
        <label class="block mb-1 font-bold text-sm">عنوان</label>
        <input name="title" value="{{ old('title', $plan->title) }}" class="w-full rounded-lg border">
    </div>

    <div>
        <label class="block mb-1 font-bold text-sm">مدت (ماه)</label>
        <input type="number" min="1" max="60" name="months" value="{{ old('months', $plan->months) }}" class="w-full rounded-lg border">
    </div>

    <div>
        <label class="block mb-1 font-bold text-sm">قیمت (تومان)</label>
        <input type="number" min="0" step="1" name="price" value="{{ old('price', $plan->price) }}" class="w-full rounded-lg border">
    </div>

    <div>
        <label class="block mb-1 font-bold text-sm">ترتیب نمایش</label>
        <input type="number" min="0" name="sort_order" value="{{ old('sort_order', $plan->sort_order ?? 0) }}" class="w-full rounded-lg border">
    </div>

    <label class="flex items-center gap-2">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $plan->is_active ?? true))>
        فعال (قابل خرید توسط کاربران)
    </label>

    <button class="btn-primary">ذخیره</button>
</form>

@endsection
