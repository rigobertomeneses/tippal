<?php
/**
 * Stripe Helper Functions
 * Funciones compartidas para todos los endpoints de Stripe
 */

/**
 * Fix email format for Stripe
 * Stripe requiere un formato de email válido, esta función corrige emails inválidos
 *
 * @param string $email Email a validar/corregir
 * @return string Email en formato válido
 */
function fixEmailForStripe($email) {
    if (empty($email)) {
        return 'user@gmail.com';
    }

    // Si no contiene @, agregar @gmail.com
    if (strpos($email, '@') === false) {
        error_log("Stripe Helper: Email sin @: '$email' -> '{$email}@gmail.com'");
        return $email . '@gmail.com';
    }

    // Si es solo un nombre sin dominio válido
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $cleanEmail = str_replace('@', '', $email);
        error_log("Stripe Helper: Email inválido: '$email' -> '{$cleanEmail}@gmail.com'");
        return $cleanEmail . '@gmail.com';
    }

    return $email;
}

/**
 * Get corrected email from user data
 * Obtiene el email del usuario y lo corrige si es necesario
 *
 * @param array $user_data Array con datos del usuario
 * @return string Email corregido
 */
function getUserEmailForStripe($user_data) {
    $email = isset($user_data['usuario_email']) ? $user_data['usuario_email'] : '';
    return fixEmailForStripe($email);
}

/**
 * Log debug info for Stripe operations
 *
 * @param string $operation Operación que se está realizando
 * @param array $data Datos a loguear
 */
function stripeDebugLog($operation, $data) {
    if (defined('STRIPE_DEBUG') && STRIPE_DEBUG) {
        error_log("=== STRIPE DEBUG: $operation ===");
        error_log(json_encode($data, JSON_PRETTY_PRINT));
    }
}
?>
