<?php
session_start();
require_once 'db.php';
// Khóa API Gemini: ưu tiên file config.php (nếu có), hoặc biến môi trường GEMINI_API_KEY
if (file_exists(__DIR__ . '/config.php')) require_once __DIR__ . '/config.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

// ======================= CẤU HÌNH AI =======================
const GEMINI_MODEL      = 'gemini-2.5-flash';
const AI_MIN_WAIT_SEC   = 2;      // chờ tối thiểu để hiệu ứng quét không bị nháy
const AI_TIMEOUT_SEC    = 8;      // quá thời gian này sẽ dùng văn bản dự phòng
// Ngưỡng nghiệp vụ (chỉnh cho khớp tài liệu đồ án)
const DTI_HIGH          = 50;     // DTI > 50%  => Rủi ro Cao
const DTI_WARN          = 35;     // DTI > 35%  => Rủi ro Trung bình
const AGE_MIN_SAFE      = 22;     // tuổi < 22  => Rủi ro Trung bình
const DOWN_PCT_MIN      = 20;     // trả trước < 20% => cảnh báo
const DEBT_INCOME_WARN  = 0.30;   // dư nợ cũ / thu nhập > 30% => cảnh báo

// ======================= HÀM PHÂN TÍCH AI =======================

/**
 * BƯỚC 1 - PHP tự tính "sự thật" và mức rủi ro theo luật nghiệp vụ.
 * Đây là nguồn đúng tuyệt đối, không phụ thuộc vào AI.
 */
function buildFacts(array $d): array {
    $dti     = (float)$d['dti'];
    $age     = (int)$d['age'];
    $income  = (float)$d['net_income'];
    $debt    = (float)$d['existing_debt'];
    $goods   = (float)$d['goods_value'];
    $down    = (float)$d['down_payment'];
    $loan    = (float)$d['loan_amount'];
    $downPct = $goods > 0 ? round($down / $goods * 100, 1) : 0;

    if ($dti > DTI_HIGH)                         $risk = 'Cao';
    elseif ($dti > DTI_WARN || $age < AGE_MIN_SAFE) $risk = 'Trung bình';
    else                                         $risk = 'Thấp';

    $anomalies = [];
    if ($dti > DTI_HIGH)       $anomalies[] = "DTI = {$dti}%, vượt ngưỡng an toàn " . DTI_HIGH . "%.";
    elseif ($dti > DTI_WARN)   $anomalies[] = "DTI = {$dti}%, ở mức cảnh báo (" . DTI_WARN . "%-" . DTI_HIGH . "%).";
    if ($age < AGE_MIN_SAFE)   $anomalies[] = "Khách hàng {$age} tuổi, dưới " . AGE_MIN_SAFE . " tuổi.";
    if ($income > 0 && ($debt / $income) > DEBT_INCOME_WARN)
        $anomalies[] = "Dư nợ cũ " . number_format($debt, 0, ',', '.') . " đ chiếm " . round($debt / $income * 100, 1) . "% thu nhập ròng.";
    if ($downPct < DOWN_PCT_MIN) $anomalies[] = "Tỷ lệ trả trước chỉ {$downPct}%, thấp hơn " . DOWN_PCT_MIN . "%.";
    if (abs(($goods - $down) - $loan) > 1000)
        $anomalies[] = "Số tiền vay không khớp: giá trị hàng - trả trước khác số tiền đề nghị vay.";

    return ['risk' => $risk, 'dti' => $dti, 'age' => $age, 'down_pct' => $downPct, 'anomalies' => $anomalies];
}

/**
 * BƯỚC 2 - Gọi Gemini để viết phần tóm tắt + khuyến nghị dựa trên sự thật ở bước 1.
 * BƯỚC 3 - Kiểm tra đầu ra của AI có cùng hướng với mức rủi ro hay không.
 */
function callGemini(array $d, array $facts): array {
    $apiKey = getenv('GEMINI_API_KEY');
    if (!$apiKey && defined('GEMINI_API_KEY')) $apiKey = GEMINI_API_KEY;
    if (!$apiKey) throw new Exception('Chưa cấu hình GEMINI_API_KEY');

    $prompt = "Bạn là chuyên viên thẩm định tín dụng trả góp. Mức rủi ro ĐÃ ĐƯỢC HỆ THỐNG XÁC ĐỊNH là: {$facts['risk']}. "
            . "Tuyệt đối không đổi mức rủi ro này, không bịa số liệu, chỉ dùng số liệu được cung cấp. "
            . "Viết bằng tiếng Việt: (1) summary: 2 câu tóm tắt hồ sơ; (2) recommendation: 1-2 câu khuyến nghị cho thẩm định viên. "
            . "Nếu rủi ro Cao, khuyến nghị phải chứa 'TỪ CHỐI'; nếu Thấp, phải chứa 'PHÊ DUYỆT'; "
            . "nếu Trung bình, phải chứa 'PHÊ DUYỆT CÓ ĐIỀU KIỆN'.\n"
            . "Điểm bất thường hệ thống đã phát hiện: " . json_encode($facts['anomalies'], JSON_UNESCAPED_UNICODE) . "\n"
            . "Dữ liệu hồ sơ: " . json_encode($d, JSON_UNESCAPED_UNICODE);

    $body = [
        'contents' => [['parts' => [['text' => $prompt]]]],
        'generationConfig' => [
            'temperature'      => 0.1,
            'responseMimeType' => 'application/json',
            'thinkingConfig'   => ['thinkingBudget' => 0],   // tắt "suy nghĩ" để phản hồi nhanh 2-5s
            'responseSchema'   => [
                'type' => 'OBJECT',
                'properties' => [
                    'summary'        => ['type' => 'STRING'],
                    'recommendation' => ['type' => 'STRING']
                ],
                'required' => ['summary', 'recommendation']
            ]
        ]
    ];

    $ch = curl_init('https://generativelanguage.googleapis.com/v1beta/models/' . GEMINI_MODEL . ':generateContent');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'x-goog-api-key: ' . $apiKey],
        CURLOPT_POSTFIELDS     => json_encode($body, JSON_UNESCAPED_UNICODE),
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT        => AI_TIMEOUT_SEC,
        CURLOPT_SSL_VERIFYPEER => false,   // chỉ để chạy localhost/XAMPP; bỏ dòng này khi triển khai thật
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err      = curl_error($ch);
    curl_close($ch);

    if ($response === false || $httpCode !== 200) throw new Exception("Gemini lỗi HTTP $httpCode $err");

    $json = json_decode($response, true);
    $out  = json_decode($json['candidates'][0]['content']['parts'][0]['text'] ?? '', true);
    if (!is_array($out) || empty($out['summary']) || empty($out['recommendation']))
        throw new Exception('Gemini trả về sai định dạng');

    // Kiểm tra khuyến nghị của AI không mâu thuẫn với mức rủi ro do luật xác định
    $rec = mb_strtoupper($out['recommendation'], 'UTF-8');
    $ok = ($facts['risk'] === 'Cao'        && mb_strpos($rec, 'TỪ CHỐI') !== false)
       || ($facts['risk'] === 'Thấp'       && mb_strpos($rec, 'PHÊ DUYỆT') !== false && mb_strpos($rec, 'TỪ CHỐI') === false)
       || ($facts['risk'] === 'Trung bình' && mb_strpos($rec, 'PHÊ DUYỆT') !== false);
    if (!$ok) throw new Exception('Khuyến nghị của AI mâu thuẫn với mức rủi ro');

    return ['summary' => $out['summary'], 'recommendation' => $out['recommendation']];
}

/** Văn bản dự phòng khi Gemini lỗi / timeout / sai định dạng */
function fallbackText(array $facts): array {
    if ($facts['risk'] === 'Cao') return [
        'summary'        => "DTI {$facts['dti']}% vượt ngưỡng an toàn, khả năng thanh toán hàng tháng bị ảnh hưởng nếu phát sinh rủi ro tài chính.",
        'recommendation' => 'Đề xuất TỪ CHỐI hoặc yêu cầu tăng tỷ lệ trả trước.'];
    if ($facts['risk'] === 'Trung bình') return [
        'summary'        => "Hồ sơ ở mức chấp nhận được nhưng có rủi ro về DTI ({$facts['dti']}%) hoặc độ tuổi ({$facts['age']}).",
        'recommendation' => 'Có thể PHÊ DUYỆT CÓ ĐIỀU KIỆN: thẩm định chéo nơi làm việc hoặc bổ sung người đồng trả nợ.'];
    return [
        'summary'        => "Hồ sơ đạt chuẩn: DTI {$facts['dti']}%, độ tuổi {$facts['age']}, không có dấu hiệu bất thường.",
        'recommendation' => 'Đề xuất PHÊ DUYỆT theo chính sách tiêu chuẩn.'];
}

// --- API XỬ LÝ DUYỆT/TỪ CHỐI (Transaction) ---
if (isset($_POST['action_type'])) {
    header('Content-Type: application/json');
    $p_id = $_POST['profile_id'];
    $status = $_POST['action_type'];
    $reason = $_POST['action_reason'];
    $statusText = $status === 'approved' ? 'Đã phê duyệt' : 'Đã từ chối';
    
    $actionEnum = $status === 'approved' ? 'approve' : 'reject'; 

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("SELECT status FROM installment_profiles WHERE id = ? FOR UPDATE");
        $stmt->execute([$p_id]);
        if ($stmt->fetchColumn() !== 'pending') throw new Exception("Hồ sơ đã được xử lý.");

        $stmtUpd = $pdo->prepare("UPDATE installment_profiles SET status=?, status_text=? WHERE id=?");
        $stmtUpd->execute([$status, $statusText, $p_id]);

        $stmtLog = $pdo->prepare("INSERT INTO audit_logs (FK_profile_id, FK_user_id, action, reason) VALUES (?, ?, ?, ?)");
        $stmtLog->execute([$p_id, $_SESSION['user_id'], $actionEnum, $reason]);

        $pdo->commit();
        echo json_encode(["status" => "success"]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
    exit;
}

// --- API GỌI AI PHÂN TÍCH (Gemini thật + kiểm chứng bằng luật nghiệp vụ) ---
if (isset($_POST['call_ai']) && $_SESSION['role'] === 'appraiser') {
    header('Content-Type: application/json');
    $p_id = $_POST['profile_id'];
    
    $stmt = $pdo->prepare("SELECT status, age, net_income, existing_debt, goods_value, down_payment, term_months, occupation, loan_amount, dti, flag_age, flag_dti FROM installment_profiles WHERE id = ?");
    $stmt->execute([$p_id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$data) { echo json_encode(["status" => "error", "message" => "Không tìm thấy hồ sơ."]); exit; }
    if ($data['status'] !== 'pending') { echo json_encode(["status" => "error", "message" => "Hồ sơ đã được xử lý, không thể quét AI."]); exit; }
    unset($data['status']);

    $start  = microtime(true);
    $facts  = buildFacts($data);
    $engine = 'gemini';
    $errMsg = '';
    try {
        $text = callGemini($data, $facts);
    } catch (Exception $e) {
        $text   = fallbackText($facts);
        $engine = 'fallback';
        $errMsg = $e->getMessage();
    }

    // Đảm bảo thời gian chờ tối thiểu cho hiệu ứng quét
    $elapsed = microtime(true) - $start;
    if ($elapsed < AI_MIN_WAIT_SEC) usleep((int)((AI_MIN_WAIT_SEC - $elapsed) * 1000000));
    $time_ms = (int)round((microtime(true) - $start) * 1000);

    $aiResult = [
        "risk_level"       => $facts['risk'],        // do luật nghiệp vụ quyết định, không do AI
        "summary"          => $text['summary'],
        "anomalies"        => $facts['anomalies'],
        "recommendation"   => $text['recommendation'],
        "response_time_ms" => $time_ms
    ];

    $stmt = $pdo->prepare("INSERT INTO ai_assessments (FK_profile_id, FK_requested_by, risk_level, summary, anomalies, recommendation, response_time_ms) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$p_id, $_SESSION['user_id'], $aiResult['risk_level'], $aiResult['summary'], json_encode($aiResult['anomalies'], JSON_UNESCAPED_UNICODE), $aiResult['recommendation'], $time_ms]);
    
    echo json_encode(["status" => "success", "data" => $aiResult, "engine" => $engine, "error" => $errMsg]);
    exit;
}

// LẤY DỮ LIỆU HIỂN THỊ KHI LOAD TRANG
$currentId = $_GET['id'] ?? '';
$stmt = $pdo->prepare("
    SELECT p.*, 
    u.full_name as creator_name, 
    (SELECT full_name FROM users WHERE id = (SELECT FK_user_id FROM audit_logs WHERE FK_profile_id = p.id AND action IN ('approve','reject') ORDER BY created_at DESC LIMIT 1)) as appraiser_name,
    (SELECT reason FROM audit_logs WHERE FK_profile_id = p.id AND action IN ('approve','reject') ORDER BY created_at DESC LIMIT 1) as action_reason
    FROM installment_profiles p 
    JOIN users u ON p.FK_created_by = u.id 
    WHERE p.id = ?
");
$stmt->execute([$currentId]);
$profileRaw = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$profileRaw) die("Không tìm thấy hồ sơ.");

$stmtAI = $pdo->prepare("SELECT * FROM ai_assessments WHERE FK_profile_id = ? ORDER BY created_at DESC LIMIT 1");
$stmtAI->execute([$currentId]);
$aiData = $stmtAI->fetch(PDO::FETCH_ASSOC);
// Hồ sơ đang chờ duyệt luôn phải bấm "Quét Rủi Ro AI" mới có kết quả
if ($profileRaw['status'] === 'pending') $aiData = null;

$userRole = $_SESSION['role'];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết hồ sơ - Back-office </title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        :root {
            --bg-body: #f8fafc; --bg-surface: #ffffff; 
            --border-light: #e2e8f0; --border-dark: #cbd5e1;
            --text-heading: #0f172a; --text-main: #334155; --text-muted: #64748b; 
            --brand-blue: #0ea5e9; 
            --primary: #4f46e5; --primary-hover: #4338ca; --primary-bg: #e0e7ff;
            --success: #10b981; --success-bg: #dcfce7; --success-text: #047857;
            --warning: #f59e0b; --warning-bg: #fef3c7; --warning-text: #b45309;
            --danger: #ef4444;  --danger-bg: #fee2e2;  --danger-text: #b91c1c;
            --shadow-sm: 0 1px 2px rgba(0,0,0,0.05);
            --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -2px rgba(0,0,0,0.05);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; -webkit-font-smoothing: antialiased; }
        body { background-color: var(--bg-body); color: var(--text-main); line-height: 1.6; }
        .navbar { background-color: var(--bg-surface); height: 64px; display: flex; justify-content: space-between; align-items: center; padding: 0 32px; border-bottom: 1px solid var(--border-light); position: sticky; top: 0; z-index: 100; box-shadow: var(--shadow-sm); }
        .nav-brand { display: flex; align-items: center; gap: 10px; font-size: 18px; font-weight: 800; color: var(--brand-blue); letter-spacing: 0.5px; }
        .nav-brand .icon-bg { background: linear-gradient(135deg, #0ea5e9, #2563eb); color: white; width: 36px; height: 36px; border-radius: 10px; display: flex; justify-content: center; align-items: center; font-size: 18px; box-shadow: 0 4px 6px rgba(14, 165, 233, 0.2); }
        .nav-user { display: flex; align-items: center; gap: 24px; }
        .user-info { text-align: right; }
        .user-name { font-size: 14px; font-weight: 700; color: var(--text-heading); }
        .user-role { font-size: 12px; font-weight: 500; color: var(--text-muted); }
        .btn-logout { font-size: 20px; color: var(--text-muted); transition: 0.2s; text-decoration: none; }
        .btn-logout:hover { color: var(--danger); }
        .container { max-width: 1400px; margin: 32px auto; padding: 0 24px; animation: fadeIn 0.5s ease-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .breadcrumb { display: flex; align-items: center; gap: 8px; font-size: 14px; color: var(--text-muted); margin-bottom: 24px; font-weight: 500; }
        .breadcrumb a { color: var(--text-muted); text-decoration: none; transition: 0.2s; }
        .breadcrumb a:hover { color: var(--primary); }
        .breadcrumb .active { color: var(--text-heading); font-weight: 600; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .header-title { display: flex; align-items: center; gap: 16px; }
        .header-title h1 { font-size: 26px; font-weight: 800; color: var(--text-heading); letter-spacing: -0.02em; }
        .badge { padding: 6px 14px; border-radius: 20px; font-size: 13.5px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; }
        .badge::before { content: ''; display: block; width: 8px; height: 8px; border-radius: 50%; box-shadow: 0 0 0 2px rgba(255,255,255,0.5); }
        .badge-pending { background: var(--warning-bg); color: var(--warning-text); } .badge-pending::before { background: var(--warning); }
        .badge-approved { background: var(--success-bg); color: var(--success-text); } .badge-approved::before { background: var(--success); }
        .badge-rejected { background: var(--danger-bg); color: var(--danger-text); } .badge-rejected::before { background: var(--danger); }
        .btn-outline { padding: 10px 20px; border-radius: 8px; border: 1px solid var(--border-light); background: white; color: var(--text-heading); font-size: 14px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: all 0.2s; box-shadow: var(--shadow-sm); }
        .btn-outline:hover { background: #f8fafc; border-color: var(--border-dark); transform: translateY(-1px); }
        .grid-layout { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; align-items: start; }
        .card { background: var(--bg-surface); border-radius: 16px; border: 1px solid var(--border-light); box-shadow: var(--shadow-md); margin-bottom: 24px; overflow: hidden; transition: box-shadow 0.3s; }
        .card-header { padding: 18px 24px; border-bottom: 1px solid var(--border-light); display: flex; align-items: center; gap: 10px; font-size: 16px; font-weight: 700; color: var(--text-heading); background: #fcfcfc; }
        .card-header i { color: var(--primary); font-size: 18px; }
        .card-body { padding: 24px; }
        .info-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px 24px; }
        .info-item { display: flex; flex-direction: column; gap: 4px; }
        .info-label { font-size: 13px; color: var(--text-muted); font-weight: 500; }
        .info-val { font-size: 15px; font-weight: 600; color: var(--text-heading); }
        .info-val.highlight { color: var(--primary); font-size: 16px; font-weight: 800; }
        .info-val.danger { color: var(--danger); font-size: 16px; font-weight: 800; }
        .staff-tag { display: flex; align-items: center; gap: 12px; background: #f1f5f9; padding: 14px; border-radius: 12px; border: 1px dashed var(--border-dark); }
        .staff-tag i { font-size: 24px; color: var(--text-muted); }
        .staff-info p { font-size: 12px; color: var(--text-muted); margin-bottom: 2px; }
        .staff-info h4 { font-size: 14px; font-weight: 700; color: var(--text-heading); }
        .dti-box { background: #fffbeb; padding: 14px; border-radius: 10px; border-left: 4px solid var(--warning); grid-column: span 2; display: flex; flex-direction: column; gap: 6px;}
        .dti-box code { font-size: 15px; font-weight: 800; color: var(--warning-text); background: none; letter-spacing: 0.5px;}
        .ai-card { border: 1px solid #c7d2fe; box-shadow: var(--shadow-md); background: linear-gradient(to bottom right, #ffffff, #f8fafc); }
        .ai-card .card-header { background: #eef2ff; color: #4338ca; border-bottom: 1px solid #c7d2fe; }
        .ai-spinner { width: 48px; height: 48px; border: 4px solid #e0e7ff; border-top: 4px solid #4338ca; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 16px; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .action-panel { background: white; border-radius: 0 0 16px 16px; padding: 32px; text-align: center; transition: all 0.3s; }
        .btn-group { display: flex; gap: 16px; justify-content: center; margin-top: 16px; }
        .btn { padding: 12px 28px; border-radius: 10px; font-size: 15px; font-weight: 600; border: none; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: all 0.2s; }
        .btn-primary { background: var(--primary); color: white; box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3); }
        .btn-approve { background: var(--success); color: white; box-shadow: 0 4px 12px rgba(16,185,129,0.3); }
        .btn-reject { background: white; color: var(--danger); border: 2px solid var(--danger); }
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); display: none; justify-content: center; align-items: center; z-index: 1000; opacity: 0; transition: opacity 0.3s; }
        .modal-overlay.active { display: flex; opacity: 1; }
        .modal-box { background: white; border-radius: 16px; padding: 28px; width: 550px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); transform: translateY(20px); transition: transform 0.3s; }
        .modal-overlay.active .modal-box { transform: translateY(0); }
        .modal-title { font-size: 18px; font-weight: 800; margin-bottom: 16px; display: flex; align-items: center; gap: 10px; color: var(--text-heading); }
        .quick-tags { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 16px; }
        .tag-chip { background: #f1f5f9; border: 1px solid var(--border-light); padding: 8px 14px; border-radius: 20px; font-size: 13px; font-weight: 500; cursor: pointer; color: var(--text-main); transition: 0.2s; }
        .tag-chip:hover { background: #e2e8f0; border-color: var(--border-dark); color: var(--primary); }
        .modal-box textarea { width: 100%; height: 120px; border: 2px solid var(--border-light); border-radius: 10px; padding: 16px; font-size: 14.5px; outline: none; resize: none; margin-bottom: 24px; transition: 0.2s; }
        .modal-box textarea:focus { border-color: var(--primary); background: #fffafaf0; }
        .toast-container { position: fixed; top: 24px; right: 24px; z-index: 9999; display: flex; flex-direction: column; gap: 12px; }
        .toast { min-width: 300px; padding: 16px 20px; border-radius: 12px; background: white; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); display: flex; align-items: center; gap: 12px; font-weight: 600; font-size: 14.5px; animation: slideInRight 0.4s forwards; }
        .toast.success { border-left: 4px solid var(--success); color: var(--success); }
        .toast.error { border-left: 4px solid var(--danger); color: var(--danger); }
        @keyframes slideInRight { 0% { transform: translateX(100%); opacity: 0; } 100% { transform: translateX(0); opacity: 1; } }
        @keyframes fadeOut { 0% { opacity: 1; } 100% { opacity: 0; transform: translateY(-10px); } }
        @keyframes popIn { 0% { transform: scale(0.9); opacity:0;} 100% { transform: scale(1); opacity:1;} }

        /* --- CSS MỚI CHO TRẠNG THÁI KHÓA HIỆN ĐẠI --- */
        .locked-card { border-radius: 16px; padding: 32px; position: relative; overflow: hidden; text-align: left; animation: popIn 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
        .locked-card.approved { background: linear-gradient(145deg, #ecfdf5, #f0fdf4); border: 2px solid #a7f3d0; box-shadow: 0 10px 25px -5px rgba(16, 185, 129, 0.15); }
        .locked-card.rejected { background: linear-gradient(145deg, #fef2f2, #fff1f2); border: 2px solid #fecaca; box-shadow: 0 10px 25px -5px rgba(239, 68, 68, 0.15); }
        .locked-watermark { position: absolute; right: -30px; top: -30px; font-size: 200px; opacity: 0.04; pointer-events: none; }
        .locked-icon-wrapper { width: 72px; height: 72px; border-radius: 50%; display: flex; justify-content: center; align-items: center; background: white; box-shadow: 0 4px 10px rgba(0,0,0,0.05); flex-shrink: 0; font-size: 40px; }
        .approved .locked-icon-wrapper { color: #10b981; }
        .rejected .locked-icon-wrapper { color: #ef4444; }
        .locked-badge { display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 800; letter-spacing: 0.5px; background: white; margin-bottom: 8px;}
        .approved .locked-badge { color: #059669; border: 1px solid #a7f3d0; }
        .rejected .locked-badge { color: #dc2626; border: 1px solid #fecaca; }
        .locked-title { font-size: 26px; font-weight: 800; margin: 0 0 16px 0; letter-spacing: -0.5px; }
        .approved .locked-title { color: #047857; }
        .rejected .locked-title { color: #b91c1c; }
        .locked-reason-box { background: white; padding: 20px; border-radius: 12px; position: relative; box-shadow: 0 2px 8px rgba(0,0,0,0.02); margin-top: 16px;}
        .approved .locked-reason-box { border-left: 4px solid #10b981; }
        .rejected .locked-reason-box { border-left: 4px solid #ef4444; }
    </style>
</head>
<body>
    <div class="toast-container" id="toastContainer"></div>
    <nav class="navbar">
        <div class="nav-brand">
            <div class="icon-bg"><i class="bi bi-robot"></i></div> BACK-OFFICE 
        </div>
        <div class="nav-user">
            <div class="user-info">
                <div class="user-name"><?php echo htmlspecialchars($_SESSION['full_name']); ?></div>
                <div class="user-role"><?php echo ($userRole === 'appraiser') ? 'Chuyên viên Thẩm định' : 'Nhân viên Tín dụng'; ?></div>
            </div>
            <a href="logout.php" class="btn-logout" title="Đăng xuất"><i class="bi bi-box-arrow-right"></i></a>
        </div>
    </nav>

    <div class="container">
        <div class="breadcrumb">
            <a href="dashboard.php"><i class="bi bi-house-door"></i> Quản lý hồ sơ</a>
            <i class="bi bi-chevron-right" style="font-size: 10px;"></i>
            <span class="active">Chi tiết hồ sơ</span>
        </div>

        <div class="page-header">
            <div class="header-title">
                <h1 id="pageTitle">Hồ sơ: Đang tải...</h1>
                <span id="badgeStatus" class="badge">Đang tải</span>
            </div>
            <button class="btn-outline" onclick="window.print()"><i class="bi bi-printer"></i> In hồ sơ</button>
        </div>

        <div class="grid-layout">
            <div class="left-col">
                <div class="card">
                    <div class="card-header"><i class="bi bi-person-badge"></i> Thông tin Phụ trách & Nhân sự</div>
                    <div class="card-body">
                        <div class="info-grid">
                            <div class="staff-tag">
                                <i class="bi bi-headset"></i>
                                <div class="staff-info"><p>Tạo hồ sơ (Sales/Tín dụng)</p><h4 id="valCreator">---</h4></div>
                            </div>
                            <div class="staff-tag">
                                <i class="bi bi-shield-check"></i>
                                <div class="staff-info"><p>Thẩm định viên</p><h4 id="valAppraiser">Chưa phân công</h4></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><i class="bi bi-person-vcard"></i> Thông tin Khách hàng & Thu nhập</div>
                    <div class="card-body">
                        <div class="info-grid">
                            <div class="info-item"><span class="info-label">Họ và tên</span><span class="info-val" id="valName">---</span></div>
                            <div class="info-item"><span class="info-label">CCCD / CMND</span><span class="info-val" id="valCCCD">---</span></div>
                            <div class="info-item"><span class="info-label">Ngày sinh / Độ tuổi</span><span class="info-val" id="valAge">---</span></div>
                            <div class="info-item"><span class="info-label">Số điện thoại</span><span class="info-val" id="valPhone">---</span></div>
                            
                            <div style="grid-column: span 2; height: 1px; background: var(--border-light); margin: 8px 0;"></div>
                            
                            <div class="info-item" style="grid-column: span 2;"><span class="info-label">Nơi công tác</span><span class="info-val" id="valCompany">---</span></div>
                            <div class="info-item"><span class="info-label">Chức vụ</span><span class="info-val" id="valJob">---</span></div>
                            <div class="info-item"><span class="info-label">Thâm niên</span><span class="info-val" id="valTenure">---</span></div>
                            
                            <div class="info-item"><span class="info-label">Thu nhập ròng (Net Income)</span><span class="info-val highlight" id="valIncome">---</span></div>
                            <div class="info-item"><span class="info-label">Dư nợ tín dụng cũ (Hàng tháng)</span><span class="info-val danger" id="valDebt">---</span></div>

                            <div class="dti-box">
                                <span class="info-label" style="display:block;">Chi tiết tính toán tỷ lệ nợ (DTI) của hệ thống:</span>
                                <code id="valDTICalc">Đang tính toán...</code>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><i class="bi bi-bag-check"></i> Thông tin Gói vay Trả góp</div>
                    <div class="card-body">
                        <div class="info-grid">
                            <div class="info-item" style="grid-column: span 2;"><span class="info-label">Sản phẩm vay mua</span><span class="info-val" id="valProduct">---</span></div>
                            <div class="info-item"><span class="info-label">Giá trị tài sản (Giá bán)</span><span class="info-val" id="valAssetValue">---</span></div>
                            <div class="info-item"><span class="info-label">Khách hàng trả trước</span><span class="info-val highlight" id="valDownPay">---</span></div>
                            <div style="grid-column: span 2; height: 1px; background: var(--border-light); margin: 8px 0;"></div>
                            <div class="info-item"><span class="info-label">Số tiền đề nghị vay</span><span class="info-val highlight" id="valLoanAmt">---</span></div>
                            <div class="info-item"><span class="info-label">Kỳ hạn vay</span><span class="info-val" id="valTerm">---</span></div>
                            <div class="info-item"><span class="info-label">Lãi suất áp dụng</span><span class="info-val" id="valRate">---</span></div>
                            <div class="info-item"><span class="info-label">Ước tính Gốc + Lãi (Tháng)</span><span class="info-val highlight" style="color:#d97706;" id="valMonthly">---</span><span class="info-sub" id="valMonthlyDetail">---</span></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="right-col">
                <div class="card ai-card" id="aiCardBlock">
                    <div class="card-header"><i class="bi bi-stars"></i> Đánh giá Rủi ro bởi Trợ lý AI</div>
                    <div class="card-body" id="aiCardBody">
                        <!-- Chèn từ JS -->
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><i class="bi bi-shield-lock"></i> Quyết định Thẩm định</div>
                    <div class="card-body" style="padding: 0;">
                        <div id="actionPanel" class="action-panel" style="border: none;">
                            <!-- JS xử lý nút duyệt / khóa duyệt -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL -->
    <div class="modal-overlay" id="actionModal">
        <div class="modal-box">
            <div class="modal-title" id="modalTitle">Tiêu đề</div>
            <p style="font-size: 13.5px; color: var(--text-muted); margin-bottom: 10px;">Lý do đề xuất (Click để chọn nhanh):</p>
            <div class="quick-tags" id="quickTags"></div>
            <textarea id="actionReason" placeholder="Hoặc tự nhập chi tiết lý do vào đây (BẮT BUỘC)..."></textarea>
            <input type="hidden" id="actionTypeVal" value="">
            <div style="display: flex; gap: 12px; justify-content: flex-end;">
                <button type="button" class="btn btn-outline" style="border: none; box-shadow: none; background: #f1f5f9;" onclick="closeModal()">Hủy bỏ</button>
                <button type="button" class="btn" id="btnConfirmAction" onclick="submitAction()">Xác nhận</button>
            </div>
        </div>
    </div>

    <script>
        const profile = <?php echo json_encode($profileRaw ?? []); ?>;
        let aiAnalyzedData = <?php echo json_encode($aiData ?: null); ?>;
        let aiAnalyzed = aiAnalyzedData !== null;
        const userRole = '<?php echo $_SESSION['role'] ?? ""; ?>';

        function formatVND(num) { return parseInt(num).toLocaleString('vi-VN') + ' đ'; }

        // Escape nội dung động (đặc biệt là chữ do AI sinh ra) trước khi chèn vào innerHTML
        function esc(s) { const d = document.createElement('div'); d.textContent = s ?? ''; return d.innerHTML; }

        function showToast(message, type = 'success') {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            toast.innerHTML = `<i class="bi ${type === 'success' ? 'bi-check-circle-fill' : 'bi-x-circle-fill'}"></i> ${message}`;
            container.appendChild(toast);
            setTimeout(() => {
                toast.style.animation = 'fadeOut 0.4s forwards';
                setTimeout(() => toast.remove(), 400);
            }, 3000);
        }

        function initPage() {
            document.getElementById('pageTitle').innerHTML = `Hồ sơ: ${profile.id}`;
            const badge = document.getElementById('badgeStatus');
            badge.className = `badge badge-${profile.status}`;
            badge.innerText = profile.status_text;

            document.getElementById('valName').innerText = profile.customer_name;
            document.getElementById('valCCCD').innerText = profile.cccd;
            document.getElementById('valAge').innerText = profile.age + ' tuổi';
            document.getElementById('valPhone').innerText = profile.phone;
            document.getElementById('valCompany').innerText = profile.company || 'Không cung cấp';
            document.getElementById('valJob').innerText = profile.occupation || 'Không cung cấp';
            document.getElementById('valTenure').innerText = profile.tenure_months ? `${profile.tenure_months} tháng` : 'Không cung cấp';
            
            document.getElementById('valProduct').innerText = profile.product_name;
            document.getElementById('valTerm').innerText = profile.term_months + ' Tháng';
            document.getElementById('valRate').innerText = (parseFloat(profile.interest_rate) * 100) + '% / tháng';
            document.getElementById('valMonthly').innerText = formatVND(profile.monthly_installment);
            document.getElementById('valMonthlyDetail').innerText = '(Tính theo dư nợ giảm dần)';

            document.getElementById('valCreator').innerText = profile.creator_name;
            document.getElementById('valAppraiser').innerText = profile.appraiser_name || 'Chưa phân công';

            document.getElementById('valIncome').innerText = formatVND(profile.net_income);
            document.getElementById('valDebt').innerText = formatVND(profile.existing_debt);
            document.getElementById('valAssetValue').innerText = formatVND(profile.goods_value);
            document.getElementById('valLoanAmt').innerText = formatVND(profile.loan_amount);

            let downPct = parseFloat(profile.down_payment_ratio);
            document.getElementById('valDownPay').innerText = `${formatVND(profile.down_payment)} (${downPct}%)`;

            let realDti = parseFloat(profile.dti);
            document.getElementById('valDTICalc').innerText = `( ${formatVND(profile.existing_debt)} + Lãi ) / ${formatVND(profile.net_income)} = ${realDti}%`;

            if (userRole === 'staff') {
                document.getElementById('aiCardBlock').style.display = 'none';
                renderActionPanel();
            } else {
                if (profile.status === 'pending') {
                    if (!aiAnalyzed) renderAIPendingState();
                    else renderAIResult();
                } else {
                    if (aiAnalyzed) renderAIResult();
                    else document.getElementById('aiCardBody').innerHTML = `<p style="text-align:center; color:var(--text-muted); padding: 30px;">Hồ sơ này được duyệt thủ công, không có dữ liệu phân tích từ AI.</p>`;
                }
                renderActionPanel();
            }
        }

        async function updateApplicationStatus(newStatus, reason) {
            const actionPanel = document.getElementById('actionPanel');
            actionPanel.innerHTML = `<div style="padding: 20px;"><div class="ai-spinner" style="border-top-color: var(--primary);"></div><p style="font-weight: 600;">Đang khóa dữ liệu hệ thống vĩnh viễn...</p></div>`;

            const formData = new FormData();
            formData.append('action_type', newStatus);
            formData.append('action_reason', reason);
            formData.append('profile_id', profile.id);

            try {
                let res = await fetch('profile_detail.php', { method: 'POST', body: formData });
                let data = await res.json();
                if(data.status === 'success') {
                    showToast(`Thành công! Hồ sơ đã lưu trạng thái`, 'success');
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    showToast("Lỗi: " + data.message, "error");
                    renderActionPanel();
                }
            } catch(e) {
                showToast("Lỗi kết nối máy chủ!", "error");
                renderActionPanel();
            }
        }

        function renderAIPendingState() {
            document.getElementById('aiCardBody').innerHTML = `
                <div style="text-align: center; padding: 32px 16px;">
                    <i class="bi bi-robot" style="font-size: 48px; color: var(--text-muted); display: block; margin-bottom: 16px;"></i>
                    <h3 style="font-size: 18px; color: var(--text-heading); margin-bottom: 8px;">AI chưa được kích hoạt</h3>
                    <p style="font-size: 14px; color: var(--text-muted); margin-bottom: 24px;">Hãy sử dụng Trợ lý AI để tự động phân tích và đối chiếu Business Rules trước khi duyệt.</p>
                    <button class="btn btn-primary" style="margin: 0 auto;" onclick="startAIAnalysis()"><i class="bi bi-magic"></i> Quét Rủi Ro AI</button>
                </div>`;
        }

        async function startAIAnalysis() {
            const body = document.getElementById('aiCardBody');
            body.innerHTML = `
                <div style="text-align: center; padding: 48px 16px;">
                    <div class="ai-spinner"></div>
                    <h3 style="font-size: 16px; color: #4338ca; font-weight: 700;">Đang quét dữ liệu bằng Trợ lý AI...</h3>
                    <p style="font-size: 13.5px; color: var(--text-muted); margin-top: 8px;">Vui lòng chờ (Có thể mất 2-5 giây)</p>
                </div>`;
            
            const formData = new FormData();
            formData.append('call_ai', '1');
            formData.append('profile_id', profile.id);

            try {
                let res = await fetch('profile_detail.php', { method: 'POST', body: formData });
                let data = await res.json();
                if(data.status === 'success') {
                    if (data.engine === 'fallback') {
                        showToast("Gemini không phản hồi, đang dùng phân tích dự phòng theo luật", "error");
                        console.warn("Gemini error:", data.error);
                    } else {
                        showToast("AI đã hoàn tất phân tích", "success");
                    }
                    aiAnalyzedData = data.data;
                    aiAnalyzed = true;
                    renderAIResult();
                    renderActionPanel();
                } else {
                    body.innerHTML = `<div style="text-align: center; padding: 32px;"><p style="color:red; font-weight:600;">Lỗi: ${esc(data.message)}</p><button class="btn btn-primary" style="margin:16px auto 0;" onclick="startAIAnalysis()">Thử lại</button></div>`;
                }
            } catch(e) {
                body.innerHTML = `<div style="text-align: center; padding: 32px;"><p style="color:red; font-weight:600;">Lỗi kết nối máy chủ / API Timeout!</p><button class="btn btn-primary" style="margin:16px auto 0;" onclick="startAIAnalysis()">Thử lại</button></div>`;
            }
        }

        function renderAIResult() {
            const risk = aiAnalyzedData.risk_level;
            let colorHex, colorVar, insightBg, insightIcon;

            if (risk === 'Thấp') { colorHex = '#10b981'; colorVar = 'var(--success)'; insightBg = 'var(--success-bg)'; insightIcon = 'bi-shield-check'; } 
            else if (risk === 'Trung bình') { colorHex = '#f59e0b'; colorVar = 'var(--warning)'; insightBg = 'var(--warning-bg)'; insightIcon = 'bi-shield-exclamation'; } 
            else { colorHex = '#ef4444'; colorVar = 'var(--danger)'; insightBg = 'var(--danger-bg)'; insightIcon = 'bi-shield-x'; }

            // Ép kiểu anomalies về Array để không bị trắng khối bên phải
            let anomaliesArray = [];
            if (typeof aiAnalyzedData.anomalies === 'string') {
                try { anomaliesArray = JSON.parse(aiAnalyzedData.anomalies); } catch(e) {}
            } else if (Array.isArray(aiAnalyzedData.anomalies)) {
                anomaliesArray = aiAnalyzedData.anomalies;
            }

            let anomaliesHtml = (anomaliesArray && anomaliesArray.length > 0) 
                ? anomaliesArray.map(item => `<li style="margin-bottom:4px;">${esc(item)}</li>`).join('') 
                : '<li>Không phát hiện điểm bất thường đáng lo ngại.</li>';

            const timeSec = aiAnalyzedData.response_time_ms ? (aiAnalyzedData.response_time_ms / 1000).toFixed(1) + 's' : '';

            document.getElementById('aiCardBody').innerHTML = `
                <div style="display:flex; align-items:center; gap:16px; margin-bottom:20px; padding-bottom:20px; border-bottom:1px dashed var(--border-dark);">
                    <div style="width:64px; height:64px; border-radius:16px; background:${insightBg}; color:${colorVar}; display:flex; justify-content:center; align-items:center; font-size:32px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
                        <i class="bi ${insightIcon}"></i>
                    </div>
                    <div>
                        <div style="font-size:12px; color:var(--text-muted); font-weight:700; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:2px;">Kết luận Hệ thống</div>
                        <div style="font-size:22px; font-weight:800; color:${colorVar}; line-height:1.2;">MỨC RỦI RO: ${esc(risk).toUpperCase()}</div>
                    </div>
                </div>

                <div style="display:flex; flex-direction:column; gap:16px;">
                    <div>
                        <h4 style="font-size:14px; font-weight:700; color:var(--text-heading); display:flex; align-items:center; gap:8px; margin-bottom:8px;"><i class="bi bi-card-text" style="color:var(--primary);"></i> Tóm tắt hồ sơ</h4>
                        <div style="background:#f8fafc; padding:14px 16px; border-radius:10px; border:1px solid var(--border-light); font-size:14px; color:var(--text-main); line-height:1.6;">
                            ${esc(aiAnalyzedData.summary)}
                        </div>
                    </div>
                    <div>
                        <h4 style="font-size:14px; font-weight:700; color:var(--text-heading); display:flex; align-items:center; gap:8px; margin-bottom:8px;"><i class="bi bi-exclamation-triangle-fill" style="color:var(--warning);"></i> Điểm bất thường & Cảnh báo</h4>
                        <ul style="background:#fffbeb; padding:14px 16px 14px 36px; border-radius:10px; border:1px solid #fde68a; font-size:14px; color:#b45309; line-height:1.6; margin:0;">
                            ${anomaliesHtml}
                        </ul>
                    </div>
                    <div>
                        <h4 style="font-size:14px; font-weight:700; color:var(--text-heading); display:flex; align-items:center; gap:8px; margin-bottom:8px;"><i class="bi bi-lightbulb-fill" style="color:#0ea5e9;"></i> Khuyến nghị Thẩm định</h4>
                        <div style="background:linear-gradient(to right, #eff6ff, #ffffff); padding:16px 20px; border-radius:10px; border:1px solid #bfdbfe; font-size:14.5px; color:#1d4ed8; font-weight:600; font-style:italic; position:relative; box-shadow: 0 2px 4px rgba(59, 130, 246, 0.05);">
                            <i class="bi bi-quote" style="position:absolute; top:-4px; right:12px; font-size:48px; color:#dbeafe; opacity:0.6;"></i>
                            <span style="position:relative; z-index:1;">"${esc(aiAnalyzedData.recommendation)}"</span>
                        </div>
                    </div>
                </div>
                <div style="font-size:12px; color:var(--text-muted); text-align:center; font-style:italic; margin-top:24px; display:flex; justify-content:center; align-items:center; gap:6px;">
                    <i class="bi bi-info-circle"></i> Trợ lý AI chỉ mang tính tham vấn, Thẩm định viên quyết định cuối cùng.${timeSec ? ' · Thời gian phân tích: ' + timeSec : ''}
                </div>
            `;
        }

        const reasonsList = {
            'approved': ["Hồ sơ đủ điều kiện, DTI an toàn", "Khách hàng VIP, lịch sử tín dụng tốt", "Thu nhập ổn định, nguồn thu rõ ràng"],
            'rejected': ["DTI vượt quá ngưỡng an toàn, rủi ro cao", "Tuổi hoặc tỷ lệ trả trước không đạt chuẩn", "Không xác minh được nơi làm việc/thu nhập"]
        };

        function openActionModal(type) {
            const modal = document.getElementById('actionModal');
            const titleEl = document.getElementById('modalTitle');
            const tagsEl = document.getElementById('quickTags');
            const btnConfirm = document.getElementById('btnConfirmAction');
            
            document.getElementById('actionTypeVal').value = type;
            document.getElementById('actionReason').value = '';
            tagsEl.innerHTML = '';
            reasonsList[type].forEach(txt => {
                let span = document.createElement('span');
                span.className = 'tag-chip';
                span.innerText = txt;
                span.onclick = () => { document.getElementById('actionReason').value = txt; };
                tagsEl.appendChild(span);
            });

            if (type === 'approved') {
                titleEl.innerHTML = `<i class="bi bi-check-circle-fill" style="color:var(--success)"></i> Xác nhận Phê Duyệt`;
                btnConfirm.className = 'btn btn-approve'; btnConfirm.innerText = 'Lưu Phê Duyệt';
            } else {
                titleEl.innerHTML = `<i class="bi bi-x-octagon-fill" style="color:var(--danger)"></i> Xác nhận Từ Chối`;
                btnConfirm.className = 'btn btn-reject'; btnConfirm.innerText = 'Lưu Từ Chối';
            }
            modal.classList.add('active');
        }

        function closeModal() { document.getElementById('actionModal').classList.remove('active'); }

        function submitAction() {
            const reason = document.getElementById('actionReason').value.trim();
            const type = document.getElementById('actionTypeVal').value;
            if(!reason) { 
                showToast("BẮT BUỘC: Bạn phải nhập hoặc chọn lý do thẩm định!", "error"); 
                document.getElementById('actionReason').focus(); return; 
            }
            closeModal();
            updateApplicationStatus(type, reason);
        }

        function renderActionPanel() {
            const panel = document.getElementById('actionPanel');
            panel.className = 'action-panel'; 

            if (profile.status === 'pending') {
                if (userRole === 'appraiser' || userRole === 'manager') {
                    if (!aiAnalyzed) {
                        panel.innerHTML = `<p style="color:var(--text-muted);"><i class="bi bi-lock-fill"></i> Hệ thống yêu cầu chạy Quét Rủi ro AI trước khi mở khóa thao tác duyệt.</p>`;
                    } else {
                        panel.innerHTML = `
                            <p class="action-desc">Quyết định của bạn sẽ <strong>Lưu vĩnh viễn</strong> và không thể thay đổi.</p>
                            <div class="btn-group">
                                <button class="btn btn-approve" onclick="openActionModal('approved')"><i class="bi bi-check-lg"></i> Phê duyệt hồ sơ</button>
                                <button class="btn btn-reject" onclick="openActionModal('rejected')"><i class="bi bi-x-lg"></i> Từ chối hồ sơ</button>
                            </div>`;
                    }
                } else {
                    panel.innerHTML = `<p style="color:var(--warning-text);"><i class="bi bi-hourglass-split"></i> Nhân viên tín dụng không có quyền phê duyệt. Vui lòng chờ.</p>`;
                }
            } else {
                const isApprove = profile.status === 'approved';
                const cardClass = isApprove ? 'approved' : 'rejected';
                const iconClass = isApprove ? 'bi-patch-check-fill' : 'bi-x-octagon-fill';
                
                let reasonText = profile.action_reason ? profile.action_reason : 'Đã duyệt qua hệ thống tự động, không có ghi chú thêm.';

                panel.innerHTML = `
                    <div class="locked-card ${cardClass}">
                        <i class="bi ${iconClass} locked-watermark"></i>
                        
                        <div style="display:flex; align-items:flex-start; gap:24px; position:relative; z-index:1;">
                            <div class="locked-icon-wrapper">
                                <i class="bi ${iconClass}"></i>
                            </div>
                            
                            <div style="flex:1;">
                                <div class="locked-badge"><i class="bi bi-lock-fill"></i> HỒ SƠ ĐÃ KHÓA</div>
                                <h2 class="locked-title">${esc(profile.status_text).toUpperCase()}</h2>
                                
                                <div style="display:flex; gap:16px; margin-bottom: 20px;">
                                    <div style="background: white; padding: 12px 16px; border-radius: 10px; border: 1px solid rgba(0,0,0,0.05); display:inline-block;">
                                        <div style="font-size: 11px; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Chuyên viên Thẩm định</div>
                                        <div style="font-size: 14.5px; font-weight: 700; color: var(--text-heading); margin-top: 4px;">
                                            <i class="bi bi-person-badge" style="color:var(--text-muted);"></i> ${esc(profile.appraiser_name || 'Hệ thống')}
                                        </div>
                                    </div>
                                </div>

                                <div class="locked-reason-box">
                                    <div style="font-size: 12px; color: var(--text-muted); font-weight: 700; text-transform: uppercase; margin-bottom: 8px;">Lý do / Ghi chú hệ thống:</div>
                                    <div style="font-size: 15px; color: var(--text-heading); font-weight: 600; font-style: italic; line-height:1.5;">
                                        "${esc(reasonText)}"
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>`;
            }
        }

        window.onload = initPage;
    </script>
</body>
</html>