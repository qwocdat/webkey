<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TikTok Bot</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; background: #f5f5f7; margin: 0; padding: 20px; display: flex; justify-content: center; }
        .container { max-width: 450px; width: 100%; background: #fff; padding: 25px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); text-align: center; }
        h2 { margin: 10px 0 5px 0; color: #1d1d1f; font-size: 24px; }
        .sub-title { color: #86868b; font-size: 14px; margin-bottom: 20px; }
        
        .upload-section { border: 2px dashed #ccc; padding: 20px; border-radius: 12px; margin-bottom: 20px; background: #fafafa; }
        .btn-select { display: inline-block; padding: 10px 24px; background: #007aff; color: #fff; border-radius: 8px; cursor: pointer; font-size: 15px; font-weight: 500; margin-bottom: 10px; }
        .file-input { display: none; }
        .file-name { font-size: 14px; color: #666; display: block; margin-bottom: 12px; }
        .btn-upload { background: #34c759; color: white; border: none; padding: 10px 30px; border-radius: 8px; font-size: 15px; cursor: pointer; font-weight: 500; width: 60%; }
        
        .status-bar { display: flex; justify-content: space-between; align-items: center; padding: 10px 5px; margin-bottom: 20px; font-size: 15px; }
        .status-dot { display: inline-block; width: 10px; height: 10px; background: #ff3b30; border-radius: 50%; margin-right: 8px; vertical-align: middle; }
        .status-dot.active { background: #34c759; }
        
        .control-btns { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; margin-bottom: 20px; }
        .btn { padding: 14px; border: none; border-radius: 10px; font-size: 16px; font-weight: 500; cursor: pointer; color: white; }
        .btn-start { background: #34c759; }
        .btn-stop { background: #ff8e86; } /* Màu đỏ nhạt/hồng cam như ảnh của bạn */
        .btn-scan { background: #007aff; }
        .btn:disabled { opacity: 0.6; cursor: not-allowed; }
        
        .console-log { background: #1c1c1e; color: #30d158; padding: 15px; border-radius: 15px; height: 150px; overflow-y: auto; text-align: left; font-family: monospace; font-size: 14px; white-space: pre-wrap; margin-bottom: 20px; }
        .info-footer { font-size: 14px; color: #333; background: #f5f5f7; padding: 12px; border-radius: 10px; text-align: left; }
    </style>
</head>
<body>
<div class="container">
    <h2>TikTok Bot</h2>
    <div class="sub-title">Upload file cookie JSON gốc → bot tự động dùng</div>
    
    <div class="upload-section">
        <form id="uploadForm" enctype="multipart/form-data">
            <label for="fileCookie" class="btn-select">Chọn file JSON</label>
            <input type="file" id="fileCookie" name="cookie_file" class="file-input" accept=".json" onchange="displayFileName()">
            <span id="fileNameDisplay" class="file-name">tiktok_cookies.json</span>
            <button type="submit" class="btn-upload">Upload</button>
        </form>
    </div>

    <div class="status-bar">
        <span><span id="dotStatus" class="status-dot"></span>Trạng thái: <strong id="txtStatus">Đã dừng</strong></span>
        <span style="color: #86868b;">PID: <strong id="txtPid">0</strong></span>
    </div>

    <div class="control-btns">
        <button class="btn btn-start" id="btnStart" onclick="controlBot('start')">Bật</button>
        <button class="btn btn-stop" id="btnStop" onclick="controlBot('stop')" disabled>Dừng</button>
        <button class="btn btn-scan" id="btnScan" onclick="controlBot('scan')">Quét</button>
    </div>

    <div class="console-log" id="consoleLog">Hệ thống sẵn sàng...</div>

    <div class="info-footer">
        File cookie: /sdcard/bottiktok/tiktok_cookies.json
    </div>
    <p style="font-size: 12px; color: #bbb; margin-top: 20px;">© 2026 · TikTok Bot Pro</p>
</div>

<script>
function displayFileName() {
    const fileInput = document.getElementById('fileCookie');
    const nameDisplay = document.getElementById('fileNameDisplay');
    if (fileInput.files.length > 0) {
        nameDisplay.textContent = fileInput.files[0].name;
    } else {
        nameDisplay.textContent = "tiktok_cookies.json";
    }
}

document.getElementById('uploadForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const fileInput = document.getElementById('fileCookie');
    if (fileInput.files.length === 0) {
        alert("Vui lòng chọn file JSON trước khi upload.");
        return;
    }

    const formData = new FormData();
    formData.append('cookie_file', fileInput.files[0]);

    fetch('bot_controller.php?action=upload_cookie', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'ok') {
            alert("Upload thành công! Cookie đã lưu.");
        } else {
            alert("Lỗi upload: " + data.error);
        }
    })
    .catch(err => alert("Lỗi kết nối: " + err));
});

function checkStatus() {
    fetch('bot_controller.php?action=status')
    .then(res => res.json())
    .then(data => {
        const dot = document.getElementById('dotStatus');
        const txt = document.getElementById('txtStatus');
        const pid = document.getElementById('txtPid');
        const consoleLog = document.getElementById('consoleLog');
        
        if (data.running) {
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
        
        if (data.log) {
            consoleLog.textContent = data.log;
        }
    });
}

function controlBot(action) {
    fetch('bot_controller.php?action=' + action)
    .then(res => res.json())
    .then(data => {
        if (data.status === 'ok') {
            alert(data.message);
        } else {
            alert("Lỗi: " + data.error);
        }
        checkStatus();
    })
    .catch(err => alert("Lỗi kết nối: " + err));
}

setInterval(checkStatus, 3000);
checkStatus();
</script>
</body>
</html>
