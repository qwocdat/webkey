<?php
$statusFile = __DIR__ . '/bot_status.json';
$status = ['running' => false, 'pid' => 0];
if (file_exists($statusFile)) {
    $status = json_decode(file_get_contents($statusFile), true);
}
$isRunning = isset($status['running']) && $status['running'];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>TikTok Bot</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family: -apple-system, "Segoe UI", Roboto, sans-serif;
            background: #e8e8e8;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 420px;
            width: 100%;
            background: #f9f9f9;
            border-radius: 30px;
            padding: 24px 20px 30px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.12);
        }
        h1 { font-weight: 400; font-size: 28px; color: #1a1a1a; margin-bottom: 4px; }
        .sub { color: #666; font-size: 14px; margin-bottom: 18px; border-bottom: 1px solid #ddd; padding-bottom: 12px; }

        .status-bar {
            display: flex; align-items: center; justify-content: space-between;
            background: #f0f0f0; padding: 8px 14px; border-radius: 20px;
            margin-bottom: 14px; font-size: 13px; color: #333;
        }
        .status-dot { display: inline-block; width: 10px; height: 10px; border-radius: 50%; margin-right: 6px; }
        .status-on { background: #34c759; }
        .status-off { background: #ff3b30; }

        .upload-area {
            border: 2px dashed #ccc;
            border-radius: 16px;
            padding: 20px;
            text-align: center;
            margin-bottom: 16px;
            transition: border 0.2s;
            background: #f5f5f5;
        }
        .upload-area:hover { border-color: #007aff; }
        .upload-area input[type="file"] { display: none; }
        .upload-label {
            display: inline-block;
            padding: 10px 20px;
            background: #007aff;
            color: #fff;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 500;
            font-size: 15px;
        }
        .upload-label i { margin-right: 6px; }
        .file-name {
            margin-top: 8px;
            font-size: 13px;
            color: #555;
        }
        .upload-status {
            margin-top: 6px;
            font-size: 13px;
            color: #28a745;
        }
        .btn-row {
            display: flex; gap: 10px; margin: 10px 0 14px;
        }
        .btn {
            flex: 1; padding: 12px 0; border: none; border-radius: 14px;
            font-weight: 600; font-size: 16px; cursor: pointer; transition: background 0.15s;
            display: flex; align-items: center; justify-content: center; gap: 6px;
        }
        .btn-start { background: #34c759; color: #fff; }
        .btn-start:hover { background: #28a745; }
        .btn-start:disabled { opacity: 0.5; cursor: not-allowed; }
        .btn-stop { background: #ff3b30; color: #fff; }
        .btn-stop:hover { background: #dc3545; }
        .btn-stop:disabled { opacity: 0.5; cursor: not-allowed; }
        .btn-scan { background: #007aff; color: #fff; }
        .btn-scan:hover { background: #005bbf; }

        .progress-wrap {
            background: #e0e0e0; border-radius: 20px; height: 8px; margin: 8px 0 6px; overflow: hidden;
        }
        .progress-bar {
            height: 100%; width: 0%; background: #007aff; border-radius: 20px; transition: width 0.5s ease;
        }
        .progress-label {
            font-size: 12px; color: #666; text-align: center; margin-bottom: 4px;
        }

        .terminal {
            background: #1c1c1e; border-radius: 14px; padding: 12px; height: 200px; overflow-y: auto;
            font-family: "SF Mono", monospace; font-size: 12px; color: #0f0; white-space: pre-wrap;
            word-break: break-all; border: 1px solid #333; margin-top: 6px;
        }
        .terminal::-webkit-scrollbar { width: 4px; }
        .terminal::-webkit-scrollbar-thumb { background: #007aff; border-radius: 4px; }

        .scan-result {
            background: #f0f0f0; border-radius: 12px; padding: 10px; max-height: 120px; overflow-y: auto;
            font-size: 13px; color: #222; margin-top: 10px;
        }
        .scan-result .user-item { padding: 4px 0; border-bottom: 1px solid #ddd; }
        .scan-result .user-item i { color: #007aff; margin-right: 6px; }

        /* Vùng hiển thị ảnh chụp màn hình */
        .screenshot-box {
            margin-top: 15px;
            background: #f0f0f0;
            border-radius: 12px;
            padding: 10px;
            text-align: center;
        }
        .screenshot-box img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            margin-top: 8px;
            border: 1px solid #ddd;
            display: none;
        }

        .footer { margin-top: 18px; font-size: 12px; color: #999; text-align: center; border-top: 1px solid #ddd; padding-top: 14px; }
        .cookie-info {
            font-size: 12px; color: #555; background: #f5f5f5; border-radius: 8px; padding: 6px 10px; margin-top: 4px;
            max-height: 60px; overflow-y: auto;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>🤖 TikTok Bot</h1>
    <div class="sub">Upload file cookie JSON gốc → bot tự động dùng</div>

    <div class="status-bar">
        <span><span class="status-dot <?= $isRunning ? 'status-on' : 'status-off' ?>"></span> <span id="statusText"><?= $isRunning ? 'Đang chạy' : 'Đã dừng' ?></span></span>
        <span>PID: <span id="pidDisplay"><?= $status['pid'] ?? 0 ?></span></span>
    </div>

    <!-- UPLOAD FILE JSON -->
    <div class="upload-area">
        <form id="uploadForm" enctype="multipart/form-data">
            <input type="file" id="cookieFile" name="cookieFile" accept=".json">
            <label for="cookieFile" class="upload-label"><i class="fas fa-cloud-upload-alt"></i> Chọn file JSON</label>
            <div class="file-name" id="fileName">Chưa có file</div>
            <button type="submit" class="btn btn-scan" style="margin-top:8px; width:auto; padding:8px 20px; font-size:14px; background:#28a745;"><i class="fas fa-upload"></i> Upload</button>
            <div class="upload-status" id="uploadStatus"></div>
        </form>
    </div>

    <!-- Progress bar -->
    <div id="progressContainer" style="display:none;">
        <div class="progress-label" id="progressLabel">Đang chuẩn bị...</div>
        <div class="progress-wrap"><div class="progress-bar" id="progressBar"></div></div>
    </div>

    <div class="btn-row">
        <button class="btn btn-start" id="startBtn" <?= $isRunning ? 'disabled' : '' ?>><i class="fas fa-play"></i> Bật</button>
        <button class="btn btn-stop" id="stopBtn" <?= $isRunning ? '' : 'disabled' ?>><i class="fas fa-stop"></i> Dừng</button>
        <button class="btn btn-scan" id="scanBtn"><i class="fas fa-users"></i> Quét</button>
    </div>

    <div class="terminal" id="terminalLog">
        <?php
        $logFile = __DIR__ . '/bot_terminal.log';
        if (file_exists($logFile)) {
            $lines = file($logFile);
            $lines = array_slice($lines, -30);
            echo htmlspecialchars(implode('', $lines));
        } else {
            echo "⚡ Sẵn sàng...\n";
        }
        ?>
    </div>

    <!-- Kết quả quét -->
    <div class="scan-result" id="scanResult">
        <div style="color:#888;">Danh sách người dùng sau khi quét.</div>
    </div>

    <!-- VÙNG HIỂN THỊ ẢNH CHỤP MÀN HÌNH MỚI THÊM -->
    <div class="screenshot-box">
        <div style="font-size: 13px; color: #555; font-weight: 500;"><i class="fas fa-camera"></i> Ảnh chụp màn hình quét mới nhất:</div>
        <img id="lastScreenshot" src="bot_screenshots/last_scan.png" alt="Chưa có ảnh chụp">
    </div>

    <div class="cookie-info" id="cookieInfo">
        <div style="color:#888;">📁 File cookie: /sdcard/bottiktok/tiktok_cookies.json</div>
    </div>

    <div class="footer">© 2026 · TikTok Bot Pro</div>
</div>

<script>
    // Kiểm tra và hiển thị ảnh sẵn có nếu có sẵn trên máy
    window.onload = function() {
        checkAndShowScreenshot();
    };

    function checkAndShowScreenshot() {
        const img = document.getElementById('lastScreenshot');
        const testImg = new Image();
        testImg.src = "bot_screenshots/last_scan.png?t=" + new Date().getTime();
        testImg.onload = function() {
            img.src = testImg.src;
            img.style.display = 'inline-block';
        };
        testImg.onerror = function() {
            img.style.display = 'none';
        };
    }

    // Upload file JSON
    document.getElementById('uploadForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const fileInput = document.getElementById('cookieFile');
        const file = fileInput.files[0];
        if (!file) {
            alert('Vui lòng chọn file JSON.');
            return;
        }
        const formData = new FormData();
        formData.append('cookieFile', file);

        document.getElementById('uploadStatus').textContent = '⏳ Đang upload...';

        fetch('bot_controller.php?action=upload', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'ok') {
                document.getElementById('uploadStatus').textContent = '✅ Upload thành công! Cookie đã lưu.';
                document.getElementById('fileName').textContent = file.name;
                loadStatus();
            } else {
                document.getElementById('uploadStatus').textContent = '❌ Lỗi: ' + (data.error || '');
            }
        })
        .catch(err => {
            document.getElementById('uploadStatus').textContent = '❌ Lỗi kết nối: ' + err;
        });
    });

    document.getElementById('cookieFile').addEventListener('change', function() {
        const file = this.files[0];
        if (file) {
            document.getElementById('fileName').textContent = file.name + ' (' + (file.size/1024).toFixed(1) + ' KB)';
        } else {
            document.getElementById('fileName').textContent = 'Chưa có file';
        }
    });

    function loadLog() {
        fetch('bot_controller.php?action=log')
            .then(res => res.text())
            .then(data => {
                const terminal = document.getElementById('terminalLog');
                terminal.textContent = data;
                terminal.scrollTop = terminal.scrollHeight;
            })
            .catch(() => {});
    }

    function loadStatus() {
        fetch('bot_controller.php?action=status')
            .then(res => res.json())
            .then(data => {
                const isRunning = data.running;
                document.getElementById('statusText').textContent = isRunning ? 'Đang chạy' : 'Đã dừng';
                document.getElementById('pidDisplay').textContent = data.pid || 0;
                document.getElementById('startBtn').disabled = isRunning;
                document.getElementById('stopBtn').disabled = !isRunning;
                document.querySelector('.status-dot').className = 'status-dot ' + (isRunning ? 'status-on' : 'status-off');
                if (!isRunning) {
                    document.getElementById('progressContainer').style.display = 'none';
                }
            })
            .catch(() => {});
    }

    function updateProgress() {
        fetch('bot_controller.php?action=log')
            .then(res => res.text())
            .then(data => {
                const lines = data.split('\n');
                let lastProgress = null;
                for (let i = lines.length-1; i>=0; i--) {
                    if (lines[i].includes('[PROGRESS]')) {
                        const match = lines[i].match(/\[PROGRESS\]\s*(\d+)\s*-\s*(.*)/);
                        if (match) {
                            lastProgress = { pct: parseInt(match[1]), label: match[2].trim() };
                            break;
                        }
                    }
                }
                if (lastProgress) {
                    const container = document.getElementById('progressContainer');
                    container.style.display = 'block';
                    document.getElementById('progressBar').style.width = lastProgress.pct + '%';
                    document.getElementById('progressLabel').textContent = lastProgress.label;
                    if (lastProgress.pct >= 100) {
                        setTimeout(() => { container.style.display = 'none'; }, 2000);
                    }
                }
            })
            .catch(() => {});
    }

    document.getElementById('startBtn').addEventListener('click', function() {
        document.getElementById('progressContainer').style.display = 'block';
        document.getElementById('progressBar').style.width = '10%';
        document.getElementById('progressLabel').textContent = 'Đang khởi động...';

        fetch('bot_controller.php?action=start')
            .then(res => res.json())
            .then(data => {
                if (data.status === 'ok') {
                    alert('Bot đã khởi động!');
                    loadStatus();
                    loadLog();
                } else {
                    alert('Lỗi: ' + (data.error || 'Không xác định'));
                    document.getElementById('progressContainer').style.display = 'none';
                }
            })
            .catch(err => {
                alert('Lỗi kết nối: ' + err);
                document.getElementById('progressContainer').style.display = 'none';
            });
    });

    document.getElementById('stopBtn').addEventListener('click', function() {
        fetch('bot_controller.php?action=stop')
            .then(res => res.json())
            .then(data => {
                if (data.status === 'ok') {
                    alert('Bot đã dừng.');
                    document.getElementById('progressContainer').style.display = 'none';
                    loadStatus();
                    loadLog();
                } else {
                    alert('Lỗi: ' + (data.error || 'Không xác định'));
                }
            })
            .catch(err => alert('Lỗi kết nối: ' + err));
    });

    document.getElementById('scanBtn').addEventListener('click', function() {
        const btn = this;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang quét...';
        document.getElementById('progressContainer').style.display = 'block';
        document.getElementById('progressBar').style.width = '20%';
        document.getElementById('progressLabel').textContent = 'Đang quét inbox...';

        fetch('bot_controller.php?action=scan')
            .then(res => res.json())
            .then(data => {
                btn.innerHTML = '<i class="fas fa-users"></i> Quét';
                btn.disabled = false;
                document.getElementById('progressBar').style.width = '100%';
                document.getElementById('progressLabel').textContent = 'Hoàn tất quét!';
                setTimeout(() => { document.getElementById('progressContainer').style.display = 'none'; }, 1500);
                
                // Cập nhật hiển thị ảnh chụp màn hình mới
                checkAndShowScreenshot();

                const resultDiv = document.getElementById('scanResult');
                if (data.status === 'ok' && data.users && data.users.length > 0) {
                    let html = '';
                    data.users.forEach(user => {
                        html += `<div class="user-item"><i class="fas fa-user-circle"></i> ${user.name} (ID: ${user.id})</div>`;
                    });
                    resultDiv.innerHTML = html;
                } else {
                    resultDiv.innerHTML = '<div style="color:#ff9500;">' + (data.error || 'Không tìm thấy người dùng trực quan trên DOM.') + '</div>';
                }
            })
            .catch(err => {
                btn.innerHTML = '<i class="fas fa-users"></i> Quét';
                btn.disabled = false;
                document.getElementById('progressContainer').style.display = 'none';
                document.getElementById('scanResult').innerHTML = '<div style="color:#ff3b30;">Lỗi: ' + err.message + '</div>';
                checkAndShowScreenshot();
            });
    });

    setInterval(() => {
        loadLog();
        loadStatus();
        updateProgress();
    }, 2000);

    loadLog();
    loadStatus();
    updateProgress();
</script>
</body>
</html>
