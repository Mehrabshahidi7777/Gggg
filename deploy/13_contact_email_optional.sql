-- =============================================================================
--  سازمت | ایمیل در فرم تماس دیگر اجباری نیست
-- =============================================================================
--
--  مسئله چه بود
--  ------------
--  فرم تماس ایمیل را اجباری می‌خواست. این سایت بازار مصالح ساختمانی
--  است؛ خیلی از مخاطبانش ایمیل ندارند یا نمی‌خواهند بدهند، ولی همه
--  شماره دارند - و خودِ سایت هم با تماس تلفنی کار می‌کند، نه با
--  ایمیل. پس آن اجبار فقط اصطکاک بود.
--
--  حالا تلفن اجباری است و ایمیل اختیاری.
--
--  این فایل چه می‌کند
--  ------------------
--  ستون `contact_messages.email` را nullable می‌کند، چون از این به بعد
--  پیام‌هایی بدون ایمیل ذخیره می‌شوند.
--
--  ⚠️ ترتیب کار
--  ------------
--  این فایل را *قبل از* آپلود کد جدید ایمپورت کنید. کد جدید ممکن است
--  ایمیل تهی بفرستد و با ستون NOT NULL خطا می‌گیرد.
--
--  هیچ ردیفی حذف یا تغییر داده نمی‌شود؛ فقط تعریف ستون عوض می‌شود.
--  پیام‌های قبلی و ایمیل‌هایشان دست‌نخورده می‌مانند.
--
--  در phpMyAdmin روی دیتابیس sazmat_sa ایمپورت کنید.
--  اجرای دوباره‌ی این فایل بی‌خطر است.
-- =============================================================================

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------------
-- ۱) وضعیت فعلی
-- ---------------------------------------------------------------------------
--
--  IS_NULLABLE باید الان NO باشد.

SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME   = 'contact_messages'
  AND COLUMN_NAME  = 'email';

-- ---------------------------------------------------------------------------
-- ۲) nullable کردن ستون، فقط اگر لازم باشد
-- ---------------------------------------------------------------------------
--
--  MODIFY در MySQL دستور «IF NOT ALREADY» ندارد، پس اول وضعیت خوانده
--  می‌شود و بعد دستور به‌صورت رشته ساخته و اجرا می‌شود. اگر از قبل
--  nullable باشد، دستور تبدیل به یک SELECT بی‌ضرر می‌شود.

SET @already_nullable := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'contact_messages'
      AND COLUMN_NAME  = 'email'
      AND IS_NULLABLE  = 'YES'
);

SET @sql := IF(
    @already_nullable = 0,
    'ALTER TABLE `contact_messages` MODIFY `email` VARCHAR(255) NULL',
    'SELECT ''ستون email از قبل اختیاری است؛ کاری انجام نشد.'' AS message'
);

PREPARE statement FROM @sql;
EXECUTE statement;
DEALLOCATE PREPARE statement;

-- ---------------------------------------------------------------------------
-- ۳) ثبت مهاجرت، تا لاراول دوباره اجرایش نکند
-- ---------------------------------------------------------------------------

INSERT INTO migrations (migration, batch)
SELECT
    '2026_09_21_000002_make_contact_email_optional',
    COALESCE((SELECT MAX(batch) FROM (SELECT batch FROM migrations) AS m), 0) + 1
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM (SELECT migration FROM migrations) AS existing
    WHERE existing.migration = '2026_09_21_000002_make_contact_email_optional'
);

-- ---------------------------------------------------------------------------
-- ۴) تأیید
-- ---------------------------------------------------------------------------
--
--  حالا باید IS_NULLABLE برابر YES باشد.

SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME   = 'contact_messages'
  AND COLUMN_NAME  = 'email';

-- ---------------------------------------------------------------------------
-- ۵) پیام‌های موجود دست‌نخورده‌اند
-- ---------------------------------------------------------------------------

SELECT
    COUNT(*)                                        AS total_messages,
    SUM(email IS NULL OR email = '')                AS without_email,
    SUM(phone IS NULL OR phone = '')                AS without_phone
FROM contact_messages;
