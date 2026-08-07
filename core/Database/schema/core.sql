CREATE TABLE IF NOT EXISTS `{prefix}settings` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `setting_key` VARCHAR(191) NOT NULL,
  `setting_value` LONGTEXT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `settings_key_unique` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `{prefix}roles` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug` VARCHAR(60) NOT NULL,
  `name` VARCHAR(120) NOT NULL,
  `is_system` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `{prefix}role_permissions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `role_id` INT UNSIGNED NOT NULL,
  `permission_key` VARCHAR(120) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_perm_unique` (`role_id`, `permission_key`),
  CONSTRAINT `fk_role_permissions_role` FOREIGN KEY (`role_id`) REFERENCES `{prefix}roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `{prefix}permissions_extra` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `permission_key` VARCHAR(120) NOT NULL,
  `label` VARCHAR(191) NOT NULL,
  `module_slug` VARCHAR(60) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_extra_key_unique` (`permission_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `{prefix}admin_users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(120) NOT NULL,
  `username` VARCHAR(60) NOT NULL,
  `email` VARCHAR(191) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role_id` INT UNSIGNED NOT NULL,
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL,
  `last_login_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `admin_users_username_unique` (`username`),
  UNIQUE KEY `admin_users_email_unique` (`email`),
  KEY `admin_users_role_idx` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `{prefix}modules` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug` VARCHAR(60) NOT NULL,
  `name` VARCHAR(120) NOT NULL,
  `version` VARCHAR(20) NOT NULL,
  `status` ENUM('enabled','disabled') NOT NULL DEFAULT 'enabled',
  `installed_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `modules_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `{prefix}activity_log` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NULL,
  `user_name` VARCHAR(120) NULL,
  `action` VARCHAR(60) NOT NULL,
  `description` VARCHAR(500) NULL,
  `subject_type` VARCHAR(60) NULL,
  `subject_id` VARCHAR(60) NULL,
  `ip_address` VARCHAR(45) NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `activity_log_action_idx` (`action`),
  KEY `activity_log_user_idx` (`user_id`),
  KEY `activity_log_created_idx` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `{prefix}rate_limits` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `limit_key` VARCHAR(191) NOT NULL,
  `attempts` INT UNSIGNED NOT NULL DEFAULT 1,
  `first_attempt_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rate_limits_key_unique` (`limit_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `{prefix}media` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `file_name` VARCHAR(255) NOT NULL,
  `stored_path` VARCHAR(255) NOT NULL,
  `thumb_path` VARCHAR(255) NULL,
  `mime_type` VARCHAR(100) NULL,
  `extension` VARCHAR(10) NULL,
  `size_bytes` INT UNSIGNED NOT NULL DEFAULT 0,
  `width` INT UNSIGNED NULL,
  `height` INT UNSIGNED NULL,
  `title` VARCHAR(191) NULL,
  `alt_text` VARCHAR(191) NULL,
  `caption` VARCHAR(500) NULL,
  `uploaded_by` INT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `media_created_idx` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `{prefix}menus` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug` VARCHAR(60) NOT NULL,
  `name` VARCHAR(120) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `menus_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `{prefix}menu_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `menu_id` INT UNSIGNED NOT NULL,
  `parent_id` INT UNSIGNED NULL,
  `label` VARCHAR(120) NOT NULL,
  `link_type` ENUM('page','news','custom_url','archive','gallery','directory','newsletter','tree','gatherings','events') NOT NULL DEFAULT 'custom_url',
  `target_id` INT UNSIGNED NULL,
  `url` VARCHAR(255) NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `open_new_tab` TINYINT(1) NOT NULL DEFAULT 0,
  `hide_on` ENUM('none','desktop','mobile') NOT NULL DEFAULT 'none',
  PRIMARY KEY (`id`),
  KEY `menu_items_menu_idx` (`menu_id`),
  KEY `menu_items_parent_idx` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `{prefix}cities` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(120) NOT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `{prefix}branches` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `description` TEXT NULL,
  `image_media_id` INT UNSIGNED NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `tree_node_id` INT UNSIGNED NULL,
  `show_public_page` TINYINT(1) NOT NULL DEFAULT 0,
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `{prefix}redirects` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `from_path` VARCHAR(255) NOT NULL,
  `to_path` VARCHAR(255) NOT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `redirects_from_unique` (`from_path`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `{prefix}backups_log` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `type` ENUM('database','files','full') NOT NULL,
  `file_path` VARCHAR(255) NOT NULL,
  `size_bytes` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `created_by` INT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `{prefix}demo_data_log` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `table_name` VARCHAR(60) NOT NULL,
  `record_id` INT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `{prefix}password_resets` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `token` VARCHAR(64) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `used_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `password_resets_token_unique` (`token`),
  KEY `password_resets_user_idx` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `{prefix}admin_user_mfa` (
  `user_id` INT UNSIGNED NOT NULL,
  `secret_encrypted` TEXT NOT NULL,
  `recovery_codes` TEXT NOT NULL,
  `enabled_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `{prefix}push_subscriptions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `endpoint` VARCHAR(500) NOT NULL,
  `endpoint_hash` CHAR(64) NOT NULL,
  `p256dh` VARCHAR(255) NOT NULL,
  `auth` VARCHAR(255) NOT NULL,
  `user_agent` VARCHAR(255) NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `push_subscriptions_endpoint_unique` (`endpoint_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `{prefix}push_log` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(120) NOT NULL,
  `body` VARCHAR(255) NULL,
  `url` VARCHAR(255) NULL,
  `sent_by` INT UNSIGNED NULL,
  `sent_by_name` VARCHAR(150) NOT NULL DEFAULT '',
  `sent_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `failed_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `removed_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `{prefix}schema_migrations` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(191) NOT NULL,
  `applied_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `schema_migrations_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
