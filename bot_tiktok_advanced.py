import os
import sys
import time
import json
import random
from selenium import webdriver
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By

# Thử import webdriver_manager để tự động sửa lỗi driver location
try:
    from webdriver_manager.chrome import ChromeDriverManager
    HAS_WDM = True
except ImportError:
    HAS_WDM = False

# --- CẤU HÌNH ĐƯỜNG DẪN CLOUD RENDER ---
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
    
    # Chỉ định vị trí Google Chrome mặc định của Linux Render
    if os.path.exists("/usr/bin/google-chrome"):
        options.binary_location = "/usr/bin/google-chrome"
        
    # Ép buộc bỏ qua việc ghi đè thư mục cache hệ thống bị lỗi quyền
    os.environ['WDM_LOCAL'] = '1'
    os.environ['SE_CACHE_PATH'] = '/tmp/selenium'

    # Cách 1: Sử dụng webdriver_manager tự động tải driver tương thích
    if HAS_WDM:
        try:
            service = Service(ChromeDriverManager().install())
            driver = webdriver.Chrome(service=service, options=options)
            return driver
        except Exception as e:
            print(f"[HỆ THỐNG] Thử cách 1 lỗi: {e}. Đang chuyển sang cách 2...")

    # Cách 2: Khởi tạo trực tiếp sử dụng Driver hệ thống có sẵn
    try:
        driver = webdriver.Chrome(options=options)
        return driver
    except Exception as e:
        print(f"[HỆ THỐNG] Thử cách 2 lỗi: {e}. Đang chuyển sang cách 3...")
        
    # Cách 3: Phương án dự phòng cuối cùng với Service rỗng
    try:
        driver = webdriver.Chrome(service=Service(), options=options)
        return driver
    except Exception as e3:
        print(f"Lỗi khởi tạo Driver: {e3}")
        return None

def load_cookies(driver):
    """ Nạp dữ liệu đăng nhập từ cookie """
    if not os.path.exists(COOKIE_PATH):
        print(f"Không tìm thấy file cookie tại {COOKIE_PATH}")
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
        print(f"Lỗi nạp cookie: {e}")
        return False

def main():
    print("[PROGRESS] 10 - Khởi động hệ thống...")
    driver = get_driver()
    if not driver:
        print("Lỗi: Không thể cấu hình và tạo môi trường chạy Chrome ẩn.")
        sys.exit(1)

    print("[PROGRESS] 50 - Đang nạp cookie...")
    if not load_cookies(driver):
        driver.quit()
        sys.exit(1)

    print("[PROGRESS] 100 - Khởi chạy bot thành công.")

    # --- ĐOẠN VÒNG LẶP CHẠY CHỨC NĂNG CỦA BẠN ---
    count = 0
    while True:
        try:
            if not os.path.exists(LOG_DIR):
                os.makedirs(LOG_DIR, exist_ok=True)
            driver.save_screenshot(os.path.join(LOG_DIR, "latest_status.png"))
            
            # Giữ luồng hoạt động ngầm ổn định
            time.sleep(30)
            
        except KeyboardInterrupt:
            break
        except Exception as e:
            print(f"Lỗi thực thi: {e}")
            time.sleep(10)
            
    driver.quit()

if __name__ == "__main__":
    main()
