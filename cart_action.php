<?php
session_start();

if (file_exists('config/db.php')) {
    require_once 'config/db.php';
} elseif (file_exists('../config/db.php')) {
    require_once '../config/db.php';
} else {
    echo json_encode(['status' => 'error', 'message' => 'หาไฟล์ config/db.php ไม่เจอ']);
    exit;
}

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'กรุณาเข้าสู่ระบบ']);
    exit;
}

$action = $_POST['action'] ?? '';

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if ($action === 'add') {
    $book_id = $_POST['book_id'];
    $type = $_POST['type']; 
    $rent_days = isset($_POST['rent_days']) ? (int)$_POST['rent_days'] : 7;
    $qty = isset($_POST['qty']) ? (int)$_POST['qty'] : 1;
    if ($qty < 1) $qty = 1;

    if (isset($_SESSION['cart'][$book_id])) {
        if ($type == 'buy' && $_SESSION['cart'][$book_id]['type'] == 'buy') {
            $_SESSION['cart'][$book_id]['qty'] += $qty;
        } else {
            $_SESSION['cart'][$book_id] = [
                'type' => $type,
                'qty' => $qty,
                'rent_days' => $rent_days,
                'added_at' => time()
            ];
        }
    } else {
        $_SESSION['cart'][$book_id] = [
            'type' => $type,
            'qty' => $qty,
            'rent_days' => $rent_days,
            'added_at' => time()
        ];
    }

    // 🔥 คำนวณยอดรวมจำนวนสินค้าทั้งหมด (Total Qty)
    $total_qty = 0;
    foreach ($_SESSION['cart'] as $item) {
        $total_qty += $item['qty'];
    }

    echo json_encode([
        'status' => 'success', 
        'total_qty' => $total_qty, // ส่งค่ายอดรวมกลับไป
        'message' => 'เพิ่มลงตะกร้าสำเร็จ'
    ]);
    exit;
}

if ($action === 'remove') {
    $book_id = $_POST['book_id'];
    unset($_SESSION['cart'][$book_id]);
    echo json_encode(['status' => 'success']);
    exit;
}
?>