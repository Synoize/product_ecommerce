<?php
/**
 * Razorpay Configuration
 * Payment Gateway Integration
 */

// Razorpay Credentials - Replace with your actual credentials
// Test Mode Credentials (provided by user)
define('RAZORPAY_KEY_ID', 'rzp_test_WZSJ554e7F8dDX');
define('RAZORPAY_KEY_SECRET', 's70J331w9UC32nTO80DIjJoX');
define('RAZORPAY_CURRENCY', 'INR');

// Razorpay API Endpoint
define('RAZORPAY_API_URL', 'https://api.razorpay.com/v1');

/**
 * Initialize Razorpay API Client
 * Uses cURL for API requests
 */
function razorpayApiRequest($endpoint, $method = 'POST', $data = null) {
    $url = RAZORPAY_API_URL . $endpoint;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_USERPWD, RAZORPAY_KEY_ID . ':' . RAZORPAY_KEY_SECRET);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    if (strtoupper($method) === 'GET') {
        curl_setopt($ch, CURLOPT_HTTPGET, true);
    } elseif ($data) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    }
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new Exception('Razorpay API Error: ' . $error);
    }
    
    curl_close($ch);
    
    $result = json_decode($response, true);
    
    if ($httpCode !== 200 && $httpCode !== 201) {
        $errorMsg = isset($result['error']['description']) ? $result['error']['description'] : 'Unknown error';
        throw new Exception('Razorpay API Error: ' . $errorMsg);
    }
    
    return $result;
}

/**
 * Create Razorpay Order
 */
function createRazorpayOrder($amount, $receipt = null) {
    // Amount must be in paise (multiply by 100)
    $amountInPaise = $amount * 100;
    
    $data = [
        'amount' => $amountInPaise,
        'currency' => RAZORPAY_CURRENCY,
        'receipt' => $receipt ?: 'order_' . time(),
        'payment_capture' => 1 // Auto capture
    ];
    
    return razorpayApiRequest('/orders', 'POST', $data);
}

/**
 * Verify Razorpay Payment Signature
 */
function verifyRazorpaySignature($orderId, $paymentId, $signature) {
    $payload = $orderId . '|' . $paymentId;
    $expectedSignature = hash_hmac('sha256', $payload, RAZORPAY_KEY_SECRET);
    
    return hash_equals($expectedSignature, $signature);
}

/**
 * Fetch Payment Details
 */
function fetchRazorpayPayment($paymentId) {
    return razorpayApiRequest('/payments/' . rawurlencode($paymentId), 'GET');
}

/**
 * Create Razorpay Refund
 * Amount is in rupees and is converted to paise.
 */
function createRazorpayRefund($paymentId, $amount, array $notes = [], $receipt = null)
{
    $payload = [
        'amount' => (int)round(((float)$amount) * 100),
        'speed' => 'normal',
    ];

    if ($receipt) {
        $payload['receipt'] = $receipt;
    }

    foreach ($notes as $key => $value) {
        if ($value !== null && $value !== '') {
            $payload['notes[' . $key . ']'] = (string)$value;
        }
    }

    return razorpayApiRequest('/payments/' . rawurlencode($paymentId) . '/refund', 'POST', $payload);
}
