const express = require('express');
const cors = require('cors');
const app = express();
const PORT = process.env.PORT || 3000; // Render sẽ tự cấp cổng ở đây

app.use(cors());
app.use(express.json());

// Đây là một API đơn giản trả về một câu chào và danh sách sản phẩm
app.get('/api/products', (req, res) => {
    const data = [
        { id: 1, name: 'Sản phẩm thử nghiệm 1', price: '100.000đ' },
        { id: 2, name: 'Sản phẩm thử nghiệm 2', price: '200.000đ' }
    ];
    res.json(data);
});

app.listen(PORT, () => {
    console.log(`Backend đang chạy ở cổng ${PORT}`);
});
