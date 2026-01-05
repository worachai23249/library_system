<?php
session_start();
require_once 'config/db.php';

header('Content-Type: application/json');

if (isset($_GET['id']) && isset($_SESSION['user_id'])) {
    $id = $_GET['id'];
    $user_id = $_SESSION['user_id'];

    // อัปเดตสถานะเป็นอ่านแล้ว (1) เฉพาะของ user คนนี้
    $sql = "UPDATE messages SET is_read = 1 WHERE id = ? AND user_id = ?";
    $stmt = $pdo->prepare($sql);
    
    if ($stmt->execute([$id, $user_id])) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error']);
    }
} else {
    echo json_encode(['status' => 'error']);
}
?>