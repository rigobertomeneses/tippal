-- SQL script to add Stripe Connect support to the database
-- Run this script to create necessary tables and columns

-- Add Stripe columns to usuario table if they don't exist
ALTER TABLE `usuario` 
ADD COLUMN IF NOT EXISTS `stripe_customer_id` VARCHAR(255) DEFAULT NULL COMMENT 'Stripe Customer ID',
ADD COLUMN IF NOT EXISTS `stripe_account_id` VARCHAR(255) DEFAULT NULL COMMENT 'Stripe Connect Account ID for receiving payments',
ADD INDEX IF NOT EXISTS `idx_stripe_customer` (`stripe_customer_id`),
ADD INDEX IF NOT EXISTS `idx_stripe_account` (`stripe_account_id`);

-- Add Stripe columns to compania table if they don't exist
ALTER TABLE `compania` 
ADD COLUMN IF NOT EXISTS `stripe_secret_key` VARCHAR(255) DEFAULT NULL COMMENT 'Stripe Secret Key',
ADD COLUMN IF NOT EXISTS `stripe_publishable_key` VARCHAR(255) DEFAULT NULL COMMENT 'Stripe Publishable Key',
ADD COLUMN IF NOT EXISTS `stripe_connect_enabled` TINYINT(1) DEFAULT 0 COMMENT 'Enable Stripe Connect for this company',
ADD COLUMN IF NOT EXISTS `stripe_webhook_secret` VARCHAR(255) DEFAULT NULL COMMENT 'Stripe Webhook Secret';

-- Create stripe_transactions table to log all Stripe transactions
CREATE TABLE IF NOT EXISTS `stripe_transactions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `compania_id` INT(11) NOT NULL,
  `usuario_id` INT(11) NOT NULL COMMENT 'User who initiated the payment',
  `destination_usuario_id` INT(11) DEFAULT NULL COMMENT 'User receiving the payment (for Connect)',
  `payment_intent_id` VARCHAR(255) NOT NULL,
  `payment_method_id` VARCHAR(255) DEFAULT NULL,
  `amount` INT(11) NOT NULL COMMENT 'Amount in cents',
  `currency` VARCHAR(3) DEFAULT 'usd',
  `application_fee` INT(11) DEFAULT 0 COMMENT 'Platform fee in cents',
  `status` VARCHAR(50) NOT NULL DEFAULT 'pending' COMMENT 'pending, processing, succeeded, failed, canceled',
  `error_message` TEXT DEFAULT NULL,
  `metadata` JSON DEFAULT NULL COMMENT 'Additional metadata',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_payment_intent` (`payment_intent_id`),
  INDEX `idx_usuario` (`usuario_id`),
  INDEX `idx_destination` (`destination_usuario_id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Stripe payment transactions log';

-- Create stripe_connect_accounts table to manage Connect accounts
CREATE TABLE IF NOT EXISTS `stripe_connect_accounts` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` INT(11) NOT NULL,
  `compania_id` INT(11) NOT NULL,
  `stripe_account_id` VARCHAR(255) NOT NULL,
  `account_type` VARCHAR(50) DEFAULT 'express' COMMENT 'express, standard, custom',
  `charges_enabled` TINYINT(1) DEFAULT 0,
  `payouts_enabled` TINYINT(1) DEFAULT 0,
  `details_submitted` TINYINT(1) DEFAULT 0,
  `business_type` VARCHAR(50) DEFAULT NULL,
  `country` VARCHAR(2) DEFAULT 'US',
  `default_currency` VARCHAR(3) DEFAULT 'usd',
  `email` VARCHAR(255) DEFAULT NULL,
  `metadata` JSON DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_stripe_account` (`stripe_account_id`),
  INDEX `idx_usuario_connect` (`usuario_id`),
  INDEX `idx_compania_connect` (`compania_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Stripe Connect account details';

-- Create stripe_payouts table to track payouts
CREATE TABLE IF NOT EXISTS `stripe_payouts` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` INT(11) NOT NULL,
  `compania_id` INT(11) NOT NULL,
  `stripe_payout_id` VARCHAR(255) NOT NULL,
  `stripe_account_id` VARCHAR(255) NOT NULL,
  `amount` INT(11) NOT NULL COMMENT 'Amount in cents',
  `currency` VARCHAR(3) DEFAULT 'usd',
  `arrival_date` DATE DEFAULT NULL,
  `method` VARCHAR(50) DEFAULT 'standard' COMMENT 'standard, instant',
  `status` VARCHAR(50) NOT NULL COMMENT 'pending, paid, failed, canceled',
  `failure_code` VARCHAR(100) DEFAULT NULL,
  `failure_message` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_payout` (`stripe_payout_id`),
  INDEX `idx_usuario_payout` (`usuario_id`),
  INDEX `idx_status_payout` (`status`),
  INDEX `idx_arrival` (`arrival_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Stripe payouts tracking';

-- Create stripe_webhooks table to log webhook events
CREATE TABLE IF NOT EXISTS `stripe_webhooks` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `stripe_event_id` VARCHAR(255) NOT NULL,
  `event_type` VARCHAR(100) NOT NULL,
  `api_version` VARCHAR(50) DEFAULT NULL,
  `data` JSON NOT NULL,
  `processed` TINYINT(1) DEFAULT 0,
  `error_message` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `processed_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_event` (`stripe_event_id`),
  INDEX `idx_event_type` (`event_type`),
  INDEX `idx_processed` (`processed`),
  INDEX `idx_created_webhook` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Stripe webhook events log';

-- Add sample Stripe configuration for testing (UPDATE with your actual keys)
-- UPDATE compania SET 
--   stripe_secret_key = 'sk_test_YOUR_KEY',
--   stripe_publishable_key = 'pk_test_YOUR_KEY',
--   stripe_connect_enabled = 1
-- WHERE id = 468; -- CanjeAR company ID