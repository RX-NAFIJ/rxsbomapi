<?php
// Vercel Serverless-এর Execution Time সেটআপ
set_time_limit(15); 

// URL প্যারামিটার
$phone = $_GET['phone'] ?? '01759546192';
$startApi = intval($_GET['start'] ?? 1);
$endApi = intval($_GET['end'] ?? 75);

function sendRequest($apiNum, $phone) {
    $url = "https://rxinfo.page.gd/api{$apiNum}.php?phone=" . urlencode($phone);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3); // Vercel-এ দ্রুত রেসপন্স পাওয়ার জন্য টাইমআউট ৩ সেকেন্ড
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    return [
        'success' => ($httpCode >= 200 && $httpCode < 300),
        'status' => $httpCode,
        'error' => $error ?: "HTTP $httpCode"
    ];
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vercel PHP API Request System</title>
    <style>
        body { background:#0a0e17; color:#fff; font-family:monospace; padding:20px; }
        .log { padding:4px 0; border-bottom:1px solid #1a2639; }
        .ok { color:#00ff88; }
        .err { color:#ff4444; }
        .info { color:#00d4ff; }
    </style>
</head>
<body>
    <h1 style="color:#00d4ff;">🚀 API Request System (Vercel)</h1>
    <p><strong>📱 Phone:</strong> <?= htmlspecialchars($phone) ?></p>
    <p><strong>📊 API Range:</strong> <?= $startApi ?> - <?= $endApi ?></p>
    <hr>

    <?php
    $total = 0;
    $success = 0;
    $failed = 0;

    for ($i = $startApi; $i <= $endApi; $i++) {
        $total++;
        echo "<div class='log'>[$i/$endApi] Sending to API$i... ";
        
        $result = sendRequest($i, $phone);
        
        if ($result['success']) {
            $success++;
            echo "<span class='ok'>✅ Status: {$result['status']}</span>";
        } else {
            $failed++;
            echo "<span class='err'>❌ Error: {$result['error']}</span>";
        }
        echo "</div>";
    }
    ?>

    <hr>
    <h2>📊 SUMMARY</h2>
    <div class="ok">✅ Success: <?= $success ?></div>
    <div class="err">❌ Failed: <?= $failed ?></div>
    <div class="info">📊 Total: <?= $total ?></div>
</body>
</html>
