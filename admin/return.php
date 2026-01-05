<?php
require_once '../config/db.php';
require_once '../admin/header.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { header("Location: ../index.php"); exit; }

$FINE_RATE_PER_DAY = 10; 

$sql = "SELECT t.*, b.title, b.isbn, b.cover_image,
               COALESCE(m.fullname, t.borrower_name) as borrower_display,
               m.email
        FROM transactions t
        JOIN books b ON t.book_id = b.id
        LEFT JOIN members m ON t.member_id = m.id
        WHERE t.status = 'borrowed'
        ORDER BY t.due_date ASC";
$borrows = $pdo->query($sql)->fetchAll();
?>

<div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
    <div>
        <h1 class="text-3xl font-serif font-bold text-navy-900 flex items-center gap-2">
            ⏳ รายการที่กำลังถูกยืม
        </h1>
        <p class="text-sm text-slate-500 mt-1">ติดตามรายการหนังสือที่ยังไม่คืนและจัดการค่าปรับ</p>
    </div>
    <a href="borrow.php" class="bg-gold-500 text-white px-6 py-2.5 rounded-full hover:bg-gold-600 shadow-lg font-bold flex items-center gap-2 text-sm transition transform hover:-translate-y-0.5">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
        ทำรายการยืม (Walk-in)
    </a>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left align-middle">
            <thead class="bg-slate-50 border-b border-slate-100 text-slate-500 uppercase text-xs font-bold tracking-wider">
                <tr>
                    <th class="p-5 w-1/3">หนังสือ</th>
                    <th class="p-5">ผู้ยืม</th>
                    <th class="p-5 text-center">ช่องทาง</th>
                    <th class="p-5 text-center">กำหนดส่ง</th>
                    <th class="p-5 text-center">สถานะ</th>
                    <th class="p-5 text-center">จัดการ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50 text-sm">
                <?php foreach ($borrows as $row): ?>
                    <?php 
                        $today = new DateTime();
                        $due = new DateTime($row['due_date']);
                        $diff = $today->diff($due);
                        $days_overdue = ($today > $due) ? $diff->days : 0;
                        $fine_amount = $days_overdue * $FINE_RATE_PER_DAY;

                        $dataJson = htmlspecialchars(json_encode([
                            'id' => $row['id'],
                            'title' => $row['title'],
                            'borrower' => $row['borrower_display'],
                            'days_overdue' => $days_overdue,
                            'fine' => $fine_amount
                        ]), ENT_QUOTES, 'UTF-8');
                    ?>
                    <tr class="hover:bg-slate-50/50 transition">
                        <td class="p-5">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-16 rounded shadow-sm overflow-hidden flex-shrink-0 bg-slate-200">
                                    <?php if($row['cover_image']): ?>
                                        <img src="../uploads/covers/<?php echo $row['cover_image']; ?>" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <div class="w-full h-full flex items-center justify-center text-xs text-slate-400">No Pic</div>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <div class="font-bold text-navy-900 text-base line-clamp-1"><?php echo htmlspecialchars($row['title']); ?></div>
                                    <div class="text-xs text-slate-500 font-mono mt-1 flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"></path></svg>
                                        <?php echo htmlspecialchars($row['isbn']); ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        
                        <td class="p-5">
                            <div class="font-bold text-navy-800"><?php echo htmlspecialchars($row['borrower_display']); ?></div>
                            <div class="text-xs text-slate-400"><?php echo htmlspecialchars($row['email'] ?? '-'); ?></div>
                        </td>
                        
                        <td class="p-5 text-center">
                            <?php if($row['source'] == 'online'): ?>
                                <span class="bg-indigo-50 text-indigo-600 px-2.5 py-1 rounded-lg text-xs font-bold border border-indigo-100">Web</span>
                            <?php else: ?>
                                <span class="bg-orange-50 text-orange-600 px-2.5 py-1 rounded-lg text-xs font-bold border border-orange-100">Shop</span>
                            <?php endif; ?>
                        </td>

                        <td class="p-5 text-center">
                            <div class="font-bold text-sm <?php echo $days_overdue > 0 ? 'text-red-600' : 'text-slate-600'; ?>">
                                <?php echo date('d/m/Y', strtotime($row['due_date'])); ?>
                            </div>
                        </td>
                        
                        <td class="p-5 text-center">
                            <?php if($days_overdue > 0): ?>
                                <span class="bg-red-50 text-red-600 px-2.5 py-1 rounded-full text-xs font-bold border border-red-100 flex items-center justify-center gap-1">
                                    <span class="w-1.5 h-1.5 bg-red-500 rounded-full animate-pulse"></span>
                                    เกิน <?php echo $days_overdue; ?> วัน
                                </span>
                                <div class="text-[10px] text-red-500 mt-1 font-bold">ปรับ <?php echo number_format($fine_amount); ?>฿</div>
                            <?php else: ?>
                                <span class="bg-emerald-50 text-emerald-600 px-2.5 py-1 rounded-full text-xs font-bold border border-emerald-100 flex items-center justify-center gap-1">
                                    <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></span>
                                    ปกติ
                                </span>
                            <?php endif; ?>
                        </td>

                        <td class="p-5 text-center">
                            <button onclick="processReturn(<?php echo $dataJson; ?>)" 
                               class="bg-navy-900 text-white px-4 py-2 rounded-lg hover:bg-gold-500 hover:text-navy-900 text-xs font-bold shadow-md transition transform active:scale-95 flex items-center gap-2 mx-auto">
                               <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                               รับคืน
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if(count($borrows) == 0): ?>
        <div class="p-12 text-center text-slate-400">
            <svg class="w-16 h-16 mx-auto mb-4 text-slate-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <p>ไม่มีรายการที่กำลังยืมอยู่</p>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function processReturn(data) {
    let htmlContent = `
        <div class="text-left bg-slate-50 p-4 rounded-lg mb-4 border border-slate-200">
            <div class="text-navy-900">ผู้ยืม: <b class="font-serif">${data.borrower}</b></div>
            <div class="text-slate-600 text-sm mt-1">หนังสือ: ${data.title}</div>
        </div>
    `;
    let confirmColor = '#d97706'; // Gold
    let iconType = 'question';

    if (data.fine > 0) {
        htmlContent += `
            <div class="text-red-600 font-bold text-lg mb-2 bg-red-50 p-3 rounded-lg border border-red-100">
                ⚠️ เกินกำหนด ${data.days_overdue} วัน<br>
                ยอดค่าปรับ: ${data.fine} บาท
            </div>
            <div class="text-xs text-slate-400 mb-3">กรุณาเก็บเงินค่าปรับก่อนยืนยัน</div>
        `;
        confirmColor = '#ef4444'; // Red
        iconType = 'warning';
    }

    htmlContent += `
        <div class="text-left mt-2">
            <label class="text-xs font-bold text-navy-900 mb-1 block uppercase">หมายเหตุ (สภาพหนังสือ/อื่นๆ)</label>
            <textarea id="swal-return-note" class="w-full border border-slate-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-gold-500 outline-none" rows="2" placeholder="เช่น ปกยับ, หน้าขาด, ปกติ..."></textarea>
        </div>
    `;

    Swal.fire({
        title: '<span class="font-serif text-navy-900">ยืนยันรับคืนหนังสือ?</span>',
        html: htmlContent,
        icon: iconType,
        showCancelButton: true,
        confirmButtonColor: confirmColor,
        cancelButtonColor: '#64748b',
        confirmButtonText: 'ยืนยันการคืน',
        cancelButtonText: 'ยกเลิก',
        customClass: { popup: 'rounded-2xl' },
        preConfirm: () => {
            return document.getElementById('swal-return-note').value;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const note = result.value || ''; 
            window.location.href = `return_save.php?id=${data.id}&fine=${data.fine}&note=${encodeURIComponent(note)}`;
        }
    });
}
</script>

<?php require_once 'footer.php'; ?>