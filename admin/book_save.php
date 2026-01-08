<?php
session_start();
require_once '../config/db.php';

// เช็คว่าเป็น Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// เช็คว่ามีการส่งข้อมูลมาจริง
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'] ?? '';
    $title = trim($_POST['title']);
    $author = trim($_POST['author']);
    $category_id = $_POST['category_id'];
    $isbn = trim($_POST['isbn']);
    
    // ❌ ลบ price ออก เพราะในฐานข้อมูลไม่มีคอลัมน์นี้
    // $price = $_POST['price'] ?? 0; 
    
    $sell_price = $_POST['sell_price']; // ราคาขาย
    $rent_price = $_POST['rent_price']; // ราคาเช่า
    $rent_price_7 = $_POST['rent_price_7'] ?? 0;
    $rent_price_15 = $_POST['rent_price_15'] ?? 0;
    $rent_price_30 = $_POST['rent_price_30'] ?? 0;

    $stock_rent = $_POST['stock_rent'];
    $stock_sale = $_POST['stock_sale'];
    $description = trim($_POST['description']);
    $status = 'available';

    // จัดการอัปโหลดรูปภาพ
    $cover_image = $_POST['old_cover'] ?? '';
    if (!empty($_FILES['cover_image']['name'])) {
        $ext = pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION);
        $new_name = "front_" . uniqid() . "." . $ext;
        move_uploaded_file($_FILES['cover_image']['tmp_name'], "../uploads/covers/" . $new_name);
        $cover_image = $new_name;
    }

    $back_cover_image = $_POST['old_back_cover'] ?? '';
    if (!empty($_FILES['back_cover_image']['name'])) {
        $ext = pathinfo($_FILES['back_cover_image']['name'], PATHINFO_EXTENSION);
        $new_name = "back_" . uniqid() . "." . $ext;
        move_uploaded_file($_FILES['back_cover_image']['tmp_name'], "../uploads/covers/" . $new_name);
        $back_cover_image = $new_name;
    }

    try {
        if ($id) {
            // --- กรณีแก้ไข (Update) ---
            // ❌ ลบ price=? ออกจาก SQL
            $sql = "UPDATE books SET 
                    title=?, author=?, category_id=?, isbn=?, sell_price=?, rent_price=?, 
                    rent_price_7=?, rent_price_15=?, rent_price_30=?,
                    stock_rent=?, stock_sale=?, description=?, cover_image=?, back_cover_image=? 
                    WHERE id=?";
            $stmt = $pdo->prepare($sql);
            // ❌ ลบ $price ออกจาก params
            $params = [$title, $author, $category_id, $isbn, $sell_price, $rent_price, 
                       $rent_price_7, $rent_price_15, $rent_price_30,
                       $stock_rent, $stock_sale, $description, $cover_image, $back_cover_image, $id];
            $action_text = "แก้ไขข้อมูลสำเร็จ";
        } else {
            // --- กรณีเพิ่มใหม่ (Insert) ---
            // ❌ ลบ price ออกจาก SQL
            $sql = "INSERT INTO books (title, author, category_id, isbn, sell_price, rent_price, rent_price_7, rent_price_15, rent_price_30, stock_rent, stock_sale, description, cover_image, back_cover_image, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            // ❌ ลบ $price ออกจาก params
            $params = [$title, $author, $category_id, $isbn, $sell_price, $rent_price, $rent_price_7, $rent_price_15, $rent_price_30, $stock_rent, $stock_sale, $description, $cover_image, $back_cover_image, $status];
            $action_text = "เพิ่มหนังสือใหม่สำเร็จ";
        }

        if ($stmt->execute($params)) {
            echo_sweetalert('success', 'สำเร็จ!', $action_text, 'books.php');
        } else {
            echo_sweetalert('error', 'เกิดข้อผิดพลาด', 'ไม่สามารถบันทึกข้อมูลได้', 'book_form.php');
        }

    } catch (PDOException $e) {
        echo_sweetalert('error', 'Database Error', $e->getMessage(), 'book_form.php');
    }
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