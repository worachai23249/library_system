<?php
require_once 'config/db.php';

// รหัสผ่านคือ 1234
$password = password_hash("1234", PASSWORD_DEFAULT);

try {
    // 1. ปิดการตรวจสอบ Foreign Key ชั่วคราว
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

    // 2. ล้างข้อมูล (ควรล้างตาราง Transactions ด้วยเพื่อให้ระบบสะอาดจริง)
    $pdo->exec("TRUNCATE TABLE transactions"); 
    $pdo->exec("TRUNCATE TABLE members"); 
    
    // 3. เปิดการตรวจสอบกลับเหมือนเดิม
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    // 4. สร้าง Admin
    $sql = "INSERT INTO members (fullname, email, password, role, phone) VALUES (?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['Admin System', 'admin@test.com', $password, 'admin', '0999999999']);
    
    // 5. สร้าง Member
    $stmt->execute(['Student One', 'student@test.com', $password, 'member', '0888888888']);

    echo "<h1>✅ สร้าง User สำเร็จ!</h1>";
    echo "<ul><li><b>Admin:</b> admin@test.com / 1234</li>";
    echo "<li><b>Member:</b> student@test.com / 1234</li></ul>";
    echo "<br><a href='login.php'>ไปที่หน้าเข้าสู่ระบบ</a>";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>