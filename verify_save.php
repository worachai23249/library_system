<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    
    // 1. รับค่าข้อมูลส่วนตัวใหม่ (เพิ่ม email)
    $fullname = trim($_POST['fullname']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']); // ✅ รับค่า Email
    
    // 2. รับค่าข้อมูลบัตร
    $dob = $_POST['dob'];
    $id_card_number = $_POST['id_card_number'];
    $laser_code = $_POST['laser_code'];

    // จัดการอัปโหลดไฟล์
    $upload_dir = 'uploads/kyc/';
    if (!file_exists($upload_dir)) { mkdir($upload_dir, 0777, true); }

    function uploadFile($file, $prefix, $uid, $dir) {
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = $prefix . '_' . $uid . '_' . time() . '.' . $ext;
        move_uploaded_file($file['tmp_name'], $dir . $filename);
        return $filename;
    }

    // อัปโหลดรูป
    $front_img = uploadFile($_FILES['id_card_front'], 'front', $user_id, $upload_dir);
    $back_img = uploadFile($_FILES['id_card_back'], 'back', $user_id, $upload_dir);
    $selfie_img = uploadFile($_FILES['selfie_image'], 'selfie', $user_id, $upload_dir);

    try {
        $pdo->beginTransaction();

        // ✅ 3. อัปเดตข้อมูลส่วนตัวลงตาราง members (เพิ่ม Email)
        $sqlMember = "UPDATE members SET fullname = ?, phone = ?, email = ?, verification_status = 'pending' WHERE id = ?";
        $stmtMember = $pdo->prepare($sqlMember);
        $stmtMember->execute([$fullname, $phone, $email, $user_id]);

        // ✅ 4. อัปเดตข้อมูลใน Session ให้เป็นปัจจุบันทันที
        $_SESSION['user_name'] = $fullname;
        $_SESSION['verification_status'] = 'pending';

        // 5. บันทึกข้อมูล KYC ลงตาราง verifications
        // (ลบข้อมูลเก่าทิ้งก่อน ถ้ามี)
        $pdo->prepare("DELETE FROM verifications WHERE user_id = ?")->execute([$user_id]);

        $sqlVerify = "INSERT INTO verifications (user_id, dob, id_card_number, laser_code, id_card_front, id_card_back, selfie_image) 
                      VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmtVerify = $pdo->prepare($sqlVerify);
        $stmtVerify->execute([$user_id, $dob, $id_card_number, $laser_code, $front_img, $back_img, $selfie_img]);

        $pdo->commit();

        echo "<script>alert('อัปเดตข้อมูลและส่งเอกสารเรียบร้อย กรุณารอเจ้าหน้าที่ตรวจสอบ'); window.location='verify.php';</script>";

    } catch (PDOException $e) {
        $pdo->rollBack();
        echo "Error: " . $e->getMessage();
    }
}
?>