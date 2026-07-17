CREATE TABLE IF NOT EXISTS `{prefix}news_categories` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(120) NOT NULL,
  `slug` VARCHAR(120) NOT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `news_categories_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `{prefix}news` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(191) NOT NULL,
  `slug` VARCHAR(191) NOT NULL,
  `excerpt` VARCHAR(500) NULL,
  `content` LONGTEXT NULL,
  `category_id` INT UNSIGNED NULL,
  `tags` VARCHAR(500) NULL,
  `cover_media_id` INT UNSIGNED NULL,
  `gallery_media_ids` VARCHAR(500) NULL,
  `video_url` VARCHAR(255) NULL,
  `attachment_media_ids` VARCHAR(500) NULL,
  `external_links` TEXT NULL,
  `author_name` VARCHAR(150) NULL,
  `status` ENUM('draft','published','scheduled','archived') NOT NULL DEFAULT 'draft',
  `is_pinned` TINYINT(1) NOT NULL DEFAULT 0,
  `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
  `social_image_media_id` INT UNSIGNED NULL,
  `seo_title` VARCHAR(191) NULL,
  `seo_description` VARCHAR(500) NULL,
  `published_at` DATETIME NULL,
  `created_by` INT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `news_slug_unique` (`slug`),
  KEY `news_status_idx` (`status`),
  KEY `news_published_idx` (`published_at`),
  KEY `news_category_idx` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
