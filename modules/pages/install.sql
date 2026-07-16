CREATE TABLE IF NOT EXISTS `{prefix}pages` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(191) NOT NULL,
  `slug` VARCHAR(191) NOT NULL,
  `content` LONGTEXT NULL,
  `cover_media_id` INT UNSIGNED NULL,
  `template` VARCHAR(60) NOT NULL DEFAULT 'default',
  `status` ENUM('draft','published') NOT NULL DEFAULT 'draft',
  `sort_order` INT NOT NULL DEFAULT 0,
  `show_in_menu` TINYINT(1) NOT NULL DEFAULT 0,
  `seo_title` VARCHAR(191) NULL,
  `seo_description` VARCHAR(500) NULL,
  `created_by` INT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pages_slug_unique` (`slug`),
  KEY `pages_status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
