<?php
header('Content-Type: application/json; charset=utf-8');

$action = isset($_GET['action']) ? $_GET['action'] : '';

$pythonPath = "python3";
$botScript = "bot_tiktok_advanced.py";

$logFile = "/var/www/html/bot_screenshots/bot_output.log";
$pidFile = "/var/www/html/bot_screenshots/bot.pid";
$cookieDir = "/sdcard/bottiktok";
$cookieTarget = $cookieDir . "/tiktok_cookies.json";

if (!file_exists('/var/www/html/bot_screenshots')) {
    mkdir('/var/www/html/bot_screenshots', 0777, true);
}
if (!file_exists($cookieDir)) {
    mkdir($cookieDir, 0777, true);
}

function isBotRunning() {
    global $pidFile;
    if (!file_exists($pidFile)) return false;
    $pid = trim(file_get_contents($pidFile));
    if (empty($pid)) return false;
    
    $res = shell_exec("ps -p $pid");
    if (strpos($res, $pid) !== false) {
        return $pid;
    }
    return false;
}

switch ($action) {
    case 'upload_cookie':
        if (!isset($_FILES['cookie_file'])) {
            echo json_encode(['status' => 'error', 'error' => 'Khong tim thay file upload.']);
            exit;
        }
        $file = $_FILES['cookie_file'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['status' => 'error', 'error' => 'Loi upload.']);
            exit;
        }
        if (move_uploaded_file($file['tmp_name'], $cookieTarget)) {
            chmod($cookieTarget, 0777);
            echo json_encode(['status' => 'ok', 'message' => 'Upload thanh cong!']);
        } else {
            echo json_encode(['status' => 'error', 'error' => 'Khong the ghi file.']);
        }
        break;

    case 'status':
        $pid = isBotRunning();
        $logContent = file_exists($logFile) ? file_get_contents($logFile) : "";
        $logLines = explode("\n", trim($logContent));
        $lastLines = array_slice($logLines, -5);
        
        echo json_encode([
            'running' => ($pid !== false),
            'pid' => $pid ? $pid : 0,
            'log' => implode("\n", $lastLines)
        ]);
        break;

    case 'start':
        if (isBotRunning()) {
            echo json_encode(['status' => 'error', 'error' => 'Bot dang chay roi.']);
            exit;
        }
        file_put_contents($logFile, "Khoi tao tien trinh bot...\n");
        
        // FIX: Cấu hình biến môi trường ép Selenium ghi cache vào thư mục /tmp công khai
        $cmd = "export SE_CACHE_PATH=/tmp/selenium && nohup $pythonPath $botScript --mode auto > $logFile 2>&1 & echo $!";
        $pid = trim(shell_exec($cmd));
        file_put_contents($pidFile, $pid);
        
        echo json_encode(['status' => 'ok', 'message' => "Kich hoat bot thanh cong."]);
        break;

    case 'stop':
        $pid = isBotRunning();
        if (!$pid) {
            echo json_encode(['status' => 'error', 'error' => 'Bot dang khong chay.']);
            exit;
        }
        shell_exec("kill -9 $pid");
        if (file_exists($pidFile)) unlink($pidFile);
        echo json_encode(['status' => 'ok', 'message' => 'Da dung tien trinh bot.']);
        break;

    case 'scan':
        // FIX: Thêm biến môi trường cho cả lệnh quét nhanh
        $cmd = "export SE_CACHE_PATH=/tmp/selenium && $pythonPath $botScript --mode scan 2>&1";
        $output = shell_exec($cmd);
        echo json_encode(['status' => 'ok', 'message' => empty($output) ? "Quet hoan tat." : trim($output)]);
        break;

    default:
        echo json_encode(['status' => 'error', 'error' => 'Hanh dong khong hop le.']);
        break;
}
