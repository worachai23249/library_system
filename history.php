<?php
require_once 'config/db.php';
require_once 'includes/header.php';

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

// ดึงข้อมูลออเดอร์ (เรียงล่าสุดขึ้นก่อน)
$sql = "SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll();
?>

<div class="bg-slate-50 min-h-screen py-10 font-sans">
    <div class="container mx-auto px-4 max-w-5xl">
        
        <div class="flex justify-between items-center mb-10">
            <h1 class="text-3xl font-serif font-bold text-slate-800 flex items-center gap-3">
                <span class="bg-white p-2 rounded-lg shadow-sm border border-slate-100 text-gold-500">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </span>
                ประวัติการสั่งซื้อ
            </h1>
            <button onclick="showReturnInfo()" class="bg-slate-900 text-white px-6 py-2.5 rounded-full shadow-lg hover:bg-gold-500 hover:text-slate-900 transition font-bold text-sm flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                วิธีคืนหนังสือ
            </button>
        </div>

        <?php if (count($orders) == 0): ?>
            <div class="bg-white p-20 rounded-3xl shadow-sm text-center border border-slate-100">
                <div class="w-24 h-24 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-6 text-slate-300">
                    <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                </div>
                <p class="text-slate-500 text-xl font-serif mb-2">ยังไม่มีประวัติการสั่งซื้อ</p>
                <p class="text-slate-400 text-sm mb-8">เลือกหนังสือเล่มโปรดของคุณแล้วเริ่มอ่านได้เลย</p>
                <a href="index.php" class="inline-block bg-slate-900 text-white px-8 py-3 rounded-full font-bold hover:shadow-lg transition transform hover:-translate-y-1">เลือกดูหนังสือ</a>
            </div>
        <?php else: ?>
            <div class="space-y-8">
                <?php foreach ($orders as $order): ?>
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden hover:shadow-md transition duration-300">
                        
                        <div class="bg-slate-50 px-8 py-5 border-b border-slate-100 flex flex-wrap justify-between items-center gap-4">
                            <div class="flex items-center gap-4">
                                <div class="p-3 bg-white border border-slate-200 rounded-xl shadow-sm">
                                    <svg class="w-6 h-6 text-gold-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                                </div>
                                <div>
                                    <p class="text-[10px] text-slate-400 uppercase tracking-widest font-bold">Order ID</p>
                                    <p class="font-bold text-slate-800 text-xl font-mono">#<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?></p>
                                </div>
                            </div>

                            <div class="flex flex-wrap gap-8 items-center">
                                <div>
                                    <p class="text-[10px] text-slate-400 uppercase font-bold">วันที่สั่งซื้อ</p>
                                    <p class="font-medium text-slate-600 text-sm"><?php echo date('d M Y, H:i', strtotime($order['created_at'])); ?></p>
                                </div>
                                
                                <div class="flex flex-col items-end">
                                    <div class="flex items-center gap-2 mb-1">
                                        <p class="text-[10px] text-slate-400 uppercase font-bold">สถานะ</p>
                                        <?php 
                                            $status = $order['status'];
                                            $badgeClass = match($status) {
                                                'pending' => 'bg-yellow-100 text-yellow-700 border-yellow-200',
                                                'paid' => 'bg-blue-100 text-blue-700 border-blue-200',
                                                'shipped' => 'bg-purple-100 text-purple-700 border-purple-200',
                                                'completed' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                                                'cancelled' => 'bg-red-100 text-red-700 border-red-200',
                                                default => 'bg-slate-100 text-slate-700'
                                            };
                                            $statusText = match($status) {
                                                'pending' => 'รอตรวจสอบ',
                                                'paid' => 'ชำระแล้ว',
                                                'shipped' => 'จัดส่งแล้ว',
                                                'completed' => 'เสร็จสิ้น',
                                                'cancelled' => 'ยกเลิก',
                                                default => $status
                                            };
                                        ?>
                                        <span class="inline-flex items-center px-3 py-0.5 rounded-full text-xs font-bold border <?php echo $badgeClass; ?>">
                                            <span class="w-1.5 h-1.5 rounded-full bg-current mr-2 opacity-60"></span>
                                            <?php echo $statusText; ?>
                                        </span>
                                    </div>

                                    <?php if(in_array($status, ['shipped', 'completed']) && !empty($order['tracking_number'])): ?>
                                        <div class="text-xs text-slate-500 bg-white border border-slate-200 px-2 py-1 rounded flex items-center gap-1">
                                            <svg class="w-3 h-3 text-gold-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                            <span class="font-bold"><?php echo htmlspecialchars($order['carrier']); ?>:</span>
                                            <span class="font-mono select-all"><?php echo htmlspecialchars($order['tracking_number']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="pl-6 border-l border-slate-200">
                                    <p class="text-[10px] text-slate-400 uppercase font-bold text-right">ยอดสุทธิ</p>
                                    <p class="font-bold text-slate-900 text-xl"><?php echo number_format($order['total_price']); ?> ฿</p>
                                </div>
                            </div>
                        </div>

                        <div class="p-8">
                            <?php 
                                $stmt_items = $pdo->prepare("SELECT oi.*, b.title, b.cover_image FROM order_items oi JOIN books b ON oi.book_id = b.id WHERE oi.order_id = ?");
                                $stmt_items->execute([$order['id']]);
                                $items = $stmt_items->fetchAll();
                            ?>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <?php foreach ($items as $item): ?>
                                    <div class="flex gap-4 items-center p-3 rounded-xl border border-slate-50 hover:bg-slate-50 transition">
                                        <div class="w-14 h-20 bg-slate-200 rounded-lg flex-shrink-0 overflow-hidden shadow-sm">
                                            <?php if($item['cover_image']): ?>
                                                <img src="uploads/covers/<?php echo $item['cover_image']; ?>" class="w-full h-full object-cover">
                                            <?php endif; ?>
                                        </div>
                                        <div class="flex-grow min-w-0">
                                            <p class="font-bold text-slate-800 text-sm truncate"><?php echo htmlspecialchars($item['title']); ?></p>
                                            <div class="flex items-center gap-2 mt-1">
                                                <span class="text-[10px] px-2 py-0.5 rounded font-bold uppercase tracking-wide <?php echo $item['type'] == 'buy' ? 'bg-emerald-100 text-emerald-700' : 'bg-blue-100 text-blue-700'; ?>">
                                                    <?php echo $item['type'] == 'buy' ? 'BUY' : 'RENT'; ?>
                                                </span>
                                                <span class="text-xs text-slate-400">x <?php echo $item['qty']; ?></span>
                                            </div>
                                        </div>
                                        </div>
                                <?php endforeach; ?>
                            </div>

                            <?php if(in_array($status, ['paid', 'shipped', 'completed'])): ?>
                            <div class="mt-6 pt-6 border-t border-slate-100 flex justify-end">
                                <a href="print_receipt.php?id=<?php echo $order['id']; ?>" target="_blank" 
                                   class="inline-flex items-center gap-2 text-sm font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 px-4 py-2 rounded-lg transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                                    พิมพ์ใบเสร็จ
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// ฟังก์ชันแสดงวิธีคืนหนังสือ
function showReturnInfo() {
    Swal.fire({
        title: '📚 วิธีการคืนหนังสือ',
        html: `
            <div class="text-left text-sm space-y-4 font-sans">
                <div class="bg-emerald-50 p-4 rounded-xl border border-emerald-100">
                    <h3 class="font-bold text-emerald-800 flex items-center gap-2">
                        <span class="bg-emerald-200 w-6 h-6 rounded-full flex items-center justify-center text-xs">1</span>
                        คืนด้วยตนเอง (Walk-in)
                    </h3>
                    <p class="text-slate-600 ml-8 mt-2 text-xs">นำหนังสือมาคืนที่เคาน์เตอร์ห้องสมุด สาขาแม่กรณ์ เชียงราย</p>
                </div>
                <div class="bg-blue-50 p-4 rounded-xl border border-blue-100">
                    <h3 class="font-bold text-blue-800 flex items-center gap-2">
                        <span class="bg-blue-200 w-6 h-6 rounded-full flex items-center justify-center text-xs">2</span>
                        ส่งไปรษณีย์ (Delivery)
                    </h3>
                    <div class="ml-8 mt-2 text-slate-600 text-xs">
                        <p>ส่งมาที่: ห้องสมุด Library System 28/2 ต.แม่กรณ์ อ.เมือง จ.เชียงราย 57000</p>
                        <p class="mt-1 text-red-500 font-bold">*แนบชื่อผู้ยืมในกล่อง</p>
                    </div>
                </div>
            </div>
        `,
        confirmButtonText: 'เข้าใจแล้ว',
        confirmButtonColor: '#0f172a',
        customClass: { popup: 'rounded-2xl' }
    });
}

// แจ้งเตือนเมื่อชำระเงินสำเร็จ
<?php if (isset($_GET['status']) && $_GET['status'] == 'success'): ?>
document.addEventListener('DOMContentLoaded', function() {
    Swal.fire({
        title: '✅ ชำระเงินสำเร็จ',
        html: '<div class="text-slate-600">ขอบคุณที่ใช้บริการ คำสั่งซื้อของคุณได้รับการบันทึกแล้ว</div>',
        icon: 'success', 
        confirmButtonText: 'ตกลง',
        confirmButtonColor: '#0f172a'
    }).then(() => {
        window.history.replaceState(null, null, window.location.pathname);
    });
});
<?php endif; ?>
</script>

<?php require_once 'includes/footer.php'; ?>