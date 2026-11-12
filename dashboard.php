<?php
session_start();
require_once 'db.php';

// Bắt buộc đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); 
    exit;
}

// Truy xuất toàn bộ CSDL (Việc lọc "Của tôi" hay "Tất cả" sẽ do bộ lọc thông minh ở Frontend xử lý)
$stmt = $pdo->query("SELECT * FROM installment_profiles ORDER BY created_at DESC");
$db_profiles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy tên người tạo
foreach ($db_profiles as &$profile) {
    $stmtUser = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
    $stmtUser->execute([$profile['FK_created_by']]);
    $profile['creatorName'] = $stmtUser->fetchColumn() ?: 'Hệ thống';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Hệ thống Back-office Xét duyệt Trả góp</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --bg-body: #f8fafc; --bg-surface: #ffffff; 
            --border-light: #e2e8f0; --border-dark: #cbd5e1;
            --text-heading: #0f172a; --text-main: #334155; --text-muted: #64748b; 
            --primary: #2563eb; --primary-hover: #1d4ed8; 
            --success: #10b981; --warning: #f59e0b; --danger: #ef4444;
            --shadow-sm: 0 1px 2px rgba(0,0,0,0.05);
            --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.1);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background-color: var(--bg-body); color: var(--text-main); line-height: 1.6; }

        /* NAVBAR */
        .navbar { background-color: var(--bg-surface); height: 64px; display: flex; justify-content: space-between; align-items: center; padding: 0 32px; border-bottom: 1px solid var(--border-light); position: sticky; top: 0; z-index: 100; box-shadow: var(--shadow-sm); }
        .nav-brand { font-size: 18px; font-weight: 800; color: var(--text-heading); display: flex; align-items: center; gap: 10px; text-decoration: none; transition: opacity 0.2s; }
        .nav-brand:hover { opacity: 0.8; }
        .nav-brand .icon-bg { background: linear-gradient(135deg, #0ea5e9, #2563eb); color: white; width: 36px; height: 36px; border-radius: 10px; display: flex; justify-content: center; align-items: center; box-shadow: 0 2px 4px rgba(37, 99, 235, 0.3); }
        
        .container { max-width: 1280px; margin: 32px auto; padding: 0 24px; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .page-title { font-size: 24px; font-weight: 800; color: var(--text-heading); display: flex; align-items: center; gap: 10px; }

        /* STATS CARDS */
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 24px; margin-bottom: 32px; }
        .stat-card { background: var(--bg-surface); border-radius: 16px; padding: 24px; box-shadow: var(--shadow-sm); border: 1px solid var(--border-light); display: flex; justify-content: space-between; align-items: center; transition: all 0.3s ease; }
        .stat-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-md); border-color: var(--border-dark); }
        .stat-info { display: flex; flex-direction: column; gap: 6px; }
        .stat-label { font-size: 14px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; }
        .stat-value { font-size: 32px; font-weight: 800; color: var(--text-heading); line-height: 1; }
        .stat-icon { width: 56px; height: 56px; border-radius: 16px; display: flex; justify-content: center; align-items: center; font-size: 28px; }
        .icon-blue { background: #eff6ff; color: #3b82f6; }
        .icon-yellow { background: #fffbeb; color: #f59e0b; }
        .icon-green { background: #f0fdf4; color: #22c55e; }
        .icon-red { background: #fef2f2; color: #ef4444; }

        /* TOOLBAR */
        .toolbar { background: var(--bg-surface); padding: 16px 24px; border-radius: 16px; box-shadow: var(--shadow-sm); border: 1px solid var(--border-light); margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; }
        .filter-group { display: flex; gap: 16px; align-items: center; flex: 1; }
        .search-box { position: relative; width: 100%; max-width: 350px; }
        .search-box i { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--text-muted); }
        .search-box input { width: 100%; padding: 10px 16px 10px 44px; border: 1px solid var(--border-dark); border-radius: 8px; font-size: 14px; outline: none; transition: all 0.2s; }
        .search-box input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1); }
        .form-select { padding: 10px 36px 10px 16px; border: 1px solid var(--border-dark); border-radius: 8px; font-size: 14px; font-weight: 500; outline: none; background: #fff url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%2364748b' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e") no-repeat right 12px center/10px 10px; appearance: none; cursor: pointer; transition: border-color 0.2s; }
        .form-select:focus { border-color: var(--primary); }
        
        .action-group { display: flex; gap: 12px; }
        .btn { padding: 10px 20px; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; border: none; display: flex; align-items: center; gap: 8px; text-decoration: none; transition: all 0.2s ease; }
        .btn-outline { background: white; border: 1px solid var(--border-dark); color: var(--text-main); }
        .btn-outline:hover { background: #f1f5f9; border-color: var(--text-muted); }
        .btn-primary { background: var(--primary); color: white; box-shadow: 0 2px 4px rgba(37, 99, 235, 0.3); }
        .btn-primary:hover { background: var(--primary-hover); transform: translateY(-1px); box-shadow: 0 4px 6px rgba(37, 99, 235, 0.4); }

        /* DATA TABLE */
        .table-container { background: var(--bg-surface); border-radius: 16px; box-shadow: var(--shadow-sm); border: 1px solid var(--border-light); overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 900px; }
        th, td { padding: 16px 24px; text-align: left; border-bottom: 1px solid var(--border-light); }
        th { background: #f8fafc; font-size: 13px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; position: sticky; top: 0; }
        tbody tr { transition: background-color 0.2s ease; }
        tbody tr:hover { background-color: #f8fafc; }
        td { font-size: 14.5px; font-weight: 500; color: var(--text-heading); }
        
        .id-cell { font-family: 'Consolas', monospace; color: var(--primary); font-weight: 700; font-size: 14px; background: #eff6ff; padding: 4px 8px; border-radius: 6px; display: inline-block;}
        
        /* Modern Status Badges */
        .status-badge { display: inline-flex; align-items: center; gap: 8px; padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 600; }
        .status-pending { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
        .status-pending::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: #f59e0b; box-shadow: 0 0 0 2px #fef3c7, 0 0 0 3px #f59e0b; }
        
        .status-approved { background: #dcfce3; color: #15803d; border: 1px solid #bbf7d0; }
        .status-approved::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: #22c55e; box-shadow: 0 0 0 2px #dcfce3, 0 0 0 3px #22c55e; }
        
        .status-rejected { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
        .status-rejected::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: #ef4444; box-shadow: 0 0 0 2px #fee2e2, 0 0 0 3px #ef4444; }
        
        .action-cell { display: flex; gap: 8px; }
        .btn-icon { width: 36px; height: 36px; border-radius: 8px; display: flex; justify-content: center; align-items: center; background: white; border: 1px solid var(--border-light); color: var(--text-muted); text-decoration: none; font-size: 16px; transition: all 0.2s; box-shadow: var(--shadow-sm); }
        .btn-icon:hover { transform: translateY(-2px); box-shadow: var(--shadow-md); }
        .btn-view { color: var(--primary); }
        .btn-view:hover { background: #eff6ff; border-color: #bfdbfe; color: #1d4ed8; }
        .btn-edit { color: #8b5cf6; }
        .btn-edit:hover { background: #f5f3ff; border-color: #ddd6fe; color: #7c3aed; }

        /* PAGINATION */
        .pagination { display: flex; justify-content: flex-end; align-items: center; gap: 8px; padding: 16px 24px; background: var(--bg-surface); border-top: 1px solid var(--border-light); }
        .page-btn { padding: 6px 12px; min-width: 36px; height: 36px; border: 1px solid var(--border-light); border-radius: 8px; background: white; font-size: 14px; font-weight: 600; color: var(--text-main); cursor: pointer; transition: 0.2s; display: flex; justify-content: center; align-items: center; }
        .page-btn:hover:not(:disabled) { background: #f1f5f9; border-color: var(--border-dark); }
        .page-btn.active { background: var(--primary); color: white; border-color: var(--primary); box-shadow: 0 2px 4px rgba(37, 99, 235, 0.2); }
        .page-btn:disabled { opacity: 0.5; cursor: not-allowed; }

        .empty-state { padding: 64px 24px; text-align: center; color: var(--text-muted); }
        .empty-state i { font-size: 56px; margin-bottom: 16px; color: var(--border-dark); display: block; }
        .empty-state h3 { font-size: 18px; color: var(--text-heading); margin-bottom: 8px; }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="dashboard.php" class="nav-brand">
            <div class="icon-bg"><i class="bi bi-shield-check"></i></div> BACK-OFFICE
        </a>
        <div style="display: flex; gap: 20px; align-items: center;">
            <div style="text-align: right; line-height: 1.4;">
                <div style="font-size: 14px; font-weight: 700; color: var(--text-heading);"><?php echo htmlspecialchars($_SESSION['full_name']); ?></div>
                <div style="font-size: 12px; color: var(--text-muted);">Quyền: <?php echo $_SESSION['role'] == 'staff' ? 'Nhân viên Tín dụng' : ($_SESSION['role'] == 'manager' ? 'Quản lý' : 'Chuyên viên Thẩm định'); ?></div>
            </div>
            
            <?php if (isset($_SESSION['role']) && ($_SESSION['role'] === 'manager' || $_SESSION['role'] === 'appraiser')): ?>
            <a href="audit_log.php" class="btn btn-outline" style="color: #7e22ce; border-color: #e9d5ff; background: #faf5ff;" title="Xem lịch sử thao tác hệ thống">
                <i class="bi bi-clock-history"></i> Nhật ký hệ thống
            </a>
            <?php endif; ?>

            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'manager'): ?>
            <a href="rule_engine.php" class="btn btn-outline" style="color: #d97706; border-color: #fde68a; background: #fffbeb;">
                <i class="bi bi-gear-fill"></i> Cài đặt Rule
            </a>
            <?php endif; ?>
            
            <a href="logout.php" class="btn btn-outline" style="color: #ef4444; border-color: #fecaca; background: #fef2f2;"><i class="bi bi-box-arrow-right"></i> Đăng xuất</a>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <h1 class="page-title"><i class="bi bi-folder2-open" style="color: var(--primary);"></i> Quản lý hồ sơ xét duyệt</h1>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-info">
                    <div class="stat-label">Tổng hồ sơ (Đang xem)</div>
                    <div class="stat-value" id="stat-total">0</div>
                </div>
                <div class="stat-icon icon-blue"><i class="bi bi-files"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <div class="stat-label">Chờ thẩm định</div>
                    <div class="stat-value" id="stat-pending">0</div>
                </div>
                <div class="stat-icon icon-yellow"><i class="bi bi-hourglass-split"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <div class="stat-label">Đã phê duyệt</div>
                    <div class="stat-value" id="stat-approved">0</div>
                </div>
                <div class="stat-icon icon-green"><i class="bi bi-check2-all"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <div class="stat-label">Đã từ chối</div>
                    <div class="stat-value" id="stat-rejected">0</div>
                </div>
                <div class="stat-icon icon-red"><i class="bi bi-x-octagon"></i></div>
            </div>
        </div>

        <div class="toolbar">
            <div class="filter-group">
                <div class="search-box">
                    <i class="bi bi-search"></i>
                    <input type="text" id="searchInput" placeholder="Tìm theo tên KH hoặc mã HS..." onkeyup="resetPageAndRender()">
                </div>
                <select id="statusFilter" class="form-select" onchange="resetPageAndRender()">
                    <option value="all">Mọi trạng thái</option>
                    <option value="pending">Chờ thẩm định</option>
                    <option value="approved">Đã phê duyệt</option>
                    <option value="rejected">Đã từ chối</option>
                </select>
                
                <!-- BỘ LỌC ĐỘC QUYỀN CHO STAFF (Chuyển đổi Của tôi / Tất cả) -->
                <?php if ($_SESSION['role'] === 'staff'): ?>
                <select id="ownerFilter" class="form-select" style="background-color: #f0fdf4; border-color: #bbf7d0; color: #166534;" onchange="resetPageAndRender()">
                    <option value="mine" selected>📌 Hồ sơ của tôi (Mặc định)</option>
                    <option value="all">Tất cả (Xem chung / Demo)</option>
                </select>
                <?php endif; ?>
            </div>
            <div class="action-group">
                <button class="btn btn-outline" onclick="resetPageAndRender()"><i class="bi bi-arrow-clockwise"></i> Làm mới</button>
                <?php if ($_SESSION['role'] == 'staff' || $_SESSION['role'] == 'manager'): ?>
                <a href="create_profile.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Tạo hồ sơ mới</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Mã Hồ Sơ</th>
                        <th>Khách hàng</th>
                        <th>Thu nhập ròng</th>
                        <th>Khoản vay</th>
                        <th>DTI</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <!-- Data rendered via JS -->
                </tbody>
            </table>
            <!-- Vùng hiển thị phân trang -->
            <div class="pagination" id="pagination"></div>
        </div>
    </div>

    <script>
        const formatVND = (num) => parseInt(num).toLocaleString('vi-VN') + ' đ';
        let currentPage = 1;
        const itemsPerPage = 5;
        const currentRole = '<?php echo $_SESSION['role'] ?? ""; ?>';
        const currentUserId = <?php echo $_SESSION['user_id'] ?? 0; ?>;

        function resetPageAndRender() { currentPage = 1; renderTable(); }
        function changePage(page) { currentPage = page; renderTable(); }

        function renderTable() {
            let db = <?php echo json_encode($db_profiles ?? []); ?>; 
            let tbody = document.getElementById('tableBody');
            let paginationDiv = document.getElementById('pagination');
            
            let search = document.getElementById('searchInput').value.toLowerCase();
            let filter = document.getElementById('statusFilter').value;
            
            // Lấy giá trị bộ lọc "Của tôi" (Nếu có)
            let ownerFilterEl = document.getElementById('ownerFilter');
            let ownerFilter = ownerFilterEl ? ownerFilterEl.value : 'all';

            // Lọc dữ liệu thông minh
            let filteredDB = db.filter(p => {
                let matchSearch = p.customer_name.toLowerCase().includes(search) || p.id.toLowerCase().includes(search);
                let matchStatus = filter === 'all' || p.status === filter;
                
                // Mặc định cho phép hiển thị hết
                let matchOwner = true; 
                // Nếu Staff đang chọn tab "Hồ sơ của tôi", tiến hành lọc ID
                if (currentRole === 'staff' && ownerFilter === 'mine') {
                    matchOwner = (p.FK_created_by == currentUserId);
                }
                
                return matchSearch && matchStatus && matchOwner;
            });

            // Cập nhật thống kê
            let statTotal = filteredDB.length, statPending = 0, statApproved = 0, statRejected = 0;
            filteredDB.forEach(p => {
                if(p.status === 'pending') statPending++;
                if(p.status === 'approved') statApproved++;
                if(p.status === 'rejected') statRejected++;
            });
            document.getElementById('stat-total').innerText = statTotal;
            document.getElementById('stat-pending').innerText = statPending;
            document.getElementById('stat-approved').innerText = statApproved;
            document.getElementById('stat-rejected').innerText = statRejected;

            // Xử lý phân trang
            let totalPages = Math.ceil(filteredDB.length / itemsPerPage);
            if (currentPage > totalPages && totalPages > 0) currentPage = totalPages;
            let paginatedDB = filteredDB.slice((currentPage - 1) * itemsPerPage, currentPage * itemsPerPage);

            if (paginatedDB.length === 0) {
                tbody.innerHTML = `<tr><td colspan="7">
                    <div class="empty-state">
                        <i class="bi bi-inbox"></i>
                        <h3>Không tìm thấy dữ liệu</h3>
                        <p>Chưa có hồ sơ nào khớp với điều kiện tìm kiếm của bạn.</p>
                    </div>
                </td></tr>`;
                paginationDiv.innerHTML = '';
                return;
            }

            let html = '';
            paginatedDB.forEach(p => {
                let dtiNum = parseFloat(p.dti);
                let dtiStyle = dtiNum > 70 ? 'color: var(--danger); font-weight: 700;' : (dtiNum > 50 ? 'color: var(--warning); font-weight: 700;' : 'color: var(--success); font-weight: 700;');
                let statusClass = `status-${p.status}`;
                
                // --- XỬ LÝ NÚT THAO TÁC CỰC CHUẨN ---
                let actionBtns = `<a href="profile_detail.php?id=${p.id}" class="btn-icon btn-view" title="Xem chi tiết"><i class="bi bi-eye"></i></a>`;
                
                let canEdit = false;
                if (p.status === 'pending') {
                    // Manager luôn được sửa mọi pending
                    if (currentRole === 'manager') canEdit = true;
                    // Staff CHỈ được sửa pending CỦA CHÍNH MÌNH
                    if (currentRole === 'staff' && p.FK_created_by == currentUserId) canEdit = true;
                }

                // Nếu thỏa điều kiện trên, hiển thị nút Cây bút màu tím
                if (canEdit) {
                    actionBtns += `<a href="edit_profile.php?id=${p.id}" class="btn-icon btn-edit" title="Chỉnh sửa"><i class="bi bi-pencil-square"></i></a>`;
                }

                html += `
                    <tr>
                        <td>
                            <div class="id-cell"><a href="profile_detail.php?id=${p.id}" style="color:inherit; text-decoration:none;">${p.id}</a></div>
                            <div style="font-size: 12px; color: var(--text-muted); margin-top: 6px;"><i class="bi bi-calendar3"></i> ${p.created_at.substring(0,10)}</div>
                        </td>
                        <td>
                            <a href="profile_detail.php?id=${p.id}" style="font-weight: 700; color: var(--text-heading); text-decoration:none;">${p.customer_name}</a>
                            <div style="font-size: 12.5px; color: var(--text-muted); margin-top: 4px;">Bởi: ${p.creatorName}</div>
                        </td>
                        <td>${formatVND(p.net_income)}</td>
                        <td>${formatVND(p.loan_amount)}</td>
                        <td style="${dtiStyle}">${dtiNum}%</td>
                        <td><span class="status-badge ${statusClass}">${p.status_text}</span></td>
                        <td>
                            <div class="action-cell">${actionBtns}</div>
                        </td>
                    </tr>
                `;
            });
            tbody.innerHTML = html;

            // Nút phân trang
            let paginationHTML = '';
            if (totalPages > 1) {
                paginationHTML += `<button class="page-btn" ${currentPage === 1 ? 'disabled' : ''} onclick="changePage(${currentPage - 1})"><i class="bi bi-chevron-left"></i></button>`;
                for (let i = 1; i <= totalPages; i++) {
                    paginationHTML += `<button class="page-btn ${i === currentPage ? 'active' : ''}" onclick="changePage(${i})">${i}</button>`;
                }
                paginationHTML += `<button class="page-btn" ${currentPage === totalPages ? 'disabled' : ''} onclick="changePage(${currentPage + 1})"><i class="bi bi-chevron-right"></i></button>`;
            }
            paginationDiv.innerHTML = paginationHTML;
        }

        window.onload = renderTable;
    </script>
</body>
</html>