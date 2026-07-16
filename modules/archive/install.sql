CREATE TABLE IF NOT EXISTS `{prefix}archive_categories` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(120) NOT NULL,
  `slug` VARCHAR(120) NOT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `archive_categories_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `{prefix}archive_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(191) NOT NULL,
  `slug` VARCHAR(191) NOT NULL,
  `description` TEXT NULL,
  `cover_media_id` INT UNSIGNED NULL,
  `file_media_id` INT UNSIGNED NULL,
  `category_id` INT UNSIGNED NULL,
  `period_label` VARCHAR(100) NULL,
  `source` VARCHAR(255) NULL,
  `notes` TEXT NULL,
  `tags` VARCHAR(500) NULL,
  `status` ENUM('draft','published') NOT NULL DEFAULT 'draft',
  `seo_title` VARCHAR(191) NULL,
  `seo_description` VARCHAR(500) NULL,
  `created_by` INT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `archive_items_slug_unique` (`slug`),
  KEY `archive_items_status_idx` (`status`),
  KEY `archive_items_category_idx` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
