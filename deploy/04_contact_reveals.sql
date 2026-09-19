-- =============================================================================
--  سازمت | جدول ثبت «نمایش شماره تماس»
-- =============================================================================
--
--  دو کار را هم‌زمان انجام می‌دهد:
--
--    ۱. ضد اسکرپ — شماره دیگر داخل HTML صفحه نیست و فقط با یک
--       درخواست جداگانه و throttle‌شده برگردانده می‌شود.
--
--    ۲. سنجش ارزش اشتراک — پنل ارائه‌دهنده می‌گوید «این ماه ۴۷ نفر
--       شماره‌ی شما را دیدند»، یعنی سرنخ واقعی، نه صرفاً بازدید صفحه.
--
--  برای حریم خصوصی، IP خام ذخیره نمی‌شود؛ فقط هش HMAC آن با APP_KEY.
--
--  معادل migration:
--    2026_09_20_000003_create_ad_contact_reveals_table
--
--  در phpMyAdmin روی دیتابیس sazmat_sa ایمپورت کنید.
--  اجرای دوباره‌ی این فایل بی‌خطر است.
-- =============================================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `ad_contact_reveals` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ad_id`       BIGINT UNSIGNED NOT NULL,
  `user_id`     BIGINT UNSIGNED DEFAULT NULL,
  `ip_hash`     CHAR(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at`  TIMESTAMP NULL DEFAULT NULL,
  `revealed_on` DATE NOT NULL,
  PRIMARY KEY (`id`),

  -- یک بازدیدکننده در یک روز برای یک آگهی فقط یک بار شمرده می‌شود.
  UNIQUE KEY `ad_contact_reveals_daily_unique` (`ad_id`, `ip_hash`, `revealed_on`),

  -- کوئری پنل: تعداد نمایش شماره برای این آگهی در این ماه.
  KEY `ad_contact_reveals_ad_id_created_at_index` (`ad_id`, `created_at`),
  KEY `ad_contact_reveals_user_id_foreign` (`user_id`),

  CONSTRAINT `ad_contact_reveals_ad_id_foreign`
    FOREIGN KEY (`ad_id`) REFERENCES `ads` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ad_contact_reveals_user_id_foreign`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------------
-- ثبت migration
-- ---------------------------------------------------------------------------
INSERT INTO `migrations` (`migration`, `batch`)
SELECT
  '2026_09_20_000003_create_ad_contact_reveals_table',
  COALESCE((SELECT MAX(b.batch) FROM (SELECT batch FROM `migrations`) AS b), 0) + 1
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM (SELECT migration FROM `migrations`) AS m
  WHERE m.migration = '2026_09_20_000003_create_ad_contact_reveals_table'
);
