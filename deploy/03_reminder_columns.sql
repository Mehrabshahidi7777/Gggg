-- =============================================================================
--  سازمت | ستون‌های ردگیری یادآوری تمدید
-- =============================================================================
--
--  کرون هر روز اجرا می‌شود. بدون این ستون‌ها، اشتراکی که ۷ روز تا
--  انقضایش مانده هر روز یک پیامک می‌گرفت. هر ستون زمان ارسال همان
--  مرحله را نگه می‌دارد تا دقیقاً یک بار ارسال شود.
--
--  معادل migration:
--    2026_09_20_000002_add_reminder_columns_to_service_subscriptions
--
--  در phpMyAdmin روی دیتابیس sazmat_sa ایمپورت کنید.
--  اجرای دوباره‌ی این فایل بی‌خطر است.
-- =============================================================================

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------------
-- ستون‌ها
-- ---------------------------------------------------------------------------
--
-- MySQL 8 دستور «ADD COLUMN IF NOT EXISTS» ندارد (آن مالِ MariaDB است).
--
-- به‌جای رویه‌ی ذخیره‌شده، از PREPARE/EXECUTE استفاده می‌کنیم. دلیلش
-- این است که رویه‌ی ذخیره‌شده به دستور DELIMITER نیاز دارد و DELIMITER
-- یک دستور کلاینت است نه سرور؛ ایمپورت phpMyAdmin گاهی روی آن
-- می‌شکند. روش زیر فقط SQL استاندارد است و همه‌جا کار می‌کند.

SET @has_column := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'service_subscriptions'
      AND COLUMN_NAME = 'reminder_7d_sent_at'
);

SET @sql := IF(@has_column = 0,
    'ALTER TABLE `service_subscriptions`
        ADD COLUMN `reminder_7d_sent_at`      TIMESTAMP NULL DEFAULT NULL AFTER `status`,
        ADD COLUMN `reminder_1d_sent_at`      TIMESTAMP NULL DEFAULT NULL AFTER `reminder_7d_sent_at`,
        ADD COLUMN `reminder_expired_sent_at` TIMESTAMP NULL DEFAULT NULL AFTER `reminder_1d_sent_at`',
    'DO 0'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;


SET @has_index := (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'service_subscriptions'
      AND INDEX_NAME = 'service_subscriptions_status_ends_at_index'
);

SET @sql := IF(@has_index = 0,
    'ALTER TABLE `service_subscriptions`
        ADD INDEX `service_subscriptions_status_ends_at_index` (`status`, `ends_at`)',
    'DO 0'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;


-- ---------------------------------------------------------------------------
-- مهم: اشتراک‌های قدیمی را «قبلاً یادآوری‌شده» علامت می‌زنیم
-- ---------------------------------------------------------------------------
--
-- بدون این بخش، اولین اجرای دستور یادآوری برای تمام اشتراک‌های
-- منقضی‌شده‌ی گذشته یکباره پیامک می‌فرستد - هم هزینه‌ی زیاد، هم
-- پیامک بی‌ربط برای کاربری که ماه‌ها پیش اشتراکش تمام شده.
--
-- فقط اشتراک‌هایی که پایانشان قبل از «الان» است علامت می‌خورند؛
-- اشتراک‌های فعال دست‌نخورده می‌مانند و یادآوری‌شان طبق برنامه
-- ارسال می‌شود.
--
UPDATE `service_subscriptions`
SET `reminder_7d_sent_at`      = COALESCE(`reminder_7d_sent_at`, NOW()),
    `reminder_1d_sent_at`      = COALESCE(`reminder_1d_sent_at`, NOW()),
    `reminder_expired_sent_at` = COALESCE(`reminder_expired_sent_at`, NOW())
WHERE `ends_at` IS NOT NULL
  AND `ends_at` <= NOW();


-- ---------------------------------------------------------------------------
-- ثبت migration
-- ---------------------------------------------------------------------------
INSERT INTO `migrations` (`migration`, `batch`)
SELECT
  '2026_09_20_000002_add_reminder_columns_to_service_subscriptions',
  COALESCE((SELECT MAX(b.batch) FROM (SELECT batch FROM `migrations`) AS b), 0) + 1
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM (SELECT migration FROM `migrations`) AS m
  WHERE m.migration = '2026_09_20_000002_add_reminder_columns_to_service_subscriptions'
);
