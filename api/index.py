import asyncio
from flask import Flask, jsonify, render_template_string, request
import aiohttp

app = Flask(__name__)

# Base URL pattern for the endpoints
BASE_URL = "https://rxinfo.page.gd/api{i}.php"

async def fetch(session, url):
    """Fetch a single URL asynchronously with timeout and error handling."""
    try:
        async with session.get(url, timeout=aiohttp.ClientTimeout(total=5)) as response:
            try:
                data = await response.json()
            except Exception:
                data = await response.text()
            return {"url": url, "status": response.status, "response": data}
    except Exception as e:
        return {"url": url, "status": "Error", "response": str(e)}

async def fetch_all_apis(phone=""):
    """Concurrently send requests to api1.php through api75.php with phone parameter."""
    async with aiohttp.ClientSession() as session:
        tasks = []
        for i in range(1, 76):
            # Construct target URL with ?phone= parameter if phone is provided
            target_url = f"{BASE_URL.format(i=i)}?phone={phone}" if phone else BASE_URL.format(i=i)
            tasks.append(fetch(session, target_url))
            
        return await asyncio.gather(*tasks)

@app.route("/")
def index():
    """Route to render the main HTML dashboard."""
    return render_template_string("""
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>API Batch Fetcher</title>
        <style>
            body { font-family: monospace; background: #0d1117; color: #c9d1d9; padding: 20px; }
            h1 { color: #58a6ff; }
            .input-group { margin-bottom: 20px; display: flex; gap: 10px; }
            input[type="text"] { 
                background: #161b22; border: 1px solid #30363d; color: #c9d1d9; 
                padding: 10px; border-radius: 6px; font-size: 16px; width: 250px; 
            }
            button { 
                background: #238636; color: white; border: none; padding: 10px 20px; 
                font-size: 16px; cursor: pointer; border-radius: 6px; font-weight: bold; 
            }
            button:hover { background: #2ea043; }
            .card { background: #161b22; border: 1px solid #30363d; margin-top: 10px; padding: 10px; border-radius: 6px; }
            .success { color: #3fb950; }
            .error { color: #f85149; }
            pre { margin: 5px 0 0 0; background: #010409; padding: 8px; border-radius: 4px; overflow-x: auto; }
        </style>
    </head>
    <body>
        <h1>RxInfo Multi-API Request Center (api1 - api75)</h1>
        
        <div class="input-group">
            <input type="text" id="phoneInput" placeholder="Enter phone number (e.g. 01700000000)" />
            <button onclick="runFetch()">Fetch All 75 APIs</button>
        </div>

        <div id="status"></div>
        <div id="results"></div>

        <script>
            async function runFetch() {
                const phone = document.getElementById('phoneInput').value.trim();
                const statusDiv = document.getElementById('status');
                const resultsDiv = document.getElementById('results');
                
                statusDiv.innerHTML = "<p style='color: #e3b341;'>Sending 75 requests asynchronously...</p>";
                resultsDiv.innerHTML = "";

                const startTime = performance.now();
                try {
                    // Send phone parameter to backend API endpoint
                    const response = await fetch(`/api/fetch-all?phone=${encodeURIComponent(phone)}`);
                    const data = await response.json();
                    const endTime = performance.now();

                    statusDiv.innerHTML = `<p class='success'>Done! Retrieved ${data.length} responses in ${((endTime - startTime) / 1000).toFixed(2)}s.</p>`;
                    
                    data.forEach((item, index) => {
                        const card = document.createElement('div');
                        card.className = 'card';
                        const isOk = item.status === 200;
                        card.innerHTML = `
                            <div><strong>#${index + 1} - ${item.url}</strong> 
                                 <span class="${isOk ? 'success' : 'error'}">[Status: ${item.status}]</span>
                            </div>
                            <pre>${JSON.stringify(item.response, null, 2)}</pre>
                        `;
                        resultsDiv.appendChild(card);
                    });
                } catch (err) {
                    statusDiv.innerHTML = `<p class='error'>Error executing batch request: ${err}</p>`;
                }
            }
        </script>
    </body>
    </html>
    """)

@app.route("/api/fetch-all")
def get_all():
    """Endpoint that accepts ?phone= query parameter and runs async fetcher."""
    phone = request.args.get('phone', default='', type=str)
    results = asyncio.run(fetch_all_apis(phone=phone))
    return jsonify(results)

if __name__ == "__main__":
    app.run(debug=True, port=5000)
