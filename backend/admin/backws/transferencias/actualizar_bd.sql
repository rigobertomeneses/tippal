-- Script para actualizar la base de datos con campos necesarios para transferencias
-- Ejecutar solo si los campos no existen

-- Verificar y agregar campo CBU a la tabla usuario si no existe
SET @sql = 'ALTER TABLE `usuario` ADD COLUMN `usuario_cbu` VARCHAR(22) NULL DEFAULT NULL AFTER `usuario_alias`';
SET @sql = IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
               WHERE TABLE_SCHEMA = DATABASE() 
               AND TABLE_NAME = 'usuario' 
               AND COLUMN_NAME = 'usuario_cbu') = 0, @sql, 'SELECT "Campo usuario_cbu ya existe"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verificar y agregar campo verificado a la tabla usuario si no existe
SET @sql = 'ALTER TABLE `usuario` ADD COLUMN `usuario_verificado` INT(1) NULL DEFAULT 0 AFTER `usuario_cbu`';
SET @sql = IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
               WHERE TABLE_SCHEMA = DATABASE() 
               AND TABLE_NAME = 'usuario' 
               AND COLUMN_NAME = 'usuario_verificado') = 0, @sql, 'SELECT "Campo usuario_verificado ya existe"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Agregar índices para mejorar búsquedas (solo si no existen)
SET @sql = 'ALTER TABLE `usuario` ADD INDEX `idx_usuario_alias` (`usuario_alias`)';
SET @sql = IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
               WHERE TABLE_SCHEMA = DATABASE() 
               AND TABLE_NAME = 'usuario' 
               AND INDEX_NAME = 'idx_usuario_alias') = 0, @sql, 'SELECT "Índice idx_usuario_alias ya existe"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = 'ALTER TABLE `usuario` ADD INDEX `idx_usuario_cbu` (`usuario_cbu`)';
SET @sql = IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
               WHERE TABLE_SCHEMA = DATABASE() 
               AND TABLE_NAME = 'usuario' 
               AND INDEX_NAME = 'idx_usuario_cbu') = 0, @sql, 'SELECT "Índice idx_usuario_cbu ya existe"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = 'ALTER TABLE `usuario` ADD INDEX `idx_usuario_email` (`usuario_email`)';
SET @sql = IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
               WHERE TABLE_SCHEMA = DATABASE() 
               AND TABLE_NAME = 'usuario' 
               AND INDEX_NAME = 'idx_usuario_email') = 0, @sql, 'SELECT "Índice idx_usuario_email ya existe"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Agregar índices a la tabla movimiento para mejorar consultas de transferencias
ALTER TABLE `movimiento` ADD INDEX `idx_mov_usuario_origen` (`usuario_id`);
ALTER TABLE `movimiento` ADD INDEX `idx_mov_usuario_destino` (`usuario_iddestino`);
ALTER TABLE `movimiento` ADD INDEX `idx_mov_fecha` (`mov_fecha`);
ALTER TABLE `movimiento` ADD INDEX `idx_mov_compania` (`compania_id`);

-- Crear tabla de notificaciones si no existe
CREATE TABLE IF NOT EXISTS `notificacion` (
  `notificacion_id` int(11) NOT NULL AUTO_INCREMENT,
  `notificacion_titulo` varchar(200) DEFAULT NULL,
  `notificacion_mensaje` text DEFAULT NULL,
  `notificacion_tipo` varchar(50) DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `notificacion_fecha` datetime DEFAULT NULL,
  `notificacion_leida` int(1) DEFAULT 0,
  `notificacion_activo` int(1) DEFAULT 1,
  `notificacion_eliminado` int(1) DEFAULT 0,
  `compania_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`notificacion_id`),
  KEY `idx_notif_usuario` (`usuario_id`),
  KEY `idx_notif_fecha` (`notificacion_fecha`),
  KEY `idx_notif_leida` (`notificacion_leida`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish_ci;

-- Insertar tipos de movimiento para transferencias si no existen
INSERT IGNORE INTO `l_tipomov` (`l_tipomov_id`, `l_tipomov_nombre`, `l_tipomov_tipo`) VALUES
(1, 'Transferencia recibida', 'entrada'),
(2, 'Transferencia enviada', 'salida'),
(3, 'Depósito', 'entrada'),
(4, 'Retiro', 'salida');