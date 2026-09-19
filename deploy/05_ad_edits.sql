-- =============================================================================
--  سازمت | جدول درخواست‌های ویرایش آگهی
-- =============================================================================
--
--  ارائه‌دهنده آگهی‌اش را ویرایش می‌کند، اما تغییر بلافاصله روی سایت
--  نمی‌نشیند: اینجا به‌صورت «درخواست» ذخیره می‌شود و تا تأیید مدیر،
--  نسخه‌ی فعلی آگهی برای بازدیدکننده‌ها نمایش داده می‌شود.
--
--  payload  = فقط فیلدهایی که عوض شده‌اند، با مقدار جدید
--  original = همان فیلدها با مقدار قدیمی (برای نمایش «قبل ← بعد»)
--
--  معادل migration:
--    2026_09_20_000004_create_ad_edits_table
--
--  در phpMyAdmin روی دیتابیس sazmat_sa ایمپورت کنید.
--  اجرای دوباره‌ی این فایل بی‌خطر است.
-- =============================================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `ad_edits` (
  `id`                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ad_id`             BIGINT UNSIGNED NOT NULL,
  `user_id`           BIGINT UNSIGNED NOT NULL,
  `payload`           JSON NOT NULL,
  `original`          JSON NOT NULL,
  `added_images`      JSON DEFAULT NULL,
  `removed_image_ids` JSON DEFAULT NULL,
  `status`            ENUM('pending','approved','rejected')
                        COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `rejection_reason`  TEXT COLLATE utf8mb4_unicode_ci,
  `reviewed_by`       BIGINT UNSIGNED DEFAULT NULL,
  `reviewed_at`       TIMESTAMP NULL DEFAULT NULL,
  `created_at`        TIMESTAMP NULL DEFAULT NULL,
  `updated_at`        TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),

  KEY `ad_edits_status_created_at_index` (`status`, `created_at`),
  KEY `ad_edits_ad_id_status_index` (`ad_id`, `status`),
  KEY `ad_edits_user_id_foreign` (`user_id`),
  KEY `ad_edits_reviewed_by_foreign` (`reviewed_by`),

  CONSTRAINT `ad_edits_ad_id_foreign`
    FOREIGN KEY (`ad_id`) REFERENCES `ads` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ad_edits_user_id_foreign`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ad_edits_reviewed_by_foreign`
    FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------------
-- ثبت migration
-- ---------------------------------------------------------------------------
INSERT INTO `migrations` (`migration`, `batch`)
SELECT
  '2026_09_20_000004_create_ad_edits_table',
  COALESCE((SELECT MAX(b.batch) FROM (SELECT batch FROM `migrations`) AS b), 0) + 1
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM (SELECT migration FROM `migrations`) AS m
  WHERE m.migration = '2026_09_20_000004_create_ad_edits_table'
);
