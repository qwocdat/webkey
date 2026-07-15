<?php
// Tăng thời gian chạy tối đa lên 2 phút để không bị lỗi "Failed to fetch" khi Selenium chụp ảnh
set_time_limit(120);

$pythonPath = '/data/data/com.termux/files/usr/bin/python3';
$botScript = __DIR__ . '/bot_tiktok_advanced.py';
$statusFile = __DIR__ . '/bot_status.json';
$logFile = __DIR__ . '/bot_terminal.log';
$pidFile = __DIR__ . '/bot.pid';
$historyFile = __DIR__ . '/cookies_history.json';

// Đường dẫn file cookie chuẩn
$cookieFile = '/sdcard/bottiktok/tiktok_cookies.json';

function writeStatus($running, $pid = 0) {
    global $statusFile;
    file_put_contents($statusFile, json_encode(['running' => $running, 'pid' => $pid]));
}

function readStatus() {
    global $statusFile;
    if (!file_exists($statusFile)) return ['running' => false, 'pid' => 0];
    return json_decode(file_get_contents($statusFile), true);
}

function isProcessRunning($pid) {
    if (!$pid) return false;
    $output = shell_exec("ps -p $pid -o pid= 2>/dev/null");
    return !empty(trim($output));
}

function saveHistory($message) {
    global $historyFile;
    $history = [];
    if (file_exists($historyFile)) {
        $history = json_decode(file_get_contents($historyFile), true) ?? [];
    }
    $history[] = [
        'time' => date('Y-m-d H:i:s'),
        'msg' => $message
    ];
    if (count($history) > 50) $history = array_slice($history, -50);
    file_put_contents($historyFile, json_encode($history, JSON_PRETTY_PRINT));
}

$action = isset($_GET['action']) ? $_GET['action'] : '';
$method = $_SERVER['REQUEST_METHOD'];
header('Content-Type: application/json');

// XỬ LÝ UPLOAD FILE JSON
if ($action === 'upload' && $method === 'POST' && isset($_FILES['cookieFile'])) {
    $file = $_FILES['cookieFile'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['status' => 'error', 'error' => 'Lỗi upload: ' . $file['error']]);
        exit;
    }
    // Kiểm tra định dạng JSON
    $content = file_get_contents($file['tmp_name']);
    $json = json_decode($content, true);
    if ($json === null) {
        echo json_encode(['status' => 'error', 'error' => 'File không phải JSON hợp lệ']);
        exit;
    }
    // Tạo thư mục nếu chưa có
    $dir = dirname($cookieFile);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    // Lưu nguyên vẹn, không sửa gì
    if (file_put_contents($cookieFile, $content) === false) {
        echo json_encode(['status' => 'error', 'error' => 'Không thể ghi file cookie']);
        exit;
    }
    saveHistory('Upload cookie file thành công');
    echo json_encode(['status' => 'ok', 'message' => 'Cookie đã được lưu']);
    exit;
}

// CÁC ACTION KHÁC (status, start, stop, log, scan)
switch ($action) {
    case 'status':
        $status = readStatus();
        if ($status['running'] && $status['pid'] && !isProcessRunning($status['pid'])) {
            $status['running'] = false;
            writeStatus(false, 0);
        }
        echo json_encode($status);
        break;

    case 'start':
        $status = readStatus();
        if ($status['running']) {
            echo json_encode(['status' => 'error', 'error' => 'Bot đã chạy']);
            break;
        }
        if (!file_exists($cookieFile)) {
            echo json_encode(['status' => 'error', 'error' => 'Chưa có file cookie, hãy upload trước']);
            break;
        }
        if (file_exists($logFile)) {
            file_put_contents($logFile, "=== BOT KHỞI ĐỘNG ".date('Y-m-d H:i:s')." ===\n");
        }
        $cmd = "nohup $pythonPath $botScript --mode reply >> $logFile 2>&1 & echo $!";
        $pid = (int) shell_exec($cmd);
        if ($pid > 0) {
            writeStatus(true, $pid);
            file_put_contents($pidFile, $pid);
            echo json_encode(['status' => 'ok', 'pid' => $pid]);
        } else {
            echo json_encode(['status' => 'error', 'error' => 'Không thể khởi động bot']);
        }
        break;

    case 'stop':
        $status = readStatus();
        if (!$status['running']) {
            echo json_encode(['status' => 'error', 'error' => 'Bot chưa chạy']);
            break;
        }
        if ($status['pid']) {
            shell_exec("kill -9 {$status['pid']} 2>/dev/null");
            shell_exec("pkill -P {$status['pid']} 2>/dev/null");
        }
        writeStatus(false, 0);
        if (file_exists($pidFile)) unlink($pidFile);
        file_put_contents($logFile, "=== BOT DỪNG ".date('Y-m-d H:i:s')." ===\n", FILE_APPEND);
        echo json_encode(['status' => 'ok']);
        break;

    case 'log':
        header('Content-Type: text/plain');
        if (!file_exists($logFile)) {
            echo "Chưa có log.\n";
            break;
        }
        $lines = file($logFile);
        $lines = array_slice($lines, -150);
        echo implode('', $lines);
        break;

    case 'scan':
        if (!file_exists($cookieFile)) {
            echo json_encode(['status' => 'error', 'error' => 'Chưa có file cookie, hãy upload trước']);
            break;
        }
        $cmd = escapeshellcmd("$pythonPath $botScript --mode scan");
        $output = shell_exec($cmd . ' 2>&1');
        $data = json_decode($output, true);
        if ($data) {
            echo json_encode($data);
        } else {
            echo json_encode(['status' => 'error', 'error' => $output]);
        }
        break;

    default:
        echo json_encode(['status' => 'error', 'error' => 'Action không hợp lệ']);
}
