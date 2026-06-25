CREATE TABLE IF NOT EXISTS `kipos_sync_runs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `action_key` VARCHAR(120) NOT NULL,
  `action_label` VARCHAR(191) NOT NULL,
  `status` VARCHAR(24) NOT NULL DEFAULT 'started',
  `summary` TEXT NULL,
  `stats` JSON NULL,
  `error_message` LONGTEXT NULL,
  `started_at` TIMESTAMP NULL DEFAULT NULL,
  `finished_at` TIMESTAMP NULL DEFAULT NULL,
  `initiated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `kipos_sync_runs_action_key_index` (`action_key`),
  KEY `kipos_sync_runs_status_index` (`status`),
  KEY `kipos_sync_runs_started_at_index` (`started_at`),
  KEY `kipos_sync_runs_finished_at_index` (`finished_at`),
  KEY `kipos_sync_runs_initiated_by_foreign` (`initiated_by`),
  KEY `kipos_sync_runs_action_key_id_index` (`action_key`, `id`),
  CONSTRAINT `kipos_sync_runs_initiated_by_foreign`
    FOREIGN KEY (`initiated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `system_settings` (`key`, `value`, `created_at`, `updated_at`) VALUES
  ('catalog_use_kipos_api', 'true', NOW(), NOW()),
  ('kipos_api_enabled', 'false', NOW(), NOW()),
  ('kipos_api_base_uri', '\"http://balidd.dyndns.org:8080/kipos.web.api/?route=\"', NOW(), NOW()),
  ('kipos_api_image_base_uri', '\"http://balidd.dyndns.org:8080/slike/\"', NOW(), NOW()),
  ('kipos_api_query_suffix', '\"webshop=1\"', NOW(), NOW()),
  ('kipos_api_timeout_seconds', '30', NOW(), NOW()),
  ('kipos_api_verify_tls', 'true', NOW(), NOW()),
  ('kipos_sync_default_locale', '\"hr\"', NOW(), NOW()),
  ('kipos_sync_import_category_id', 'null', NOW(), NOW()),
  ('kipos_sync_size_option_id', 'null', NOW(), NOW()),
  ('kipos_sync_price_field', '\"CIJENA_MPC\"', NOW(), NOW()),
  ('kipos_sync_action_price_field', '\"AKCIJSKA_CIJENA\"', NOW(), NOW()),
  ('kipos_sync_stock_warehouse_ids', '\"200\"', NOW(), NOW()),
  ('kipos_sync_quantity_overrides', '\"\"', NOW(), NOW()),
  ('kipos_order_prefix', '\"KHR\"', NOW(), NOW()),
  ('kipos_order_valuta', '\"978\"', NOW(), NOW()),
  ('kipos_order_customer_cms_id', '\"1\"', NOW(), NOW()),
  ('kipos_order_shipping_item_code', '\"\"', NOW(), NOW()),
  ('kipos_order_payment_fee_item_code', '\"\"', NOW(), NOW()),
  ('kipos_order_private_at_company_id', '2', NOW(), NOW()),
  ('kipos_order_private_de_company_id', '3', NOW(), NOW())
ON DUPLICATE KEY UPDATE
  `value` = VALUES(`value`),
  `updated_at` = VALUES(`updated_at`);
