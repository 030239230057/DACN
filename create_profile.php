<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    die("<h2 style='color:red; text-align:center; margin-top:50px;'>Lỗi: Chỉ Nhân viên Tín dụng (Staff) mới có quyền tạo hồ sơ mới.</h2>");
}

// XỬ LÝ API POST 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $config = getSysConfig();
    
    $customer_name = $_POST['customer_name'];
    $cccd = $_POST['cccd'];
    $phone = $_POST['phone'];
    $age = (int)$_POST['age'];
    $net_income = (float)$_POST['net_income'];
    $existing_debt = (float)$_POST['existing_debt'];
    $goods_value = (float)$_POST['goods_value'];
    $down_payment = (float)$_POST['down_payment'];
    $term_months = (int)$_POST['term_months'];
    
    // Tự động tính toán (chuẩn nghiệp vụ)
    $loan_amount = $goods_value - $down_payment;
    $down_payment_ratio = ($goods_value > 0) ? ($down_payment / $goods_value * 100) : 0;
    $monthly_installment = ($loan_amount / $term_months) + ($loan_amount * $config['INTEREST_RATE_MONTHLY']);
    $dti = ($net_income > 0) ? (($existing_debt + $monthly_installment) / $net_income * 100) : 0;
    
    // Gắn cờ chính sách
    $flag_age = ($age >= $config['AGE_MIN'] && $age <= $config['AGE_MAX']) ? 1 : 0;
    $flag_down_payment = ($down_payment_ratio >= $config['DOWN_PAYMENT_MIN_PCT']) ? 1 : 0;
    $flag_dti = ($dti <= $config['DTI_MAX_PCT']) ? 1 : 0;

    $profile_id = 'HS-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);

    try {
        $pdo->beginTransaction(); // Giao dịch 1

        $stmt = $pdo->prepare("INSERT INTO installment_profiles (id, FK_created_by, customer_name, cccd, phone, age, net_income, existing_debt, goods_value, down_payment, term_months, occupation, company, tenure_months, product_name, loan_amount, down_payment_ratio, monthly_installment, dti, flag_age, flag_down_payment, flag_dti, interest_rate) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $profile_id, $_SESSION['user_id'], $customer_name, $cccd, $phone, $age, $net_income, $existing_debt, 
            $goods_value, $down_payment, $term_months, $_POST['occupation'], $_POST['company'], 
            (int)$_POST['tenure_months'], $_POST['product_name'], $loan_amount, $down_payment_ratio, 
            $monthly_installment, $dti, $flag_age, $flag_down_payment, $flag_dti, $config['INTEREST_RATE_MONTHLY']
        ]);

        $stmtLog = $pdo->prepare("INSERT INTO audit_logs (FK_profile_id, FK_user_id, action) VALUES (?, ?, 'create')");
        $stmtLog->execute([$profile_id, $_SESSION['user_id']]);

        $pdo->commit();
        echo json_encode(["status" => "success", "id" => $profile_id]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tạo hồ sơ trả góp - Back-office</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --bg-body: #f8fafc; --bg-surface: #ffffff; 
            --border-light: #e2e8f0; --border-dark: #cbd5e1;
            --text-heading: #0f172a; --text-main: #334155; --text-muted: #64748b; 
            --primary: #4f46e5; --primary-hover: #4338ca;
            --success: #10b981; --warning: #f59e0b; --danger: #ef4444;
            --shadow-sm: 0 1px 2px rgba(0,0,0,0.05);
            --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background-color: var(--bg-body); color: var(--text-main); line-height: 1.6; }

        /* NAVBAR */
        .navbar { background-color: var(--bg-surface); height: 64px; display: flex; justify-content: space-between; align-items: center; padding: 0 32px; border-bottom: 1px solid var(--border-light); position: sticky; top: 0; z-index: 100; box-shadow: var(--shadow-sm); }
        .nav-brand { font-size: 18px; font-weight: 800; color: #0ea5e9; display: flex; align-items: center; gap: 10px; text-decoration: none;}
        .nav-brand .icon-bg { background: linear-gradient(135deg, #0ea5e9, #2563eb); color: white; width: 36px; height: 36px; border-radius: 10px; display: flex; justify-content: center; align-items: center; }
        
        .container { max-width: 1000px; margin: 32px auto; padding: 0 24px; animation: fadeIn 0.5s ease-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        .page-header { margin-bottom: 24px; }
        .page-header h1 { font-size: 24px; font-weight: 800; color: var(--text-heading); }

        .card { background: var(--bg-surface); border-radius: 16px; border: 1px solid var(--border-light); box-shadow: var(--shadow-md); overflow: hidden; }
        .card-header { padding: 18px 24px; border-bottom: 1px solid var(--border-light); font-weight: 700; color: var(--text-heading); background: #fcfcfc; display: flex; align-items: center; gap: 8px; }
        .card-body { padding: 24px; }

        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px 20px; }
        .form-group { display: flex; flex-direction: column; gap: 6px; position: relative; }
        .form-label { font-size: 13.5px; font-weight: 600; color: var(--text-heading); }
        .form-label span.req { color: var(--danger); }
        
        .form-control { padding: 12px 16px; border: 1px solid var(--border-dark); border-radius: 8px; font-size: 14.5px; outline: none; transition: 0.2s; }
        .form-control:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1); }
        .form-control[readonly] { background-color: #f1f5f9; font-weight: 700; color: var(--primary); border-color: var(--border-light); cursor: not-allowed; }
        
        /* HIỆU ỨNG LỖI REAL-TIME (Luồng 3a UC-02) */
        .form-control.is-invalid { border-color: var(--danger); box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1); background-color: #fef2f2; }
        .form-control.is-warning { border-color: var(--warning); box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1); background-color: #fffbeb; }
        
        .error-msg, .warning-msg { font-size: 12px; font-weight: 600; display: none; position: absolute; bottom: -18px; left: 0; }
        .error-msg { color: var(--danger); }
        .warning-msg { color: var(--warning); }

        .section-divider { grid-column: span 2; height: 1px; background: var(--border-light); margin: 10px 0; }
        .section-title { grid-column: span 2; font-size: 15px; font-weight: 700; color: var(--text-heading); margin-bottom: 4px; display: flex; align-items: center; gap: 8px; }

        .btn-group { display: flex; gap: 12px; justify-content: flex-end; padding: 20px 24px; background: #f8fafc; border-top: 1px solid var(--border-light); }
        .btn { padding: 12px 24px; border-radius: 8px; font-size: 14.5px; font-weight: 600; cursor: pointer; border: none; transition: 0.2s; display: flex; align-items: center; gap: 8px; text-decoration: none;}
        .btn-cancel { background: white; border: 1px solid var(--border-dark); color: var(--text-main); }
        .btn-cancel:hover { background: #f1f5f9; }
        .btn-submit { background: var(--primary); color: white; box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3); }
        .btn-submit:hover { background: var(--primary-hover); transform: translateY(-1px); }
        .btn-submit:disabled { opacity: 0.5; cursor: not-allowed; transform: none; box-shadow: none; }

        /* MODAL XÁC NHẬN */
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); display: none; justify-content: center; align-items: center; z-index: 1000; opacity: 0; transition: opacity 0.2s; }
        .modal-overlay.active { display: flex; opacity: 1; }
        .modal-box { background: white; border-radius: 12px; padding: 24px; width: 400px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); transform: translateY(20px); transition: transform 0.2s; text-align: center; }
        .modal-overlay.active .modal-box { transform: translateY(0); }
        .modal-box i { font-size: 40px; color: var(--primary); margin-bottom: 12px; }
        .modal-box h3 { font-size: 18px; color: var(--text-heading); margin-bottom: 8px; font-weight: 800; }
        .modal-box p { font-size: 14px; color: var(--text-muted); margin-bottom: 24px; }

        .toast-container { position: fixed; top: 24px; right: 24px; z-index: 9999; display: flex; flex-direction: column; gap: 10px;}
        .toast { min-width: 320px; padding: 16px; border-radius: 12px; background: white; box-shadow: 0 10px 25px rgba(0,0,0,0.1); display: flex; flex-direction: column; gap: 4px; border-left: 4px solid var(--success); animation: slideIn 0.4s forwards; }
        .toast-title { font-weight: 800; font-size: 14px; color: var(--text-heading); display: flex; align-items: center; gap: 8px;}
        .toast-desc { font-size: 13px; color: var(--text-muted); font-weight: 500; padding-left: 24px;}
        @keyframes slideIn { from { transform: translateX(100%); } to { transform: translateX(0); } }

    </style>
</head>
<body>
    <div class="toast-container" id="toastContainer"></div>

    <!-- MODAL XÁC NHẬN (Post-condition UC-02) -->
    <div class="modal-overlay" id="confirmModal">
        <div class="modal-box">
            <i class="bi bi-question-circle-fill"></i>
            <h3>Xác nhận tạo hồ sơ</h3>
            <p>Hồ sơ của khách hàng <strong id="modal-cus-name"></strong> sẽ được khởi tạo với trạng thái mặc định <strong>Chờ thẩm định</strong> và ghi vào CSDL.</p>
            <div style="display: flex; gap: 12px; justify-content: center;">
                <button type="button" class="btn btn-cancel" onclick="closeConfirmModal()">Xem lại</button>
                <button type="button" class="btn btn-submit" onclick="executeSaveProfile()">Xác nhận lưu</button>
            </div>
        </div>
    </div>

    <nav class="navbar">
        <a href="dashboard.php" class="nav-brand">
            <div class="icon-bg"><i class="bi bi-shield-check"></i></div> BACK-OFFICE
        </a>
        <div style="display: flex; gap: 20px; align-items: center;">
            <div style="text-align: right; line-height: 1.4;">
                <div style="font-size: 14px; font-weight: 700; color: var(--text-heading);"><?php echo htmlspecialchars($_SESSION['full_name']); ?></div>
                <div style="font-size: 12px; color: var(--text-muted);">Quyền: Nhân viên Tín dụng</div>
            </div>
            <a href="dashboard.php" class="btn" style="background: #f1f5f9; color: var(--text-main); padding: 8px 12px; font-size: 13.5px;"><i class="bi bi-house-door"></i> Dashboard</a>
            <a href="logout.php" class="btn" style="background: #fef2f2; color: var(--danger); padding: 8px 12px; font-size: 13.5px;"><i class="bi bi-box-arrow-right"></i> Đăng xuất</a>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <h1>Tạo hồ sơ trả góp mới</h1>
            <p style="color: var(--text-muted); font-size: 14px; margin-top: 4px;">Điền đầy đủ thông tin khách hàng. Hệ thống tự động bắt lỗi logic và tính toán chỉ số.</p>
        </div>

        <div class="card">
            <div class="card-header"><i class="bi bi-person-vcard"></i> Thông tin nhập liệu</div>
            <form id="createProfileForm" onsubmit="openConfirmModal(event)">
                <div class="card-body form-grid">
                    <!-- NHÓM THÔNG TIN CÁ NHÂN -->
                    <div class="section-title"><i class="bi bi-person"></i> Thông tin cá nhân</div>
                    <div class="form-group">
                        <label class="form-label">Họ tên khách hàng <span class="req">*</span></label>
                        <input type="text" id="customer_name" class="form-control" placeholder="Nhập họ và tên" required oninput="clearError(this)">
                        <span class="error-msg">Vui lòng nhập họ tên.</span>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Số CCCD / CMND <span class="req">*</span></label>
                        <input type="text" id="cccd" class="form-control" placeholder="Nhập 12 số CCCD" pattern="^0\d{11}$" maxlength="12" required oninput="validateCCCD(this)" title="CCCD phải có 12 số và bắt đầu bằng số 0">
                        <span class="error-msg" id="err-cccd">CCCD không hợp lệ hoặc đang có hồ sơ Chờ thẩm định.</span>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Số điện thoại <span class="req">*</span></label>
                        <input type="text" id="phone" class="form-control" placeholder="Nhập 10 số điện thoại" required pattern="^0\d{9}$" maxlength="10" oninput="clearError(this)" title="Số điện thoại phải có 10 số và bắt đầu bằng số 0">
                        <span class="error-msg">Số điện thoại không hợp lệ.</span>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tuổi (Năm) <span class="req">*</span></label>
                        <input type="number" id="age" class="form-control" placeholder="VD: 25" required oninput="calculateAutoFields()">
                        <span class="error-msg" id="err-age">Tuổi phải lớn hơn 0.</span>
                        <span class="warning-msg" id="warn-age">⚠️ Tuổi ngoài quy định</span>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Nghề nghiệp</label>
                        <input type="text" id="occupation" class="form-control" placeholder="(Tùy chọn) VD: Nhân viên IT">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nơi công tác</label>
                        <input type="text" id="company" class="form-control" placeholder="(Tùy chọn) VD: Công ty TNHH ABC">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Thâm niên làm việc (Tháng)</label>
                        <input type="number" id="tenure_months" class="form-control" placeholder="(Tùy chọn) VD: 24" min="0" oninput="calculateAutoFields()">
                        <span class="error-msg" id="err-tenure">Thâm niên không được âm.</span>
                    </div>

                    <div class="section-divider"></div>

                    <!-- NHÓM TÀI CHÍNH -->
                    <div class="section-title"><i class="bi bi-wallet2"></i> Thông tin Tài chính & Khoản vay</div>
                    <div class="form-group">
                        <label class="form-label">Thu nhập ròng / Tháng (VNĐ) <span class="req">*</span></label>
                        <input type="number" id="net_income" class="form-control" placeholder="Thu nhập thực lãnh" required oninput="calculateAutoFields()">
                        <span class="error-msg" id="err-income">Thu nhập ròng phải lớn hơn 0.</span>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nghĩa vụ nợ hiện tại / Tháng <span class="req">*</span></label>
                        <input type="number" id="existing_debt" class="form-control" placeholder="Nhập 0 nếu không có nợ" value="0" required oninput="calculateAutoFields()">
                        <span class="error-msg" id="err-debt">Dư nợ không được âm.</span>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Sản phẩm vay mua <span class="req">*</span></label>
                        <input type="text" id="product_name" class="form-control" placeholder="Tên sản phẩm" required oninput="clearError(this)">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Kỳ hạn vay <span class="req">*</span></label>
                        <select id="term_months" class="form-control" required onchange="calculateAutoFields()">
                            <option value="6">6 Tháng</option>
                            <option value="9">9 Tháng</option>
                            <option value="12" selected>12 Tháng</option>
                            <option value="18">18 Tháng</option>
                            <option value="24">24 Tháng</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Giá trị hàng hóa (VNĐ) <span class="req">*</span></label>
                        <input type="number" id="goods_value" class="form-control" placeholder="Nhập giá bán niêm yết" required oninput="calculateAutoFields()">
                        <span class="error-msg" id="err-goods">Giá trị hàng hóa phải lớn hơn 0.</span>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Khách trả trước (VNĐ) <span class="req">*</span></label>
                        <input type="number" id="down_payment" class="form-control" placeholder="Số tiền trả trước" required oninput="calculateAutoFields()">
                        <span class="error-msg" id="err-downpay">Tiền trả trước phải < Giá trị hàng hóa.</span>
                        <span class="warning-msg" id="warn-downpay">⚠️ Trả trước dưới quy định.</span>
                    </div>

                    <div class="section-divider"></div>

                    <!-- MOCK CIC MODE -->
                    <div class="form-group" style="grid-column: span 2; background: #f0fdf4; padding: 12px; border-radius: 8px; border: 1px dashed #22c55e;">
                        <label class="form-label" style="color: #15803d;"><i class="bi bi-bug"></i> Chế độ Tester: Giả lập điểm CIC</label>
                        <select id="mock_cic" class="form-control" style="border-color: #86efac; background: white;">
                            <option value="random">Ngẫu nhiên (Tỷ lệ thực tế 75% Đẹp)</option>
                            <option value="good">Ép ra Hồ sơ ĐẸP (Điểm 700 - 850)</option>
                            <option value="bad">Ép ra NỢ XẤU (Điểm 300 - 500)</option>
                        </select>
                    </div>

                    <!-- NHÓM TỰ TÍNH TOÁN BỞI HỆ THỐNG -->
                    <div class="section-title"><i class="bi bi-cpu"></i> Hệ thống tự tính toán </div>
                    <div class="form-group">
                        <label class="form-label">Số tiền đề nghị vay (VNĐ)</label>
                        <input type="text" id="loan_amount" class="form-control" readonly value="0 đ">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Trả góp ước tính / Tháng (VNĐ)</label>
                        <input type="text" id="monthly_installment" class="form-control" readonly value="0 đ">
                    </div>
                    <div class="form-group" style="grid-column: span 2;">
                        <label class="form-label">Tỷ lệ nợ trên thu nhập (DTI)</label>
                        <input type="text" id="dti" class="form-control" readonly value="0%">
                        <span class="warning-msg" id="warn-dti">⚠️ DTI vượt ngưỡng an toàn. Cần cân nhắc kỹ.</span>
                    </div>
                </div>
                
                <div class="btn-group">
                    <button type="button" class="btn btn-cancel" onclick="window.history.back()">Hủy bỏ</button>
                    <button type="submit" class="btn btn-submit" id="btnSubmit"><i class="bi bi-save"></i> Lưu hồ sơ</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Lấy cấu hình từ LocalStorage (Rule Engine)
        function getConfig() {
            return JSON.parse(localStorage.getItem('sys_config')) || {
                AGE_MIN: 18, AGE_MAX: 60, DOWN_PAYMENT_MIN_PCT: 20, DTI_MAX_PCT: 43, INTEREST_RATE_MONTHLY: 0.025
            };
        }

        function formatVND(num) {
            return parseInt(num).toLocaleString('vi-VN') + ' đ';
        }

        function clearError(inputEl) {
            inputEl.classList.remove('is-invalid', 'is-warning');
            const errSpan = inputEl.parentElement.querySelector('.error-msg');
            const warnSpan = inputEl.parentElement.querySelector('.warning-msg');
            if(errSpan) errSpan.style.display = 'none';
            if(warnSpan) warnSpan.style.display = 'none';
        }

        function validateCCCD(inputEl) {
            clearError(inputEl);
            let cccd = inputEl.value.trim();
            if (cccd.length < 12) return; 

            let db = JSON.parse(localStorage.getItem('bo_db')) || [];
            let isDuplicate = db.some(p => p.cccd === cccd && p.status === 'pending');
            
            if (isDuplicate) {
                inputEl.classList.add('is-invalid');
                let errSpan = document.getElementById('err-cccd');
                errSpan.innerText = "Lỗi: Khách hàng này đang có 1 hồ sơ 'Chờ thẩm định'.";
                errSpan.style.display = 'block';
                document.getElementById('btnSubmit').disabled = true;
            } else {
                document.getElementById('btnSubmit').disabled = false;
            }
        }

        function calculateAutoFields() {
            let CONFIG = getConfig();
            let goodsInput = document.getElementById('goods_value');
            let downInput = document.getElementById('down_payment');
            let incomeInput = document.getElementById('net_income');
            let debtInput = document.getElementById('existing_debt');
            let ageInput = document.getElementById('age');
            let dtiInput = document.getElementById('dti');
            let tenureInput = document.getElementById('tenure_months');
            
            [goodsInput, downInput, incomeInput, debtInput, ageInput, dtiInput, tenureInput].forEach(clearError);

            let goodsVal = parseFloat(goodsInput.value) || 0;
            let downPay = parseFloat(downInput.value) || 0;
            let netIncome = parseFloat(incomeInput.value) || 0;
            let existingDebt = parseFloat(debtInput.value) || 0;
            let term = parseInt(document.getElementById('term_months').value) || 12;
            let age = parseInt(ageInput.value) || 0;
            let tenure = tenureInput.value !== "" ? parseInt(tenureInput.value) : 0;

            let hasError = false;

            if (age < 0) { ageInput.classList.add('is-invalid'); document.getElementById('err-age').style.display = 'block'; hasError = true; }
            if (netIncome < 0) { incomeInput.classList.add('is-invalid'); document.getElementById('err-income').style.display = 'block'; hasError = true; }
            if (existingDebt < 0) { debtInput.classList.add('is-invalid'); document.getElementById('err-debt').style.display = 'block'; hasError = true; }
            if (goodsVal < 0) { goodsInput.classList.add('is-invalid'); document.getElementById('err-goods').style.display = 'block'; hasError = true; }
            if (tenure < 0) { tenureInput.classList.add('is-invalid'); document.getElementById('err-tenure').style.display = 'block'; hasError = true; }
            
            if (downPay >= goodsVal && goodsVal > 0) {
                downInput.classList.add('is-invalid');
                document.getElementById('err-downpay').style.display = 'block';
                hasError = true;
            }

            document.getElementById('btnSubmit').disabled = hasError;

            let loanAmt = hasError ? 0 : (goodsVal - downPay);
            document.getElementById('loan_amount').value = formatVND(loanAmt);

            let downPct = goodsVal > 0 ? Math.round((downPay / goodsVal) * 100) : 0;
            if (goodsVal > 0 && downPct < CONFIG.DOWN_PAYMENT_MIN_PCT && !downInput.classList.contains('is-invalid')) {
                downInput.classList.add('is-warning');
                document.getElementById('warn-downpay').style.display = 'block';
            }

            let monthlyInst = (loanAmt > 0 && term > 0) ? ((loanAmt / term) + (loanAmt * CONFIG.INTEREST_RATE_MONTHLY)) : 0;
            document.getElementById('monthly_installment').value = formatVND(monthlyInst);

            if (netIncome > 0 && !hasError) {
                let dti = Math.round(((existingDebt + monthlyInst) / netIncome) * 100);
                dtiInput.value = dti + '%';
                if (dti > CONFIG.DTI_MAX_PCT) {
                    dtiInput.classList.add('is-warning');
                    document.getElementById('warn-dti').style.display = 'block';
                }
            } else {
                dtiInput.value = '0%';
            }

            if (age > 0 && (age < CONFIG.AGE_MIN || age > CONFIG.AGE_MAX) && !ageInput.classList.contains('is-invalid')) {
                ageInput.classList.add('is-warning');
                document.getElementById('warn-age').style.display = 'block';
            }
        }

        function openConfirmModal(e) {
            e.preventDefault(); 
            calculateAutoFields(); 
            
            if (document.getElementById('btnSubmit').disabled) return;
            if (!document.getElementById('createProfileForm').checkValidity()) return;

            document.getElementById('modal-cus-name').innerText = document.getElementById('customer_name').value.toUpperCase();
            document.getElementById('confirmModal').classList.add('active');
        }

        function closeConfirmModal() {
            document.getElementById('confirmModal').classList.remove('active');
        }

        function generateRealisticCIC() {
            let mode = document.getElementById('mock_cic').value;
            if (mode === 'good') return Math.floor(700 + Math.random() * 151); 
            if (mode === 'bad') return Math.floor(300 + Math.random() * 201); 
            
            let rand = Math.random();
            if (rand < 0.10) return Math.floor(300 + Math.random() * 200); 
            else if (rand < 0.25) return Math.floor(501 + Math.random() * 149); 
            else return Math.floor(650 + Math.random() * 200); 
        }

        function executeSaveProfile() {
            closeConfirmModal();
            let CONFIG = getConfig();
            
            let goodsVal = parseFloat(document.getElementById('goods_value').value);
            let downPay = parseFloat(document.getElementById('down_payment').value);
            let loanAmt = goodsVal - downPay;
            
            let newProfile = {
                id: 'HS-' + Math.floor(100 + Math.random() * 900), 
                name: document.getElementById('customer_name').value,
                cccd: document.getElementById('cccd').value,
                phone: document.getElementById('phone').value,
                age: parseInt(document.getElementById('age').value),
                company: document.getElementById('company').value || 'Không cung cấp',
                job: document.getElementById('occupation').value || 'Không cung cấp',
                tenureMonths: parseInt(document.getElementById('tenure_months').value) || 0,
                income: document.getElementById('net_income').value,
                debt: document.getElementById('existing_debt').value,
                assetVal: document.getElementById('goods_value').value,
                downPay: document.getElementById('down_payment').value,
                productName: document.getElementById('product_name').value,
                term: document.getElementById('term_months').value,
                loanAmt: loanAmt.toString(),
                downPct: Math.round((downPay / goodsVal) * 100),
                monthlyInst: (loanAmt / parseInt(document.getElementById('term_months').value)) + (loanAmt * CONFIG.INTEREST_RATE_MONTHLY), // Tính riêng để lưu CSDL
                dti: parseInt(document.getElementById('dti').value.replace('%','')), // Lưu dạng số để xài sau
                creatorName: '<?php echo $_SESSION['full_name']; ?>',
                appraiserName: '',
                status: 'pending',          
                statusText: 'Chờ thẩm định',
                actionReason: '',
                cicScore: generateRealisticCIC(), 
                cicStatus: 'Chưa truy xuất',
                date: new Date().toISOString().split('T')[0]
            };

            let db = JSON.parse(localStorage.getItem('bo_db')) || [];
            db.unshift(newProfile);
            localStorage.setItem('bo_db', JSON.stringify(db));

            let auditLogs = JSON.parse(localStorage.getItem('audit_logs')) || [];
            auditLogs.push({
                id: Date.now(),
                FK_profile_id: newProfile.id,
                FK_user_id: <?php echo $_SESSION['user_id']; ?>,
                action: 'create',
                user_name: '<?php echo $_SESSION['full_name']; ?>', // Lưu thêm để render detail
                reason: '',
                created_at: new Date().toISOString().replace('T', ' ').substring(0, 19)
            });
            localStorage.setItem('audit_logs', JSON.stringify(auditLogs));

            const toast = document.createElement('div');
            toast.className = `toast`;
            toast.innerHTML = `
                <div class="toast-title"><i class="bi bi-check-circle-fill" style="color:var(--success); font-size:18px;"></i> Khởi tạo hồ sơ thành công!</div>
                <div class="toast-desc">Mã hồ sơ: <strong>${newProfile.id}</strong></div>
                <div class="toast-desc">Trạng thái: <span style="color:var(--warning); font-weight:700;">${newProfile.statusText}</span></div>
            `;
            document.getElementById('toastContainer').appendChild(toast);
            
            document.getElementById('createProfileForm').reset();
            setTimeout(() => { window.location.href = 'dashboard.php'; }, 2500);
        }
    </script>
</body>
</html>