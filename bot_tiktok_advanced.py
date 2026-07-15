import os
import sys
import time
import json
import random

# CHỈ IMPORT CÁC THƯ VIỆN CÓ SẴN TRONG PYTHON VÀ SELENIUM MẶC ĐỊNH
from selenium import webdriver
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.common.action_chains import ActionChains
from selenium.webdriver.common.keys import Keys

# --- CẤU HÌNH ĐƯỜNG DẪN HỆ THỐNG RENDER ---
COOKIE_PATH = "/sdcard/bottiktok/tiktok_cookies.json"
LOG_DIR = "/var/www/html/bot_screenshots"
LOG_FILE = "/var/www/html/bot_screenshots/bot_output.log"

def write_log(message):
    print(message)
    try:
        if not os.path.exists(LOG_DIR):
            os.makedirs(LOG_DIR, exist_ok=True)
        with open(LOG_FILE, "a", encoding="utf-8") as f:
            f.write(message + "\n")
    except:
        pass

def get_driver():
    """ Khởi tạo driver bằng Chrome + Chromedriver mặc định của Render, bỏ qua WDM """
    options = Options()
    options.add_argument('--headless=new')
    options.add_argument('--no-sandbox')
    options.add_argument('--disable-dev-shm-usage')
    options.add_argument('--disable-gpu')
    options.add_argument('--disable-software-rasterizer')
    options.add_argument('--disable-extensions')
    options.add_argument('--window-size=1920,1080')
    options.add_argument('--user-agent=Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36')
    
    # Ép buộc Selenium dùng thư mục /tmp công khai để tránh lỗi Permission denied khi tạo cache
    os.environ['SE_CACHE_PATH'] = '/tmp/selenium'

    # Chỉ định vị trí Chrome trên Render
    if os.path.exists("/usr/bin/google-chrome"):
        options.binary_location = "/usr/bin/google-chrome"

    # Chạy trực tiếp bằng driver mặc định của hệ thống
    try:
        driver = webdriver.Chrome(options=options)
        return driver
    except Exception as e:
        write_log(f"[LOG Dự Phòng 1] Khởi động trực tiếp lỗi: {e}")

    try:
        driver = webdriver.Chrome(service=Service(), options=options)
        return driver
    except Exception as e2:
        write_log(f"Lỗi hệ thống nghiêm trọng (Không tìm thấy Chromedriver): {e2}")
        return None

def load_cookies(driver):
    if not os.path.exists(COOKIE_PATH):
        write_log(f"Lỗi: Không tìm thấy file cookie tại {COOKIE_PATH}")
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
        write_log(f"Lỗi nạp dữ liệu cookie: {e}")
        return False

def main():
    # Làm sạch file log cũ khi bắt đầu bật
    try:
        if os.path.exists(LOG_FILE):
            os.remove(LOG_FILE)
    except:
        pass

    write_log("[PROGRESS] 10 - Khởi động hệ thống...")
    driver = get_driver()
    if not driver:
        write_log("Lỗi: Không thể cấu hình môi trường chạy Chrome ẩn.")
        sys.exit(1)

    write_log("[PROGRESS] 50 - Đang nạp cookie đăng nhập...")
    if not load_cookies(driver):
        driver.quit()
        sys.exit(1)

    write_log("[PROGRESS] 100 - Khởi chạy bot thành công.")

    # --- KHU VỰC VÒNG LẶP CHẠY CHỨC NĂNG CHÍNH CỦA BẠN ---
    while True:
        try:
            if not os.path.exists(LOG_DIR):
                os.makedirs(LOG_DIR, exist_ok=True)
            driver.save_screenshot(os.path.join(LOG_DIR, "latest_status.png"))
            time.sleep(30)
        except KeyboardInterrupt:
            break
        except Exception as e:
            write_log(f"Lỗi trong tiến trình chạy ngầm: {e}")
            time.sleep(10)
            
    driver.quit()

if __name__ == "__main__":
    main()
