-- Sales & Marketing Module — run once on each server (admin + staging)

CREATE TABLE IF NOT EXISTS `sales_queries` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ref_no` VARCHAR(20) NOT NULL,
  `contact_name` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `email` VARCHAR(100) NULL,
  `company` VARCHAR(150) NULL,
  `zone_id` BIGINT UNSIGNED NULL,
  `assigned_admin_id` BIGINT UNSIGNED NULL,
  `source` ENUM('website','phone','whatsapp','email','referral','other') NOT NULL DEFAULT 'other',
  `status` ENUM('new','in_progress','converted','lost','on_hold') NOT NULL DEFAULT 'new',
  `priority` ENUM('low','medium','high') NOT NULL DEFAULT 'medium',
  `description` TEXT NULL,
  `notes` TEXT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sales_queries_ref_no_unique` (`ref_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sales_followups` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `query_id` BIGINT UNSIGNED NULL,
  `title` VARCHAR(200) NOT NULL,
  `notes` TEXT NULL,
  `admin_id` BIGINT UNSIGNED NOT NULL,
  `zone_id` BIGINT UNSIGNED NULL,
  `due_date` DATE NOT NULL,
  `due_time` TIME NULL,
  `status` ENUM('pending','done','missed','cancelled') NOT NULL DEFAULT 'pending',
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `support_tickets` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ticket_no` VARCHAR(20) NOT NULL,
  `subject` VARCHAR(200) NOT NULL,
  `contact_name` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `email` VARCHAR(100) NULL,
  `zone_id` BIGINT UNSIGNED NULL,
  `assigned_admin_id` BIGINT UNSIGNED NULL,
  `query_id` BIGINT UNSIGNED NULL,
  `channel` ENUM('phone','email','whatsapp','website','walk_in') NOT NULL DEFAULT 'phone',
  `priority` ENUM('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
  `status` ENUM('open','in_progress','resolved','closed','on_hold') NOT NULL DEFAULT 'open',
  `description` TEXT NULL,
  `resolution` TEXT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `support_tickets_ticket_no_unique` (`ticket_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `support_ticket_replies` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ticket_id` BIGINT UNSIGNED NOT NULL,
  `admin_id` BIGINT UNSIGNED NOT NULL,
  `message` TEXT NOT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
