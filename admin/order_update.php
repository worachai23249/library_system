<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { header("Location: ../login.php"); exit; }

if (isset($_GET['id']) && isset($_GET['status'])) {
    $order_id = $_GET['id'];
    $status = $_GET['status'];
    $reason = isset($_GET['reason']) ? $_GET['reason'] : null;
    
    // รับค่าขนส่งและเลขพัสดุ
    $carrier = isset($_GET['carrier']) ? $_GET['carrier'] : null;
    $tracking = isset($_GET['tracking']) ? $_GET['tracking'] : null;

    try {
        $pdo->beginTransaction();

        $stmtOrder = $pdo->prepare("SELECT user_id FROM orders WHERE id = ?");
        $stmtOrder->execute([$order_id]);
        $order = $stmtOrder->fetch();
        $user_id = $order['user_id'];

        // อัปเดตข้อมูล (เพิ่ม carrier และ tracking_number)
        $sql = "UPDATE orders SET status = ?, cancellation_reason = ?, carrier = ?, tracking_number = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$status, $reason, $carrier, $tracking, $order_id]);

        // [ส่วนตัดสต็อก] เมื่ออนุมัติ (Paid)
        if ($status == 'paid') {
            $stmtItems = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
            $stmtItems->execute([$order_id]);
            $items = $stmtItems->fetchAll();

            foreach ($items as $item) {
                for ($i = 0; $i < $item['qty']; $i++) {
                    $borrowDate = date('Y-m-d');
                    if ($item['type'] == 'rent') {
                        $dueDate = date('Y-m-d', strtotime('+7 days'));
                        $sqlTrans = "INSERT INTO transactions (book_id, member_id, borrow_date, due_date, status, source, type) VALUES (?, ?, ?, ?, 'borrowed', 'online', 'rent')";
                        $pdo->prepare($sqlTrans)->execute([$item['book_id'], $user_id, $borrowDate, $dueDate]);
                        $pdo->prepare("UPDATE books SET stock_rent = stock_rent - 1 WHERE id = ?")->execute([$item['book_id']]);
                    } else {
                        $sqlTrans = "INSERT INTO transactions (book_id, member_id, borrow_date, due_date, status, source, type) VALUES (?, ?, ?, ?, 'returned', 'online', 'sale')";
                        $pdo->prepare($sqlTrans)->execute([$item['book_id'], $user_id, $borrowDate, $borrowDate]);
                        $pdo->prepare("UPDATE books SET stock_sale = stock_sale - 1 WHERE id = ?")->execute([$item['book_id']]);
                    }
                }
            }
        }

        // --- สร้างข้อความแจ้งเตือน (Notification) ---
        $msg_title = ""; $msg_body = "";
        
        if ($status == 'paid') {
            $msg_title = "✅ อนุมัติแล้ว: Order #".str_pad($order_id, 5, '0', STR_PAD_LEFT);
            $msg_body = "รายการของคุณได้รับการอนุมัติเรียบร้อยแล้ว เตรียมจัดส่งสินค้า";
        } elseif ($status == 'shipped') {
            $msg_title = "🚚 จัดส่งแล้ว: Order #".str_pad($order_id, 5, '0', STR_PAD_LEFT);
            $msg_body = "ขนส่ง: $carrier \nเลขพัสดุ: $tracking \nตรวจสอบสถานะได้ในเมนูประวัติการสั่งซื้อ";
        } elseif ($status == 'cancelled') {
            $msg_title = "❌ ถูกยกเลิก: Order #".str_pad($order_id, 5, '0', STR_PAD_LEFT);
            $msg_body = "เหตุผล: " . $reason;
        }

        if (!empty($msg_title)) {
            $sqlMsg = "INSERT INTO messages (user_id, title, message) VALUES (?, ?, ?)";
            $pdo->prepare($sqlMsg)->execute([$user_id, $msg_title, $msg_body]);
        }

        $pdo->commit();
        echo "<script>window.location.href = 'orders.php';</script>";

    } catch (PDOException $e) {
        $pdo->rollBack();
        echo "<script>alert('Error: " . $e->getMessage() . "'); window.location.href = 'orders.php';</script>";
    }
} else {
    header("Location: orders.php");
}
?>