<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    die("<h2 style='color:red; text-align:center; margin-top:50px;'>Lỗi: Bạn không có quyền truy cập.</h2>");
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sửa hồ sơ trả góp - Back-office</title>
    <!-- CSS giữ nguyên y hệt file create_profile.php để đồng bộ giao diện -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root { --bg-body: #f8fafc; --bg-surface: #ffffff; --border-light: #e2e8f0; --border-dark: #cbd5e1; --text-heading: #0f172a; --text-main: #334155; --text-muted: #64748b; --primary: #4f46e5; --primary-hover: #4338ca; --success: #10b981; --warning: #f59e0b; --danger: #ef4444; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background-color: var(--bg-body); color: var(--text-main); line-height: 1.6; }
        .navbar { background-color: var(--bg-surface); height: 64px; display: flex; justify-content: space-between; align-items: center; padding: 0 32px; border-bottom: 1px solid var(--border-light); }
        .container { max-width: 1000px; margin: 32px auto; padding: 0 24px; }
        .card { background: var(--bg-surface); border-radius: 16px; border: 1px solid var(--border-light); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); overflow: hidden; }
        .card-header { padding: 18px 24px; border-bottom: 1px solid var(--border-light); font-weight: 700; color: var(--text-heading); background: #fcfcfc; display: flex; align-items: center; gap: 8px; }
        .card-body { padding: 24px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px 20px; }
        .form-group { display: flex; flex-direction: column; gap: 6px; position: relative; }
        .form-label { font-size: 13.5px; font-weight: 600; }
        .form-control { padding: 12px 16px; border: 1px solid var(--border-dark); border-radius: 8px; font-size: 14.5px; outline: none; }
        .form-control:focus { border-color: var(--primary); }
        .form-control[readonly] { background-color: #f1f5f9; cursor: not-allowed; }
        .form-control.is-invalid { border-color: var(--danger); background-color: #fef2f2; }
        .form-control.is-warning { border-color: var(--warning); background-color: #fffbeb; }
        .error-msg, .warning-msg { font-size: 12px; font-weight: 600; display: none; position: absolute; bottom: -18px; left: 0; }
        .error-msg { color: var(--danger); }
        .warning-msg { color: var(--warning); }
        .section-title { grid-column: span 2; font-size: 15px; font-weight: 700; margin-bottom: 4px; border-bottom: 1px solid var(--border-light); padding-bottom: 8px;}
        .btn-group { display: flex; gap: 12px; justify-content: flex-end; padding: 20px 24px; background: #f8fafc; border-top: 1px solid var(--border-light); }
        .btn { padding: 12px 24px; border-radius: 8px; font-size: 14.5px; font-weight: 600; cursor: pointer; border: none; display: flex; align-items: center; gap: 8px; }
    </style>
</head>
<body>
    <nav class="navbar">
        <div style="font-weight: 800; color: var(--primary); font-size: 18px;">BACK-OFFICE</div>
        <div style="display: flex; gap: 20px; align-items: center;">
            <div style="text-align: right; line-height: 1.4;">
                <div style="font-size: 14px; font-weight: 700;"><?php echo $_SESSION['full_name']; ?></div>
                <div style="font-size: 12px; color: var(--text-muted);">Quyền: Nhân viên Tín dụng</div>
            </div>
            <a href="dashboard.php" class="btn" style="background: #f1f5f9; color: var(--text-main); padding: 8px 12px; text-decoration:none;"><i class="bi bi-house-door"></i> Dashboard</a>
        </div>
    </nav>

    <div class="container">
        <h1 style="margin-bottom: 24px;">Chỉnh sửa hồ sơ <span id="display-id" style="color:var(--primary);"></span></h1>

        <div class="card">
            <div class="card-header"><i class="bi bi-pencil-square"></i> Cập nhật thông tin</div>
            <form id="editProfileForm">
                <div class="card-body form-grid">
                    <!-- NHÓM THÔNG TIN CÁ NHÂN -->
                    <div class="section-title">Thông tin cá nhân</div>
                    <div class="form-group">
                        <label class="form-label">Họ tên khách hàng</label>
                        <input type="text" id="customer_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">CCCD (Không thể sửa)</label>
                        <input type="text" id="cccd" class="form-control" readonly style="background:#e2e8f0;">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Số điện thoại</label>
                        <input type="text" id="phone" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tuổi (Năm)</label>
                        <input type="number" id="age" class="form-control" required oninput="calculateAutoFields()">
                        <span class="warning-msg" id="warn-age">⚠️ Tuổi ngoài quy định</span>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Nghề nghiệp</label>
                        <input type="text" id="occupation" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nơi công tác</label>
                        <input type="text" id="company" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Thâm niên làm việc (Tháng)</label>
                        <input type="number" id="tenure_months" class="form-control">
                    </div>

                    <div class="section-divider"></div>

                    <!-- NHÓM TÀI CHÍNH -->
                    <div class="section-title">Thông tin Tài chính & Khoản vay</div>
                    <div class="form-group">
                        <label class="form-label">Thu nhập ròng / Tháng (VNĐ)</label>
                        <input type="number" id="net_income" class="form-control" required oninput="calculateAutoFields()">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nghĩa vụ nợ hiện tại / Tháng (VNĐ)</label>
                        <input type="number" id="existing_debt" class="form-control" required oninput="calculateAutoFields()">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Sản phẩm vay mua</label>
                        <input type="text" id="product_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Kỳ hạn vay</label>
                        <select id="term_months" class="form-control" onchange="calculateAutoFields()">
                            <option value="6">6 Tháng</option>
                            <option value="9">9 Tháng</option>
                            <option value="12">12 Tháng</option>
                            <option value="18">18 Tháng</option>
                            <option value="24">24 Tháng</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Giá trị hàng hóa (VNĐ)</label>
                        <input type="number" id="goods_value" class="form-control" required oninput="calculateAutoFields()">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Khách trả trước (VNĐ)</label>
                        <input type="number" id="down_payment" class="form-control" required oninput="calculateAutoFields()">
                        <span class="error-msg" id="err-downpay">Trả trước phải < Giá trị</span>
                        <span class="warning-msg" id="warn-downpay">⚠️ Trả trước dưới mức tối thiểu</span>
                    </div>

                    <div class="section-divider"></div>

                    <!-- NHÓM TỰ TÍNH TOÁN BỞI HỆ THỐNG -->
                    <div class="section-title">Hệ thống tự tính toán</div>
                    <div class="form-group">
                        <label class="form-label">Số tiền đề nghị vay (VNĐ)</label>
                        <input type="text" id="loan_amount" class="form-control" readonly>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tỷ lệ nợ trên thu nhập (DTI)</label>
                        <input type="text" id="dti" class="form-control" readonly>
                        <span class="warning-msg" id="warn-dti">⚠️ DTI vượt ngưỡng an toàn</span>
                    </div>
                </div>
                
                <div class="btn-group">
                    <button type="button" class="btn" style="background: white; border: 1px solid var(--border-dark); color: var(--text-main);" onclick="window.history.back()">Quay lại</button>
                    <button type="button" class="btn" style="background:#fef2f2; color:#ef4444; border:1px solid #fca5a5;" id="btnDelete" onclick="cancelProfile()"><i class="bi bi-trash"></i> Hủy hồ sơ</button>
                    <button type="button" class="btn" style="background: var(--primary); color: white;" id="btnSubmit" onclick="executeEditProfile()"><i class="bi bi-save"></i> Cập nhật</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function getConfig() {
            return JSON.parse(localStorage.getItem('sys_config')) || { AGE_MIN: 18, AGE_MAX: 60, DOWN_PAYMENT_MIN_PCT: 20, DTI_MAX_PCT: 43, INTEREST_RATE_MONTHLY: 0.025 };
        }
        function formatVND(num) { return parseInt(num).toLocaleString('vi-VN') + ' đ'; }

        let currentProfileId = new URLSearchParams(window.location.search).get('id');
        let db = JSON.parse(localStorage.getItem('bo_db')) || [];
        let profileIndex = db.findIndex(p => p.id === currentProfileId);
        
        window.onload = function() {
            if (profileIndex === -1) { alert("Không tìm thấy hồ sơ!"); return window.history.back(); }
            
            let p = db[profileIndex];
            document.getElementById('display-id').innerText = p.id;
            
            // BR4: Khóa nếu không còn pending
            if (p.status !== 'pending') {
                document.querySelectorAll('.form-control').forEach(input => input.setAttribute('readonly', true));
                document.getElementById('term_months').setAttribute('disabled', true);
                document.getElementById('btnSubmit').style.display = 'none';
                document.getElementById('btnDelete').style.display = 'none';
                alert("Hồ sơ này đã chốt (" + p.statusText + "), hệ thống đã khóa chức năng chỉnh sửa.");
            }

            // Fill dữ liệu
            document.getElementById('customer_name').value = p.name;
            document.getElementById('cccd').value = p.cccd;
            document.getElementById('phone').value = p.phone;
            document.getElementById('age').value = p.age;
            document.getElementById('occupation').value = p.job === 'Không cung cấp' ? '' : p.job;
            document.getElementById('company').value = p.company === 'Không cung cấp' ? '' : p.company;
            document.getElementById('tenure_months').value = p.tenureMonths || '';
            document.getElementById('net_income').value = p.income;
            document.getElementById('existing_debt').value = p.debt;
            document.getElementById('product_name').value = p.productName;
            document.getElementById('term_months').value = p.term;
            document.getElementById('goods_value').value = p.assetVal;
            document.getElementById('down_payment').value = p.downPay;
            
            calculateAutoFields();
        };

        function calculateAutoFields() {
            let CONFIG = getConfig();
            document.querySelectorAll('.warning-msg, .error-msg').forEach(e => e.style.display = 'none');
            document.querySelectorAll('.is-invalid, .is-warning').forEach(e => e.classList.remove('is-invalid', 'is-warning'));

            let goodsVal = parseFloat(document.getElementById('goods_value').value) || 0;
            let downPay = parseFloat(document.getElementById('down_payment').value) || 0;
            let netIncome = parseFloat(document.getElementById('net_income').value) || 0;
            let existingDebt = parseFloat(document.getElementById('existing_debt').value) || 0;
            let term = parseInt(document.getElementById('term_months').value) || 12;
            let age = parseInt(document.getElementById('age').value) || 0;

            let hasError = false;
            if (downPay >= goodsVal && goodsVal > 0) {
                document.getElementById('down_payment').classList.add('is-invalid');
                document.getElementById('err-downpay').style.display = 'block';
                hasError = true;
            }
            document.getElementById('btnSubmit').disabled = hasError;

            let loanAmt = hasError ? 0 : (goodsVal - downPay);
            document.getElementById('loan_amount').value = formatVND(loanAmt);

            let downPct = goodsVal > 0 ? Math.round((downPay / goodsVal) * 100) : 0;
            if (goodsVal > 0 && downPct < CONFIG.DOWN_PAYMENT_MIN_PCT && !hasError) {
                document.getElementById('down_payment').classList.add('is-warning');
                document.getElementById('warn-downpay').style.display = 'block';
            }

            let monthlyInst = (loanAmt > 0 && term > 0) ? ((loanAmt / term) + (loanAmt * CONFIG.INTEREST_RATE_MONTHLY)) : 0;

            if (netIncome > 0 && !hasError) {
                let dti = Math.round(((existingDebt + monthlyInst) / netIncome) * 100);
                document.getElementById('dti').value = dti + '%';
                if (dti > CONFIG.DTI_MAX_PCT) {
                    document.getElementById('dti').classList.add('is-warning');
                    document.getElementById('warn-dti').style.display = 'block';
                }
            }
            if (age > 0 && (age < CONFIG.AGE_MIN || age > CONFIG.AGE_MAX)) {
                document.getElementById('age').classList.add('is-warning');
                document.getElementById('warn-age').style.display = 'block';
            }
        }

        function executeEditProfile() {
            if(!document.getElementById('editProfileForm').checkValidity()) {
                document.getElementById('editProfileForm').reportValidity();
                return;
            }
            let CONFIG = getConfig();
            let goodsVal = parseFloat(document.getElementById('goods_value').value);
            let downPay = parseFloat(document.getElementById('down_payment').value);
            let loanAmt = goodsVal - downPay;

            db[profileIndex].name = document.getElementById('customer_name').value;
            db[profileIndex].phone = document.getElementById('phone').value;
            db[profileIndex].age = parseInt(document.getElementById('age').value);
            db[profileIndex].job = document.getElementById('occupation').value || 'Không cung cấp';
            db[profileIndex].company = document.getElementById('company').value || 'Không cung cấp';
            db[profileIndex].tenureMonths = parseInt(document.getElementById('tenure_months').value) || 0;
            db[profileIndex].income = document.getElementById('net_income').value;
            db[profileIndex].debt = document.getElementById('existing_debt').value;
            db[profileIndex].productName = document.getElementById('product_name').value;
            db[profileIndex].term = document.getElementById('term_months').value;
            db[profileIndex].assetVal = goodsVal;
            db[profileIndex].downPay = downPay;
            db[profileIndex].loanAmt = loanAmt.toString();
            db[profileIndex].downPct = Math.round((downPay / goodsVal) * 100);
            db[profileIndex].monthlyInst = (loanAmt / parseInt(db[profileIndex].term)) + (loanAmt * CONFIG.INTEREST_RATE_MONTHLY);
            db[profileIndex].dti = parseInt(document.getElementById('dti').value.replace('%',''));
            
            localStorage.setItem('bo_db', JSON.stringify(db));

            let auditLogs = JSON.parse(localStorage.getItem('audit_logs')) || [];
            auditLogs.push({ id: Date.now(), FK_profile_id: currentProfileId, FK_user_id: <?php echo $_SESSION['user_id']; ?>, user_name: '<?php echo $_SESSION['full_name']; ?>', action: 'update', reason: 'Nhân viên cập nhật lại thông tin', created_at: new Date().toISOString().replace('T', ' ').substring(0, 19) });
            localStorage.setItem('audit_logs', JSON.stringify(auditLogs));

            alert("Cập nhật hồ sơ thành công!");
            window.location.href = 'dashboard.php';
        }

        function cancelProfile() {
            if(confirm("Xác nhận HỦY hồ sơ này? Hành động này không thể hoàn tác.")) {
                db[profileIndex].status = 'cancelled';
                db[profileIndex].statusText = 'Đã hủy';
                localStorage.setItem('bo_db', JSON.stringify(db));

                let auditLogs = JSON.parse(localStorage.getItem('audit_logs')) || [];
                auditLogs.push({ id: Date.now(), FK_profile_id: currentProfileId, FK_user_id: <?php echo $_SESSION['user_id']; ?>, user_name: '<?php echo $_SESSION['full_name']; ?>', action: 'cancel', reason: 'Khách hàng đổi ý hoặc sai dữ liệu', created_at: new Date().toISOString().replace('T', ' ').substring(0, 19) });
                localStorage.setItem('audit_logs', JSON.stringify(auditLogs));

                alert("Đã hủy hồ sơ!");
                window.location.href = 'dashboard.php';
            }
        }
    </script>
</body>
</html>