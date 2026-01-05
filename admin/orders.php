<?php
require_once 'header.php'; 

// ดึงข้อมูลออเดอร์ทั้งหมด
// เรียงลำดับ: รอตรวจสอบ -> ชำระแล้ว -> อื่นๆ
$sql = "SELECT o.*, m.fullname, m.email, a.recipient_name, a.phone, a.address_line
        FROM orders o 
        JOIN members m ON o.user_id = m.id 
        LEFT JOIN addresses a ON o.address_id = a.id 
        ORDER BY CASE WHEN o.status = 'pending' THEN 0 WHEN o.status = 'paid' THEN 1 ELSE 2 END, o.id DESC";
$orders = $pdo->query($sql)->fetchAll();
?>

<div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
    <div>
        <h1 class="text-3xl font-serif font-bold text-navy-900 flex items-center gap-2">
            📦 จัดการคำสั่งซื้อ (Orders)
        </h1>
        <p class="text-slate-500 text-sm mt-1">ตรวจสอบรายการสั่งซื้อ แจ้งชำระเงิน และอนุมัติสินค้า</p>
    </div>
    
    <div class="bg-white px-5 py-2.5 rounded-full shadow-sm border border-slate-200 text-sm flex items-center gap-3">
        <span class="text-slate-500 font-bold">ทั้งหมด</span> 
        <span class="w-px h-4 bg-slate-200"></span>
        <span class="font-bold text-gold-600 text-lg"><?php echo count($orders); ?></span> 
        <span class="text-slate-400">รายการ</span>
    </div>
</div>

<div class="bg-white rounded-2xl shadow-sm overflow-hidden border border-slate-100">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead class="bg-slate-50 border-b border-slate-100">
                <tr>
                    <th class="p-5 font-bold text-slate-500 text-xs uppercase tracking-wider w-20">ID</th>
                    <th class="p-5 font-bold text-slate-500 text-xs uppercase tracking-wider w-1/5">ลูกค้า</th>
                    <th class="p-5 font-bold text-slate-500 text-xs uppercase tracking-wider w-1/3">รายการสินค้า</th>
                    <th class="p-5 font-bold text-slate-500 text-xs uppercase tracking-wider text-right">ยอดเงิน</th>
                    <th class="p-5 font-bold text-slate-500 text-xs uppercase tracking-wider text-center">สถานะ / ขนส่ง</th>
                    <th class="p-5 font-bold text-slate-500 text-xs uppercase tracking-wider text-center">จัดการ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                <?php foreach($orders as $order): ?>
                <tr class="hover:bg-slate-50/50 transition <?php echo $order['status']=='pending' ? 'bg-gold-50/20' : ''; ?>">
                    <td class="p-5 align-top">
                        <div class="font-bold text-navy-900 bg-slate-100 px-2 py-1 rounded inline-block text-xs">#<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?></div>
                        <div class="text-[10px] text-slate-400 mt-2 flex flex-col">
                            <span><?php echo date('d/m/y', strtotime($order['created_at'])); ?></span>
                            <span><?php echo date('H:i', strtotime($order['created_at'])); ?> น.</span>
                        </div>
                    </td>

                    <td class="p-5 align-top">
                        <div class="font-bold text-navy-800 text-sm mb-1"><?php echo htmlspecialchars($order['recipient_name']); ?></div>
                        <div class="text-xs text-slate-500 leading-relaxed mb-2"><?php echo htmlspecialchars($order['address_line']); ?></div>
                        <div class="flex items-center gap-1.5 text-xs text-slate-400 bg-slate-50 px-2 py-1 rounded w-fit">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                            <?php echo htmlspecialchars($order['phone']); ?>
                        </div>
                    </td>
                    
                    <?php 
                        $stmtItems = $pdo->prepare("SELECT oi.*, b.title FROM order_items oi JOIN books b ON oi.book_id = b.id WHERE oi.order_id = ?");
                        $stmtItems->execute([$order['id']]);
                        $items = $stmtItems->fetchAll();
                    ?>

                    <td class="p-5 align-top">
                        <div class="flex flex-col gap-2">
                            <?php foreach($items as $item): ?>
                            <div class="flex items-start gap-2 text-sm text-slate-700">
                                <span class="text-slate-300">•</span>
                                <div>
                                    <span class="line-clamp-1" title="<?php echo htmlspecialchars($item['title']); ?>">
                                        <?php echo htmlspecialchars($item['title']); ?>
                                    </span>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        <span class="text-xs text-slate-400">x<?php echo $item['qty']; ?></span>
                                        <?php if($item['type'] == 'rent'): ?>
                                            <span class="text-[9px] font-bold text-blue-600 bg-blue-50 px-1.5 py-0.5 rounded border border-blue-100">เช่า</span>
                                        <?php else: ?>
                                            <span class="text-[9px] font-bold text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-100">ซื้อ</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </td>

                    <td class="p-5 text-right align-top">
                        <span class="font-bold text-navy-900 text-lg"><?php echo number_format($order['total_price'], 2); ?></span>
                        <span class="text-xs text-slate-400 block">บาท</span>
                    </td>

                    <td class="p-5 text-center align-top">
                        <?php 
                            $status_info = match($order['status']) {
                                'pending' => ['bg-gold-100 text-gold-800 border-gold-200', 'รอตรวจสอบ', 'animate-pulse'],
                                'paid' => ['bg-blue-100 text-blue-800 border-blue-200', 'ที่ต้องจัดส่ง', ''],
                                'shipped' => ['bg-purple-100 text-purple-800 border-purple-200', 'จัดส่งแล้ว', ''],
                                'completed' => ['bg-emerald-100 text-emerald-800 border-emerald-200', 'สำเร็จ', ''],
                                'cancelled' => ['bg-red-100 text-red-800 border-red-200', 'ยกเลิก', ''],
                                default => ['bg-slate-100 text-slate-800 border-slate-200', $order['status'], '']
                            };
                        ?>
                        <div class="flex flex-col items-center gap-3">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold border shadow-sm <?php echo $status_info[0]; ?>">
                                <?php if($order['status']=='pending'): ?><span class="w-1.5 h-1.5 rounded-full bg-gold-600 <?php echo $status_info[2]; ?>"></span><?php endif; ?>
                                <?php echo $status_info[1]; ?>
                            </span>

                            <?php if(($order['status'] == 'shipped' || $order['status'] == 'completed') && !empty($order['tracking_number'])): ?>
                                <div class="text-left bg-slate-50 p-2.5 rounded-lg border border-slate-200 w-full max-w-[160px] shadow-sm">
                                    <div class="text-[10px] text-slate-400 font-bold uppercase tracking-wider mb-0.5"><?php echo htmlspecialchars($order['carrier']); ?></div>
                                    <div class="text-xs font-mono text-navy-700 font-bold break-all select-all cursor-pointer hover:text-gold-600 transition flex items-center justify-between group" onclick="navigator.clipboard.writeText(this.innerText); Swal.fire({toast:true,position:'top',icon:'success',title:'คัดลอกแล้ว',showConfirmButton:false,timer:1000})">
                                        <?php echo htmlspecialchars($order['tracking_number']); ?>
                                        <svg class="w-3 h-3 opacity-0 group-hover:opacity-100 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if($order['status'] == 'cancelled'): ?>
                                <div class="text-[10px] text-red-500 bg-red-50 px-2 py-1 rounded max-w-[150px] leading-tight border border-red-100">
                                    เหตุผล: <?php echo htmlspecialchars($order['cancellation_reason']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </td>

                    <td class="p-5 text-center align-top">
                        <div class="space-y-2 flex flex-col items-center">
                        <?php if($order['status'] == 'pending'): ?>
                            
                            <?php if($order['slip_image']): ?>
                                <button onclick="viewSlip('../uploads/slips/<?php echo $order['slip_image']; ?>')" 
                                        class="w-full bg-white border border-blue-200 text-blue-600 px-3 py-2 rounded-lg hover:bg-blue-50 text-xs font-bold transition shadow-sm flex items-center justify-center gap-1">
                                    📄 ดูสลิป
                                </button>
                            <?php else: ?>
                                <span class="w-full block bg-slate-100 text-slate-400 px-3 py-2 rounded-lg text-xs border border-slate-200 cursor-not-allowed text-center">
                                    ไม่มีสลิป
                                </span>
                            <?php endif; ?>

                            <div class="flex gap-2 w-full">
                                <a href="order_update.php?id=<?php echo $order['id']; ?>&status=paid" 
                                   onclick="return confirm('ตรวจสอบยอดเงินเรียบร้อยแล้วใช่หรือไม่?')" 
                                   class="flex-1 bg-emerald-600 text-white px-3 py-2 rounded-lg hover:bg-emerald-700 text-xs font-bold transition shadow-sm text-center">
                                   อนุมัติ
                                </a>

                                <button onclick="cancelOrder(<?php echo $order['id']; ?>)" 
                                        class="flex-1 bg-white border border-red-200 text-red-500 px-3 py-2 rounded-lg hover:bg-red-50 text-xs font-bold transition text-center">
                                    ยกเลิก
                                </button>
                            </div>

                        <?php elseif($order['status'] == 'paid'): ?>
                            <button onclick="shipOrder(<?php echo $order['id']; ?>)" 
                               class="w-full bg-navy-900 text-white px-3 py-2 rounded-lg hover:bg-gold-500 hover:text-navy-900 text-xs font-bold shadow-md flex items-center justify-center gap-1 animate-pulse transition">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                จัดส่งสินค้า
                            </button>
                            <a href="../print_receipt.php?id=<?php echo $order['id']; ?>" target="_blank" class="w-full block bg-white border border-slate-200 text-slate-600 px-3 py-2 rounded-lg hover:bg-slate-50 text-xs font-bold transition text-center">
                                🖨️ พิมพ์ใบปะหน้า
                            </a>

                        <?php else: ?>
                             <a href="../print_receipt.php?id=<?php echo $order['id']; ?>" target="_blank" class="w-full block bg-white border border-slate-200 text-slate-600 px-3 py-2 rounded-lg hover:bg-slate-50 text-xs font-bold transition text-center">
                                🖨️ พิมพ์ใบเสร็จ
                            </a>
                        <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// ดูสลิป
function viewSlip(url) {
    Swal.fire({
        imageUrl: url,
        imageAlt: 'หลักฐานการโอนเงิน',
        confirmButtonText: 'ปิด',
        confirmButtonColor: '#0f172a', // Navy
        customClass: { popup: 'rounded-2xl', image: 'rounded-xl border shadow-sm' },
        width: '400px',
        background: '#fff'
    });
}

// ยกเลิกออเดอร์
function cancelOrder(id) {
    Swal.fire({
        title: 'ระบุเหตุผลยกเลิก',
        input: 'select',
        inputOptions: {
            'หลักฐานไม่ถูกต้อง': 'หลักฐานไม่ถูกต้อง',
            'ยอดเงินไม่ครบ': 'ยอดเงินไม่ครบ',
            'สินค้าหมด': 'สินค้าหมด',
            'other': 'อื่นๆ'
        },
        inputPlaceholder: 'เลือกเหตุผล...',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'ยืนยันการยกเลิก',
        cancelButtonText: 'ปิด',
        customClass: { popup: 'rounded-2xl', input: 'rounded-lg border-slate-300' }
    }).then((res) => {
        if(res.isConfirmed) {
            let reason = res.value;
            if(reason === 'other'){
                Swal.fire({
                    title: 'ระบุเหตุผลเพิ่มเติม',
                    input: 'text',
                    showCancelButton: true,
                    confirmButtonText: 'ยืนยัน',
                    confirmButtonColor: '#ef4444',
                    customClass: { popup: 'rounded-2xl', input: 'rounded-lg border-slate-300' }
                }).then((textRes) => {
                    if(textRes.isConfirmed) submitCancel(id, textRes.value);
                });
            } else {
                submitCancel(id, reason);
            }
        }
    });
}

function submitCancel(id, reason) {
    window.location.href = `order_update.php?id=${id}&status=cancelled&reason=${encodeURIComponent(reason)}`;
}

// ฟังก์ชันจัดส่งสินค้า (Tracking)
function shipOrder(id) {
    Swal.fire({
        title: '<span class="font-serif text-navy-900">🚚 ระบุข้อมูลการจัดส่ง</span>',
        html: `
            <div class="text-left space-y-4 pt-2">
                <div>
                    <label class="block text-sm font-bold text-navy-700 mb-1.5">บริษัทขนส่ง</label>
                    <select id="swal-carrier" class="w-full border border-slate-300 p-2.5 rounded-lg focus:ring-2 focus:ring-gold-500 outline-none transition bg-slate-50">
                        <option value="Flash Express">Flash Express</option>
                        <option value="Kerry Express">Kerry Express</option>
                        <option value="J&T Express">J&T Express</option>
                        <option value="Thai Post">ไปรษณีย์ไทย</option>
                        <option value="Shopee Xpress">Shopee Xpress</option>
                        <option value="Ninja Van">Ninja Van</option>
                        <option value="Other">อื่นๆ</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-bold text-navy-700 mb-1.5">เลขพัสดุ (Tracking No.)</label>
                    <input id="swal-tracking" type="text" class="w-full border border-slate-300 p-2.5 rounded-lg focus:ring-2 focus:ring-gold-500 outline-none transition bg-slate-50" placeholder="เช่น TH0123456789">
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'ยืนยันการจัดส่ง',
        confirmButtonColor: '#0f172a', // Navy
        cancelButtonText: 'ยกเลิก',
        customClass: { popup: 'rounded-2xl' },
        preConfirm: () => {
            const carrier = document.getElementById('swal-carrier').value;
            const tracking = document.getElementById('swal-tracking').value;
            if (!tracking) {
                Swal.showValidationMessage('กรุณากรอกเลขพัสดุ');
            }
            return { carrier, tracking };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const { carrier, tracking } = result.value;
            window.location.href = `order_update.php?id=${id}&status=shipped&carrier=${encodeURIComponent(carrier)}&tracking=${encodeURIComponent(tracking)}`;
        }
    });
}
</script>

<?php require_once 'footer.php'; ?>