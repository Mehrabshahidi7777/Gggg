<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title', 'پنل مدیریت سازمت')</title>

    @vite([
        'resources/css/app.css',
        'resources/js/app.js'
    ])

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css"
    >
</head>

<body class="bg-gray-100">

    <div class="min-h-screen md:flex">

        {{-- Sidebar --}}
        <aside class="md:w-64 bg-gray-950 text-white p-5">

            <a
                href="{{ route('admin.dashboard') }}"
                class="text-2xl font-black"
            >
                سازمت
            </a>

            <div class="text-xs text-gray-400 mt-1">
                پنل مدیریت
            </div>

            <nav class="mt-8 space-y-2 text-sm">

                <a
                    href="{{ route('admin.dashboard') }}"
                    class="block p-2 rounded hover:bg-gray-800"
                >
                    داشبورد
                </a>

                <a
                    href="{{ route('admin.ads.index') }}"
                    class="block p-2 rounded hover:bg-gray-800"
                >
                    آگهی‌ها
                </a>

                <a
                    href="{{ route('admin.orders.index') }}"
                    class="block p-2 rounded hover:bg-gray-800"
                >
                    خرید و فروش‌ها
                </a>

                <a
                    href="{{ route('admin.sales.index') }}"
                    class="block p-2 rounded hover:bg-gray-800"
                >
                    گزارش فروش فروشندگان
                </a>

                <a
                    href="{{ route('admin.categories.index') }}"
                    class="block p-2 rounded hover:bg-gray-800"
                >
                    دسته‌بندی‌ها
                </a>

                <a
                    href="{{ route('admin.provinces.index') }}"
                    class="block p-2 rounded hover:bg-gray-800"
                >
                    استان‌ها
                </a>

                <a
                    href="{{ route('admin.cities.index') }}"
                    class="block p-2 rounded hover:bg-gray-800"
                >
                    شهرها
                </a>

                <a
                    href="{{ route('admin.users.index') }}"
                    class="block p-2 rounded hover:bg-gray-800"
                >
                    کاربران
                </a>
<a
    href="{{ route('admin.plans.index') }}"
    class="block p-2 rounded hover:bg-gray-800"
>
    پلن‌های اشتراک
</a>
<a
    href="{{ route('admin.contact-messages.index') }}"
    class="block p-2 rounded hover:bg-gray-800"
>
    پیام‌های تماس با ما
</a>
                <a
                    href="{{ route('admin.settings') }}"
                    class="block p-2 rounded hover:bg-gray-800"
                >
                    تنظیمات
                </a>

                <a
                    href="{{ route('home') }}"
                    class="block p-2 rounded hover:bg-gray-800"
                >
                    بازگشت به سایت
                </a>

            </nav>

        </aside>

        {{-- Main Content --}}
        <main class="flex-1 p-5 md:p-8">

            @if(session('success'))
                <div class="bg-green-50 text-green-700 p-3 rounded-lg mb-4">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-50 text-red-700 p-3 rounded-lg mb-4">
                    {{ session('error') }}
                </div>
            @endif

            @yield('content')

        </main>

    </div>

</body>
</html>