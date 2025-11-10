<?php
/**
 * Stripe Webhook Handler
 * Processes Stripe webhook events for payment confirmations and updates
 */

// Include necessary files
include_once '../../lib/config.php';
include_once '../../lib/funciones.php';
include_once '../../vendor/autoload.php'; // Stripe PHP SDK

// Get the webhook payload
$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

// Initialize response
http_response_code(200);

try {
    // Get webhook secret from database or environment
    // For now, using a placeholder - should be stored per company
    $endpoint_secret = 'whsec_YOUR_WEBHOOK_SECRET'; // Replace with actual webhook secret
    
    // Verify webhook signature
    try {
        $event = \Stripe\Webhook::constructEvent(
            $payload, $sig_header, $endpoint_secret
        );
    } catch(\UnexpectedValueException $e) {
        // Invalid payload
        http_response_code(400);
        error_log('Webhook Error: Invalid payload');
        exit();
    } catch(\Stripe\Exception\SignatureVerificationException $e) {
        // Invalid signature
        http_response_code(400);
        error_log('Webhook Error: Invalid signature');
        exit();
    }
    
    // Log the webhook event
    $event_json = json_encode($event);
    $sql_log = "INSERT INTO stripe_webhooks (
        stripe_event_id,
        event_type,
        api_version,
        data,
        created_at
    ) VALUES (
        '{$event->id}',
        '{$event->type}',
        '{$event->api_version}',
        '$event_json',
        NOW()
    )";
    mysql_query($sql_log);
    
    // Handle the event
    switch ($event->type) {
        case 'payment_intent.succeeded':
            $payment_intent = $event->data->object;
            handlePaymentIntentSucceeded($payment_intent);
            break;
            
        case 'payment_intent.payment_failed':
            $payment_intent = $event->data->object;
            handlePaymentIntentFailed($payment_intent);
            break;
            
        case 'charge.succeeded':
            $charge = $event->data->object;
            handleChargeSucceeded($charge);
            break;
            
        case 'charge.failed':
            $charge = $event->data->object;
            handleChargeFailed($charge);
            break;
            
        case 'transfer.created':
            $transfer = $event->data->object;
            handleTransferCreated($transfer);
            break;
            
        case 'payout.created':
            $payout = $event->data->object;
            handlePayoutCreated($payout);
            break;
            
        case 'payout.paid':
            $payout = $event->data->object;
            handlePayoutPaid($payout);
            break;
            
        case 'payout.failed':
            $payout = $event->data->object;
            handlePayoutFailed($payout);
            break;
            
        case 'account.updated':
            $account = $event->data->object;
            handleAccountUpdated($account);
            break;
            
        default:
            // Unhandled event type
            error_log('Unhandled webhook event type: ' . $event->type);
    }
    
    // Mark webhook as processed
    $sql_update = "UPDATE stripe_webhooks 
                  SET processed = 1, processed_at = NOW() 
                  WHERE stripe_event_id = '{$event->id}'";
    mysql_query($sql_update);
    
    echo json_encode(['status' => 'success']);
    
} catch (Exception $e) {
    error_log('Webhook Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Webhook handler error']);
}

/**
 * Handle successful payment intent
 */
function handlePaymentIntentSucceeded($payment_intent) {
    $payment_intent_id = $payment_intent->id;
    $amount = $payment_intent->amount;
    $currency = $payment_intent->currency;
    $metadata = $payment_intent->metadata;
    
    // Update transaction status
    $sql = "UPDATE stripe_transactions 
            SET status = 'succeeded',
                updated_at = NOW()
            WHERE payment_intent_id = '$payment_intent_id'";
    mysql_query($sql);
    
    // Get transaction details
    $sql_trans = "SELECT * FROM stripe_transactions WHERE payment_intent_id = '$payment_intent_id'";
    $result = mysql_query($sql_trans);
    
    if ($result && mysql_num_rows($result) > 0) {
        $trans = mysql_fetch_assoc($result);
        
        // If it's a Connect payment with destination
        if (!empty($trans['destination_usuario_id'])) {
            $amount_dollars = $amount / 100;
            $fee_dollars = $trans['application_fee'] / 100;
            $net_amount = $amount_dollars - $fee_dollars;
            
            // Update destination user balance
            $sql_balance = "UPDATE usuario 
                           SET balance = balance + $net_amount 
                           WHERE id = '{$trans['destination_usuario_id']}'";
            mysql_query($sql_balance);
            
            // Send notification to destination user
            sendPaymentNotification($trans['destination_usuario_id'], $net_amount, 'received');
        }
        
        // Send notification to sender
        sendPaymentNotification($trans['usuario_id'], $amount / 100, 'sent');
    }
}

/**
 * Handle failed payment intent
 */
function handlePaymentIntentFailed($payment_intent) {
    $payment_intent_id = $payment_intent->id;
    $error_message = $payment_intent->last_payment_error->message ?? 'Unknown error';
    
    // Update transaction status
    $sql = "UPDATE stripe_transactions 
            SET status = 'failed',
                error_message = '$error_message',
                updated_at = NOW()
            WHERE payment_intent_id = '$payment_intent_id'";
    mysql_query($sql);
    
    // Get transaction details and notify user
    $sql_trans = "SELECT usuario_id FROM stripe_transactions WHERE payment_intent_id = '$payment_intent_id'";
    $result = mysql_query($sql_trans);
    
    if ($result && mysql_num_rows($result) > 0) {
        $trans = mysql_fetch_assoc($result);
        sendPaymentNotification($trans['usuario_id'], 0, 'failed', $error_message);
    }
}

/**
 * Handle successful charge
 */
function handleChargeSucceeded($charge) {
    // Log successful charge
    error_log('Charge succeeded: ' . $charge->id);
}

/**
 * Handle failed charge
 */
function handleChargeFailed($charge) {
    // Log failed charge
    error_log('Charge failed: ' . $charge->id . ' - ' . $charge->failure_message);
}

/**
 * Handle transfer created (for Connect)
 */
function handleTransferCreated($transfer) {
    // Log transfer creation
    error_log('Transfer created: ' . $transfer->id . ' to ' . $transfer->destination);
}

/**
 * Handle payout created
 */
function handlePayoutCreated($payout) {
    $stripe_account_id = $payout->destination ?? '';
    
    // Find user by Stripe account
    $sql = "SELECT id FROM usuario WHERE stripe_account_id = '$stripe_account_id'";
    $result = mysql_query($sql);
    
    if ($result && mysql_num_rows($result) > 0) {
        $user = mysql_fetch_assoc($result);
        
        // Log payout
        $sql_insert = "INSERT INTO stripe_payouts (
            usuario_id,
            compania_id,
            stripe_payout_id,
            stripe_account_id,
            amount,
            currency,
            arrival_date,
            method,
            status,
            created_at
        ) VALUES (
            '{$user['id']}',
            '468', -- Default company, should be dynamic
            '{$payout->id}',
            '$stripe_account_id',
            '{$payout->amount}',
            '{$payout->currency}',
            '" . date('Y-m-d', $payout->arrival_date) . "',
            '{$payout->method}',
            'pending',
            NOW()
        )";
        mysql_query($sql_insert);
    }
}

/**
 * Handle payout paid
 */
function handlePayoutPaid($payout) {
    // Update payout status
    $sql = "UPDATE stripe_payouts 
            SET status = 'paid',
                updated_at = NOW()
            WHERE stripe_payout_id = '{$payout->id}'";
    mysql_query($sql);
}

/**
 * Handle payout failed
 */
function handlePayoutFailed($payout) {
    // Update payout status
    $sql = "UPDATE stripe_payouts 
            SET status = 'failed',
                failure_code = '{$payout->failure_code}',
                failure_message = '{$payout->failure_message}',
                updated_at = NOW()
            WHERE stripe_payout_id = '{$payout->id}'";
    mysql_query($sql);
}

/**
 * Handle Connect account updated
 */
function handleAccountUpdated($account) {
    // Update account status
    $sql = "UPDATE stripe_connect_accounts 
            SET charges_enabled = " . ($account->charges_enabled ? 1 : 0) . ",
                payouts_enabled = " . ($account->payouts_enabled ? 1 : 0) . ",
                details_submitted = " . ($account->details_submitted ? 1 : 0) . ",
                updated_at = NOW()
            WHERE stripe_account_id = '{$account->id}'";
    mysql_query($sql);
}

/**
 * Send payment notification to user
 */
function sendPaymentNotification($usuario_id, $amount, $type, $error_message = '') {
    $title = '';
    $message = '';
    
    switch ($type) {
        case 'received':
            $title = 'Pago Recibido';
            $message = "Has recibido un pago de $" . number_format($amount, 2);
            break;
        case 'sent':
            $title = 'Pago Enviado';
            $message = "Tu pago de $" . number_format($amount, 2) . " ha sido procesado";
            break;
        case 'failed':
            $title = 'Pago Fallido';
            $message = "Tu pago no pudo ser procesado: " . $error_message;
            break;
    }
    
    // Insert notification into database
    $sql = "INSERT INTO notificacion (
        compania,
        usuario,
        titulo,
        mensaje,
        tipo,
        fecha,
        leida
    ) VALUES (
        '468', -- Should be dynamic
        '$usuario_id',
        '$title',
        '$message',
        'pago',
        NOW(),
        0
    )";
    mysql_query($sql);
    
    // TODO: Send push notification if user has push token
}

?>