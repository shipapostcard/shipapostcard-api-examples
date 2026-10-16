<?php
// List your postcards, get one, and cancel it. PHP 8.1+ with the curl extension.
// Usage: SHIPAPOSTCARD_API_KEY=... php manage-postcards.php sap_order_1234

$api = getenv('SHIPAPOSTCARD_API_URL') ?: 'https://shipapostcard.com/api/v1';
$id = $argv[1] ?? exit("Pass a postcard id, e.g. sap_order_1234\n");

function request(string $method, string $url): array
{
    $curl = curl_init($url);
    curl_setopt_array($curl, [
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer '.getenv('SHIPAPOSTCARD_API_KEY'), 'Accept: application/json'],
    ]);
    $body = curl_exec($curl);

    return [curl_getinfo($curl, CURLINFO_RESPONSE_CODE), json_decode($body ?: 'null', true)];
}

[, $page] = request('GET', "{$api}/postcards?page=1");
echo "{$page['total']} postcards; first page:".PHP_EOL;
foreach ($page['data'] as $postcard) {
    echo "  {$postcard['id']}  {$postcard['status']}  {$postcard['recipient_details']['name']}".PHP_EOL;
}

[, $postcard] = request('GET', "{$api}/postcards/{$id}");
echo PHP_EOL."{$id} is {$postcard['status']}, expected delivery: ".($postcard['tracking']['expected_delivery_date'] ?? 'not printed yet').PHP_EOL;
foreach ($postcard['tracking']['events'] as $event) {
    echo "  {$event['timestamp']}  {$event['event']}".PHP_EOL;
}

[$status] = request('DELETE', "{$api}/postcards/{$id}");
echo match ($status) {
    204 => 'Cancelled.',
    403 => 'Too late to cancel: it is already being billed, printed or mailed.',
    default => "HTTP {$status}",
}.PHP_EOL;
