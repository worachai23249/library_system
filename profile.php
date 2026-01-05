<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$user_id = $_SESSION['user_id'];
$success_msg = "";
$error_msg = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fullname = $_POST['fullname'];
    $phone = $_POST['phone'];
    
    $sql = "UPDATE members SET fullname = ?, phone = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    
    if ($stmt->execute([$fullname, $phone, $user_id])) {
        $_SESSION['fullname'] = $fullname;
        $success_msg = "บันทึกข้อมูลเรียบร้อยแล้ว";
    } else {
        $error_msg = "เกิดข้อผิดพลาดในการบันทึกข้อมูล";
    }

    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $ext = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $new_filename = "profile_" . $user_id . "_" . time() . "." . $ext;
            $upload_path = "uploads/profiles/" . $new_filename;

            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
                $sql_img = "UPDATE members SET profile_image = ? WHERE id = ?";
                $pdo->prepare($sql_img)->execute([$new_filename, $user_id]);
                $_SESSION['profile_image'] = $new_filename;
                $success_msg = "อัปเดตข้อมูลและรูปโปรไฟล์เรียบร้อยแล้ว";
            } else {
                $error_msg = "เกิดข้อผิดพลาดในการอัปโหลดรูปภาพ";
            }
        } else {
            $error_msg = "รองรับเฉพาะไฟล์รูปภาพ (JPG, PNG, GIF)";
        }
    }
}

$stmt = $pdo->prepare("SELECT * FROM members WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

require_once 'includes/header.php'; 
?>

<div class="bg-slate-50 min-h-screen py-10 font-sans">
    <div class="container mx-auto px-4 max-w-5xl">
        
        <div class="flex flex-col md:flex-row gap-8">
            
            <aside class="w-full md:w-72 flex-shrink-0">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 sticky top-24">
                    <div class="text-center mb-6">
                        <div class="relative w-24 h-24 mx-auto mb-3">
                            <?php if(!empty($user['profile_image'])): ?>
                                <img src="uploads/profiles/<?php echo $user['profile_image']; ?>" class="w-full h-full object-cover rounded-full border-4 border-slate-50 shadow-md">
                            <?php else: ?>
                                <div class="w-full h-full bg-slate-100 rounded-full flex items-center justify-center text-slate-300 border-4 border-slate-50">
                                    <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                </div>
                            <?php endif; ?>
                            <div class="absolute bottom-0 right-0 w-6 h-6 bg-green-500 border-2 border-white rounded-full"></div>
                        </div>
                        <h3 class="font-serif font-bold text-lg text-slate-800"><?php echo htmlspecialchars($user['fullname']); ?></h3>
                        <p class="text-xs text-slate-500"><?php echo htmlspecialchars($user['email']); ?></p>
                    </div>

                    <nav class="space-y-1">
                        <a href="profile.php" class="flex items-center gap-3 px-4 py-3 bg-slate-900 text-white rounded-xl shadow-md transition">
                            <svg class="w-5 h-5 text-gold-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
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
                        <a href="verify.php" class="flex items-center gap-3 px-4 py-3 text-slate-600 hover:bg-slate-50 hover:text-gold-600 rounded-xl transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
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
                    <h1 class="text-2xl font-serif font-bold text-slate-800 mb-6 pb-4 border-b border-slate-100 flex items-center gap-3">
                        <span class="bg-gold-100 text-gold-600 p-2 rounded-lg"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg></span>
                        แก้ไขข้อมูลส่วนตัว
                    </h1>

                    <?php if($success_msg): ?>
                        <div class="mb-6 p-4 bg-emerald-50 border border-emerald-100 text-emerald-700 rounded-xl flex items-center gap-3">
                            <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                            <?php echo $success_msg; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if($error_msg): ?>
                        <div class="mb-6 p-4 bg-red-50 border border-red-100 text-red-700 rounded-xl flex items-center gap-3">
                            <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                            <?php echo $error_msg; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data" class="space-y-6">
                        
                        <div class="flex items-start gap-6">
                            <div class="flex-shrink-0 relative group cursor-pointer">
                                <div class="w-24 h-24 rounded-2xl overflow-hidden bg-slate-100 border-2 border-dashed border-slate-300 group-hover:border-gold-500 transition">
                                    <?php if(!empty($user['profile_image'])): ?>
                                        <img id="preview-img" src="uploads/profiles/<?php echo $user['profile_image']; ?>" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <img id="preview-img" class="w-full h-full object-cover hidden">
                                        <div id="upload-placeholder" class="w-full h-full flex flex-col items-center justify-center text-slate-400">
                                            <svg class="w-8 h-8 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                            <span class="text-[10px]">Upload</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <input type="file" name="profile_image" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" onchange="previewImage(this)">
                            </div>
                            <div class="pt-2">
                                <h4 class="font-bold text-slate-700">รูปโปรไฟล์</h4>
                                <p class="text-xs text-slate-500 mt-1">คลิกที่รูปเพื่ออัปโหลดใหม่ (รองรับ JPG, PNG)</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">ชื่อ - นามสกุล</label>
                                <input type="text" name="fullname" value="<?php echo htmlspecialchars($user['fullname']); ?>" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-gold-500 focus:ring-2 focus:ring-gold-500/20 outline-none transition" required>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">เบอร์โทรศัพท์</label>
                                <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-gold-500 focus:ring-2 focus:ring-gold-500/20 outline-none transition" placeholder="08x-xxx-xxxx">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-bold text-slate-700 mb-2">อีเมล (ใช้สำหรับเข้าสู่ระบบ)</label>
                                <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" class="w-full px-4 py-3 bg-slate-100 border border-slate-200 rounded-xl text-slate-500 cursor-not-allowed" disabled>
                            </div>
                        </div>

                        <div class="pt-4 border-t border-slate-100 flex justify-end">
                            <button type="submit" class="bg-slate-900 text-white px-8 py-3 rounded-xl font-bold hover:bg-gold-500 hover:text-slate-900 transition shadow-lg transform active:scale-95">
                                บันทึกการเปลี่ยนแปลง
                            </button>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>
</div>

<script>
function previewImage(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('preview-img').src = e.target.result;
            document.getElementById('preview-img').classList.remove('hidden');
            if(document.getElementById('upload-placeholder')) {
                document.getElementById('upload-placeholder').classList.add('hidden');
            }
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>