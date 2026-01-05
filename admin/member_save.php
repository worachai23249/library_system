<?php
session_start();
require_once '../config/db.php';

// เช็คสิทธิ์ Admin
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php"); exit;
}

$id = $_POST['id'] ?? null;
$fullname = trim($_POST['fullname']);
$email = trim($_POST['email']);
$phone = trim($_POST['phone']);
$password = $_POST['password'];

try {
    if ($id) {
        // --- กรณีแก้ไข (Update) ---
        if (!empty($password)) {
            // ถ้ากรอกรหัสใหม่มาด้วย ให้เปลี่ยนรหัสผ่าน
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql = "UPDATE members SET fullname=?, email=?, phone=?, password=? WHERE id=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$fullname, $email, $phone, $hashed_password, $id]);
        } else {
            // ถ้าไม่กรอกรหัส ให้แก้แค่ข้อมูลส่วนตัว
            $sql = "UPDATE members SET fullname=?, email=?, phone=? WHERE id=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$fullname, $email, $phone, $id]);
        }
    } else {
        // --- กรณีเพิ่มใหม่ (Insert) ---
        // ตรวจสอบก่อนว่าอีเมลซ้ำไหม
        $check = $pdo->prepare("SELECT id FROM members WHERE email = ?");
        $check->execute([$email]);
        if ($check->rowCount() > 0) {
            echo "<script>alert('อีเมลนี้มีอยู่ในระบบแล้ว'); window.history.back();</script>";
            exit;
        }

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        // กำหนด role เป็น 'member' เสมอ
        $sql = "INSERT INTO members (fullname, email, password, phone, role) VALUES (?, ?, ?, ?, 'member')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$fullname, $email, $hashed_password, $phone]);
    }

    header("Location: members.php");

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    echo "<br><a href='members.php'>กลับ</a>";
}
?>