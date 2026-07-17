CREATE TABLE IF NOT EXISTS `{prefix}newsletter_subscribers` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NULL,
  `email` VARCHAR(191) NOT NULL,
  `status` ENUM('subscribed','unsubscribed') NOT NULL DEFAULT 'subscribed',
  `category` VARCHAR(120) NULL,
  `source` ENUM('website_form','admin_manual','import_csv') NOT NULL DEFAULT 'admin_manual',
  `subscribed_at` DATETIME NULL,
  `unsubscribed_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `newsletter_subscribers_email_unique` (`email`),
  KEY `newsletter_subscribers_status_idx` (`status`),
  KEY `newsletter_subscribers_category_idx` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
