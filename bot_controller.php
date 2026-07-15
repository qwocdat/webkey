<?php
header('Content-Type: application/json; charset=utf-8');

$action = isset($_GET['action']) ? $_GET['action'] : '';

// ĐƯỜNG DẪN PYTHON CHUẨN TRÊN RENDER CLOUD (BỎ QUA TERMUX CŨ)
$pythonPath = "python3";
$botScript = "bot_tiktok_advanced.py";

// Các đường dẫn lưu trữ log và tiến trình trong Docker
$logFile = "/var/www/html/bot_screenshots/bot_output.log";
$pidFile = "/var/www/html/bot_screenshots/bot.pid";

// Đảm bảo thư mục lưu log tồn tại
if (!file_exists('/var/www/html/bot_screenshots')) {
    mkdir('/var/www/html/bot_screenshots', 0777, true);
}

// Hàm kiểm tra xem tiến trình Bot Python còn sống hay không
function isBotRunning() {
    global $pidFile;
    if (!file_exists($pidFile)) return false;
    $pid = trim(file_get_contents($pidFile));
    if (empty($pid)) return false;
    
    // Kiểm tra xem tiến trình PID đó có đang hoạt động trên Linux không
    $res = shell_exec("ps -p $pid");
    if (strpos($res, $pid) !== false) {
        return $pid;
    }
    return false;
}

switch ($action) {
    case 'status':
        $pid = isBotRunning();
        $logContent = file_exists($logFile) ? file_get_contents($logFile) : "Chưa có dữ liệu hoạt động.";
        // Chỉ lấy 10 dòng cuối cùng của log để hiển thị cho nhẹ mượt
        $logLines = explode("\n", trim($logContent));
        $lastLines = array_slice($logLines, -8);
        
        echo json_encode([
            'running' => ($pid !== false),
            'pid' => $pid ? $pid : 0,
            'log' => implode("\n", $lastLines)
        ]);
        break;

    case 'start':
        if (isBotRunning()) {
            echo json_encode(['status' => 'error', 'error' => 'Bot hiện tại đang chạy rồi!']);
            exit;
        }
        
        // Làm sạch file log trước khi chạy phiên mới
        file_put_contents($logFile, "--- Khởi tạo tiến trình Bot mới ---\n");
        
        // Lệnh chạy ngầm nohup bằng python3 gốc của máy chủ Linux Render
        $cmd = "nohup $pythonPath $botScript --mode auto > $logFile 2>&1 & echo $!";
        $pid = trim(shell_exec($cmd));
        
        // Ghi lại PID để quản lý
        file_put_contents($pidFile, $pid);
        
        echo json_encode(['status' => 'ok', 'message' => "Kích hoạt Bot thành công độc lập trên đám mây! (PID: $pid)"]);
        break;

    case 'stop':
        $pid = isBotRunning();
        if (!$pid) {
            echo json_encode(['status' => 'error', 'error' => 'Bot hiện tại đang không chạy.']);
            exit;
        }
        
        // Kill tiến trình chạy ngầm trên Linux
        shell_exec("kill -9 $pid");
        if (file_exists($pidFile)) unlink($pidFile);
        
        echo json_encode(['status' => 'ok', 'message' => 'Đã gửi tín hiệu dừng (Kill) tiến trình Bot thành công!']);
        break;

    case 'scan':
        // Chạy quét thử nhanh trực tiếp, trả kết quả ngay lập tức lên màn hình console
        $cmd = "$pythonPath $botScript --mode scan 2>&1";
        $output = shell_exec($cmd);
        echo json_encode(['status' => 'ok', 'message' => empty($output) ? "Quét hoàn tất (Không có phản hồi lỗi)." : $output]);
        break;

    default:
        echo json_encode(['status' => 'error', 'error' => 'Hành động không hợp lệ']);
        break;
}
