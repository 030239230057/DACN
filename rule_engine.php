<?php
session_start();

// MOCK SESSION ĐỂ TEST (DÀNH CHO BẠN):
// Nếu bạn muốn test màn hình này mà chưa code xong trang login, hãy xóa các dấu // ở 4 dòng dưới đây:
// if (!isset($_SESSION['user_id'])) {
//     $_SESSION['user_id'] = 3; 
//     $_SESSION['role'] = 'manager'; 
//     $_SESSION['full_name'] = 'Trần Quản Trị (Manager)';
// }

// BẢO MẬT RBAC CỐT LÕI: Chặn mọi chức danh khác ngoại trừ Manager
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'manager') {
    die("<div style='text-align:center; margin-top:50px; font-family:sans-serif;'>
            <h1 style='color:#ef4444;'>🚫 Truy cập bị từ chối!</h1>
            <p style='font-size:18px; color:#334155;'>Chỉ tài khoản cấp <b>Quản lý (Manager)</b> mới có thẩm quyền thay đổi Tham số Rủi ro của hệ thống.</p>
            <br>
            <a href='dashboard.php' style='padding:10px 20px; background:#4f46e5; color:white; text-decoration:none; border-radius:8px; font-weight:600;'>&larr; Quay lại Dashboard</a>
         </div>");
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cài đặt Tham số rủi ro - Rule Engine</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root { --bg-body: #f8fafc; --bg-surface: #ffffff; --border-light: #e2e8f0; --border-dark: #cbd5e1; --text-heading: #0f172a; --text-main: #334155; --text-muted: #64748b; --primary: #4f46e5; --success: #10b981; --warning: #f59e0b; --danger: #ef4444; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background-color: var(--bg-body); color: var(--text-main); line-height: 1.6; }
        .navbar { background-color: var(--bg-surface); height: 64px; display: flex; justify-content: space-between; align-items: center; padding: 0 32px; border-bottom: 1px solid var(--border-light); }
        .container { max-width: 800px; margin: 32px auto; padding: 0 24px; }
        .card { background: var(--bg-surface); border-radius: 16px; border: 1px solid var(--border-light); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        .card-header { padding: 18px 24px; border-bottom: 1px solid var(--border-light); font-weight: 700; font-size: 16px; background: #fcfcfc; color: var(--text-heading); }
        .card-body { padding: 24px; display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-label { font-size: 13.5px; font-weight: 600; color: var(--text-heading); }
        .form-control { padding: 12px 16px; border: 1px solid var(--border-dark); border-radius: 8px; font-size: 14.5px; }
        .form-control:focus { border-color: var(--primary); outline: none; }
        .btn-submit { padding: 12px 24px; border-radius: 8px; font-weight: 600; background: var(--primary); color: white; border: none; cursor: pointer; width: 100%; grid-column: span 2; margin-top: 10px; transition: 0.2s; }
        .btn-submit:hover { background: #4338ca; }
        .info-box { grid-column: span 2; background: #eff6ff; border: 1px dashed #3b82f6; padding: 12px; border-radius: 8px; font-size: 13px; color: #1e3a8a; }
    </style>
</head>
<body>
    <nav class="navbar">
        <div style="font-weight: 800; color: var(--primary); font-size: 18px;"><i class="bi bi-gear-fill"></i> RULE ENGINE</div>
        <div style="display: flex; gap: 20px; align-items: center;">
            <div style="text-align: right; line-height: 1.4;">
                <div style="font-size: 14px; font-weight: 700; color: var(--text-heading);"><?php echo htmlspecialchars($_SESSION['full_name']); ?></div>
                <div style="font-size: 12px; color: var(--text-muted);">Quyền: Quản trị viên hệ thống</div>
            </div>
            <a href="dashboard.php" style="background: #f1f5f9; color: var(--text-main); padding: 8px 12px; font-size: 13.5px; text-decoration:none; border-radius:8px; font-weight:600;"><i class="bi bi-house-door"></i> Dashboard</a>
        </div>
    </nav>

    <div class="container">
        <h1 style="margin-bottom: 20px; font-size:24px; color:var(--text-heading);">Cài đặt Tham số Chính sách (BR5)</h1>
        
        <div class="card">
            <div class="card-header"><i class="bi bi-sliders"></i> Bộ lọc rủi ro tín dụng</div>
            <div class="card-body">
                <div class="info-box"><i class="bi bi-info-circle"></i> Các thay đổi tại đây sẽ được áp dụng ngay lập tức cho các hồ sơ tạo mới hoặc được chỉnh sửa sau thời điểm này. Các hồ sơ cũ giữ nguyên tham số cũ.</div>
                
                <div class="form-group">
                    <label class="form-label">Độ tuổi tối thiểu (Năm)</label>
                    <input type="number" id="AGE_MIN" class="form-control" value="18">
                </div>
                <div class="form-group">
                    <label class="form-label">Độ tuổi tối đa (Năm)</label>
                    <input type="number" id="AGE_MAX" class="form-control" value="60">
                </div>
                <div class="form-group">
                    <label class="form-label">Tỷ lệ trả trước tối thiểu (%)</label>
                    <input type="number" id="DOWN_PAYMENT_MIN_PCT" class="form-control" value="20">
                </div>
                <div class="form-group">
                    <label class="form-label">Ngưỡng nợ DTI tối đa (%)</label>
                    <input type="number" id="DTI_MAX_PCT" class="form-control" value="43">
                </div>
                <div class="form-group" style="grid-column: span 2;">
                    <label class="form-label">Lãi suất trả góp ước tính (% / Tháng)</label>
                    <input type="number" step="0.001" id="INTEREST_RATE_MONTHLY" class="form-control" value="0.025">
                </div>

                <button class="btn-submit" onclick="saveConfig()"><i class="bi bi-check2-circle"></i> Lưu cấu hình hệ thống</button>
            </div>
        </div>
    </div>

    <script>
        // Load cấu hình hiện tại từ LocalStorage
        window.onload = function() {
            let config = JSON.parse(localStorage.getItem('sys_config'));
            if(config) {
                document.getElementById('AGE_MIN').value = config.AGE_MIN;
                document.getElementById('AGE_MAX').value = config.AGE_MAX;
                document.getElementById('DOWN_PAYMENT_MIN_PCT').value = config.DOWN_PAYMENT_MIN_PCT;
                document.getElementById('DTI_MAX_PCT').value = config.DTI_MAX_PCT;
                document.getElementById('INTEREST_RATE_MONTHLY').value = config.INTEREST_RATE_MONTHLY;
            }
        };

        function saveConfig() {
            let newConfig = {
                AGE_MIN: parseInt(document.getElementById('AGE_MIN').value),
                AGE_MAX: parseInt(document.getElementById('AGE_MAX').value),
                DOWN_PAYMENT_MIN_PCT: parseFloat(document.getElementById('DOWN_PAYMENT_MIN_PCT').value),
                DTI_MAX_PCT: parseFloat(document.getElementById('DTI_MAX_PCT').value),
                INTEREST_RATE_MONTHLY: parseFloat(document.getElementById('INTEREST_RATE_MONTHLY').value)
            };
            
            localStorage.setItem('sys_config', JSON.stringify(newConfig));
            alert('Đã cập nhật Rule Engine thành công! Mọi hồ sơ tính toán từ lúc này sẽ áp dụng luật mới.');
        }
    </script>
</body>
</html>