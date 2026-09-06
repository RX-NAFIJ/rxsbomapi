from flask import Flask, request, Response, render_template_string
import requests
import time

app = Flask(__name__)

# HTML টেমপ্লেট
HTML_TEMPLATE = """
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Request System</title>
    <style>
        body { background:#0a0e17; color:#fff; font-family:monospace; padding:20px; }
        .log { padding:4px 0; border-bottom:1px solid #1a2639; }
        .ok { color:#00ff88; }
        .err { color:#ff4444; }
        .info { color:#00d4ff; }
    </style>
</head>
<body>
    <h1 style="color:#00d4ff;">🚀 API Request System (Flask)</h1>
    <p><strong>📱 Phone:</strong> {{ phone }}</p>
    <p><strong>📊 API Range:</strong> {{ start }} - {{ end }}</p>
    <hr>
"""

def send_request(api_num, phone):
    url = f"https://rxinfo.page.gd/api{api_num}.php?phone={phone}"
    try:
        response = requests.get(url, timeout=5, verify=False)
        return {
            'success': 200 <= response.status_code < 300,
            'status': response.status_code,
            'error': None
        }
    except Exception as e:
        return {
            'success': False,
            'status': 0,
            'error': str(e)
        }

@app.route('/')
def index():
    phone = request.args.get('phone', '01759546192')
    start_api = int(request.args.get('start', 1))
    end_api = int(request.args.get('end', 75))

    def generate():
        # প্রারম্ভিক HTML পাঠাবে
        yield render_template_string(HTML_TEMPLATE, phone=phone, start=start_api, end=end_api)
        
        total = 0
        success = 0
        failed = 0

        for i in range(start_api, end_api + 1):
            total += 1
            res = send_request(i, phone)
            
            if res['success']:
                success += 1
                status_html = f"<span class='ok'>✅ Status: {res['status']}</span>"
            else:
                failed += 1
                status_html = f"<span class='err'>❌ Error</span>"

            # লাইভ ব্রাউজার রেসপন্স স্ট্রিমিং
            log_line = f"<div class='log'>[{i}/{end_api}] Sending to API{i}... {status_html}</div>"
            yield log_line

        # শেষ সামারি অংশ
        summary_html = f"""
        <hr>
        <h2>📊 SUMMARY</h2>
        <div class='ok'>✅ Success: {success}</div>
        <div class='err'>❌ Failed: {failed}</div>
        <div class='info'>📊 Total: {total}</div>
        </body></html>
        """
        yield summary_html

    return Response(generate(), mimetype='text/html')

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000, debug=True)
