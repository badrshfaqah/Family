CREATE TABLE IF NOT EXISTS `{prefix}directory_contacts` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `phone` VARCHAR(30) NOT NULL,
  `city_id` INT UNSIGNED NULL,
  `city_ids` VARCHAR(191) NULL,
  `branch_id` INT UNSIGNED NULL,
  `email` VARCHAR(190) NULL,
  `status` ENUM('active','excluded') NOT NULL DEFAULT 'active',
  `source` ENUM('website_form','admin_manual','import_csv') NOT NULL DEFAULT 'admin_manual',
  `consent_at` DATETIME NULL,
  `registered_at` DATETIME NOT NULL,
  `created_by` INT UNSIGNED NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `directory_contacts_phone_idx` (`phone`),
  KEY `directory_contacts_status_idx` (`status`),
  KEY `directory_contacts_city_idx` (`city_id`),
  KEY `directory_contacts_branch_idx` (`branch_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `{prefix}directory_removal_requests` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `phone` VARCHAR(30) NOT NULL,
  `requested_at` DATETIME NOT NULL,
  `status` ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `reviewed_by` INT UNSIGNED NULL,
  `reviewed_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `directory_removal_requests_status_idx` (`status`),
  KEY `directory_removal_requests_phone_idx` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `{prefix}directory_message_templates` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `body` TEXT NOT NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `{prefix}directory_message_templates` (`name`, `body`, `created_at`) VALUES
('دعوة زواج', 'يسرنا دعوتكم لحضور حفل زواج {event_name}، وذلك يوم {date} الساعة {time} في {city}.\nللتفاصيل: {link}', NOW()),
('إعلان عزاء', 'تعزي إدارة الموقع بوفاة {event_name}، وذلك يوم {date}. مكان استقبال العزاء: {city} الساعة {time}.\n{link}', NOW()),
('دعوة وليمة', 'تتشرف إدارة الموقع بدعوتكم لحضور وليمة {event_name}، وذلك يوم {date} الساعة {time} في {city}.\n{link}', NOW()),
('إعلان اجتماع', 'تعلن إدارة الموقع عن عقد اجتماع {event_name} يوم {date} الساعة {time} في {city}.\nللمزيد: {link}', NOW()),
('رسالة عامة', '{event_name}\nالتاريخ: {date}\nالوقت: {time}\nالمكان: {city}\nللتفاصيل: {link}', NOW());
