<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php"); exit;
}

if (isset($_GET['id'])) {
    $trans_id = $_GET['id'];
    // รับค่า fine จาก URL ถ้าไม่มีให้เป็น 0
    $fine_amount = isset($_GET['fine']) ? floatval($_GET['fine']) : 0.00;
    
    // รับค่า note จาก URL (ถ้าไม่มีเป็น NULL)
    $return_note = isset($_GET['note']) && trim($_GET['note']) !== '' ? $_GET['note'] : null;
    
    $return_date = date('Y-m-d');

    try {
        $pdo->beginTransaction();

        // 1. ตรวจสอบสถานะก่อน
        $stmt = $pdo->prepare("SELECT book_id, status FROM transactions WHERE id = ?");
        $stmt->execute([$trans_id]);
        $trans = $stmt->fetch();

        if ($trans && $trans['status'] == 'borrowed') {
            $book_id = $trans['book_id'];

            // 2. อัปเดตสถานะ + วันที่คืน + ค่าปรับ + หมายเหตุ
            $sql = "UPDATE transactions 
                    SET status = 'returned', 
                        return_date = ?, 
                        fine_amount = ?,
                        return_note = ? 
                    WHERE id = ?";
            $updateTrans = $pdo->prepare($sql);
            $updateTrans->execute([$return_date, $fine_amount, $return_note, $trans_id]);

            // 3. คืนสต็อกเช่า (+1)
            $updateBook = $pdo->prepare("UPDATE books SET stock_rent = stock_rent + 1 WHERE id = ?");
            $updateBook->execute([$book_id]);

            $pdo->commit();

            // แจ้งเตือน
            $msg = "รับคืนหนังสือเรียบร้อย";
            if($fine_amount > 0) {
                $msg .= " (บันทึกยอดค่าปรับ " . number_format($fine_amount) . " บาทแล้ว)";
            }

            echo "<script>
                alert('$msg');
                window.location.href = 'return.php';
            </script>";
        } else {
            echo "<script>
                alert('ไม่พบรายการ หรือรายการนี้ถูกคืนไปแล้ว');
                window.location.href = 'return.php';
            </script>";
        }

    } catch (PDOException $e) {
        $pdo->rollBack();
        echo "Error: " . $e->getMessage();
    }
} else {
    header("Location: return.php");
}
?>