CREATE TABLE IF NOT EXISTS `{prefix}tree_nodes` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `parent_id` INT UNSIGNED NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `note` TEXT NULL,
  `branch_id` INT UNSIGNED NULL,
  `is_visible` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `tree_nodes_parent_idx` (`parent_id`),
  KEY `tree_nodes_branch_idx` (`branch_id`),
  KEY `tree_nodes_visible_idx` (`is_visible`),
  CONSTRAINT `fk_{prefix}tree_nodes_parent` FOREIGN KEY (`parent_id`) REFERENCES `{prefix}tree_nodes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
