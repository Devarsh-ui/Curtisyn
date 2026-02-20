<?php
require_once __DIR__ . '/database.php';

$razorpayConfig = [
    'key_id' => 'rzp_live_SHgt3547cziGH6',
    'key_secret' => 'uo3wuK5YAQnE8E6ktqJMCSRS',
    'currency' => 'INR'
];

function getRazorpayConfig() {
    global $razorpayConfig;
    return $razorpayConfig;
}

function createRazorpayOrder($amount, $orderId, $receipt) {
    $config = getRazorpayConfig();

    $data = [
        'amount' => intval(round($amount * 100)),
        'currency' => $config['currency'],
        'receipt' => $receipt,
        'notes' => [
            'order_id' => $orderId
        ]
    ];

    $ch = curl_init('https://api.razorpay.com/v1/orders');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode($config['key_id'] . ':' . $config['key_secret'])
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    error_log('Razorpay API Response: HTTP ' . $httpCode . ' - ' . $response);
    if ($curlError) {
        error_log('Razorpay CURL Error: ' . $curlError);
    }

    if ($httpCode === 200) {
        return json_decode($response, true);
    }

    return null;
}

function verifyRazorpayPayment($paymentId, $orderId, $signature) {
    $config = getRazorpayConfig();

    $generatedSignature = hash_hmac('sha256', $orderId . '|' . $paymentId, $config['key_secret']);

    return hash_equals($generatedSignature, $signature);
}

function fetchRazorpayPayment($paymentId) {
    $config = getRazorpayConfig();

    $ch = curl_init('https://api.razorpay.com/v1/payments/' . $paymentId);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Basic ' . base64_encode($config['key_id'] . ':' . $config['key_secret'])
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);
}
