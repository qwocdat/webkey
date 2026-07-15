import os
import sys
import time
import json
import random
import requests

# Khai báo các thư viện Selenium bắt buộc
from selenium import webdriver
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.common.action_chains import ActionChains
from selenium.webdriver.common.keys import Keys

# Import bộ quản lý Driver tự động sửa lỗi môi trường Render Linux
try:
    from webdriver_manager.chrome import ChromeDriverManager
    HAS_WDM = True
except ImportError:
    HAS_WDM = False

# --- CẤU HÌNH ĐƯỜNG DẪN HỆ THỐNG RENDER ---
COOKIE_PATH = "/sdcard/bottiktok/tiktok_cookies.json"
LOG_DIR = "/var/www/html/bot_screenshots"

def get_driver():
    """ Khởi tạo driver tự động tương thích và sửa lỗi vị trí Chrome trên Render """
    options = Options()
    options.add_argument('--headless=new')
    options.add_argument('--no-sandbox')
    options.add_argument('--disable-dev-shm-usage')
    options.add_argument('--disable-gpu')
    options.add_argument('--window-size=1920,1080')
    options.add_argument('--user-agent=Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36')
    
    # Bổ sung cấu hình ép quyền chạy ẩn danh tránh crash
    options.add_argument('--log-level=3')
    options.add_argument('--remote-debugging-port=9222')
    
    # Chỉ định vị trí Google Chrome mặc định của Linux Render nếu tìm thấy
    if os.path.exists("/usr/bin/google-chrome"):
        options.binary_location = "/usr/bin/google-chrome"
        
    # Ép buộc bỏ qua việc ghi đè thư mục cache hệ thống bị lỗi quyền (Permission Denied)
    os.environ['WDM_LOCAL'] = '1'
    os.environ['SE_CACHE_PATH'] = '/tmp/selenium'

    # Cách 1: Sử dụng webdriver_manager tự động cài đặt driver phù hợp
    if HAS_WDM:
        try:
            service = Service(ChromeDriverManager().install())
            driver = webdriver.Chrome(service=service, options=options)
            return driver
        except Exception as e:
            print(f"[HỆ THỐNG LOG] Thử tự động tải driver lỗi: {e}. Đang chuyển sang phương án dự phòng...")

    # Cách 2: Khởi tạo trực tiếp sử dụng Driver hệ thống có sẵn
    try:
        driver = webdriver.Chrome(options=options)
        return driver
    except Exception as e:
        print(f"[HỆ THỐNG LOG] Thử driver hệ thống lỗi: {e}. Chuyển sang phương án cuối...")
        
    # Cách 3: Phương án dự phòng cuối cùng với Service rỗng
    try:
        driver = webdriver.Chrome(service=Service(), options=options)
        return driver
    except Exception as e3:
        print(f"Lỗi nghiêm trọng: Không thể khởi tạo Driver Chrome. Chi tiết: {e3}")
        return None

def load_cookies(driver):
    """ Nạp dữ liệu đăng nhập từ cookie JSON """
    if not os.path.exists(COOKIE_PATH):
        print(f"Lỗi: Không tìm thấy file cookie tại {COOKIE_PATH}")
        return False
    
    try:
        driver.get("https://www.tiktok.com")
        time.sleep(3)
        with open(COOKIE_PATH, 'r') as f:
            cookies = json.load(f)
            for cookie in cookies:
                if 'sameSite' in cookie and cookie['sameSite'] not in ["Strict", "Lax", "None"]:
                    del cookie['sameSite']
                try:
                    driver.add_cookie(cookie)
                except:
                    pass
        driver.refresh()
        time.sleep(5)
        return True
    except Exception as e:
        print(f"Lỗi nạp dữ liệu cookie: {e}")
        return False

def main():
    print("[PROGRESS] 10 - Khởi động hệ thống...")
    driver = get_driver()
    if not driver:
        print("Lỗi: Không thể cấu hình môi trường chạy Chrome ẩn.")
        sys.exit(1)

    print("[PROGRESS] 50 - Đang nạp cookie đăng nhập...")
    if not load_cookies(driver):
        driver.quit()
        sys.exit(1)

    print("[PROGRESS] 100 - Khởi chạy bot thành công.")

    # --- KHU VỰC VÒNG LẶP CHỨC NĂNG CHÍNH CỦA BẠN ---
    count = 0
    while True:
        try:
            # Lưu ảnh trạng thái để hiển thị lên giao diện web công khai công khai
            if not os.path.exists(LOG_DIR):
                os.makedirs(LOG_DIR, exist_ok=True)
            driver.save_screenshot(os.path.join(LOG_DIR, "latest_status.png"))
            
            # Vòng lặp giữ luồng chạy ổn định
            time.sleep(30)
            
        except KeyboardInterrupt:
            print("Đang dừng bot theo lệnh hệ thống...")
            break
        except Exception as e:
            print(f"Lỗi trong tiến trình chạy ngầm: {e}")
            time.sleep(10)
            
    driver.quit()

if __name__ == "__main__":
    main()
