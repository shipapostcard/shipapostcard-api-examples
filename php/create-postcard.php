<?php
// Send a postcard to a US address. PHP 8.1+ with the curl extension, no Composer packages.
// Usage: SHIPAPOSTCARD_API_KEY=... php create-postcard.php

$api = getenv('SHIPAPOSTCARD_API_URL') ?: 'https://shipapostcard.com/api/v1';

$postcard = [
    'message' => 'Thanks for your order! Enjoy 20% off your next one with THANKS20.',
    'recipient_details' => [
        'name' => 'Jane Doe',
        'address_line1' => '185 Berry St',
        'city' => 'San Francisco',
        'state' => 'CA',
        'zip' => '94107',
        'country' => 'US',
    ],
    'design_details' => [
        'image_url' => 'https://example.com/postcard-front.jpg',
        'size' => '4x6',
        'orientation' => 'landscape',
    ],
];

$curl = curl_init("{$api}/postcards");
curl_setopt_array($curl, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer '.getenv('SHIPAPOSTCARD_API_KEY'),
        'Content-Type: application/json',
        'Accept: application/json',
    ],
    CURLOPT_POSTFIELDS => json_encode($postcard),
]);
$body = json_decode(curl_exec($curl), true);
$status = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);

if ($status === 422) {
    // ['error' => 'Validation Failed', 'details' => ['recipient_details.zip' => ['...']]]
    fwrite(STDERR, 'Invalid postcard: '.json_encode($body['details'], JSON_PRETTY_PRINT).PHP_EOL);
    exit(1);
}
if ($status !== 201) {
    fwrite(STDERR, "HTTP {$status}: ".json_encode($body).PHP_EOL);
    exit(1);
}

echo "Queued {$body['id']} ({$body['status']}) for \${$body['amount']}".PHP_EOL;
