<?php
session_start();
require_once 'config/db.php';

// ตรวจสอบสิทธิ์
if (!isset($_GET['id'])) {
    die("Error: ไม่พบเลขที่คำสั่งซื้อ");
}

$order_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'] ?? 0;
$role = $_SESSION['role'] ?? 'guest';

// ดึงข้อมูลคำสั่งซื้อ
$sql = "SELECT o.*, m.fullname, m.email, a.recipient_name, a.address_line, a.phone
        FROM orders o
        JOIN members m ON o.user_id = m.id
        LEFT JOIN addresses a ON o.address_id = a.id
        WHERE o.id = ?";

if ($role !== 'admin') {
    $sql .= " AND o.user_id = $user_id";
}

$stmt = $pdo->prepare($sql);
$stmt->execute([$order_id]);
$order = $stmt->fetch();

if (!$order) {
    die("Error: ไม่พบข้อมูลคำสั่งซื้อ หรือคุณไม่มีสิทธิ์เข้าถึง");
}

// ดึงรายการสินค้า
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
    <title>Receipt #<?php echo str_pad($order_id, 6, '0', STR_PAD_LEFT); ?></title>
    
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    
    <script src="https://cdn.tailwindcss.com"></script>
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        navy: { 800: '#1e293b', 900: '#0f172a' },
                        gold: { 400: '#fbbf24', 500: '#f59e0b', 600: '#d97706' }
                    },
                    fontFamily: {
                        sans: ['"Kanit"', 'sans-serif'],
                        serif: ['"Playfair Display"', 'serif'],
                    }
                }
            }
        }
    </script>

    <style>
        body { font-family: 'Kanit', sans-serif; background-color: #f1f5f9; }
        @media print {
            body { background-color: white; }
            .no-print { display: none !important; }
            .print-container { 
                box-shadow: none !important; border: none !important; 
                width: 100% !important; margin: 0 !important; padding: 0 !important;
            }
            * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        }
    </style>
</head>
<body class="py-10">

    <div class="max-w-[210mm] mx-auto mb-6 flex justify-between items-center no-print px-4 md:px-0">
        <a href="javascript:window.close()" class="text-slate-500 hover:text-navy-900 font-bold flex items-center gap-2 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            ปิดหน้าต่าง
        </a>
        <button onclick="window.print()" class="bg-navy-900 text-white px-6 py-2.5 rounded-full hover:bg-gold-500 hover:text-navy-900 font-bold shadow-lg transition flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
            พิมพ์ใบเสร็จ
        </button>
    </div>

    <div class="print-container bg-white w-full max-w-[210mm] min-h-[297mm] mx-auto p-12 shadow-2xl relative overflow-hidden">
        
        <div class="absolute top-0 left-0 w-full h-4 bg-gradient-to-r from-navy-900 via-navy-800 to-gold-500"></div>

        <div class="flex justify-between items-start mb-12 mt-4">
            <div>
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 bg-navy-900 text-gold-400 rounded-full flex items-center justify-center border-2 border-gold-500">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                    </div>
                    <div>
                        <h1 class="text-2xl font-serif font-bold text-navy-900 tracking-wider">THE LIBRARY</h1>
                        <p class="text-xs text-gold-600 font-bold uppercase tracking-[0.2em]">Premium Book Service</p>
                    </div>
                </div>
                <div class="text-sm text-slate-500 leading-relaxed">
                    123 ถนนบรรณาลัย แขวงหนังสือ<br>
                    เขตปัญญา กรุงเทพมหานคร 10110<br>
                    โทร: 02-123-4567 | Email: info@thelibrary.com
                </div>
            </div>

            <div class="text-right">
                <h2 class="text-4xl font-serif font-bold text-slate-200 uppercase tracking-widest mb-2">Receipt</h2>
                <div class="text-sm font-bold text-navy-900">
                    เลขที่: <span class="text-gold-600 text-lg">#<?php echo str_pad($order_id, 6, '0', STR_PAD_LEFT); ?></span>
                </div>
                <div class="text-sm text-slate-500 mt-1">
                    วันที่: <?php echo date('d/m/Y', strtotime($order['created_at'])); ?>
                </div>
                <div class="text-sm text-slate-500">
                    เวลา: <?php echo date('H:i', strtotime($order['created_at'])); ?> น.
                </div>
            </div>
        </div>

        <hr class="border-dashed border-slate-300 my-8">

        <div class="grid grid-cols-2 gap-12 mb-10">
            <div>
                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">ลูกค้า (Customer)</h3>
                <div class="text-navy-900 font-bold text-lg"><?php echo htmlspecialchars($order['fullname']); ?></div>
                <div class="text-slate-600 text-sm"><?php echo htmlspecialchars($order['email']); ?></div>
                <div class="text-slate-600 text-sm mt-1">
                    สถานะ: 
                    <?php if(in_array($order['status'], ['paid','shipped','completed'])): ?>
                        <span class="text-emerald-600 font-bold bg-emerald-50 px-2 py-0.5 rounded text-xs border border-emerald-100">ชำระแล้ว (Paid)</span>
                    <?php elseif($order['status'] == 'cancelled'): ?>
                        <span class="text-red-600 font-bold bg-red-50 px-2 py-0.5 rounded text-xs border border-red-100">ยกเลิก</span>
                    <?php else: ?>
                        <span class="text-gold-600 font-bold bg-gold-50 px-2 py-0.5 rounded text-xs border border-gold-100">รอตรวจสอบ</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="text-right">
                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">จัดส่งถึง (Ship To)</h3>
                <div class="text-navy-900 font-bold"><?php echo htmlspecialchars($order['recipient_name']); ?></div>
                <div class="text-slate-600 text-sm leading-relaxed max-w-[250px] ml-auto">
                    <?php echo htmlspecialchars($order['address_line']); ?>
                </div>
                <div class="text-slate-600 text-sm mt-1">โทร: <?php echo htmlspecialchars($order['phone']); ?></div>
            </div>
        </div>

        <div class="mb-8">
            <table class="w-full">
                <thead>
                    <tr class="bg-navy-900 text-white text-xs uppercase tracking-wider">
                        <th class="py-3 px-4 text-left rounded-l-lg">รายการ (Description)</th>
                        <th class="py-3 px-4 text-center">ประเภท</th>
                        <th class="py-3 px-4 text-center">จำนวน</th>
                        <th class="py-3 px-4 text-right">ราคาต่อหน่วย</th>
                        <th class="py-3 px-4 text-right rounded-r-lg">รวม (Total)</th>
                    </tr>
                </thead>
                <tbody class="text-sm text-slate-700">
                    <?php 
                    $grand_total = 0;
                    foreach($items as $index => $item): 
                        $qty = $item['qty']; 
                        $total = $item['price'] * $qty;
                        $grand_total += $total;
                        $bgClass = $index % 2 == 0 ? 'bg-white' : 'bg-slate-50';
                    ?>
                    <tr class="<?php echo $bgClass; ?> border-b border-slate-100">
                        <td class="py-4 px-4">
                            <div class="font-bold text-navy-900"><?php echo htmlspecialchars($item['title']); ?></div>
                            <div class="text-xs text-slate-400 font-mono mt-0.5">ISBN: <?php echo htmlspecialchars($item['isbn']); ?></div>
                        </td>
                        <td class="py-4 px-4 text-center">
                            <span class="<?php echo $item['type'] == 'rent' ? 'bg-blue-50 text-blue-700 border-blue-100' : 'bg-emerald-50 text-emerald-700 border-emerald-100'; ?> px-2 py-0.5 rounded text-[10px] font-bold border whitespace-nowrap">
                                <?php echo $item['type'] == 'rent' ? 'เช่าอ่าน' : 'ซื้อขาด'; ?>
                            </span>
                        </td>
                        <td class="py-4 px-4 text-center font-bold"><?php echo $qty; ?></td>
                        <td class="py-4 px-4 text-right whitespace-nowrap"><?php echo number_format($item['price'], 2); ?></td>
                        <td class="py-4 px-4 text-right font-bold text-navy-900 whitespace-nowrap"><?php echo number_format($total, 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="flex justify-end mb-12">
            <div class="w-full md:w-5/12 space-y-3">
                <div class="flex justify-between text-sm text-slate-600">
                    <span>ยอดรวมย่อย (Subtotal)</span>
                    <span class="font-bold whitespace-nowrap"><?php echo number_format($grand_total, 2); ?> ฿</span>
                </div>
                <div class="flex justify-between text-sm text-slate-600">
                    <span>ค่าจัดส่ง (Shipping)</span>
                    <span class="font-bold whitespace-nowrap">0.00 ฿</span>
                </div>
                <div class="border-t border-slate-300 my-2"></div>
                
                <div class="flex justify-between items-end">
                    <span class="text-navy-900 font-bold text-lg">ยอดสุทธิ (Total)</span>
                    
                    <span class="text-gold-600 font-serif font-bold text-2xl whitespace-nowrap flex items-baseline">
                        <?php echo number_format($grand_total, 2); ?> 
                        <span class="font-sans text-xl ml-1">฿</span>
                    </span>
                </div>
            </div>
        </div>

        <div class="absolute bottom-0 left-0 w-full bg-slate-50 p-8 border-t border-slate-200 text-center">
            <div class="flex justify-center gap-2 mb-4">
                <div class="w-1.5 h-1.5 bg-gold-500 rounded-full"></div>
                <div class="w-1.5 h-1.5 bg-navy-900 rounded-full"></div>
                <div class="w-1.5 h-1.5 bg-gold-500 rounded-full"></div>
            </div>
            <h4 class="font-serif font-bold text-navy-900 text-lg mb-2">Thank you for your order!</h4>
            <p class="text-xs text-slate-500 max-w-md mx-auto leading-relaxed">
                หากมีข้อสงสัยเกี่ยวกับรายการสั่งซื้อ กรุณาติดต่อเราภายใน 7 วัน<br>
                หนังสือเช่าต้องส่งคืนภายในกำหนดเพื่อหลีกเลี่ยงค่าปรับ
            </p>
            <div class="mt-6 pt-4 border-t border-slate-200 text-[10px] text-slate-400">
                &copy; <?php echo date('Y'); ?> The Library System. All rights reserved.
            </div>
        </div>

    </div>
</body>
</html>