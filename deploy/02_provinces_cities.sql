-- =============================================================================
--  سازمت | تکمیل استان‌ها و شهرها
-- =============================================================================
--
--  قبلاً فقط ۲۰ استان از ۳۱ استان کشور در دیتابیس بود. این فایل ۱۱ استان
--  جاافتاده و شهرهایشان را اضافه می‌کند، و چند شهر مهم به استان‌های موجود.
--
--  هر INSERT با NOT EXISTS محافظت شده، پس:
--    - رکوردهای موجود دست نمی‌خورند (اسلاگ فعلی‌شان حفظ می‌شود)
--    - اجرای دوباره‌ی این فایل هیچ رکورد تکراری نمی‌سازد
--
--  در phpMyAdmin روی دیتابیس sazmat_sa ایمپورت کنید.
-- =============================================================================

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------------
-- استان‌ها
-- ---------------------------------------------------------------------------
INSERT INTO `provinces` (`name`,`slug`,`created_at`,`updated_at`)
SELECT 'آذربایجان شرقی', 'آذربایجان-شرقی', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM (SELECT `name` FROM `provinces`) AS p WHERE p.`name` = 'آذربایجان شرقی');
INSERT INTO `provinces` (`name`,`slug`,`created_at`,`updated_at`)
SELECT 'آذربایجان غربی', 'آذربایجان-غربی', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM (SELECT `name` FROM `provinces`) AS p WHERE p.`name` = 'آذربایجان غربی');
INSERT INTO `provinces` (`name`,`slug`,`created_at`,`updated_at`)
SELECT 'اردبیل', 'اردبیل', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM (SELECT `name` FROM `provinces`) AS p WHERE p.`name` = 'اردبیل');
INSERT INTO `provinces` (`name`,`slug`,`created_at`,`updated_at`)
SELECT 'اصفهان', 'اصفهان', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM (SELECT `name` FROM `provinces`) AS p WHERE p.`name` = 'اصفهان');
INSERT INTO `provinces` (`name`,`slug`,`created_at`,`updated_at`)
SELECT 'البرز', 'البرز', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM (SELECT `name` FROM `provinces`) AS p WHERE p.`name` = 'البرز');
INSERT INTO `provinces` (`name`,`slug`,`created_at`,`updated_at`)
SELECT 'ایلام', 'ایلام', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM (SELECT `name` FROM `provinces`) AS p WHERE p.`name` = 'ایلام');
INSERT INTO `provinces` (`name`,`slug`,`created_at`,`updated_at`)
SELECT 'بوشهر', 'بوشهر', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM (SELECT `name` FROM `provinces`) AS p WHERE p.`name` = 'بوشهر');
INSERT INTO `provinces` (`name`,`slug`,`created_at`,`updated_at`)
SELECT 'تهران', 'تهران', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM (SELECT `name` FROM `provinces`) AS p WHERE p.`name` = 'تهران');
INSERT INTO `provinces` (`name`,`slug`,`created_at`,`updated_at`)
SELECT 'چهارمحال و بختیاری', 'چهارمحال-و-بختیاری', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM (SELECT `name` FROM `provinces`) AS p WHERE p.`name` = 'چهارمحال و بختیاری');
INSERT INTO `provinces` (`name`,`slug`,`created_at`,`updated_at`)
SELECT 'خراسان جنوبی', 'خراسان-جنوبی', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM (SELECT `name` FROM `provinces`) AS p WHERE p.`name` = 'خراسان جنوبی');
INSERT INTO `provinces` (`name`,`slug`,`created_at`,`updated_at`)
SELECT 'خراسان رضوی', 'خراسان-رضوی', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM (SELECT `name` FROM `provinces`) AS p WHERE p.`name` = 'خراسان رضوی');
INSERT INTO `provinces` (`name`,`slug`,`created_at`,`updated_at`)
SELECT 'خراسان شمالی', 'خراسان-شمالی', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM (SELECT `name` FROM `provinces`) AS p WHERE p.`name` = 'خراسان شمالی');
INSERT INTO `provinces` (`name`,`slug`,`created_at`,`updated_at`)
SELECT 'خوزستان', 'خوزستان', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM (SELECT `name` FROM `provinces`) AS p WHERE p.`name` = 'خوزستان');
INSERT INTO `provinces` (`name`,`slug`,`created_at`,`updated_at`)
SELECT 'زنجان', 'زنجان', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM (SELECT `name` FROM `provinces`) AS p WHERE p.`name` = 'زنجان');
INSERT INTO `provinces` (`name`,`slug`,`created_at`,`updated_at`)
SELECT 'سمنان', 'سمنان', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM (SELECT `name` FROM `provinces`) AS p WHERE p.`name` = 'سمنان');
INSERT INTO `provinces` (`name`,`slug`,`created_at`,`updated_at`)
SELECT 'سیستان و بلوچستان', 'سیستان-و-بلوچستان', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM (SELECT `name` FROM `provinces`) AS p WHERE p.`name` = 'سیستان و بلوچستان');
INSERT INTO `provinces` (`name`,`slug`,`created_at`,`updated_at`)
SELECT 'فارس', 'فارس', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM (SELECT `name` FROM `provinces`) AS p WHERE p.`name` = 'فارس');
INSERT INTO `provinces` (`name`,`slug`,`created_at`,`updated_at`)
SELECT 'قزوین', 'قزوین', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM (SELECT `name` FROM `provinces`) AS p WHERE p.`name` = 'قزوین');
INSERT INTO `provinces` (`name`,`slug`,`created_at`,`updated_at`)
SELECT 'قم', 'قم', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM (SELECT `name` FROM `provinces`) AS p WHERE p.`name` = 'قم');
INSERT INTO `provinces` (`name`,`slug`,`created_at`,`updated_at`)
SELECT 'کردستان', 'کردستان', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM (SELECT `name` FROM `provinces`) AS p WHERE p.`name` = 'کردستان');
INSERT INTO `provinces` (`name`,`slug`,`created_at`,`updated_at`)
SELECT 'کرمان', 'کرمان', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM (SELECT `name` FROM `provinces`) AS p WHERE p.`name` = 'کرمان');
INSERT INTO `provinces` (`name`,`slug`,`created_at`,`updated_at`)
SELECT 'کرمانشاه', 'کرمانشاه', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM (SELECT `name` FROM `provinces`) AS p WHERE p.`name` = 'کرمانشاه');
INSERT INTO `provinces` (`name`,`slug`,`created_at`,`updated_at`)
SELECT 'کهگیلویه و بویراحمد', 'کهگیلویه-و-بویراحمد', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM (SELECT `name` FROM `provinces`) AS p WHERE p.`name` = 'کهگیلویه و بویراحمد');
INSERT INTO `provinces` (`name`,`slug`,`created_at`,`updated_at`)
SELECT 'گلستان', 'گلستان', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM (SELECT `name` FROM `provinces`) AS p WHERE p.`name` = 'گلستان');
INSERT INTO `provinces` (`name`,`slug`,`created_at`,`updated_at`)
SELECT 'گیلان', 'گیلان', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM (SELECT `name` FROM `provinces`) AS p WHERE p.`name` = 'گیلان');
INSERT INTO `provinces` (`name`,`slug`,`created_at`,`updated_at`)
SELECT 'لرستان', 'لرستان', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM (SELECT `name` FROM `provinces`) AS p WHERE p.`name` = 'لرستان');
INSERT INTO `provinces` (`name`,`slug`,`created_at`,`updated_at`)
SELECT 'مازندران', 'مازندران', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM (SELECT `name` FROM `provinces`) AS p WHERE p.`name` = 'مازندران');
INSERT INTO `provinces` (`name`,`slug`,`created_at`,`updated_at`)
SELECT 'مرکزی', 'مرکزی', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM (SELECT `name` FROM `provinces`) AS p WHERE p.`name` = 'مرکزی');
INSERT INTO `provinces` (`name`,`slug`,`created_at`,`updated_at`)
SELECT 'هرمزگان', 'هرمزگان', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM (SELECT `name` FROM `provinces`) AS p WHERE p.`name` = 'هرمزگان');
INSERT INTO `provinces` (`name`,`slug`,`created_at`,`updated_at`)
SELECT 'همدان', 'همدان', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM (SELECT `name` FROM `provinces`) AS p WHERE p.`name` = 'همدان');
INSERT INTO `provinces` (`name`,`slug`,`created_at`,`updated_at`)
SELECT 'یزد', 'یزد', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM (SELECT `name` FROM `provinces`) AS p WHERE p.`name` = 'یزد');

-- ---------------------------------------------------------------------------
-- شهرها
-- ---------------------------------------------------------------------------

-- تهران
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'تهران', 'تهران', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'تهران'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'تهران');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'ری', 'ری', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'تهران'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'ری');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'شمیرانات', 'شمیرانات', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'تهران'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'شمیرانات');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'شهریار', 'شهریار', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'تهران'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'شهریار');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'اسلامشهر', 'اسلامشهر', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'تهران'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'اسلامشهر');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'پاکدشت', 'پاکدشت', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'تهران'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'پاکدشت');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'ورامین', 'ورامین', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'تهران'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'ورامین');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'دماوند', 'دماوند', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'تهران'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'دماوند');

-- اصفهان
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'اصفهان', 'اصفهان', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'اصفهان'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'اصفهان');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'کاشان', 'کاشان', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'اصفهان'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'کاشان');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'خمینی‌شهر', 'خمینی-شهر', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'اصفهان'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'خمینی‌شهر');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'نجف‌آباد', 'نجف-آباد', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'اصفهان'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'نجف‌آباد');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'شاهین‌شهر', 'شاهین-شهر', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'اصفهان'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'شاهین‌شهر');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'مبارکه', 'مبارکه', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'اصفهان'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'مبارکه');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'لنجان', 'لنجان', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'اصفهان'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'لنجان');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'اردستان', 'اردستان', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'اصفهان'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'اردستان');

-- فارس
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'شیراز', 'شیراز', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'فارس'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'شیراز');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'مرودشت', 'مرودشت', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'فارس'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'مرودشت');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'جهرم', 'جهرم', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'فارس'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'جهرم');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'فسا', 'فسا', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'فارس'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'فسا');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'کازرون', 'کازرون', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'فارس'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'کازرون');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'لار', 'لار', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'فارس'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'لار');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'داراب', 'داراب', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'فارس'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'داراب');

-- خراسان رضوی
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'مشهد', 'مشهد', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'خراسان رضوی'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'مشهد');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'نیشابور', 'نیشابور', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'خراسان رضوی'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'نیشابور');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'سبزوار', 'سبزوار', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'خراسان رضوی'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'سبزوار');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'تربت حیدریه', 'تربت-حیدریه', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'خراسان رضوی'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'تربت حیدریه');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'قوچان', 'قوچان', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'خراسان رضوی'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'قوچان');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'کاشمر', 'کاشمر', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'خراسان رضوی'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'کاشمر');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'تربت جام', 'تربت-جام', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'خراسان رضوی'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'تربت جام');

-- خوزستان
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'اهواز', 'اهواز', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'خوزستان'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'اهواز');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'دزفول', 'دزفول', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'خوزستان'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'دزفول');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'آبادان', 'آبادان', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'خوزستان'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'آبادان');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'خرمشهر', 'خرمشهر', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'خوزستان'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'خرمشهر');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'بندر ماهشهر', 'بندر-ماهشهر', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'خوزستان'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'بندر ماهشهر');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'اندیمشک', 'اندیمشک', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'خوزستان'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'اندیمشک');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'شوشتر', 'شوشتر', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'خوزستان'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'شوشتر');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'بهبهان', 'بهبهان', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'خوزستان'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'بهبهان');

-- مازندران
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'ساری', 'ساری', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'مازندران'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'ساری');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'آمل', 'آمل', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'مازندران'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'آمل');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'بابل', 'بابل', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'مازندران'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'بابل');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'نوشهر', 'نوشهر', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'مازندران'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'نوشهر');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'قائم‌شهر', 'قائم-شهر', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'مازندران'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'قائم‌شهر');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'چالوس', 'چالوس', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'مازندران'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'چالوس');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'تنکابن', 'تنکابن', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'مازندران'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'تنکابن');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'بهشهر', 'بهشهر', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'مازندران'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'بهشهر');

-- گیلان
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'رشت', 'رشت', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'گیلان'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'رشت');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'بندر انزلی', 'بندر-انزلی', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'گیلان'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'بندر انزلی');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'لاهیجان', 'لاهیجان', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'گیلان'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'لاهیجان');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'رودسر', 'رودسر', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'گیلان'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'رودسر');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'لنگرود', 'لنگرود', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'گیلان'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'لنگرود');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'تالش', 'تالش', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'گیلان'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'تالش');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'آستارا', 'آستارا', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'گیلان'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'آستارا');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'صومعه‌سرا', 'صومعه-سرا', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'گیلان'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'صومعه‌سرا');

-- آذربایجان شرقی
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'تبریز', 'تبریز', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'آذربایجان شرقی'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'تبریز');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'مراغه', 'مراغه', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'آذربایجان شرقی'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'مراغه');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'مرند', 'مرند', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'آذربایجان شرقی'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'مرند');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'اهر', 'اهر', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'آذربایجان شرقی'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'اهر');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'میانه', 'میانه', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'آذربایجان شرقی'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'میانه');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'بناب', 'بناب', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'آذربایجان شرقی'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'بناب');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'شبستر', 'شبستر', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'آذربایجان شرقی'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'شبستر');

-- آذربایجان غربی
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'ارومیه', 'ارومیه', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'آذربایجان غربی'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'ارومیه');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'خوی', 'خوی', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'آذربایجان غربی'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'خوی');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'میاندوآب', 'میاندوآب', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'آذربایجان غربی'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'میاندوآب');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'مهاباد', 'مهاباد', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'آذربایجان غربی'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'مهاباد');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'بوکان', 'بوکان', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'آذربایجان غربی'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'بوکان');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'سلماس', 'سلماس', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'آذربایجان غربی'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'سلماس');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'نقده', 'نقده', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'آذربایجان غربی'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'نقده');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'پیرانشهر', 'پیرانشهر', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'آذربایجان غربی'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'پیرانشهر');

-- کرمان
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'کرمان', 'کرمان', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'کرمان'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'کرمان');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'رفسنجان', 'رفسنجان', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'کرمان'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'رفسنجان');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'سیرجان', 'سیرجان', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'کرمان'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'سیرجان');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'جیرفت', 'جیرفت', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'کرمان'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'جیرفت');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'بم', 'بم', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'کرمان'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'بم');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'زرند', 'زرند', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'کرمان'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'زرند');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'شهربابک', 'شهربابک', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'کرمان'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'شهربابک');

-- کرمانشاه
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'کرمانشاه', 'کرمانشاه', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'کرمانشاه'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'کرمانشاه');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'اسلام‌آباد غرب', 'اسلام-آباد-غرب', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'کرمانشاه'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'اسلام‌آباد غرب');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'کنگاور', 'کنگاور', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'کرمانشاه'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'کنگاور');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'سنقر', 'سنقر', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'کرمانشاه'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'سنقر');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'هرسین', 'هرسین', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'کرمانشاه'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'هرسین');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'جوانرود', 'جوانرود', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'کرمانشاه'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'جوانرود');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'سرپل ذهاب', 'سرپل-ذهاب', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'کرمانشاه'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'سرپل ذهاب');

-- همدان
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'همدان', 'همدان', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'همدان'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'همدان');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'ملایر', 'ملایر', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'همدان'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'ملایر');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'نهاوند', 'نهاوند', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'همدان'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'نهاوند');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'تویسرکان', 'تویسرکان', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'همدان'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'تویسرکان');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'اسدآباد', 'اسدآباد', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'همدان'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'اسدآباد');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'بهار', 'بهار', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'همدان'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'بهار');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'کبودرآهنگ', 'کبودرآهنگ', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'همدان'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'کبودرآهنگ');

-- یزد
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'یزد', 'یزد', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'یزد'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'یزد');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'میبد', 'میبد', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'یزد'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'میبد');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'اردکان', 'اردکان', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'یزد'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'اردکان');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'بافق', 'بافق', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'یزد'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'بافق');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'مهریز', 'مهریز', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'یزد'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'مهریز');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'تفت', 'تفت', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'یزد'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'تفت');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'اشکذر', 'اشکذر', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'یزد'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'اشکذر');

-- سمنان
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'سمنان', 'سمنان', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'سمنان'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'سمنان');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'شاهرود', 'شاهرود', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'سمنان'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'شاهرود');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'دامغان', 'دامغان', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'سمنان'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'دامغان');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'گرمسار', 'گرمسار', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'سمنان'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'گرمسار');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'مهدی‌شهر', 'مهدی-شهر', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'سمنان'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'مهدی‌شهر');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'ایوانکی', 'ایوانکی', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'سمنان'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'ایوانکی');

-- البرز
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'کرج', 'کرج', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'البرز'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'کرج');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'نظرآباد', 'نظرآباد', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'البرز'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'نظرآباد');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'هشتگرد', 'هشتگرد', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'البرز'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'هشتگرد');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'طالقان', 'طالقان', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'البرز'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'طالقان');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'فردیس', 'فردیس', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'البرز'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'فردیس');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'اشتهارد', 'اشتهارد', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'البرز'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'اشتهارد');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'ماهدشت', 'ماهدشت', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'البرز'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'ماهدشت');

-- قم
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'قم', 'قم', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'قم'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'قم');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'جعفریه', 'جعفریه', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'قم'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'جعفریه');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'کهک', 'کهک', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'قم'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'کهک');

-- قزوین
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'قزوین', 'قزوین', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'قزوین'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'قزوین');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'تاکستان', 'تاکستان', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'قزوین'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'تاکستان');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'آبیک', 'آبیک', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'قزوین'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'آبیک');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'الوند', 'الوند', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'قزوین'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'الوند');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'بوئین‌زهرا', 'بوئین-زهرا', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'قزوین'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'بوئین‌زهرا');

-- گلستان
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'گرگان', 'گرگان', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'گلستان'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'گرگان');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'گنبد کاووس', 'گنبد-کاووس', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'گلستان'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'گنبد کاووس');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'علی‌آباد کتول', 'علی-آباد-کتول', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'گلستان'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'علی‌آباد کتول');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'بندر ترکمن', 'بندر-ترکمن', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'گلستان'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'بندر ترکمن');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'آق‌قلا', 'آق-قلا', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'گلستان'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'آق‌قلا');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'کردکوی', 'کردکوی', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'گلستان'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'کردکوی');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'مینودشت', 'مینودشت', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'گلستان'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'مینودشت');

-- مرکزی
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'اراک', 'اراک', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'مرکزی'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'اراک');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'ساوه', 'ساوه', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'مرکزی'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'ساوه');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'خمین', 'خمین', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'مرکزی'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'خمین');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'محلات', 'محلات', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'مرکزی'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'محلات');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'دلیجان', 'دلیجان', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'مرکزی'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'دلیجان');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'شازند', 'شازند', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'مرکزی'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'شازند');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'تفرش', 'تفرش', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'مرکزی'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'تفرش');

-- هرمزگان
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'بندرعباس', 'بندرعباس', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'هرمزگان'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'بندرعباس');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'میناب', 'میناب', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'هرمزگان'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'میناب');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'قشم', 'قشم', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'هرمزگان'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'قشم');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'بندر لنگه', 'بندر-لنگه', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'هرمزگان'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'بندر لنگه');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'رودان', 'رودان', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'هرمزگان'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'رودان');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'کیش', 'کیش', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'هرمزگان'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'کیش');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'بستک', 'بستک', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'هرمزگان'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'بستک');

-- اردبیل
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'اردبیل', 'اردبیل', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'اردبیل'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'اردبیل');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'پارس‌آباد', 'پارس-آباد', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'اردبیل'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'پارس‌آباد');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'مشگین‌شهر', 'مشگین-شهر', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'اردبیل'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'مشگین‌شهر');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'خلخال', 'خلخال', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'اردبیل'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'خلخال');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'گرمی', 'گرمی', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'اردبیل'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'گرمی');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'بیله‌سوار', 'بیله-سوار', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'اردبیل'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'بیله‌سوار');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'نمین', 'نمین', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'اردبیل'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'نمین');

-- بوشهر
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'بوشهر', 'بوشهر', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'بوشهر'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'بوشهر');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'برازجان', 'برازجان', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'بوشهر'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'برازجان');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'گناوه', 'گناوه', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'بوشهر'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'گناوه');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'دیلم', 'دیلم', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'بوشهر'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'دیلم');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'کنگان', 'کنگان', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'بوشهر'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'کنگان');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'جم', 'جم', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'بوشهر'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'جم');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'عسلویه', 'عسلویه', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'بوشهر'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'عسلویه');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'دشتستان', 'دشتستان', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'بوشهر'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'دشتستان');

-- چهارمحال و بختیاری
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'شهرکرد', 'شهرکرد', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'چهارمحال و بختیاری'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'شهرکرد');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'بروجن', 'بروجن', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'چهارمحال و بختیاری'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'بروجن');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'فارسان', 'فارسان', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'چهارمحال و بختیاری'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'فارسان');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'لردگان', 'لردگان', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'چهارمحال و بختیاری'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'لردگان');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'اردل', 'اردل', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'چهارمحال و بختیاری'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'اردل');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'سامان', 'سامان', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'چهارمحال و بختیاری'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'سامان');

-- خراسان جنوبی
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'بیرجند', 'بیرجند', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'خراسان جنوبی'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'بیرجند');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'قائن', 'قائن', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'خراسان جنوبی'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'قائن');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'فردوس', 'فردوس', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'خراسان جنوبی'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'فردوس');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'طبس', 'طبس', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'خراسان جنوبی'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'طبس');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'نهبندان', 'نهبندان', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'خراسان جنوبی'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'نهبندان');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'سربیشه', 'سربیشه', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'خراسان جنوبی'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'سربیشه');

-- خراسان شمالی
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'بجنورد', 'بجنورد', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'خراسان شمالی'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'بجنورد');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'شیروان', 'شیروان', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'خراسان شمالی'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'شیروان');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'اسفراین', 'اسفراین', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'خراسان شمالی'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'اسفراین');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'آشخانه', 'آشخانه', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'خراسان شمالی'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'آشخانه');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'جاجرم', 'جاجرم', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'خراسان شمالی'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'جاجرم');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'گرمه', 'گرمه', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'خراسان شمالی'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'گرمه');

-- زنجان
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'زنجان', 'زنجان', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'زنجان'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'زنجان');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'ابهر', 'ابهر', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'زنجان'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'ابهر');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'خرمدره', 'خرمدره', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'زنجان'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'خرمدره');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'قیدار', 'قیدار', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'زنجان'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'قیدار');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'ماهنشان', 'ماهنشان', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'زنجان'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'ماهنشان');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'طارم', 'طارم', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'زنجان'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'طارم');

-- سیستان و بلوچستان
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'زاهدان', 'زاهدان', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'سیستان و بلوچستان'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'زاهدان');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'زابل', 'زابل', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'سیستان و بلوچستان'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'زابل');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'چابهار', 'چابهار', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'سیستان و بلوچستان'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'چابهار');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'ایرانشهر', 'ایرانشهر', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'سیستان و بلوچستان'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'ایرانشهر');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'سراوان', 'سراوان', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'سیستان و بلوچستان'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'سراوان');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'خاش', 'خاش', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'سیستان و بلوچستان'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'خاش');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'نیک‌شهر', 'نیک-شهر', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'سیستان و بلوچستان'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'نیک‌شهر');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'کنارک', 'کنارک', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'سیستان و بلوچستان'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'کنارک');

-- کردستان
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'سنندج', 'سنندج', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'کردستان'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'سنندج');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'سقز', 'سقز', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'کردستان'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'سقز');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'مریوان', 'مریوان', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'کردستان'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'مریوان');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'بانه', 'بانه', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'کردستان'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'بانه');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'قروه', 'قروه', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'کردستان'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'قروه');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'بیجار', 'بیجار', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'کردستان'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'بیجار');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'کامیاران', 'کامیاران', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'کردستان'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'کامیاران');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'دیواندره', 'دیواندره', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'کردستان'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'دیواندره');

-- کهگیلویه و بویراحمد
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'یاسوج', 'یاسوج', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'کهگیلویه و بویراحمد'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'یاسوج');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'دوگنبدان', 'دوگنبدان', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'کهگیلویه و بویراحمد'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'دوگنبدان');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'دهدشت', 'دهدشت', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'کهگیلویه و بویراحمد'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'دهدشت');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'سی‌سخت', 'سی-سخت', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'کهگیلویه و بویراحمد'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'سی‌سخت');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'لیکک', 'لیکک', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'کهگیلویه و بویراحمد'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'لیکک');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'چرام', 'چرام', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'کهگیلویه و بویراحمد'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'چرام');

-- لرستان
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'خرم‌آباد', 'خرم-آباد', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'لرستان'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'خرم‌آباد');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'بروجرد', 'بروجرد', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'لرستان'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'بروجرد');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'دورود', 'دورود', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'لرستان'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'دورود');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'الیگودرز', 'الیگودرز', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'لرستان'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'الیگودرز');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'کوهدشت', 'کوهدشت', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'لرستان'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'کوهدشت');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'ازنا', 'ازنا', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'لرستان'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'ازنا');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'نورآباد', 'نورآباد', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'لرستان'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'نورآباد');

-- ایلام
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'ایلام', 'ایلام', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'ایلام'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'ایلام');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'دهلران', 'دهلران', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'ایلام'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'دهلران');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'آبدانان', 'آبدانان', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'ایلام'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'آبدانان');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'مهران', 'مهران', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'ایلام'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'مهران');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'ایوان', 'ایوان', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'ایلام'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'ایوان');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'دره‌شهر', 'دره-شهر', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'ایلام'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'دره‌شهر');
INSERT INTO `cities` (`province_id`,`name`,`slug`,`created_at`,`updated_at`)
SELECT p.`id`, 'سرابله', 'سرابله', NOW(), NOW() FROM `provinces` p
WHERE p.`name` = 'ایلام'
  AND NOT EXISTS (SELECT 1 FROM (SELECT `province_id`,`name` FROM `cities`) AS c
                  WHERE c.`province_id` = p.`id` AND c.`name` = 'سرابله');
