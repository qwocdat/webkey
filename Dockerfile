# Sử dụng hình ảnh cơ sở có sẵn PHP và Apache
FROM php:8.1-apache

# Cài đặt các công cụ hệ thống cần thiết, Python3, pip và các thư viện đồ họa cho Chrome
# Đã lược bỏ hoàn toàn gói libgconf-2-4 bị lỗi thời
RUN apt-get update && apt-get install -y \
    python3 \
    python3-pip \
    python3-venv \
    wget \
    curl \
    gnupg \
    unzip \
    libnss3 \
    libfontconfig1 \
    libxrender1 \
    libxtst6 \
    libxi6 \
    libatk1.0-0 \
    libatk-bridge2.0-0 \
    libcups2 \
    libdrm2 \
    libxkbcommon0 \
    libxcomposite1 \
    libxdamage1 \
    libxrandr2 \
    libgbm1 \
    libasound2 \
    && rm -rf /var/lib/apt/lists/*

# Cài đặt Google Chrome bản ổn định trực tiếp từ gói .deb chính thức của Google
RUN wget -q https://dl.google.com/linux/direct/google-chrome-stable_current_amd64.deb \
    && dpkg -i google-chrome-stable_current_amd64.deb || apt-get install -fy \
    && rm google-chrome-stable_current_amd64.deb

# Cài đặt thư viện Selenium cho Python
RUN pip3 install --no-cache-dir selenium --break-system-packages

# Sao chép toàn bộ mã nguồn vào thư mục web mặc định của Apache
COPY . /var/www/html/

# Tạo thư mục lưu dữ liệu, ảnh chụp màn hình và cấp quyền ghi dữ liệu
RUN mkdir -p /var/www/html/bot_screenshots /sdcard/bottiktok \
    && chown -R www-data:www-data /var/www/html /sdcard/bottiktok \
    && chmod -R 777 /var/www/html /sdcard/bottiktok

# Mở cổng 80 để truy cập web
EXPOSE 80
