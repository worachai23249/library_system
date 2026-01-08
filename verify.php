<?php
session_start();
require_once 'config/db.php';
require_once 'includes/header.php'; // เรียก Header หน้าบ้าน

// ตรวจสอบสถานะปัจจุบัน
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT verification_status FROM members WHERE id = ?");
$stmt->execute([$user_id]);
$status = $stmt->fetchColumn();
?>

<div class="container mx-auto px-4 py-12 max-w-3xl">
    
    <div class="text-center mb-10">
        <h1 class="text-3xl font-serif font-bold text-navy-900">ยืนยันตัวตน (KYC)</h1>
        <p class="text-slate-500 mt-2">กรุณากรอกข้อมูลและแนบเอกสารเพื่อยืนยันตัวตน เพื่อสิทธิในการเช่าหนังสือ</p>
    </div>

    <?php if ($status == 'pending'): ?>
        <div class="bg-white p-10 rounded-2xl shadow-sm border border-gold-200 text-center">
            <div class="w-20 h-20 bg-gold-100 text-gold-600 rounded-full flex items-center justify-center mx-auto mb-6 animate-pulse">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <h2 class="text-2xl font-serif font-bold text-navy-900 mb-2">อยู่ระหว่างการตรวจสอบ</h2>
            <p class="text-slate-600">
                ระบบได้รับข้อมูลของท่านเรียบร้อยแล้ว<br>
                เจ้าหน้าที่จะทำการตรวจสอบเอกสารภายใน 1-2 วันทำการ
            </p>
            <a href="index.php" class="inline-block mt-8 text-gold-600 font-bold hover:underline">กลับสู่หน้าหลัก</a>
        </div>

    <?php elseif ($status == 'verified'): ?>
        <div class="bg-white p-10 rounded-2xl shadow-sm border border-green-200 text-center">
             <div class="w-20 h-20 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            </div>
            <h2 class="text-2xl font-serif font-bold text-navy-900 mb-2">ยืนยันตัวตนสำเร็จ</h2>
            <p class="text-slate-600">บัญชีของคุณได้รับการยืนยันเรียบร้อยแล้ว สามารถใช้บริการเช่าหนังสือได้ทันที</p>
            <a href="index.php" class="inline-block mt-8 bg-navy-900 text-white px-6 py-2.5 rounded-full font-bold hover:bg-gold-500 hover:text-navy-900 transition">เลือกชมหนังสือเลย</a>
        </div>

    <?php else: ?>
        
        <?php if ($status == 'rejected'): ?>
            <div class="bg-red-50 border border-red-100 text-red-700 px-6 py-4 rounded-xl mb-6 flex items-center gap-3">
                <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <div>
                    <span class="font-bold">การยืนยันตัวตนไม่ผ่าน:</span> กรุณาตรวจสอบความถูกต้องของเอกสารและลองใหม่อีกครั้ง
                </div>
            </div>
        <?php endif; ?>

        <form action="verify_save.php" method="post" enctype="multipart/form-data" class="bg-white p-8 rounded-2xl shadow-lg border border-slate-100 space-y-6">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-navy-900 font-bold mb-2">เลขบัตรประชาชน <span class="text-red-500">*</span></label>
                    <input type="text" name="id_card_number" required maxlength="13" class="w-full px-4 py-3 rounded-lg border border-slate-200 focus:border-gold-500 focus:ring-1 focus:ring-gold-500 outline-none transition bg-slate-50" placeholder="1234567890123">
                </div>
                <div>
                    <label class="block text-navy-900 font-bold mb-2">Laser Code (หลังบัตร) <span class="text-red-500">*</span></label>
                    <input type="text" name="laser_code" required class="w-full px-4 py-3 rounded-lg border border-slate-200 focus:border-gold-500 focus:ring-1 focus:ring-gold-500 outline-none transition bg-slate-50" placeholder="ME0-xxxxxxxxx">
                </div>
            </div>

            <div>
                <label class="block text-navy-900 font-bold mb-2">วันเกิด (ตามบัตรประชาชน) <span class="text-red-500">*</span></label>
                <input type="date" name="dob" required class="w-full px-4 py-3 rounded-lg border border-slate-200 focus:border-gold-500 focus:ring-1 focus:ring-gold-500 outline-none transition bg-slate-50">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 pt-4">
                <div class="text-center">
                    <label class="block text-sm font-bold text-slate-600 mb-2">รูปถ่ายหน้าบัตร</label>
                    <div class="relative w-full aspect-[4/3] bg-slate-100 border-2 border-dashed border-slate-300 rounded-lg flex items-center justify-center cursor-pointer hover:border-gold-500 transition group overflow-hidden">
                        <input type="file" name="id_card_front" required class="absolute inset-0 opacity-0 cursor-pointer z-10" onchange="preview(this, 'p1')">
                        <img id="p1" class="hidden w-full h-full object-cover">
                        <div class="text-slate-400 group-hover:text-gold-500 transition" id="txt1">
                            <span class="text-2xl">+</span><br>อัปโหลด
                        </div>
                    </div>
                </div>
                <div class="text-center">
                    <label class="block text-sm font-bold text-slate-600 mb-2">รูปถ่ายหลังบัตร</label>
                    <div class="relative w-full aspect-[4/3] bg-slate-100 border-2 border-dashed border-slate-300 rounded-lg flex items-center justify-center cursor-pointer hover:border-gold-500 transition group overflow-hidden">
                        <input type="file" name="id_card_back" required class="absolute inset-0 opacity-0 cursor-pointer z-10" onchange="preview(this, 'p2')">
                        <img id="p2" class="hidden w-full h-full object-cover">
                        <div class="text-slate-400 group-hover:text-gold-500 transition" id="txt2">
                            <span class="text-2xl">+</span><br>อัปโหลด
                        </div>
                    </div>
                </div>
                <div class="text-center">
                    <label class="block text-sm font-bold text-slate-600 mb-2">เซลฟี่คู่กับบัตร</label>
                    <div class="relative w-full aspect-[4/3] bg-slate-100 border-2 border-dashed border-slate-300 rounded-lg flex items-center justify-center cursor-pointer hover:border-gold-500 transition group overflow-hidden">
                        <input type="file" name="selfie_image" required class="absolute inset-0 opacity-0 cursor-pointer z-10" onchange="preview(this, 'p3')">
                        <img id="p3" class="hidden w-full h-full object-cover">
                        <div class="text-slate-400 group-hover:text-gold-500 transition" id="txt3">
                            <span class="text-2xl">+</span><br>อัปโหลด
                        </div>
                    </div>
                </div>
            </div>

            <div class="pt-6">
                <button type="submit" class="w-full bg-navy-900 text-white py-4 rounded-xl font-bold text-lg hover:bg-gold-500 hover:text-navy-900 shadow-lg transition transform active:scale-95">
                    ส่งข้อมูลยืนยันตัวตน
                </button>
            </div>
        </form>
    <?php endif; ?>
</div>

<script>
function preview(input, imgId) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById(imgId).src = e.target.result;
            document.getElementById(imgId).classList.remove('hidden');
            document.getElementById('txt'+imgId.substr(1)).classList.add('hidden');
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php if(isset($_GET['status']) && $_GET['status'] == 'success'): ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
            title: '<h3 class="font-serif text-navy-900">ส่งคำขอสำเร็จ!</h3>',
            html: '<p class="text-slate-600 text-base">ระบบได้รับข้อมูลยืนยันตัวตนของคุณแล้ว<br>กรุณารอการตรวจสอบจากเจ้าหน้าที่ (1-2 วันทำการ)</p>',
            icon: 'success',
            confirmButtonText: 'รับทราบ',
            confirmButtonColor: '#0f172a', // Navy color
            background: '#fff',
            backdrop: `rgba(15, 23, 42, 0.6)`,
            customClass: {
                popup: 'rounded-2xl shadow-xl',
                confirmButton: 'px-6 py-2.5 rounded-lg font-bold'
            },
            allowOutsideClick: false
        }).then((result) => {
            if (result.isConfirmed) {
                // ลบ Query String ออก เพื่อไม่ให้ Popup เด้งซ้ำเมื่อ Refresh
                window.history.replaceState(null, null, window.location.pathname);
                // หรือจะ Redirect ไปหน้า Profile ก็ได้
                // window.location.href = 'profile.php'; 
            }
        });
    });
</script>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>