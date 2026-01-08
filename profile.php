<?php
session_start();
require_once 'config/db.php';

// เช็คการล็อกอิน
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$msg_script = "";

// --- 1. ส่วนบันทึกข้อมูล (PHP Logic) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fullname = trim($_POST['fullname']);
    $phone = trim($_POST['phone']);
    
    try {
        // 1.1 อัปเดตข้อมูลทั่วไป
        $sql = "UPDATE members SET fullname = ?, phone = ? WHERE id = ?";
        $params = [$fullname, $phone, $user_id];
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        // 1.2 จัดการรูปโปรไฟล์ (ถ้ามีการ Crop มา)
        if (!empty($_POST['cropped_image_data'])) {
            $data = $_POST['cropped_image_data'];
            
            // แปลง Base64 กลับเป็นไฟล์รูปภาพ
            list($type, $data) = explode(';', $data);
            list(, $data)      = explode(',', $data);
            $data = base64_decode($data);
            
            // ตั้งชื่อไฟล์ใหม่
            $new_filename = 'profile_' . $user_id . '_' . time() . '.png';
            $path = 'uploads/profiles/' . $new_filename;
            
            // สร้างโฟลเดอร์ถ้ายังไม่มี
            if (!file_exists('uploads/profiles')) {
                mkdir('uploads/profiles', 0777, true);
            }

            // บันทึกไฟล์ลง Server
            file_put_contents($path, $data);

            // อัปเดตชื่อไฟล์ลงฐานข้อมูล
            $stmt_img = $pdo->prepare("UPDATE members SET profile_image = ? WHERE id = ?");
            $stmt_img->execute([$new_filename, $user_id]);
            
            // อัปเดต Session รูปภาพด้วย (เพื่อให้ Header เปลี่ยนทันที)
            $_SESSION['profile_image'] = $new_filename;
        }

        // อัปเดต Session ชื่อ
        $_SESSION['fullname'] = $fullname;

        $msg_script = "<script>
            Swal.fire({
                icon: 'success',
                title: 'บันทึกสำเร็จ',
                text: 'อัปเดตข้อมูลและรูปโปรไฟล์เรียบร้อยแล้ว',
                confirmButtonColor: '#0f172a',
                timer: 2000
            });
        </script>";

    } catch (PDOException $e) {
        $msg_script = "<script>Swal.fire({icon: 'error', title: 'เกิดข้อผิดพลาด', text: '".$e->getMessage()."'});</script>";
    }
}

// --- 2. ดึงข้อมูลล่าสุดมาแสดง ---
$stmt = $pdo->prepare("SELECT * FROM members WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขข้อมูลส่วนตัว - The Library</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        gold: { 400: '#fbbf24', 500: '#f59e0b', 600: '#d97706' },
                        navy: { 800: '#1e293b', 900: '#0f172a' }
                    },
                    fontFamily: {
                        serif: ['"Playfair Display"', 'serif'],
                        sans: ['"Kanit"', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Kanit', sans-serif; background-color: #f8fafc; }
        .cropper-container { max-height: 500px; }
    </style>
</head>
<body>

    <?php 
    if (file_exists('header.php')) {
        require_once 'header.php';
    } elseif (file_exists('includes/header.php')) {
        require_once 'includes/header.php';
    } else {
        // กรณีหาไฟล์ไม่เจอ จะได้ไม่ Error จอขาว แต่จะไม่มีเมนู
        echo '<div class="bg-red-100 text-red-700 p-4 text-center">Warning: header.php not found</div>';
    }
    ?>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
            
            <div class="lg:col-span-1 space-y-6">
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 text-center">
                    <div class="w-24 h-24 mx-auto rounded-full p-1 border-2 border-gold-500 mb-4 overflow-hidden relative">
                         <?php 
                            $img_src = !empty($user['profile_image']) ? "uploads/profiles/" . $user['profile_image'] : "https://ui-avatars.com/api/?name=".urlencode($user['fullname'])."&background=0f172a&color=fbbf24";
                         ?>
                        <img src="<?php echo $img_src; ?>" class="w-full h-full object-cover rounded-full">
                        <div class="absolute bottom-0 right-0 w-4 h-4 bg-emerald-500 border-2 border-white rounded-full"></div>
                    </div>
                    <h3 class="font-bold text-lg text-navy-900"><?php echo htmlspecialchars($user['fullname']); ?></h3>
                    <p class="text-xs text-slate-500"><?php echo htmlspecialchars($user['email']); ?></p>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                    <a href="profile.php" class="flex items-center gap-3 px-6 py-4 bg-navy-900 text-white font-medium border-l-4 border-gold-500">
                        <i class="fa-regular fa-user"></i> ข้อมูลส่วนตัว
                    </a>
                    <a href="profile_addresses.php" class="flex items-center gap-3 px-6 py-4 text-slate-600 hover:bg-slate-50 transition border-b border-slate-50">
                        <i class="fa-solid fa-location-dot"></i> ที่อยู่จัดส่ง
                    </a>
                    <a href="change_password.php" class="flex items-center gap-3 px-6 py-4 text-slate-600 hover:bg-slate-50 transition border-b border-slate-50">
                        <i class="fa-solid fa-lock"></i> เปลี่ยนรหัสผ่าน
                    </a>
                    <a href="history.php" class="flex items-center gap-3 px-6 py-4 text-slate-600 hover:bg-slate-50 transition">
                        <i class="fa-regular fa-bell"></i> การแจ้งเตือน
                    </a>
                </div>
            </div>

            <div class="lg:col-span-3">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-8">
                    
                    <div class="flex items-center gap-3 mb-6 pb-6 border-b border-slate-100">
                        <div class="w-10 h-10 rounded-full bg-gold-100 text-gold-600 flex items-center justify-center">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </div>
                        <h2 class="text-2xl font-serif font-bold text-navy-900">แก้ไขข้อมูลส่วนตัว</h2>
                    </div>

                    <form action="" method="post" enctype="multipart/form-data" id="profileForm">
                        
                        <div class="flex flex-col sm:flex-row items-center gap-8 mb-8">
                            <div class="relative group cursor-pointer" onclick="document.getElementById('fileInput').click()">
                                <div class="w-32 h-32 rounded-2xl overflow-hidden shadow-md border-4 border-white ring-2 ring-slate-100 relative">
                                    <img id="mainPreview" src="<?php echo $img_src; ?>" class="w-full h-full object-cover transition duration-300 group-hover:scale-110">
                                    
                                    <div class="absolute inset-0 bg-navy-900/50 flex flex-col items-center justify-center text-white opacity-0 group-hover:opacity-100 transition duration-300">
                                        <i class="fa-solid fa-camera text-2xl mb-1"></i>
                                        <span class="text-xs font-light">เปลี่ยนรูป</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flex-1 text-center sm:text-left">
                                <h4 class="font-bold text-navy-900 mb-1">รูปโปรไฟล์</h4>
                                <p class="text-sm text-slate-500 mb-3">คลิกที่รูปเพื่ออัปโหลดใหม่ (รองรับ JPG, PNG)</p>
                                <button type="button" onclick="document.getElementById('fileInput').click()" class="text-sm bg-slate-100 hover:bg-slate-200 text-navy-900 px-4 py-2 rounded-lg transition font-medium">
                                    เลือกรูปภาพ...
                                </button>
                                <input type="file" id="fileInput" accept="image/*" class="hidden">
                                <input type="hidden" name="cropped_image_data" id="croppedImageData">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                            <div>
                                <label class="block text-sm font-bold text-navy-900 mb-2">ชื่อ - นามสกุล</label>
                                <input type="text" name="fullname" value="<?php echo htmlspecialchars($user['fullname']); ?>" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-gold-400 transition" required>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-navy-900 mb-2">เบอร์โทรศัพท์</label>
                                <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-gold-400 transition">
                            </div>
                        </div>

                        <div class="mb-8">
                            <label class="block text-sm font-bold text-navy-900 mb-2">อีเมล (ใช้สำหรับเข้าสู่ระบบ)</label>
                            <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" class="w-full bg-slate-100 border border-slate-200 rounded-xl px-4 py-3 text-slate-500 cursor-not-allowed" readonly>
                        </div>

                        <div class="flex justify-end pt-6 border-t border-slate-100">
                            <button type="submit" class="bg-navy-900 text-white px-8 py-3.5 rounded-xl font-bold hover:bg-navy-800 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition duration-300">
                                บันทึกการเปลี่ยนแปลง
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>

    <div id="cropModal" class="fixed inset-0 z-50 hidden bg-navy-900/90 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl w-full max-w-lg overflow-hidden shadow-2xl animate-fade-in-up">
            <div class="p-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                <h3 class="font-bold text-navy-900 flex items-center gap-2">
                    <i class="fa-solid fa-crop-simple text-gold-500"></i> จัดระเบียบรูปโปรไฟล์
                </h3>
                <button onclick="closeCropModal()" class="text-slate-400 hover:text-red-500 transition"><i class="fa-solid fa-xmark text-xl"></i></button>
            </div>
            
            <div class="p-6">
                <div class="h-80 w-full bg-slate-900 rounded-lg overflow-hidden mb-4 relative">
                    <img id="imageToCrop" src="" class="max-w-full block">
                </div>
                <p class="text-xs text-slate-500 text-center mb-4">
                    <i class="fa-solid fa-hand-pointer"></i> ลากเพื่อเลื่อน | เลื่อนเมาส์เพื่อซูมเข้า-ออก
                </p>
                
                <div class="flex gap-3 justify-end">
                    <button type="button" onclick="closeCropModal()" class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 font-bold hover:bg-slate-50 transition">ยกเลิก</button>
                    <button type="button" onclick="cropImage()" class="px-6 py-2.5 rounded-xl bg-gold-500 text-white font-bold hover:bg-gold-600 shadow-lg transition">
                        <i class="fa-solid fa-check mr-1"></i> ยืนยันรูปนี้
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let cropper;
        const fileInput = document.getElementById('fileInput');
        const cropModal = document.getElementById('cropModal');
        const imageToCrop = document.getElementById('imageToCrop');
        const mainPreview = document.getElementById('mainPreview');
        const croppedImageData = document.getElementById('croppedImageData');

        // 1. เมื่อเลือกไฟล์
        fileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    imageToCrop.src = event.target.result;
                    cropModal.classList.remove('hidden');
                    
                    if (cropper) { cropper.destroy(); }
                    
                    cropper = new Cropper(imageToCrop, {
                        aspectRatio: 1,
                        viewMode: 1,
                        dragMode: 'move',
                        autoCropArea: 0.8,
                        background: false,
                        zoomable: true,
                        movable: true,
                    });
                };
                reader.readAsDataURL(file);
            }
        });

        // 2. เมื่อกดยืนยัน (Crop)
        function cropImage() {
            if (cropper) {
                const canvas = cropper.getCroppedCanvas({ width: 400, height: 400 });
                const base64Image = canvas.toDataURL('image/png');
                mainPreview.src = base64Image;
                croppedImageData.value = base64Image;
                closeCropModal();
            }
        }

        function closeCropModal() {
            cropModal.classList.add('hidden');
            fileInput.value = ''; 
        }

        cropModal.addEventListener('click', function(e) {
            if (e.target === cropModal) {
                closeCropModal();
            }
        });
    </script>
    
    <?php echo $msg_script; ?>
</body>
</html>