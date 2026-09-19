@extends('layouts.app')

@section('title', 'اشتراک خدمات')

@section('content')

<div class="max-w-6xl mx-auto px-4 py-12">

    <h1 class="text-3xl font-black">
        پلن اشتراک خدمات
    </h1>

    <p class="text-gray-500 mt-2">
        برای ارائه خدمات در سایت، یکی از پلن‌های زیر را انتخاب کنید.
    </p>

    <div class="grid sm:grid-cols-2 lg:grid-cols-5 gap-5 mt-8">

        @foreach($plans as $plan)

            <div class="card p-5 flex flex-col">

                <h2 class="font-black text-lg">
                    {{ $plan->title }}
                </h2>

                <div class="text-2xl font-black text-blue-500 mt-5">
                    {{ number_format((float) $plan->price) }}

                    <span class="text-sm">
                        تومان
                    </span>
                </div>

                <div class="text-sm text-gray-500 mt-2">
                    ارائه خدمات نامحدود در مدت اشتراک
                </div>

                <form
                    method="POST"
                    action="{{ route('service.plans.pay', $plan) }}"
                    class="mt-auto pt-6"
                >

                    @csrf

                    <button
                        type="submit"
                        class="btn-primary w-full"
                    >
                        انتخاب و پرداخت
                    </button>

                </form>

            </div>

        @endforeach

    </div>

</div>

@endsection