<?php
/**
 * Mollie Payments API v2, plain PHP cURL (no Composer package).
 * Docs: https://docs.mollie.com/reference/v2/payments-api/create-payment
 */

require_once __DIR__ . '/config.php';

function mollie_api(string $method, string $path, ?array $body = null): array
{
    $key = payments_mollie_api_key();
    if ($key === '' || $key === 'test_xxxxxxxx') {
        return ['ok' => false, 'error' => 'not_configured', 'status' => 0, 'data' => null];
    }
    $url = 'https://api.mollie.com/v2' . $path;
    $ch = curl_init($url);
    $headers = [
        'Authorization: Bearer ' . $key,
        'Accept: application/json',
    ];
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_CUSTOMREQUEST => strtoupper($method),
        CURLOPT_HTTPHEADER => $headers,
    ];
    if ($body !== null) {
        $json = json_encode($body);
        $headers[] = 'Content-Type: application/json';
        $opts[CURLOPT_HTTPHEADER] = $headers;
        $opts[CURLOPT_POSTFIELDS] = $json;
    }
    curl_setopt_array($ch, $opts);
    $raw = curl_exec($ch);
    $errno = curl_errno($ch);
    $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($errno || $raw === false) {
        error_log('mollie_api: curl error ' . $errno);
        return ['ok' => false, 'error' => 'curl', 'status' => $http, 'data' => null];
    }
    $data = json_decode((string) $raw, true);
    if (!is_array($data)) {
        error_log('mollie_api: invalid json HTTP ' . $http);
        return ['ok' => false, 'error' => 'json', 'status' => $http, 'data' => null];
    }
    $ok = $http >= 200 && $http < 300;
    if (!$ok) {
        $detail = (string) ($data['detail'] ?? $data['title'] ?? 'http');
        error_log('mollie_api: HTTP ' . $http . ' ' . $detail);
        return ['ok' => false, 'error' => $detail, 'status' => $http, 'data' => $data];
    }
    return ['ok' => true, 'error' => '', 'status' => $http, 'data' => $data];
}

/**
 * Create a Mollie payment. $args: amount, currency, description, redirectUrl,
 * webhookUrl, method, metadata (array). Returns the API result.
 */
function mollie_create_payment(array $args): array
{
    $body = [
        'amount' => [
            'currency' => (string) $args['currency'],
            'value' => payments_amount_value($args['amount']),
        ],
        'description' => (string) $args['description'],
        'redirectUrl' => (string) $args['redirectUrl'],
        'method' => (string) ($args['method'] ?? 'ideal'),
    ];
    if (!empty($args['webhookUrl'])) {
        $body['webhookUrl'] = (string) $args['webhookUrl'];
    }
    if (!empty($args['metadata']) && is_array($args['metadata'])) {
        $body['metadata'] = $args['metadata'];
    }
    return mollie_api('POST', '/payments', $body);
}

/** Fetch a payment by Mollie id (tr_...). Never trust a posted status; always use this. */
function mollie_get_payment(string $id): array
{
    $id = trim($id);
    if (!preg_match('/^tr_[A-Za-z0-9]+$/', $id)) {
        return ['ok' => false, 'error' => 'bad_id', 'status' => 0, 'data' => null];
    }
    return mollie_api('GET', '/payments/' . rawurlencode($id));
}
