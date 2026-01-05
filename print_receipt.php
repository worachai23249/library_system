<?php
session_start();
require_once 'config/db.php';

// 1. ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// 2. รับค่า Order ID
if (!isset($_GET['id'])) {
    die("ไม่พบรหัสคำสั่งซื้อ");
}
$order_id = $_GET['id'];

// 3. ดึงข้อมูล Order + ที่อยู่ + ข้อมูลสมาชิก
$sql = "SELECT o.*, 
               a.recipient_name, a.address_line, a.phone as recipient_phone,
               m.fullname, m.email, m.phone as member_phone
        FROM orders o
        LEFT JOIN addresses a ON o.address_id = a.id
        LEFT JOIN members m ON o.user_id = m.id
        WHERE o.id = ?";

$stmt = $pdo->prepare($sql);
$stmt->execute([$order_id]);
$order = $stmt->fetch();

if (!$order) {
    die("ไม่พบข้อมูลคำสั่งซื้อ");
}

// 4. ตรวจสอบสิทธิ์ (Security Check) และกำหนดลิงก์ย้อนกลับ
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$is_owner = $_SESSION['user_id'] == $order['user_id'];

if (!$is_admin && !$is_owner) {
    die("คุณไม่มีสิทธิ์เข้าถึงใบเสร็จนี้");
}

// ✅ กำหนดลิงก์ปุ่มย้อนกลับตามสถานะ
$back_link = 'history.php'; // ค่าเริ่มต้นสำหรับลูกค้า
if ($is_admin) {
    $back_link = 'admin/orders.php'; // ถ้าเป็นแอดมิน ให้กลับไปหน้าจัดการออเดอร์
}

// 5. ดึงรายการสินค้าใน Order
$sql_items = "SELECT oi.*, b.title, b.isbn 
              FROM order_items oi
              JOIN books b ON oi.book_id = b.id
              WHERE oi.order_id = ?";
$stmt_items = $pdo->prepare($sql_items);
$stmt_items->execute([$order_id]);
$items = $stmt_items->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ใบเสร็จรับเงิน #<?php echo str_pad($order_id, 5, '0', STR_PAD_LEFT); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Kanit', sans-serif; background: #f3f4f6; }
        @media print {
            @page { margin: 0; size: auto; }
            body { background: white; -webkit-print-color-adjust: exact; }
            .no-print { display: none !important; }
            .print-container { 
                box-shadow: none !important; 
                margin: 0 !important; 
                width: 100% !important; 
                max-width: 100% !important;
                padding: 20px !important;
            }
        }
    </style>
</head>
<body class="py-10">

    <div class="max-w-3xl mx-auto mb-6 flex justify-between items-center no-print px-4">
        <a href="<?php echo $back_link; ?>" class="text-gray-600 hover:text-gray-900 flex items-center gap-2 transition hover:bg-gray-200 px-3 py-1.5 rounded-lg">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            ย้อนกลับ
        </a>
        <button onclick="window.print()" class="bg-blue-600 text-white px-6 py-2 rounded-lg shadow hover:bg-blue-700 font-bold flex items-center gap-2 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
            พิมพ์ใบเสร็จ
        </button>
    </div>

    <div class="print-container max-w-3xl mx-auto bg-white p-12 rounded-xl shadow-lg border border-gray-200 text-gray-800 relative overflow-hidden">
        
        <?php if($order['status'] == 'paid' || $order['status'] == 'shipped' || $order['status'] == 'completed'): ?>
        <div class="absolute top-10 right-10 border-4 border-green-500 text-green-500 font-bold text-4xl px-4 py-2 opacity-20 transform -rotate-12 select-none pointer-events-none">
            PAID
        </div>
        <?php endif; ?>

        <div class="flex justify-between items-start mb-10 border-b pb-8">
            <div>
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 bg-blue-600 rounded-lg flex items-center justify-center text-white">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 tracking-wide">E-Library System</h1>
                        <p class="text-sm text-gray-500">ระบบห้องสมุดออนไลน์ครบวงจร</p>
                    </div>
                </div>
                <div class="text-sm text-gray-500 leading-relaxed">
                    28/2 ต.แม่กรณ์ อ.เมือง จ.เชียงราย 57000<br>
                    โทร: 085-558-6077<br>
                    อีเมล: support@library.com
                </div>
            </div>
            <div class="text-right">
                <h2 class="text-3xl font-bold text-gray-800 mb-1">ใบเสร็จรับเงิน</h2>
                <p class="text-gray-500 uppercase tracking-widest text-xs mb-4">Receipt / Tax Invoice</p>
                
                <table class="text-right float-right text-sm">
                    <tr>
                        <td class="text-gray-500 pr-4">เลขที่ใบเสร็จ:</td>
                        <td class="font-bold">#ORD-<?php echo str_pad($order_id, 5, '0', STR_PAD_LEFT); ?></td>
                    </tr>
                    <tr>
                        <td class="text-gray-500 pr-4">วันที่สั่งซื้อ:</td>
                        <td class="font-bold"><?php echo date('d/m/Y', strtotime($order['created_at'])); ?></td>
                    </tr>
                    <tr>
                        <td class="text-gray-500 pr-4">เวลา:</td>
                        <td class="font-bold"><?php echo date('H:i', strtotime($order['created_at'])); ?> น.</td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="mb-10 bg-gray-50 p-6 rounded-lg border border-gray-100">
            <h3 class="text-gray-800 font-bold mb-3 border-b border-gray-200 pb-2">ที่อยู่สำหรับจัดส่ง (Bill To)</h3>
            <div class="text-sm text-gray-600">
                <p class="font-bold text-lg text-gray-800 mb-1"><?php echo htmlspecialchars($order['recipient_name']); ?></p>
                <p class="mb-1"><?php echo htmlspecialchars($order['address_line']); ?></p>
                <p>โทร: <?php echo htmlspecialchars($order['recipient_phone']); ?></p>
                <p>อีเมล: <?php echo htmlspecialchars($order['email']); ?></p>
            </div>
        </div>

        <table class="w-full mb-8">
            <thead>
                <tr class="bg-gray-100 text-gray-600 text-xs uppercase tracking-wider text-left">
                    <th class="py-3 px-4 font-semibold rounded-l-lg">ลำดับ</th>
                    <th class="py-3 px-4 font-semibold w-1/2">รายการหนังสือ</th>
                    <th class="py-3 px-4 font-semibold text-center">ประเภท</th>
                    <th class="py-3 px-4 font-semibold text-center">จำนวน</th>
                    <th class="py-3 px-4 font-semibold text-right">ราคา/หน่วย</th>
                    <th class="py-3 px-4 font-semibold text-right rounded-r-lg">รวม</th>
                </tr>
            </thead>
            <tbody class="text-sm text-gray-700 divide-y divide-gray-100">
                <?php 
                $grand_total = 0;
                $i = 1;
                foreach ($items as $item): 
                    $line_total = $item['price'] * $item['qty'];
                    $grand_total += $line_total;
                ?>
                <tr>
                    <td class="py-4 px-4 text-center text-gray-400"><?php echo $i++; ?></td>
                    <td class="py-4 px-4">
                        <div class="font-bold text-gray-800"><?php echo htmlspecialchars($item['title']); ?></div>
                        <div class="text-xs text-gray-400">ISBN: <?php echo htmlspecialchars($item['isbn']); ?></div>
                    </td>
                    <td class="py-4 px-4 text-center">
                        <?php if($item['type'] == 'buy'): ?>
                            <span class="bg-green-100 text-green-700 px-2 py-1 rounded text-xs font-bold">ซื้อขาด</span>
                        <?php else: ?>
                            <span class="bg-blue-100 text-blue-700 px-2 py-1 rounded text-xs font-bold">เช่าอ่าน</span>
                        <?php endif; ?>
                    </td>
                    <td class="py-4 px-4 text-center font-medium"><?php echo $item['qty']; ?></td>
                    <td class="py-4 px-4 text-right"><?php echo number_format($item['price'], 2); ?></td>
                    <td class="py-4 px-4 text-right font-bold"><?php echo number_format($line_total, 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="flex justify-end mb-12">
            <div class="w-1/2 md:w-1/3">
                <div class="flex justify-between mb-2 text-sm text-gray-600">
                    <span>รวมเป็นเงิน (Subtotal)</span>
                    <span><?php echo number_format($grand_total, 2); ?> ฿</span>
                </div>
                
                <div class="border-t-2 border-gray-200 mt-3 pt-3 flex justify-between items-center">
                    <span class="text-lg font-bold text-gray-800">ยอดสุทธิ (Total)</span>
                    <span class="text-xl font-bold text-blue-600"><?php echo number_format($order['total_price'], 2); ?> ฿</span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-8 mt-16 pt-8 border-t border-gray-200">
            <div class="text-center">
                <div class="h-16"></div> 
                <div class="border-t border-gray-300 w-3/4 mx-auto"></div>
                <p class="text-sm text-gray-500 mt-2">ผู้รับเงิน / Collector</p>
                <p class="text-xs text-gray-400 mt-1">วันที่ ____/____/________</p>
            </div>
            <div class="text-center">
                <div class="h-16"></div> 
                <div class="border-t border-gray-300 w-3/4 mx-auto"></div>
                <p class="text-sm text-gray-500 mt-2">ผู้รับสินค้า / Receiver</p>
                <p class="text-xs text-gray-400 mt-1">วันที่ ____/____/________</p>
            </div>
        </div>

        <div class="mt-12 text-center text-xs text-gray-400">
            <p>ขอบคุณที่ใช้บริการ E-Library System</p>
            <p>หากสินค้ามีปัญหา กรุณาติดต่อภายใน 7 วัน พร้อมใบเสร็จรับเงินฉบับนี้</p>
        </div>

    </div>

</body>
</html>