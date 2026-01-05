<?php
require_once 'header.php';

// ✅ ตั้งค่าโซนเวลา
date_default_timezone_set('Asia/Bangkok');

// เช็คสิทธิ์ Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php"); exit;
}

// ดึงข้อมูล
$sql = "SELECT t.*, b.title, b.isbn, 
               COALESCE(m.fullname, t.borrower_name) as borrower_display
        FROM transactions t
        JOIN books b ON t.book_id = b.id
        LEFT JOIN members m ON t.member_id = m.id
        ORDER BY t.borrow_date DESC, t.id DESC";
$history = $pdo->query($sql)->fetchAll();
?>

<div class="mb-8 flex flex-col md:flex-row justify-between items-end gap-4">
    <div>
        <h1 class="text-3xl font-serif font-bold text-navy-900 flex items-center gap-2">
            📜 ประวัติการยืม-คืน
        </h1>
        <p class="text-slate-500 text-sm mt-1">รายการเคลื่อนไหวหนังสือทั้งหมดในระบบ</p>
    </div>
    <div class="bg-white px-4 py-2 rounded-full border border-slate-200 shadow-sm text-sm font-bold text-slate-600">
        รายการทั้งหมด: <span class="text-gold-600 text-lg"><?php echo number_format(count($history)); ?></span>
    </div>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead class="bg-slate-50 border-b border-slate-100">
                <tr>
                    <th class="p-5 font-bold text-slate-500 text-xs uppercase tracking-wider">วันที่ทำรายการ</th>
                    <th class="p-5 font-bold text-slate-500 text-xs uppercase tracking-wider">ผู้ทำรายการ</th>
                    <th class="p-5 font-bold text-slate-500 text-xs uppercase tracking-wider w-1/3">หนังสือ</th>
                    <th class="p-5 font-bold text-slate-500 text-xs uppercase tracking-wider text-center">กำหนดส่ง</th>
                    <th class="p-5 font-bold text-slate-500 text-xs uppercase tracking-wider text-center">วันที่คืน</th>
                    <th class="p-5 font-bold text-slate-500 text-xs uppercase tracking-wider text-center">สถานะ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50 text-sm">
                <?php foreach ($history as $row): ?>
                <tr class="hover:bg-slate-50/50 transition duration-150">
                    
                    <td class="p-5 align-top">
                        <div class="text-navy-900 font-bold text-base"><?php echo date('d/m/Y', strtotime($row['borrow_date'])); ?></div>
                        <div class="text-[10px] text-slate-400 font-mono mt-1">ID: #<?php echo str_pad($row['id'], 5, '0', STR_PAD_LEFT); ?></div>
                    </td>

                    <td class="p-5 align-top">
                        <div class="font-bold text-navy-800 text-sm"><?php echo htmlspecialchars($row['borrower_display']); ?></div>
                        <?php if($row['source'] == 'online'): ?>
                            <span class="inline-block mt-1 text-[9px] font-bold text-indigo-500 bg-indigo-50 px-1.5 py-0.5 rounded border border-indigo-100">Online</span>
                        <?php else: ?>
                            <span class="inline-block mt-1 text-[9px] font-bold text-orange-500 bg-orange-50 px-1.5 py-0.5 rounded border border-orange-100">Walk-in</span>
                        <?php endif; ?>
                    </td>

                    <td class="p-5 align-top">
                        <div class="text-navy-900 font-medium line-clamp-2 leading-relaxed" title="<?php echo htmlspecialchars($row['title']); ?>">
                            <?php echo htmlspecialchars($row['title']); ?>
                        </div>
                        <div class="text-xs text-slate-400 font-mono mt-1 flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            <?php echo htmlspecialchars($row['isbn']); ?>
                        </div>
                    </td>

                    <td class="p-5 text-center align-top text-slate-600">
                        <?php if($row['type'] == 'sale'): ?>
                            <span class="text-slate-300">-</span>
                        <?php else: ?>
                            <span class="font-mono"><?php echo date('d/m/Y', strtotime($row['due_date'])); ?></span>
                        <?php endif; ?>
                    </td>

                    <td class="p-5 text-center align-top">
                        <?php 
                        if ($row['type'] == 'sale') {
                            echo '<span class="font-bold text-emerald-600 text-xs">ซื้อขาด</span>';
                        } elseif (!empty($row['return_date'])) {
                            echo '<div class="text-emerald-600 font-bold font-mono">' . date('d/m/Y', strtotime($row['return_date'])) . '</div>';
                            if(!empty($row['fine_amount']) && $row['fine_amount'] > 0){
                                echo '<div class="text-[10px] text-red-500 mt-1">ค่าปรับ: '.number_format($row['fine_amount']).'฿</div>';
                            }
                        } else {
                            echo '<span class="text-slate-300">-</span>';
                        }
                        ?>
                    </td>

                    <td class="p-5 text-center align-top">
                        <?php if($row['type'] == 'sale'): ?>
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-100">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> ซื้อแล้ว
                            </span>
                        <?php else: ?>
                            <?php 
                                if ($row['status'] == 'returned') {
                                    echo '<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-600 border border-slate-200"><span class="w-1.5 h-1.5 rounded-full bg-slate-500"></span> คืนแล้ว</span>';
                                } else {
                                    $today = date('Y-m-d'); 
                                    if ($today > $row['due_date']) {
                                        echo '<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-red-50 text-red-700 border border-red-100 animate-pulse"><span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> เลยกำหนด</span>';
                                    } else {
                                        echo '<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-gold-50 text-gold-700 border border-gold-100"><span class="w-1.5 h-1.5 rounded-full bg-gold-500"></span> กำลังยืม</span>';
                                    }
                                }
                            ?>
                        <?php endif; ?>
                    </td>

                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'footer.php'; ?>