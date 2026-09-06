<?php

// CORS Headers — যেকোনো ওয়েবসাইট বা অ্যাপ থেকে কল করার জন্য
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With, X-CSRF-Token');
header('Access-Control-Max-Age: 86400');

// Preflight Request হ্যান্ডলিং
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

// GET প্যারামিটার যাচাই
$eiin = $_GET['eiin'] ?? '';

if (!preg_match('/^\d+$/', $eiin)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Valid numeric eiin parameter is required'
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// সাধারণ User-Agent
$userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';

// Step 1: EMIS এর মেইন পেজে হিট করে Fresh CSRF Token ও Cookies সংগ্রহ করা
$chInit = curl_init('https://emis.gov.bd/EMIS/portalone');
curl_setopt_array($chInit, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HEADER => true,
    CURLOPT_USERAGENT => $userAgent,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_SSL_VERIFYPEER => false
]);

$initResponse = curl_exec($chInit);
curl_close($chInit);

// Response থেকে Headers ও Body আলাদা করা
preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $initResponse, $cookieMatches);
$cookies = implode('; ', $cookieMatches[1] ?? []);

// HTML থেকে CSRF Token খোঁজা
$csrfToken = '';
if (preg_match('/name="__RequestVerificationToken"\s+type="hidden"\s+value="([^"]+)"/i', $initResponse, $tokenMatch)) {
    $csrfToken = $tokenMatch[1];
}

// Step 2: সংগৃহীত Token ও Cookie দিয়ে মূল ডেটার জন্য Request পাঠানো
$dataUrl = 'https://emis.gov.bd/emis/Portal/GetTeacherDetails';
$postFields = http_build_query([
    'instituteId' => '',
    'EIIN' => $eiin,
    'isTeacher' => ''
]);

$chData = curl_init($dataUrl);
curl_setopt_array($chData, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $postFields,
    CURLOPT_HTTPHEADER => [
        'User-Agent: ' . $userAgent,
        'Accept: application/json, text/javascript, */*; q=0.01',
        'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
        'X-CSRF-Token: ' . $csrfToken,
        'X-Requested-With: XMLHttpRequest',
        'Origin: https://emis.gov.bd',
        'Referer: https://emis.gov.bd/EMIS/portalone',
        'Cookie: ' . $cookies
    ],
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_TIMEOUT => 20
]);

$response = curl_exec($chData);
$error = curl_error($chData);
$status = curl_getinfo($chData, CURLINFO_HTTP_CODE);
curl_close($chData);

if ($error) {
    http_response_code(502);
    echo json_encode([
        'success' => false,
        'error' => 'cURL Error: ' . $error
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// JSON আউটপুট ডেলিভারি
$decoded = json_decode($response, true);

if (json_last_error() === JSON_ERROR_NONE) {
    http_response_code($status ?: 200);
    echo json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
} else {
    http_response_code($status ?: 200);
    echo json_encode([
        'success' => true,
        'raw_response' => $response
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
}
