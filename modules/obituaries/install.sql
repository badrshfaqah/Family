CREATE TABLE IF NOT EXISTS `{prefix}obituaries` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(191) NOT NULL,
  `city_id` INT UNSIGNED NULL,
  `deceased_on` DATE NULL,
  `condolence_venue` VARCHAR(255) NULL,
  `condolence_times` VARCHAR(255) NULL,
  `condolence_map_url` VARCHAR(255) NULL,
  `details` TEXT NULL,
  `status` ENUM('active','archived') NOT NULL DEFAULT 'active',
  `created_by` INT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `obituaries_status_idx` (`status`),
  KEY `obituaries_city_idx` (`city_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
