<?php
require_once 'header.php';

// Action: อนุมัติ / ปฏิเสธ
if (isset($_GET['action']) && isset($_GET['uid'])) {
    $uid = intval($_GET['uid']);
    $action = $_GET['action'];
    
    // Logic เดิม
    if ($action == 'approve') {
        $status = 'verified';
        $msg_title = "✅ ยืนยันตัวตนสำเร็จ (KYC Verified)";
        $msg_body = "เอกสารการยืนยันตัวตนของคุณได้รับการอนุมัติเรียบร้อยแล้ว\nคุณสามารถใช้บริการ เช่า/ซื้อ หนังสือได้ตามปกติ";
        $alert_text = "อนุมัติเรียบร้อย";
        $alert_icon = "success";
    } else {
        $status = 'rejected';
        $msg_title = "❌ การยืนยันตัวตนไม่ผ่าน";
        $msg_body = "เอกสารการยืนยันตัวตนของคุณ ไม่ผ่านการตรวจสอบ\nกรุณาตรวจสอบความถูกต้องของข้อมูลและอัปโหลดเอกสารใหม่อีกครั้ง";
        $alert_text = "ปฏิเสธคำขอเรียบร้อย";
        $alert_icon = "warning";
    }
    
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("UPDATE members SET verification_status = ? WHERE id = ?");
        $stmt->execute([$status, $uid]);
        $stmtMsg = $pdo->prepare("INSERT INTO messages (user_id, title, message, is_read, created_at) VALUES (?, ?, ?, 0, NOW())");
        $stmtMsg->execute([$uid, $msg_title, $msg_body]);
        $pdo->commit();

        echo "<script>
            Swal.fire({
                icon: '$alert_icon',
                title: 'ดำเนินการสำเร็จ',
                text: '$alert_text',
                timer: 1500,
                showConfirmButton: false,
                customClass: { popup: 'rounded-2xl' }
            }).then(() => {
                window.location='verifications.php';
            });
        </script>";

    } catch (PDOException $e) {
        $pdo->rollBack();
        echo "<script>alert('Error: " . $e->getMessage() . "'); window.location='verifications.php';</script>";
    }
    exit;
}

$sql = "SELECT v.*, m.fullname, m.email, m.phone 
        FROM verifications v 
        JOIN members m ON v.user_id = m.id 
        WHERE m.verification_status = 'pending' 
        ORDER BY v.submitted_at ASC";
$items = $pdo->query($sql)->fetchAll();
?>

<div class="mb-8 flex justify-between items-end border-b border-slate-200 pb-4">
    <div>
        <h1 class="text-3xl font-serif font-bold text-navy-900">🕵️ ตรวจสอบ KYC</h1>
        <p class="text-slate-500 mt-1">ตรวจสอบเอกสารยืนยันตัวตนของสมาชิกใหม่</p>
    </div>
    <div class="bg-white px-4 py-2 rounded-full border border-slate-200 shadow-sm text-sm font-bold text-slate-600">
        รอตรวจสอบ: <span class="text-gold-600 text-lg"><?php echo count($items); ?></span> รายการ
    </div>
</div>

<?php if(count($items) == 0): ?>
    <div class="bg-white p-16 rounded-2xl shadow-sm border border-slate-100 text-center text-slate-400">
        <div class="text-6xl mb-4 grayscale opacity-50">✨</div>
        <h3 class="text-xl font-serif font-bold text-navy-900 mb-2">ไม่มีรายการค้างตรวจสอบ</h3>
        <p>สมาชิกทุกคนได้รับการยืนยันครบถ้วนแล้ว</p>
    </div>
<?php else: ?>

<div class="space-y-8">
    <?php foreach($items as $row): ?>
    <div class="bg-white rounded-2xl shadow-md border border-slate-100 overflow-hidden relative">
        <div class="bg-navy-900 px-6 py-4 flex flex-col md:flex-row justify-between md:items-center gap-4">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-gold-500 text-navy-900 rounded-full flex items-center justify-center font-bold text-xl border-2 border-white/20">
                    <?php echo mb_substr($row['fullname'], 0, 1); ?>
                </div>
                <div>
                    <h3 class="font-bold text-lg text-white font-serif tracking-wide"><?php echo htmlspecialchars($row['fullname']); ?></h3>
                    <div class="text-xs text-slate-300 flex items-center gap-3 font-light">
                        <span>📞 <?php echo $row['phone']; ?></span>
                        <span class="w-1 h-1 bg-slate-500 rounded-full"></span>
                        <span>✉️ <?php echo $row['email']; ?></span>
                    </div>
                </div>
            </div>
            <div class="text-xs text-navy-200 font-mono bg-navy-800 px-3 py-1 rounded border border-navy-700">
                ส่งเมื่อ: <?php echo date('d/m/Y H:i', strtotime($row['submitted_at'])); ?>
            </div>
        </div>
        
        <div class="p-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="bg-slate-50 p-6 rounded-xl border border-slate-200 h-fit">
                    <h4 class="font-bold text-navy-900 mb-4 flex items-center gap-2 border-b border-slate-200 pb-2">
                        <span class="text-gold-500">📄</span> ข้อมูลบัตรประชาชน
                    </h4>
                    <div class="space-y-4 text-sm">
                        <div>
                            <span class="block text-xs text-slate-400 uppercase font-bold tracking-wider">เลขบัตรประชาชน</span>
                            <span class="font-mono font-bold text-lg text-slate-700 tracking-wider"><?php echo $row['id_card_number']; ?></span>
                        </div>
                        <div>
                            <span class="block text-xs text-slate-400 uppercase font-bold tracking-wider">Laser Code (หลังบัตร)</span>
                            <span class="font-mono font-bold text-slate-700"><?php echo $row['laser_code']; ?></span>
                        </div>
                        <div>
                            <span class="block text-xs text-slate-400 uppercase font-bold tracking-wider">วันเกิด</span>
                            <span class="font-bold text-slate-700"><?php echo date('d/m/Y', strtotime($row['dob'])); ?></span>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-2">
                    <h4 class="font-bold text-navy-900 mb-4 flex items-center gap-2 border-b border-slate-200 pb-2">
                        <span class="text-gold-500">🖼️</span> หลักฐานรูปภาพ
                    </h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6 items-end">
                        
                        <div class="text-center group w-full">
                            <p class="text-xs mb-2 font-bold text-slate-500">หน้าบัตร</p>
                            <div class="relative w-full aspect-[86/54] bg-slate-100 rounded-lg border shadow-sm overflow-hidden group-hover:shadow-md transition">
                                <img src="../uploads/kyc/<?php echo $row['id_card_front']; ?>" 
                                     class="w-full h-full object-cover transition duration-500 group-hover:scale-110 cursor-zoom-in" 
                                     onclick="viewImage(this.src)">
                            </div>
                        </div>

                        <div class="text-center group w-full">
                            <p class="text-xs mb-2 font-bold text-slate-500">หลังบัตร</p>
                            <div class="relative w-full aspect-[86/54] bg-slate-100 rounded-lg border shadow-sm overflow-hidden group-hover:shadow-md transition">
                                <img src="../uploads/kyc/<?php echo $row['id_card_back']; ?>" 
                                     class="w-full h-full object-cover transition duration-500 group-hover:scale-110 cursor-zoom-in" 
                                     onclick="viewImage(this.src)">
                            </div>
                        </div>

                        <div class="text-center group w-full flex flex-col items-center">
                            <p class="text-xs mb-2 font-bold text-slate-500">เซลฟี่คู่บัตร</p>
                            <div class="relative w-full max-w-[150px] aspect-[3/4] bg-slate-100 rounded-lg border shadow-sm overflow-hidden group-hover:shadow-md transition">
                                <img src="../uploads/kyc/<?php echo $row['selfie_image']; ?>" 
                                     class="w-full h-full object-cover transition duration-500 group-hover:scale-110 cursor-zoom-in" 
                                     onclick="viewImage(this.src)">
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-6 mt-6 border-t border-slate-100">
                <button onclick="confirmAction('reject', <?php echo $row['user_id']; ?>)" class="px-6 py-2.5 bg-white text-red-500 rounded-xl hover:bg-red-50 font-bold text-sm transition border border-red-100 shadow-sm">
                    ❌ ปฏิเสธคำขอ
                </button>
                <button onclick="confirmAction('approve', <?php echo $row['user_id']; ?>)" class="px-8 py-2.5 bg-navy-900 text-white rounded-xl hover:bg-gold-500 hover:text-navy-900 font-bold text-sm shadow-md transition transform active:scale-95 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    อนุมัติ (Verified)
                </button>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<script>
function viewImage(src) {
    Swal.fire({
        imageUrl: src,
        imageAlt: 'หลักฐาน',
        width: 'auto',
        showCloseButton: true,
        showConfirmButton: false,
        background: 'transparent',
        backdrop: 'rgba(15, 23, 42, 0.9)', // Navy backdrop
        customClass: {
            image: 'max-h-[85vh] rounded-lg shadow-2xl'
        }
    });
}

function confirmAction(action, uid) {
    const isApprove = action === 'approve';
    Swal.fire({
        title: isApprove ? '<span class="font-serif text-navy-900">ยืนยันอนุมัติ?</span>' : '<span class="font-serif text-red-600">ยืนยันปฏิเสธ?</span>',
        text: isApprove ? "สมาชิกจะสามารถเช่า/ซื้อหนังสือได้ทันที" : "ระบบจะแจ้งเตือนให้สมาชิกส่งเอกสารใหม่",
        icon: isApprove ? 'question' : 'warning',
        showCancelButton: true,
        confirmButtonColor: isApprove ? '#10b981' : '#ef4444',
        confirmButtonText: isApprove ? 'ยืนยันอนุมัติ' : 'ยืนยันปฏิเสธ',
        cancelButtonText: 'ยกเลิก',
        customClass: { popup: 'rounded-2xl' }
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `verifications.php?action=${action}&uid=${uid}`;
        }
    });
}
</script>
<?php endif; ?>

<?php require_once 'footer.php'; ?>