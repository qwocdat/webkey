<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TikTok Bot Control Panel</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background: #f5f5f7; margin: 0; padding: 20px; display: flex; justify-content: center; }
        .container { max-width: 450px; width: 100%; background: #fff; padding: 25px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); text-align: center; }
        h2 { margin: 10px 0 5px 0; color: #1d1d1f; font-size: 22px; }
        .sub-title { color: #86868b; font-size: 13px; margin-bottom: 20px; }
        
        .upload-section { border: 2px dashed #ddd; padding: 20px; border-radius: 8px; margin-bottom: 20px; background: #fafafa; }
        .file-input-label { display: inline-block; padding: 8px 16px; background: #007aff; color: #fff; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 500; margin-bottom: 10px; }
        .file-input { display: none; }
        .file-name { font-size: 13px; color: #666; display: block; margin-bottom: 10px; }
        .btn-upload { background: #34c759; color: white; border: none; padding: 8px 20px; border-radius: 6px; font-size: 14px; cursor: pointer; font-weight: 500; }
        
        .status-bar { display: flex; justify-content: space-between; align-items: center; background: #f5f5f7; padding: 10px 15px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; }
        .status-dot { display: inline-block; width: 8px; height: 8px; background: #ff3b30; border-radius: 50%; margin-right: 6px; }
        .status-dot.active { background: #34c759; }
        
        .control-btns { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; margin-bottom: 20px; }
        .btn { padding: 12px; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; color: white; }
        .btn-start { background: #34c759; }
        .btn-stop { background: #ff9500; }
        .btn-scan { background: #007aff; }
        .btn:disabled { background: #ccc; cursor: not-allowed; }
        
        .console-log { background: #1c1c1e; color: #30d158; padding: 15px; border-radius: 8px; height: 15px; overflow-y: auto; text-align: left; font-family: monospace; font-size: 13px; white-space: pre-wrap; margin-bottom: 15px; display: none; }
        .info-footer { font-size: 12px; color: #86868b; text-align: left; background: #f5f5f7; padding: 10px; border-radius: 6px; word-break: break-all; }
    </style>
</head>
<body>
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
        alert("Vui long chon file JSON truoc khi upload.");
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
            alert(data.message);
        } else {
            alert("Loi upload: " + data.error);
        }
    })
    .catch(err => alert("Loi ket noi: " + err));
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
            alert(data.message);
        } else {
            alert("Loi: " + data.error);
        }
        checkStatus();
    })
    .catch(err => alert("Loi ket noi server: " + err));
}

setInterval(checkStatus, 3000);
checkStatus();
</script>
</body>
</html>
