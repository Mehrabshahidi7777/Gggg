<!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title','سازمت')</title>
<meta name="description" content="@yield('meta_description', 'سازمت، بازار آنلاین محصولات و خدمات صنعت ساختمان. محصولات، خدمات و متخصصان صنعت ساختمان را پیدا کن.')">

{{--
    آدرس متعارف (canonical).

    بدون این تگ، هر ترکیبی از پارامترهای فیلتر و صفحه‌بندی
    (?page=2&sort=popular&province=3&...) از نظر گوگل یک صفحه‌ی
    جداگانه با محتوای تقریباً تکراری است. نتیجه‌اش پخش‌شدن اعتبار
    صفحه بین ده‌ها آدرس و افت رتبه است.

    پیش‌فرض، آدرس فعلی بدون هیچ پارامتری است؛ صفحه‌هایی که
    صفحه‌بندی دارند خودشان canonical مناسب را تعریف می‌کنند.
--}}
<link rel="canonical" href="@yield('canonical', url()->current())">

{{--
    تأیید مالکیت سایت در گوگل سرچ کنسول.

    تا وقتی GOOGLE_SITE_VERIFICATION در فایل .env خالی باشد هیچ تگی
    چاپ نمی‌شود؛ بنابراین این خط برای سایتی که هنوز ثبت نشده کاملاً
    بی‌اثر است. توضیح کامل در config/seo.php.
--}}
@if($googleVerification = config('seo.google_site_verification'))
<meta name="google-site-verification" content="{{ $googleVerification }}">
@endif

{{-- داده‌ی ساخت‌یافته و تگ‌های اختصاصی هر صفحه --}}
@stack('head')

{{-- آیکون سایت و تصویر برند برای نتایج گوگل / پیش‌نمایش لینک --}}
<link rel="icon" type="image/jpeg" href="{{ asset('images/sazmat-logo.jpg') }}">
<link rel="apple-touch-icon" href="{{ asset('images/sazmat-logo.jpg') }}">
<meta property="og:site_name" content="سازمت">
<meta property="og:title" content="@yield('title','سازمت | بازار آنلاین صنعت ساختمان')">
<meta property="og:description" content="@yield('meta_description', 'بازار آنلاین محصولات و خدمات صنعت ساختمان.')">
<meta property="og:image" content="{{ asset('images/sazmat-logo.jpg') }}">
<meta property="og:url" content="{{ url()->current() }}">
<meta property="og:type" content="website">
<meta name="twitter:card" content="summary">
<meta name="twitter:image" content="{{ asset('images/sazmat-logo.jpg') }}">
<script type="application/ld+json">
{
  "@@context": "https://schema.org",
    "@@type": "Organization",
      "name": "سازمت",
        "url": "{{ url('/') }}",
          "logo": "{{ asset('images/sazmat-logo.jpg') }}"
          }
          </script>

          @vite(['resources/css/app.css','resources/js/app.js'])
          <link rel="stylesheet" href="{{ asset('css/design-tokens.css') }}?v={{ @filemtime(public_path('css/design-tokens.css')) }}">
          <link rel="stylesheet" href="{{ asset('css/sazmat-theme.css') }}?v={{ @filemtime(public_path('css/sazmat-theme.css')) }}">
          {{--
              وزیرمتن حالا از خود هاست می‌آید (تعریف @font-face در بالای
              design-tokens.css). خط jsdelivr برداشته شد: برای کاربر
              ایرانی آن CDN گاهی کند یا در دسترس نیست و تا رسیدنِ فونت،
              سایت شکل خودش را ندارد.

              سه وزنی که همان ابتدای صفحه لازم‌اند preload می‌شوند تا متن
              با قلم درست رندر شود، نه اینکه اول با قلم سیستم بیاید و بعد
              بپرد: ۴۰۰ برای بدنه، ۷۰۰ برای برچسب‌ها، و ۹۰۰ برای عنوان
              اصلی.

              وزن ۹۰۰ اول جا افتاده بود. عنوان هیرو با همان رندر می‌شود و
              درست بالای صفحه است، پس تا رسیدنش با قلم پیش‌فرض سیستم
              نشان داده می‌شد و بعد می‌پرید - که چشم آن را «یک جور
              دیگر، کم‌جان‌تر» می‌بیند.
          --}}
          <link rel="preload" as="font" type="font/woff2" crossorigin
                href="{{ asset('fonts/Vazirmatn-Regular.woff2') }}">
          <link rel="preload" as="font" type="font/woff2" crossorigin
                href="{{ asset('fonts/Vazirmatn-Bold.woff2') }}">
          <link rel="preload" as="font" type="font/woff2" crossorigin
                href="{{ asset('fonts/Vazirmatn-Black.woff2') }}">

          <style>
          .cart-badge {
          min-width: 20px;
          height: 20px;
          padding: 0 5px;
          display: inline-flex;
          align-items: center;
          justify-content: center;
          background: var(--color-orange, #BF5522);
          color: #fff;
          border-radius: 999px;
          font-size: 10px;
          font-weight: 900;
          line-height: 1;
          border: 2px solid #fff;
          box-shadow: 0 2px 6px rgba(191,85,34,.3);
          }
          .cart-badge.hidden { display:none !important; }
          .cart-badge.pulse { animation: cartBadgePulse .35s ease; }
          @keyframes cartBadgePulse {
          0% { transform:scale(.7); opacity:.5; }
          70% { transform:scale(1.15); opacity:1; }
          100% { transform:scale(1); }
          }
          .account-menu { position:relative; }
          .account-trigger-wrap { position:relative; display:flex; align-items:center; }
          .account-trigger {
          display:flex; align-items:center; gap:6px; font-weight:700; font-size:.85rem;
          color:#fff; background:var(--color-ink,#0E2A47); padding:9px 16px;
          border-radius:999px; border:none; white-space:nowrap; cursor:pointer;
          }
          .account-trigger:hover { background:#16375c; }
          .account-trigger svg { width:16px; height:16px; flex-shrink:0; }
          .account-header-badge { position:absolute; left:-8px; top:-7px; z-index:2; flex:0 0 auto; }
          .header-actions .btn-sm { white-space:nowrap; }
          .account-dropdown {
          position:absolute; inset-inline-end:0; top:calc(100% + 10px); min-width:235px;
          background:#fff; border:1px solid var(--color-line,#E1E6EC);
          border-radius:var(--radius-md,12px); box-shadow:var(--shadow-lg,0 16px 48px rgba(14,42,71,.14));
          padding:8px; display:none; z-index:120;
          }
          .account-dropdown.is-open { display:block; }
          .account-dropdown a,.account-dropdown button {
          display:flex; align-items:center; gap:9px; width:100%; text-align:right;
          padding:10px 12px; border-radius:8px; font-size:.88rem; font-weight:600;
          color:var(--color-steel,#4B5C70); background:transparent; border:0; cursor:pointer;
          }
          .account-dropdown a:hover,.account-dropdown button:hover { background:var(--color-paper,#F6F7F9); color:var(--color-ink,#0E2A47); }
          .account-dropdown .is-danger { color:var(--color-red,#B23B2E); }
          .account-dropdown .menu-count {
          margin-inline-start:auto; min-width:20px; height:20px; padding:0 5px;
          border-radius:999px; display:inline-flex; align-items:center; justify-content:center;
          background:var(--color-orange,#BF5522); color:#fff; font-size:10px; font-weight:900;
          }
          .cart-guest { position:relative; }
          .cart-link { display:flex; align-items:center; gap:6px; padding:9px 14px; border-radius:999px; border:1.5px solid var(--color-line-strong,#C7D0DB); color:var(--color-ink,#0E2A47); font-weight:700; font-size:.85rem; white-space:nowrap; }
          .cart-link:hover { border-color:var(--color-blueprint); color:var(--color-blueprint); }
          .cart-link svg { width:18px; height:18px; flex-shrink:0; }
          @media (max-width:860px) {
          .header-actions { gap:8px; }
          .account-trigger { padding:9px 12px; font-size:.8rem; }
          .cart-link { padding:9px 10px; }
          .cart-link .txt-full { display:none; }
          }
          </style>
          </head>
          <body>
          <header class="site-header">
          <div class="container">
          <a href="{{ route('home') }}" class="brand">
          <img src="{{ asset('images/sazmat-logo.jpg') }}" alt="Sazmat Construction" style="height:52px;width:auto;max-width:none;object-fit:contain;">
          </a>

          <nav class="main-nav" aria-label="ناوبری اصلی">
          <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'is-active' : '' }}">خانه</a>
          <a href="{{ route('products') }}" class="{{ request()->routeIs('products') ? 'is-active' : '' }}">محصولات</a>
          <a href="{{ route('services') }}" class="{{ request()->routeIs('services') ? 'is-active' : '' }}">خدمات</a>
          <a href="{{ route('about') }}" class="{{ request()->routeIs('about') ? 'is-active' : '' }}">درباره ما</a>
          <a href="{{ route('contact') }}" class="{{ request()->routeIs('contact') ? 'is-active' : '' }}">تماس</a>
          </nav>

          <div class="header-actions">
          {{-- شمارش سبد خرید فقط وقتی معنی دارد که خرید آنلاین روشن باشد. --}}
          @php $cartEnabled = config('marketplace.online_checkout'); @endphp
          @php $cartCount = $cartEnabled ? array_sum(array_map('intval', session('cart', []))) : 0; @endphp

          @auth
          {{--
              نارنجی در هدر فقط روی «ثبت آگهی» می‌نشیند و جای دیگری خرج
              نمی‌شود. هدر روی همه‌ی صفحه‌ها دیده می‌شود، پس هر نارنجیِ
              اضافه‌ای اینجا با دکمه‌ی اصلیِ خودِ آن صفحه رقابت می‌کند.

              «ثبت‌نام» اقدام فرعی است (خودِ «ثبت آگهی» هم کاربر را به
              ثبت‌نام می‌برد) و «بله، خروج» یک تأیید است، نه اقدام اصلی.

              ولی «ورود» و «ثبت‌نام» هم نباید هم‌وزن باشند: برای مهمان،
              ثبت‌نام کاربر تازه می‌آورد و ورود فقط برای کسی است که از قبل
              حساب دارد. پس ثبت‌نام سرمه‌ای پُر است و ورود خنثی با حاشیه —
              سلسله‌مراتب ساخته می‌شود بدون اینکه نارنجی خرج شود.
          --}}
          <a href="{{ route('ad.create') }}" class="btn btn-primary btn-sm">ثبت آگهی</a>

          <div class="account-menu">
          <div class="account-trigger-wrap">
          <button type="button" class="account-trigger" id="account-toggle" aria-expanded="false" aria-controls="account-dropdown">
          <span data-icon="users"></span>
          <span>پنل کاربری</span>
          </button>
          @if($cartEnabled)
          <span
          class="cart-badge account-header-badge {{ $cartCount > 0 ? '' : 'hidden' }}"
          id="cart-count-badge"
          aria-label="{{ $cartCount }} کالا در سبد خرید"
          >{{ $cartCount > 99 ? '99+' : $cartCount }}</span>
          @endif
          </div>

          <div class="account-dropdown" id="account-dropdown">
          <a href="{{ route('profile') }}"><span data-icon="users"></span><span>{{ auth()->user()->username ?: auth()->user()->name }}</span></a>

          @if($cartEnabled)
          <a href="{{ route('cart.index') }}">
          <span data-icon="box"></span><span>سبد خرید</span>
          <span class="menu-count {{ $cartCount > 0 ? '' : 'hidden' }}" id="cart-menu-count">{{ $cartCount > 99 ? '99+' : $cartCount }}</span>
          </a>
          @endif

          @if(auth()->user()->ads()->where('type','service')->exists() || auth()->user()->serviceSubscriptions()->exists())
          <a href="{{ route('service.panel') }}"><span data-icon="settings"></span><span>پنل ارائه خدمات</span></a>
          @endif

          @if(auth()->user()->ads()->where('type','product')->exists() || auth()->user()->productSubscriptions()->exists())
          <a href="{{ route('product.panel') }}"><span data-icon="settings"></span><span>پنل ارائه محصولات</span></a>
          @endif

          {{-- «پنل فروشنده» فقط برای رسیدگی به سفارش‌های آنلاینِ قبلی است.
               خرید آنلاین از روی آگهی‌ها برداشته شده، پس این پنل دیگر
               سفارش جدید نمی‌گیرد و تنها به کسی نشان داده می‌شود که
               سفارشِ باز و ناتمامی از قبل دارد. --}}
          @if(auth()->user()->orderItems()->whereIn('status',['paid','processing','shipped'])->exists())
          <a href="{{ route('seller.panel') }}"><span data-icon="building"></span><span>سفارش‌های در جریان</span></a>
          @endif

          {{-- «پنل مشتری» اینجا بود و چون خرید آنلاین خاموش است همیشه
               سه صفر نشان می‌داد. جایش را فعالیت واقعیِ کاربر گرفت:
               امتیازها، نظرها و شماره‌هایی که دیده. سفارش‌های قدیمی
               از داخل همان صفحه در دسترس‌اند، آن هم فقط برای کسی که
               سفارشی دارد. --}}
          <a href="{{ route('activity') }}"><span data-icon="users"></span><span>فعالیت‌های من</span></a>

          @if(auth()->user()->is_admin)
          <a href="{{ route('admin.dashboard') }}"><span data-icon="dashboard"></span><span>مدیریت</span></a>
          @endif

          <button type="button" id="logout-open" class="is-danger"><span data-icon="logout"></span><span>خروج</span></button>
          </div>
          </div>
          @else
          @if($cartEnabled)
          <a href="{{ route('cart.index') }}" class="cart-guest cart-link" aria-label="سبد خرید">
          <span data-icon="box"></span><span class="txt-full">سبد خرید</span>
          <span class="cart-badge {{ $cartCount > 0 ? '' : 'hidden' }}" id="guest-cart-count-badge">{{ $cartCount > 99 ? '99+' : $cartCount }}</span>
          </a>
          @endif
          {{--
              این دو کنار هم می‌نشینند و هم‌رده‌اند، پس چشم انتظار دارد
              هم‌اندازه باشند. header-auth-group با یک گرید دو ستونه
              هر دو را به عرض پهن‌ترین متن می‌رساند - نه بیشتر. ظرف
              لازم است چون .header-actions آیتم‌های دیگری هم دارد
              (سبد خرید، دکمه‌ی همبرگری) که نباید هم‌عرض این دو شوند.
          --}}
          <div class="header-auth-group">
          <a href="{{ route('login') }}" class="btn btn-ghost btn-sm header-auth">ورود</a>
          <a href="{{ route('register') }}" class="btn btn-navy btn-sm header-auth">ثبت‌نام</a>
          </div>
          @endauth

          <button class="menu-toggle" aria-label="باز کردن منو" aria-expanded="false"><span></span></button>
          </div>
          </div>
          </header>

          @auth
          <div id="logout-modal" class="hidden fixed inset-0 z-[100] bg-black/50 items-center justify-center px-4">
          <div class="bg-white rounded-2xl p-6 max-w-sm w-full shadow-xl">
          <h2 class="font-black text-lg">آیا می‌خواهید خارج شوید؟</h2>
          <p class="text-sm text-gray-500 mt-2">با تأیید، از حساب کاربری خارج می‌شوید.</p>
          <div class="flex gap-3 mt-6">
          <form method="POST" action="{{ route('logout') }}" class="flex-1">@csrf<button class="btn btn-navy btn-block" type="submit">بله، خروج</button></form>
          <button type="button" id="logout-close" class="btn btn-ghost" style="flex:1;">خیر</button>
          </div>
          </div>
          </div>
          @endauth

          @if(session('success'))
          <div class="container" style="margin-top:16px;"><div class="bg-green-50 text-green-700 border border-green-200 p-3 rounded-lg">{{ session('success') }}</div></div>
          @endif
          @if(session('error'))
          <div class="container" style="margin-top:16px;"><div class="bg-red-50 text-red-700 border border-red-200 p-3 rounded-lg">{{ session('error') }}</div></div>
          @endif
          @if($errors->any())
          <div class="container" style="margin-top:16px;"><div class="bg-red-50 text-red-700 border border-red-200 p-3 rounded-lg"><ul class="list-disc mr-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div></div>
          @endif

          <main>@yield('content')</main>

          <footer class="site-footer">
          <div class="container">
          <div class="footer-grid">
          <div class="footer-about">
          <h3 style="color:#fff;font-size:1.25rem;font-weight:800;">سازمت</h3>
          <p>بازار آنلاین محصولات و خدمات صنعت ساختمان.</p>
          <a rel="noopener" referrerpolicy="origin" target="_blank" href="https://trustseal.enamad.ir/?id=761668&Code=cXlNee9d8gjzkSIXsjk2F9Mm13xtWdnZ">
          <img referrerpolicy="origin" src="https://trustseal.enamad.ir/logo.aspx?id=761668&Code=cXlNee9d8gjzkSIXsjk2F9Mm13xtWdnZ" alt="نماد اعتماد الکترونیکی" style="cursor:pointer;margin-top:14px;border-radius:8px;" code="cXlNee9d8gjzkSIXsjk2F9Mm13xtWdnZ">
          </a>
          </div>
          <div>
          <h4>دسترسی سریع</h4>
          <ul class="footer-links">
          <li><a href="{{ route('products') }}">محصولات</a></li>
          <li><a href="{{ route('services') }}">خدمات</a></li>
          <li><a href="{{ route('categories.index') }}">دسته‌بندی‌ها</a></li>
          <li><a href="{{ route('contact') }}">تماس با ما</a></li>
          </ul>
          </div>
          <div>
          <h4>تماس</h4>
          <ul class="footer-contact">
          <li><span data-icon="mail"></span><span>{{ \App\Models\Setting::get('contact_email','support@sazmat.com') }}</span></li>
          <li><span data-icon="phone"></span><span>09134451542</span></li>
          </ul>
          </div>
          </div>
          <div class="footer-bottom"><span>© {{ date('Y') }} سازمت</span></div>
          </div>
          </footer>

          @stack('scripts')

          <script>
          /*
          | سقف تعداد تصویر، همان‌جا در مرورگر.
          |
          | اعتبارسنجی سمت سرور از قبل هست و حرف آخر را می‌زند
          | (AdSubmitRequest و AdEditController)، ولی تا وقتی فقط آنجا
          | باشد کاربر بیست فایل را آپلود می‌کند، منتظر می‌ماند، و بعد
          | خطا می‌گیرد. این بررسی پیش از ارسال جلویش را می‌گیرد.
          |
          | ⚠️ اینجا قبلاً ورودی فایل خالی می‌شد و یک باگ بد می‌ساخت.
          |
          | وقتی کاربر بیشتر از سقف انتخاب می‌کرد، اسکریپت انتخابش را
          | پاک می‌کرد و فقط یک خط راهنمای کوچک می‌نوشت. کاربر آن خط را
          | نمی‌دید، فرم را می‌فرستاد، و آگهی با *صفر* تصویر ثبت
          | می‌شد - چون دیگر هیچ فایلی در فرم نبود، پس حتی خطای سرور هم
          | اتفاق نمی‌افتاد. کاربر فکر می‌کرد عکس‌هایش رفته‌اند.
          |
          | حالا انتخاب کاربر هرگز پاک نمی‌شود. موقع انتخاب فقط تعداد
          | گفته می‌شود و هیچ ایرادی گرفته نمی‌شود؛ ایراد سرِ «ثبت»
          | گرفته می‌شود، با پیامی که دقیقاً می‌گوید چند تا زیادی است.
          */
          (function () {
          document.querySelectorAll('input[type="file"][data-max-images]').forEach(function (input) {

          const note = document.querySelector(input.dataset.imagesNote || '');
          const form = input.form;
          const isEditForm = input.dataset.imagesRemaining !== undefined;

          /*
           * برچسبِ کادر بزرگ آپلود («۱۱ عکس انتخاب شد»).
           *
           * این برچسب اسکریپت خودش را دارد و مستقل از پیام زیرش
           * نوشته می‌شود. نتیجه‌اش این بود که بعد از خطا، کادرِ بزرگ
           * و پررنگ خبر خوش می‌داد و خطا در یک خط کوچک زیرش
           * می‌نشست - چشم اولی را می‌دید و دومی را نه.
           */
          const label = document.querySelector(input.dataset.imagesLabel || '');
          const labelBox = label ? label.closest('.file-upload-box') || label : null;

          const picked = function () {
          return input.files ? input.files.length : 0;
          };

          /*
           * ظرفیت واقعی.
           *
           * در فرم ثبت، همان سقف کل است.
           *
           * در فرم ویرایش، «باقی‌مانده» است - ولی این عدد زنده است، نه
           * چیزی که سرور یک بار فرستاده: کاربر همین حالا می‌تواند چند
           * تصویر را برای حذف تیک بزند و جا باز کند. با عدد ثابت،
           * کسی که ۳ تصویر را برای حذف تیک زده بود بی‌دلیل بلوکه
           * می‌شد.
           */
          const capacity = function () {

          const total = parseInt(input.dataset.maxImages, 10);

          if (! isEditForm) { return total; }

          const boxes = form ? form.querySelectorAll('input[name="delete_images[]"]') : [];

          if (! boxes.length) { return parseInt(input.dataset.imagesRemaining, 10); }

          let keeping = 0;
          boxes.forEach(function (box) { if (! box.checked) { keeping++; } });

          return total - keeping;
          };

          /* موقع انتخاب فقط شمارش - بدون ایراد، بدون قرمز. */
          const showCount = function () {
          if (labelBox) { labelBox.classList.remove('is-error'); }
          if (! note) { return; }
          note.classList.remove('is-error');
          note.textContent = picked() === 0 ? '' : picked() + ' تصویر انتخاب شد.';
          };

          const tooManyMessage = function () {

          const max = capacity();
          const extra = picked() - max;

          if (max <= 0) {
          return 'این آگهی به سقف ' + input.dataset.maxImages + ' تصویر رسیده است. '
          + 'برای افزودن تصویر تازه، اول چند تصویر بالا را برای حذف تیک بزنید.';
          }

          /* «دیگر» فقط در فرم ویرایش معنی دارد، که جای خالی می‌شمارد. */
          const room = isEditForm
          ? 'در این آگهی جا برای ' + max + ' تصویر دیگر هست'
          : 'برای هر آگهی حداکثر ' + max + ' تصویر می‌توانید انتخاب کنید';

          return room + '، ولی ' + picked() + ' تصویر انتخاب کرده‌اید. '
          + 'لطفاً ' + extra + ' تصویر را کم کنید و دوباره امتحان کنید.';
          };

          input.addEventListener('change', showCount);

          /* تیک حذف، ظرفیت را عوض می‌کند. */
          if (form) {
          form.addEventListener('change', function (event) {
          if (event.target.name === 'delete_images[]') { showCount(); }
          });
          }

          if (! form) { return; }

          const overLimit = function () { return picked() > capacity(); };

          /*
           * خطا باید در هر دو جا دیده شود.
           *
           * انتخاب کاربر عمداً پاک نمی‌شود - همان کاری که این باگ را
           * ساخته بود - پس «عکسی انتخاب نشده» دروغ می‌بود: فایل‌ها
           * هنوز در فرم‌اند و اگر پاکشان کنیم، کاربری که دوباره
           * انتخاب نکند آگهی‌اش بی‌تصویر ثبت می‌شود.
           *
           * به جایش خودِ کادر می‌گوید چه خبر است، تا پیامِ پررنگ و
           * پیامِ کوچک یک چیز بگویند.
           */
          const complain = function () {

          if (labelBox) { labelBox.classList.add('is-error'); }

          if (label) {
          label.textContent = picked() + ' عکس انتخاب شد — بیشتر از حد مجاز. '
          + 'دوباره روی همین کادر بزنید و کمتر انتخاب کنید.';
          }

          if (! note) { return; }
          note.textContent = tooManyMessage();
          note.classList.add('is-error');
          };

          /*
           * چرا هم click و هم submit؟
           *
           * اعتبارسنجی خود مرورگر جلوی رویداد submit را می‌گیرد: اگر
           * فیلد لازمِ دیگری خالی باشد، اصلاً submit شلیک نمی‌شود.
           * یعنی با شنیدنِ تنها submit، کاربری که هم ۱۵ تصویر انتخاب
           * کرده و هم آدرس را ننوشته، اول فقط از آدرس خبردار می‌شد و
           * تازه دور بعد می‌فهمید تصویرها هم زیادی‌اند.
           *
           * پس پیام روی کلیکِ دکمه‌ی ثبت نوشته می‌شود - همان لحظه‌ای
           * که کاربر منتظر جواب است - و جلوگیری از ارسال سرِ submit
           * انجام می‌شود، که حرف آخر را می‌زند.
           */
          form.addEventListener('click', function (event) {

          const trigger = event.target.closest(
          'button[type="submit"], input[type="submit"], button:not([type])'
          );

          if (trigger && trigger.form === form && overLimit()) { complain(); }
          });

          form.addEventListener('submit', function (event) {

          if (! overLimit()) { return; }

          event.preventDefault();
          complain();

          (note || input).scrollIntoView({ behavior: 'smooth', block: 'center' });
          });

          });
          })();
          </script>

          <script>
          (function(){
          const faDigits='۰۱۲۳۴۵۶۷۸۹',arDigits='٠١٢٣٤٥٦٧٨٩';
          window.sazmatDigitsOnly=function(el,maxLen,allowPlus){
          let v=el.value;
          v=v.replace(/[۰-۹]/g,d=>faDigits.indexOf(d)).replace(/[٠-٩]/g,d=>arDigits.indexOf(d));
          v=allowPlus?v.replace(/(?!^)\+|[^0-9+]/g,''):v.replace(/[^0-9]/g,'');
          if(maxLen)v=v.slice(0,maxLen);
          el.value=v;
          };
          document.addEventListener('DOMContentLoaded',()=>{
          document.querySelectorAll('input[data-digits-only]').forEach(el=>{
          const max=el.getAttribute('maxlength')||null;
          const allowPlus=el.hasAttribute('data-allow-plus');
          el.addEventListener('input',()=>window.sazmatDigitsOnly(el,max,allowPlus));
          });
          });
          })();
          </script>
          <script>
          window.SAZMET_ICON = function (name) {
          const common='viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"';
          const icons={
          search:`<svg ${common}><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>`,
          grid:`<svg ${common}><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>`,
          users:`<svg ${common}><circle cx="9" cy="8" r="3.2"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><circle cx="17.5" cy="9" r="2.6"/><path d="M15 20a5.5 4.7 0 0 1 8.5-3.9"/></svg>`,
          box:`<svg ${common}><path d="M21 8 12 3 3 8v8l9 5 9-5V8Z"/><path d="M3 8l9 5 9-5M12 13v8"/></svg>`,
          settings:`<svg ${common}><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.6V21a2 2 0 1 1-4 0v-.2a1.7 1.7 0 0 0-1-1.6 1.7 1.7 0 0 0-1.9.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.9 1.7 1.7 0 0 0-1.6-1H3a2 2 0 1 1 0-4h.2a1.7 1.7 0 0 0 1.6-1 1.7 1.7 0 0 0-.3-1.9l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 1 1.9.3H9a1.7 1.7 0 0 0 1-1.6V3a2 2 0 1 1 4 0v.2a1.7 1.7 0 0 0 1 1.6 1.7 1.7 0 0 0 1.9-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.9V9a1.7 1.7 0 0 0 1.6 1H21a2 2 0 1 1 0 4h-.2a1.7 1.7 0 0 0-1.6 1Z"/></svg>`,
          building:`<svg ${common}><rect x="4" y="3" width="16" height="18" rx="1"/><path d="M8 7h2M14 7h2M8 11h2M14 11h2M8 15h2M14 15h2M10 21v-4h4v4"/></svg>`,
          dashboard:`<svg ${common}><rect x="3" y="3" width="8" height="10" rx="1.2"/><rect x="13" y="3" width="8" height="6" rx="1.2"/><rect x="13" y="11" width="8" height="10" rx="1.2"/><rect x="3" y="15" width="8" height="6" rx="1.2"/></svg>`,
          logout:`<svg ${common}><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5M21 12H9"/></svg>`,
          mail:`<svg ${common}><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>`,
          phone:`<svg ${common}><path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2Z"/></svg>`,
          map:`<svg ${common}><path d="M12 21s7-5.5 7-11a7 7 0 1 0-14 0c0 5.5 7 11 7 11Z"/><circle cx="12" cy="10" r="2.5"/></svg>`,
          filter:`<svg ${common}><path d="M4 5h16M7 12h10M10 19h4"/></svg>`,
          star:`<svg ${common}><path d="m12 3 2.7 5.9 6.3.7-4.7 4.4 1.2 6.3L12 17.4l-5.5 2.9 1.2-6.3L3 9.6l6.3-.7Z"/></svg>`,
          shield:`<svg ${common}><path d="M12 3 4 6v6c0 5 3.5 8 8 9 4.5-1 8-4 8-9V6Z"/><path d="m9 12 2 2 4-4"/></svg>`,
          menu:`<svg ${common}><path d="M4 7h16M4 12h16M4 17h16"/></svg>`
          };
          return icons[name] || icons.box;
          };
          </script>
          <script>
          document.addEventListener('DOMContentLoaded',()=>{
          document.querySelectorAll('[data-icon]').forEach(el=>el.innerHTML=window.SAZMET_ICON(el.getAttribute('data-icon')));
          const toggle=document.querySelector('.menu-toggle'),nav=document.querySelector('.main-nav');
          toggle?.addEventListener('click',()=>{const open=nav.classList.toggle('is-open');toggle.setAttribute('aria-expanded',open?'true':'false');});
          nav?.querySelectorAll('a').forEach(a=>a.addEventListener('click',()=>{nav.classList.remove('is-open');toggle?.setAttribute('aria-expanded','false');}));

          const accountToggle=document.getElementById('account-toggle'),accountDropdown=document.getElementById('account-dropdown');
          accountToggle?.addEventListener('click',e=>{e.stopPropagation();const open=accountDropdown.classList.toggle('is-open');accountToggle.setAttribute('aria-expanded',open?'true':'false');});
          document.addEventListener('click',e=>{if(accountDropdown&&!accountDropdown.contains(e.target)&&e.target!==accountToggle){accountDropdown.classList.remove('is-open');accountToggle?.setAttribute('aria-expanded','false');}});

          const modal=document.getElementById('logout-modal'),openButton=document.getElementById('logout-open'),closeButton=document.getElementById('logout-close');
          openButton?.addEventListener('click',()=>{modal?.classList.remove('hidden');modal?.classList.add('flex');});
          closeButton?.addEventListener('click',()=>{modal?.classList.add('hidden');modal?.classList.remove('flex');});
          modal?.addEventListener('click',e=>{if(e.target===modal){modal.classList.add('hidden');modal.classList.remove('flex');}});
          });
          </script>

          {{--
          ویجت امتیاز ستاره‌ای.

          ستاره‌ها هم روی کارت‌های فهرست و هم روی صفحه‌ی خود آگهی
          استفاده می‌شوند. انتخاب یک ستاره بلافاصله با fetch ارسال
          می‌شود تا کاربر از صفحه‌ی فهرست بیرون نرود. اگر جاوااسکریپت
          در دسترس نباشد، همان فرم به‌صورت معمولی submit می‌شود
          (دکمه‌ی داخل <noscript>).
          --}}
          <script>
          document.addEventListener('DOMContentLoaded',()=>{

          const LABELS={1:'افتضاح',2:'ضعیف',3:'متوسط',4:'خوب',5:'بسیار عالی'};
          const token=document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

          // پرکردن ستاره‌ها تا مقدار انتخاب‌شده. این کار را CSS هم با
          // :has() انجام می‌دهد؛ این‌جا برای مرورگرهای قدیمی‌تر تکرار
          // می‌شود.
          const paint=(group,value)=>{
          group.querySelectorAll('.star-rate__star').forEach((label,index)=>{
          label.querySelector('.star')?.classList.toggle('is-on',index < value);
          });
          };

          document.querySelectorAll('[data-star-form]').forEach(form=>{

          const group=form.querySelector('.star-rate__stars');
          const live=form.querySelector('[data-star-live]');
          const summary=form.closest('.star-rate')?.querySelector('.star-rate__summary');

          paint(group, Number(form.querySelector('input[name="rating"]:checked')?.value || 0));

          const commentField=form.querySelector('textarea[name="comment"]');
          const commentButton=form.querySelector('[data-comment-submit]');
          const status=form.querySelector('[data-comment-status]');

          const send=async(value)=>{

          if(!value){
          if(live){live.textContent='اول یک ستاره انتخاب کنید.';live.classList.add('is-error');}
          return;
          }

          if(live){
          live.textContent='در حال ثبت…';
          live.classList.remove('is-saved','is-error');
          }

          try{
          const response=await fetch(form.action,{
          method:'POST',
          headers:{
          'X-CSRF-TOKEN':token,
          'Accept':'application/json',
          'Content-Type':'application/json',
          'X-Requested-With':'XMLHttpRequest'
          },
          body:JSON.stringify({
          rating:value,
          // فیلد نظر فقط روی صفحه‌ی خود آگهی وجود دارد
          comment:commentField?commentField.value:null
          })
          });

          const data=await response.json().catch(()=>({}));

          if(!response.ok||!data.success){
          if(live){
          live.textContent=data.message||'ثبت امتیاز انجام نشد.';
          live.classList.add('is-error');
          }
          return;
          }

          /*
          پیام ستاره و پیام نظر جدا نگه داشته می‌شوند:
          - خط ریزِ زیر ستاره‌ها فقط امتیاز را اعلام می‌کند
          - کادر درشتِ بالای فیلد نظر، وضعیت نظر را می‌گوید
          سرور هر دو را در یک رشته می‌فرستد و با «—» جدا کرده است.
          */
          const parts=String(data.message||'').split('—');

          if(live){
          live.textContent=(parts[0]||('امتیاز شما ثبت شد: '+(LABELS[value]||''))).trim();
          live.classList.add('is-saved');
          }

          if(status){
          if(parts.length>1){
          status.textContent=parts.slice(1).join('—').trim();
          status.className='comment-box__status comment-box__status--pending';
          status.hidden=false;
          }else if(!commentField||!commentField.value.trim()){
          // نظری فرستاده نشده؛ کادر وضعیت پنهان می‌ماند
          status.hidden=true;
          }
          }

          if(summary&&data.count){
          summary.innerHTML='<span class="star-rate__avg">'+Number(data.average).toFixed(1)+
          '</span><span class="star-rate__count">از '+data.count+' امتیاز</span>';
          }

          }catch(error){
          if(live){
          live.textContent='خطا در ارتباط با سرور. دوباره تلاش کنید.';
          live.classList.add('is-error');
          }
          }
          };

          const selected=()=>Number(form.querySelector('input[name="rating"]:checked')?.value||0);

          // انتخاب ستاره: بلافاصله ارسال می‌شود
          form.addEventListener('change',e=>{
          if(e.target.name!=='rating') return;
          const value=Number(e.target.value);
          paint(group,value);
          send(value);
          });

          /*
          روی بعضی مرورگرهای موبایل، تپ روی <label> همیشه به ورودیِ
          رادیویی داخلش نمی‌رسد و رویداد change شلیک نمی‌شود. این
          هندلر مستقیماً روی خود ستاره می‌نشیند و انتخاب را دستی
          انجام می‌دهد، پس تپ همیشه کار می‌کند.
          */
          group?.querySelectorAll('.star-rate__star').forEach(label=>{
          label.addEventListener('click',e=>{

          const input=label.querySelector('input[name="rating"]');
          if(!input||input.checked) return;

          // جلوگیری از اجرای دوباره وقتی مرورگر خودش هم change می‌زند
          e.preventDefault();

          input.checked=true;
          input.dispatchEvent(new Event('change',{bubbles:true}));
          });
          });

          // دکمه‌ی «ثبت نظر»: متن را با همان امتیاز انتخاب‌شده می‌فرستد
          commentButton?.addEventListener('click',e=>{
          e.preventDefault();
          send(selected());
          });

          // جلوگیری از ارسال معمولی فرم وقتی جاوااسکریپت فعال است
          form.addEventListener('submit',e=>{
          e.preventDefault();
          send(selected());
          });

          });

          });
          </script>

          {{--
          نمایش شماره تماس.

          شماره در HTML صفحه نیست؛ با کلیک از سرور گرفته می‌شود. همین
          یک درخواست هم شمارش «سرنخ» را برای پنل ارائه‌دهنده ثبت می‌کند.
          هر دو دکمه‌ی صفحه (داخل کادر تماس و دکمه‌ی بزرگ کناری) به یک
          کادر وصل‌اند و با هم به‌روز می‌شوند.
          --}}
          <script>
          document.addEventListener('DOMContentLoaded',()=>{

          const box=document.querySelector('[data-contact-box]');
          if(!box) return;

          const token=document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
          const value=box.querySelector('[data-contact-value]');
          const buttons=document.querySelectorAll('[data-contact-reveal]');
          let loading=false, revealed=false;

          const reveal=async()=>{

          if(loading) return;

          // بار دوم به بعد: فقط شماره‌گیری، بدون درخواست دوباره
          if(revealed){ window.location.href=value.getAttribute('href'); return; }

          loading=true;
          buttons.forEach(b=>{b.disabled=true;b.dataset.prev=b.textContent;b.textContent='لطفاً صبر کنید…';});

          try{
          const response=await fetch(box.dataset.url,{
          method:'POST',
          headers:{'X-CSRF-TOKEN':token,'Accept':'application/json','X-Requested-With':'XMLHttpRequest'},
          credentials:'same-origin'
          });

          const data=await response.json().catch(()=>({}));

          /*
           * مهمان: سرور مقصد را در سشن گذاشته، پس بعد از ثبت‌نام
           * کاربر به همین آگهی برمی‌گردد. دکمه به حالت اولش
           * برنمی‌گردد چون صفحه در حال ترک شدن است و پرش متن بد
           * دیده می‌شود.
           */
          if(data.requires_auth&&data.url){
          buttons.forEach(b=>{b.textContent=data.message||'برای دیدن شماره وارد شوید';});
          window.location.href=data.url;
          return;
          }

          if(!response.ok||!data.success){
          buttons.forEach(b=>{b.disabled=false;b.textContent=data.message||'دوباره تلاش کنید';});
          loading=false;
          return;
          }

          revealed=true;

          value.textContent=data.phone;
          value.setAttribute('href',data.tel);
          value.hidden=false;

          buttons.forEach(b=>{
          b.disabled=false;
          // دکمه‌ی کوچکِ داخل کادر دیگر لازم نیست؛ خودِ شماره جایش را می‌گیرد.
          if(b.classList.contains('contact-reveal')) b.remove();
          else b.textContent='تماس: '+data.phone;
          });

          }catch(error){
          buttons.forEach(b=>{b.disabled=false;b.textContent=b.dataset.prev||'نمایش شماره';});
          }finally{
          loading=false;
          }
          };

          buttons.forEach(b=>b.addEventListener('click',reveal));
          });
          </script>

          {{--
          دروازه‌ی دکمه‌ی اصلی.

          تا وقتی فیلدهای لازمِ یک فرم پر نشده‌اند، دکمه‌ی اقدام کم‌رنگ
          می‌ماند و وقتی همه‌چیز درست شد، پُررنگ می‌شود. کاربر پیش از
          کلیک می‌فهمد هنوز چیزی مانده، به‌جای اینکه بزند و خطا بگیرد.

          ⚠️ دکمه عمداً disabled نمی‌شود.

          دکمه‌ی disabled کلیک را می‌خورد و هیچ نمی‌گوید - کاربر
          می‌ماند که «چرا کار نمی‌کند؟». اینجا دکمه کلیک‌پذیر می‌ماند،
          پس اعتبارسنجی خود مرورگر اجرا می‌شود، روی اولین فیلد ناقص
          می‌پرد و می‌گوید چه چیزی کم است. کم‌رنگی خبر می‌دهد،
          جلوگیری نمی‌کند.

          فیلدهای پنهان (مرحله‌ی دیگر فرم، یا بخش مخصوص محصول در
          فرمی که خدمت انتخاب شده) شمرده نمی‌شوند - وگرنه دکمه هرگز
          پُررنگ نمی‌شد.
          --}}
          <script>
          document.addEventListener('DOMContentLoaded',()=>{

          const syncers=[];

          document.querySelectorAll('form[data-gate]').forEach(form=>{

          const buttons=[...form.querySelectorAll('[data-gate-submit]')];
          if(!buttons.length) return;

          const isLive=el=>!el.disabled&&!el.closest('[hidden]')&&el.offsetParent!==null;

          /*
           * فیلدی که باید با فیلد دیگری یکی باشد - عملاً «تکرار رمز
           * عبور». رایج‌ترین خطای فرم ثبت‌نام همین است و تا وقتی فقط
           * سرور آن را می‌گرفت، کاربر کل فرم را می‌فرستاد تا بفهمد.
           * setCustomValidity باعث می‌شود هم دروازه آن را ببیند و هم
           * خود مرورگر پیام بدهد.
           */
          form.querySelectorAll('[data-match]').forEach(field=>{
          const other=form.querySelector(field.dataset.match);
          if(!other) return;
          const compare=()=>field.setCustomValidity(field.value===other.value?'':'با رمز عبور بالا یکی نیست.');
          field.addEventListener('input',compare);
          other.addEventListener('input',compare);
          compare();
          });

          /*
           * validity.valid خوانده می‌شود نه checkValidity().
           *
           * checkValidity() رویداد invalid را شلیک می‌کند، و بعضی
           * فیلدهای سایت روی همان رویداد setCustomValidity دارند.
           * یعنی یک بررسیِ صرفاً خواندنی، وضعیت فیلد را عوض می‌کرد.
           * validity.valid همان جواب را بدون هیچ عارضه‌ای می‌دهد.
           */
          const ready=()=>[...form.querySelectorAll('[required]')]
          .filter(isLive)
          .every(f=>f.validity.valid);

          /*
           * فقط کلاس - نه aria-disabled.
           *
           * اول aria-disabled="true" گذاشته بودم، ولی آن یک دروغ بود:
           * دکمه واقعاً غیرفعال نیست و کلیک‌کردنش کار مفیدی می‌کند
           * (اعتبارسنجی مرورگر می‌گوید چه چیزی کم است). گفتنِ
           * «غیرفعال» به صفحه‌خوان یعنی کاربرِ نابینا اصلاً امتحان
           * نمی‌کند و آن راهنمایی را از دست می‌دهد - در حالی که
           * کاربر بینا فقط یک دکمه‌ی کم‌رنگ می‌بیند و می‌زندش.
           *
           * ابزارهای خودکار هم همین را می‌فهمند: Playwright از کلیک
           * روی aria-disabled امتناع کرد، که دقیقاً نشان داد این صفت
           * چه چیزی را به بقیه اعلام می‌کند.
           */
          const sync=()=>{
          const ok=ready();
          buttons.forEach(b=>b.classList.toggle('is-locked',!ok));
          };

          form.addEventListener('input',sync);
          form.addEventListener('change',sync);
          form.addEventListener('gate:refresh',sync);

          syncers.push(sync);
          sync();
          });

          if(!syncers.length) return;

          /*
           * بعضی چیزها مجموعه‌ی «فیلدهای زنده» را عوض می‌کنند بدون
           * اینکه هیچ فیلدی تغییر کند:
           *
           *   - دکمه‌ی «ادامه» در فرم چندمرحله‌ای، که مرحله‌ی بعد را
           *     نمایان می‌کند
           *   - تب‌های «ورود با ایمیل / با شماره»، که یک فرم را پنهان
           *     و فرم دیگر را آشکار می‌کنند - و این تب‌ها بیرون از
           *     خود فرم‌اند، پس شنونده‌ی روی فرم آنها را نمی‌بیند
           *
           * پس گوش دادن در سطح سند لازم است. setTimeout می‌گذارد
           * اسکریپتِ آن بخش اول کارش را تمام کند.
           */
          document.addEventListener('click',()=>{
          setTimeout(()=>syncers.forEach(s=>s()),0);
          });

          });
          </script>
          </body>
          </html>