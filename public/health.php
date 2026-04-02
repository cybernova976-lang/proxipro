<?php
// Lightweight health check for Railway – does NOT boot Laravel
http_response_code(200);
header('Content-Type: text/plain');
echo 'OK';
