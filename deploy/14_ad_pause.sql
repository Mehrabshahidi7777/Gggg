-- =============================================================================
--  سازمت | خاموش‌کردن موقتِ آگهی به دست خودِ ارائه‌دهنده
-- =============================================================================
--
--  مسئله چه بود
--  ------------
--  تنها راهِ پنهان‌کردن یک آگهی، حذفش بود. کسی که جنسش تمام شده یا چند
--  هفته سرش شلوغ است، مجبور بود آگهی را پاک کند و بعد از نو بسازد -
--  یعنی عکس‌ها، امتیازها و نظرهایش را برای همیشه از دست بدهد.
--
--  این فایل چه می‌کند
--  ------------------
--  ستون `paused_at` را به جدول `ads` اضافه می‌کند، به‌علاوه‌ی ایندکسش.
--
--  ⚠️ چرا ستون تازه، و نه همان is_suspended؟
--  ------------------------------------------
--  چون دو مفهوم متفاوت‌اند و صاحب متفاوتی دارند:
--
--      is_suspended : سیستم خاموش کرده (اشتراک تمام شده)
--      paused_at    : خودِ صاحب آگهی خاموش کرده
--
--  اگر روی یک ستون می‌نشستند، اولین اجرای کرون تصمیم کاربر را پاک
--  می‌کرد - یا بدتر، آگهیِ تعلیق‌شده به‌خاطر نپرداختن را «فعال»
--  می‌کرد.
--
--  ⚠️ ترتیب کار
--  ------------
--  این فایل را *قبل از* آپلود کد جدید ایمپورت کنید. کد جدید انتظار
--  دارد ستون `paused_at` وجود داشته باشد و بدون آن هر صفحه‌ی سایت خطا
--  می‌دهد (این ستون در scopeApproved استفاده می‌شود، که همه‌جا هست).
--
--  همه‌ی آگهی‌های موجود paused_at تهی می‌گیرند، یعنی روشن می‌مانند.
--  هیچ آگهی‌ای با این ایمپورت از سایت خارج نمی‌شود.
--
--  در phpMyAdmin روی دیتابیس sazmat_sa ایمپورت کنید.
--  اجرای دوباره‌ی این فایل بی‌خطر است.
-- =============================================================================

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------------
-- ۱) وضعیت فعلی
-- ---------------------------------------------------------------------------

SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME   = 'ads'
  AND COLUMN_NAME  = 'paused_at';

-- ---------------------------------------------------------------------------
-- ۲) افزودن ستون، فقط اگر وجود نداشته باشد
-- ---------------------------------------------------------------------------

SET @column_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'ads'
      AND COLUMN_NAME  = 'paused_at'
);

SET @sql := IF(
    @column_exists = 0,
    'ALTER TABLE `ads` ADD COLUMN `paused_at` TIMESTAMP NULL DEFAULT NULL AFTER `suspended_at`',
    'SELECT ''ستون paused_at از قبل وجود دارد؛ کاری انجام نشد.'' AS message'
);

PREPARE statement FROM @sql;
EXECUTE statement;
DEALLOCATE PREPARE statement;

-- ---------------------------------------------------------------------------
-- ۳) ایندکس
-- ---------------------------------------------------------------------------
--
--  این ستون در scopeApproved است، یعنی در کوئریِ هر بازدید از سایت.

SET @index_exists := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'ads'
      AND INDEX_NAME   = 'ads_paused_at_index'
);

SET @sql := IF(
    @index_exists = 0,
    'ALTER TABLE `ads` ADD INDEX `ads_paused_at_index` (`paused_at`)',
    'SELECT ''ایندکس paused_at از قبل وجود دارد؛ کاری انجام نشد.'' AS message'
);

PREPARE statement FROM @sql;
EXECUTE statement;
DEALLOCATE PREPARE statement;

-- ---------------------------------------------------------------------------
-- ۴) ثبت مهاجرت، تا لاراول دوباره اجرایش نکند
-- ---------------------------------------------------------------------------

INSERT INTO migrations (migration, batch)
SELECT
    '2026_09_21_000003_add_paused_at_to_ads',
    COALESCE((SELECT MAX(batch) FROM (SELECT batch FROM migrations) AS m), 0) + 1
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM (SELECT migration FROM migrations) AS existing
    WHERE existing.migration = '2026_09_21_000003_add_paused_at_to_ads'
);

-- ---------------------------------------------------------------------------
-- ۵) تأیید
-- ---------------------------------------------------------------------------
--
--  باید یک ردیف با نوع timestamp و IS_NULLABLE = YES برگردد.

SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME   = 'ads'
  AND COLUMN_NAME  = 'paused_at';

-- ---------------------------------------------------------------------------
-- ۶) هیچ آگهی‌ای خاموش نشده
-- ---------------------------------------------------------------------------
--
--  paused باید صفر باشد.

SELECT
    COUNT(*)                        AS total_ads,
    SUM(paused_at IS NOT NULL)      AS paused,
    SUM(is_suspended = 1)           AS suspended_by_system
FROM ads;
