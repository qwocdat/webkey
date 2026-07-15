# Sử dụng hình ảnh cơ sở có sẵn PHP và Apache
FROM php:8.1-apache

# Cài đặt các công cụ hệ thống, Python3 và các thư viện cần thiết cho Chrome
RUN apt-get update && apt-get install -y \
    python3 \
    python3-pip \
    python3-venv \
    wget \
    gnupg \
    unzip \
    libnss3 \
    libgconf-2-4 \
    libfontconfig1 \
    libxrender1 \
    libxtst6 \
    libxi6 \
    && rm -rf /var/lib/apt/lists/*

# Cài đặt Google Chrome bản ổn định mới nhất cho Linux
RUN wget -q -O - https://dl-ssl.google.com/linux/linux_signing_key.pub | apt-key add - \
    && echo "deb [arch=amd64] http://dl.google.com/linux/chrome/deb/ stable main" >> /etc/apt/sources.list.d/google-chrome.list \
    && apt-get update \
    && apt-get install -y google-chrome-stable \
    && rm -rf /var/lib/apt/lists/*

# Cài đặt thư viện Selenium cho Python
RUN pip3 install --no-cache-dir selenium --break-system-packages

# Sao chép toàn bộ mã nguồn của bạn vào thư mục web mặc định của Apache
COPY . /var/www/html/

# Tạo thư mục lưu dữ liệu, ảnh chụp màn hình và cấp quyền cho Web Server ghi dữ liệu
RUN mkdir -p /var/www/html/bot_screenshots /sdcard/bottiktok \
    && chown -R www-data:www-data /var/www/html /sdcard/bottiktok \
    && chmod -R 777 /var/www/html /sdcard/bottiktok

# Mở cổng 80 để bên ngoài có thể truy cập trang web của bạn
EXPOSE 80
