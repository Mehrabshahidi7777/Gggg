{{--
|-------------------------------------------------------------------------
| حباب گفت‌وگو
|-------------------------------------------------------------------------
|
| تا امروز تنها راه پیام دادن، رفتن به صفحه‌ی «تماس با ما» بود - یعنی
| کاربر باید کاری که می‌کرد را رها می‌کرد. این دکمه در هر صفحه‌ای
| همراهش است و همان فرم را همان‌جا باز می‌کند.
|
| در صفحه‌ی «تماس با ما» نمایش داده نمی‌شود؛ آنجا خودِ فرم روی صفحه
| است و دکمه فقط تکرار می‌شد.
|
| بعداً اگر هوش مصنوعی آمد، جایش همین‌جاست: پنل عوض می‌شود، دکمه و
| رفتارش نه.
--}}

@unless(request()->routeIs('contact'))

<div class="chat-bubble" data-chat>

    <button type="button"
            class="chat-bubble__fab"
            data-chat-toggle
            aria-expanded="false"
            aria-controls="chat-bubble-panel"
            aria-label="ارسال پیام به سازمت">
        <span data-icon="mail"></span>
    </button>

    <div class="chat-bubble__panel" id="chat-bubble-panel" role="dialog"
         aria-label="ارسال پیام به سازمت" hidden>

        <div class="chat-bubble__head">
            <div>
                <strong>پیام به سازمت</strong>
                <span>معمولاً در چند ساعت کاری پاسخ می‌دهیم.</span>
            </div>

            <button type="button" class="chat-bubble__close" data-chat-close aria-label="بستن">×</button>
        </div>

        <form class="chat-bubble__form" method="POST" action="{{ route('contact.store') }}"
              data-chat-form data-gate>
            @csrf

            {{-- همان تله‌ی فرم تماس. توضیح کامل در front/contact.blade.php --}}
            <div class="hp-field" aria-hidden="true">
                <label for="chat-website">وب‌سایت</label>
                <input id="chat-website" type="text" name="website" value="" tabindex="-1" autocomplete="off">
            </div>
            <input type="hidden" name="opened_at" value="{{ encrypt(time()) }}">

            <label class="chat-bubble__field">
                <span>نام شما</span>
                <input type="text" name="name" required maxlength="255" autocomplete="name">
            </label>

            <label class="chat-bubble__field">
                <span>ایمیل</span>
                <input type="email" name="email" required autocomplete="email"
                       placeholder="برای اینکه بتوانیم پاسخ بدهیم">
            </label>

            <label class="chat-bubble__field">
                <span>تلفن <i>(اختیاری)</i></span>
                <input type="text" name="phone" maxlength="15" inputmode="numeric"
                       dir="ltr" data-digits-only autocomplete="tel">
            </label>

            <label class="chat-bubble__field">
                <span>پیام</span>
                <textarea name="message" rows="4" required maxlength="5000"
                          placeholder="چه کمکی از ما برمی‌آید؟"></textarea>
            </label>

            {{-- خطاهای سرور اینجا می‌نشینند --}}
            <p class="chat-bubble__error" data-chat-error hidden></p>

            <button type="submit" class="btn btn-primary btn-block" data-gate-submit>
                ارسال پیام
            </button>
        </form>

        {{-- بعد از ارسال، جای فرم را می‌گیرد --}}
        <div class="chat-bubble__done" data-chat-done hidden>
            <strong>پیامت رسید ✅</strong>
            <p>ممنون. به‌زودی جواب می‌دهیم.</p>
            <button type="button" class="btn btn-ghost btn-sm" data-chat-close>بستن</button>
        </div>

    </div>
</div>

@endunless
