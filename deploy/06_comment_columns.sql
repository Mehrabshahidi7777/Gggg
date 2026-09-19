-- =============================================================================
--  سازمت | ستون‌های نظر متنی روی جدول امتیازها
-- =============================================================================
--
--  امتیاز و نظر در یک ردیف نگه داشته می‌شوند: هر کاربر برای هر آگهی
--  یک امتیاز و یک نظر دارد، و قید یکتای موجود روی (ad_id, user_id)
--  همان قانون را برای نظر هم اعمال می‌کند.
--
--  اما چرخه‌ی تأییدشان فرق دارد:
--    امتیاز ستاره → بلافاصله در میانگین اعمال می‌شود
--    نظر متنی     → تا تأیید مدیر روی سایت دیده نمی‌شود
--
--  comment_status وقتی NULL است که کاربر فقط ستاره داده و متنی
--  ننوشته، تا صف تأیید مدیر با رکوردهای بی‌متن شلوغ نشود.
--
--  معادل migration:
--    2026_09_20_000005_add_comment_to_ad_ratings
--
--  پیش‌نیاز: فایل 01_ad_ratings.sql قبلاً ایمپورت شده باشد.
--  اجرای دوباره‌ی این فایل بی‌خطر است.
-- =============================================================================

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------------
-- ستون‌ها
-- ---------------------------------------------------------------------------
--
-- MySQL 8 دستور «ADD COLUMN IF NOT EXISTS» ندارد، و رویه‌ی ذخیره‌شده
-- به DELIMITER نیاز دارد که ایمپورت phpMyAdmin گاهی رویش می‌شکند.
-- پس از PREPARE/EXECUTE استفاده می‌کنیم که فقط SQL استاندارد است.

SET @has_column := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'ad_ratings'
      AND COLUMN_NAME = 'comment'
);

SET @sql := IF(@has_column = 0,
    'ALTER TABLE `ad_ratings`
        ADD COLUMN `comment`                  TEXT COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `rating`,
        ADD COLUMN `comment_status`           ENUM(''pending'',''approved'',''rejected'') COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `comment`,
        ADD COLUMN `comment_rejection_reason` TEXT COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `comment_status`,
        ADD COLUMN `comment_reviewed_at`      TIMESTAMP NULL DEFAULT NULL AFTER `comment_rejection_reason`,
        ADD COLUMN `comment_reviewed_by`      BIGINT UNSIGNED DEFAULT NULL AFTER `comment_reviewed_at`',
    'DO 0'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;


-- ---------------------------------------------------------------------------
-- ایندکس صف تأیید
-- ---------------------------------------------------------------------------
SET @has_index := (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'ad_ratings'
      AND INDEX_NAME = 'ad_ratings_comment_status_created_at_index'
);

SET @sql := IF(@has_index = 0,
    'ALTER TABLE `ad_ratings`
        ADD INDEX `ad_ratings_comment_status_created_at_index` (`comment_status`, `created_at`)',
    'DO 0'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;


-- ---------------------------------------------------------------------------
-- کلید خارجیِ بازبین
-- ---------------------------------------------------------------------------
SET @has_fk := (
    SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'ad_ratings'
      AND CONSTRAINT_NAME = 'ad_ratings_comment_reviewed_by_foreign'
);

SET @sql := IF(@has_fk = 0,
    'ALTER TABLE `ad_ratings`
        ADD CONSTRAINT `ad_ratings_comment_reviewed_by_foreign`
        FOREIGN KEY (`comment_reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL',
    'DO 0'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;


-- ---------------------------------------------------------------------------
-- ثبت migration
-- ---------------------------------------------------------------------------
INSERT INTO `migrations` (`migration`, `batch`)
SELECT
  '2026_09_20_000005_add_comment_to_ad_ratings',
  COALESCE((SELECT MAX(b.batch) FROM (SELECT batch FROM `migrations`) AS b), 0) + 1
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM (SELECT migration FROM `migrations`) AS m
  WHERE m.migration = '2026_09_20_000005_add_comment_to_ad_ratings'
);
