<?php

ini_set ("display_errors","0");

// Archivo: create-payment-intent.php

//include_once '../vendor/autoload.php';

include_once '../vendor/autoload.php'; // Composer autoload

ini_set ("display_errors","0");


\Stripe\Stripe::setApiKey('sk_test_YOUR_SECRET_KEY_HERE'); // Tu clave secreta de Stripe

// Permitir solicitudes CORS si estás probando desde local
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Obtener datos JSON enviados desde React Native
$input = json_decode(file_get_contents("php://input"), true);

$amount = $input['amount'] ?? 1000; // en centavos (ej: 1000 = $10)
$currency = $input['currency'] ?? 'usd';

try {
    $intent = \Stripe\PaymentIntent::create([
        'amount' => $amount,
        'currency' => $currency,
        'payment_method_types' => ['card'],
    ]);

    echo json_encode(['clientSecret' => $intent->client_secret]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

?>