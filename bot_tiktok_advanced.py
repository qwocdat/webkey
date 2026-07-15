#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
bot_tiktok_advanced.py – Sửa lỗi nạp cookie và tự động chụp màn hình khi quét
"""

import json
import time
import os
import datetime
import random
import sys
import argparse
from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from selenium.common.exceptions import NoSuchElementException

# =========================================================================
# CONFIG & ĐƯỜNG DẪN HỆ THỐNG
# =========================================================================
CHROMEDRIVER_PATH = "/data/data/com.termux/files/usr/bin/chromedriver"
BASE_DIR = "/sdcard/bottiktok"
COOKIE_PATH = f"{BASE_DIR}/tiktok_cookies.json"
CONFIG_PATH = f"{BASE_DIR}/config.json"

# Lưu ảnh màn hình ngay tại thư mục chứa file bot để PHP dễ dàng hiển thị lên Web
CURRENT_DIR = os.path.dirname(os.path.abspath(__file__))
SCREENSHOT_PATH = os.path.join(CURRENT_DIR, "bot_screenshots")

for directory in [BASE_DIR, SCREENSHOT_PATH]:
    if not os.path.exists(directory):
        try:
            os.makedirs(directory)
        except Exception as e:
            print(f"⚠️ Không thể tạo thư mục {directory}: {e}")

DEFAULT_CONFIG = {
    "bot_settings": {
        "scan_interval_seconds": 3,
        "load_page_delay_seconds": 8,
    },
    "auto_replies": [
        "[Bot tự động] Xin chào! Mình đã nhận được tin nhắn.",
        "[Bot tự động] Hiện tại mình không online, bot sẽ hỗ trợ bạn sau nhé!",
        "[Bot tự động] Cảm ơn bạn đã tương tác!",
        "[Bot tự động] Chúc bạn một ngày tốt lành nhé."
    ]
}

def get_config():
    if not os.path.exists(CONFIG_PATH):
        try:
            with open(CONFIG_PATH, "w", encoding="utf-8") as f:
                json.dump(DEFAULT_CONFIG, f, indent=4, ensure_ascii=False)
            return DEFAULT_CONFIG
        except Exception:
            return DEFAULT_CONFIG
    try:
        with open(CONFIG_PATH, "r", encoding="utf-8") as f:
            return json.load(f)
    except Exception:
        return DEFAULT_CONFIG

CONFIG = get_config()

# =========================================================================
# LỚP BOT - SỬA LỖI NẠP COOKIE CHÍ MẠNG
# =========================================================================
class TikTokBot:
    def __init__(self):
        self.driver = None
        self.config = CONFIG
        self.last_replied = ""
        self.stats = {"scans": 0, "replies": 0, "errors": 0}
        self.init_driver()

    def log(self, text, progress=None):
        now = datetime.datetime.now().strftime("%H:%M:%S")
        msg = f"🤖 [{now}] {text}"
        print(msg)
        if progress is not None:
            print(f"[PROGRESS] {progress} - {text}")
        sys.stdout.flush()

    def init_driver(self):
        self.log("➔ Đang thiết lập trình duyệt Headless...", 10)
        options = Options()
        options.add_argument("--headless")
        options.add_argument("--no-sandbox")
        options.add_argument("--disable-dev-shm-usage")
        options.add_argument("--disable-gpu")
        options.add_argument("--window-size=1920,1080")
        options.add_argument("user-agent=Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36")
        try:
            service = Service(executable_path=CHROMEDRIVER_PATH)
            self.driver = webdriver.Chrome(service=service, options=options)
            self.log("✅ Khởi động ChromeDriver thành công!", 25)
        except Exception as e:
            self.log(f"❌ Lỗi driver: {e}", 0)
            sys.exit(1)

    # ========== FIX LỖI IMPORT COOKIES ==========
    def inject_cookies(self):
        if not os.path.exists(COOKIE_PATH):
            self.log(f"❌ Không tìm thấy file cookie tại: {COOKIE_PATH}", 0)
            return False
        
        self.log("➔ Đang mở TikTok để chuẩn bị nạp cookie...", 30)
        self.driver.get("https://www.tiktok.com")
        time.sleep(4)
        
        try:
            with open(COOKIE_PATH, "r", encoding="utf-8") as f:
                data = json.load(f)
                
                # Sửa lỗi định dạng (Hỗ trợ cả List phẳng hoặc Object chứa key 'cookies')
                if isinstance(data, dict):
                    if "cookies" in data:
                        cookies = data["cookies"]
                    elif "cookie" in data:
                        cookies = data["cookie"]
                    else:
                        cookies = [data]
                else:
                    cookies = data
            
            self.log(f"📋 Đang nạp {len(cookies)} cookies...", 40)
            
            # Xóa sạch cookie mặc định tránh xung đột Session
            self.driver.delete_all_cookies()
            success_count = 0

            for cookie in cookies:
                try:
                    clean_cookie = {
                        "name": str(cookie["name"]),
                        "value": str(cookie["value"]),
                        "path": cookie.get("path", "/")
                    }
                    
                    # Giới hạn domain để tránh crash bảo mật Selenium
                    domain = cookie.get("domain", "")
                    if domain:
                        if "tiktok.com" in domain:
                            clean_cookie["domain"] = ".tiktok.com" if domain.startswith(".") else domain
                        else:
                            continue
                    else:
                        clean_cookie["domain"] = ".tiktok.com"

                    if "expiry" in cookie:
                        try:
                            clean_cookie["expiry"] = int(cookie["expiry"])
                        except:
                            pass
                    
                    self.driver.add_cookie(clean_cookie)
                    success_count += 1
                except Exception:
                    continue
            
            self.log(f"⚡ Đã nạp thành công {success_count}/{len(cookies)} cookies.", 45)
            
            # Reload trang để kích hoạt session mới
            self.driver.refresh()
            time.sleep(5)
            
            # Kiểm tra xem có giữ được đăng nhập không
            if "login" in self.driver.current_url:
                self.log("❌ Cookie không hợp lệ hoặc đã hết hạn!", 0)
                return False

            self.log("✅ Đăng nhập bằng Cookie thành công!", 50)
            return True
        except Exception as e:
            self.log(f"❌ Lỗi xử lý cookie: {e}", 0)
            return False

    def handle_response(self, msg):
        m = msg.strip().lower()
        if m in ["/help","help","lenh"]:
            return "[Bot tự động] Lệnh: /ping, /time, /hello"
        if m in ["/ping","ping"]:
            return "[Bot tự động] Pong!"
        if m in ["/time","time"]:
            return f"[Bot tự động] {datetime.datetime.now().strftime('%H:%M:%S %d/%m/%Y')}"
        if m in ["/hello","hello"]:
            return "[Bot tự động] Xin chào!"
        replies = self.config.get("auto_replies", DEFAULT_CONFIG["auto_replies"])
        return random.choice(replies)

    def process_chat(self):
        try:
            input_box = None
            for sel in ["div[role='textbox']", "div[contenteditable='true']", "textarea"]:
                try:
                    el = self.driver.find_element(By.CSS_SELECTOR, sel)
                    if el:
                        input_box = el
                        break
                except:
                    continue
            if not input_box:
                return
            js = """
            let texts = [];
            document.querySelectorAll("span, p").forEach(node => {
                let txt = node.innerText ? node.innerText.trim() : "";
                if (txt && txt.length > 1 && txt.length < 300) texts.push(txt);
            });
            return texts;
            """
            all_texts = self.driver.execute_script(js)
            valid = [t for t in all_texts if not t.startswith("[Bot") and t not in ["Send a message...","Send","Messages","Mute","Delete","Report","Block"]]
            if not valid:
                return
            last = valid[-1]
            if last == self.last_replied:
                return
            self.log(f"💬 Tin nhắn: \"{last}\"")
            reply = self.handle_response(last)
            if reply:
                self.log("⚡ Đang gửi...", 80)
                input_box.click()
                time.sleep(0.1)
                input_box.send_keys(Keys.CONTROL + "a")
                input_box.send_keys(Keys.BACKSPACE)
                time.sleep(0.05)
                input_box.send_keys(reply)
                time.sleep(0.1)
                input_box.send_keys(Keys.ENTER)
                self.log(f"✅ Đã gửi: \"{reply}\"", 100)
                self.stats["replies"] += 1
                self.last_replied = last
                time.sleep(0.5)
        except Exception as e:
            self.stats["errors"] += 1
            self.log(f"⚠️ Lỗi chat: {e}")

    def check_inbox(self):
        try:
            unread = self.driver.find_elements(By.CSS_SELECTOR, "div[data-e2e='message-item-unread'], div[class*='unread'], span[class*='Badge']")
            if unread:
                self.log("🔔 Phát hiện tin nhắn mới!", 60)
                target = unread[0]
                try:
                    parent = target.find_element(By.XPATH, "./ancestor::div[contains(@data-e2e,'chat-item') or @role='button']")
                    self.driver.execute_script("arguments[0].click();", parent)
                except:
                    self.driver.execute_script("arguments[0].click();", target)
                time.sleep(1.5)
                self.last_replied = ""
                self.process_chat()
                self.driver.get("https://www.tiktok.com/messages")
                time.sleep(1.5)
        except Exception as e:
            pass

    # ========== SCAN USERS & CHỤP MÀN HÌNH ==========
    def scan_users(self):
        self.log("🔍 Bắt đầu quét danh sách người dùng...", 10)
        if not self.inject_cookies():
            return {"status": "error", "error": "Cookie không hợp lệ hoặc hết hạn"}
        
        self.driver.get("https://www.tiktok.com/messages")
        time.sleep(self.config["bot_settings"]["load_page_delay_seconds"])
        self.log("📥 Đang lấy danh sách...", 70)
        
        # --- THỰC HIỆN CHỤP MÀN HÌNH ---
        try:
            screenshot_file = os.path.join(SCREENSHOT_PATH, "last_scan.png")
            self.driver.save_screenshot(screenshot_file)
            self.log(f"📸 Đã chụp và lưu ảnh màn hình: last_scan.png", 85)
        except Exception as e:
            self.log(f"⚠️ Không thể chụp ảnh màn hình: {e}")

        if "login" in self.driver.current_url:
            return {"status": "error", "error": "Cookie hết hạn"}
        
        users = []
        try:
            js = """
            let result = [];
            document.querySelectorAll("div[data-e2e='message-item'], div[class*='ChatItem']").forEach(item => {
                let nameEl = item.querySelector("span[class*='Name'], div[class*='name']");
                let name = nameEl ? nameEl.innerText.trim() : "Không tên";
                let id = item.getAttribute("data-user-id") || item.getAttribute("data-id") || "N/A";
                result.push({ name: name, id: id });
            });
            return result;
            """
            users = self.driver.execute_script(js)
            if not users:
                links = self.driver.find_elements(By.CSS_SELECTOR, "a[href*='/@']")
                for link in links:
                    href = link.get_attribute("href")
                    if href and "/messages" not in href:
                        name = link.text.strip() or href.split('/')[-1]
                        users.append({"name": name, "id": href})
        except Exception as e:
            return {"status": "error", "error": str(e)}
        
        seen = set()
        unique = []
        for u in users:
            key = u.get("name","") + u.get("id","")
            if key and key not in seen:
                seen.add(key)
                unique.append(u)
        self.log(f"✅ Tìm thấy {len(unique)} người dùng.", 100)
        return {"status": "ok", "users": unique[:20]}

    def run_reply(self):
        self.log("🚀 KHỞI ĐỘNG BOT REPLY...", 5)
        if not self.inject_cookies():
            self.log("❌ Nạp cookie thất bại. Tiến trình dừng.", 0)
            self.close()
            return
        self.driver.get("https://www.tiktok.com/messages")
        time.sleep(self.config["bot_settings"]["load_page_delay_seconds"])
        if "login" in self.driver.current_url:
            self.log("❌ Cookie hết hạn!", 0)
            self.close()
            return
        self.log("✅ Hệ thống sẵn sàng!", 100)
        try:
            while True:
                self.stats["scans"] += 1
                self.check_inbox()
                time.sleep(self.config["bot_settings"]["scan_interval_seconds"])
        except KeyboardInterrupt:
            self.log("🛑 Dừng bot.")
        finally:
            self.close()

    def close(self):
        if self.driver:
            try:
                self.driver.quit()
            except:
                pass

def main():
    parser = argparse.ArgumentParser()
    parser.add_argument('--mode', default='reply', choices=['reply','scan'])
    args = parser.parse_args()
    bot = TikTokBot()
    if args.mode == 'scan':
        result = bot.scan_users()
        print(json.dumps(result, ensure_ascii=False))
        bot.close()
    else:
        bot.run_reply()

if __name__ == '__main__':
    main()
