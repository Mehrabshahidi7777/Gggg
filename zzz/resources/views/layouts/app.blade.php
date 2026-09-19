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
          <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css">

          <style>
          .cart-badge {
          min-width: 20px;
          height: 20px;
          padding: 0 5px;
          display: inline-flex;
          align-items: center;
          justify-content: center;
          background: var(--color-orange, #D9642B);
          color: #fff;
          border-radius: 999px;
          font-size: 10px;
          font-weight: 900;
          line-height: 1;
          border: 2px solid #fff;
          box-shadow: 0 2px 6px rgba(217,100,43,.3);
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
          background:var(--color-orange,#D9642B); color:#fff; font-size:10px; font-weight:900;
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

          <a href="{{ route('orders.index') }}"><span data-icon="box"></span><span>پنل مشتری</span></a>

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
          <a href="{{ route('login') }}" class="btn btn-ghost btn-sm">ورود</a>
          <a href="{{ route('register') }}" class="btn btn-primary btn-sm">ثبت‌نام</a>
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
          <form method="POST" action="{{ route('logout') }}" class="flex-1">@csrf<button class="btn btn-primary btn-block" type="submit">بله، خروج</button></form>
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

          form.addEventListener('change',async e=>{

          if(e.target.name!=='rating') return;

          const value=Number(e.target.value);
          paint(group,value);

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
          body:JSON.stringify({rating:value})
          });

          const data=await response.json().catch(()=>({}));

          if(!response.ok||!data.success){
          if(live){
          live.textContent=data.message||'ثبت امتیاز انجام نشد.';
          live.classList.add('is-error');
          }
          return;
          }

          if(live){
          live.textContent='امتیاز شما ثبت شد: '+(LABELS[value]||'');
          live.classList.add('is-saved');
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
          </body>
          </html>