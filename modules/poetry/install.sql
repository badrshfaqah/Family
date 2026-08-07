CREATE TABLE IF NOT EXISTS `{prefix}poets` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(191) NOT NULL,
  `bio` VARCHAR(500) NULL,
  `photo_media_id` INT UNSIGNED NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `status` ENUM('active','hidden') NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `poets_status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `{prefix}poems` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `poet_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(191) NOT NULL,
  `content` TEXT NOT NULL,
  `occasion` VARCHAR(191) NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `status` ENUM('published','draft') NOT NULL DEFAULT 'published',
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `poems_poet_idx` (`poet_id`),
  KEY `poems_status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
