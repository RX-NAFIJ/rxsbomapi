<?php
// URL প্যারামিটার থেকে ডাটা সংগ্রহ
$phone = $_GET['phone'] ?? '';
$startApi = intval($_GET['start'] ?? 1);
$endApi = intval($_GET['end'] ?? 75);
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RxInfo Multi-API Request Center</title>
    <style>
        body { background:#0a0e17; color:#fff; font-family:monospace; padding:20px; }
        .log { padding:4px 0; border-bottom:1px solid #1a2639; }
        .ok { color:#00ff88; }
        .err { color:#ff4444; }
        .info { color:#00d4ff; }
        #logs { max-height: 450px; overflow-y: auto; background: #05080f; padding: 12px; border-radius: 6px; margin-top: 15px; border: 1px solid #1a2639; }
        .card { background: #131b29; padding: 15px; border-radius: 8px; margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="card">
        <h1 style="color:#00d4ff; margin-top:0;">🚀 RxInfo Multi-API Request Center</h1>
        <p><strong>📱 Phone:</strong> <span class="info"><?= htmlspecialchars($phone ?: 'Not Set') ?></span></p>
        <p><strong>📊 API Range:</strong> <?= $startApi ?> - <?= $endApi ?></p>
    </div>

    <div id="logs">অপেক্ষা করা হচ্ছে...<br></div>

    <div id="summary" style="display:none;" class="card">
        <h2>📊 SUMMARY</h2>
        <div class="ok" id="succ-count">✅ Success: 0</div>
        <div class="err" id="fail-count">❌ Failed: 0</div>
        <div class="info" id="total-count">📊 Total: 0</div>
    </div>

    <script>
    const phone = "<?= htmlspecialchars($phone) ?>";
    const start = <?= $startApi ?>;
    const end = <?= $endApi ?>;

    async function autoStart() {
        const logsDiv = document.getElementById('logs');

        if (!phone) {
            logsDiv.innerHTML = "<span class='err'>❌ কোনো ফোন নম্বর দেওয়া হয়নি! URL-এ ?phone=01759546192 যুক্ত করুন।</span>";
            return;
        }

        logsDiv.innerHTML = "স্বয়ংক্রিয়ভাবে রিকোয়েস্ট শুরু হচ্ছে...<br><br>";
        
        let success = 0;
        let failed = 0;
        let total = 0;

        for (let i = start; i <= end; i++) {
            total++;
            const apiUrl = `https://rxinfo.page.gd/api${i}.php?phone=${encodeURIComponent(phone)}`;
            
            const logItem = document.createElement('div');
            logItem.className = 'log';
            logItem.innerHTML = `[${i}/${end}] Sending to API${i}... `;
            logsDiv.appendChild(logItem);

            try {
                // Vercel Timeout বাইপাস করে সরাসরি ব্রাউজার থেকে কল হবে
                await fetch(apiUrl, { mode: 'no-cors' });
                success++;
                logItem.innerHTML += `<span class="ok">✅ Sent (OK)</span>`;
            } catch (error) {
                failed++;
                logItem.innerHTML += `<span class="err">❌ Failed</span>`;
            }

            logsDiv.scrollTop = logsDiv.scrollHeight;
            await new Promise(r => setTimeout(r, 40)); // ৫০ মিলি-সেকেন্ড বিরতি
        }

        // সামারি দেখাবে
        document.getElementById('succ-count').innerText = `✅ Success: ${success}`;
        document.getElementById('fail-count').innerText = `❌ Failed: ${failed}`;
        document.getElementById('total-count').innerText = `📊 Total: ${total}`;
        document.getElementById('summary').style.display = 'block';
    }

    // পেজ ওপেন হওয়ার সাথেই রান হবে
    window.onload = autoStart;
    </script>
</body>
</html>
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
