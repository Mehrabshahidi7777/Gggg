@extends('admin.layouts.app')

@section('content')

<div class="flex justify-between gap-4">
    <h1 class="text-2xl font-black">
        سفارش {{ $order->order_number }}
    </h1>

    <span>
        {{ $order->status_text }}
    </span>
</div>

<div class="grid lg:grid-cols-2 gap-5 mt-5">

    {{-- اطلاعات خریدار --}}
    <div class="bg-white rounded-xl p-5">
        <h2 class="font-black">
            خریدار
        </h2>

        <p class="mt-3">
            نام کاربری:
            {{ $order->buyer?->username ?: ($order->buyer?->name ?? 'حذف‌شده') }}
        </p>

        <p class="mt-2">
            ایمیل:
            {{ $order->buyer?->email ?: '—' }}
        </p>

        <p class="mt-2">
            شماره مشتری:
            {{ $order->phone ?: '—' }}
        </p>

        <p class="mt-2 leading-7">
            آدرس:
            {{ $order->address ?: '—' }}
        </p>
    </div>

    {{-- وضعیت سفارش --}}
    <div class="bg-white rounded-xl p-5">
        <h2 class="font-black">
            وضعیت سفارش
        </h2>

        <form
            method="POST"
            action="{{ route('admin.orders.update', $order) }}"
            class="mt-4 flex gap-2"
        >
            @csrf
            @method('PATCH')

            <select
                name="status"
                class="rounded-lg border flex-1"
            >
                @foreach([
                    'pending' => 'در انتظار پرداخت',
                    'paid' => 'پرداخت‌شده',
                    'processing' => 'آماده‌سازی',
                    'shipped' => 'ارسال‌شده',
                    'completed' => 'تکمیل‌شده',
                    'cancelled' => 'لغوشده'
                ] as $k => $v)

                    <option
                        value="{{ $k }}"
                        @selected($order->status === $k)
                    >
                        {{ $v }}
                    </option>

                @endforeach
            </select>

            <button class="btn-primary">
                ذخیره
            </button>
        </form>
    </div>

</div>

{{-- اقلام خرید --}}
<div class="bg-white rounded-xl p-5 mt-5">

    <h2 class="font-black">
        اقلام خرید
    </h2>

    <div class="overflow-auto">

        <table class="w-full text-sm mt-4">

            <thead>
                <tr class="border-b">
                    <th class="p-3 text-right">
                        محصول
                    </th>

                    <th>
                        فروشنده
                    </th>

                    <th>
                        شماره فروشنده
                    </th>

                    <th>
                        شماره شبا
                    </th>

                    <th>
                        تعداد
                    </th>

                    <th>
                        قیمت واحد
                    </th>

                    <th>
                        جمع
                    </th>
                </tr>
            </thead>

            <tbody>

                @foreach($order->items as $item)

                    <tr class="border-b">

                        <td class="p-3">
                            {{ $item->title }}
                        </td>

                        <td>
                            {{ $item->seller?->username ?: ($item->seller?->name ?? 'حذف‌شده') }}
                        </td>

                        <td>
                            {{ $item->seller_phone ?: '—' }}
                        </td>

                        <td>
                            {{ $item->ad?->card_number ? 'IR'.$item->ad->card_number : '—' }}
                        </td>

                        <td>
                            {{ $item->quantity }}
                        </td>

                        <td>
                            {{ number_format($item->unit_price) }}
                        </td>

                        <td>
                            {{ number_format($item->subtotal) }}
                        </td>

                    </tr>

                @endforeach

            </tbody>

        </table>

    </div>

</div>

@endsection