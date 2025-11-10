<?php
/**
 * Stripe Configuration
 * 
 * IMPORTANT: You need to get your own Stripe API keys from https://dashboard.stripe.com/test/apikeys
 * 
 * Steps to get your keys:
 * 1. Go to https://dashboard.stripe.com/register to create a Stripe account (if you don't have one)
 * 2. Once logged in, go to https://dashboard.stripe.com/test/apikeys
 * 3. Copy your test keys and replace them below
 * 
 * The keys below are placeholder examples and won't work.
 * You MUST replace them with your actual Stripe test keys.
 */

// Test keys - REPLACE THESE WITH YOUR ACTUAL STRIPE TEST KEYS
// Get them from: https://dashboard.stripe.com/test/apikeys
define('STRIPE_PUBLISHABLE_KEY', 'pk_test_YOUR_PUBLISHABLE_KEY_HERE');
define('STRIPE_SECRET_KEY', 'sk_test_YOUR_SECRET_KEY_HERE');

// Live keys (use these for production - replace with your actual live keys)
// define('STRIPE_PUBLISHABLE_KEY', 'pk_live_YOUR_LIVE_PUBLISHABLE_KEY');
// define('STRIPE_SECRET_KEY', 'sk_live_YOUR_LIVE_SECRET_KEY');

// Optional: Webhook secret for validating webhook events
define('STRIPE_WEBHOOK_SECRET', '');

?>