<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
$user_id = $_SESSION['user_id'];

// --- ส่วนจัดการบันทึกข้อมูล (PHP Logic) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $id_card_number = $_POST['id_card_number'];
        $laser_code = $_POST['laser_code'];
        
        // ตัวแปรเก็บชื่อไฟล์ (ถ้าไม่อัปโหลดใหม่ ให้เป็น null ไปก่อน)
        $file_front = null;
        $file_back = null;
        $file_selfie = null;

        // ฟังก์ชันช่วยอัปโหลดไฟล์
        function uploadKYC($file, $prefix, $uid) {
            if (!empty($file['name'])) {
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png'];
                if (in_array($ext, $allowed)) {
                    $new_name = "kyc_{$prefix}_{$uid}_" . time() . ".{$ext}";
                    move_uploaded_file($file['tmp_name'], "uploads/kyc/" . $new_name);
                    return $new_name;
                }
            }
            return null;
        }

        // สร้างโฟลเดอร์ถ้ายังไม่มี
        if (!file_exists('uploads/kyc')) { mkdir('uploads/kyc', 0777, true); }

        $file_front = uploadKYC($_FILES['id_card_front'], 'front', $user_id);
        $file_back = uploadKYC($_FILES['id_card_back'], 'back', $user_id);
        $file_selfie = uploadKYC($_FILES['selfie_with_card'], 'selfie', $user_id);

        // ถ้าอัปโหลดครบทั้ง 3 รูป (หรือจะปรับเงื่อนไขตามต้องการ)
        if ($file_front && $file_back && $file_selfie) {
            // อัปเดตข้อมูลลง Database
            $sql = "UPDATE members SET 
                    id_card_number = ?, 
                    laser_code = ?, 
                    id_card_front = ?, 
                    id_card_back = ?, 
                    selfie_with_card = ?, 
                    verification_status = 'pending',
                    reject_reason = NULL 
                    WHERE id = ?";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id_card_number, $laser_code, $file_front, $file_back, $file_selfie, $user_id]);

            // Refresh เพื่อแสดงสถานะใหม่
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'success',
                        title: 'ส่งข้อมูลเรียบร้อย',
                        text: 'เจ้าหน้าที่จะทำการตรวจสอบภายใน 24 ชม.',
                        confirmButtonColor: '#0f172a'
                    }).then(() => { window.location.href = 'verify.php'; });
                });
            </script>";
        } else {
            echo "<script>alert('กรุณาอัปโหลดรูปภาพให้ครบทั้ง 3 ส่วน');</script>";
        }

    } catch (Exception $e) {
        echo "<script>alert('Error: " . $e->getMessage() . "');</script>";
    }
}

// ดึงข้อมูลผู้ใช้ปัจจุบัน
$stmt = $pdo->prepare("SELECT * FROM members WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
$status = $user['verification_status'];

require_once 'includes/header.php';
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="bg-slate-50 min-h-screen py-10 font-sans">
    <div class="container mx-auto px-4 max-w-5xl">
        <div class="flex flex-col md:flex-row gap-8">
            
            <aside class="w-full md:w-72 flex-shrink-0">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 sticky top-24">
                    <nav class="space-y-1">
                        <a href="profile.php" class="flex items-center gap-3 px-4 py-3 text-slate-600 hover:bg-slate-50 hover:text-gold-600 rounded-xl transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                            <span class="text-sm font-medium">ข้อมูลส่วนตัว</span>
                        </a>
                        <a href="profile_addresses.php" class="flex items-center gap-3 px-4 py-3 text-slate-600 hover:bg-slate-50 hover:text-gold-600 rounded-xl transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            <span class="text-sm font-medium">ที่อยู่จัดส่ง</span>
                        </a>
                        <a href="change_password.php" class="flex items-center gap-3 px-4 py-3 text-slate-600 hover:bg-slate-50 hover:text-gold-600 rounded-xl transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                            <span class="text-sm font-medium">เปลี่ยนรหัสผ่าน</span>
                        </a>
                        <a href="verify.php" class="flex items-center gap-3 px-4 py-3 bg-slate-900 text-white rounded-xl shadow-md transition">
                            <svg class="w-5 h-5 text-gold-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <span class="text-sm font-medium">ยืนยันตัวตน (KYC)</span>
                        </a>
                        <a href="messages.php" class="flex items-center gap-3 px-4 py-3 text-slate-600 hover:bg-slate-50 hover:text-gold-600 rounded-xl transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                            <span class="text-sm font-medium">การแจ้งเตือน</span>
                        </a>
                    </nav>
                </div>
            </aside>

            <main class="flex-grow">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-8">
                    <h1 class="text-2xl font-serif font-bold text-slate-800 mb-2 flex items-center gap-3">
                        <span class="bg-gold-100 text-gold-600 p-2 rounded-lg"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg></span>
                        ยืนยันตัวตน (KYC)
                    </h1>
                    <p class="text-slate-500 mb-8 text-sm">กรุณากรอกข้อมูลและอัปโหลดเอกสารเพื่อยืนยันตัวตนก่อนเริ่มใช้งาน</p>

                    <?php if($status == 'verified'): ?>
                        <div class="bg-emerald-50 border border-emerald-100 rounded-xl p-8 text-center animate-fade-in">
                            <div class="w-16 h-16 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            </div>
                            <h3 class="text-xl font-bold text-emerald-800 mb-2">ยืนยันตัวตนสำเร็จ</h3>
                            <p class="text-emerald-600">บัญชีของคุณได้รับการอนุมัติแล้ว สามารถใช้บริการได้ตามปกติ</p>
                        </div>
                    <?php elseif($status == 'pending'): ?>
                        <div class="bg-yellow-50 border border-yellow-100 rounded-xl p-8 text-center animate-pulse">
                            <div class="w-16 h-16 bg-yellow-100 text-yellow-600 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                            <h3 class="text-xl font-bold text-yellow-800 mb-2">กำลังตรวจสอบ</h3>
                            <p class="text-yellow-600">เจ้าหน้าที่กำลังตรวจสอบเอกสารของคุณ กรุณารอ 1-2 วันทำการ</p>
                        </div>
                    <?php else: ?>
                        <?php if($status == 'rejected'): ?>
                            <div class="bg-red-50 border border-red-100 rounded-xl p-4 mb-6 text-sm text-red-700 flex items-start gap-3">
                                <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <div>
                                    <strong>⚠️ เอกสารไม่ผ่าน:</strong> <?php echo htmlspecialchars($user['reject_reason']); ?>
                                    <br>กรุณาตรวจสอบและส่งข้อมูลใหม่อีกครั้ง
                                </div>
                            </div>
                        <?php endif; ?>

                        <form method="POST" enctype="multipart/form-data" class="space-y-8">
                            
                            <div class="bg-slate-50 p-6 rounded-xl border border-slate-100">
                                <h3 class="font-bold text-slate-800 mb-4 flex items-center gap-2">
                                    <span class="w-6 h-6 bg-slate-200 rounded-full flex items-center justify-center text-xs text-slate-600">1</span> 
                                    ข้อมูลบัตรประชาชน
                                </h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500 uppercase mb-2">เลขบัตรประชาชน (13 หลัก)</label>
                                        <input type="text" name="id_card_number" required maxlength="13" placeholder="x-xxxx-xxxxx-xx-x" 
                                               class="w-full bg-white border border-slate-200 rounded-lg px-4 py-3 text-sm focus:outline-none focus:border-gold-500 focus:ring-1 focus:ring-gold-500 transition">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500 uppercase mb-2 flex justify-between">
                                            เลขหลังบัตร (Laser Code)
                                            <span class="text-[10px] text-gold-600 cursor-help" title="อยู่ด้านหลังบัตร ขึ้นต้นด้วยตัวอักษร 2 ตัว">? คำแนะนำ</span>
                                        </label>
                                        <input type="text" name="laser_code" required maxlength="12" placeholder="JT9-9999999-99" 
                                               class="w-full bg-white border border-slate-200 rounded-lg px-4 py-3 text-sm focus:outline-none focus:border-gold-500 focus:ring-1 focus:ring-gold-500 transition">
                                    </div>
                                </div>
                            </div>

                            <div>
                                <h3 class="font-bold text-slate-800 mb-4 flex items-center gap-2">
                                    <span class="w-6 h-6 bg-slate-200 rounded-full flex items-center justify-center text-xs text-slate-600">2</span> 
                                    อัปโหลดรูปภาพยืนยัน
                                </h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm text-slate-600 mb-2">รูปถ่ายบัตรประชาชน (ด้านหน้า)</label>
                                        <div class="relative group cursor-pointer border-2 border-dashed border-slate-300 rounded-xl p-4 hover:border-gold-500 transition bg-slate-50 h-48 flex items-center justify-center text-slate-400">
                                            <div class="text-center pointer-events-none">
                                                <svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                                <span class="text-xs">คลิกเพื่ออัปโหลด</span>
                                            </div>
                                            <input type="file" name="id_card_front" required accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" onchange="previewFile(this)">
                                            <img class="absolute inset-0 w-full h-full object-cover rounded-xl hidden preview-img pointer-events-none">
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-sm text-slate-600 mb-2">รูปถ่ายบัตรประชาชน (ด้านหลัง)</label>
                                        <div class="relative group cursor-pointer border-2 border-dashed border-slate-300 rounded-xl p-4 hover:border-gold-500 transition bg-slate-50 h-48 flex items-center justify-center text-slate-400">
                                            <div class="text-center pointer-events-none">
                                                <svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                                <span class="text-xs">คลิกเพื่ออัปโหลด</span>
                                            </div>
                                            <input type="file" name="id_card_back" required accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" onchange="previewFile(this)">
                                            <img class="absolute inset-0 w-full h-full object-cover rounded-xl hidden preview-img pointer-events-none">
                                        </div>
                                    </div>

                                    <div class="md:col-span-2">
                                        <label class="block text-sm text-slate-600 mb-2">รูปถ่ายคู่กับบัตร (Selfie)</label>
                                        <div class="relative group cursor-pointer border-2 border-dashed border-slate-300 rounded-xl p-4 hover:border-gold-500 transition bg-slate-50 h-64 flex items-center justify-center text-slate-400">
                                            <div class="text-center pointer-events-none">
                                                <svg class="w-10 h-10 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                <span class="text-sm">คลิกเพื่ออัปโหลด</span>
                                            </div>
                                            <input type="file" name="selfie_with_card" required accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" onchange="previewFile(this)">
                                            <img class="absolute inset-0 w-full h-full object-contain rounded-xl hidden preview-img pointer-events-none">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="pt-4 border-t border-slate-100 flex justify-end">
                                <button type="submit" class="bg-slate-900 text-white px-10 py-3.5 rounded-xl font-bold hover:bg-gold-500 hover:text-slate-900 transition shadow-lg transform active:scale-95 flex items-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    ส่งข้อมูลยืนยันตัวตน
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>
</div>

<script>
function previewFile(input) {
    const parent = input.parentElement;
    const img = parent.querySelector('.preview-img');
    const placeholder = parent.querySelector('div');
    
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            img.src = e.target.result;
            img.classList.remove('hidden');
            placeholder.classList.add('opacity-0');
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>