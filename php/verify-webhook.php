<?php
// Receive Ship a Postcard webhooks and verify their signature. PHP 8.1+, no packages.
// Try it: SHIPAPOSTCARD_WEBHOOK_SECRET=... php -S localhost:3000 verify-webhook.php

const TOLERANCE_SECONDS = 300; // reject replays of old requests

/**
 * @param  string  $rawBody  the request body exactly as received (don't decode and re-encode it)
 * @param  string  $header  the ShipAPostcard-Signature header: "t=1760600000,v1=<hex>"
 */
function verify_signature(string $rawBody, string $header, string $secret, ?int $now = null): bool
{
    $parts = [];
    foreach (explode(',', $header) as $part) {
        [$key, $value] = array_pad(explode('=', $part, 2), 2, '');
        $parts[$key] = $value;
    }

    $timestamp = (int) ($parts['t'] ?? 0);
    if ($timestamp === 0 || empty($parts['v1']) || abs(($now ?? time()) - $timestamp) > TOLERANCE_SECONDS) {
        return false;
    }

    $expected = hash_hmac('sha256', "{$timestamp}.{$rawBody}", $secret);

    return hash_equals($expected, $parts['v1']); // constant-time comparison
}

if (PHP_SAPI !== 'cli') {
    $rawBody = file_get_contents('php://input');

    if (! verify_signature($rawBody, $_SERVER['HTTP_SHIPAPOSTCARD_SIGNATURE'] ?? '', getenv('SHIPAPOSTCARD_WEBHOOK_SECRET'))) {
        http_response_code(400);
        exit('Invalid signature');
    }

    // ShipAPostcard-Event-Id repeats on retries: store it and skip events you've already handled
    $eventId = $_SERVER['HTTP_SHIPAPOSTCARD_EVENT_ID'] ?? '';
    $event = json_decode($rawBody, true);
    error_log("{$eventId} {$event['type']} ".json_encode($event['data']));

    http_response_code(200); // answer within 10 seconds, do slow work afterwards
    echo 'ok';
}
