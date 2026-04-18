-- ============================================================
-- Laundry Module — Table Creation Queries
-- Run these one by one on your database
-- ============================================================

-- 1. laundry_items — Item master / price list per store
CREATE TABLE `laundry_items` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `store_id`   BIGINT UNSIGNED NOT NULL,
    `name`       VARCHAR(100)    NOT NULL,
    `category`   ENUM('normal','premium','dry_clean') NOT NULL DEFAULT 'normal',
    `price`      DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
    `unit`       ENUM('piece','kg','pair') NOT NULL DEFAULT 'piece',
    `is_active`  TINYINT(1)      NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP       NULL DEFAULT NULL,
    `updated_at` TIMESTAMP       NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `laundry_items_store_id_index` (`store_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 2. laundry_orders — Walk-in customer orders
CREATE TABLE `laundry_orders` (
    `id`                    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `store_id`              BIGINT UNSIGNED NOT NULL,
    `order_no`              VARCHAR(30)     NOT NULL,
    `store_customer_id`     BIGINT UNSIGNED NULL DEFAULT NULL,
    `customer_name`         VARCHAR(100)    NULL DEFAULT NULL,
    `customer_phone`        VARCHAR(20)     NULL DEFAULT NULL,
    `drop_date`             DATE            NOT NULL,
    `expected_ready_date`   DATE            NULL DEFAULT NULL,
    `pickup_date`           DATE            NULL DEFAULT NULL,
    `status`                ENUM('received','processing','ready','delivered','cancelled') NOT NULL DEFAULT 'received',
    `total_amount`          DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
    `payment_status`        ENUM('pending','paid') NOT NULL DEFAULT 'pending',
    `employee_id`           BIGINT UNSIGNED NULL DEFAULT NULL,
    `receivable_receipt_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `invoice_id`            VARCHAR(50)     NULL DEFAULT NULL,
    `notes`                 TEXT            NULL DEFAULT NULL,
    `created_at`            TIMESTAMP       NULL DEFAULT NULL,
    `updated_at`            TIMESTAMP       NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `laundry_orders_order_no_unique` (`order_no`),
    KEY `laundry_orders_store_id_index`          (`store_id`),
    KEY `laundry_orders_store_customer_id_index` (`store_customer_id`),
    KEY `laundry_orders_status_index`            (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 3. laundry_order_items — Items per walk-in order
CREATE TABLE `laundry_order_items` (
    `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `laundry_order_id` BIGINT UNSIGNED NOT NULL,
    `laundry_item_id`  BIGINT UNSIGNED NULL DEFAULT NULL,
    `item_name`        VARCHAR(100)    NOT NULL,
    `qty`              INT             NOT NULL DEFAULT 1,
    `rate`             DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
    `amount`           DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
    `notes`            VARCHAR(255)    NULL DEFAULT NULL,
    `created_at`       TIMESTAMP       NULL DEFAULT NULL,
    `updated_at`       TIMESTAMP       NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `laundry_order_items_order_id_index` (`laundry_order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 4. laundry_challans — Hotel / B2B batch dispatch
CREATE TABLE `laundry_challans` (
    `id`                   BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `store_id`             BIGINT UNSIGNED NOT NULL,
    `challan_no`           VARCHAR(30)     NOT NULL,
    `store_customer_id`    BIGINT UNSIGNED NOT NULL,
    `outward_date`         DATE            NOT NULL,
    `expected_inward_date` DATE            NULL DEFAULT NULL,
    `inward_date`          DATE            NULL DEFAULT NULL,
    `status`               ENUM('pending','partial','returned') NOT NULL DEFAULT 'pending',
    `invoice_id`           VARCHAR(50)     NULL DEFAULT NULL,
    `notes`                TEXT            NULL DEFAULT NULL,
    `created_at`           TIMESTAMP       NULL DEFAULT NULL,
    `updated_at`           TIMESTAMP       NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `laundry_challans_challan_no_unique` (`challan_no`),
    KEY `laundry_challans_store_id_index`          (`store_id`),
    KEY `laundry_challans_store_customer_id_index` (`store_customer_id`),
    KEY `laundry_challans_status_index`            (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 5. laundry_challan_items — Per-item tracking with balance carry
CREATE TABLE `laundry_challan_items` (
    `id`                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `laundry_challan_id`  BIGINT UNSIGNED NOT NULL,
    `laundry_item_id`     BIGINT UNSIGNED NULL DEFAULT NULL,
    `item_name`           VARCHAR(100)    NOT NULL,
    `previous_balance`    INT             NOT NULL DEFAULT 0,
    `sent_qty`            INT             NOT NULL DEFAULT 0,
    `total`               INT             NOT NULL DEFAULT 0,
    `received_qty`        INT             NOT NULL DEFAULT 0,
    `damaged_qty`         INT             NOT NULL DEFAULT 0,
    `balance`             INT             NOT NULL DEFAULT 0,
    `remarks`             VARCHAR(255)    NULL DEFAULT NULL,
    `created_at`          TIMESTAMP       NULL DEFAULT NULL,
    `updated_at`          TIMESTAMP       NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `laundry_challan_items_challan_id_index` (`laundry_challan_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
