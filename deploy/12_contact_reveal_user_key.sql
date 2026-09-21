-- =============================================================================
--  سازمت | کلید یکتای «نمایش شماره»: از IP به حساب کاربری
-- =============================================================================
--
--  مسئله چه بود
--  ------------
--  تا امروز مهمان‌ها هم می‌توانستند شماره ببینند، پس تنها چیزی که برای
--  جلوگیری از شمارش تکراری در دست بود هشِ IP بود:
--
--      unique (ad_id, ip_hash, revealed_on)
--
--  حالا دیدن شماره نیاز به حساب دارد، و آن کلید دو جای واقعی اشتباه
--  می‌کند:
--
--  ۱. دو نفر پشت یک IP (خانه، دفتر، اینترنت موبایل) اگر در یک روز یک
--     آگهی را ببینند، فقط نفر اول ثبت می‌شود. نفر دوم در پنل خودش
--     چیزی نمی‌بیند - در حالی که واقعاً شماره را دیده.
--
--  ۲. یک نفر که وسط روز از وای‌فای به دیتای موبایل می‌رود، دو ردیف
--     می‌سازد و در پنلش تکراری می‌بیند.
--
--  کلید درست حالا «این کاربر، این آگهی، امروز» است.
--
--  ip_hash حذف نمی‌شود؛ همچنان ثبت می‌شود و برای بررسی سوءاستفاده به
--  درد می‌خورد، فقط دیگر کلید یکتا نیست.
--
--  ⚠️ ترتیب کار
--  ------------
--  این فایل را *قبل از* آپلود کد جدید ایمپورت کنید.
--
--  ⚠️ این فایل ردیف حذف می‌کند
--  ---------------------------
--  بخش ۲ ردیف‌های تکراری را پاک می‌کند: جایی که یک کاربر در یک روز
--  برای یک آگهی بیش از یک ردیف دارد (همان حالت وای‌فای/دیتا). از هر
--  گروه، قدیمی‌ترین ردیف می‌ماند.
--
--  بدون این پاک‌سازی، ساختنِ کلید یکتا در بخش ۳ با خطا متوقف می‌شود.
--
--  ردیف‌های مهمان (user_id تهی) دست‌نخورده می‌مانند؛ MySQL چند NULL را
--  در کلید یکتا می‌پذیرد.
--
--  بخش ۱ پیش از هر تغییری می‌گوید چند ردیف قرار است پاک شود. اگر آن
--  عدد صفر بود - که روی سایتی با آمار کم محتمل است - هیچ ردیفی حذف
--  نمی‌شود.
--
--  در phpMyAdmin روی دیتابیس sazmat_sa ایمپورت کنید.
--  اجرای دوباره‌ی این فایل بی‌خطر است.
-- =============================================================================

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------------
-- ۱) پیش از تغییر: چند ردیف تکراری هست؟
-- ---------------------------------------------------------------------------
--
--  اگر خروجی خالی بود، یعنی چیزی برای پاک کردن نیست.

SELECT
    ad_id,
    user_id,
    revealed_on,
    COUNT(*) AS duplicate_rows
FROM ad_contact_reveals
WHERE user_id IS NOT NULL
GROUP BY ad_id, user_id, revealed_on
HAVING COUNT(*) > 1;

-- ---------------------------------------------------------------------------
-- ۲) پاک‌سازی تکراری‌ها، با نگه‌داشتن قدیمی‌ترین ردیف هر گروه
-- ---------------------------------------------------------------------------
--
--  جدول مشتق (d) اول ساخته و مادی می‌شود، بعد DELETE اجرا می‌شود؛
--  برای همین ارجاع به همان جدول اینجا مجاز است.

DELETE r
FROM ad_contact_reveals AS r
JOIN (
    SELECT ad_id, user_id, revealed_on, MIN(id) AS keep_id
    FROM ad_contact_reveals
    WHERE user_id IS NOT NULL
    GROUP BY ad_id, user_id, revealed_on
    HAVING COUNT(*) > 1
) AS d
  ON  d.ad_id       = r.ad_id
  AND d.user_id     = r.user_id
  AND d.revealed_on = r.revealed_on
WHERE r.id > d.keep_id;

-- ---------------------------------------------------------------------------
-- ۳) برداشتن کلید قدیمی (اگر باشد)
-- ---------------------------------------------------------------------------
--
--  MySQL دستور «DROP INDEX IF EXISTS» ندارد، پس اول شمرده می‌شود و
--  بعد دستور به‌صورت رشته ساخته و اجرا می‌شود. اگر نباشد، دستور
--  تبدیل به یک SELECT بی‌ضرر می‌شود.

SET @old_exists := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'ad_contact_reveals'
      AND INDEX_NAME   = 'ad_contact_reveals_daily_unique'
);

SET @sql := IF(
    @old_exists > 0,
    'ALTER TABLE `ad_contact_reveals` DROP INDEX `ad_contact_reveals_daily_unique`',
    'SELECT ''کلید قدیمی از قبل برداشته شده؛ کاری انجام نشد.'' AS message'
);

PREPARE statement FROM @sql;
EXECUTE statement;
DEALLOCATE PREPARE statement;

-- ---------------------------------------------------------------------------
-- ۴) ساختن کلید تازه: این کاربر، این آگهی، این روز
-- ---------------------------------------------------------------------------

SET @new_exists := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'ad_contact_reveals'
      AND INDEX_NAME   = 'ad_contact_reveals_user_daily_unique'
);

SET @sql := IF(
    @new_exists = 0,
    'ALTER TABLE `ad_contact_reveals`
       ADD UNIQUE `ad_contact_reveals_user_daily_unique` (`ad_id`, `user_id`, `revealed_on`)',
    'SELECT ''کلید تازه از قبل وجود دارد؛ کاری انجام نشد.'' AS message'
);

PREPARE statement FROM @sql;
EXECUTE statement;
DEALLOCATE PREPARE statement;

-- ---------------------------------------------------------------------------
-- ۵) ایندکس معمولی روی ip_hash
-- ---------------------------------------------------------------------------
--
--  دیگر کلید یکتا نیست، ولی برای بررسی سوءاستفاده هنوز لازم است
--  بشود رویش جست‌وجو کرد.

SET @ip_exists := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'ad_contact_reveals'
      AND INDEX_NAME   = 'ad_contact_reveals_ip_hash_index'
);

SET @sql := IF(
    @ip_exists = 0,
    'ALTER TABLE `ad_contact_reveals` ADD INDEX `ad_contact_reveals_ip_hash_index` (`ip_hash`)',
    'SELECT ''ایندکس ip_hash از قبل وجود دارد؛ کاری انجام نشد.'' AS message'
);

PREPARE statement FROM @sql;
EXECUTE statement;
DEALLOCATE PREPARE statement;

-- ---------------------------------------------------------------------------
-- ۶) ثبت مهاجرت، تا لاراول دوباره اجرایش نکند
-- ---------------------------------------------------------------------------

INSERT INTO migrations (migration, batch)
SELECT
    '2026_09_21_000001_key_contact_reveals_by_user',
    COALESCE((SELECT MAX(batch) FROM (SELECT batch FROM migrations) AS m), 0) + 1
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM (SELECT migration FROM migrations) AS existing
    WHERE existing.migration = '2026_09_21_000001_key_contact_reveals_by_user'
);

-- ---------------------------------------------------------------------------
-- ۷) تأیید
-- ---------------------------------------------------------------------------
--
--  باید سه ایندکس دیده شود:
--    ad_contact_reveals_user_daily_unique  (یکتا، سه ستون)
--    ad_contact_reveals_ip_hash_index      (معمولی)
--    ad_contact_reveals_ad_id_created_at_index
--
--  و ad_contact_reveals_daily_unique نباید دیگر باشد.

SELECT
    INDEX_NAME,
    GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) AS columns_in_index,
    IF(NON_UNIQUE = 0, 'یکتا', 'معمولی')            AS kind
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME   = 'ad_contact_reveals'
GROUP BY INDEX_NAME, NON_UNIQUE
ORDER BY INDEX_NAME;
