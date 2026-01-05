<?php
require_once 'header.php';

$sql = "SELECT t.*, b.title, b.isbn, 
               COALESCE(m.fullname, t.borrower_name) as borrower_display,
               m.phone as member_phone, m.email as member_email 
        FROM transactions t
        JOIN books b ON t.book_id = b.id
        LEFT JOIN members m ON t.member_id = m.id
        WHERE t.status = 'borrowed' AND t.due_date < CURDATE()
        ORDER BY t.due_date ASC";
$overdue = $pdo->query($sql)->fetchAll();
?>

<div class="flex flex-col md:flex-row justify-between items-start md:items-end mb-8 gap-4 no-print">
    <div>
        <h1 class="text-3xl font-serif font-bold text-red-600 flex items-center gap-3">
            <span class="bg-red-100 p-2 rounded-lg"><svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg></span>
            รายงานการค้างส่ง
        </h1>
        <p class="text-slate-500 mt-2 font-light">รายชื่อผู้ที่ยังไม่คืนหนังสือตามกำหนดเวลา (Overdue Report)</p>
    </div>
    
    <?php if(count($overdue) > 0): ?>
    <button onclick="window.print()" class="bg-navy-900 text-white px-5 py-2.5 rounded-full hover:bg-gold-500 hover:text-navy-900 transition flex items-center gap-2 text-sm font-bold shadow-md transform hover:-translate-y-0.5">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
        พิมพ์รายงาน
    </button>
    <?php endif; ?>
</div>

<?php if (count($overdue) > 0): ?>
    <div class="bg-white rounded-2xl shadow-sm border border-red-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-red-50/50 border-b border-red-100">
                    <tr>
                        <th class="p-5 text-xs font-bold text-red-800 uppercase tracking-wider">ผู้ยืม</th>
                        <th class="p-5 text-xs font-bold text-red-800 uppercase tracking-wider">ข้อมูลติดต่อ</th>
                        <th class="p-5 text-xs font-bold text-red-800 uppercase tracking-wider">หนังสือที่ค้าง</th>
                        <th class="p-5 text-xs font-bold text-red-800 uppercase tracking-wider text-center">กำหนดส่ง</th>
                        <th class="p-5 text-xs font-bold text-red-800 uppercase tracking-wider text-center">สถานะ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-red-50">
                    <?php foreach ($overdue as $row): ?>
                    <?php
                        $days_late = (strtotime(date('Y-m-d')) - strtotime($row['due_date'])) / (60 * 60 * 24);
                    ?>
                    <tr class="hover:bg-red-50/30 transition">
                        <td class="p-5 align-top">
                            <div class="font-bold text-navy-900 text-base"><?php echo htmlspecialchars($row['borrower_display']); ?></div>
                            <?php if($row['source'] == 'walkin'): ?>
                                <span class="text-[10px] font-bold text-orange-600 bg-orange-50 px-2 py-0.5 rounded border border-orange-100 mt-1 inline-block">Walk-in</span>
                            <?php else: ?>
                                <span class="text-[10px] font-bold text-blue-600 bg-blue-50 px-2 py-0.5 rounded border border-blue-100 mt-1 inline-block">Member</span>
                            <?php endif; ?>
                        </td>
                        <td class="p-5 align-top text-sm">
                            <?php if(!empty($row['member_phone']) || !empty($row['member_email'])): ?>
                                <div class="space-y-1">
                                    <?php if($row['member_phone']): ?>
                                        <div class="flex items-center gap-2 text-slate-700 font-medium">
                                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                                            <a href="tel:<?php echo htmlspecialchars($row['member_phone']); ?>" class="hover:text-gold-600 underline decoration-slate-300 decoration-1 underline-offset-2"><?php echo htmlspecialchars($row['member_phone']); ?></a>
                                        </div>
                                    <?php endif; ?>
                                    <?php if($row['member_email']): ?>
                                        <div class="flex items-center gap-2 text-slate-500">
                                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                            <?php echo htmlspecialchars($row['member_email']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <span class="text-slate-400 text-xs italic">- ไม่พบข้อมูลติดต่อ -</span>
                            <?php endif; ?>
                        </td>
                        <td class="p-5 align-top">
                            <div class="text-sm font-bold text-navy-900"><?php echo htmlspecialchars($row['title']); ?></div>
                            <div class="text-xs text-slate-400 font-mono mt-0.5"><?php echo htmlspecialchars($row['isbn']); ?></div>
                        </td>
                        <td class="p-5 text-center align-top">
                            <div class="text-red-600 font-bold text-sm bg-red-50 px-3 py-1 rounded-lg border border-red-100 inline-block shadow-sm">
                                <?php echo date('d/m/Y', strtotime($row['due_date'])); ?>
                            </div>
                        </td>
                        <td class="p-5 text-center align-top">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-red-600 text-white shadow-md animate-pulse">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                +<?php echo floor($days_late); ?> วัน
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php else: ?>
    <div class="bg-emerald-50 border border-emerald-100 rounded-2xl p-12 text-center shadow-sm">
        <div class="w-20 h-20 bg-emerald-100 text-emerald-500 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        </div>
        <h3 class="text-xl font-serif font-bold text-emerald-800">เยี่ยมมาก! ไม่มีรายการค้างส่ง</h3>
        <p class="text-emerald-600 mt-2">สมาชิกทุกคนคืนหนังสือตรงเวลา</p>
    </div>
<?php endif; ?>

<style>
    @media print {
        button, nav, .no-print { display: none !important; }
        body { background-color: white; }
        .shadow-sm, .shadow-md { box-shadow: none !important; }
        .border { border: 1px solid #eee !important; }
        table { width: 100% !important; border-collapse: collapse !important; }
        th, td { border-bottom: 1px solid #ddd !important; padding: 10px !important; }
        @page { margin: 2cm; }
    }
</style>

<?php require_once 'footer.php'; ?>