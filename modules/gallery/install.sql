CREATE TABLE IF NOT EXISTS `{prefix}gallery_albums` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(191) NOT NULL,
  `slug` VARCHAR(191) NOT NULL,
  `description` VARCHAR(500) NULL,
  `cover_media_id` INT UNSIGNED NULL,
  `album_type` ENUM('photo','video') NOT NULL DEFAULT 'photo',
  `video_url` VARCHAR(255) NULL,
  `year` SMALLINT UNSIGNED NULL,
  `city_id` INT UNSIGNED NULL,
  `branch_id` INT UNSIGNED NULL,
  `related_news_id` INT UNSIGNED NULL,
  `related_event_id` INT UNSIGNED NULL,
  `related_calendar_id` INT UNSIGNED NULL,
  `status` ENUM('draft','published') NOT NULL DEFAULT 'draft',
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_by` INT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `gallery_albums_slug_unique` (`slug`),
  KEY `gallery_albums_status_idx` (`status`),
  KEY `gallery_albums_year_idx` (`year`),
  KEY `gallery_albums_city_idx` (`city_id`),
  KEY `gallery_albums_branch_idx` (`branch_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `{prefix}gallery_photos` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `album_id` INT UNSIGNED NOT NULL,
  `media_id` INT UNSIGNED NOT NULL,
  `caption` VARCHAR(255) NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `gallery_photos_album_idx` (`album_id`),
  KEY `gallery_photos_media_idx` (`media_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
