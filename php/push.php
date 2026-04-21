<?php

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/push-config.php';

use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

header('Content-Type: text/plain; charset=utf-8');

function fail(int $code, string $message): void
{
    http_response_code($code);
    exit($message . "\n");
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    fail(405, 'Method Not Allowed');
}

function getHeaderValue(string $name): string
{
    $serverKey = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
    return $_SERVER[$serverKey] ?? '';
}

function decodeBase64Flexible(string $input): string|false
{
    $input = trim($input);

    $decoded = base64_decode($input, true);
    if ($decoded !== false) {
        return $decoded;
    }

    $b64 = strtr($input, '-_', '+/');
    $padding = strlen($b64) % 4;
    if ($padding > 0) {
        $b64 .= str_repeat('=', 4 - $padding);
    }

    return base64_decode($b64, true);
}

function parseSubscriptionToken(string $token): array
{
    $decodedToken = decodeBase64Flexible($token);
    if ($decodedToken === false) {
        throw new RuntimeException('Token is not valid base64/base64url');
    }

    $subscriptionData = json_decode($decodedToken, true);
    if (!is_array($subscriptionData)) {
        throw new RuntimeException('Token does not contain valid JSON');
    }

    if (
        empty($subscriptionData['endpoint']) ||
        empty($subscriptionData['keys']['p256dh']) ||
        empty($subscriptionData['keys']['auth'])
    ) {
        throw new RuntimeException('Token JSON must contain endpoint, keys.p256dh and keys.auth');
    }

    return $subscriptionData;
}

$pushTokensHeader = trim(getHeaderValue('Push-Token'));
$pushTitle = trim(getHeaderValue('Push-Title'));
$pushBody  = trim(getHeaderValue('Push-Body'));

if ($pushTokensHeader === '') {
    fail(400, 'Missing Push-Token header');
}

if (!defined('VAPID_SUBJECT') || !defined('VAPID_PUBLIC_KEY') || !defined('VAPID_PRIVATE_KEY')) {
    fail(500, 'VAPID keys are not configured');
}

// token;token;;token; -> ['token','token','token']
$rawTokens = array_filter(
    array_map('trim', explode(';', $pushTokensHeader)),
    fn($v) => $v !== ''
);

// убрать дубли
$rawTokens = array_values(array_unique($rawTokens));

if (!$rawTokens) {
    fail(400, 'No valid tokens in Push-Token header');
}

$payload = json_encode([
    'title' => $pushTitle !== '' ? $pushTitle : 'GyverPush',
    'body'  => $pushBody,
    'url'   => '/',
], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);

if ($payload === false) {
    fail(500, 'Failed to encode payload JSON');
}

$auth = [
    'VAPID' => [
        'subject' => VAPID_SUBJECT,
        'publicKey' => VAPID_PUBLIC_KEY,
        'privateKey' => VAPID_PRIVATE_KEY,
    ],
];

try {
    $webPush = new WebPush($auth);

    $prepared = 0;
    $invalid = [];

    foreach ($rawTokens as $index => $token) {
        try {
            $subscriptionData = parseSubscriptionToken($token);
            $subscription = Subscription::create($subscriptionData);
            $webPush->queueNotification($subscription, $payload);
            $prepared++;
        } catch (\Throwable $e) {
            $invalid[] = 'token#' . ($index + 1) . ': ' . $e->getMessage();
        }
    }

    if ($prepared === 0) {
        fail(400, "All tokens are invalid\n" . implode("\n", $invalid));
    }

    $success = 0;
    $failed = 0;
    $reportsText = [];

    foreach ($webPush->flush() as $report) {
        if ($report->isSuccess()) {
            $success++;
        } else {
            $failed++;
            $reason = $report->getReason() ?: 'unknown';
            $endpoint = (string)$report->getRequest()->getUri();
            $reportsText[] = "FAIL: {$reason}; endpoint={$endpoint}";
        }
    }

    http_response_code($failed > 0 ? 207 : 200);

    echo "Prepared: {$prepared}\n";
    echo "Success: {$success}\n";
    echo "Failed: {$failed}\n";

    if ($invalid) {
        echo "Invalid tokens:\n";
        echo implode("\n", $invalid) . "\n";
    }

    if ($reportsText) {
        echo "Push errors:\n";
        echo implode("\n", $reportsText) . "\n";
    }

} catch (\Throwable $e) {
    fail(500, 'Exception: ' . $e->getMessage());
}