<?php
$ch = curl_init('http://127.0.0.1:8199/feed');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "HTTP: $httpCode\n";
echo "Length: " . strlen($response) . "\n";
// Check if there's a JS error by looking for the closing script tag
$lastScript = strrpos($response, '</script>');
echo "Last </script> at position: $lastScript\n";
echo "Last 300 chars:\n" . substr($response, -300) . "\n";

// Check for PHP errors in the response
if (preg_match_all('/(Fatal error|Parse error|Warning|Notice|ErrorException|Whoops)/i', $response, $matches)) {
    echo "\nERRORS FOUND:\n";
    foreach ($matches[0] as $m) echo "  - $m\n";
}
