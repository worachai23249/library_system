<?php
session_start();
require_once 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    
    // 1. รับค่ารายการที่เลือกมา
    $checkout_items_str = $_POST['checkout_items'] ?? '';
    if (empty($checkout_items_str)) { header("Location: cart.php"); exit; }
    
    $selected_ids = explode(',', $checkout_items_str);
    
    $user_id = $_SESSION['user_id'];
    $address_id = $_POST['address_id'];
    $total_price = $_POST['total_price'];
    
    // 2. อัปโหลดสลิป
    $slip_image = null;
    if (isset($_FILES['slip_image']) && $_FILES['slip_image']['error'] == 0) {
        $ext = pathinfo($_FILES['slip_image']['name'], PATHINFO_EXTENSION);
        $new_name = "slip_" . time() . "_" . rand(1000,9999) . "." . $ext;
        
        $target_dir = "uploads/slips/";
        
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        if(move_uploaded_file($_FILES['slip_image']['tmp_name'], $target_dir . $new_name)) {
            $slip_image = $new_name;
        }
    }

    try {
        $pdo->beginTransaction();

        // 3. สร้าง Order หลัก
        $sql = "INSERT INTO orders (user_id, address_id, total_price, slip_image, status) VALUES (?, ?, ?, ?, 'pending')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id, $address_id, $total_price, $slip_image]);
        $order_id = $pdo->lastInsertId();

        // 4. วนลูปบันทึกสินค้า
        foreach ($selected_ids as $book_id) {
            if (isset($_SESSION['cart'][$book_id])) {
                
                $stmtBook = $pdo->prepare("SELECT sell_price, rent_price FROM books WHERE id = ?");
                $stmtBook->execute([$book_id]);
                $bookDB = $stmtBook->fetch();

                if ($bookDB) {
                    $cartItem = $_SESSION['cart'][$book_id];
                    $type = $cartItem['type'];
                    $qty = $cartItem['qty'];
                    $price = ($type == 'buy') ? $bookDB['sell_price'] : $bookDB['rent_price'];

                    $sqlItem = "INSERT INTO order_items (order_id, book_id, type, qty, price) VALUES (?, ?, ?, ?, ?)";
                    $pdo->prepare($sqlItem)->execute([$order_id, $book_id, $type, $qty, $price]);

                    // 5. ลบออกจากตะกร้า
                    unset($_SESSION['cart'][$book_id]);
                }
            }
        }

        $pdo->commit();

        // --- ส่วนแสดง Popup แจ้งเตือนเรื่องวีดีโอ ---
        echo "
        <!DOCTYPE html>
        <html lang='th'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Processing...</title>
            <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
            <script src='https://cdn.tailwindcss.com'></script>
            <link href='https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;700&display=swap' rel='stylesheet'>
            <style> body { font-family: 'Kanit', sans-serif; background: #f3f4f6; } </style>
        </head>
        <body>
        <script>
            Swal.fire({
                title: '✅ สั่งซื้อสำเร็จ',
                html: `
                    <div class='text-left'>
                        <p class='mb-4 text-gray-600'>ขอบคุณที่ใช้บริการ ระบบได้รับคำสั่งซื้อเรียบร้อยแล้ว</p>
                        
                        <div class='bg-red-50 border-l-4 border-red-500 p-4 rounded shadow-sm'>
                            <div class='flex items-start'>
                                <div class='flex-shrink-0'>
                                    <svg class='h-5 w-5 text-red-500' fill='currentColor' viewBox='0 0 20 20'>
                                        <path fill-rule='evenodd' d='M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z' clip-rule='evenodd'/>
                                    </svg>
                                </div>
                                <div class='ml-3'>
                                    <h3 class='text-lg font-bold text-red-800'>⚠️ ข้อควรปฏิบัติสำคัญ</h3>
                                    <div class='mt-2 text-sm text-red-700 space-y-2'>
                                        <p>
                                            กรุณา <strong>ถ่ายคลิปวิดีโอขณะเปิดพัสดุอย่างละเอียด</strong> (โดยไม่ตัดต่อ) 
                                            เพื่อใช้เป็นหลักฐานยืนยันสภาพสินค้าทันทีที่ได้รับ
                                        </p>
                                        <p>
                                            หากหนังสือมีความเสียหายจากการขนส่ง และท่าน <strong>ไม่มีคลิปวิดีโอยืนยัน</strong> 
                                            ทางร้านขอสงวนสิทธิ์ในการพิจารณาความรับผิดชอบ
                                        </p>
                                        <p class='font-semibold'>
                                            *กรณีเช่า: หากคืนหนังสือแล้วพบความเสียหายโดยไม่มีหลักฐานยืนยัน 
                                            ท่านอาจต้องรับผิดชอบค่าเสียหายตามจริง
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `,
                icon: 'success',
                confirmButtonText: 'รับทราบและยอมรับเงื่อนไข',
                confirmButtonColor: '#d33',
                width: '600px',
                allowOutsideClick: false
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'history.php';
                }
            });
        </script>
        </body>
        </html>
        ";

    } catch (Exception $e) {
        $pdo->rollBack();
        echo "Error: " . $e->getMessage();
    }

} else {
    header("Location: index.php");
}
?>