<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$id = $_GET['id'] ?? null;

if ($id) {
    try {
        // 1. ลบรูปภาพก่อน (ถ้าต้องการลบไฟล์ขยะ) - Optional
        /*
        $stmt_img = $pdo->prepare("SELECT cover_image, back_cover_image FROM books WHERE id = ?");
        $stmt_img->execute([$id]);
        $book = $stmt_img->fetch();
        if ($book) {
            if ($book['cover_image'] && file_exists("../uploads/covers/" . $book['cover_image'])) unlink("../uploads/covers/" . $book['cover_image']);
            if ($book['back_cover_image'] && file_exists("../uploads/covers/" . $book['back_cover_image'])) unlink("../uploads/covers/" . $book['back_cover_image']);
        }
        */

        // 2. ลบข้อมูลในฐานข้อมูล
        $stmt = $pdo->prepare("DELETE FROM books WHERE id = ?");
        
        if ($stmt->execute([$id])) {
            echo_sweetalert('success', 'ลบเสร็จสิ้น', 'ข้อมูลหนังสือถูกลบออกจากระบบแล้ว', 'books.php');
        } else {
            echo_sweetalert('error', 'ผิดพลาด', 'ไม่สามารถลบข้อมูลได้', 'books.php');
        }

    } catch (PDOException $e) {
        // ดักจับ Error Code 1451 (ติด Foreign Key)
        if ($e->getCode() == 23000) { 
            echo_sweetalert('warning', 'ไม่สามารถลบได้', 'หนังสือเล่มนี้มีประวัติการยืม-คืน หรืออยู่ในรายการสั่งซื้อ \nกรุณาเปลี่ยนสถานะเป็น "เลิกจำหน่าย" แทนการลบ', 'books.php');
        } else {
            echo_sweetalert('error', 'Database Error', $e->getMessage(), 'books.php');
        }
    }
} else {
    header("Location: books.php");
}

function echo_sweetalert($icon, $title, $text, $redirectUrl) {
    echo '<!DOCTYPE html>
    <html lang="th">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500&display=swap" rel="stylesheet">
        <style>body { font-family: "Kanit", sans-serif; background-color: #f1f5f9; }</style>
    </head>
    <body>
        <script>
            Swal.fire({
                icon: "' . $icon . '",
                title: "' . $title . '",
                text: "' . $text . '",
                confirmButtonColor: "#0f172a",
                confirmButtonText: "ตกลง"
            }).then(() => {
                window.location.href = "' . $redirectUrl . '";
            });
        </script>
    </body>
    </html>';
    exit;
}
?>