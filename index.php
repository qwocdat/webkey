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
        .btn-stop { background: #ff8e86; }
        .btn-scan { background: #007aff; }
        .btn:disabled { opacity: 0.6; cursor: not-allowed; }
        
        .console-log { background: #1c1c1e; color: #30d158; padding: 15px; border-radius: 15px; height: 150px; overflow-y: auto; text-align: left; font-family: monospace; font-size: 14px; white-space: pre-wrap; margin-bottom: 20px; }
        .info-footer { font-size: 14px; color: #333; background: #f5f5f7; padding: 12px; border-radius: 10px; text-align: left; }

        /* GIAO DIỆN DIỄN POPUP KIỂU IOS KHÔNG LÀM TỐI ĐEN */
        .ios-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.2); display: flex; align-items: center; justify-content: center; opacity: 0; pointer-events: none; transition: opacity 0.3s ease; z-index: 9999; }
        .ios-overlay.show { opacity: 1; pointer-events: auto; }
        .ios-alert { background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); width: 270px; border-radius: 14px; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.1); transform: scale(1.1); transition: transform 0.25s ease; font-size: 14px; }
        .ios-overlay.show .ios-alert { transform: scale(1); }
        .ios-title { font-weight: 600; padding: 16px 16px 4px 16px; font-size: 17px; color: #000; }
        .ios-message { padding: 0 16px 16px 16px; color: #333; font-size: 13px; line-height: 1.4; word-wrap: break-word; max-height: 200px; overflow-y: auto; }
        .ios-buttons { display: flex; border-top: 0.5px solid #c8c8cd; }
        .ios-btn { flex: 1; padding: 12px; border: none; background: transparent; font-size: 16px; color: #007aff; cursor: pointer; outline: none; }
        .ios-btn:active { background: rgba(0,0,0,0.05); }
        .ios-btn.bold { font-weight: 600; }
        .ios-btn.cancel { border-right: 0.5px solid #c8c8cd; color: #ff3b30; }
    </style>
</head>
<body>

<!-- Khung cấu trúc Popup iOS -->
<div class="ios-overlay" id="iosOverlay">
    <div class="ios-alert">
        <div class="ios-title" id="iosTitle">Thông báo</div>
        <div class="ios-message" id="iosMsg">Nội dung</div>
        <div class="ios-buttons" id="iosBtnContainer">
            <!-- Nút bấm động sẽ chèn vào đây -->
        </div>
    </div>
</div>

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
        <button class="btn btn-start" id="btnStart" onclick="confirmAction('start', 'Bạn có chắc chắn muốn Bật bot không?')">Bật</button>
        <button class="btn btn-stop" id="btnStop" onclick="confirmAction('stop', 'Bạn có chắc chắn muốn Dừng bot không?')" disabled>Dừng</button>
        <button class="btn btn-scan" id="btnScan" onclick="confirmAction('scan', 'Bạn có chắc chắn muốn Quét dữ liệu ngay không?')">Quét</button>
    </div>

    <div class="console-log" id="consoleLog">Hệ thống sẵn sàng...</div>

    <div class="info-footer">
        File cookie: /sdcard/bottiktok/tiktok_cookies.json
    </div>
    <p style="font-size: 12px; color: #bbb; margin-top: 20px;">© 2026 · TikTok Bot Pro</p>
</div>

<script>
// Hàm gọi hiển thị Popup iOS chuyên nghiệp
function openIosAlert(title, message, buttons) {
    document.getElementById('iosTitle').textContent = title;
    document.getElementById('iosMsg').textContent = message;
    const container = document.getElementById('iosBtnContainer');
    container.innerHTML = '';
    
    buttons.forEach(b => {
        const btn = document.createElement('button');
        btn.className = `ios-btn ${b.style || ''}`;
        btn.textContent = b.text;
        btn.onclick = () => {
            document.getElementById('iosOverlay').classList.remove('show');
            if(b.onClick) b.onClick();
        };
        container.appendChild(btn);
    });
    document.getElementById('iosOverlay').classList.add('show');
}

function confirmAction(action, question) {
    openIosAlert("Xác nhận", question, [
        { text: "Hủy", style: "cancel" },
        { text: "Đồng ý", style: "bold", onClick: () => executeBotAction(action) }
    ]);
}

function displayFileName() {
    const fileInput = document.getElementById('fileCookie');
    const nameDisplay = document.getElementById('fileNameDisplay');
    nameDisplay.textContent = fileInput.files.length > 0 ? fileInput.files[0].name : "tiktok_cookies.json";
}

document.getElementById('uploadForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const fileInput = document.getElementById('fileCookie');
    if (fileInput.files.length === 0) {
        openIosAlert("Thông báo", "Vui lòng chọn file JSON trước.", [{ text: "OK", style: "bold" }]);
        return;
    }
    const formData = new FormData(this);
    fetch('bot_controller.php?action=upload_cookie', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        let msg = data.status === 'ok' ? "Upload thành công! Cookie đã được lưu." : "Lỗi: " + data.error;
        openIosAlert("Thông báo", msg, [{ text: "OK", style: "bold" }]);
    });
});

function executeBotAction(action) {
    fetch('bot_controller.php?action=' + action)
    .then(res => res.json())
    .then(data => {
        let title = data.status === 'ok' ? "Thành công" : "Lỗi hệ thống";
        let msg = data.status === 'ok' ? data.message : data.error;
        openIosAlert(title, msg, [{ text: "OK", style: "bold" }]);
        checkStatus();
    });
}

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
        if (data.log) consoleLog.textContent = data.log;
    });
}

setInterval(checkStatus, 3000);
checkStatus();
</script>
</body>
</html>
