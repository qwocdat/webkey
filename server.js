const express = require('express');
const cors = require('cors');
const jwt = require('jsonwebtoken');
const app = express();

const PORT = process.env.PORT || 3000;
const JWT_SECRET = "APP_HUB_SECRET_KEY_123!"; // Khóa bí mật để mã hóa token

app.use(cors());
app.use(express.json());

// ================= DỮ LIỆU GIẢ LẬP (DATABASE LƯU TRONG RAM) =================
let pageViews = 100; // Số lượt xem ban đầu

// Danh sách tài khoản người dùng
let users = [
    { email: "admin@gmail.com", password: "123", isVip: true, vipDownloadsLeft: 99, vipExpiresAt: Date.now() + 24*60*60*1000 },
    { email: "user@gmail.com", password: "123", isVip: false, vipDownloadsLeft: 0, vipExpiresAt: null }
];

// Danh sách ứng dụng trong Store
let apps = {
    "app1": { name: "Facebook Mod No Ads", icon: "https://placehold.co/100x100/ff3b30/fff?text=FB", type: "free", version: "450.0.0", size: "65 MB", downloads: 1420, downloadLink: "https://google.com", description: "Phiên bản Facebook chặn hoàn toàn quảng cáo, lướt mượt mà." },
    "app2": { name: "YouTube Premium", icon: "https://placehold.co/100x100/ff3b30/fff?text=YT", type: "vip", version: "19.05", size: "120 MB", downloads: 850, downloadLink: "https://google.com", description: "Xem video không quảng cáo, mở nhạc chạy nền trong nền tắt màn hình. Yêu cầu tài khoản VIP." },
    "app3": { name: "GTA V Mobile", icon: "https://placehold.co/100x100/ff3b30/fff?text=GTA", type: "coming_soon", version: "0.1", size: "2 GB", downloads: 0, downloadLink: "#", description: "Siêu phẩm bom tấn đang được chuyển thể sang nền tảng di động. Sắp ra mắt!" }
};

// Danh sách mã VIP để kích hoạt (Mã test: vipuseractive-123)
let vipCodes = {
    "vipuseractive-123": { valid: true }
};

// ================= MIDDLEWARE KIỂM TRA ĐĂNG NHẬP (AUTH) =================
const authenticateToken = (req, res, next) => {
    const authHeader = req.headers['authorization'];
    const token = authHeader && authHeader.split(' ')[1];
    
    if (!token) return res.status(0).json({ success: false, message: "Thiếu token đăng nhập" });

    jwt.verify(token, JWT_SECRET, (err, userPayload) => {
        if (err) return res.status(0).json({ success: false, message: "Token hết hạn hoặc không hợp lệ" });
        // Tìm user mới nhất trong bộ nhớ
        const foundUser = users.find(u => u.email === userPayload.email);
        if(!foundUser) return res.status(0).json({ success: false, message: "Không tìm thấy user" });
        req.user = foundUser;
        next();
    });
};

// ================= CÁC API ĐƯỜNG DẪN (ROUTES) =================

// 1. Đăng ký tài khoản
app.post('/api/auth/register', (req, res) => {
    const { email, password } = req.body;
    if (users.find(u => u.email === email)) {
        return res.json({ success: false, message: "Email này đã tồn tại trên hệ thống!" });
    }
    users.push({ email, password, isVip: false, vipDownloadsLeft: 0, vipExpiresAt: null });
    res.json({ success: true });
});

// 2. Đăng nhập tài khoản -> Trả về Token
app.post('/api/auth/login', (req, res) => {
    const { email, password } = req.body;
    const user = users.find(u => u.email === email && u.password === password);
    if (!user) {
        return res.json({ success: false, message: "Sai tài khoản hoặc mật khẩu!" });
    }
    // Tạo token mã hóa chứa email gửi về cho client lưu trữ
    const token = jwt.sign({ email: user.email }, JWT_SECRET, { expiresIn: '7d' });
    res.json({ success: true, token });
});

// 3. Lấy thông tin cá nhân hiện tại
app.get('/api/auth/me', authenticateToken, (req, res) => {
    res.json({ success: true, user: req.user });
});

// 4. Lấy danh sách toàn bộ Apps công khai
app.get('/api/apps', (req, res) => {
    res.json(apps);
});

// 5. Kích hoạt mã VIP
app.post('/api/vip/activate', authenticateToken, (req, res) => {
    const { code } = req.body;
    if (vipCodes[code] && vipCodes[code].valid === true) {
        vipCodes[code].valid = false; // Vô hiệu hóa mã sau khi dùng
        
        req.user.isVip = true;
        req.user.vipDownloadsLeft = 1;
        req.user.vipExpiresAt = Date.now() + (24 * 60 * 60 * 1000); // 24 giờ
        
        res.json({ success: true });
    } else {
        res.json({ success: false, message: "Mã kích hoạt không đúng hoặc đã được sử dụng trước đó!" });
    }
});

// 6. Trừ lượt tải VIP khi bấm Download
app.post('/api/vip/deduct', authenticateToken, (req, res) => {
    if (req.user.isVip && req.user.vipDownloadsLeft > 0) {
        req.user.vipDownloadsLeft -= 1;
        res.json({ success: true });
    } else {
        res.json({ success: false, message: "Hết lượt tải VIP" });
    }
});

// 7. Xử lý khi gói VIP tự động hết hạn
app.post('/api/vip/expire', authenticateToken, (req, res) => {
    req.user.isVip = false;
    req.user.vipDownloadsLeft = 0;
    req.user.vipExpiresAt = null;
    res.json({ success: true });
});

// 8. Hủy gói VIP chủ động
app.post('/api/vip/cancel', authenticateToken, (req, res) => {
    req.user.isVip = false;
    req.user.vipDownloadsLeft = 0;
    req.user.vipExpiresAt = null;
    res.json({ success: true });
});

// 9. Tăng số lượt xem trang web (Page View)
app.post('/api/stats/view', (req, res) => {
    pageViews++;
    console.log(`Tổng lượt xem hiện tại: ${pageViews}`);
    res.json({ success: true, views: pageViews });
});


// Lắng nghe cổng khởi chạy máy chủ
app.listen(PORT, () => {
    console.log(`Backend AppHub đang chạy mượt mà tại cổng: ${PORT}`);
});
