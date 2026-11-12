<?php
session_start();
require_once 'db.php'; // KẾT NỐI DATABASE THẬT

// Chặn nếu chưa đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// BẢO MẬT RBAC: Chặn Staff không được xem Master Logs
if (!isset($_SESSION['role']) || $_SESSION['role'] === 'staff') {
    die("<div style='text-align:center; margin-top:50px; font-family:sans-serif;'>
            <h1 style='color:#ef4444;'>🚫 Truy cập bị từ chối!</h1>
            <p style='font-size:18px; color:#334155;'>Tài khoản của bạn không có quyền xem <b>Nhật ký Hệ thống (Master Audit Logs)</b>.</p>
            <br>
            <a href='dashboard.php' style='padding:10px 20px; background:#8b5cf6; color:white; text-decoration:none; border-radius:8px; font-weight:600; box-shadow: 0 4px 6px -1px rgba(139, 92, 246, 0.3);'>&larr; Quay lại Dashboard</a>
         </div>");
}

// LẤY DỮ LIỆU TỪ DATABASE THẬT 
// Join bảng audit_logs với bảng users để lấy chính xác tên người thực hiện thao tác
$stmt = $pdo->query("
    SELECT a.created_at, a.FK_profile_id, a.action, a.reason, u.full_name as user_name, a.FK_user_id
    FROM audit_logs a
    LEFT JOIN users u ON a.FK_user_id = u.id
    ORDER BY a.created_at DESC
");
$db_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nhật ký hệ thống - Back-office</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --bg-body: #f8fafc; --bg-surface: #ffffff; 
            --border-light: #e2e8f0; --border-dark: #cbd5e1;
            --text-heading: #0f172a; --text-main: #334155; --text-muted: #64748b; 
            --primary: #8b5cf6; --primary-hover: #7c3aed; 
            --shadow-sm: 0 1px 2px rgba(0,0,0,0.05);
            --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.1);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background-color: var(--bg-body); color: var(--text-main); line-height: 1.6; }

        /* NAVBAR */
        .navbar { background-color: var(--bg-surface); height: 64px; display: flex; justify-content: space-between; align-items: center; padding: 0 32px; border-bottom: 1px solid var(--border-light); position: sticky; top: 0; z-index: 100; box-shadow: var(--shadow-sm); }
        .nav-brand { font-size: 18px; font-weight: 800; color: var(--text-heading); display: flex; align-items: center; gap: 10px; text-decoration: none; transition: opacity 0.2s; }
        .nav-brand:hover { opacity: 0.8; }
        .nav-brand .icon-bg { background: linear-gradient(135deg, #8b5cf6, #6d28d9); color: white; width: 36px; height: 36px; border-radius: 10px; display: flex; justify-content: center; align-items: center; box-shadow: 0 2px 4px rgba(139, 92, 246, 0.3); }
        
        .container { max-width: 1200px; margin: 32px auto; padding: 0 24px; }
        .page-header { display: flex; flex-direction: column; gap: 4px; margin-bottom: 24px; }
        .page-title { font-size: 24px; font-weight: 800; color: var(--text-heading); display: flex; align-items: center; gap: 10px;}
        .page-subtitle { color: var(--text-muted); font-size: 14px; margin-left: 38px; }

        /* TOOLBAR */
        .toolbar { background: var(--bg-surface); padding: 16px 24px; border-radius: 16px; box-shadow: var(--shadow-sm); border: 1px solid var(--border-light); margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap; }
        .filter-group { display: flex; gap: 16px; flex: 1; }
        .search-box { position: relative; width: 100%; max-width: 350px; }
        .search-box i { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--text-muted); }
        .search-box input { width: 100%; padding: 10px 16px 10px 40px; border: 1px solid var(--border-dark); border-radius: 8px; font-size: 14px; outline: none; transition: all 0.2s; }
        .search-box input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1); }
        
        .form-select { padding: 10px 36px 10px 16px; border: 1px solid var(--border-dark); border-radius: 8px; font-size: 14px; outline: none; background: #fff url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23334155' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e") no-repeat right 12px center/10px 10px; appearance: none; cursor: pointer; transition: border-color 0.2s; }
        .form-select:focus { border-color: var(--primary); }

        .btn { padding: 10px 20px; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; border: none; display: flex; align-items: center; gap: 8px; text-decoration: none; transition: all 0.2s ease; }
        .btn-outline { background: white; border: 1px solid var(--border-dark); color: var(--text-main); }
        .btn-outline:hover { background: #f1f5f9; border-color: var(--text-muted); }

        /* DATA TABLE */
        .table-container { background: var(--bg-surface); border-radius: 16px; box-shadow: var(--shadow-sm); border: 1px solid var(--border-light); overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 900px; }
        th, td { padding: 16px 24px; text-align: left; border-bottom: 1px solid var(--border-light); }
        th { background: #f8fafc; font-size: 13px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; }
        tbody tr { transition: background-color 0.2s ease; }
        tbody tr:hover { background-color: #f8fafc; }
        td { font-size: 14.5px; font-weight: 500; color: var(--text-heading); }
        
        .action-badge { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 8px; font-size: 12.5px; font-weight: 600; }
        .action-create { background: #e0f2fe; color: #0284c7; border: 1px solid #bae6fd; }
        .action-update { background: #fef3c7; color: #d97706; border: 1px solid #fde68a; }
        .action-approve { background: #dcfce3; color: #15803d; border: 1px solid #bbf7d0; }
        .action-reject { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
        .action-cancel { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }

        .time-cell { font-family: 'Consolas', monospace; font-size: 13.5px; color: var(--text-muted); background: #f8fafc; padding: 4px 8px; border-radius: 6px; border: 1px solid var(--border-light); display: inline-block;}
        .reason-cell { max-width: 300px; font-size: 13.5px; color: var(--text-muted); font-weight: 500;}

        /* PAGINATION */
        .pagination { display: flex; justify-content: flex-end; align-items: center; gap: 8px; padding: 16px 24px; background: var(--bg-surface); border-top: 1px solid var(--border-light); }
        .page-btn { padding: 6px 12px; min-width: 36px; height: 36px; border: 1px solid var(--border-light); border-radius: 8px; background: white; font-size: 14px; font-weight: 600; color: var(--text-main); cursor: pointer; transition: 0.2s; display: flex; justify-content: center; align-items: center; }
        .page-btn:hover:not(:disabled) { background: #f1f5f9; border-color: var(--border-dark); }
        .page-btn.active { background: var(--primary); color: white; border-color: var(--primary); }
        .page-btn:disabled { opacity: 0.5; cursor: not-allowed; }

        .empty-state { padding: 64px 24px; text-align: center; color: var(--text-muted); }
        .empty-state i { font-size: 56px; margin-bottom: 16px; color: var(--border-dark); display: block; }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="dashboard.php" class="nav-brand">
            <div class="icon-bg"><i class="bi bi-clock-history"></i></div> AUDIT LOGS
        </a>
        <div style="display: flex; gap: 20px; align-items: center;">
            <div style="text-align: right; line-height: 1.4;">
                <div style="font-size: 14px; font-weight: 700; color: var(--text-heading);"><?php echo htmlspecialchars($_SESSION['full_name']); ?></div>
                <div style="font-size: 12px; color: var(--text-muted);">Quyền: <?php echo $_SESSION['role'] == 'manager' ? 'Quản trị viên hệ thống' : 'Chuyên viên Thẩm định'; ?></div>
            </div>
            <a href="dashboard.php" class="btn btn-outline" style="border-color: var(--border-light);"><i class="bi bi-house-door"></i> Dashboard</a>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <h1 class="page-title"><i class="bi bi-journal-text" style="color: var(--primary);"></i> Nhật ký hệ thống toàn cục</h1>
            <p class="page-subtitle">Theo dõi và truy xuất toàn bộ lịch sử thao tác từ mọi tài khoản nhân viên và thẩm định</p>
        </div>

        <div class="toolbar">
            <div class="filter-group">
                <div class="search-box">
                    <i class="bi bi-search"></i>
                    <input type="text" id="searchInput" placeholder="Tìm theo Mã HS, người thực hiện..." onkeyup="resetPageAndRender()">
                </div>
                <select id="actionFilter" class="form-select" onchange="resetPageAndRender()">
                    <option value="all">Tất cả thao tác</option>
                    <option value="create">Tạo hồ sơ mới</option>
                    <option value="update">Cập nhật hồ sơ</option>
                    <option value="approve">Phê duyệt</option>
                    <option value="reject">Từ chối</option>
                </select>
            </div>
            <button class="btn btn-outline" onclick="window.location.reload()"><i class="bi bi-arrow-clockwise"></i> Làm mới dữ liệu</button>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Thời gian</th>
                        <th>Người thực hiện</th>
                        <th>Hồ sơ tác động</th>
                        <th>Hành động</th>
                        <th>Ghi chú / Lý do (Trích xuất DB)</th>
                    </tr>
                </thead>
                <tbody id="logBody">
                    <!-- Data rendered via JS -->
                </tbody>
            </table>
            <div class="pagination" id="pagination"></div>
        </div>
    </div>

    <script>
        const actionMap = {
            'create': { text: 'Tạo hồ sơ', class: 'action-create', icon: 'bi-plus-circle' },
            'update': { text: 'Cập nhật', class: 'action-update', icon: 'bi-pencil' },
            'approve': { text: 'Phê duyệt', class: 'action-approve', icon: 'bi-check-circle' },
            'reject': { text: 'Từ chối', class: 'action-reject', icon: 'bi-x-circle' },
            'cancel': { text: 'Hủy bỏ', class: 'action-cancel', icon: 'bi-trash' }
        };

        // LẤY DỮ LIỆU THẬT TỪ PHP PHP ĐỔ XUỐNG
        let logs = <?php echo json_encode($db_logs ?? []); ?>;

        let currentPage = 1;
        const itemsPerPage = 8;

        function resetPageAndRender() {
            currentPage = 1;
            renderLogs();
        }

        function changePage(page) {
            currentPage = page;
            renderLogs();
        }

        function renderLogs() {
            let tbody = document.getElementById('logBody');
            let search = document.getElementById('searchInput').value.toLowerCase();
            let actionFilter = document.getElementById('actionFilter').value;

            // Lọc dữ liệu
            let filteredLogs = logs.filter(log => {
                let term = search.trim();
                let matchSearch = !term || 
                                  (log.FK_profile_id || '').toLowerCase().includes(term) || 
                                  (log.user_name || '').toLowerCase().includes(term) || 
                                  (log.reason || '').toLowerCase().includes(term);
                let matchAction = actionFilter === 'all' || log.action === actionFilter;
                return matchSearch && matchAction;
            });

            // Xử lý Phân trang
            let totalPages = Math.ceil(filteredLogs.length / itemsPerPage);
            if (currentPage > totalPages && totalPages > 0) currentPage = totalPages;
            let paginatedLogs = filteredLogs.slice((currentPage - 1) * itemsPerPage, currentPage * itemsPerPage);

            if (paginatedLogs.length === 0) {
                tbody.innerHTML = `<tr><td colspan="5">
                    <div class="empty-state">
                        <i class="bi bi-inbox"></i>
                        <h3 style="font-size:16px; color:#0f172a; margin-bottom:8px;">Không có dữ liệu</h3>
                        <p style="font-size:14px;">Chưa có hoạt động nào khớp với điều kiện tìm kiếm.</p>
                    </div>
                </td></tr>`;
                document.getElementById('pagination').innerHTML = '';
                return;
            }

            let html = '';
            paginatedLogs.forEach(log => {
                let actionConfig = actionMap[log.action] || { text: log.action, class: 'action-cancel', icon: 'bi-dot' };
                let reason = log.reason ? log.reason : '<span style="color:#cbd5e1; font-style:italic;">Hệ thống ghi nhận thành công</span>';
                
                // Format ngày giờ Việt Nam
                let timeDisplay = log.created_at;
                try {
                    let d = new Date(log.created_at);
                    if(!isNaN(d.getTime())) {
                        timeDisplay = d.toLocaleString('vi-VN', { hour12: false, day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' });
                    }
                } catch(e) {}

                html += `
                    <tr>
                        <td><span class="time-cell">${timeDisplay}</span></td>
                        <td>
                            <div style="font-weight:700; color: var(--text-heading);">${log.user_name || `User: ${log.FK_user_id}`}</div>
                        </td>
                        <td><a href="profile_detail.php?id=${log.FK_profile_id}" style="color:var(--primary); font-weight:700; text-decoration:none;">${log.FK_profile_id}</a></td>
                        <td><span class="action-badge ${actionConfig.class}"><i class="bi ${actionConfig.icon}"></i> ${actionConfig.text}</span></td>
                        <td class="reason-cell">"${reason}"</td>
                    </tr>
                `;
            });
            tbody.innerHTML = html;

            // Render nút phân trang
            let paginationHTML = '';
            if (totalPages > 1) {
                paginationHTML += `<button class="page-btn" ${currentPage === 1 ? 'disabled' : ''} onclick="changePage(${currentPage - 1})"><i class="bi bi-chevron-left"></i></button>`;
                for (let i = 1; i <= totalPages; i++) {
                    paginationHTML += `<button class="page-btn ${i === currentPage ? 'active' : ''}" onclick="changePage(${i})">${i}</button>`;
                }
                paginationHTML += `<button class="page-btn" ${currentPage === totalPages ? 'disabled' : ''} onclick="changePage(${currentPage + 1})"><i class="bi bi-chevron-right"></i></button>`;
            }
            document.getElementById('pagination').innerHTML = paginationHTML;
        }

        window.onload = renderLogs;
    </script>
</body>
</html>