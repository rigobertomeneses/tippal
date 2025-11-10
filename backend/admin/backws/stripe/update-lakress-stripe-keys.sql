-- =====================================================
-- CONFIGURACIÓN DE STRIPE PARA LA KRESS (COMPAÑÍA 473)
-- =====================================================
-- Fecha: 2025-10-08
-- Descripción: Actualización de claves de Stripe para La Kress
-- Modo: TEST (Claves de prueba)
-- =====================================================

-- Verificar que la tabla compania tiene las columnas necesarias
-- Si no existen, ejecutar primero el script stripe_complete_setup.sql

-- Actualizar claves de Stripe para La Kress (compania_id = 473)
UPDATE compania
SET
    stripe_publishable_key = 'pk_test_51Ri0x8K0J7tynvrGSTTmpvlKdwKN4hbjQBb2mgGDB37CIClsZpfVS3lKQbwhpa2vfXh0Vu0HRK6VK2qlb1OgpbHM00WfYbsKfU',
    stripe_secret_key = 'sk_test_51Ri0x8K0J7tynvrGvFBUQ6WinfV9psoFq3H3E2uBT6bQMVeIdmc96n5L6d3xDVIjvO70xIAxLf0eM7kKXmTVWq6700Nt5BgsuZ',
    stripe_mode = 'test',
    stripe_enabled = 1,
    stripe_connect_enabled = 0,
    stripe_application_fee_percent = 0
WHERE compania_id = 473;

-- Verificar que la actualización fue exitosa
SELECT
    compania_id,
    compania_nombre,
    stripe_enabled,
    stripe_mode,
    LEFT(stripe_publishable_key, 20) as publishable_key_preview,
    LEFT(stripe_secret_key, 20) as secret_key_preview,
    stripe_connect_enabled,
    stripe_application_fee_percent
FROM compania
WHERE compania_id = 473;

-- =====================================================
-- NOTAS IMPORTANTES:
-- =====================================================
-- 1. Estas son claves de PRUEBA (test mode)
-- 2. Para producción, cambiar stripe_mode a 'live' y usar claves pk_live_ y sk_live_
-- 3. El sistema obtiene las claves dinámicamente desde la tabla compania
-- 4. No es necesario modificar archivos PHP para cambiar las claves
-- 5. stripe_application_fee_percent = 0 significa sin comisión de plataforma
-- 6. stripe_connect_enabled = 0 significa que no se usa Stripe Connect
-- =====================================================

-- Para habilitar Stripe Connect en el futuro:
-- UPDATE compania SET stripe_connect_enabled = 1 WHERE compania_id = 473;

-- Para cambiar a modo producción (SOLO CUANDO TENGAS CLAVES LIVE):
-- UPDATE compania
-- SET
--     stripe_publishable_key = 'pk_live_TU_CLAVE_LIVE_AQUI',
--     stripe_secret_key = 'sk_live_TU_CLAVE_LIVE_AQUI',
--     stripe_mode = 'live'
-- WHERE compania_id = 473;
