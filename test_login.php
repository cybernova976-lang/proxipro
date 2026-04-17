<?php
// First, get the login page to get CSRF token
$ch = curl_init('http://127.0.0.1:8199/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, __DIR__ . '/cookies.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, __DIR__ . '/cookies.txt');
$response = curl_exec($ch);
curl_close($ch);

// Extract CSRF token
preg_match('/<meta name="csrf-token" content="([^"]+)"/', $response, $m);
$token = $m[1] ?? '';
echo "CSRF token: " . substr($token, 0, 20) . "...\n";

// Also try to extract from _token input
if (!$token) {
    preg_match('/<input[^>]*name="_token"[^>]*value="([^"]+)"/', $response, $m);
    $token = $m[1] ?? '';
    echo "Form token: " . substr($token, 0, 20) . "...\n";
}

// Try to login - we need actual credentials
// Check .env for test credentials or database info
$envFile = file_get_contents(__DIR__ . '/.env');
preg_match('/DB_DATABASE=(.+)/', $envFile, $m);
$dbName = trim($m[1] ?? '');
echo "DB: $dbName\n";

// Try to find a user via artisan tinker
echo "\nChecking users via tinker...\n";
