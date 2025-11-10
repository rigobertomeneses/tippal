-- =====================================================
-- STRIPE INTEGRATION - COMPLETE DATABASE SETUP
-- =====================================================
-- Author: Claude
-- Date: 2025-08-16
-- Description: Complete SQL setup for Stripe payment integration
-- Company: 467 (TipPal)
-- =====================================================

-- -----------------------------------------------------
-- 1. Table: usuario_payment_methods
-- Description: Stores saved payment methods (cards) for users
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `usuario_payment_methods` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `payment_method_id` varchar(255) NOT NULL COMMENT 'Stripe Payment Method ID',
  `brand` varchar(50) DEFAULT NULL COMMENT 'Card brand (visa, mastercard, etc)',
  `last4` varchar(4) DEFAULT NULL COMMENT 'Last 4 digits of card',
  `exp_month` int(2) DEFAULT NULL COMMENT 'Expiry month',
  `exp_year` int(4) DEFAULT NULL COMMENT 'Expiry year',
  `fingerprint` varchar(255) DEFAULT NULL COMMENT 'Unique card identifier from Stripe',
  `cardholder_name` varchar(255) DEFAULT NULL COMMENT 'Name on card',
  `is_default` tinyint(1) DEFAULT 0 COMMENT 'Is this the default payment method',
  `activo` tinyint(1) DEFAULT 1,
  `eliminado` tinyint(1) DEFAULT 0,
  `compania_id` int(11) NOT NULL,
  `fecha_creada` datetime DEFAULT NULL,
  `fecha_actualizada` datetime DEFAULT NULL,
  `fecha_eliminada` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_usuario_id` (`usuario_id`),
  KEY `idx_payment_method_id` (`payment_method_id`),
  KEY `idx_fingerprint` (`fingerprint`),
  KEY `idx_compania_id` (`compania_id`),
  KEY `idx_usuario_compania` (`usuario_id`, `compania_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- 2. Table: stripe_transactions
-- Description: Logs all Stripe transactions
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `stripe_transactions` (
  `transaction_id` int(11) NOT NULL AUTO_INCREMENT,
  `compania_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `payment_intent_id` varchar(255) DEFAULT NULL COMMENT 'Stripe Payment Intent ID',
  `charge_id` varchar(255) DEFAULT NULL COMMENT 'Stripe Charge ID',
  `amount` decimal(10,2) NOT NULL COMMENT 'Amount in dollars',
  `currency` varchar(3) DEFAULT 'usd',
  `status` varchar(50) DEFAULT NULL COMMENT 'Transaction status',
  `type` varchar(50) DEFAULT NULL COMMENT 'payment, refund, transfer, tip',
  `description` text DEFAULT NULL,
  `metadata` text DEFAULT NULL COMMENT 'JSON metadata',
  `destination_account_id` varchar(255) DEFAULT NULL COMMENT 'For Stripe Connect transfers',
  `application_fee_amount` decimal(10,2) DEFAULT NULL COMMENT 'Platform fee',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`transaction_id`),
  KEY `idx_payment_intent` (`payment_intent_id`),
  KEY `idx_charge_id` (`charge_id`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_compania` (`compania_id`),
  KEY `idx_status` (`status`),
  KEY `idx_type` (`type`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- 3. Table: stripe_setup_intents (Optional - for tracking)
-- Description: Logs setup intents for saving cards
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `stripe_setup_intents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setup_intent_id` varchar(255) NOT NULL COMMENT 'Stripe Setup Intent ID',
  `usuario_id` int(11) NOT NULL,
  `status` varchar(50) DEFAULT NULL COMMENT 'Setup status from Stripe',
  `payment_method_id` varchar(255) DEFAULT NULL COMMENT 'Resulting payment method ID',
  `metadata` text DEFAULT NULL COMMENT 'JSON metadata',
  `error_message` text DEFAULT NULL COMMENT 'Error if setup failed',
  `compania_id` int(11) NOT NULL,
  `fecha_creada` datetime DEFAULT NULL,
  `fecha_actualizada` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_setup_intent_id` (`setup_intent_id`),
  KEY `idx_usuario_id` (`usuario_id`),
  KEY `idx_status` (`status`),
  KEY `idx_compania_id` (`compania_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- 4. Table: stripe_payment_intents (Optional - for tracking)
-- Description: Logs all payment intents created
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `stripe_payment_intents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `payment_intent_id` varchar(255) NOT NULL COMMENT 'Stripe Payment Intent ID',
  `usuario_id` int(11) NOT NULL,
  `recipient_usuario_id` int(11) DEFAULT NULL COMMENT 'Recipient user for tips',
  `amount` decimal(10,2) NOT NULL COMMENT 'Amount in dollars',
  `currency` varchar(3) DEFAULT 'usd',
  `status` varchar(50) DEFAULT NULL COMMENT 'Payment status from Stripe',
  `payment_method_id` varchar(255) DEFAULT NULL COMMENT 'Payment method used',
  `metadata` text DEFAULT NULL COMMENT 'JSON metadata',
  `error_message` text DEFAULT NULL COMMENT 'Error if payment failed',
  `compania_id` int(11) NOT NULL,
  `fecha_creada` datetime DEFAULT NULL,
  `fecha_actualizada` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_payment_intent_id` (`payment_intent_id`),
  KEY `idx_usuario_id` (`usuario_id`),
  KEY `idx_recipient_usuario_id` (`recipient_usuario_id`),
  KEY `idx_status` (`status`),
  KEY `idx_compania_id` (`compania_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- 5. Add Stripe columns to usuario table
-- -----------------------------------------------------
ALTER TABLE `usuario` 
ADD COLUMN IF NOT EXISTS `stripe_customer_id` varchar(255) DEFAULT NULL COMMENT 'Stripe Customer ID' AFTER `usuario_email`,
ADD COLUMN IF NOT EXISTS `stripe_account_id` varchar(255) DEFAULT NULL COMMENT 'Stripe Connect Account ID (for receiving payments)' AFTER `stripe_customer_id`,
ADD INDEX IF NOT EXISTS `idx_stripe_customer_id` (`stripe_customer_id`),
ADD INDEX IF NOT EXISTS `idx_stripe_account_id` (`stripe_account_id`);

-- -----------------------------------------------------
-- 6. Add Stripe configuration columns to compania table
-- -----------------------------------------------------
ALTER TABLE `compania` 
ADD COLUMN IF NOT EXISTS `stripe_publishable_key` varchar(255) DEFAULT NULL COMMENT 'Stripe Publishable Key',
ADD COLUMN IF NOT EXISTS `stripe_secret_key` varchar(255) DEFAULT NULL COMMENT 'Stripe Secret Key',
ADD COLUMN IF NOT EXISTS `stripe_webhook_secret` varchar(255) DEFAULT NULL COMMENT 'Stripe Webhook Secret',
ADD COLUMN IF NOT EXISTS `stripe_connect_enabled` tinyint(1) DEFAULT 0 COMMENT 'Is Stripe Connect enabled',
ADD COLUMN IF NOT EXISTS `stripe_mode` enum('test','live') DEFAULT 'test' COMMENT 'Stripe Mode',
ADD COLUMN IF NOT EXISTS `stripe_enabled` tinyint(1) DEFAULT 0 COMMENT 'Is Stripe enabled for this company',
ADD COLUMN IF NOT EXISTS `stripe_application_fee_percent` decimal(5,2) DEFAULT 0 COMMENT 'Platform fee percentage';

-- -----------------------------------------------------
-- 7. Table: tips_transactions (For tip specific transactions)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tips_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sender_usuario_id` int(11) NOT NULL COMMENT 'Who sent the tip',
  `recipient_usuario_id` int(11) NOT NULL COMMENT 'Who received the tip',
  `amount` decimal(10,2) NOT NULL COMMENT 'Tip amount in dollars',
  `currency` varchar(3) DEFAULT 'usd',
  `stripe_payment_intent_id` varchar(255) DEFAULT NULL,
  `stripe_transfer_id` varchar(255) DEFAULT NULL COMMENT 'Transfer ID if using Stripe Connect',
  `platform_fee` decimal(10,2) DEFAULT 0 COMMENT 'Platform fee amount',
  `net_amount` decimal(10,2) NOT NULL COMMENT 'Amount after fees',
  `status` enum('pending','processing','completed','failed','refunded') DEFAULT 'pending',
  `payment_method_id` varchar(255) DEFAULT NULL,
  `message` text DEFAULT NULL COMMENT 'Optional tip message',
  `metadata` text DEFAULT NULL COMMENT 'JSON metadata',
  `compania_id` int(11) NOT NULL,
  `fecha_creada` datetime DEFAULT NULL,
  `fecha_actualizada` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_sender` (`sender_usuario_id`),
  KEY `idx_recipient` (`recipient_usuario_id`),
  KEY `idx_payment_intent` (`stripe_payment_intent_id`),
  KEY `idx_transfer` (`stripe_transfer_id`),
  KEY `idx_status` (`status`),
  KEY `idx_compania` (`compania_id`),
  KEY `idx_fecha` (`fecha_creada`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- VERIFICACIÓN DE TABLAS EXISTENTES
-- =====================================================
-- Ejecuta estas consultas para verificar qué existe:

-- Verificar si existe la tabla usuario_payment_methods:
-- SHOW TABLES LIKE 'usuario_payment_methods';

-- Verificar si existe la tabla stripe_transactions:
-- SHOW TABLES LIKE 'stripe_transactions';

-- Verificar columnas de Stripe en usuario:
-- SHOW COLUMNS FROM usuario LIKE 'stripe%';

-- Verificar columnas de Stripe en compania:
-- SHOW COLUMNS FROM compania LIKE 'stripe%';

-- =====================================================
-- CONFIGURACIÓN INICIAL PARA COMPAÑÍA 467 (TipPal)
-- =====================================================
-- IMPORTANTE: Reemplaza las claves con las reales de Stripe

-- Configurar claves de prueba para la compañía 467:
/*
UPDATE compania 
SET stripe_publishable_key = 'pk_test_TU_CLAVE_PUBLICA_AQUI',
    stripe_secret_key = 'sk_test_TU_CLAVE_SECRETA_AQUI',
    stripe_mode = 'test',
    stripe_enabled = 1,
    stripe_connect_enabled = 0,
    stripe_application_fee_percent = 2.5
WHERE compania_id = 467;
*/

-- =====================================================
-- NOTAS IMPORTANTES:
-- =====================================================
-- 1. Ejecuta este script en tu base de datos MySQL
-- 2. Las tablas se crearán solo si no existen (IF NOT EXISTS)
-- 3. Las columnas se agregarán solo si no existen (IF NOT EXISTS)
-- 4. Después de ejecutar, actualiza las claves de Stripe en la tabla compania
-- 5. Para producción, cambia stripe_mode a 'live' y usa claves de producción
-- 6. La tabla tips_transactions es específica para el manejo de propinas
-- 7. Todas las cantidades se almacenan en dólares (decimal 10,2)
-- =====================================================