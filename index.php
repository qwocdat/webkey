<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TikTok Bot Control Panel</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background: #f5f5f7; margin: 0; padding: 20px; display: flex; justify-content: center; }
        .container { max-width: 450px; width: 100%; background: #fff; padding: 25px; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); text-align: center; position: relative; }
        h2 { margin: 10px 0 5px 0; color: #1d1d1f; font-size: 22px; }
        .sub-title { color: #86868b; font-size: 13px; margin-bottom: 20px; }
        
        .upload-section { border: 2px dashed #e5e5ea; padding: 20px; border-radius: 12px; margin-bottom: 20px; background: #fafafa; }
        .file-input-label { display: inline-block; padding: 10px 20px; background: #007aff; color: #fff; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 500; margin-bottom: 10px; transition: background 0.2s; }
        .file-input-label:hover { background: #0062cc; }
        .file-input { display: none; }
        .file-name { font-size: 13px; color: #86868b; display: block; margin-bottom: 12px; }
        .btn-upload { background: #34c759; color: white; border: none; padding: 10px 24px; border-radius: 8px; font-size: 14px; cursor: pointer; font-weight: 600; }
        
        .status-bar { display: flex; justify-content: space-between; align-items: center; background: #f5f5f7; padding: 12px 15px; border-radius: 10px; margin-bottom: 20px; font-size: 14px; }
        .status-dot { display: inline-block; width: 8px; height: 8px; background: #ff3b30; border-radius: 50%; margin-right: 6px; }
        .status-dot.active { background: #34c759; }
        
        .control-btns { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; margin-bottom: 20px; }
        .btn { padding: 12px; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; color: white; transition: opacity 0.2s; }
        .btn:active { opacity: 0.8; }
        .btn-start { background: #34c759; }
        .btn-stop { background: #ff9500; }
        .btn-scan { background: #007aff; }
        .btn:disabled { background: #ccc; cursor: not-allowed; }
        
        .console-log { background: #1c1c1e; color: #30d158; padding: 15px; border-radius: 10px; height: 80px; overflow-y: auto; text-align: left; font-family: monospace; font-size: 12px; white-space: pre-wrap; margin-bottom: 15px; display: none; }
        .info-footer { font-size: 12px; color: #86868b; text-align: left; background: #f5f5f7; padding: 12px; border-radius: 8px; word-break: break-all; }

        /* Custom Popup đẹp mắt, không làm tối đen màn hình thô thiển */
        .custom-popup { position: fixed; top: 20px; left: 50%; transform: translateX(-50%) translateY(-100px); background: #323232; color: #fff; padding: 12px 24px; border-radius: 30px; font-size: 14px; font-weight: 500; box-shadow: 0 4px 15px rgba(0,0,0,0.15); transition: transform 0.3s ease, opacity 0.3s ease; opacity: 0; z-index: 9999; pointer-events: none; }
        .custom-popup.show { transform: translateX(-50%) translateY(0); opacity: 1; }
    </style>
</head>
<body>

<div class="custom-popup" id="customPopup">Thông báo</div>

<div class="container">
    <h2>TikTok Bot</h2>
    <div class="sub-title">Upload file cookie JSON goc -> bot tu dong dung</div>
    
    <div class="upload-section">
        <form id="uploadForm" enctype="multipart/form-data">
            <label for="fileCookie" class="file-input-label">Chon file JSON</label>
            <input type="file" id="fileCookie" name="cookie_file" class="file-input" accept=".json" onchange="displayFileName()">
            <span id="fileNameDisplay" class="file-name">tiktok_cookies.json</span>
            <button type="submit" class="btn-upload">Upload</button>
        </form>
    </div>

    <div class="status-bar">
        <span><span id="dotStatus" class="status-dot"></span>Trang thai: <strong id="txtStatus">Da dung</strong></span>
        <span style="color: #86868b;">PID: <strong id="txtPid">0</strong></span>
    </div>

    <div class="control-btns">
        <button class="btn btn-start" id="btnStart" onclick="controlBot('start')">Bat</button>
        <button class="btn btn-stop" id="btnStop" onclick="controlBot('stop')" disabled>Dung</button>
        <button class="btn btn-scan" id="btnScan" onclick="controlBot('scan')">Quet</button>
    </div>

    <div class="console-log" id="consoleLog"></div>

    <div class="info-footer">
        File cookie: /sdcard/bottiktok/tiktok_cookies.json
    </div>
</div>

<script>
function showNotification(msg) {
    const popup = document.getElementById('customPopup');
    popup.textContent = msg;
    popup.classList.add('show');
    setTimeout(() => {
        popup.classList.remove('show');
    }, 3000);
}

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
        showNotification("Vui long chon file JSON.");
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
            showNotification(data.message);
        } else {
            showNotification("Loi upload: " + data.error);
        }
    })
    .catch(err => showNotification("Loi ket noi: " + err));
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
            txt.textContent = "Dang chay";
            txt.style.color = "#34c759";
            pid.textContent = data.pid;
            document.getElementById('btnStart').disabled = true;
            document.getElementById('btnStop').disabled = false;
        } else {
            dot.className = "status-dot";
            txt.textContent = "Da dung";
            txt.style.color = "#ff3b30";
            pid.textContent = "0";
            document.getElementById('btnStart').disabled = false;
            document.getElementById('btnStop').disabled = true;
        }
        
        if (data.log && data.running) {
            consoleLog.style.display = "block";
            consoleLog.textContent = data.log;
        } else {
            consoleLog.style.display = "none";
        }
    });
}

function controlBot(action) {
    fetch('bot_controller.php?action=' + action)
    .then(res => res.json())
    .then(data => {
        if (data.status === 'ok') {
            showNotification(data.message);
        } else {
            showNotification("Loi: " + data.error);
        }
        checkStatus();
    })
    .catch(err => showNotification("Loi ket noi: " + err));
}

setInterval(checkStatus, 3000);
checkStatus();
</script>
</body>
</html>
