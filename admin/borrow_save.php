<?php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_SESSION['role'] == 'admin') {
    
    $user_type = $_POST['user_type'];
    $book_id = $_POST['book_id'];
    $borrow_date = $_POST['borrow_date'];
    $type = $_POST['type']; // rent หรือ sale
    
    // ข้อมูลผู้ยืม
    $phone = trim($_POST['borrower_phone']);
    $citizen_id = trim($_POST['borrower_citizen_id']);
    $member_id = null;
    $borrower_name = '';

    if ($user_type === 'member') {
        $member_id = $_POST['member_id'];
        $stmtM = $pdo->prepare("SELECT fullname FROM members WHERE id = ?");
        $stmtM->execute([$member_id]);
        $borrower_name = $stmtM->fetchColumn();
    } else {
        $borrower_name = trim($_POST['guest_name']);
        $member_id = null;
    }

    try {
        $pdo->beginTransaction();

        // 1. เช็คสต็อกก่อน
        $stmt = $pdo->prepare("SELECT stock_rent, stock_sale FROM books WHERE id = ?");
        $stmt->execute([$book_id]);
        $book = $stmt->fetch();

        if ($book) {
            
            if ($type == 'rent') {
                // --- กรณีเช่า (Rent) ---
                if ($book['stock_rent'] > 0) {
                    $due_date = $_POST['due_date'];
                    
                    // บันทึก Transaction
                    $sql = "INSERT INTO transactions (book_id, member_id, borrower_name, borrower_phone, borrower_citizen_id, borrow_date, due_date, status, source, type) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, 'borrowed', 'walkin', 'rent')";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$book_id, $member_id, $borrower_name, $phone, $citizen_id, $borrow_date, $due_date]);

                    // ✅ ตัดสต็อกเช่า (-1)
                    $pdo->prepare("UPDATE books SET stock_rent = stock_rent - 1 WHERE id = ?")->execute([$book_id]);
                    
                    $msg = "บันทึกการเช่าสำเร็จ!";
                } else {
                    throw new Exception("สินค้าสำหรับ 'เช่า' หมดสต็อกแล้ว");
                }

            } else {
                // --- กรณีซื้อขาด (Sale) ---
                if ($book['stock_sale'] > 0) {
                    $sql = "INSERT INTO transactions (book_id, member_id, borrower_name, borrower_phone, borrower_citizen_id, borrow_date, due_date, status, source, type) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, 'returned', 'walkin', 'sale')";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$book_id, $member_id, $borrower_name, $phone, $citizen_id, $borrow_date, $borrow_date]);

                    // ✅ ตัดสต็อกขาย (-1)
                    $pdo->prepare("UPDATE books SET stock_sale = stock_sale - 1 WHERE id = ?")->execute([$book_id]);
                    
                    $msg = "บันทึกการขายสำเร็จ!";
                } else {
                    throw new Exception("สินค้าสำหรับ 'ขาย' หมดสต็อกแล้ว");
                }
            }

            // (Optional) อัปเดตสถานะหนังสือเป็น 'หมด' ถ้าสต็อกเหลือ 0 (ทั้งเช่าและขาย)
            // แต่ระบบนี้เราใช้ตัวเลขคุม ดังนั้นไม่ต้องเปลี่ยน status ก็ได้ หรือจะทำเพิ่มก็ได้

            $pdo->commit();
            echo "<script>alert('$msg'); window.location='return.php';</script>";

        } else {
            throw new Exception("ไม่พบข้อมูลหนังสือ");
        }

    } catch (Exception $e) {
        $pdo->rollBack();
        echo "<script>alert('Error: " . $e->getMessage() . "'); window.location='borrow.php';</script>";
    }
} else {
    header("Location: ../index.php");
}
?>