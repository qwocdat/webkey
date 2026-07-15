<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🤖 TikTok Bot Pro - Cloud Render</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: -apple-system, sans-serif; background: #f5f5f7; margin: 0; padding: 20px; display: flex; justify-content: center; }
        .container { max-width: 450px; width: 100%; background: #fff; padding: 25px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); text-align: center; }
        h2 { margin: 10px 0; color: #1d1d1f; display: flex; align-items: center; justify-content: center; gap: 10px; }
        .sub-title { color: #86868b; font-size: 14px; margin-bottom: 20px; }
        .status-bar { display: flex; justify-content: space-between; align-items: center; background: #f5f5f7; padding: 10px 15px; border-radius: 10px; margin-bottom: 20px; font-size: 14px; }
        .status-dot { display: inline-block; width: 8px; height: 8px; background: #ff3b30; border-radius: 50%; margin-right: 5px; }
        .status-dot.active { background: #34c759; }
        .control-btns { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; margin-bottom: 20px; }
        .btn { padding: 12px; border: none; border-radius: 12px; font-size: 15px; font-weight: bold; cursor: pointer; color: white; display: flex; align-items: center; justify-content: center; gap: 5px; }
        .btn-start { background: #34c759; }
        .btn-stop { background: #ff9500; }
        .btn-scan { background: #007aff; }
        .btn:disabled { background: #ccc; cursor: not-allowed; }
        .console-log { background: #1c1c1e; color: #30d158; padding: 15px; border-radius: 12px; height: 180px; overflow-y: auto; text-align: left; font-family: monospace; font-size: 13px; white-space: pre-wrap; margin-bottom: 20px; }
        .info-footer { font-size: 12px; color: #86868b; background: #f5f5f7; padding: 8px; border-radius: 8px; text-align: left; }
    </style>
</head>
<body>
<div class="container">
    <h2>🤖 TikTok Bot</h2>
    <div class="sub-title">Chạy ngầm trên máy chủ Cloud Render</div>
    
    <div class="status-bar">
        <span><span id="dotStatus" class="status-dot"></span>Trạng thái: <strong id="txtStatus">Đang dừng</strong></span>
        <span style="color: #86868b;">PID: <strong id="txtPid">0</strong></span>
    </div>

    <div class="control-btns">
        <button class="btn btn-start" id="btnStart" onclick="controlBot('start')"><i class="fas fa-play"></i> Bật</button>
        <button class="btn btn-stop" id="btnStop" onclick="controlBot('stop')" disabled><i class="fas fa-stop"></i> Dừng</button>
        <button class="btn btn-scan" id="btnScan" onclick="controlBot('scan')"><i class="fas fa-search"></i> Quét</button>
    </div>

    <div class="console-log" id="consoleLog">Hệ thống sẵn sàng... nhấn Bật hoặc Quét để bắt đầu điều khiển script Python.</div>

    <div class="info-footer">
        <i class="fas fa-folder"></i> File cookie: <code>/sdcard/bottiktok/tiktok_cookies.json</code>
    </div>
    <p style="font-size: 11px; color: #ccc; margin-top: 15px;">© 2026 · TikTok Bot Pro Cloud</p>
</div>

<script>
function appendLog(text) {
    const consoleLog = document.getElementById('consoleLog');
    consoleLog.textContent = text;
    consoleLog.scrollTop = consoleLog.scrollHeight;
}

function checkStatus() {
    fetch('bot_controller.php?action=status')
    .then(res => res.json())
    .then(data => {
        const dot = document.getElementById('dotStatus');
        const txt = document.getElementById('txtStatus');
        const pid = document.getElementById('txtPid');
        
        if(data.running) {
            dot.className = "status-dot active";
            txt.textContent = "Đang chạy";
            txt.style.color = "#34c759";
            pid.textContent = data.pid;
            document.getElementById('btnStart').disabled = true;
            document.getElementById('btnStop').disabled = false;
        } else {
            dot.className = "status-dot";
            txt.textContent = "Đã dừng";
            txt.style.color = "#ff3b30";
            pid.textContent = "0";
            document.getElementById('btnStart').disabled = false;
            document.getElementById('btnStop').disabled = true;
        }
        if(data.log) {
            appendLog(data.log);
        }
    });
}

function controlBot(action) {
    appendLog("⏳ Đang gửi yêu cầu xử lý đến Server Render...");
    
    fetch('bot_controller.php?action=' + action)
    .then(res => res.json())
    .then(data => {
        if(data.status === 'ok') {
            appendLog(data.message);
        } else {
            appendLog("❌ Lỗi: " + data.error);
        }
        checkStatus();
    })
    .catch(err => appendLog("❌ Lỗi kết nối server: " + err));
}

// Tự động kiểm tra trạng thái bot mỗi 3 giây một lần
setInterval(checkStatus, 3000);
checkStatus();
</script>
</body>
</html>
