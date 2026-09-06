<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With, X-CSRF-Token');
header('Access-Control-Max-Age: 86400');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

$eiin = $_GET['eiin'] ?? '';

if (!preg_match('/^\d+$/', $eiin)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Valid numeric eiin parameter is required'], JSON_UNESCAPED_UNICODE);
    exit;
}

$ua = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';

// Step 1: EMIS থেকে কুকি সংগ্রহ
$ch1 = curl_init('https://emis.gov.bd/EMIS/portalone');
curl_setopt_array($ch1, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HEADER => true,
    CURLOPT_USERAGENT => $ua,
    CURLOPT_SSL_VERIFYPEER => false
]);
$res1 = curl_exec($ch1);
curl_close($ch1);

preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $res1, $m);
$cookies = implode('; ', $m[1] ?? []);

// Step 2: Main Data Request
$ch2 = curl_init('https://emis.gov.bd/emis/Portal/GetTeacherDetails');
curl_setopt_array($ch2, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query(['instituteId' => '', 'EIIN' => $eiin, 'isTeacher' => '']),
    CURLOPT_HTTPHEADER => [
        'User-Agent: ' . $ua,
        'Accept: application/json, text/javascript, */*; q=0.01',
        'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
        'X-Requested-With: XMLHttpRequest',
        'Origin: https://emis.gov.bd',
        'Referer: https://emis.gov.bd/EMIS/portalone',
        'Cookie: ' . $cookies
    ],
    CURLOPT_SSL_VERIFYPEER => false
]);

$response = curl_exec($ch2);
$status = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
curl_close($ch2);

http_response_code($status ?: 200);
$data = json_decode($response, true);
echo json_encode($data ?: ['response' => $response], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
