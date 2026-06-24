const express = require('express');
const cors = require('cors');
const jwt = require('jsonwebtoken'); 
const app = express();

const PORT = process.env.PORT || 3000;
const JWT_SECRET = "APP_HUB_SECRET_KEY_123!"; 

app.use(cors());
app.use(express.json());

// ================= THÊM ĐOẠN NÀY ĐỂ HẾT LỖI CANNOT GET / =================
app.get('/', (req, res) => {
    res.send(`
        <div style="font-family: sans-serif; text-align: center; padding: 50px; background: #000; color: #fff; min-height: 100vh;">
            <h1 style="color: #ff3b30; font-size: 40px; margin-bottom: 10px;">📱 AppHub Backend</h1>
            <p style="color: #666; font-size: 18px;">Máy chủ API đã kết nối trực tuyến và hoạt động 100% mượt mà!</p>
            <div style="margin-top: 30px; display: inline-block; padding: 10px 20px; background: rgba(0,255,0,0.1); border: 1px solid #00ff00; color: #00ff00; border-radius: 20px; font-weight: bold;">
                ● STATUS: LIVE
            </div>
        </div>
    `);
});

// ================= DATABASE GIẢ LẬP TRÊN RAM =================
let pageViews = 100;
let users = [
    { email: "admin@gmail.com", password: "123", isVip: true, vipDownloadsLeft: 99, vipExpiresAt: Date.now() + 24*60*60*1000 },
    { email: "user@gmail.com", password: "123", isVip: false, vipDownloadsLeft: 0, vipExpiresAt: null }
];

let apps = {
    "app1": { name: "Facebook Mod No Ads", icon: "https://placehold.co/100x100/ff3b30/fff?text=FB", type: "free", version: "450.0.0", size: "65 MB", downloads: 1420, downloadLink: "https://google.com", description: "Phiên bản Facebook chặn hoàn toàn quảng cáo, lướt mượt mà." },
    "app2": { name: "YouTube Premium", icon: "https://placehold.co/100x100/ff3b30/fff?text=YT", type: "vip", version: "19.05", size: "120 MB", downloads: 850, downloadLink: "https://google.com", description: "Xem video không quảng cáo, mở nhạc chạy nền trong nền tắt màn hình. Yêu cầu tài khoản VIP." },
    "app3": { name: "GTA V Mobile", icon: "https://placehold.co/100x100/ff3b30/fff?text=GTA", type: "coming_soon", version: "0.1", size: "2 GB", downloads: 0, downloadLink: "#", description: "Siêu phẩm bom tấn đang được chuyển thể sang nền tảng di động. Sắp ra mắt!" }
};

let vipCodes = {
    "vipuseractive-123": { valid: true }
};

// ================= MIDDLEWARE AUTH =================
const authenticateToken = (req, res, next) => {
    const authHeader = req.headers['authorization'];
    const token = authHeader && authHeader.split(' ')[1];
    
    if (!token) return res.status(401).json({ success: false, message: "Thiếu token đăng nhập" });

    jwt.verify(token, JWT_SECRET, (err, userPayload) => {
        if (err) return res.status(403).json({ success: false, message: "Token hết hạn hoặc không hợp lệ" });
        const foundUser = users.find(u => u.email === userPayload.email);
        if(!foundUser) return res.status(404).json({ success: false, message: "Không tìm thấy user" });
        req.user = foundUser;
        next();
    });
};

// ================= API ROUTES =================

app.post('/api/auth/register', (req, res) => {
    const { email, password } = req.body;
    if (users.find(u => u.email === email)) {
        return res.json({ success: false, message: "Email này đã tồn tại trên hệ thống!" });
    }
    users.push({ email, password, isVip: false, vipDownloadsLeft: 0, vipExpiresAt: null });
    res.json({ success: true });
});

app.post('/api/auth/login', (req, res) => {
    const { email, password } = req.body;
    const user = users.find(u => u.email === email && u.password === password);
    if (!user) {
        return res.json({ success: false, message: "Sai tài khoản hoặc mật khẩu!" });
    }
    const token = jwt.sign({ email: user.email }, JWT_SECRET, { expiresIn: '7d' });
    res.json({ success: true, token });
});

app.get('/api/auth/me', authenticateToken, (req, res) => {
    res.json({ success: true, user: req.user });
});

app.get('/api/apps', (req, res) => {
    res.json(apps);
});

app.post('/api/vip/activate', authenticateToken, (req, res) => {
    const { code } = req.body;
    if (vipCodes[code] && vipCodes[code].valid === true) {
        vipCodes[code].valid = false; 
        req.user.isVip = true;
        req.user.vipDownloadsLeft = 1;
        req.user.vipExpiresAt = Date.now() + (24 * 60 * 60 * 1000); 
        res.json({ success: true });
    } else {
        res.json({ success: false, message: "Mã kích hoạt không đúng hoặc đã được sử dụng trước đó!" });
    }
});

app.post('/api/vip/deduct', authenticateToken, (req, res) => {
    if (req.user.isVip && req.user.vipDownloadsLeft > 0) {
        req.user.vipDownloadsLeft -= 1;
        res.json({ success: true });
    } else {
        res.json({ success: false, message: "Hết lượt tải VIP" });
    }
});

app.post('/api/vip/expire', authenticateToken, (req, res) => {
    req.user.isVip = false;
    req.user.vipDownloadsLeft = 0;
    req.user.vipExpiresAt = null;
    res.json({ success: true });
});

app.post('/api/vip/cancel', authenticateToken, (req, res) => {
    req.user.isVip = false;
    req.user.vipDownloadsLeft = 0;
    req.user.vipExpiresAt = null;
    res.json({ success: true });
});

app.post('/api/stats/view', (req, res) => {
    pageViews++;
    res.json({ success: true, views: pageViews });
});

app.listen(PORT, () => {
    console.log(`Backend AppHub đang chạy mượt mà tại cổng: ${PORT}`);
});
