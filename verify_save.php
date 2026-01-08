<?php
session_start();
require_once 'config/db.php';

// ตรวจสอบการ Login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $id_card_number = $_POST['id_card_number'];
    $laser_code = $_POST['laser_code'];
    $dob = $_POST['dob'];

    // ฟังก์ชันสำหรับอัปโหลดไฟล์
    function uploadKYCImage($file, $prefix) {
        $target_dir = "uploads/kyc/";
        if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }
        
        $filename = $prefix . "_" . $_SESSION['user_id'] . "_" . time() . "." . pathinfo($file["name"], PATHINFO_EXTENSION);
        $target_file = $target_dir . $filename;
        
        if (move_uploaded_file($file["tmp_name"], $target_file)) {
            return $filename;
        }
        return null;
    }

    // อัปโหลด 3 ไฟล์
    $front_img = uploadKYCImage($_FILES['id_card_front'], 'front');
    $back_img = uploadKYCImage($_FILES['id_card_back'], 'back');
    $selfie_img = uploadKYCImage($_FILES['selfie_image'], 'selfie');

    try {
        $pdo->beginTransaction();

        // 1. บันทึกลงตาราง verifications
        $sql = "INSERT INTO verifications (user_id, id_card_number, laser_code, dob, id_card_front, id_card_back, selfie_image, submitted_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id, $id_card_number, $laser_code, $dob, $front_img, $back_img, $selfie_img]);

        // 2. อัปเดตสถานะสมาชิกเป็น pending
        $stmt = $pdo->prepare("UPDATE members SET verification_status = 'pending' WHERE id = ?");
        $stmt->execute([$user_id]);

        $pdo->commit();

        // ✅ ส่งกลับไปหน้าเดิมพร้อมสถานะ success
        header("Location: verify.php?status=success");
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        header("Location: verify.php?status=error&msg=" . urlencode($e->getMessage()));
        exit;
    }
}
?>