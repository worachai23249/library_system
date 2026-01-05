<?php
session_start();
require_once 'config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$my_id = $_SESSION['user_id'];
$my_role = $_SESSION['role'] ?? 'member';

// --- 1. ส่งข้อความ (Text, Image, Voice) ---
if ($action == 'send') {
    $receiver_id = $_POST['receiver_id'];
    $message = $_POST['message'] ?? '';
    $type = 'text';
    $filename = null;

    // ถ้าแอดมินส่ง ให้ส่งหา User นั้นๆ, ถ้า User ส่ง ให้ส่งหา Admin (ID 1)
    if ($my_role == 'member') {
        $receiver_id = 1; // Default Admin ID
    }

    // จัดการไฟล์แนบ (รูปภาพ หรือ เสียง)
    if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
        $ext = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
        if(!$ext) $ext = 'webm'; // Default สำหรับเสียงจาก JS MediaRecorder
        
        $filename = "chat_" . time() . "_" . rand(1000,9999) . "." . $ext;
        $target = "uploads/chats/";
        
        if (!file_exists($target)) mkdir($target, 0777, true);
        move_uploaded_file($_FILES['file']['tmp_name'], $target . $filename);
        
        $type = $_POST['msg_type']; // image หรือ voice
    }

    $sql = "INSERT INTO chats (sender_id, receiver_id, message, attachment, type) VALUES (?, ?, ?, ?, ?)";
    $pdo->prepare($sql)->execute([$my_id, $receiver_id, $message, $filename, $type]);
    
    echo json_encode(['status' => 'success']);
    exit;
}

// --- 2. ดึงข้อความ (Fetch) ---
if ($action == 'fetch') {
    $partner_id = $_GET['partner_id'];
    
    if ($my_role == 'member') {
        $partner_id = 1; // User จะดึงแชทที่คุยกับ Admin เท่านั้น
    }

    $sql = "SELECT * FROM chats 
            WHERE (sender_id = ? AND receiver_id = ?) 
               OR (sender_id = ? AND receiver_id = ?) 
            ORDER BY created_at ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$my_id, $partner_id, $partner_id, $my_id]);
    $chats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // เปลี่ยนสถานะเป็นอ่านแล้ว (เฉพาะข้อความที่อีกฝ่ายส่งมา)
    $pdo->prepare("UPDATE chats SET is_read = 1 WHERE sender_id = ? AND receiver_id = ?")->execute([$partner_id, $my_id]);

    echo json_encode($chats);
    exit;
}

// --- 3. ดึงรายชื่อคนทัก (สำหรับ Admin) ---
if ($action == 'get_users' && $my_role == 'admin') {
    // ดึง User ที่เคยคุยกับ Admin ล่าสุด
    $sql = "SELECT DISTINCT 
                CASE WHEN sender_id = 1 THEN receiver_id ELSE sender_id END as user_id
            FROM chats WHERE sender_id = 1 OR receiver_id = 1";
            
    $stmt = $pdo->query($sql);
    $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $users = [];
    if(!empty($ids)) {
        $inQuery = implode(',', array_fill(0, count($ids), '?'));
        $stmtUsers = $pdo->prepare("SELECT id, fullname, role FROM members WHERE id IN ($inQuery)");
        $stmtUsers->execute($ids);
        $users = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);
        
        // นับข้อความที่ยังไม่อ่านของแต่ละคน
        foreach($users as &$u) {
            $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM chats WHERE sender_id = ? AND receiver_id = 1 AND is_read = 0");
            $stmtCount->execute([$u['id']]);
            $u['unread'] = $stmtCount->fetchColumn();
        }
    }
    
    echo json_encode($users);
    exit;
}
?>