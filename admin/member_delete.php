<?php
session_start();
require_once '../config/db.php';

// ตรวจสอบว่าเป็น Admin และมี ID ส่งมา
if (isset($_GET['id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    $id = $_GET['id'];
    
    try {
        // เริ่ม Transaction (เพื่อให้การลบทุกขั้นตอนสำเร็จพร้อมกัน ถ้าพลาดให้ยกเลิกทั้งหมด)
        $pdo->beginTransaction();

        // 1. ลบประวัติการยืม-คืน (Transactions) ของสมาชิกคนนี้
        $stmt = $pdo->prepare("DELETE FROM transactions WHERE member_id = ?");
        $stmt->execute([$id]);

        // 2. ลบที่อยู่จัดส่ง (Addresses)
        $stmt = $pdo->prepare("DELETE FROM addresses WHERE user_id = ?");
        $stmt->execute([$id]);

        // 3. จัดการเรื่องออเดอร์ (Orders) ถ้ามี
        // หา Order ID ของสมาชิกคนนี้ก่อน
        $stmt = $pdo->prepare("SELECT id FROM orders WHERE user_id = ?");
        $stmt->execute([$id]);
        $order_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($order_ids)) {
            // ลบรายการสินค้าในออเดอร์ (Order Items)
            $inQuery = implode(',', array_fill(0, count($order_ids), '?'));
            $stmt = $pdo->prepare("DELETE FROM order_items WHERE order_id IN ($inQuery)");
            $stmt->execute($order_ids);

            // ลบตัวออเดอร์ (Orders)
            $stmt = $pdo->prepare("DELETE FROM orders WHERE user_id = ?");
            $stmt->execute([$id]);
        }

        // 4. สุดท้าย... ลบสมาชิกออกจากระบบ (Members)
        $stmt = $pdo->prepare("DELETE FROM members WHERE id = ?");
        $stmt->execute([$id]);
        
        // ยืนยันการทำงานทั้งหมด
        $pdo->commit();

    } catch (PDOException $e) {
        // ถ้ามีอะไรผิดพลาด ให้ย้อนกลับ (Rollback)
        $pdo->rollBack();
        echo "<script>
            alert('เกิดข้อผิดพลาดในการลบ: " . $e->getMessage() . "'); 
            window.location='members.php';
        </script>";
        exit;
    }
}

// ลบเสร็จแล้วกลับไปหน้ารายชื่อ
header("Location: members.php");
exit;
?>