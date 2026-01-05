<?php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php"); exit;
}

$id = $_POST['id'] ?? null;
$title = $_POST['title'];
$author = $_POST['author'];
$isbn = $_POST['isbn'];
$category_id = $_POST['category_id'];

// รับค่าสต็อก
$stock_rent = $_POST['stock_rent'] ?? 0;
$stock_sale = $_POST['stock_sale'] ?? 0;
$status = ($stock_rent > 0) ? 'available' : 'borrowed';

$description = $_POST['description'] ?? '';

// รับราคา (ทั่วไป) และราคาเช่าตามวัน
$rent_price = $_POST['rent_price'] ?? 0;
$sell_price = $_POST['sell_price'] ?? 0;

// 🔥 รับค่าราคาเช่าใหม่ 3 ระดับ
$rent_price_7  = $_POST['rent_price_7'] ?? 0;
$rent_price_15 = $_POST['rent_price_15'] ?? 0;
$rent_price_30 = $_POST['rent_price_30'] ?? 0;

// ฟังก์ชันอัปโหลดไฟล์ (เพิ่มความปลอดภัยด้วย uniqid)
function uploadFile($fileInputName, $oldFileName = null) {
    if (isset($_FILES[$fileInputName]) && $_FILES[$fileInputName]['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES[$fileInputName]['tmp_name'];
        $fileName = $_FILES[$fileInputName]['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        // เช็ค MIME type
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($fileTmpPath);
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];

        if (in_array($mimeType, $allowedMimeTypes)) {
            $prefix = ($fileInputName == 'cover_image') ? 'front_' : 'back_';
            // ใช้ uniqid ลดโอกาสชื่อซ้ำ
            $newFileName = $prefix . uniqid() . '.' . $fileExtension;
            $uploadFileDir = '../uploads/covers/';
            
            if (!file_exists($uploadFileDir)) {
                mkdir($uploadFileDir, 0777, true);
            }

            if(move_uploaded_file($fileTmpPath, $uploadFileDir . $newFileName)) {
                if ($oldFileName && file_exists($uploadFileDir . $oldFileName)) {
                    unlink($uploadFileDir . $oldFileName);
                }
                return $newFileName;
            }
        }
    }
    return $oldFileName;
}

$cover_image = null;
$back_cover_image = null;
if ($id) {
    $stmt = $pdo->prepare("SELECT cover_image, back_cover_image FROM books WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    $cover_image = $row['cover_image'];
    $back_cover_image = $row['back_cover_image'];
}

$cover_image = uploadFile('cover_image', $cover_image);
$back_cover_image = uploadFile('back_cover_image', $back_cover_image);

// 🔥 SQL Query: เพิ่มฟิลด์ rent_price_7, 15, 30
if ($id) {
    // Update
    $sql = "UPDATE books SET title=?, author=?, isbn=?, category_id=?, 
            rent_price=?, rent_price_7=?, rent_price_15=?, rent_price_30=?, 
            sell_price=?, status=?, stock_rent=?, stock_sale=?, 
            cover_image=?, back_cover_image=?, description=? 
            WHERE id=?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $title, $author, $isbn, $category_id, 
        $rent_price, $rent_price_7, $rent_price_15, $rent_price_30, 
        $sell_price, $status, $stock_rent, $stock_sale, 
        $cover_image, $back_cover_image, $description, 
        $id
    ]);
} else {
    // Insert
    $sql = "INSERT INTO books (
            title, author, isbn, category_id, 
            rent_price, rent_price_7, rent_price_15, rent_price_30, 
            sell_price, status, stock_rent, stock_sale, 
            cover_image, back_cover_image, description
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $title, $author, $isbn, $category_id, 
        $rent_price, $rent_price_7, $rent_price_15, $rent_price_30, 
        $sell_price, $status, $stock_rent, $stock_sale, 
        $cover_image, $back_cover_image, $description
    ]);
}

header("Location: index.php");
exit;
?>