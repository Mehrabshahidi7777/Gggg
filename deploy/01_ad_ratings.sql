-- =============================================================================
--  سازمت | افزودن جدول امتیاز ستاره‌ای آگهی‌ها
-- =============================================================================
--
--  این فایل را در phpMyAdmin (یا هر ابزار مدیریت دیتابیس هاست) روی
--  دیتابیس `sazmat_sa` ایمپورت کنید.
--
--  چرا این فایل لازم است:
--  ------------------------------------------------------------------
--  چون به ترمینال دسترسی ندارید و نمی‌توانید `php artisan migrate`
--  را اجرا کنید. این فایل دقیقاً همان کاری را می‌کند که migration
--  فایلِ 2026_09_20_000001_create_ad_ratings_table.php انجام می‌دهد،
--  و در انتها خودش را در جدول `migrations` ثبت می‌کند تا اگر روزی
--  artisan migrate اجرا شد، دوباره تلاش نکند و خطا ندهد.
--
--  اجرای دوباره‌ی این فایل بی‌خطر است (IF NOT EXISTS و INSERT شرطی).
--
--  پیش‌نیاز: جدول‌های `ads` و `users` باید وجود داشته باشند.
-- =============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 1;

-- -----------------------------------------------------------------------------
-- 1) جدول امتیازها
-- -----------------------------------------------------------------------------
--
-- قید یکتای (ad_id, user_id) تضمین می‌کند هر کاربر فقط یک امتیاز برای
-- هر آگهی داشته باشد. ثبت دوباره، همان ردیف را به‌روز می‌کند.
--
CREATE TABLE IF NOT EXISTS `ad_ratings` (
  `id`         BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `ad_id`      BIGINT UNSIGNED  NOT NULL,
  `user_id`    BIGINT UNSIGNED  NOT NULL,
  `rating`     TINYINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ad_ratings_ad_id_user_id_unique` (`ad_id`, `user_id`),
  KEY `ad_ratings_ad_id_rating_index` (`ad_id`, `rating`),
  KEY `ad_ratings_user_id_foreign` (`user_id`),
  CONSTRAINT `ad_ratings_ad_id_foreign`
    FOREIGN KEY (`ad_id`) REFERENCES `ads` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ad_ratings_user_id_foreign`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- -----------------------------------------------------------------------------
-- 2) ثبت migration تا artisan دوباره اجرایش نکند
-- -----------------------------------------------------------------------------
--
-- نکته‌ی نحوی: در MySQL نمی‌توان SELECT بدون FROM را با WHERE آورد،
-- و نمی‌توان مستقیماً از جدولی که در حال INSERT در آن هستیم در
-- زیرکوئری خواند. برای همین هم `FROM DUAL` آمده و هم زیرکوئری‌های
-- روی `migrations` داخل جدول مشتق (AS b / AS m) پیچیده شده‌اند.
--
INSERT INTO `migrations` (`migration`, `batch`)
SELECT
  '2026_09_20_000001_create_ad_ratings_table',
  COALESCE((SELECT MAX(b.batch) FROM (SELECT batch FROM `migrations`) AS b), 0) + 1
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM (SELECT migration FROM `migrations`) AS m
  WHERE m.migration = '2026_09_20_000001_create_ad_ratings_table'
);
